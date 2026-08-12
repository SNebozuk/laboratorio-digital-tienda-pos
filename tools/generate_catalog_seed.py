from __future__ import annotations

import argparse
import csv
import re
import unicodedata
from collections import OrderedDict
from decimal import Decimal, InvalidOperation, ROUND_HALF_UP
from pathlib import Path


def sql_text(value: str) -> str:
    return "'" + value.replace("'", "''") + "'"


def slug(value: str) -> str:
    normalized = unicodedata.normalize("NFKD", value)
    ascii_value = normalized.encode("ascii", "ignore").decode("ascii").lower()
    return re.sub(r"[^a-z0-9]+", "-", ascii_value).strip("-") or "general"


def non_negative_int(value: str, field: str, row_number: int) -> int:
    try:
        parsed = int(value)
    except ValueError as exc:
        raise ValueError(f"Fila {row_number}: {field} no es un entero") from exc
    if parsed < 0:
        raise ValueError(f"Fila {row_number}: {field} no puede ser negativo")
    return parsed


def price_cents(value: str, row_number: int) -> int:
    try:
        parsed = Decimal(value).quantize(Decimal("0.01"), rounding=ROUND_HALF_UP)
    except InvalidOperation as exc:
        raise ValueError(f"Fila {row_number}: precio inválido") from exc
    if parsed < 0:
        raise ValueError(f"Fila {row_number}: el precio no puede ser negativo")
    return int(parsed * 100)


def load_catalog(source: Path) -> tuple[list[str], list[dict[str, object]]]:
    categories: list[str] = []
    category_seen: set[str] = set()
    products: OrderedDict[tuple[str, str], dict[str, object]] = OrderedDict()
    seen_skus: set[str] = set()

    with source.open("r", encoding="utf-8-sig", newline="") as handle:
        reader = csv.DictReader(handle)
        required = {
            "sku",
            "name",
            "category",
            "variant",
            "price",
            "stock",
            "min_stock",
            "image_url",
            "description",
        }
        if set(reader.fieldnames or []) != required:
            missing = sorted(required.difference(reader.fieldnames or []))
            extra = sorted(set(reader.fieldnames or []).difference(required))
            raise ValueError(f"Columnas inválidas. Faltan={missing}; sobran={extra}")

        for row_number, row in enumerate(reader, start=2):
            sku = row["sku"].strip().upper()
            name = row["name"].strip().upper()
            category = row["category"].strip().upper() or "GENERAL"
            variant = row["variant"].strip() or "Única"
            if not sku or not name:
                raise ValueError(f"Fila {row_number}: SKU y nombre son obligatorios")
            if sku in seen_skus:
                raise ValueError(f"Fila {row_number}: SKU duplicado {sku}")
            seen_skus.add(sku)

            if category not in category_seen:
                category_seen.add(category)
                categories.append(category)

            key = (category, name)
            if key not in products:
                products[key] = {
                    "category": category,
                    "name": name,
                    "description": row["description"].strip(),
                    "image_url": row["image_url"].strip(),
                    "variants": [],
                }
            else:
                if not products[key]["description"] and row["description"].strip():
                    products[key]["description"] = row["description"].strip()
                if not products[key]["image_url"] and row["image_url"].strip():
                    products[key]["image_url"] = row["image_url"].strip()

            variants = products[key]["variants"]
            assert isinstance(variants, list)
            variants.append(
                {
                    "name": variant,
                    "sku": sku,
                    "price_cents": price_cents(row["price"].strip(), row_number),
                    "stock_on_hand": non_negative_int(
                        row["stock"].strip(), "stock", row_number
                    ),
                    "min_stock": non_negative_int(
                        row["min_stock"].strip(), "min_stock", row_number
                    ),
                }
            )

    return categories, list(products.values())


def build_sql(categories: list[str], products: list[dict[str, object]]) -> str:
    lines = [
        "-- Generado desde catalogo-catalogo-anterior-corregido.csv.",
        "-- Se aplica una sola vez mediante schema_migrations(version = 2).",
        "",
    ]

    for sort_order, category in enumerate(categories):
        lines.append(
            "INSERT OR IGNORE INTO categories(name, slug, sort_order, active) "
            f"VALUES({sql_text(category)}, {sql_text(slug(category))}, "
            f"{sort_order}, 1);"
        )

    lines.append("")
    product_sort: dict[str, int] = {}
    for product in products:
        category = str(product["category"])
        name = str(product["name"])
        description = str(product["description"])
        image_url = str(product["image_url"])
        sort_order = product_sort.get(category, 0)
        product_sort[category] = sort_order + 1

        lines.extend(
            [
                "",
                f"-- {name}",
                "INSERT INTO products("
                "category_id, name, description, image_path, active, sort_order"
                ")",
                "SELECT c.id, "
                f"{sql_text(name)}, {sql_text(description)}, "
                f"{sql_text(image_url)}, 1, {sort_order}",
                "FROM categories c",
                f"WHERE c.name = {sql_text(category)} COLLATE NOCASE",
                "  AND NOT EXISTS (",
                "      SELECT 1 FROM products p",
                "      WHERE p.category_id = c.id",
                f"        AND p.name = {sql_text(name)} COLLATE NOCASE",
                "  );",
            ]
        )

        variants = product["variants"]
        assert isinstance(variants, list)
        for variant_sort, variant in enumerate(variants):
            assert isinstance(variant, dict)
            lines.extend(
                [
                    "INSERT OR IGNORE INTO product_variants(",
                    "    product_id, name, sku, barcode, price_cents,",
                    "    stock_on_hand, stock_reserved, min_stock, sort_order, active",
                    ")",
                    "SELECT p.id, "
                    f"{sql_text(str(variant['name']))}, "
                    f"{sql_text(str(variant['sku']))}, NULL, "
                    f"{int(variant['price_cents'])}, "
                    f"{int(variant['stock_on_hand'])}, 0, "
                    f"{int(variant['min_stock'])}, {variant_sort}, 1",
                    "FROM products p",
                    "JOIN categories c ON c.id = p.category_id",
                    f"WHERE c.name = {sql_text(category)} COLLATE NOCASE",
                    f"  AND p.name = {sql_text(name)} COLLATE NOCASE",
                    "ORDER BY p.id",
                    "LIMIT 1;",
                ]
            )

    lines.append("")
    return "\n".join(lines)


def main() -> None:
    parser = argparse.ArgumentParser(
        description="Genera el catálogo inicial idempotente para SQLite."
    )
    parser.add_argument("source", type=Path)
    parser.add_argument("output", type=Path)
    args = parser.parse_args()

    categories, products = load_catalog(args.source)
    sql = build_sql(categories, products)
    args.output.parent.mkdir(parents=True, exist_ok=True)
    args.output.write_text(sql, encoding="utf-8", newline="\n")

    variants = sum(len(product["variants"]) for product in products)
    print(
        f"Categorías: {len(categories)} | "
        f"Productos: {len(products)} | Variantes: {variants}"
    )


if __name__ == "__main__":
    main()
