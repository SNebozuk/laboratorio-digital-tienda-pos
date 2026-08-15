"""Genera descripciones limpias a partir de una exportación de Tienda Nube.

Tienda Nube exporta el campo ``Descripción`` como HTML con estilos propios del
editor. La aplicación almacena texto simple, por lo que este conversor conserva
la estructura útil (párrafos, títulos, listas y saltos de línea) y descarta
solamente los estilos heredados.
"""

from __future__ import annotations

import argparse
import csv
import html
import json
import re
from html.parser import HTMLParser
from pathlib import Path


class DescriptionParser(HTMLParser):
    """Pasa HTML editorial a texto legible, sin estilos de Tienda Nube."""

    BLOCK_TAGS = {"p", "div", "section", "article", "h1", "h2", "h3", "h4", "h5", "h6"}

    def __init__(self) -> None:
        super().__init__(convert_charrefs=True)
        self.parts: list[str] = []

    def newline(self) -> None:
        if not self.parts or self.parts[-1] != "\n":
            self.parts.append("\n")

    def handle_starttag(self, tag: str, attrs: list[tuple[str, str | None]]) -> None:
        tag = tag.lower()
        if tag in self.BLOCK_TAGS:
            self.newline()
        elif tag == "br":
            self.newline()
        elif tag == "li":
            self.newline()
            self.parts.append("• ")

    def handle_endtag(self, tag: str) -> None:
        if tag.lower() in self.BLOCK_TAGS | {"li"}:
            self.newline()

    def handle_data(self, data: str) -> None:
        self.parts.append(data.replace("\xa0", " "))

    def text(self) -> str:
        raw = html.unescape("".join(self.parts)).replace("\r", "")
        lines = [re.sub(r"[ \t]+", " ", line).strip() for line in raw.split("\n")]
        compact: list[str] = []
        for line in lines:
            if line or (compact and compact[-1] != ""):
                compact.append(line)
        while compact and not compact[-1]:
            compact.pop()
        merged: list[str] = []
        index = 0
        while index < len(compact):
            line = compact[index]
            if line in {"•", "-", "–"} and index + 1 < len(compact) and compact[index + 1]:
                merged.append(f"{line} {compact[index + 1]}")
                index += 2
                continue
            merged.append(line)
            index += 1
        return "\n".join(merged)


def read_rows(source: Path) -> list[dict[str, str]]:
    # Las exportaciones actuales de Tienda Nube usan Windows-1252 y punto y coma.
    # utf-8-sig queda como alternativa para exportaciones futuras.
    payload = source.read_bytes()
    for encoding in ("cp1252", "utf-8-sig"):
        try:
            content = payload.decode(encoding)
        except UnicodeDecodeError:
            continue
        reader = csv.DictReader(content.splitlines(), delimiter=";")
        if reader.fieldnames and "Nombre" in reader.fieldnames and "Descripción" in reader.fieldnames:
            return list(reader)
    raise ValueError("No se reconocieron las columnas de la exportación de Tienda Nube.")


def clean_description(value: str) -> str:
    parser = DescriptionParser()
    parser.feed(value or "")
    parser.close()
    return parser.text()


def main() -> None:
    parser = argparse.ArgumentParser(description="Prepara el mapa de descripciones de Tienda Nube.")
    parser.add_argument("source", type=Path)
    parser.add_argument("output", type=Path)
    args = parser.parse_args()

    descriptions: dict[str, str] = {}
    descriptions_by_sku: dict[str, str] = {}
    for row in read_rows(args.source):
        name = (row.get("Nombre") or "").strip().upper()
        sku = (row.get("SKU") or "").strip().upper()
        description = clean_description(row.get("Descripción") or "")
        # También conservamos una descripción vacía: significa que ese producto
        # no la tiene en Tienda Nube y evita heredar un texto viejo o truncado.
        if name and (name not in descriptions or (not descriptions[name] and description)):
            descriptions[name] = description
        if sku and (sku not in descriptions_by_sku or (not descriptions_by_sku[sku] and description)):
            descriptions_by_sku[sku] = description

    args.output.parent.mkdir(parents=True, exist_ok=True)
    args.output.write_text(
        json.dumps(
            {"by_name": descriptions, "by_sku": descriptions_by_sku},
            ensure_ascii=False,
            indent=2,
        )
        + "\n",
        encoding="utf-8",
    )
    print(
        f"Productos revisados: {len(descriptions)} | "
        f"con descripción: {sum(bool(value) for value in descriptions.values())} | "
        f"SKU revisados: {len(descriptions_by_sku)}"
    )


if __name__ == "__main__":
    main()
