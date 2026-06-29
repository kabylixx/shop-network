# 9. Value Objects géo dans `Shared\Domain` ; lecture cross-module composant les modules

- **Statut** : accepté
- **Date de décision** : 2026-06-29
- **Première application** : `GET /api/products/{id}/availability` (disponibilité d'un produit en boutique)

## Contexte

La disponibilité produit — « où, et près de moi, ce produit est-il en stock ? »,
la fonctionnalité *find in store* du retail — est une **lecture qui croise les
trois modules** : catalogue (`Catalog`), stock (`Inventory`) et géolocalisation
des boutiques (`Network`). Elle réutilise la recherche géographique
(cf. [ADR 6](0006-recherche-geo-sql-natif.md)), jusqu'ici propre à `Network` : les
Value Objects `Coordinates` et `SearchArea` vivaient dans ce module.

Un **second consommateur** (`Inventory`) apparaît donc pour ces VO. Deux
options se présentent : les **dupliquer** dans `Inventory` (copie d'un VO existant),
ou faire dépendre `Inventory` de la couche **Application** de `Network`
(`SearchArea` était un artefact du use case `SearchShops`) — un couplage de use
cases sans rapport.

## Décision

- **Promouvoir `Coordinates` et `SearchArea` dans `Shared\Domain`** : une seule
  source de vérité, aucun module « propriétaire » d'un VO que l'autre emprunte. La
  règle est la même que pour les identités typées (cf.
  [ADR 4](0004-identites-typees-uuid.md)) : ce sont les **VO de domaine** qui
  traversent les modules, jamais les artefacts de use case.
- La disponibilité est une **lecture cross-module** en CQRS-light (cf.
  [ADR 1](0001-architecture-hexagonale-pragmatique.md)) : un port
  `ProductAvailabilityFinder` (Application `Inventory`) + un read model
  `AvailabilityView`, dont l'adapter DBAL **joint `stock` et `shop` par nom de
  table** — même couplage-par-schéma que les ports d'existence (cf.
  [ADR 8](0008-ports-existence-intermodule.md)), sans dépendance de classe vers
  `Network`.
- Réutilisation directe des décisions existantes : `ST_Distance_Sphere`
  (cf. [ADR 6](0006-recherche-geo-sql-natif.md)) et contrôle d'existence en handler
  → `404` (cf. [ADR 5](0005-references-par-identite.md)). Le produit est une
  **ressource** adressée ; géolocalisation **optionnelle** (trio tout-ou-rien) ;
  seules les boutiques `open` où le produit est en stock (`quantity > 0`) sont
  remontées.

## Conséquences

**Positives**

- **Noyau géo unique** : plus de duplication de `Coordinates`/`SearchArea`.
- Le **modèle prouve sa valeur** : les agrégats restent de petites frontières
  indépendantes, et c'est la *lecture* qui les compose pour le besoin métier — sans
  introduire d'association ni d'agrégat « god object ».
- Dépendances inter-modules maintenues minimales : des **VO de domaine** partagés
  (`Shared`) et un **schéma** partagé (jointure par nom de table), jamais une
  dépendance de classe entre couches Application de modules distincts.

**Négatives / limites**

- `Shared\Domain` grossit — assumé : ce sont des VO réellement transverses, pas un
  fourre-tout. La barre reste « un VO transverse est promu au 2ᵉ consommateur ».
- Apparition d'une dépendance **Domain → Domain** inter-module (`Network\Domain\Shop`
  et `Inventory` vers `Shared\Domain`), autorisée car `Shared` est le noyau dont
  tout le monde dépend (vérifié par Deptrac : 0 violation).
