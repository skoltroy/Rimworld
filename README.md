# Mods et extensions RimWorld

Ce projet regroupe les mods locaux et les extensions de traduction/correction pour les mods RimWorld.

- [Catalogue des extensions et des mods suivis](Extensions/ReadMe.md)
- Les ID numériques désignent les dossiers Workshop dans `D:\Games\RimWorld\Mods`.

## Workflow de traduction

Le workflow de traduction est préparé par [Update-RimWorldModTranslations.ps1](tools/Update-RimWorldModTranslations.ps1). Il prend en charge les deux modes suivants :

- **ID fournis** : traiter un ou plusieurs ID numériques indiqués dans le chat.
- **Découverte** : comparer les dossiers numériques du dossier du jeu aux ID connus dans ce README, le catalogue `Extensions/ReadMe.md` et les dossiers `Extensions\<ID>`. La racine `Mods` de ce projet contient actuellement des mods nommés, pas des dossiers numériques; elle n’est donc pas prise pour un inventaire d’ID.

Le rapport distingue une traduction **complète** (audit de couverture et revue de toutes les entrées sources) d’un **complément** (ajout des clés manquantes et revue des valeurs exactement identiques à l’anglais). Les deux modes préservent les traductions françaises correctes : « complète » ne signifie pas réécrire aveuglément toutes les chaînes. Une égalité anglais/français est un indice, pas une preuve d’erreur.

Pour déclarer un lot complet, vérifier séparément que les clés sources sont couvertes, que les valeurs françaises sont effectivement traduites ou volontairement partagées (noms propres, termes techniques, etc.), et que les ressources chargées sont prises en compte. Les compteurs du rapport signalent des clés à revoir, pas des traductions à remplacer automatiquement. Si `Languages\English` est absent, le rapport ne peut pas mesurer la couverture : examiner les ressources French existantes, `LoadFolders.xml` et les Defs chargées, puis consigner les textes ambigus ou sans source fiable plutôt que de les deviner. Le script ne traduit pas automatiquement et ne modifie pas les ressources pendant la préparation du rapport.

### Utilisation

Depuis la racine du projet, dans PowerShell :

```powershell
# Poser les questions dans le terminal, puis préparer un rapport
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\tools\Update-RimWorldModTranslations.ps1 -SelectionMode Ask -TranslationMode Ask

# Découvrir les nouveaux ID non suivis et analyser les traductions complémentaires
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\tools\Update-RimWorldModTranslations.ps1 -SelectionMode Discover -TranslationMode Supplement

# Préparer l’analyse complète d’ID précis
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\tools\Update-RimWorldModTranslations.ps1 -SelectionMode Explicit -ModId 1234567890,2345678901 -TranslationMode Complete

# Après traduction et validation, exporter uniquement les ressources French
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\tools\Update-RimWorldModTranslations.ps1 -Action Export -ModId 1234567890
```

Les rapports JSON sont écrits sous `tools/reports` et ignorés par Git. L’export copie uniquement les fichiers sous `Languages\French` vers `Extensions\<ID>`, conserve les chemins relatifs et vérifie chaque copie par SHA256. Il n’efface aucun fichier.

Le script automatise la sélection, l’inventaire et l’export; l’agent de traduction réalise le travail linguistique. Les ressources anglaises sont comparées aux traductions françaises par chemin relatif. Les mods sans ressources `Languages\English` sont signalés pour examen des Defs chargées; l’absence de source anglaise ne prouve ni l’absence de texte utilisateur ni l’exhaustivité du français. Le bilan doit distinguer les clés manquantes, les valeurs identiques à examiner et les cas qui ne peuvent pas être vérifiés de façon fiable.

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
