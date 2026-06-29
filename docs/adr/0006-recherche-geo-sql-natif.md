# 6. Recherche géographique via `ST_Distance_Sphere` (SQL natif)

- **Statut** : accepté
- **Date de décision** : 2026-06-27
- **Première application** : recherche de boutiques par proximité

## Contexte

La recherche de boutiques par proximité d'un point doit filtrer celles dans un
rayon donné, les trier de la plus proche à la plus éloignée et renvoyer leur
distance. Calculer une distance géographique en PHP (formule de
haversine) puis trier/filtrer en mémoire imposerait de charger toutes les
boutiques et **dupliquerait** une logique que la base sait faire.

## Décision

Déléguer **calcul, filtre et tri à MySQL** via une requête DBAL native utilisant
`ST_Distance_Sphere` (le choix du SGBD lui-même, et les alternatives écartées
— PostGIS notamment —, sont actés dans [ADR 3](0003-choix-mysql-sgbd.md)) :

```sql
ST_Distance_Sphere(POINT(longitude, latitude), POINT(:lng, :lat))
```

- Le résultat est exposé dans un **read model** `ShopView` enrichi d'une
  `distance` (en mètres) — CQRS-light, sans hydrater l'agrégat `Shop` (cf.
  [ADR 1](0001-architecture-hexagonale-pragmatique.md)).
- Le centre et le rayon de recherche sont portés par un Value Object
  **`SearchArea`** (`Coordinates` + rayon en mètres) qui rend l'état illégal
  « centre sans rayon » **non représentable** et garantit ses invariants (bornes
  des coordonnées, rayon > 0).
- Géolocalisation **optionnelle** : sans elle, tri par nom et `distance` à `null`.
- Les boutiques `closed` sont exclues de cette recherche.

## Conséquences

**Positives**

- **Source unique de vérité** pour la distance : aucune formule dupliquée en PHP.
- Filtre et tri exécutés au plus près des données, sur l'ensemble indexable.
- `SearchArea` empêche un appel incohérent dès la construction de la requête.

**Négatives / limites**

- `latitude`/`longitude` étant stockées en **colonnes scalaires**, `POINT()` est
  reconstruit par ligne → **full scan**, sans index spatial. C'est optimal à
  l'échelle visée (centaines à milliers de boutiques) ; le passage à grand volume
  (index spatial / PostGIS) est traité en [roadmap](../roadmap.md).
- Couplage au dialecte spatial de MySQL, isolé dans l'adapter d'infrastructure.
