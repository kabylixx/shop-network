# 3. Choix de MySQL 8 comme SGBD

- **Statut** : accepté
- **Date de décision** : 2026-06-27
- **Première application** : socle de persistance (recherche géo et recherche par nom)

## Contexte

Le type de base de données est **libre** (énoncé : « vous pouvez utiliser le type
de base de données que vous souhaitez »). Deux besoins de lecture orientent le
choix :

1. une **recherche géographique** par proximité (filtre rayon + tri distance, cf.
   [ADR 6](0006-recherche-geo-sql-natif.md)) ;
2. une **recherche par nom** insensible à la casse **et aux accents**
   (« robe » doit matcher « Robe », « Hélène » doit matcher « helene »).

Le réflexe pour du géo serait PostgreSQL + PostGIS. Mais à l'échelle visée
(centaines à milliers de boutiques/produits), l'enjeu n'est pas la performance
spatiale — c'est le **ratio robustesse/effort/risque** sur la durée d'un test.

## Décision

**MySQL 8** comme SGBD unique, en s'appuyant sur ses fonctions **natives** pour
couvrir les deux besoins sans extension ni dépendance supplémentaire :

- **Géo** : `ST_Distance_Sphere` (distance sphérique en mètres, native MySQL 8) —
  aucune lib Doctrine spatiale, aucune extension à activer.
- **Recherche par nom** : collation **`utf8mb4_0900_ai_ci`** (*accent-insensitive,
  case-insensitive*) — l'insensibilité accents/casse est assurée par la base, sans
  colonne normalisée ni dénormalisation applicative.

## Alternatives écartées

| Option | Pourquoi écartée |
|--------|------------------|
| **PostgreSQL + PostGIS** | La référence géo (index GiST, `ST_Distance` précis), mais index spatial **inutile à l'échelle visée** ; coût/risque élevé (extension à activer y compris en base de test, migrations en SQL manuel, footgun de compat DBAL 4). « Effet waouh » sans bénéfice réel ici. |
| **PostgreSQL + Haversine** | Robuste et simple, mais formule de distance **à écrire et maintenir** soi-même, là où MySQL offre une fonction native correcte. |
| **MySQL POINT + index spatial** | Apporte l'index spatial (pré-filtre *bounding box*), mais même coût/risque que PostGIS (colonne `POINT SRID 4326`, migrations manuelles) pour un gain nul au volume visé. |
| **MySQL + Haversine** | Dominé par `ST_Distance_Sphere` (fonction native) — réécrire la formule n'apporte rien. |

Verdict : `ST_Distance_Sphere` sur MySQL 8 = **meilleur ratio** (fonction native
correcte, zéro extension, base de test triviale).

## Conséquences

**Positives**

- Un seul SGBD, **sans extension**, couvre géo et recherche textuelle ; base de
  test triviale à provisionner.
- L'insensibilité accents/casse est déclarative (collation), pas du code à tester.
- Pas de dépendance spatiale Doctrine ni de migrations SQL manuelles.

**Négatives / limites**

- Pas d'index spatial : la recherche géo fait un **full scan** (assumé à l'échelle
  visée — détails et seuil de bascule dans [ADR 6](0006-recherche-geo-sql-natif.md)).
- Le passage à grand volume (index spatial MySQL, ou PostGIS) est une évolution
  isolée à l'infrastructure (cf. [roadmap](../roadmap.md)).
- Footgun connu de `ST_Distance_Sphere` : l'ordre des arguments est
  `POINT(longitude, latitude)` — verrouillé par les tests.
