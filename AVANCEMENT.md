# Avancement — compatibilité GLPI 12

## Objectif

Mettre à jour le plugin Protocols Manager pour le rendre compatible avec GLPI 12, en préservant son comportement et les données existantes.

## Étapes

- 2026-10-16 — Démarrage : inspection de l’état du dépôt. Le dépôt est sur la branche `v11`. Des modifications locales préexistantes sont présentes ; les fichiers de plugin signalés par Git n’ont pas de diff de contenu (changements de permissions uniquement). Elles sont conservées et ne seront pas incluses dans les commits de migration.
- 2026-10-16 — Compatibilité déclarée : correction de la plage de versions GLPI dans `setup.php` (GLPI 11.x et 12.x, borne haute exclusive `13.0.0`) et du contrôle de prérequis qui rejetait auparavant GLPI 12. Mise à jour des prérequis documentés dans `README.md`.
- 2026-10-16 — Adaptations GLPI 12 : les formulaires dynamiques n’appellent `Session::getNewCSRFToken()` que si cette méthode existe, car GLPI 12 s’appuie sur les en-têtes Fetch Metadata et n’attend plus de jeton caché. Ajout des types de retour attendus par les méthodes d’onglet du plugin.
- 2026-10-16 — Version du plugin portée à `1.6.0.3` pour que GLPI détecte la mise à jour.
- 2026-10-16 — Métadonnées catalogue : ajout de la version `1.6.0.3` et compatibilités GLPI 11.0 et 12.0 dans `plugin.xml`.
- 2026-10-16 — Revue statique des changements GLPI 12 : confirmation de la transition CSRF vers les en-têtes Fetch Metadata et de l’ajout de types natifs aux API cœur. Pas de validation d’exécution effectuée : aucun environnement GLPI 12 de test n’est fourni dans le dépôt.
- 2026-10-16 — Métadonnées auteur : attribution du plugin à « Cheilmann » dans `setup.php` et `plugin.xml`, à la demande de l’auteur.

## À faire

- Valider l’installation/mise à jour et les parcours de génération PDF, gestion des droits et envoi d’e-mails sur une instance de test GLPI 12 avant déploiement en production.
