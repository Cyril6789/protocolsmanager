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
- 2026-10-16 — Rafraîchissement des métadonnées GLPI : nouvelle version `1.6.0.4` pour que GLPI recopie l’auteur dans ses informations enregistrées sur le plugin. L’interface devra appliquer la mise à jour détectée.
- 2026-10-16 — Bug PDF : qualification de `QueryExpression` dans `Glpi\DBAL` lors de la requête qui calcule le numéro de protocole. GLPI 12 a déplacé cette classe hors de l’espace de noms global.
- 2026-10-16 — Bug d’icône : ajout de l’icône Tabler `ti ti-file-text` à l’onglet utilisateur avec `createTabEntry()` sur GLPI 12, en conservant l’appel historique sur GLPI 11.
- 2026-10-16 — Version du correctif portée à `1.6.0.5` pour exposer les deux corrections à GLPI et dans les métadonnées du catalogue.
- 2026-10-16 — Logo PDF : réduction de la taille maximale par défaut de 200 px à 160 px dans chaque dimension; les dimensions explicitement configurées dans les modèles restent inchangées. Version portée à `1.6.0.6` pour faire détecter la mise à jour par GLPI.
- 2026-10-16 — Modèle PDF : ajout d’une couleur d’accent configurable par modèle, avec migration de la colonne `theme_color` et valeur brun doré par défaut. Version portée à `1.6.0.7` pour faire détecter la mise à jour par GLPI.
- 2026-10-16 — Modèle PDF : ajout d’une couleur de texte configurable pour les en-têtes colorés, blanche par défaut. Version portée à `1.6.0.8`.

## À faire

- Valider l’installation/mise à jour et les parcours de génération PDF, gestion des droits et envoi d’e-mails sur une instance de test GLPI 12 avant déploiement en production.
