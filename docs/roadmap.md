# Évolutions possibles

Pistes d'évolution **non décidées** à ce stade (un ADR acte une décision *prise* ;
ces pistes sont futures, elles vivent donc ici et non dans [`docs/adr/`](adr/)).
Chacune renvoie, quand c'est pertinent, à la décision actuelle qu'elle prolonge.

## Authentification & identité de l'appelant (`Identity`)

L'API est aujourd'hui ouverte : « le client » de l'énoncé est implicite. En
production, on introduirait une authentification (token / OAuth2 via
`symfony/security`) et une notion d'`Identity` (le marchand connecté) propagée
dans la couche Application. Elle deviendrait le **périmètre de cohérence
multi-tenant** : un produit, une boutique, un stock appartiennent à un marchand,
et chaque commande/requête serait filtrée par cette identité (le contrôle d'accès
vivant dans le handler, pas seulement au routage). La frontière hexagonale
(cf. [ADR 1](adr/0001-architecture-hexagonale-pragmatique.md)) rend l'ajout
local : les agrégats porteraient un `MerchantId`, sans bouleverser la logique
métier existante.

## Command bus

Aujourd'hui les Actions invoquent directement leur handler
(`CreateProductCommandHandler`), ce qui est explicite et sans indirection. Un bus
(via `symfony/messenger`) deviendrait pertinent pour appliquer des préoccupations
transverses de façon uniforme (boundary transactionnel, dispatch d'events
domaine, logging) ou pour traiter des commandes en asynchrone (transport / queue).
Point clé : l'ajout se ferait **sans toucher au domaine** — les handlers et le
domaine restent inchangés —, ce qui illustre l'intérêt de la frontière hexagonale.

## Messages d'erreur traduisibles

Chaque violation expose déjà un `code` stable, ce qui permet au **client**
d'assurer l'i18n selon la locale de son utilisateur (approche recommandée pour une
API : le message serveur reste un défaut indicatif, le `code` est le contrat). Si
l'API devait servir des messages déjà localisés, on activerait l'i18n **côté
serveur** via le Translator Symfony (messages de contraintes en clés de
traduction, catalogues par langue, négociation de la locale sur l'en-tête
`Accept-Language`).

## Codes d'erreur applicatifs

Aujourd'hui le `code` des violations est l'identifiant natif de Symfony (UUID par
type de contrainte). On pourrait exposer notre **propre référentiel de codes**
(ex. `PRODUCT_NAME_REQUIRED`, `INVALID_URL`) : un code = une erreur précise, ce qui
**simplifie le dictionnaire d'erreurs côté front** (mapping direct code → message
localisé). Mise en œuvre : dériver un code lisible du type de contrainte dans le
listener, avec surcharge possible par champ via l'option `payload` des contraintes.

## Index spatial pour la recherche géo à grand volume

La requête actuelle (`ST_Distance_Sphere` sur colonnes `latitude`/`longitude`
scalaires, cf. [ADR 6](adr/0006-recherche-geo-sql-natif.md)) fait un full scan,
sans coût perceptible à l'échelle visée. Pour de très gros volumes, on ajouterait
une colonne `POINT SRID 4326` avec **index spatial** et un pré-filtre par
*bounding box* (`MBRContains`) avant le calcul exact de distance, voire un passage
à **PostGIS** (`geography` + index GiST) — l'alternative écartée à ce stade
(cf. [ADR 3](adr/0003-choix-mysql-sgbd.md)). Évolution isolée à l'infrastructure :
le port `ShopFinder` et le domaine sont inchangés.

## Recherche par adresse (géocodage)

La saisie brute de `lat`/`lng` est peu pratique pour un humain. Un service de
géocodage (adresse → coordonnées) côté serveur permettrait une recherche « près de
telle adresse », en réutilisant tel quel le filtre géographique existant.

## Contrôle d'existence inter-module via un contrat publié

`Inventory` vérifie aujourd'hui l'existence d'un produit / d'une boutique en
interrogeant directement leurs tables (couplage limité au schéma, cf.
[ADR 8](adr/0008-ports-existence-intermodule.md)). Si les modules devaient être
davantage isolés (déploiements séparés, base par module), le module propriétaire
publierait un **port de lecture dédié** (ex. `Catalog` exposant un contrat
`ProductExistence`), consommé par `Inventory` — supprimant le partage de schéma au
profit d'un contrat applicatif explicite.
