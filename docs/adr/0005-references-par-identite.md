# 5. Références par identité, sans clé étrangère ; existence vérifiée en handler

- **Statut** : accepté
- **Date de décision** : 2026-06-27
- **Première application** : association boutique → gérant, réappliquée au stock → produit/boutique

## Contexte

Les agrégats se référencent mutuellement : une boutique a un gérant, une ligne
de stock concerne un produit et une boutique. La tentation est de poser des
associations Doctrine (`@ManyToOne`) et des clés étrangères SQL. Mais un agrégat
est une **frontière de cohérence** : le charger pour en traverser un autre brouille
ces frontières et fait remonter des graphes d'objets entiers.

## Décision

Les agrégats se référencent **par identité** (`ManagerId`, `ProductId`,
`ShopId`), jamais par association ORM ni clé étrangère SQL.

- L'**existence** de la cible est vérifiée **dans le handler** avant l'écriture
  (port d'écriture pour le gérant ; ports d'existence dédiés pour le stock, cf.
  [ADR 8](0008-ports-existence-intermodule.md)).
- Une cible absente lève une **exception de domaine** étendant le marqueur
  partagé `Shared\Domain\NotFoundException`, traduite en **`404`** par le listener
  Problem Details (RFC 7807) — là où une violation de clé étrangère aurait produit
  un `500`.
- **La validation prime sur l'existence** : une requête à la fois mal formée et
  référençant une cible inexistante renvoie `422` (la validation s'exécute avant
  le contrôle d'existence).

Corollaire de minimalisme (cf. [ADR 1](0001-architecture-hexagonale-pragmatique.md)) :
`address` reste un **`string`**, pas un Value Object. Un VO se justifie quand il
protège un invariant ou regroupe des champs cohérents (c'est le cas de
`Coordinates`) ; emballer une adresse en texte libre, dont la seule règle est la
longueur (déjà couverte par la validation et la colonne), serait de la cérémonie
sans garantie.

## Conséquences

**Positives**

- Frontières d'agrégats nettes ; pas de chargement transitif involontaire.
- Erreurs métier explicites (`404`) au lieu d'erreurs techniques (`500`).
- Couche Application découplée de HTTP : le mapping `exception → statut` vit dans
  un listener.

**Négatives / limites**

- L'intégrité référentielle est portée par le **code** (contrôle en handler),
  pas par la base : pas de garde-fou FK en cas d'écriture hors application.
- Un contrôle d'existence = une requête supplémentaire ; négligeable au regard de
  la clarté gagnée, et mutualisable en lot (cf. [ADR 8](0008-ports-existence-intermodule.md)).
