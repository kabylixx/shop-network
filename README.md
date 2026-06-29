# Shop Network

API serveur (PHP / Symfony, **sans API Platform**) pour gérer un réseau de
boutiques, un catalogue de produits et leur stock.

Stack : **PHP 8.5 / Symfony 8.1 / MySQL 8.4**, servie par **FrankenPHP** dans un
conteneur unique. Architecture hexagonale pragmatique, organisée en modules
(`Catalog`, `Network`, `Inventory`) au sein d'un unique bounded context.

## Démarrage en une commande

Pré-requis : Docker + Docker Compose.

```bash
make start
```

Cette commande construit l'image, lève la stack (FrankenPHP + MySQL), installe
les dépendances et applique les migrations. L'API est alors disponible sur
<http://localhost:8080>.

| Commande               | Effet                                                      |
| ---------------------- | ---------------------------------------------------------- |
| `make start`           | Build + up + install + migrate (démarrage complet)         |
| `make test`            | Prépare la base de test puis lance la suite PHPUnit        |
| `make migrate`         | Applique les migrations Doctrine                           |
| `make fixtures`        | Charge les données de démo (catalogue façon Sézane)        |
| `make clear-cache`     | Vide le cache Symfony (env dev)                            |
| `make clear-testcache` | Vide le cache Symfony (env test)                           |
| `make down`            | Arrête et supprime les conteneurs                          |
| `make sh`              | Ouvre un shell dans le conteneur applicatif                |

## Endpoints

### `POST /api/products` — Créer un produit

Ajoute un produit au catalogue.

**Requête**

```http
POST /api/products
Content-Type: application/json

{
  "name": "Chemise en coton bio",
  "pictureUrl": "https://example.com/chemise.jpg"
}
```

**Réponse `201 Created`**

```json
{
  "id": "019f0df9-eef9-79e5-9d74-20e92f1721f7",
  "name": "Chemise en coton bio",
  "pictureUrl": "https://example.com/chemise.jpg"
}
```

L'identifiant est un **UUID v7** (ordonné dans le temps), porté par un Value
Object d'identité typé (`ProductId`) et généré par la couche application.

**Erreurs** — au format [RFC 7807](https://datatracker.ietf.org/doc/html/rfc7807)
(`application/problem+json`) :

- `422 Unprocessable Content` — payload invalide (`name` vide ou trop long,
  `pictureUrl` absente ou non valide). Le corps liste les `violations`, chacune
  avec un `code` stable (machine-readable) et un `message` maîtrisé.
- `400 Bad Request` — corps JSON malformé.

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

### `GET /api/products` — Lister le catalogue

Renvoie le catalogue paginé, avec recherche par nom et tri.

**Paramètres de requête** (tous optionnels)

| Param       | Défaut | Description                                                        |
| ----------- | ------ | ------------------------------------------------------------------ |
| `page`      | `1`    | Numéro de page (≥ 1).                                              |
| `limit`     | `20`   | Taille de page (1 à 100).                                          |
| `search`    | —      | Filtre par nom, **partiel**, insensible à la casse et aux accents. |
| `sort`      | `name` | Champ de tri (`name`).                                             |
| `direction` | `asc`  | Sens du tri (`asc` ou `desc`).                                     |

La recherche insensible aux accents/casse est assurée nativement par la
collation `utf8mb4_0900_ai_ci` de MySQL (pas de colonne normalisée).

**Requête**

```http
GET /api/products?search=robe&sort=name&direction=desc&page=1&limit=20
```

**Réponse `200 OK`**

```json
{
  "items": [
    {
      "id": "019f0f3c-ea98-7727-9d42-f4724f489ff4",
      "name": "Robe Sandy",
      "pictureUrl": "https://media.sezane.com/products/robe-sandy.jpg"
    },
    {
      "id": "019f0f3c-ea9a-7c98-a5f8-7109e2b6b40e",
      "name": "Robe Andy",
      "pictureUrl": "https://media.sezane.com/products/robe-andy.jpg"
    }
  ],
  "page": 1,
  "limit": 20,
  "total": 2,
  "totalPages": 1
}
```

Une page au-delà de la dernière renvoie `items: []` avec les métadonnées
correctes (pas d'erreur).

**Erreurs** — au format RFC 7807 :

- `422 Unprocessable Content` — paramètre invalide (`page < 1`, `limit` hors
  bornes, `sort` non whitelisté). Le corps liste les `violations` comme pour la
  création.

### `POST /api/managers` — Créer un gérant

Enregistre un gérant, rattachable ensuite à une ou plusieurs boutiques.

**Requête**

```http
POST /api/managers
Content-Type: application/json

{
  "name": "Amélie Poulain"
}
```

**Réponse `201 Created`**

```json
{
  "id": "019f0fbb-99d7-7004-be48-1c77a6b3f41c",
  "name": "Amélie Poulain"
}
```

**Erreurs** — RFC 7807 :

- `422 Unprocessable Content` — `name` vide ou supérieur à 150 caractères.
- `400 Bad Request` — corps JSON malformé ou mal typé.

### `POST /api/shops` — Créer une boutique

Crée une boutique et l'associe à un gérant existant.

**Corps de requête**

| Champ       | Requis | Description                                                |
| ----------- | ------ | ---------------------------------------------------------- |
| `name`      | oui    | Nom de la boutique (≤ 150).                                |
| `address`   | oui    | Adresse postale, texte libre (≤ 255).                      |
| `latitude`  | oui    | Latitude, bornée à `[-90, 90]`.                            |
| `longitude` | oui    | Longitude, bornée à `[-180, 180]`.                         |
| `managerId` | oui    | UUID d'un gérant **existant**.                             |
| `status`    | non    | `open` (défaut) ou `closed`.                               |

**Requête**

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

**Réponse `201 Created`**

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

La boutique référence son gérant **par identifiant** (pas d'association ORM) ;
les coordonnées sont portées par un Value Object `Coordinates` qui garantit ses
bornes comme invariant de domaine, en plus de la validation HTTP.

**Erreurs** — RFC 7807 :

- `422 Unprocessable Content` — champ requis manquant, coordonnées hors bornes,
  `managerId` non conforme à un UUID, `status` hors liste.
- `404 Not Found` — `managerId` syntaxiquement valide mais inexistant.
- `400 Bad Request` — corps JSON malformé.

La validation prime sur l'existence : une requête combinant des coordonnées hors
bornes **et** un `managerId` inconnu renvoie `422` (la validation s'exécute avant
le contrôle d'existence).

### `GET /api/shops` — Rechercher des boutiques

Recherche paginée par **nom** et/ou **proximité géographique**. Les boutiques
`closed` sont exclues des résultats. La distance est calculée côté base par
`ST_Distance_Sphere` (distance sphérique, en mètres).

**Paramètres de requête** (tous optionnels)

| Param    | Défaut | Description                                                        |
| -------- | ------ | ------------------------------------------------------------------ |
| `page`   | `1`    | Numéro de page (≥ 1).                                              |
| `limit`  | `20`   | Taille de page (1 à 100).                                          |
| `search` | —      | Filtre par nom, **partiel**, insensible à la casse et aux accents. |
| `lat`    | —      | Latitude du centre de recherche, bornée à `[-90, 90]`.            |
| `lng`    | —      | Longitude du centre de recherche, bornée à `[-180, 180]`.         |
| `radius` | —      | Rayon de recherche **en mètres** (> 0).                            |

`lat`, `lng` et `radius` forment un **trio tout-ou-rien** : fournir l'un sans les
autres renvoie `422` (un rayon n'a pas de sens sans centre, et inversement).

- **Sans géolocalisation** → tri par nom croissant ; le champ `distance` vaut
  `null`.
- **Avec géolocalisation** → seules les boutiques **dans le rayon** sont
  renvoyées, triées de la **plus proche à la plus éloignée**, et chaque résultat
  porte sa `distance` au centre (en mètres). `search` se combine au filtre géo.

**Requête**

```http
GET /api/shops?search=marais&lat=48.8566&lng=2.3522&radius=50000&page=1&limit=20
```

**Réponse `200 OK`**

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

**Erreurs** — RFC 7807 :

- `422 Unprocessable Content` — trio géo incomplet, coordonnées hors bornes,
  `radius` ≤ 0, `page`/`limit` invalides, `search` trop long. Le corps liste les
  `violations` comme pour la création.

### `PUT /api/products/{id}/stock` — Renseigner le stock d'un produit

Met à jour, pour un produit, la quantité disponible dans une ou plusieurs
boutiques. Sémantique **upsert par couple `(boutique, produit)`** : chaque ligne
du corps crée ou remplace la quantité du couple ; les boutiques **absentes** du
corps conservent leur stock. L'opération est **tout-ou-rien** (un identifiant
inconnu rejette toute la requête, aucune écriture partielle).

`{id}` est l'UUID d'un produit **existant** (contraint au niveau du routage : un
identifiant non conforme à un UUID renvoie `404`).

**Corps de requête** — tableau JSON de couples :

| Champ      | Requis | Description                                       |
| ---------- | ------ | ------------------------------------------------- |
| `shopId`   | oui    | UUID d'une boutique **existante**, unique par requête. |
| `quantity` | oui    | Entier ≥ 0 (`0` = produit référencé en rupture).  |

**Requête**

```http
PUT /api/products/019f0fbb-99fe-790a-9ef8-415b9d7d7e22/stock
Content-Type: application/json

[
  { "shopId": "019f0fbb-99d7-7004-be48-1c77a6b3f41c", "quantity": 12 },
  { "shopId": "019f0fbb-9a1b-71c2-be48-1c77a6b3f41c", "quantity": 0 }
]
```

**Réponse `200 OK`** — écho des couples upsertés (le produit est dans l'URL) :

```json
[
  { "shopId": "019f0fbb-99d7-7004-be48-1c77a6b3f41c", "quantity": 12 },
  { "shopId": "019f0fbb-9a1b-71c2-be48-1c77a6b3f41c", "quantity": 0 }
]
```

**Erreurs** — RFC 7807 :

- `422 Unprocessable Content` — `quantity` < 0, `shopId` non conforme à un UUID,
  champ manquant, ou même boutique répétée dans le corps. Le corps liste les
  `violations`, avec un chemin par ligne (ex. `lines[0].quantity`).
- `404 Not Found` — produit inconnu, ou au moins une boutique inconnue.
- `400 Bad Request` — corps JSON malformé.

La validation prime sur l'existence (même règle que la création de boutique).

### `GET /api/stock` — Afficher le stock par boutique(s)

Liste paginée du stock, **ventilé par boutique** : une ligne par couple
`(boutique, produit)`, jamais sommée. Chaque ligne porte les informations produit
(nom, photo) jointes depuis le catalogue.

**Paramètres de requête** (tous optionnels)

| Param               | Défaut  | Description                                                       |
| ------------------- | ------- | ----------------------------------------------------------------- |
| `shopIds`           | —       | UUID de boutiques séparés par des virgules ; omis = toutes.       |
| `includeOutOfStock` | `false` | `true` pour inclure les ruptures (`quantity = 0`).                |
| `page`              | `1`     | Numéro de page (≥ 1).                                             |
| `limit`             | `20`    | Taille de page (1 à 100).                                         |

- Filtre **multi-boutiques** : `shopIds=<uuid>,<uuid>`. C'est un filtre
  **tolérant** : une boutique inconnue ne matche simplement rien (pas de `404`),
  comme un `WHERE shop_id IN (...)`.
- Les ruptures (`quantity = 0`) sont **exclues par défaut** ; `includeOutOfStock=true`
  les réintègre.

**Requête**

```http
GET /api/stock?shopIds=019f0fbb-99d7-7004-be48-1c77a6b3f41c,019f0fbb-9a1b-71c2-be48-1c77a6b3f41c&page=1&limit=20
```

**Réponse `200 OK`**

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

**Erreurs** — RFC 7807 :

- `422 Unprocessable Content` — un `shopIds` non conforme à un UUID, `page`/`limit`
  invalides.

### `GET /api/shops/{id}/products` — Produits d'une boutique

Stock d'**une** boutique, vu comme une ressource. Même représentation et mêmes
options (`includeOutOfStock`, `page`, `limit`) que `GET /api/stock`. Contrairement
au filtre tolérant de `/api/stock`, cet endpoint adresse une boutique précise :
un `{id}` non conforme à un UUID **ou** une boutique inexistante renvoie `404`.

```http
GET /api/shops/019f0fbb-99d7-7004-be48-1c77a6b3f41c/products
```

## Qualité

- **Tests** : `make test` (PHPUnit ; base de test isolée par transaction via
  `dama/doctrine-test-bundle`).
- **Analyse** : PHPStan (niveau 8), PHP-CS-Fixer (`@Symfony`) et Deptrac
  (frontières hexagonales Domain → Application → Infrastructure), orchestrés par
  GrumPHP (`vendor/bin/grumphp run`) et branchés en hook pre-commit.

## Choix & hypothèses

Les choix d'architecture (hexagonale pragmatique, identités typées par agrégat
— `ProductId` & co — en UUID v7 via `symfony/uid`, mapping Doctrine par module,
réponses en représentation maîtrisée, erreurs RFC 7807) et les hypothèses de
cadrage sont détaillés dans l'étude du projet.

Côté **lecture**, le listing suit une approche **CQRS-light** : un port de
lecture dédié (`ProductFinder`, dans la couche Application) renvoie directement
des read models `ProductView` sans hydrater l'agrégat, tandis que l'écriture
conserve son propre `ProductRepository` (couche Domain). La pagination est
mutualisée dans `Shared` (`Pagination` / `Paginated`), réutilisable par les
prochains listings (boutiques, stock).

Côté **module `Network`**, une boutique référence son gérant **par identifiant**
(`ManagerId`), sans association Doctrine : les agrégats sont des frontières de
cohérence indépendantes, on ne traverse pas l'un pour charger l'autre. Le
contrôle d'existence du gérant vit donc dans le handler (`exists()` sur le port
d'écriture), ce qui permet de renvoyer un **`404`** explicite — là où une clé
étrangère aurait produit un `500`. Pour mapper ce cas sans coupler la couche
Application à HTTP, une exception domaine étend le marqueur partagé
`Shared\Domain\NotFoundException`, que le listener Problem Details traduit en
`404`.

**Recherche géolocalisée — `ST_Distance_Sphere` en requête SQL native.** La
lecture suit le même CQRS-light : un port `ShopFinder` (Application) renvoie des
`ShopView` enrichis d'une `distance`, sans hydrater l'agrégat `Shop`. Le calcul
de distance, le filtre par rayon et le tri sont délégués à MySQL —
`ST_Distance_Sphere(POINT(longitude, latitude), POINT(:lng, :lat))` —, **source
unique de vérité** : aucune formule de distance n'est dupliquée en PHP. Le centre
et le rayon de recherche sont portés par un Value Object `SearchArea` (centre
`Coordinates` + rayon en mètres), qui rend l'état illégal « centre sans rayon »
non représentable et garantit ses invariants (bornes, rayon > 0) en amont.
*Limitation assumée* : `latitude`/`longitude` étant stockées en colonnes
scalaires, `POINT()` est construit par ligne → **full scan** (sans index
spatial). C'est optimal à l'échelle visée (centaines à milliers de boutiques) ;
le passage à l'échelle est traité en évolutions.

**`address` est un `string`, pas un Value Object.** Un VO se justifie quand il
protège un invariant ou regroupe des champs cohérents : c'est le cas de
`Coordinates` (couple latitude/longitude + bornes), ce ne l'est pas d'une adresse
en texte libre, dont la seule règle (longueur) est déjà couverte par la
validation HTTP et la colonne. L'emballer dans un VO mono-champ serait de la
cérémonie sans garantie supplémentaire — au même titre que `name` reste un
`string`. Le jour où l'adresse deviendrait structurée (rue / ville / code postal
/ pays) ou nécessiterait une normalisation ou un géocodage, la promotion en VO se
justifierait ; ce besoin n'existe pas à ce stade.

**Module `Inventory` — `Stock` est un agrégat à part, référencé par identité.**
Une boutique peut détenir des milliers de lignes de stock : les charger avec
l'agrégat `Shop` serait néfaste pour la perf et la cohérence. `Stock` est donc un
agrégat indépendant qui référence `Product` et `Shop` **par leur identité**
(`ProductId`, `ShopId`) — règle des petits agrégats référencés par identité. Il
porte un identifiant propre (`StockId`) et garantit l'unicité du couple
`(shop, product)` via une contrainte d'unicité en base ; la quantité est un Value
Object `Quantity` (≥ 0, `0` = rupture). L'écriture est un **upsert par couple**
dans une seule transaction.

Le contrôle d'existence inter-module suit l'inversion de dépendances : plutôt que
de dépendre des modules `Catalog`/`Network`, `Inventory` **déclare ses propres
ports** (`ProductExistence`, `ShopExistence`) dans sa couche Application —
prolongement du choix fait pour `ShopFinder`. Leurs adapters interrogent les
tables `product`/`shop` en SQL natif (`ShopExistence` en **lot** pour N boutiques
en une requête) : le couplage se réduit au **schéma partagé** (un nom de table),
isolé dans un adapter, sans aucune dépendance de classe entre modules. Comme pour
le gérant, l'absence de clé étrangère est compensée par ce contrôle dans le
handler, qui renvoie un `404` explicite au lieu d'un `500`.

Côté **lecture du stock**, même CQRS-light : un port `StockFinder` (Application)
renvoie des `StockView` (produit + boutique + quantité) sans hydrater l'agrégat.
L'adapter fait une requête DBAL native qui **joint `stock` et `product` par nom de
table** — comme les adapters d'existence (couplage limité au schéma, sans
dépendance de classe Catalog). Point d'attention métier : le résultat est
**ventilé par boutique, jamais sommé** — un produit présent dans deux boutiques
donne deux lignes distinctes (un `GROUP BY product + SUM(quantity)` aurait été un
contresens vis-à-vis du besoin « détail par boutique »). Les deux routes sont
**deux use cases distincts** partageant le `StockFinder` : `GetStockByShops`
(`/api/stock`) est un **filtre tolérant** (boutique inconnue ignorée, comme un
`WHERE shop_id IN (...)`), tandis que `GetShopProducts` (`/api/shops/{id}/products`)
traite la boutique comme une **ressource** et exige son existence (`404` sinon).

Cette section sera complétée au fil des user stories.

## Évolutions possibles

- **Command bus** (via `symfony/messenger`) — aujourd'hui les Actions invoquent
  directement leur handler (`CreateProductCommandHandler`), ce qui est explicite
  et sans indirection. Un bus deviendrait pertinent pour appliquer des
  préoccupations transverses de façon uniforme (boundary transactionnel,
  dispatch d'events domaine, logging) ou pour traiter des commandes en
  asynchrone (transport / queue). Point clé : l'ajout se ferait **sans toucher au
  domaine** — les handlers et le domaine restent inchangés —, ce qui illustre
  l'intérêt de la frontière hexagonale.
- **Messages d'erreur traduisibles** — chaque violation expose déjà un `code`
  stable, ce qui permet au **client** d'assurer l'i18n selon la locale de son
  utilisateur (approche recommandée pour une API : le message serveur reste un
  défaut indicatif, le `code` est le contrat). Si l'API devait servir des
  messages déjà localisés, on activerait l'i18n **côté serveur** via le Translator
  Symfony (messages de contraintes en clés de traduction, catalogues par langue,
  négociation de la locale sur l'en-tête `Accept-Language`).
- **Codes d'erreur applicatifs** — aujourd'hui le `code` des violations est
  l'identifiant natif de Symfony (UUID par type de contrainte). On pourrait
  exposer notre **propre référentiel de codes** (ex. `PRODUCT_NAME_REQUIRED`,
  `INVALID_URL`) : un code = une erreur précise, ce qui **simplifie le
  dictionnaire d'erreurs côté front** (mapping direct code → message localisé).
  Mise en œuvre : dériver un code lisible du type de contrainte dans le listener,
  avec surcharge possible par champ via l'option `payload` des contraintes.
- **Index spatial pour la recherche géo à grand volume** — la requête actuelle
  (`ST_Distance_Sphere` sur colonnes `latitude`/`longitude` scalaires) fait un
  full scan, sans coût perceptible à l'échelle visée. Pour de très gros volumes,
  on ajouterait une colonne `POINT SRID 4326` avec **index spatial** et un
  pré-filtre par *bounding box* (`MBRContains`) avant le calcul exact de
  distance, voire un passage à **PostGIS** (`geography` + index GiST). Évolution
  isolée à l'infrastructure : le port `ShopFinder` et le domaine sont inchangés.
- **Recherche par adresse (géocodage)** — la saisie brute de `lat`/`lng` est peu
  pratique pour un humain. Un service de géocodage (adresse → coordonnées) côté
  serveur permettrait une recherche « près de telle adresse », en réutilisant
  tel quel le filtre géographique existant.
- **Contrôle d'existence inter-module via un contrat publié** — `Inventory`
  vérifie aujourd'hui l'existence d'un produit / d'une boutique en interrogeant
  directement leurs tables (couplage limité au schéma). Si les modules devaient
  être davantage isolés (déploiements séparés, base par module), le module
  propriétaire publierait un **port de lecture dédié** (ex. `Catalog` exposant un
  contrat `ProductExistence`), consommé par `Inventory` — supprimant le partage de
  schéma au profit d'un contrat applicatif explicite.
