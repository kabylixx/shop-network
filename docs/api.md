# API Reference

Detailed contract for each endpoint: request body, parameters, responses,
error codes and examples. Overview in the [README](../README.md#api); the *why*
behind the choices is in the [ADRs](adr/).

**REST / JSON** API. Cross-cutting conventions (pagination, error format) are
factored out in [Common conventions](#common-conventions) — endpoints only
refer to it for their specifics.

## Table of contents

- [Common conventions](#common-conventions) — pagination, errors (RFC 7807)
- [Catalog](#catalog) — `POST /api/products`, `GET /api/products`
- [Shop network](#shop-network) — `POST /api/managers`, `POST /api/shops`, `GET /api/shops`
- [Stock](#stock) — `PUT /api/products/{id}/stock`, `GET /api/stock`, `GET /api/shops/{id}/products`, `GET /api/products/{id}/availability`

## Common conventions

### Pagination

All listings are paginated and accept these (optional) parameters:

| Param   | Default | Description                |
| ------- | ------- | -------------------------- |
| `page`  | `1`     | Page number (≥ 1).         |
| `limit` | `20`    | Page size (1 to 100).      |

They return an envelope with metadata. A page beyond the last one returns
`items: []` with correct metadata (this is not an error).

```json
{
  "items": [],
  "page": 1,
  "limit": 20,
  "total": 0,
  "totalPages": 0
}
```

### Errors (RFC 7807)

Errors follow the [RFC 7807](https://datatracker.ietf.org/doc/html/rfc7807)
format (`application/problem+json`):

| Code | When |
| ---- | ---- |
| `400 Bad Request` | Malformed or mistyped JSON body. |
| `404 Not Found` | Resource (or related resource) referenced by a valid but non-existing identifier. |
| `422 Unprocessable Content` | Validation failure (missing field, out of bounds, invalid format…). The body lists the `violations`. |

Each `violation` carries a **stable** (machine-readable) `code` and a curated
`message`; `propertyPath` locates the offending field (e.g. `lines[0].quantity`).

```json
{
  "type": "about:blank",
  "title": "Unprocessable Content",
  "status": 422,
  "detail": "The request payload failed validation.",
  "violations": [
    {
      "propertyPath": "name",
      "code": "c1051bb4-d103-4f74-8988-acbcafc7fdc3",
      "message": "The product name is required."
    }
  ]
}
```

> **Validation takes precedence over existence**: a request that is both
> malformed *and* references a non-existing resource returns `422` (validation
> runs before the existence check).

## Catalog

### `POST /api/products` — Create a product

**Request body**

| Field        | Required | Description                       |
| ------------ | -------- | --------------------------------- |
| `name`       | yes      | Product name (1 to 255).          |
| `pictureUrl` | yes      | Photo URL (valid URL).            |

```http
POST /api/products
Content-Type: application/json

{
  "name": "Organic cotton shirt",
  "pictureUrl": "https://example.com/shirt.jpg"
}
```

**`201 Created`**

```json
{
  "id": "019f0df9-eef9-79e5-9d74-20e92f1721f7",
  "name": "Organic cotton shirt",
  "pictureUrl": "https://example.com/shirt.jpg"
}
```

**Errors**: `422` (`name` empty/too long, `pictureUrl` missing or invalid),
`400`.

### `GET /api/products` — List the catalog

Paginated catalog, with search by name and sort. Parameters in addition to
[pagination](#pagination):

| Param       | Default | Description                                                        |
| ----------- | ------- | ------------------------------------------------------------------ |
| `search`    | —       | Filter by name, **partial**, case- and accent-insensitive.         |
| `sort`      | `name`  | Sort field (`name`).                                               |
| `direction` | `asc`   | Sort direction (`asc` or `desc`).                                  |

```http
GET /api/products?search=dress&sort=name&direction=desc&page=1&limit=20
```

**`200 OK`**

```json
{
  "items": [
    { "id": "019f0f3c-ea98-7727-9d42-f4724f489ff4", "name": "Sandy Dress", "pictureUrl": "https://media.example.com/products/sandy-dress.jpg" },
    { "id": "019f0f3c-ea9a-7c98-a5f8-7109e2b6b40e", "name": "Andy Dress", "pictureUrl": "https://media.example.com/products/andy-dress.jpg" }
  ],
  "page": 1,
  "limit": 20,
  "total": 2,
  "totalPages": 1
}
```

**Errors**: `422` (`page`/`limit` out of bounds, `sort` not whitelisted).

## Shop network

### `POST /api/managers` — Create a manager

Registers a manager who can then be attached to one or more shops.

| Field  | Required | Description                    |
| ------ | -------- | ------------------------------ |
| `name` | yes      | Manager name (1 to 150).       |

```http
POST /api/managers
Content-Type: application/json

{ "name": "Jane Cooper" }
```

**`201 Created`**

```json
{
  "id": "019f0fbb-99d7-7004-be48-1c77a6b3f41c",
  "name": "Jane Cooper"
}
```

**Errors**: `422` (`name` empty or > 150), `400`.

### `POST /api/shops` — Create a shop

Creates a shop and associates it with an **existing** manager.

| Field       | Required | Description                              |
| ----------- | -------- | ---------------------------------------- |
| `name`      | yes      | Shop name (≤ 150).                       |
| `address`   | yes      | Postal address, free text (≤ 255).       |
| `latitude`  | yes      | Latitude, bounded to `[-90, 90]`.        |
| `longitude` | yes      | Longitude, bounded to `[-180, 180]`.     |
| `managerId` | yes      | UUID of an **existing** manager.         |
| `status`    | no       | `open` (default) or `closed`.            |

```http
POST /api/shops
Content-Type: application/json

{
  "name": "Marais Boutique",
  "address": "12 rue de Rivoli, Paris",
  "latitude": 48.8566,
  "longitude": 2.3522,
  "managerId": "019f0fbb-99d7-7004-be48-1c77a6b3f41c"
}
```

**`201 Created`**

```json
{
  "id": "019f0fbb-99fe-790a-9ef8-415b9d7d7e22",
  "name": "Marais Boutique",
  "address": "12 rue de Rivoli, Paris",
  "latitude": 48.8566,
  "longitude": 2.3522,
  "managerId": "019f0fbb-99d7-7004-be48-1c77a6b3f41c",
  "status": "open"
}
```

**Errors**: `422` (required field missing, coordinates out of bounds, `managerId`
not a valid UUID, `status` not in the list), `404` (`managerId` valid but
non-existing), `400`.

### `GET /api/shops` — Search shops

Paginated search by **name** and/or **geographic proximity**. `closed` shops are
excluded from the results. Parameters in addition to [pagination](#pagination):

| Param    | Default | Description                                                        |
| -------- | ------- | ------------------------------------------------------------------ |
| `search` | —       | Filter by name, **partial**, case- and accent-insensitive.         |
| `lat`    | —       | Latitude of the search center, bounded to `[-90, 90]`.             |
| `lng`    | —       | Longitude of the search center, bounded to `[-180, 180]`.          |
| `radius` | —       | Search radius **in meters** (> 0).                                 |

- `lat`, `lng`, `radius` form an **all-or-nothing trio**: providing one without
  the others returns `422`.
- **Without geolocation** → sorted by name ascending; `distance` is `null`.
- **With geolocation** → only shops **within the radius**, sorted from
  **nearest to farthest**, each with its `distance` (in meters). `search`
  combines with the geo filter.

```http
GET /api/shops?search=marais&lat=48.8566&lng=2.3522&radius=50000&page=1&limit=20
```

**`200 OK`**

```json
{
  "items": [
    {
      "id": "019f0fbb-99fe-790a-9ef8-415b9d7d7e22",
      "name": "Paris Marais",
      "address": "12 rue de Rivoli, 75004 Paris",
      "latitude": 48.8559,
      "longitude": 2.3601,
      "managerId": "019f0fbb-99d7-7004-be48-1c77a6b3f41c",
      "status": "open",
      "distance": 583.2
    }
  ],
  "page": 1,
  "limit": 20,
  "total": 1,
  "totalPages": 1
}
```

**Errors**: `422` (incomplete geo trio, coordinates out of bounds, `radius` ≤ 0,
invalid `page`/`limit`, `search` too long).

## Stock

### `PUT /api/products/{id}/stock` — Set the stock of a product

Updates, for a product, the available quantity in one or more shops.

- **Upsert semantics per `(shop, product)` couple**: each line creates or
  replaces the couple's quantity; shops **absent** from the body keep their
  stock.
- **All-or-nothing** operation: an unknown identifier rejects the entire request
  (no partial write).
- `{id}` is the UUID of an **existing** product (an identifier that is not a
  valid UUID returns `404` at routing).

**Request body** — JSON array of couples:

| Field      | Required | Description                                            |
| ---------- | -------- | ------------------------------------------------------ |
| `shopId`   | yes      | UUID of an **existing** shop, unique per request.      |
| `quantity` | yes      | Integer ≥ 0 (`0` = product listed as out of stock).    |

```http
PUT /api/products/019f0fbb-99fe-790a-9ef8-415b9d7d7e22/stock
Content-Type: application/json

[
  { "shopId": "019f0fbb-99d7-7004-be48-1c77a6b3f41c", "quantity": 12 },
  { "shopId": "019f0fbb-9a1b-71c2-be48-1c77a6b3f41c", "quantity": 0 }
]
```

**`200 OK`** — echo of the upserted couples (the product is in the URL):

```json
[
  { "shopId": "019f0fbb-99d7-7004-be48-1c77a6b3f41c", "quantity": 12 },
  { "shopId": "019f0fbb-9a1b-71c2-be48-1c77a6b3f41c", "quantity": 0 }
]
```

**Errors**: `422` (`quantity` < 0, `shopId` not a valid UUID, missing field, or
the same shop repeated), `404` (unknown product or ≥ 1 unknown shop), `400`.

### `GET /api/stock` — Show / filter stock by shop(s)

Paginated list of stock, **broken down by shop**: one line per `(shop, product)`
couple, **never summed**. Each line carries the product info (name, photo) joined
from the catalog. Parameters in addition to [pagination](#pagination):

| Param               | Default | Description                                                 |
| ------------------- | ------- | ----------------------------------------------------------- |
| `shopIds`           | —       | Comma-separated shop UUIDs; omitted = all.                  |
| `includeOutOfStock` | `false` | `true` to include out-of-stock items (`quantity = 0`).      |

- **Multi-shop** filter `shopIds=<uuid>,<uuid>`, **lenient**: an unknown shop
  matches nothing (no `404`), like a `WHERE shop_id IN (...)`.
- Out-of-stock items (`quantity = 0`) are **excluded by default**.

```http
GET /api/stock?shopIds=019f0fbb-99d7-7004-be48-1c77a6b3f41c,019f0fbb-9a1b-71c2-be48-1c77a6b3f41c&page=1&limit=20
```

**`200 OK`**

```json
{
  "items": [
    {
      "productId": "019f0fbb-99fe-790a-9ef8-415b9d7d7e22",
      "productName": "Wrap dress",
      "pictureUrl": "https://example.com/robe.jpg",
      "shopId": "019f0fbb-99d7-7004-be48-1c77a6b3f41c",
      "quantity": 12
    }
  ],
  "page": 1,
  "limit": 20,
  "total": 1,
  "totalPages": 1
}
```

**Errors**: `422` (a `shopIds` value not a valid UUID, invalid `page`/`limit`).

### `GET /api/shops/{id}/products` — Products of a shop

Stock of **one** shop, seen as a **resource**. Same representation and same
options (`includeOutOfStock`, [pagination](#pagination)) as `GET /api/stock`.
Unlike the lenient filter of `/api/stock`, this endpoint addresses a specific
shop: an `{id}` that is not a valid UUID **or** a non-existing shop returns
`404`.

```http
GET /api/shops/019f0fbb-99d7-7004-be48-1c77a6b3f41c/products
```

**`200 OK`**: same envelope as `GET /api/stock`.

**Errors**: `404` (`{id}` not a valid UUID, or non-existing shop).

### `GET /api/products/{id}/availability` — Availability of a product in shops

The inverse question of stock: **in which shops**, and — if a position is
provided — **which ones near me**, is this product available? This is the retail
*find in store* feature, which cross-references catalog, stock and geolocation in
a single read. The product is a **resource**: an `{id}` that is not a valid UUID
**or** a non-existing product returns `404`. Only `open` shops where the product
is **in stock** (`quantity > 0`) are returned.

Parameters in addition to [pagination](#pagination) — same geo rules as
`GET /api/shops`:

| Param    | Default | Description                                                |
| -------- | ------- | ---------------------------------------------------------- |
| `lat`    | —       | Latitude of the search center, bounded to `[-90, 90]`.     |
| `lng`    | —       | Longitude of the search center, bounded to `[-180, 180]`.  |
| `radius` | —       | Search radius **in meters** (> 0).                         |

- `lat`, `lng`, `radius` form an **all-or-nothing trio**: providing one without
  the others returns `422`.
- **Without geolocation** → all shops stocking the product, sorted by name;
  `distance` is `null`.
- **With geolocation** → only shops **within the radius**, sorted from
  **nearest to farthest**, each with its `distance` (in meters).

```http
GET /api/products/019f0f3c-ea98-7727-9d42-f4724f489ff4/availability?lat=48.8530&lng=2.3499&radius=8000
```

**`200 OK`** — **shop-centric** view (the product is in the URL):

```json
{
  "items": [
    {
      "shopId": "019f0fbb-99fe-790a-9ef8-415b9d7d7e22",
      "shopName": "Paris Marais",
      "address": "12 rue de Rivoli, 75004 Paris",
      "latitude": 48.8559,
      "longitude": 2.3601,
      "status": "open",
      "quantity": 12,
      "distance": 812.95
    }
  ],
  "page": 1,
  "limit": 20,
  "total": 1,
  "totalPages": 1
}
```

**Errors**:

- `422` (incomplete geo trio, coordinates out of bounds, `radius` ≤ 0,
  invalid `page`/`limit`).
- `404` (`{id}` not a valid UUID, or non-existing product).
