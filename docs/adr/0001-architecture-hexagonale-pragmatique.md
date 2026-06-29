# 1. Architecture hexagonale pragmatique

- **Statut** : accepté
- **Date de décision** : 2026-06-27
- **Première application** : socle du projet, dès le premier endpoint du catalogue

## Contexte

Le projet expose une API serveur couvrant trois préoccupations métier — le
catalogue produits, le réseau de boutiques, le stock — au sein d'un **unique
bounded context**. On veut isoler la logique métier de l'infrastructure
(Doctrine, HTTP, MySQL) pour qu'elle reste testable et que les choix techniques
restent remplaçables, **sans** payer le coût d'une architecture hexagonale
canonique (un projet/déploiement par module, interfaces pour tout).

## Décision

Architecture **hexagonale pragmatique**, organisée en **modules**
(`Catalog`, `Network`, `Inventory`, plus `Shared`), chacun découpé en trois
couches :

- **Domain** — agrégats, Value Objects, ports (interfaces) ; aucune dépendance
  framework.
- **Application** — commands/queries et leurs handlers, read models, ports de
  lecture (CQRS-light).
- **Infrastructure** — adapters Doctrine/DBAL, Actions HTTP, mapping.

Conventions retenues, dans l'esprit « juste ce qu'il faut » :

- **CQRS-light** : l'écriture passe par un `…Repository` (Domain) qui hydrate
  l'agrégat ; la lecture passe par un *finder* dédié (Application) qui renvoie
  des **read models** sans hydrater l'agrégat.
- **Minimalisme des abstractions** : on n'introduit un Value Object, un port ou
  une indirection que lorsqu'il protège un invariant ou un découplage réel — pas
  par réflexe (cf. [ADR 5](0005-references-par-identite.md) pour `address`
  laissée en `string`).

Les frontières entre couches sont **vérifiées automatiquement** par Deptrac
(Domain → Application → Infrastructure), branché dans la QA.

## Conséquences

**Positives**

- Logique métier testable sans infrastructure ; adapters remplaçables.
- Frontières explicites et garanties par l'outillage (Deptrac), pas seulement
  par convention.
- Évolutions transverses possibles **sans toucher au domaine** — ex. l'ajout
  d'un command bus (cf. [roadmap](../roadmap.md)).

**Négatives / limites**

- Un peu de cérémonie (commands/handlers/ports) pour des cas simples ; assumé
  comme le prix de la séparation.
- Modules dans un **même** déploiement et une **même** base : l'isolation est
  logique, pas physique (cf. [ADR 8](0008-ports-existence-intermodule.md) pour
  le couplage inter-module résiduel).
