# Shop Network

API serveur (PHP / Symfony, **sans API Platform**) pour gérer un réseau de
boutiques, un catalogue de produits et leur stock.

Stack : **PHP 8.5 / Symfony 8.1 / MySQL 8.4**, servie par **FrankenPHP** dans un
conteneur unique. Architecture hexagonale pragmatique, organisée en modules
(`Catalog`, `Network`, `Inventory`) au sein d'un unique bounded context.

## Démarrage en une commande

Pré-requis : Docker + Docker Compose.

```bash
make demo
```

Cette commande construit l'image, lève la stack (FrankenPHP + MySQL), installe
les dépendances, applique les migrations **et charge les données de démo**
(catalogue, réseau de boutiques, stock). L'API est alors disponible,
prête à explorer, sur <http://localhost:8080>.

> `make start` fait la même chose **sans** les fixtures : c'est le démarrage
> idempotent du quotidien (un redémarrage ne purge pas la base). `make demo`
> ajoute le chargement (destructif) des données de démo par-dessus.

| Commande               | Effet                                                      |
| ---------------------- | ---------------------------------------------------------- |
| `make demo`            | Démarrage complet **avec** données de démo (start + fixtures) |
| `make start`           | Build + up + install + migrate (sans fixtures, idempotent) |
| `make test`            | Prépare la base de test puis lance la suite PHPUnit        |
| `make migrate`         | Applique les migrations Doctrine                           |
| `make fixtures`        | (Re)charge les données de démo (catalogue, boutiques, stock) |
| `make clear-cache`     | Vide le cache Symfony (env dev)                            |
| `make clear-testcache` | Vide le cache Symfony (env test)                           |
| `make down`            | Arrête et supprime les conteneurs                          |
| `make sh`              | Ouvre un shell dans le conteneur applicatif                |

## API

API REST JSON : listings paginés, erreurs au format **RFC 7807**
(`application/problem+json`). Le contrat détaillé de chaque endpoint (corps,
paramètres, codes, exemples requête/réponse) est dans la **[référence API](docs/api.md)**.

| Méthode | Route | Description |
| ------- | ----- | ----------- |
| `POST` | `/api/products` | Créer un produit |
| `GET` | `/api/products` | Lister le catalogue (pagination, recherche, tri) |
| `POST` | `/api/managers` | Créer un gérant |
| `POST` | `/api/shops` | Créer une boutique |
| `GET` | `/api/shops` | Rechercher des boutiques (nom + géolocalisation) |
| `PUT` | `/api/products/{id}/stock` | Renseigner le stock d'un produit |
| `GET` | `/api/stock` | Afficher / filtrer le stock par boutique(s) |
| `GET` | `/api/shops/{id}/products` | Produits d'une boutique |

→ **Détail complet : [`docs/api.md`](docs/api.md)**

## Qualité

- **Tests** : `make test` (PHPUnit ; base de test isolée par transaction via
  `dama/doctrine-test-bundle`).
- **Analyse** : PHPStan (niveau 8), PHP-CS-Fixer (`@Symfony`) et Deptrac
  (frontières hexagonales Domain → Application → Infrastructure), orchestrés par
  GrumPHP (`vendor/bin/grumphp run`) et branchés en hook pre-commit.

## Architecture en bref

API serveur **sans API Platform**, en **hexagonale pragmatique** : un unique
bounded context organisé en modules (`Catalog`, `Network`, `Inventory`, `Shared`),
chacun en couches **Domain / Application / Infrastructure** (frontières vérifiées
par Deptrac).

- **Identités typées par agrégat** (`ProductId`, `ShopId`…) en **UUID v7**,
  générées dans la couche Application.
- **Références par identité, sans clé étrangère** : l'existence d'un agrégat lié
  est vérifiée dans le handler → **`404`** explicite (jamais de `500` sur clé
  étrangère).
- **CQRS-light** : l'écriture passe par un *repository* (Domain) qui hydrate
  l'agrégat ; la lecture par un *finder* (Application) qui renvoie des **read
  models** (`ProductView`, `ShopView`, `StockView`), avec pagination mutualisée
  dans `Shared`.
- **Recherche géo** déléguée à MySQL (`ST_Distance_Sphere`), **source unique de
  vérité** ; recherche par nom insensible aux accents/casse via la collation
  MySQL — aucune extension, aucune lib spatiale.
- **`Stock`** est un agrégat indépendant (référencé par identité), écrit en
  **upsert par couple** et lu **ventilé par boutique, jamais sommé**.
- **Erreurs** au format **RFC 7807** (`application/problem+json`) ; représentations
  de sortie maîtrisées, jamais l'agrégat brut.

### Pour aller plus loin

- **Décisions d'architecture** — le *pourquoi* de chaque choix (et les
  alternatives écartées) est consigné en [ADR](docs/adr/) : 8 décisions, classées
  par ordre d'apparition dans le développement.
- **Évolutions possibles** — auth/`Identity`, command bus, i18n des erreurs, index
  spatial / PostGIS, géocodage par adresse… sont détaillées dans la
  [roadmap](docs/roadmap.md).
