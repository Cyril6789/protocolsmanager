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
- [x] Ne pas afficher de valeur « null » ou fictive pour les données GLPI vides ; conserver le tableau d’identification complet avec des cellules vides.

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
- 2026-10-19 — Incident après chargement du nouvel onglet : les journaux de `glpi.it.web` ont révélé une erreur fatale PHP due au conflit entre la méthode statique `showForm()` et `CommonDBTM::showForm()`. La méthode a été renommée `renderCommissionForm()` et le fichier passe `php -l` dans le conteneur. Le parcours ordinateur doit être rechargé pour confirmer le rétablissement.
- 2026-10-19 — Ajustement visuel du PV : logo réduit et centré, numéro immo placé à gauche avec libellé noir et numéro/soulignement dans la couleur du modèle, suppression des doublons du chapitre 1, regroupement inventaire/série sur une ligne et largeurs de colonnes de validation rééquilibrées.
- [x] Renommer l’onglet matériel « PV de mise en service » et réorganiser le formulaire en sections lisibles avec sélection des matériels, modèle PDF, date, états, utilisation prévue, lieu et observations.
- [x] Ajouter au formulaire un tableau des PV GLPI associés au matériel, avec document, date, fichier, créateur et commentaire.
- [x] Forcer le chapitre 4 « Validations » à commencer sur une nouvelle page PDF (page 2 lorsque le contenu précédent tient sur la première page).
- [ ] Vérifier visuellement le formulaire dans GLPI 12 et confirmer que le tableau liste les PV associés à un matériel simple et à un PV groupé.
- [ ] Générer un PDF représentatif sur une instance de test et confirmer que le chapitre 4 commence bien en haut de la page 2.
- 2026-10-19 — Mise en page du formulaire et tableau des documents générés ajoutés ; chapitre 4 configuré avec un saut de page explicite. `php -l` passe localement. Vérification visuelle en GLPI et rendu PDF à confirmer sur instance de test.
- [x] Autoriser la génération d’un PV autonome lorsque le numéro immo est vide ; enregistrer le document sans association à un matériel et laisser le numéro immo vide dans le PDF.
- [ ] Vérifier la génération autonome sur une instance de test et confirmer que le document reste sans lien matériel dans GLPI.
- 2026-10-19 — La validation du formulaire accepte maintenant une sélection vide uniquement lorsque le numéro immo est vide. Le PDF est créé sans lignes matériel ni liens GLPI ; avec un numéro immo renseigné, une sélection valide reste requise.
- [x] Garder le numéro immo et la date effective facultatifs ; afficher leurs valeurs vides dans le PDF sans bloquer la génération.
- [x] Intercepter les exceptions du rendu PDF et de l’enregistrement GLPI, journaliser leur détail et afficher un message compréhensible au lieu d’une erreur 500.
- [ ] Reproduire les cas signalés (numéro immo vide, date effective vide, puis les deux vides) sur une instance de test.
- 2026-10-19 — Confirmation statique : immo et date vide sont acceptés ; ajout d’une gestion d’erreur autour de Dompdf et de la création du document GLPI afin que les erreurs réelles soient consignées et remontées à l’utilisateur. Scénarios à reproduire en test.
- [x] Afficher le formulaire autonome si les données de gestion d’un matériel ne sont pas disponibles, afin qu’une fiche dont l’onglet Gestion est vide puisse quand même générer un PV sans lien matériel.
- 2026-10-19 — Cas signalé sur une imprimante sans données de gestion : `loadAsset()` pouvait retourner `null` et empêcher l’affichage du formulaire. Le parcours affiche maintenant le formulaire autonome dans ce cas.
- [x] Rattacher au matériel courant tout PV généré depuis son onglet, même lorsque le numéro immo est vide ; conserver un PDF sans numéro immo et sans regroupement d’autres matériels.
- [ ] Confirmer dans GLPI que le PV de l’imprimante 64 apparaît dans ses documents associés et que le numéro immo reste vide dans le PDF.
- 2026-10-19 — Correction après constat d’un document orphelin : le formulaire transmet l’identifiant du matériel courant, le serveur le revalide et l’ajoute aux associations GLPI et aux données d’identification du PV.
- [x] Conserver toutes les lignes du tableau d’identification dans le PDF lorsque certaines ou toutes les données matérielles sont absentes ; laisser les valeurs manquantes en blanc.
- [x] Afficher « RAS » dans les observations du PDF lorsque le champ correspondant est laissé vide à la génération.
- [x] Afficher l’icône maison de la carte du plugin dans la gestion des plugins GLPI et la faire pointer vers le dépôt GitHub du plugin.
- 2026-10-19 — La carte Marketplace GLPI 12 lit le lien maison depuis les métadonnées du catalogue distant et ignore le champ `homepage` local. Ajout d’un script frontend ciblé sur la carte `protocolsmanager`, avec mise à jour de la version du plugin pour invalider les caches d’assets.
- [x] Détecter aussi la carte si GLPI l’injecte après le chargement initial via AJAX.
- 2026-10-19 — Après retour utilisateur indiquant que l’icône n’apparaissait toujours pas, vérification du script servi (HTTP 200), de l’URL homepage en base et du chargement asynchrone potentiel. Le script observe maintenant le DOM entier et ajoute le lien dès que la carte apparaît.

## Extension de l’onglet Procès-Verbaux : mise au rebut

- [x] Ajouter un choix entre le PV de mise en service existant et le nouveau PV de mise au rebut.
- [x] Conserver le formulaire et le PDF de mise en service existants après sélection de ce type.
- [x] Ajouter le formulaire de mise au rebut avec date du jour par défaut, service Informatique, nom et fonction de l’utilisateur connecté.
- [x] Rendre obligatoires le motif et le mode de mise au rebut, côté formulaire et côté serveur.
- [x] Générer le chapitre 1 d’informations générales et le chapitre 2 avec désignation, inventaire, série, date disponible, montant, marque, modèle et type.
- [x] Ajouter les chapitres motif, mode de mise au rebut et signature, avec nom prérempli et date/signature laissées vides.
- [x] Réutiliser logo et couleurs du modèle PDF, afficher le numéro immo en tête et conserver un PDF générable lorsque ce numéro est vide.
- [x] Rattacher le document GLPI à la licence ou au matériel courant, ou à chaque matériel sélectionné pour un immo partagé ; afficher les deux types de PV dans la liste.
- [x] Mettre à jour le README et incrémenter la version du plugin.
- [x] Vérifier `php -l` pour les fichiers PHP modifiés et `git diff --check`.
- [ ] Vérifier sur GLPI la sélection des deux types et confirmer que le PV de mise en service garde son rendu et son parcours.
- [ ] Générer un PV de mise au rebut avec tous les champs, puis avec un numéro immo vide et des données de gestion absentes.
- [ ] Vérifier les refus lorsque le motif ou le mode de mise au rebut est vide, et le rattachement du document sur une licence, un objet simple et un groupe au même numéro immo.
- [x] Afficher les choix de type de PV directement dans la fiche GLPI, sans navigation vers une réponse AJAX isolée.
- 2026-10-19 — Correction du sélecteur : le contenu des onglets est chargé par GLPI via AJAX ; les liens construits à partir de l’URL courante ouvraient la réponse seule hors de la fiche. Les deux formulaires se déplient maintenant dans le contexte de l’objet.
- [x] Compacter le PDF de mise au rebut : numéro immo et date aux deux extrémités du logo, demandeur et fonction sur une ligne, inventaire et série sur une ligne, marque/modèle/type sur une ligne, espacements et tableau réduits.
- [x] Remplacer la saisie libre du mode de mise au rebut par les choix radio Destruction, Don, Vente symbolique, Recyclage DEEE après dépose du disque dur et Autre ; exiger une précision pour Autre et valider aussi côté serveur.
- [x] Afficher dans le PDF le choix de mode retenu et, le cas échéant, la précision fournie pour Autre.
- [x] Documenter les choix de mise au rebut dans le README et incrémenter la version du plugin.
- [x] Vérifier `php -l` sur les fichiers PHP modifiés et `git diff --check`.
- [ ] Vérifier dans GLPI l’affichage et la sélection des cinq modes et le champ de précision pour Autre.
- [ ] Générer un PV pour chacun des quatre choix prédéfinis, puis un PV Autre avec précision ; vérifier le mode reporté dans le PDF.
- [ ] Vérifier que le serveur refuse une raison vide, un mode absent/invalide et Autre sans précision.
- [x] Réaérer la mise en page du PV de mise au rebut en gardant les champs regroupés sur une ligne uniquement là où demandé ; réserver une zone manuscrite au numéro immo vide au-dessus du trait.
- [x] Rétablir la taille du logo du PV de mise au rebut à sa taille précédente (120 × 120 px maximum), tout en le gardant centré.
- [x] Élargir légèrement la colonne « Service concerné » du tableau d’informations générales.
- [x] Fixer la première colonne de libellés du chapitre 2 à 30 % directement sur ses cellules (Désignation, inventaire, date de mise en service, marque/modèle), en réduisant la première colonne de valeur afin que les libellés ne soient pas tronqués ou tassés.
- [x] Incrémenter la version du plugin après l’ajustement de présentation.
- [x] Vérifier `php -l` sur les fichiers PHP modifiés et `git diff --check`.
- [ ] Vérifier dans le PDF imprimé que l’espacement est confortable et que le numéro immo vide dispose d’une zone manuscrite avant son trait.
- [ ] Vérifier dans le PDF généré qu’un PV courant tient sur une page et que les textes longs ou les groupes de plusieurs objets restent lisibles.
