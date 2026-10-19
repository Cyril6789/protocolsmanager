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

## Nouvelle fonctionnalité — procès-verbal de mise en service d’une immobilisation

### Objectif et règles de conservation

Ajouter au plugin un parcours distinct de génération d’un procès-verbal de mise en service pour du matériel GLPI. Le PDF reprend les données de gestion des matériels et permet d’ajouter les informations propres à la mise en service et aux validations.

- [x] Garder le parcours, le contenu et le comportement des documents d’inventaire utilisateur indépendants de cette nouvelle fonctionnalité.
- [x] Réutiliser le logo et les couleurs configurées pour les PDF existants, sans modifier leur rendu ni leurs réglages.
- [x] Tolérer les champs GLPI absents ou vides : omettre leur valeur dans le PDF sans avertissement PHP ni erreur de génération.
- [x] N’enregistrer aucune information spécifique au PV dans les champs des objets matériels de GLPI ; les saisies du formulaire servent à produire le document.
- [ ] Maintenir la compatibilité avec les versions GLPI prises en charge par le plugin et vérifier les droits de lecture des matériels sélectionnés.

### 1. Repérage des données GLPI et conception

- [ ] Identifier les types de matériels accessibles dans le parcours proposé et leurs tables/champs communs dans GLPI 11 et GLPI 12.
- [ ] Repérer les champs de l’onglet Gestion correspondant au numéro d’immobilisation, au fournisseur, à la date d’acquisition, au numéro de facture et à la valeur d’acquisition HT.
- [ ] Repérer les champs communs d’un matériel : nom, type, numéro d’inventaire, numéro de série et lieu d’affectation.
- [ ] Déterminer comment obtenir le premier niveau du lieu sélectionné (site d’affectation) et traiter un lieu non renseigné.
- [ ] Vérifier le format et les cas possibles du numéro d’immobilisation, notamment les valeurs absentes et les objets de types différents.
- [x] Définir le regroupement : un PV peut couvrir un ou plusieurs objets ayant exactement le même numéro d’immobilisation ; les objets d’autres numéros ne sont pas ajoutés au document.
- [ ] Définir le calcul du montant total d’acquisition HT pour les objets du groupe ; ne pas présenter un total partiel comme un total complet si une valeur d’acquisition manque.
- [x] Déterminer l’entrée utilisateur : un onglet distinct sur les fiches matériel, séparé de l’onglet d’inventaire utilisateur.
- [x] Identifier la source du prénom et du nom de l’utilisateur connecté qui signe la déclaration, ainsi que le format de la date courante.

### 2. Parcours de génération et sélection des objets

- [x] Ajouter un point d’entrée distinct pour le PV matériel, sans détourner la génération des documents d’inventaire utilisateur.
- [x] Permettre de produire un PV pour un seul objet.
- [x] Permettre de produire un PV regroupant plusieurs objets partageant le même numéro immo, avec un choix clair des objets concernés.
- [x] Filtrer ou valider côté serveur les objets sélectionnés afin qu’ils aient tous le même numéro immo ; refuser proprement une sélection incohérente.
- [x] Vérifier côté serveur les droits de lecture sur chaque objet, sans faire confiance aux seuls identifiants soumis par le formulaire.
- [x] Préserver les contrôles d’accès du plugin et échapper les valeurs affichées ou intégrées au HTML/PDF.
- [x] Préremplir la date effective de mise en service avec la date d’acquisition du matériel ; pour un groupe, ne préremplir que si toutes les dates sont identiques.
- [x] Précocher les quatre états « Installée », « Testée », « Réceptionnée conforme » et « Mise en exploitation », tout en permettant leur modification avant génération.
- [x] Proposer la saisie de la description d’utilisation prévue et des observations éventuelles.
- [x] Proposer le choix du lieu d’établissement parmi deux boutons radio : Eichhoffen et Barr.
- [x] Préremplir la date « Document établi le » avec la date du jour.
- [x] Afficher une erreur compréhensible si aucun matériel exploitable n’est sélectionné, sans produire de PDF incomplet par accident.

### 3. Contenu du PDF

- [x] Reprendre le numéro immo en tête du document.
- [x] Ajouter le chapitre 1 « Identification de l’immobilisation » avec, pour chaque objet, le numéro immo, la désignation (nom de l’objet et type), le numéro d’inventaire, le numéro de série, le fournisseur, la date d’acquisition, le numéro de facture, la valeur d’acquisition HT et le site d’affectation (premier niveau du lieu).
- [x] Pour un PV groupé, lister clairement les données de chaque objet et ajouter le montant total d’acquisition HT si les valeurs de tous les objets sont connues.
- [x] Ajouter le chapitre 2 « Mise en service » avec la déclaration « Je soussigné(e) <prénom nom de l’utilisateur en cours>, certifie que l’immobilisation décrite ci-dessous a été : » et les quatre états cochés/modifiables.
- [x] Ajouter la date effective de mise en service et la description libre de l’utilisation prévue.
- [x] Ajouter le chapitre 3 « Observations éventuelles » avec le texte libre multi-ligne.
- [x] Ajouter le chapitre 4 « Validations » et le tableau « Fonction | Nom | Signature | Date » avec les quatre lignes demandées.
- [x] Sous le tableau, afficher « Document établi le : <date actuelle>, lieu <Eichhoffen ou Barr> ».
- [x] Réutiliser le logo configuré et appliquer la couleur choisie aux titres et mots importants du nouveau PDF.
- [ ] Vérifier les sauts de page et les groupes longs afin que tableaux, observations et zones de validation restent lisibles et ne soient pas coupés de façon gênante.
- [x] Ne pas afficher de valeur « null » ou fictive pour les données GLPI vides ; omettre les lignes d’identification sans valeur.

### 4. Implémentation et intégration

- [x] Isoler le nouveau formulaire, la préparation des données et le rendu du PV dans une classe et un point d’entrée dédiés.
- [x] Réutiliser Dompdf, le logo et les réglages de couleur sans changer le traitement de l’inventaire utilisateur.
- [x] Formater les dates et montants, et prendre en charge les caractères accentués et textes multi-lignes.
- [x] Protéger le rendu contre le HTML injecté par les noms de matériels, fournisseurs et champs libres.
- [ ] Vérifier en exécution qu’un champ facultatif vide ne déclenche ni notice, warning, erreur SQL ni échec Dompdf.
- [x] Mettre à jour les libellés et la documentation utilisateur du plugin.
- [x] Incrémenter la version du plugin et les métadonnées catalogue conformément à la convention du dépôt.

### 5. Vérifications et livraison

- [x] Relire le diff ciblé : seuls les fichiers du PV, sa documentation, les métadonnées et le journal d’avancement sont concernés ; aucun fichier de `dompdf/`, des captures ou des traductions générées n’est inclus.
- [x] Vérifier statiquement les chemins de génération, les droits, l’échappement et le comportement des champs absents ou vides ; `php -l` passe pour les trois fichiers PHP modifiés.
- [ ] Vérifier un PV d’un matériel avec tous les champs remplis.
- [ ] Vérifier un PV d’un matériel avec numéro de série, fournisseur, date, facture, valeur ou lieu manquant.
- [ ] Vérifier un PV groupé avec plusieurs objets au même numéro immo, le détail de chaque objet et le montant total.
- [ ] Vérifier qu’une sélection avec des numéros immo différents est refusée proprement.
- [ ] Vérifier le nom du signataire connecté, les états précochés, les champs libres, les radios Eichhoffen/Barr et la date du document.
- [ ] Vérifier logo et couleur configurés, ainsi que les sauts de page pour un document long.
- [ ] Vérifier une génération de document d’inventaire utilisateur avant et après les changements, afin de confirmer l’absence de régression.
- [ ] Valider les scénarios sur une instance de test compatible avec la version GLPI cible avant toute mise en production.
- [ ] Committer chaque étape cohérente de la fonctionnalité en ne sélectionnant que les fichiers concernés ; ne jamais inclure les modifications locales préexistantes sans rapport.

### Journal de progression

- 2026-10-19 — Demande enregistrée et checklist initiale commitée avant l’implémentation.
- 2026-10-19 — Premier parcours ajouté dans un onglet dédié aux matériels : génération pour un matériel ou une sélection au même numéro immo, saisies de mise en service, modèle PDF sélectionnable pour logo/couleurs, document GLPI lié aux matériels. Vérifications PHP de syntaxe réussies ; essais fonctionnels GLPI 11/12 et rendu PDF restent à faire.
- 2026-10-19 — Liens de la page plugin GLPI et du catalogue mis à jour vers `https://github.com/Cyril6789/protocolsmanager`, à la demande de l’utilisateur.
