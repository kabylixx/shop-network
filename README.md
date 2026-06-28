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
| `make fixtures`        | Charge les données de démo (à venir avec les US suivantes) |
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
