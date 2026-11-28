# Mods et extensions RimWorld

Ce projet regroupe les mods locaux et les extensions de traduction/correction pour les mods RimWorld.

Site web : https://rimworld.skoltroy.com/

- Dans le workspace VS Code, **Rimworld/Mods** pointe vers `D:\Games\RimWorld\Mods` et **Skoltroy/extensions** pointe vers la racine de ce dépôt; les archives sont dans `extensions\`.
- [Catalogue des extensions et des mods suivis](extensions/ReadMe.md)
- Les ID numériques désignent les dossiers Workshop dans `D:\Games\RimWorld\Mods`.

## Workflow de traduction

Le workflow de traduction est préparé par [Update-RimWorldModTranslations.ps1](.tools/Update-RimWorldModTranslations.ps1). Il prend en charge les deux modes suivants :

- **ID fournis** : traiter un ou plusieurs ID numériques indiqués dans le chat.
- **Découverte** : comparer les dossiers numériques du dossier du jeu aux ID connus dans ce README, le catalogue `extensions/ReadMe.md` et les archives `extensions\<ID> - French TradFix.zip`. La racine `Mods` de ce projet contient actuellement des mods nommés, pas des dossiers numériques; elle n’est donc pas prise pour un inventaire d’ID.

Le rapport distingue une traduction **complète** (audit de couverture et revue de toutes les entrées sources) d’un **complément** (ajout des clés manquantes et revue des valeurs exactement identiques à l’anglais). Les deux modes préservent les traductions françaises correctes : « complète » ne signifie pas réécrire aveuglément toutes les chaînes. Une égalité anglais/français est un indice, pas une preuve d’erreur.

Pour déclarer un lot complet, vérifier séparément que les clés sources sont couvertes, que les valeurs françaises sont effectivement traduites ou volontairement partagées (noms propres, termes techniques, etc.), et que les ressources chargées sont prises en compte. Les compteurs du rapport signalent des clés à revoir, pas des traductions à remplacer automatiquement. Si `Languages\English` est absent, le rapport ne peut pas mesurer la couverture : examiner les ressources French existantes, `LoadFolders.xml` et les Defs chargées, puis consigner les textes ambigus ou sans source fiable plutôt que de les deviner. Le script ne traduit pas automatiquement et ne modifie pas les ressources pendant la préparation du rapport.

### Utilisation

Depuis la racine du projet, dans PowerShell :

```powershell
# Poser les questions dans le terminal, puis préparer un rapport
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\.tools\Update-RimWorldModTranslations.ps1 -SelectionMode Ask -TranslationMode Ask -ProjectPath '.'

# Découvrir les nouveaux ID non suivis et analyser les traductions complémentaires
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\.tools\Update-RimWorldModTranslations.ps1 -SelectionMode Discover -TranslationMode Supplement -ProjectPath '.'

# Préparer l’analyse complète d’ID précis
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\.tools\Update-RimWorldModTranslations.ps1 -SelectionMode Explicit -ModId 1234567890,2345678901 -TranslationMode Complete -ProjectPath '.'

# Après traduction et validation, exporter uniquement les ressources French
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\.tools\Update-RimWorldModTranslations.ps1 -Action Export -ModId 1234567890 -ProjectPath '.'
```

Les rapports JSON sont écrits sous `.tools/reports` et ignorés par Git. L’export copie uniquement les fichiers sous `Languages\French` vers `.tools\exports\<ID>`, conserve les chemins relatifs et vérifie chaque copie par SHA256. Il ne crée pas de dossier décompressé dans `extensions`. Les sauvegardes de packages dans `extensions` sont des archives ZIP nommées d’après le dossier du package (`<ID> - French TradFix.zip` ou `<nom> - French TradFix.zip`) ; leur contenu est le package lui-même, sans dossier enveloppe. Pour les mettre à jour, travailler dans un dossier temporaire hors de `extensions`, puis remplacer l’archive seulement après validation.

Tous les mods et packages gérés par Skoltroy doivent utiliser `<url>https://rimworld.skoltroy.com</url>` dans `About\About.xml`. Lorsqu’un lien source ou Workshop est remplacé, conserver cette référence dans la description; ne jamais modifier les métadonnées du mod source Workshop.

### Previews des packages français

- Dériver la preview du visuel `About\Preview.png` du mod source sans modifier ce dernier. Ajouter le drapeau français en haut à droite.
- Pour les previews avec visuel source, placer le crédit dans un panneau contrasté et encadré en haut à gauche : `French Trad by Skoltroy` pour une traduction complète, `French TradFix by Skoltroy` pour un complément, ou `French Version by Skoltroy` pour une version française recompilée sans fichier de langue.
- Si le mod source n’a pas de preview, créer une carte PNG rectangulaire 16:9 à fond gris. Afficher le nom court du composant centré en bas (retirer les suffixes comme ` - French TradFix` ou ` - French Translation`), puis placer le panneau de crédit encadré en haut à droite, immédiatement à gauche du drapeau français.
- Garder la preview du dossier live identique à celle du ZIP de sauvegarde et vérifier l’archive après sa régénération.

Le script automatise la sélection, l’inventaire et l’export; l’agent de traduction réalise le travail linguistique. Les ressources anglaises sont comparées aux traductions françaises par chemin relatif. Toujours essayer d’ajouter des ressources `Languages\French` chargeables lorsque c’est possible; l’absence de ressources `Languages\English` n’empêche pas d’examiner les Defs, patches ou autres sources. Si les textes utilisateur sont intégrés à une DLL et ne peuvent pas raisonnablement être fournis par des ressources françaises, décompiler l’assembly, traduire les chaînes concernées et recompiler avec les références compatibles, sans modifier la DLL du mod original. Si cette voie échoue, signaler précisément la limite. Le bilan doit distinguer les clés manquantes, les valeurs identiques à examiner et les cas qui ne peuvent pas être vérifiés de façon fiable.

## Agents et skills du projet

### Agents

- [RimWorld Mod Localizer](.github/agents/rimworld-mod-localizer.agent.md) : sélection d’ID, traduction intégrale ou complémentaire, validation et export français.
- [RimWorld Log Debugger](.github/agents/rimworld-log-debugger.agent.md) : diagnostic fondé sur les journaux, les rapports d’erreur de traduction et les versions/mods effectivement chargés.

### Skills

- [rimworld-mod-localization](.github/skills/rimworld-mod-localization/SKILL.md) : conventions de traduction, comparaison des clés, préservation des tokens, versions chargées et export.
- [rimworld-log-debugging](.github/skills/rimworld-log-debugging/SKILL.md) : lecture prudente des logs et rapports de traduction, recherche de la cause et validation des corrections.

Ces instructions s’appuient sur les vérifications déjà utilisées dans ce projet : XML valide, parité des clés et paramètres de format, prise en compte de `LoadFolders.xml`, export limité au français et vérification de copie. Une trace de pile générique ne suffit pas à attribuer un avertissement de traduction à un mod; demander le rapport de traduction lorsque les entrées fautives ne sont pas identifiées.

## Tests

Exécuter les tests PowerShell du sélecteur, du rapport de lacunes et de l’export :

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\tests\Test-TranslationWorkflow.ps1
```

## Construction de la version publiée

```powershell
php .tools/build-publish.php
```

Tâches VS Code : **build** et **publish** (`.vscode/tasks.json`).

Dépendances : PHP 8.1 ou plus récent (CLI), extension `gd` et `curl`. Le script les contrôle et s'arrête en signalant ce qui manque. Rien n'est à installer sur IONOS.
Bibliothèque commune : `.tools/publish-lib.php` (minification, optimisation des images, copie).

Le cache écrit par le site (`cache/`) n'est ni publié ni effacé.

## Conventions et bonnes pratiques

- PHP 8 avec `declare(strict_types=1)` dans les nouveaux fichiers ; MariaDB uniquement, requêtes préparées.
- Échappement à la sortie, jeton CSRF sur les formulaires, aucun secret ni fichier de configuration dans Git.
- Ne jamais modifier `.publish/` à la main : il est régénéré par le script, puis commité.
- Vérifier avant de publier : `php -l` sur les fichiers modifiés et parcours du site en local.
