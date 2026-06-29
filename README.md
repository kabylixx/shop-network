# Shop Network

Server-side API (PHP / Symfony, **without API Platform**) to manage a shop
network, a product catalog, and their stock.

Stack: **PHP 8.5 / Symfony 8.1 / MySQL 8.4**, served by **FrankenPHP** in a
single container. Pragmatic hexagonal architecture, organized into modules
(`Catalog`, `Network`, `Inventory`) within a single bounded context.

## Context & needs

A retailer operates a **network of physical shops** and wants to expose, through an
API, the building blocks its applications (back-office, website, mobile app) need:

- **Catalog** — reference products (name, image) and browse them
  (search by name, sorting, pagination).
- **Shop network** — register shops (address, **geolocation**,
  manager) and **search them by proximity** ("shops open around me").
- **Stock by shop** — know *how much of which product is available
  in which shop*, update it, and answer the key customer question:
  **"where can I find this product, near here?"** (`/api/products/{id}/availability`).

The split into three modules (`Catalog`, `Network`, `Inventory`) follows these three
business needs.

## One-command startup

Prerequisites: Docker + Docker Compose.

```bash
make demo
```

This command builds the image, brings up the stack (FrankenPHP + MySQL), installs
the dependencies, applies the migrations **and loads the demo data**
(catalog, shop network, stock). The API is then available,
ready to explore, at <http://localhost:8080>.

> `make start` does the same thing **without** the fixtures: it's the idempotent
> day-to-day startup (a restart does not wipe the database). `make demo`
> adds the (destructive) loading of demo data on top.

| Command                | Effect                                                     |
| ---------------------- | ---------------------------------------------------------- |
| `make demo`            | Full startup **with** demo data (start + fixtures)         |
| `make start`           | Build + up + install + migrate (no fixtures, idempotent)   |
| `make test`            | Prepares the test database then runs the PHPUnit suite     |
| `make migrate`         | Applies the Doctrine migrations                            |
| `make fixtures`        | (Re)loads the demo data (catalog, shops, stock)            |
| `make clear-cache`     | Clears the Symfony cache (dev env)                         |
| `make clear-testcache` | Clears the Symfony cache (test env)                        |
| `make down`            | Stops and removes the containers                           |
| `make sh`              | Opens a shell in the application container                 |

## API

JSON REST API: paginated listings, errors in **RFC 7807** format
(`application/problem+json`). The detailed contract of each endpoint (body,
parameters, codes, request/response examples) is in the **[API reference](docs/api.md)**.

| Method | Route | Description |
| ------- | ----- | ----------- |
| `POST` | `/api/products` | Create a product |
| `GET` | `/api/products` | List the catalog (pagination, search, sorting) |
| `POST` | `/api/managers` | Create a manager |
| `POST` | `/api/shops` | Create a shop |
| `GET` | `/api/shops` | Search shops (name + geolocation) |
| `PUT` | `/api/products/{id}/stock` | Set a product's stock |
| `GET` | `/api/stock` | Show / filter stock by shop(s) |
| `GET` | `/api/shops/{id}/products` | Products of a shop |

→ **Full detail: [`docs/api.md`](docs/api.md)**

## Quality

- **Tests**: `make test` (PHPUnit; test database isolated per transaction via
  `dama/doctrine-test-bundle`).
- **Analysis**: PHPStan (level 8), PHP-CS-Fixer (`@Symfony`) and Deptrac
  (hexagonal boundaries Domain → Application → Infrastructure), orchestrated by
  GrumPHP (`vendor/bin/grumphp run`) and wired into a pre-commit hook.

## Architecture in brief

Server-side API **without API Platform**, in **pragmatic hexagonal** style: a single
bounded context organized into modules (`Catalog`, `Network`, `Inventory`, `Shared`),
each in **Domain / Application / Infrastructure** layers (boundaries verified
by Deptrac).

- **Typed identities per aggregate** (`ProductId`, `ShopId`…) as **UUID v7**,
  generated in the Application layer.
- **References by identity, no foreign key**: the existence of a related
  aggregate is verified in the handler → explicit **`404`** (never a `500` on a
  foreign key).
- **CQRS-light**: writes go through a *repository* (Domain) that hydrates
  the aggregate; reads through a *finder* (Application) that returns **read
  models** (`ProductView`, `ShopView`, `StockView`), with pagination shared in
  `Shared`.
- **Geolocation search** delegated to MySQL (`ST_Distance_Sphere`), the **single
  source of truth**; search by name accent- and case-insensitive via the MySQL
  collation — no extension, no spatial library.
- **`Stock`** is an independent aggregate (referenced by identity), written as a
  **per-couple upsert** and read **broken down by shop, never summed**.
- **Errors** in **RFC 7807** format (`application/problem+json`); controlled output
  representations, never the raw aggregate.

### Going further

- **Architecture decisions** — the *why* of each choice (and the
  alternatives discarded) is recorded in [ADRs](docs/adr/): 8 decisions, ordered
  by their appearance during development.
- **Possible evolutions** — auth/`Identity`, command bus, error i18n, spatial
  index / PostGIS, geocoding by address… are detailed in the
  [roadmap](docs/roadmap.md).
