# 2. Pas d'API Platform

- **Statut** : accepté
- **Date de décision** : 2026-06-27
- **Première application** : socle HTTP du projet, dès le premier endpoint du catalogue

## Contexte

API Platform industrialise la création d'API REST/GraphQL sur Symfony+Doctrine
(exposition d'entités, pagination, filtres, négociation de contenu, erreurs). Le
cahier des charges du projet **interdit explicitement** son usage.

## Décision

Construire l'API **à la main**, avec les briques Symfony de base :

- **Actions invokables** (une classe = un endpoint), pas de contrôleurs CRUD
  générés.
- Désérialisation/validation des entrées via `#[MapRequestPayload]` /
  `#[MapQueryString]` + le composant Validator.
- **Représentations maîtrisées** en sortie (read models / `JsonResponse`), jamais
  l'exposition directe d'un agrégat.
- Erreurs au format **RFC 7807** (`application/problem+json`) via un listener
  dédié.
- Pagination mutualisée maison (`Pagination` / `Paginated` dans `Shared`).

## Conséquences

**Positives**

- **Contrôle total** sur le contrat HTTP : forme des réponses, codes, format
  d'erreur, sémantique des endpoints.
- Aucune fuite de la structure de persistance dans l'API.
- Démontre la compréhension des mécanismes qu'un framework de haut niveau
  automatise.

**Négatives / limites**

- On réimplémente ce qu'API Platform offrirait (pagination, filtres,
  documentation OpenAPI) — **limité au strict besoin**, donc coût contenu.
- Pas de documentation OpenAPI générée ; le contrat est décrit dans
  [`docs/api.md`](../api.md).
