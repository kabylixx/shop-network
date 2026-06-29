# Référence API

Contrat détaillé de chaque endpoint : corps de requête, paramètres, réponses,
codes d'erreur et exemples. Vue d'ensemble dans le [README](../README.md#api) ;
le *pourquoi* des choix est dans les [ADR](adr/).

API **REST / JSON**. Conventions transverses (pagination, format d'erreur)
factorisées dans [Conventions communes](#conventions-communes) — les endpoints n'y
réfèrent que pour leurs spécificités.

## Sommaire

- [Conventions communes](#conventions-communes) — pagination, erreurs (RFC 7807)
- [Catalogue](#catalogue) — `POST /api/products`, `GET /api/products`
- [Réseau de boutiques](#réseau-de-boutiques) — `POST /api/managers`, `POST /api/shops`, `GET /api/shops`
- [Stock](#stock) — `PUT /api/products/{id}/stock`, `GET /api/stock`, `GET /api/shops/{id}/products`, `GET /api/products/{id}/availability`

## Conventions communes

### Pagination

Tous les listings sont paginés et acceptent ces paramètres (optionnels) :

| Param   | Défaut | Description                |
| ------- | ------ | -------------------------- |
| `page`  | `1`    | Numéro de page (≥ 1).      |
| `limit` | `20`   | Taille de page (1 à 100).  |

Ils renvoient une enveloppe avec métadonnées. Une page au-delà de la dernière
renvoie `items: []` avec des métadonnées correctes (ce n'est pas une erreur).

```json
{
  "items": [],
  "page": 1,
  "limit": 20,
  "total": 0,
  "totalPages": 0
}
```

### Erreurs (RFC 7807)

Les erreurs suivent le format [RFC 7807](https://datatracker.ietf.org/doc/html/rfc7807)
(`application/problem+json`) :

| Code | Quand |
| ---- | ----- |
| `400 Bad Request` | Corps JSON malformé ou mal typé. |
| `404 Not Found` | Ressource (ou ressource liée) référencée par un identifiant valide mais inexistante. |
| `422 Unprocessable Content` | Échec de validation (champ manquant, hors bornes, format invalide…). Le corps liste les `violations`. |

Chaque `violation` porte un `code` **stable** (machine-readable) et un `message`
maîtrisé ; `propertyPath` situe le champ fautif (ex. `lines[0].quantity`).

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

> **La validation prime sur l'existence** : une requête à la fois mal formée *et*
> référençant une ressource inexistante renvoie `422` (la validation s'exécute
> avant le contrôle d'existence).

## Catalogue

### `POST /api/products` — Créer un produit

**Corps de requête**

| Champ        | Requis | Description                       |
| ------------ | ------ | --------------------------------- |
| `name`       | oui    | Nom du produit (1 à 255).         |
| `pictureUrl` | oui    | URL de la photo (URL valide).     |

```http
POST /api/products
Content-Type: application/json

{
  "name": "Chemise en coton bio",
  "pictureUrl": "https://example.com/chemise.jpg"
}
```

**`201 Created`**

```json
{
  "id": "019f0df9-eef9-79e5-9d74-20e92f1721f7",
  "name": "Chemise en coton bio",
  "pictureUrl": "https://example.com/chemise.jpg"
}
```

**Erreurs** : `422` (`name` vide/trop long, `pictureUrl` absente ou non valide),
`400`.

### `GET /api/products` — Lister le catalogue

Catalogue paginé, avec recherche par nom et tri. Paramètres en plus de la
[pagination](#pagination) :

| Param       | Défaut | Description                                                        |
| ----------- | ------ | ------------------------------------------------------------------ |
| `search`    | —      | Filtre par nom, **partiel**, insensible à la casse et aux accents. |
| `sort`      | `name` | Champ de tri (`name`).                                             |
| `direction` | `asc`  | Sens du tri (`asc` ou `desc`).                                     |

```http
GET /api/products?search=robe&sort=name&direction=desc&page=1&limit=20
```

**`200 OK`**

```json
{
  "items": [
    { "id": "019f0f3c-ea98-7727-9d42-f4724f489ff4", "name": "Robe Sandy", "pictureUrl": "https://media.sezane.com/products/robe-sandy.jpg" },
    { "id": "019f0f3c-ea9a-7c98-a5f8-7109e2b6b40e", "name": "Robe Andy", "pictureUrl": "https://media.sezane.com/products/robe-andy.jpg" }
  ],
  "page": 1,
  "limit": 20,
  "total": 2,
  "totalPages": 1
}
```

**Erreurs** : `422` (`page`/`limit` hors bornes, `sort` non whitelisté).

## Réseau de boutiques

### `POST /api/managers` — Créer un gérant

Enregistre un gérant, rattachable ensuite à une ou plusieurs boutiques.

| Champ  | Requis | Description                    |
| ------ | ------ | ------------------------------ |
| `name` | oui    | Nom du gérant (1 à 150).       |

```http
POST /api/managers
Content-Type: application/json

{ "name": "Amélie Poulain" }
```

**`201 Created`**

```json
{
  "id": "019f0fbb-99d7-7004-be48-1c77a6b3f41c",
  "name": "Amélie Poulain"
}
```

**Erreurs** : `422` (`name` vide ou > 150), `400`.

### `POST /api/shops` — Créer une boutique

Crée une boutique et l'associe à un gérant **existant**.

| Champ       | Requis | Description                              |
| ----------- | ------ | ---------------------------------------- |
| `name`      | oui    | Nom de la boutique (≤ 150).              |
| `address`   | oui    | Adresse postale, texte libre (≤ 255).    |
| `latitude`  | oui    | Latitude, bornée à `[-90, 90]`.          |
| `longitude` | oui    | Longitude, bornée à `[-180, 180]`.       |
| `managerId` | oui    | UUID d'un gérant **existant**.           |
| `status`    | non    | `open` (défaut) ou `closed`.             |

```http
POST /api/shops
Content-Type: application/json

{
  "name": "Boutique Marais",
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
  "name": "Boutique Marais",
  "address": "12 rue de Rivoli, Paris",
  "latitude": 48.8566,
  "longitude": 2.3522,
  "managerId": "019f0fbb-99d7-7004-be48-1c77a6b3f41c",
  "status": "open"
}
```

**Erreurs** : `422` (champ requis manquant, coordonnées hors bornes, `managerId`
non conforme à un UUID, `status` hors liste), `404` (`managerId` valide mais
inexistant), `400`.

### `GET /api/shops` — Rechercher des boutiques

Recherche paginée par **nom** et/ou **proximité géographique**. Les boutiques
`closed` sont exclues des résultats. Paramètres en plus de la
[pagination](#pagination) :

| Param    | Défaut | Description                                                        |
| -------- | ------ | ------------------------------------------------------------------ |
| `search` | —      | Filtre par nom, **partiel**, insensible à la casse et aux accents. |
| `lat`    | —      | Latitude du centre de recherche, bornée à `[-90, 90]`.            |
| `lng`    | —      | Longitude du centre de recherche, bornée à `[-180, 180]`.         |
| `radius` | —      | Rayon de recherche **en mètres** (> 0).                            |

- `lat`, `lng`, `radius` forment un **trio tout-ou-rien** : fournir l'un sans les
  autres renvoie `422`.
- **Sans géolocalisation** → tri par nom croissant ; `distance` vaut `null`.
- **Avec géolocalisation** → seules les boutiques **dans le rayon**, triées de la
  **plus proche à la plus éloignée**, chacune avec sa `distance` (en mètres).
  `search` se combine au filtre géo.

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

**Erreurs** : `422` (trio géo incomplet, coordonnées hors bornes, `radius` ≤ 0,
`page`/`limit` invalides, `search` trop long).

## Stock

### `PUT /api/products/{id}/stock` — Renseigner le stock d'un produit

Met à jour, pour un produit, la quantité disponible dans une ou plusieurs
boutiques.

- Sémantique **upsert par couple `(boutique, produit)`** : chaque ligne crée ou
  remplace la quantité du couple ; les boutiques **absentes** du corps conservent
  leur stock.
- Opération **tout-ou-rien** : un identifiant inconnu rejette toute la requête
  (aucune écriture partielle).
- `{id}` est l'UUID d'un produit **existant** (un identifiant non conforme à un
  UUID renvoie `404` au routage).

**Corps de requête** — tableau JSON de couples :

| Champ      | Requis | Description                                            |
| ---------- | ------ | ------------------------------------------------------ |
| `shopId`   | oui    | UUID d'une boutique **existante**, unique par requête. |
| `quantity` | oui    | Entier ≥ 0 (`0` = produit référencé en rupture).       |

```http
PUT /api/products/019f0fbb-99fe-790a-9ef8-415b9d7d7e22/stock
Content-Type: application/json

[
  { "shopId": "019f0fbb-99d7-7004-be48-1c77a6b3f41c", "quantity": 12 },
  { "shopId": "019f0fbb-9a1b-71c2-be48-1c77a6b3f41c", "quantity": 0 }
]
```

**`200 OK`** — écho des couples upsertés (le produit est dans l'URL) :

```json
[
  { "shopId": "019f0fbb-99d7-7004-be48-1c77a6b3f41c", "quantity": 12 },
  { "shopId": "019f0fbb-9a1b-71c2-be48-1c77a6b3f41c", "quantity": 0 }
]
```

**Erreurs** : `422` (`quantity` < 0, `shopId` non conforme à un UUID, champ
manquant, ou même boutique répétée), `404` (produit inconnu ou ≥ 1 boutique
inconnue), `400`.

### `GET /api/stock` — Afficher / filtrer le stock par boutique(s)

Liste paginée du stock, **ventilée par boutique** : une ligne par couple
`(boutique, produit)`, **jamais sommée**. Chaque ligne porte les infos produit
(nom, photo) jointes depuis le catalogue. Paramètres en plus de la
[pagination](#pagination) :

| Param               | Défaut  | Description                                                 |
| ------------------- | ------- | ----------------------------------------------------------- |
| `shopIds`           | —       | UUID de boutiques séparés par des virgules ; omis = toutes. |
| `includeOutOfStock` | `false` | `true` pour inclure les ruptures (`quantity = 0`).          |

- Filtre **multi-boutiques** `shopIds=<uuid>,<uuid>`, **tolérant** : une boutique
  inconnue ne matche rien (pas de `404`), comme un `WHERE shop_id IN (...)`.
- Les ruptures (`quantity = 0`) sont **exclues par défaut**.

```http
GET /api/stock?shopIds=019f0fbb-99d7-7004-be48-1c77a6b3f41c,019f0fbb-9a1b-71c2-be48-1c77a6b3f41c&page=1&limit=20
```

**`200 OK`**

```json
{
  "items": [
    {
      "productId": "019f0fbb-99fe-790a-9ef8-415b9d7d7e22",
      "productName": "Robe portefeuille",
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

**Erreurs** : `422` (un `shopIds` non conforme à un UUID, `page`/`limit` invalides).

### `GET /api/shops/{id}/products` — Produits d'une boutique

Stock d'**une** boutique, vu comme une **ressource**. Même représentation et mêmes
options (`includeOutOfStock`, [pagination](#pagination)) que `GET /api/stock`.
Contrairement au filtre tolérant de `/api/stock`, cet endpoint adresse une
boutique précise : un `{id}` non conforme à un UUID **ou** une boutique
inexistante renvoie `404`.

```http
GET /api/shops/019f0fbb-99d7-7004-be48-1c77a6b3f41c/products
```

**`200 OK`** : même enveloppe que `GET /api/stock`.

**Erreurs** : `404` (`{id}` non conforme à un UUID, ou boutique inexistante).

### `GET /api/products/{id}/availability` — Disponibilité d'un produit en boutique

Question inverse du stock : **dans quelles boutiques**, et — si l'on fournit une
position — **lesquelles près de moi**, ce produit est-il disponible ? C'est la
fonctionnalité *find in store* du retail, qui croise catalogue, stock et
géolocalisation en une lecture. Le produit est une **ressource** : un `{id}` non
conforme à un UUID **ou** un produit inexistant renvoie `404`. Seules les
boutiques `open` où le produit est **en stock** (`quantity > 0`) sont remontées.

Paramètres en plus de la [pagination](#pagination) — mêmes règles géo que
`GET /api/shops` :

| Param    | Défaut | Description                                                |
| -------- | ------ | ---------------------------------------------------------- |
| `lat`    | —      | Latitude du centre de recherche, bornée à `[-90, 90]`.    |
| `lng`    | —      | Longitude du centre de recherche, bornée à `[-180, 180]`. |
| `radius` | —      | Rayon de recherche **en mètres** (> 0).                    |

- `lat`, `lng`, `radius` forment un **trio tout-ou-rien** : fournir l'un sans les
  autres renvoie `422`.
- **Sans géolocalisation** → toutes les boutiques stockant le produit, triées par
  nom ; `distance` vaut `null`.
- **Avec géolocalisation** → seules les boutiques **dans le rayon**, triées de la
  **plus proche à la plus éloignée**, chacune avec sa `distance` (en mètres).

```http
GET /api/products/019f0f3c-ea98-7727-9d42-f4724f489ff4/availability?lat=48.8530&lng=2.3499&radius=8000
```

**`200 OK`** — vue **centrée boutique** (le produit est dans l'URL) :

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

**Erreurs** :

- `422` (trio géo incomplet, coordonnées hors bornes, `radius` ≤ 0, `page`/`limit`
  invalides).
- `404` (`{id}` non conforme à un UUID, ou produit inexistant).
