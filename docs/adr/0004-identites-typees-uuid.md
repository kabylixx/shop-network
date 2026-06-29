# 4. Identités typées par agrégat, en UUID v7

- **Statut** : accepté
- **Date de décision** : 2026-06-27
- **Première application** : identifiant de produit, généralisé aux boutiques, gérants et stock

## Contexte

Chaque agrégat a besoin d'un identifiant. Deux risques classiques : mélanger des
identifiants d'agrégats différents (passer un id de boutique là où on attend un
id de produit) et exposer un détail de persistance (auto-increment) dans le
contrat HTTP.

## Décision

Un **Value Object d'identité typé par agrégat** : `ProductId`, `ShopId`,
`ManagerId`, `StockId`, chacun étendant `Symfony\Component\Uid\Uuid`
(`symfony/uid`).

- **UUID v7** (ordonné dans le temps) : les identifiants suivent l'ordre de
  création, ce qui donne des index/paginations naturellement ordonnés.
- **Génération dans la couche Application** (le handler), pas par la base ni par
  le client : l'identité fait partie de la décision métier de création.
- Stockés en **`BINARY(16)`** via des types Doctrine custom par agrégat.

## Conséquences

**Positives**

- **Type-safety** : la signature `setStock(ProductId $p, ShopId $s)` rend
  impossible l'inversion des identifiants — erreur attrapée à la compilation,
  pas au runtime.
- Identifiants **opaques et non devinables** dans l'API ; aucun couplage au
  schéma de stockage.
- Ordre temporel utile pour la pagination et la localité d'index (v7 vs v4).

**Négatives / limites**

- `BINARY(16)` est moins lisible qu'un entier en debug SQL (nécessite
  `HEX()` / conversion) — coût mineur, assumé.
- Un type Doctrine + un VO par agrégat : parti-pris de typage fort.
