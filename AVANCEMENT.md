# Avancement — compatibilité GLPI 12

## Objectif

Mettre à jour le plugin Protocols Manager pour le rendre compatible avec GLPI 12, en préservant son comportement et les données existantes.

## Étapes

- 2026-10-16 — Démarrage : inspection de l’état du dépôt. Le dépôt est sur la branche `v11`. Des modifications locales préexistantes sont présentes ; les fichiers de plugin signalés par Git n’ont pas de diff de contenu (changements de permissions uniquement). Elles sont conservées et ne seront pas incluses dans les commits de migration.

## À faire

- Identifier les points d’API GLPI utilisés et les écarts de compatibilité avec GLPI 12.
- Appliquer les adaptations nécessaires et les consigner ici au fil de l’eau.
- Vérifier la syntaxe et les métadonnées du plugin, puis documenter le résultat.
