# 8. Ports d'existence inter-module (couplage limité au schéma)

- **Statut** : accepté
- **Date de décision** : 2026-06-28
- **Première application** : écriture du stock (existence du produit et des boutiques)

## Contexte

Écrire un stock suppose que le produit et la/les boutique(s) existent (cf.
[ADR 5](0005-references-par-identite.md), contrôle d'existence en handler). Mais
`Product` appartient au module `Catalog` et `Shop` au module `Network`. Comment
`Inventory` vérifie-t-il cette existence **sans** dépendre des classes des autres
modules ?


## Décision

`Inventory` **déclare ses propres ports** dans sa couche Application —
`ProductExistence`, `ShopExistence` — exprimant exactement son besoin (« cet
identifiant existe-t-il ? »), sans présumer du statut.

- Leurs adapters d'infrastructure interrogent les tables `product` / `shop` en
  **SQL natif, par nom de table**.
- `ShopExistence` vérifie en **lot** (`WHERE id IN (:ids)`) : N boutiques en une
  requête.
- Inversion de dépendances : c'est `Inventory` qui possède l'abstraction ; aucun
  `use` d'une classe `Catalog`/`Network`.

## Conséquences

**Positives**

- Modules **découplés au niveau classe** : le besoin est exprimé côté consommateur.
- Sémantique correcte : l'existence est indépendante du statut (une boutique
  fermée existe) — pas de faux `404`.
- Contrôle d'existence en lot, économe en requêtes.

**Négatives / limites**

- **Couplage résiduel au schéma partagé** : un nom de table (`product`, `shop`)
  est connu hors de son module — assumé, et **isolé dans un seul adapter**.
- Si les modules devaient être isolés physiquement (base par module), il faudrait
  un contrat publié par le module propriétaire (cf. [roadmap](../roadmap.md)).
