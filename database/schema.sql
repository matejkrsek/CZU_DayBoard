-- DayBoard – schéma SQLite databáze.
-- Databáze se vytvoří automaticky při prvním spuštění (viz src/Core/Database.php).
-- Po změně schématu smažte storage/dayboard.sqlite nebo spusťte: php bin/reset-db.php

CREATE TABLE users (
    id                    INTEGER PRIMARY KEY AUTOINCREMENT,
    login                 TEXT NOT NULL UNIQUE COLLATE NOCASE,   -- e-mail nebo uživatelské jméno
    display_name          TEXT NOT NULL,
    password_hash         TEXT NOT NULL,
    role                  TEXT NOT NULL CHECK (role IN ('admin', 'member', 'guest')),
    must_change_password  INTEGER NOT NULL DEFAULT 0,
    -- osobní nastavení
    theme                 TEXT NOT NULL DEFAULT 'system',        -- light | dark | system
    density               TEXT NOT NULL DEFAULT 'standard',      -- compact | standard | comfortable
    home_page             TEXT NOT NULL DEFAULT 'dashboard',     -- dashboard | settings | household
    show_greeting         INTEGER NOT NULL DEFAULT 1,
    show_scope_labels     INTEGER NOT NULL DEFAULT 1,
    confirm_remove        INTEGER NOT NULL DEFAULT 1,
    share_location        INTEGER NOT NULL DEFAULT 1,
    origin_destination_id INTEGER,                               -- NULL = cesty začínají doma
    rss_url               TEXT,                                  -- NULL = výchozí zdroj domácnosti
    layout_saved          INTEGER NOT NULL DEFAULT 0,            -- 0 = výchozí rozložení
    -- připojený účet Google (tokeny jsou uložené šifrovaně)
    google_email          TEXT,
    google_access_token   TEXT,
    google_refresh_token  TEXT,
    google_token_expires  INTEGER,
    created_at            TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Sdílená nastavení domácnosti (klíč – hodnota)
CREATE TABLE settings (
    key   TEXT PRIMARY KEY,
    value TEXT NOT NULL
);

-- Které widgety povolil správce členům a hostům/dětem
CREATE TABLE widget_access (
    widget_key      TEXT PRIMARY KEY,
    members_enabled INTEGER NOT NULL DEFAULT 1,
    guest_enabled   INTEGER NOT NULL DEFAULT 0
);

-- Rozložení nástěnky: pozice a velikost widgetu v mřížce (v buňkách)
CREATE TABLE layouts (
    user_id    INTEGER NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    widget_key TEXT NOT NULL,
    x          INTEGER NOT NULL,
    y          INTEGER NOT NULL,
    w          INTEGER NOT NULL,
    h          INTEGER NOT NULL,
    PRIMARY KEY (user_id, widget_key)
);

-- Cíle cest pro widget Doprava autem (nejvýše 3 na uživatele)
CREATE TABLE destinations (
    id       INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id  INTEGER NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    label    TEXT NOT NULL,
    address  TEXT NOT NULL,
    lat      REAL,
    lon      REAL,
    position INTEGER NOT NULL DEFAULT 0
);

-- Oblíbené zastávky pro widget Odjezdy MHD
CREATE TABLE stops (
    id       INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id  INTEGER NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    name     TEXT NOT NULL,
    position INTEGER NOT NULL DEFAULT 0
);

-- Sdílený nákupní seznam domácnosti
CREATE TABLE shopping_items (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    name       TEXT NOT NULL,
    done       INTEGER NOT NULL DEFAULT 0,
    created_by INTEGER REFERENCES users (id) ON DELETE SET NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Oblíbené rozhlasové stanice domácnosti
CREATE TABLE radio_stations (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    name         TEXT NOT NULL,
    stream_url   TEXT NOT NULL,
    station_uuid TEXT,
    position     INTEGER NOT NULL DEFAULT 0
);

-- Trvalé přihlášení ("Zůstat přihlášen")
CREATE TABLE remember_tokens (
    selector   TEXT PRIMARY KEY,
    token_hash TEXT NOT NULL,
    user_id    INTEGER NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    expires_at INTEGER NOT NULL
);

-- Neúspěšné pokusy o přihlášení (ochrana proti hádání hesel)
CREATE TABLE login_attempts (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    login        TEXT NOT NULL COLLATE NOCASE,
    ip           TEXT NOT NULL,
    attempted_at INTEGER NOT NULL
);

CREATE INDEX idx_destinations_user ON destinations (user_id, position);
CREATE INDEX idx_stops_user ON stops (user_id, position);
CREATE INDEX idx_login_attempts ON login_attempts (login, attempted_at);
