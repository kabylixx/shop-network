# 7. `Stock`, agrégat indépendant ; écriture en upsert par couple

- **Statut** : accepté
- **Date de décision** : 2026-06-28
- **Première application** : renseignement du stock, prolongé à sa lecture

## Contexte

Le stock relie produits et boutiques. Le modéliser comme une collection portée
par l'agrégat `Shop` (ou `Product`) serait néfaste : une boutique peut détenir
des milliers de lignes de stock, qu'on chargerait alors avec l'agrégat. Il faut
aussi décider de la sémantique d'écriture (`PUT` d'une liste de couples) et de
lecture (faut-il sommer les quantités d'un produit across boutiques ?).

## Décision

`Stock` est un **agrégat indépendant**, conforme à la règle « petits agrégats
référencés par identité » :

- Identifiant propre `StockId` ; référence `ProductId` et `ShopId` **par
  identité** (cf. [ADR 5](0005-references-par-identite.md)).
- **Unicité du couple `(shop, product)`** garantie par une contrainte en base.
- Quantité = Value Object **`Quantity`** (entier ≥ 0 ; `0` = produit référencé en
  rupture).

**Écriture** (`PUT /api/products/{id}/stock`) : **upsert par couple** dans une
seule transaction — chaque ligne crée ou remplace la quantité du couple ; les
boutiques absentes du corps conservent leur stock ; un identifiant inconnu rejette
toute la requête (tout-ou-rien).

**Lecture** : le résultat est **ventilé par boutique, jamais sommé** — un
produit présent dans deux boutiques donne deux lignes (un `GROUP BY` + `SUM`
serait un contresens vis-à-vis du besoin « détail par boutique »). La lecture est
volontairement **status-agnostic** : une boutique fermée garde un stock visible.
Deux **use cases distincts** partagent le `StockFinder` :

- `GetStockByShops` (`/api/stock`) — **filtre tolérant** : une boutique inconnue
  est ignorée (sémantique `WHERE shop_id IN (...)`), pas de `404`.
- `GetShopProducts` (`/api/shops/{id}/products`) — la boutique est une
  **ressource** : inexistante → `404` (via [ADR 8](0008-ports-existence-intermodule.md)).

## Conséquences

**Positives**

- Pas de chargement de milliers de lignes via `Shop`/`Product` ; cohérence portée
  au niveau du couple.
- Sémantique d'écriture prévisible (upsert idempotent par couple, atomique).
- Lecture alignée sur le besoin métier (détail par boutique), et asymétrie
  filtre/ressource explicite (« une Action = un handler »).

**Négatives / limites**

- L'unicité `(shop, product)` est garantie en base ; toute écriture hors
  application doit la respecter.
- « Jamais sommé » est un choix métier : un besoin futur d'agrégat (total par
  produit) serait une **nouvelle** lecture, pas une modification de celle-ci.
