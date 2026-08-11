PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS schema_migrations (
    version INTEGER PRIMARY KEY,
    applied_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL COLLATE NOCASE UNIQUE,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL CHECK (role IN ('admin', 'seller')),
    active INTEGER NOT NULL DEFAULT 1 CHECK (active IN (0, 1)),
    last_login_at TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL COLLATE NOCASE UNIQUE,
    slug TEXT NOT NULL COLLATE NOCASE UNIQUE,
    parent_id INTEGER REFERENCES categories(id) ON DELETE SET NULL,
    sort_order INTEGER NOT NULL DEFAULT 0,
    active INTEGER NOT NULL DEFAULT 1 CHECK (active IN (0, 1)),
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    category_id INTEGER REFERENCES categories(id) ON DELETE SET NULL,
    name TEXT NOT NULL COLLATE NOCASE,
    description TEXT NOT NULL DEFAULT '',
    image_path TEXT,
    active INTEGER NOT NULL DEFAULT 1 CHECK (active IN (0, 1)),
    sort_order INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_products_catalog
    ON products(active, category_id, sort_order, name);

CREATE TABLE IF NOT EXISTS product_variants (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    name TEXT NOT NULL,
    sku TEXT NOT NULL COLLATE NOCASE UNIQUE,
    barcode TEXT COLLATE NOCASE UNIQUE,
    image_path TEXT,
    price_cents INTEGER NOT NULL CHECK (price_cents >= 0),
    stock_on_hand INTEGER NOT NULL DEFAULT 0 CHECK (stock_on_hand >= 0),
    stock_reserved INTEGER NOT NULL DEFAULT 0 CHECK (
        stock_reserved >= 0 AND stock_reserved <= stock_on_hand
    ),
    min_stock INTEGER NOT NULL DEFAULT 0 CHECK (min_stock >= 0),
    sort_order INTEGER NOT NULL DEFAULT 0,
    active INTEGER NOT NULL DEFAULT 1 CHECK (active IN (0, 1)),
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_variants_product
    ON product_variants(product_id, active, sort_order, name);

CREATE INDEX IF NOT EXISTS idx_variants_barcode
    ON product_variants(barcode);

CREATE TABLE IF NOT EXISTS orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    public_number TEXT NOT NULL UNIQUE,
    channel TEXT NOT NULL CHECK (channel IN ('web', 'whatsapp', 'pos')),
    status TEXT NOT NULL CHECK (status IN (
        'pending_payment',
        'payment_reported',
        'paid_prepare',
        'ready_pickup',
        'delivered',
        'rejected',
        'cancelled'
    )),
    customer_name TEXT NOT NULL,
    customer_email TEXT,
    customer_phone TEXT,
    subtotal_cents INTEGER NOT NULL CHECK (subtotal_cents >= 0),
    total_cents INTEGER NOT NULL CHECK (total_cents >= 0),
    payment_method TEXT NOT NULL,
    payment_deadline_at TEXT,
    rejection_deadline_at TEXT,
    upload_token_hash TEXT,
    stock_reserved_at TEXT,
    delivered_at TEXT,
    cancelled_at TEXT,
    archived_at TEXT,
    archived_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
    created_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_orders_status_created
    ON orders(status, created_at);

CREATE INDEX IF NOT EXISTS idx_orders_deadlines
    ON orders(status, payment_deadline_at, rejection_deadline_at);

CREATE INDEX IF NOT EXISTS idx_orders_archived_created
    ON orders(archived_at, created_at);

CREATE TABLE IF NOT EXISTS order_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    variant_id INTEGER NOT NULL REFERENCES product_variants(id) ON DELETE RESTRICT,
    product_name TEXT NOT NULL,
    variant_name TEXT NOT NULL,
    sku TEXT NOT NULL,
    quantity INTEGER NOT NULL CHECK (quantity > 0),
    unit_price_cents INTEGER NOT NULL CHECK (unit_price_cents >= 0),
    line_total_cents INTEGER NOT NULL CHECK (line_total_cents >= 0),
    UNIQUE(order_id, variant_id)
);

CREATE INDEX IF NOT EXISTS idx_order_items_order
    ON order_items(order_id);

CREATE TABLE IF NOT EXISTS payment_proofs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    storage_key TEXT NOT NULL UNIQUE,
    original_name TEXT NOT NULL,
    mime_type TEXT NOT NULL CHECK (mime_type IN (
        'image/jpeg',
        'image/png',
        'application/pdf'
    )),
    size_bytes INTEGER NOT NULL CHECK (size_bytes > 0),
    sha256 TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'reported' CHECK (status IN (
        'reported',
        'approved',
        'rejected'
    )),
    reviewed_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
    reviewed_at TEXT,
    review_note TEXT,
    ai_status TEXT NOT NULL DEFAULT 'not_run' CHECK (ai_status IN (
        'not_run',
        'disabled',
        'prevalidated',
        'review',
        'failed'
    )),
    ai_risk_level TEXT CHECK (ai_risk_level IN ('low', 'medium', 'high')),
    ai_summary TEXT,
    ai_result_json TEXT,
    ai_model TEXT,
    ai_checked_at TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_payment_proofs_order
    ON payment_proofs(order_id, created_at);

CREATE TABLE IF NOT EXISTS order_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    actor_user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    event_type TEXT NOT NULL,
    from_status TEXT,
    to_status TEXT,
    detail TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_order_events_order
    ON order_events(order_id, created_at);

CREATE TABLE IF NOT EXISTS stock_movements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    variant_id INTEGER NOT NULL REFERENCES product_variants(id) ON DELETE RESTRICT,
    order_id INTEGER REFERENCES orders(id) ON DELETE SET NULL,
    actor_user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    on_hand_delta INTEGER NOT NULL DEFAULT 0,
    reserved_delta INTEGER NOT NULL DEFAULT 0,
    reason TEXT NOT NULL,
    reference TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CHECK (on_hand_delta <> 0 OR reserved_delta <> 0)
);

CREATE INDEX IF NOT EXISTS idx_stock_movements_variant
    ON stock_movements(variant_id, created_at);

CREATE TABLE IF NOT EXISTS cash_sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    opened_by INTEGER NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    closed_by INTEGER REFERENCES users(id) ON DELETE RESTRICT,
    opening_cents INTEGER NOT NULL CHECK (opening_cents >= 0),
    counted_closing_cents INTEGER CHECK (counted_closing_cents >= 0),
    expected_closing_cents INTEGER CHECK (expected_closing_cents >= 0),
    difference_cents INTEGER,
    status TEXT NOT NULL DEFAULT 'open' CHECK (status IN ('open', 'closed')),
    opened_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    closed_at TEXT
);

CREATE UNIQUE INDEX IF NOT EXISTS idx_single_open_cash_session
    ON cash_sessions(status)
    WHERE status = 'open';

CREATE TABLE IF NOT EXISTS cash_movements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    cash_session_id INTEGER NOT NULL REFERENCES cash_sessions(id) ON DELETE RESTRICT,
    order_id INTEGER REFERENCES orders(id) ON DELETE SET NULL,
    actor_user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    type TEXT NOT NULL CHECK (type IN ('sale', 'income', 'expense')),
    amount_cents INTEGER NOT NULL CHECK (amount_cents > 0),
    payment_method TEXT NOT NULL,
    detail TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_cash_movements_session
    ON cash_movements(cash_session_id, created_at);

CREATE TABLE IF NOT EXISTS settings (
    key TEXT PRIMARY KEY,
    value TEXT NOT NULL,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS mail_queue (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER REFERENCES orders(id) ON DELETE SET NULL,
    recipient TEXT NOT NULL,
    subject TEXT NOT NULL,
    template TEXT NOT NULL,
    payload_json TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'pending' CHECK (status IN (
        'pending',
        'sending',
        'sent',
        'failed'
    )),
    attempts INTEGER NOT NULL DEFAULT 0 CHECK (attempts >= 0),
    last_error TEXT,
    available_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sent_at TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS customer_notification_queue (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    event_type TEXT NOT NULL,
    customer_phone TEXT,
    customer_email TEXT,
    status TEXT NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'sent', 'failed')),
    payload_json TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sent_at TEXT,
    last_error TEXT
);

CREATE INDEX IF NOT EXISTS idx_customer_notifications_pending
    ON customer_notification_queue(status, created_at);

INSERT OR IGNORE INTO settings(key, value) VALUES
    ('store_name', 'Laboratorio Digital'),
    ('sales_email', 'ventas@laboratorio-digital.com.ar'),
    ('whatsapp_number', '5493415699338'),
    ('payment_window_minutes', '120'),
    ('rejected_retry_minutes', '120'),
    ('proof_max_bytes', '8388608'),
    ('bank_holder', 'Allessandra Lear · Banco Galicia'),
    ('bank_alias', 'labdigital'),
    ('bank_cbu', ''),
    ('pickup_address', ''),
    ('business_hours', 'Lunes a viernes de 9 a 17 h'),
    ('size_guide_intro', 'Las medidas son aproximadas. Si necesitas ayuda para elegir, escribinos por WhatsApp.'),
    ('size_guide_json', '[]');

INSERT OR IGNORE INTO schema_migrations(version) VALUES (1);
INSERT OR IGNORE INTO schema_migrations(version) VALUES (5);
INSERT OR IGNORE INTO schema_migrations(version) VALUES (6);
INSERT OR IGNORE INTO schema_migrations(version) VALUES (7);
INSERT OR IGNORE INTO schema_migrations(version) VALUES (8);
