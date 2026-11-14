# Guide détaillé d'exportation des plans

Ce guide présente chaque option dans l'ordre où elle apparaît dans la fenêtre d'exportation, de haut en bas.

---

## 1. Barre d'outils supérieure (configuration et automatisation)

Tout en haut de la fenêtre se trouve une rangée de boutons permettant de gérer l'état des champs et la traduction :

1. **Activer la sauvegarde temporaire** : lorsque cette option est cochée, le contenu saisi est conservé même si vous fermez la fenêtre. Cliquez sur « Réinitialiser le contenu » pour l'effacer.
2. **Changer de langue** : cliquez pour changer la langue en cours de modification. Vous pouvez saisir des noms et descriptions spécifiques à chaque langue.
3. **Traduction automatique** : cliquez pour utiliser l'IA et renseigner automatiquement les traductions dans toutes les autres langues à partir du texte saisi.
4. **Configuration de la traduction automatique** : configurez la clé API et l'adresse du modèle nécessaires à la traduction par IA.

---

## 2. Informations principales (obligatoires et facultatives)

Il s'agit des informations d'identification du plan :

### 1. Nom du bâtiment (obligatoire)
- **Explication** : détermine le nom du fichier enregistré.
- **Restrictions** : ne peut pas contenir `< > : " / \ | ? *`.
- **Validation** : « Nom valide » ou un message d'erreur s'affiche en temps réel sous le champ de saisie.

### 2. Description (facultative)
- **Explication** : présentation détaillée de la fonction ou du contexte du bâtiment. Les retours à la ligne sont acceptés.

### 3. Auteur (facultatif)
- **Restrictions** : **ne peut pas contenir d'espaces** ni de symboles spéciaux (comme `@ # $ %`), et ne peut pas commencer par un chiffre.
- **Suggestion** : utilisez l'anglais ou le pinyin, par exemple `Artisan_Li`.

### 4. Zone d'exportation
- **Menu déroulant** : « Rectangle » est sélectionné par défaut.
- **Exportation d'une zone autorisée** : si vous avez défini une « zone autorisée » sur la carte et qu'elle contient au moins une cellule, vous pouvez sélectionner son nom ici.

![Sélection de la zone d'exportation](ExportAreaSelection)

Vous pouvez créer une nouvelle zone autorisée, puis utiliser « Agrandir la zone autorisée » pour couvrir la zone du bâtiment à exporter.

![Ajouter une zone d'exportation](AddExportArea)

Sélectionnez ensuite cette zone autorisée ici et lancez l'exportation. Vous obtiendrez ainsi un export de forme irrégulière, limité aux parties sélectionnées.
L'exportation par zone autorisée peut réduire considérablement l'inclusion de contenu inutile et produire un plan plus propre et plus précis.
De plus, puisque la sélection rectangulaire ne définit alors plus la zone d'exportation, vous pouvez choisir librement une meilleure zone de capture d'écran.

![Exemple de zone d'exportation](ExportAreaExample)

Par exemple, bien que le rectangle sélectionné sur ce plan soit grand, la taille réelle est déterminée par votre zone autorisée.
S'il s'agit d'un très grand plan de base, vous pouvez répartir le plan entre plusieurs zones d'exportation pour réduire les ralentissements pendant le placement.

### 5. Étiquettes (obligatoires)
- Cliquez sur « Sélectionner les étiquettes » pour choisir plusieurs étiquettes dans la grille colorée d'origine. Cliquez sur une étiquette pour la sélectionner ou la désélectionner, puis sur « OK » pour appliquer.
- Sélectionnez de 1 à 5 étiquettes par exportation. Les catégories existantes ainsi que Bâtiment de départ, Transport, Pépinière, Salle des gènes, Chambre de dormance, Salle des mécaïdes, Esthétique, Pratique, Par phases, Rituel de l'anomalie, Salle de confinement, Hygiène, Vaisseau spatial, Module de pièce, Cuisine, Stockage frigorifique et Position de combat sont des étiquettes officielles et ne peuvent pas être supprimées du catalogue.
- Saisissez une étiquette personnalisée en bas et cliquez sur « Ajouter ». Elle est sélectionnée automatiquement si moins de cinq étiquettes le sont ; sinon, elle est uniquement enregistrée dans la bibliothèque d'étiquettes. Les étiquettes personnalisées conservent leur texte d'origine et peuvent contenir des espaces. Elles sont grises, marquées d'un astérisque (*) et conservées séparément jusqu'à leur suppression. Survolez-les pour afficher une info-bulle ou utilisez le bouton de suppression en haut à droite. Les doublons sont refusés avec un message ; les alias chinois et anglais reconnus désignent la même étiquette.
- « Effacer » désélectionne toutes les étiquettes. Annuler ou fermer n'applique pas les nouvelles sélections. L'ajout ou la suppression d'étiquettes dans la bibliothèque prend effet immédiatement et persiste après une annulation ou « Réinitialiser le contenu ».
- La sélection est partagée entre les langues. Les libellés intégrés sont traduits ; les étiquettes personnalisées sont exclues de la traduction par IA.
- À l'exportation, la première catégorie d'origine sélectionnée dans l'ordre de la liste est utilisée comme catégorie de compatibilité. En l'absence de catégorie d'origine, une catégorie personnalisée est utilisée. Les favoris et la compatibilité sont calculés localement et ne nécessitent pas d'étiquettes d'exportation.

---

## 3. Cartes des fonctions avancées (à activer si nécessaire)

Ces cartes n'apparaissent qu'après avoir coché les options correspondantes en bas de la fenêtre :

### 1. Groupe pliable
- « Groupes récents » réutilise les trois noms de groupe distincts les plus récemment exportés, du plus récent au plus ancien. L'historique est conservé lors de la réinitialisation du contenu. Informations
- **Nom du groupe** : les plans portant le même nom de groupe seront regroupés dans la bibliothèque.

![Exemple de groupe pliable](FoldGroupExample)

- **Ordre d'affichage** : plus le nombre est petit, plus le plan apparaît haut dans la liste du groupe.

![Classement du groupe pliable](FoldGroupRanking)

- **Définir la couverture** : un seul plan du groupe peut servir de couverture.
Cette fonction permet aux créateurs de publier des plans en plusieurs phases, par exemple différentes étapes de construction d'une base, regroupées pour en faciliter la consultation.

### 2. URL associée
- **Nom de l'URL** : par exemple, « Démonstration vidéo » ou « Page d'accueil de l'auteur ».

![Emplacement de saisie de l'URL](UrlInputLocation)

- **Adresse de l'URL** : seuls les domaines autorisés sont pris en charge, comme BiliBili, YouTube, Xiaoheihe, etc.

![Résultat final de l'URL](UrlFinalEffect)

### 3. ID de bâtiment personnalisé
- **Utilisation** : activez « Indiquer l'ID du bâtiment » uniquement pour **mettre à jour et remplacer** un plan cloud existant, puis saisissez son BuildingID. Laissez cette option décochée pour les exports normaux et les nouveaux envois ; un nouvel ID est généré automatiquement.

![Saisie de l'ID du bâtiment](BuildingIDInput)

Notez que la mise à jour d'un plan prend de 3 à 5 minutes et que vous devez redémarrer le jeu pour voir réellement la modification.
---

### 4. Filtrer le contenu exporté
- Activez « Affiner le contenu après la sélection » et choisissez ce qui doit être enregistré après avoir sélectionné la zone. Les fichiers ne sont écrits qu'après confirmation.
- Par défaut, les exports normaux incluent les bâtiments, les sols artificiels et fondations, les structures naturelles, les toits et les zones de toit. Les exports de carte entière sélectionnent davantage de contenu. Toutes les catégories sont initialement repliées.
- Les cases parentes des catégories et des mods ont trois états : cochée pour tout, barre jaune pour une sélection partielle, croix pour rien. Cliquez sur une case parente pour sélectionner ou désélectionner ses éléments, ou faites glisser sur plusieurs cases pour les sélectionner ou les désélectionner. Les entrées de chaque groupe sont triées par quantité décroissante.
- La restauration d'un plan d'exportation complète (badge violet) depuis un PNG ou un XML active automatiquement « Affiner le contenu après la sélection ». Cette sélection est partagée entre les langues, puis enregistrée et restaurée par le système de sauvegarde temporaire. « Réinitialiser le contenu » l'efface.
- Les mods sources apparaissent dans la colonne du milieu. Les mods décochés bloquent leurs entrées, même lorsqu'elles sont sélectionnées dans Contenu ; la catégorie doit également être activée.
- Les personnages et les animaux nécessitent Character Editor. Les factures et filtres de stockage nécessitent Save Storage Settings. Le contenu supplémentaire exporté déclenche le badge violet ; les exports de carte entière conservent le badge rose.

---

## 5. Exécuter la sélection
   Notez que la sélection de la capture d'écran se base entièrement sur les **coordonnées de l'écran**. Les coins supérieur gauche et inférieur droit sur lesquels vous cliquez déterminent la zone de capture. Si vous déplacez la caméra pendant la capture, la zone ne change pas : elle correspond toujours aux positions à l'écran où vous avez cliqué, et non aux coordonnées du monde sur la carte du jeu !

## 6. Rapport des mods

Pour connaître le mod auquel appartiennent les objets d'une construction, vous pouvez utiliser la fonction Rapport des mods.

![Bouton Rapport des mods](ModReportBtn)

Cliquez dessus et sélectionnez la zone dont vous souhaitez vérifier les mods.

![Sélection de la zone du rapport des mods](ModReportSelectArea)

La page Rapport des mods affiche le contenu des mods présent dans la zone.

![Détails du rapport des mods](ModReportDetails)

Le bouton **Afficher** peut mettre en évidence les cellules occupées par les bâtiments du mod. Si ces éléments sont sélectionnables, ils le seront également. Le bouton **Supprimer** efface directement ces objets.

![Mise en évidence du rapport des mods](ModReportHighlight)

L'option **Remplacer** permet de choisir un bâtiment d'un mod donné pour remplacer ce bâtiment ou ce sol. Les **Options avancées** permettent de choisir l'orientation et le matériau. Notez que la sélection peut remplacer directement d'autres bâtiments.

![Page de remplacement du rapport des mods](ModReportReplace)

Cette fonction vise à aider les créateurs de plans à réduire les dépendances à des mods.

## Affiner le contenu après la sélection

Activez « Affiner le contenu après la sélection » en bas de la fenêtre d'exportation. Après avoir sélectionné le plan ou la zone de capture d'écran, une fenêtre fixe de sélection du contenu apparaît. Les exports normaux commencent avec le contenu des bâtiments sélectionné ; les exports de carte entière utilisent les paramètres par défaut correspondants. Confirmez pour enregistrer ; annulez pour ne créer aucun fichier. Cette fenêtre remplace l'ancienne case « Exporter tous les objets ».

Il y a 12 options. Les sols artificiels et les fondations partagent une option. Les personnages et animaux sont limités à la faction du joueur, y compris les méchas du joueur. Configuration inclut les factures, filtres de stockage et zones de stockage ; Recherche enregistre les projets terminés.

Les exports de carte entière conservent toujours le badge rose. Les autres exports utilisent le badge violet si des personnages, animaux, objets au sol, saletés, éléments de configuration ou recherches terminées sont effectivement enregistrés. Une option vide sélectionnée ne déclenche pas le badge violet. Le nombre de dépendances aux mods tiers des autres exports est recalculé en fonction du contenu enregistré.
