# Architecture Decision Records

Décisions d'architecture du projet, au format léger **Contexte → Décision →
Conséquences**. Un ADR acte **une** décision *prise* ; les pistes futures non
décidées vivent dans la [roadmap](../roadmap.md), pas ici.

Chaque ADR est auto-portant : sa **date de décision** situe le moment où le choix
a été fait, et « Première application » décrit la capacité qui l'a motivé. Ces
décisions ont été formalisées rétroactivement lors de la passe de finition du
projet ; elles sont **classées par ordre d'apparition dans le développement** —
chaque décision précède la fonctionnalité qui l'applique.

| # | Décision | Date | Statut |
|---|----------|------|--------|
| [0001](0001-architecture-hexagonale-pragmatique.md) | Architecture hexagonale pragmatique (modules, CQRS-light) | 2026-06-27 | accepté |
| [0002](0002-pas-d-api-platform.md) | Pas d'API Platform | 2026-06-27 | accepté |
| [0003](0003-choix-mysql-sgbd.md) | Choix de MySQL 8 comme SGBD (géo + recherche insensible aux accents) | 2026-06-27 | accepté |
| [0004](0004-identites-typees-uuid.md) | Identités typées par agrégat, en UUID v7 | 2026-06-27 | accepté |
| [0005](0005-references-par-identite.md) | Références par identité, sans clé étrangère ; existence en handler | 2026-06-27 | accepté |
| [0006](0006-recherche-geo-sql-natif.md) | Recherche géographique via `ST_Distance_Sphere` (SQL natif) | 2026-06-27 | accepté |
| [0007](0007-stock-agregat-upsert.md) | `Stock`, agrégat indépendant ; écriture en upsert par couple | 2026-06-28 | accepté |
| [0008](0008-ports-existence-intermodule.md) | Ports d'existence inter-module (couplage limité au schéma) | 2026-06-28 | accepté |
| [0009](0009-disponibilite-produit-vo-geo-partages.md) | VO géo dans `Shared\Domain` ; lecture cross-module (disponibilité produit) | 2026-06-30 | accepté |
