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
Application à HTTP, une exception domaine implémente le marqueur partagé
`Shared\Domain\NotFound`, que le listener Problem Details traduit en `404`.

**`address` est un `string`, pas un Value Object.** Un VO se justifie quand il
protège un invariant ou regroupe des champs cohérents : c'est le cas de
`Coordinates` (couple latitude/longitude + bornes), ce ne l'est pas d'une adresse
en texte libre, dont la seule règle (longueur) est déjà couverte par la
validation HTTP et la colonne. L'emballer dans un VO mono-champ serait de la
cérémonie sans garantie supplémentaire — au même titre que `name` reste un
`string`. Le jour où l'adresse deviendrait structurée (rue / ville / code postal
/ pays) ou nécessiterait une normalisation ou un géocodage, la promotion en VO se
justifierait ; ce besoin n'existe pas à ce stade.

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
