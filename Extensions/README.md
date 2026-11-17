# Extensions de mods RimWorld

Ce dossier contient des extensions pour des mods RimWorld publiés sur Steam :

- corrections d’erreurs liées au chargement des mods ;
- traductions françaises complètes ou complémentaires aux traductions existantes.

| Date | ID | Dossier | Mod | Version déclarée / versions de RimWorld | Travail effectué |
|---|---:|---|---|---|---|
| 17/11/2026 | 3808100707 | `3808100707` | Wraith & Nanite Gravtech | Non déclarée (RW 1.6, 1.7) | Traduction FR complète sans dossier English : 1 649 entrées extraites des Defs (labels, descriptions, jobString, rulesStrings, comps, Compatibility CE), 31 fichiers DefInjected ; XML valide. Restent hors DefInjected : 3 libellés de `Patches/Gravship_VanillaParity.xml` (entrées de `RitualOutcomeEffectDef`). Export SHA-256 vérifié. |
| 17/11/2026 | 3405333015 | `3405333015` | Stargates! to Skipgates! | Non déclarée (RW 1.6) | Traduction FR des 37 entrées des patches (`StargateTextPatches.xml`, `DHD.xml`) en DefInjected. Le mod de base Stargates! (2831698056) n'est pas installé : les textes appliqués à ses Defs sont couverts par clés, mais les règles de quête (`questNameRules`, `questDescriptionRules`) ne sont pas traduites car leur index de liste dépend de Defs absentes. Export SHA-256 vérifié. |
| 17/11/2026 | 3754128439 | `3754128439` | Defensive Network | Non déclarée (RW 1.5, 1.6) | Correctif de chargement : `DNX_Gun_HunterKillerCannon` utilise `Gun_AutocannonTurret` et `DNX_Gun_HunterKillerRocketRack` utilise `Mortar_LaunchA` (sons absents remplacés). Patch `Patches/HunterKiller_Sound_Fix.xml`. |
| 17/11/2026 | 1888778429 | `1888778429` | Vault-Tec - Handy things for your vault! | 1.6 | Correctif de texture : `FakerockWall` partageait `Name="Wall"` avec le mur vanilla et était écrasé par le patch ReBuild. Patch `1.6/Patches/FakerockWall_Texture_Fix.xml` (Name renommé, texture `Rock_Atlas` et icône restaurées). |
| 17/11/2026 | 2478833213 | `2478833213` | RPG Style Inventory Revamped | 1.2–1.6 | Traduction FR : le dossier de jeu contenait une version partielle (7 clés) ; synchronisé depuis cette extension (42 clés DefInjected, 45 clés Keyed). XML validé. |
| 17/11/2026 | 3698412697 | `3698412697` | FIP - Hubris | 1.6, 1.7 (RW 1.6, 1.7) | Traduction FR complète : 6 fichiers, 80 clés DefInjected/Keyed (révélations, concentration cryptique, totems et sanctuaires). Tokens `{USER_*}`, `{PSYCAST_label}` et `{0}`/`{1}` vérifiés ; XML valide ; export SHA-256 vérifié. |
| 17/11/2026 | 1662119905 | `1662119905` | Dubs Mint Minimap | 1.3.821 (RW 1.0, 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7) | Audit complet des ressources et Defs : les deux valeurs identiques à l’anglais (« Hostiles », « Silhouette ») sont correctes en français ; ressources préservées. |
| 16/11/2026 | 1677616980 | `1677616980` | Bionic icons | Non déclarée (RW 1.0, 1.1, 1.2, 1.3, 1.4, 1.5, 1.6) | Audit des ressources et Defs : aucune chaîne de jeu localisable ; aucune ressource FR créée. |
| 16/11/2026 | 2009463077 | `2009463077` | Harmony | 3.0.1.0 (RW 1.2, 1.3, 1.4, 1.5, 1.6, 1.7) | Audit des ressources chargées : aucune chaîne de jeu à traduire. |
| 17/11/2026 | 2142743468 | `2142743468` | Dev In Your Language | Non déclarée (RW 1.1, 1.2, 1.3, 1.4, 1.5, 1.6) | Audit complet de 2 758 clés/56 fichiers ; traduction des 7 candidats exacts, dont `DrawMapRooms` (« Afficher les pièces de la carte ») malgré la coquille source « Drap map rooms » ; chaînes substituées corrigées dans 24 fichiers, export validé. |
| 16/11/2026 | 2202185773 | `2202185773` | Smarter Construction | Non déclarée (RW 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7) | Audit des Defs chargées : aucune chaîne de jeu localisable ; aucune ressource FR créée. |
| 16/11/2026 | 2581693737 | `2581693737` | [NL] Facial Animation - Experimentals | Non déclarée (RW 1.3, 1.4, 1.5, 1.6, 1.7) | Audit des ressources et Defs chargées : aucune chaîne de jeu à traduire. |
| 16/11/2026 | 2865361569 | `2865361569` | Clean Textures | Non déclarée (RW 1.3, 1.4, 1.5, 1.6) | Audit : mod de textures ; aucune ressource linguistique ou chaîne de Def à traduire. |
| 16/11/2026 | 2888638004 | `2888638004` | Clean Textures Background | Non déclarée (RW 1.3, 1.4, 1.5, 1.6) | Audit : mod de fond d’écran ; aucune ressource linguistique ou chaîne de Def à traduire. |
| 16/11/2026 | 2980467701 | `2980467701` | Turret Vanilla Retexture and Restyle | Non déclarée (RW 1.4, 1.5, 1.6, 1.7) | Audit : mod de textures ; aucune ressource linguistique ou chaîne de Def à traduire. |
| 16/11/2026 | 3366505975 | `3366505975` | Food Texture Variety - Babies | Non déclarée (RW 1.5, 1.6, 1.7) | Audit des ressources et Defs chargées : aucune chaîne de jeu à traduire. |
| 16/11/2026 | 3366513037 | `3366513037` | Food Texture Variety - Replacement | Non déclarée (RW 1.5, 1.6, 1.7) | Audit des ressources et Defs chargées : aucune chaîne de jeu à traduire. |
| 17/11/2026 | 3403563363 | `3403563363` | Replace Context Menu | 1.2.9 (RW 1.5, 1.6) | Audit complet des 78 clés sur RW 1.5–1.6 ; traductions préservées, aucun candidat exact à corriger. |
| 16/11/2026 | 3426432471 | `3426432471` | Animal Use Dispenser | Non déclarée (RW 1.5, 1.6, 1.7) | Audit des ressources et Defs chargées : aucune chaîne de jeu à traduire. |
| 16/11/2026 | 3453364177 | `3453364177` | Rustic Meal Retexture | Non déclarée (RW 1.4, 1.5, 1.6, 1.7) | Audit : mod de textures ; aucune ressource linguistique ou chaîne de Def à traduire. |
| 16/11/2026 | 3525980713 | `3525980713` | Orbital Platforms | Non déclarée (RW 1.6) | Audit : 8 traductions DefInjected déjà présentes dans le mod ; conservées et exportées sans modification. |
| 16/11/2026 | 3545135569 | `3545135569` | Taste of Vanilla - Bean Turrets | Non déclarée (RW 1.5, 1.6, 1.7) | Audit : mod de textures ; aucune ressource linguistique ou chaîne de Def à traduire. |
| 17/11/2026 | 3697920753 | `3697920753` | Architect Menu Optimizer | Non déclarée (RW 1.6, 1.7) | Audit complet des 44 clés ; « Pagination » est un terme français valide et reste inchangé. |
| 17/11/2026 | 3754128439 | `3754128439` | Defensive Network | Non déclarée (RW 1.5, 1.6) | Audit complet : 239 clés chinoises traduites, 42 champs DefInjected ajoutés et 2 descriptions complétées depuis le chinois ; 45 entrées vérifiées via commentaires EN/Defs. 20 labels internes « Mote » exclus de l’interface. XML/tokens et export SHA-256 vérifiés. |
| 17/11/2026 | 974066449 | `974066449` | WeaponStats | Non déclarée (RW 1.0, 1.1, 1.2, 1.3, 1.4, 1.5, 1.6) | Audit complet des 47 clés ; « Hostiles », « Grenades », « DPS » et le champ vide sont des valeurs françaises/techniques valides. |
| Non documentée | 735106432 | `735106432` | EdB Prepare Carefully | Non déclarée (RW 1.1, 1.2, 1.3, 1.4, 1.5, 1.6) | Extension existante ; détail historique non consigné. |
| Non documentée | 761421485 | `761421485` | Allow Tool | Non déclarée (RW 1.0, 1.1, 1.2, 1.3, 1.4, 1.5, 1.6) | Extension existante ; détail historique non consigné. |
| Non documentée | 818773962 | `818773962` | HugsLib | Non déclarée (RW 1.0, 1.1, 1.2, 1.3, 1.4, 1.5, 1.6) | Extension existante ; détail historique non consigné. |
| Non documentée | 1279012058 | `1279012058` | Pick Up And Haul | Non déclarée (RW 1.0, 1.1, 1.2, 1.3, 1.4, 1.5, 1.6) | Extension existante ; détail historique non consigné. |
| Non documentée | 1434137894 | `1434137894` | RandomPlus | Non déclarée (RW 1.6, 1.5, 1.4, 1.3, 1.2) | Extension existante ; détail historique non consigné. |
| 15/11/2026 | 1446523594 | `1446523594` | Dubs Mint Menus | 1.3.1704 (RW 1.0, 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7) | Traduction FR de MintMenus, des boutons et des raccourcis clavier. |
| Non documentée | 1508850027 | `1508850027` | RimHUD | Non déclarée (RW 1.1, 1.2, 1.3, 1.4, 1.5, 1.6) | Extension existante ; détail historique non consigné. |
| Non documentée | 1534881550 | `1534881550` | Veggie Mix | Non déclarée (RW 1.0, 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7) | Extension existante ; détail historique non consigné. |
| Non documentée | 1534882140 | `1534882140` | Meat Blend | Non déclarée (RW 1.0, 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7) | Extension existante ; détail historique non consigné. |
| Non documentée | 1635901197 | `1635901197` | [NL] Facial Animation - WIP | Non déclarée (RW 1.0, 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7) | Extension existante ; détail historique non consigné. |
| Non documentée | 1668983184 | `1668983184` | RimThemes | Non déclarée (RW 1.0, 1.1, 1.2, 1.3, 1.4, 1.5, 1.6) | Extension existante ; détail historique non consigné. |
| Non documentée | 1678847247 | `1678847247` | Graphics Settings | Non déclarée (RW 1.2, 1.3, 1.4, 1.5, 1.6, 1.7) | Extension existante ; détail historique non consigné. |
| Non documentée | 1874644848 | `1874644848` | Character Editor | 1.7.1 (RW 1.0, 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7) | Extension existante ; détail historique non consigné. |
| Non documentée | 2013476665 | `2013476665` | Utility Columns | Non déclarée (RW 1.5, 1.6, 1.7) | Extension existante ; détail historique non consigné. |
| Non documentée | 2023507013 | `2023507013` | Vanilla Expanded Framework | Non déclarée (RW 1.0, 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7) | Extension existante ; détail historique non consigné. |
| Non documentée | 2030354690 | `2030354690` | Pulsefire Turret (Continued) | 1.7.0 (RW 1.0, 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7) | Extension existante ; détail historique non consigné. |
| Non documentée | 2102143149 | `2102143149` | Vanilla Furniture Expanded - Props and Decor | Non déclarée (RW 1.7, 1.5, 1.6) | Extension existante ; détail historique non consigné. |
| Non documentée | 2478833213 | `2478833213` | RPG Style Inventory Revamped | Non déclarée (RW 1.2, 1.3, 1.4, 1.5, 1.6) | Extension existante ; détail historique non consigné. |
| Non documentée | 2773943594 | `2773943594` | Geological Landforms | Non déclarée (RW 1.3, 1.4, 1.5, 1.6, 1.7) | Extension existante ; détail historique non consigné. |
| Non documentée | 2800857642 | `2800857642` | Map Preview | Non déclarée (RW 1.3, 1.4, 1.5, 1.6, 1.7) | Extension existante ; détail historique non consigné. |
| 15/11/2026 | 2871933948 | `2871933948` | Vanilla Animals Expanded | Non déclarée (RW 1.7, 1.5, 1.6) | Complément FR : 5 chaînes sur le rythme nycthéméral des animaux. |
| Non documentée | 2877292196 | `2877292196` | Yayo's Animation (Continued) | Non déclarée (RW 1.3, 1.4, 1.5, 1.6) | Extension existante ; détail historique non consigné. |
| Non documentée | 2898411376 | `2898411376` | RimFridge: Now with Shelves! | 2.0.7 (RW 1.6, 1.5, 1.4) | Extension existante ; détail historique non consigné. |
| 15/11/2026 | 2928608119 | `2928608119` | Searchable Menus | Non déclarée (RW 1.4, 1.5, 1.6) | Traduction FR complète des options de recherche (4 clés). |
| Non documentée | 3015391450 | `3015391450` | GrimWorld 40,000 - Hammer of the Imperium | Non déclarée (RW 1.6) | Extension existante ; détail historique non consigné. |
| Non documentée | 3018671708 | `3018671708` | GrimWorld 40,000 - Core Imperialis | Non déclarée (RW 1.6) | Extension existante ; détail historique non consigné. |
| Non documentée | 3033901359 | `3033901359` | Adaptive Storage Framework | Non déclarée (RW 1.4, 1.5, 1.6) | Extension existante ; détail historique non consigné. |
| Non documentée | 3119805903 | `3119805903` | GrimWorld 40,000 - Framework | Non déclarée (RW 1.6) | Extension existante ; détail historique non consigné. |
| Non documentée | 3119816857 | `3119816857` | Grimworld 40,000 - Angels of Death | Non déclarée (RW 1.6) | Extension existante ; détail historique non consigné. |
| Non documentée | 3203269467 | `3203269467` | Colored deep resources | Non déclarée (RW 1.5, 1.6, 1.7) | Extension existante ; détail historique non consigné. |
| Non documentée | 3209604085 | `3209604085` | Ilya Tech - Core (Continued) | 1.0.2 (RW 1.3, 1.4, 1.5, 1.6) | Extension existante ; détail historique non consigné. |
| Non documentée | 3215305656 | `3215305656` | Reinforced Walls (Continued) | 1.6.2 (RW 1.4, 1.5, 1.6, 1.7) | Extension existante ; détail historique non consigné. |
| Non documentée | 3234498246 | `3234498246` | Wall Sun Lamp | Non déclarée (RW 1.5, 1.6, 1.7) | Extension existante ; détail historique non consigné. |
| Non documentée | 3237638097 | `3237638097` | Reel's Expanded Storage | Non déclarée (RW 1.5, 1.6, 1.7) | Extension existante ; détail historique non consigné. |
| Non documentée | 3262718980 | `3262718980` | ReBuild: Doors and Corners | 1.5-Miller-rev2 (RW 1.5, 1.6, 1.7) | Extension existante ; détail historique non consigné. |
| Non documentée | 3297307747 | `3297307747` | Adaptive Simple Storage | Non déclarée (RW 1.4, 1.5, 1.6) | Extension existante ; détail historique non consigné. |
| Non documentée | 3320940377 | `3320940377` | GrimWorld 40,000 - Talons of the Emperor | Non déclarée (RW 1.5, 1.6) | Extension existante ; détail historique non consigné. |
| Non documentée | 3352097566 | `3352097566` | GrimWorld 40,000 - Aspectus Imperialis | Non déclarée (RW 1.6) | Extension existante ; détail historique non consigné. |
| Non documentée | 3373064575 | `3373064575` | LWM's Adaptive Deep Storage | Non déclarée (RW 1.4, 1.5, 1.6) | Extension existante ; détail historique non consigné. |
| Non documentée | 3382446150 | `3382446150` | Alpha Crafts | Non déclarée (RW 1.5, 1.6, 1.7) | Extension existante ; détail historique non consigné. |
| Non documentée | 3390925031 | `3390925031` | Grimworld 40,000 - Scattered Sons | Non déclarée (RW 1.6) | Extension existante ; détail historique non consigné. |
| Non documentée | 3400037215 | `3400037215` | Adaptive Primitive Storage | Non déclarée (RW 1.5, 1.6) | Extension existante ; détail historique non consigné. |
| Non documentée | 3411401573 | `3411401573` | Vanilla Quests Expanded - The Generator | Non déclarée (RW 1.5, 1.6, 1.7) | Extension existante ; détail historique non consigné. |
| Non documentée | 3416243474 | `3416243474` | [sbz] Neat Storage | Non déclarée (RW 1.5, 1.6, 1.7) | Extension existante ; détail historique non consigné. |
| Non documentée | 3438175685 | `3438175685` | Auto Dissector | Non déclarée (RW 1.5, 1.6) | Extension existante ; détail historique non consigné. |
| Non documentée | 3486264784 | `3486264784` | [sbz] Fridge | Non déclarée (RW 1.5, 1.6, 1.7) | Extension existante ; détail historique non consigné. |
| Non documentée | 3506573327 | `3506573327` | Useful Marks | Non déclarée (RW 1.5, 1.6, 1.7) | Extension existante ; détail historique non consigné. |
| Non documentée | 3512466087 | `3512466087` | QualityBuilder Unofficial 1.6 | 1.6.013 (RW 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7) | Extension existante ; détail historique non consigné. |
| Non documentée | 3513846773 | `3513846773` | Quality Colors (Continued) | Non déclarée (RW 1.2, 1.3, 1.4, 1.5, 1.6, 1.7) | Extension existante ; détail historique non consigné. |
| Non documentée | 3516409503 | `3516409503` | Auto Arm | 0.7.0 (RW 1.6, 1.7) | Extension existante ; détail historique non consigné. |
| Non documentée | 3519963835 | `3519963835` | Warehouse Storage | Non déclarée (RW 1.4, 1.5, 1.6) | Extension existante ; détail historique non consigné. |
| Non documentée | 3520130671 | `3520130671` | Nice Bill Tab | Non déclarée (RW 1.5, 1.6, 1.7) | Extension existante ; détail historique non consigné. |
| Non documentée | 3520167264 | `3520167264` | Auto-Cut Blight - 1.6 | Non déclarée (RW 1.0, 1.1, 1.2, 1.3, 1.4, 1.5, 1.6) | Extension existante ; détail historique non consigné. |
| Non documentée | 3522676478 | `3522676478` | Ushankas Glittertech Expansion | Non déclarée (RW 1.5, 1.6) | Extension existante ; détail historique non consigné. |
| Non documentée | 3523916780 | `3523916780` | [MUS]太空基地家具Space Base Furniture | Non déclarée (RW 1.5, 1.6) | Extension existante ; détail historique non consigné. |
| Non documentée | 3525001145 | `3525001145` | Blueprints Forked - 1.6 | Non déclarée | Extension existante ; détail historique non consigné. |
| Non documentée | 3526354009 | `3526354009` | Replace Stuff - Continued | Non déclarée (RW 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7) | Extension existante ; détail historique non consigné. |
| Non documentée | 3532608331 | `3532608331` | LWM's Deep Storage | Non déclarée (RW 1.0, 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7) | Extension existante ; détail historique non consigné. |
| Non documentée | 3535481557 | `3535481557` | Loading Progress | 0.21.1 (RW 1.6, 1.7) | Extension existante ; détail historique non consigné. |
| Non documentée | 3536463789 | `3536463789` | Cheaters Resource Replicator | Non déclarée (RW 1.6) | Extension existante ; détail historique non consigné. |
| 15/11/2026 | 3536598972 | `3536598972` | Alpha Animals Retextured | Non déclarée (RW 1.0, 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 1.8, 1.9) | Audit : mod de textures, aucune ressource linguistique à traduire. |
| Non documentée | 3537799933 | `3537799933` | [kyzy] Ship Wall Enhanced | Non déclarée (RW 1.6) | Extension existante ; détail historique non consigné. |
| Non documentée | 3537905298 | `3537905298` | [sbz] Gravship Storage | Non déclarée (RW 1.5, 1.6, 1.7) | Extension existante ; détail historique non consigné. |
| Non documentée | 3538194513 | `3538194513` | Cheat Reactor | Non déclarée (RW 1.6, 1.7) | Extension existante ; détail historique non consigné. |
| Non documentée | 3538880089 | `3538880089` | Warhammer themed Mechanoids | Non déclarée (RW 1.6) | Extension existante ; détail historique non consigné. |
| Non documentée | 3540066516 | `3540066516` | Universal Blueprints | Non déclarée (RW 1.6, 1.7) | Extension existante ; détail historique non consigné. |
| Non documentée | 3543503264 | `3543503264` | Wall Heater/Cooler | Non déclarée (RW 1.6) | Extension existante ; détail historique non consigné. |
| Non documentée | 3550734583 | `3550734583` | GrimWorld 40,000 - Dead Men | Non déclarée (RW 1.6) | Extension existante ; détail historique non consigné. |
| Non documentée | 3550744255 | `3550744255` | GrimWorld 40,000 - Regiments of Renown | Non déclarée (RW 1.6) | Extension existante ; détail historique non consigné. |
| Non documentée | 3553115301 | `3553115301` | Pandora:Frontier ReMake | Non déclarée (RW 1.5, 1.6) | Extension existante ; détail historique non consigné. |
| 15/11/2026 | 3563882422 | `3563882422` | Better Architect Menu | Non déclarée (RW 1.6, 1.7) | Traduction FR de Better Architect et de deux extensions intégrées. |
| Non documentée | 3566138248 | `3566138248` | Kenji's Tripleburst Autopistol(Cheat) | Non déclarée (RW 1.6, 1.5, 1.4) | Extension existante ; détail historique non consigné. |
| Non documentée | 3573344880 | `3573344880` | Ushankas Hacking Expansion | Non déclarée (RW 1.6) | Extension existante ; détail historique non consigné. |
| Non documentée | 3609897594 | `3609897594` | Nice Inventory Tab | Non déclarée (RW 1.6, 1.7) | Extension existante ; détail historique non consigné. |
| Non documentée | 3632480044 | `3632480044` | Layered Apparel | Non déclarée (RW 1.6, 1.7) | Extension existante ; détail historique non consigné. |
| Non documentée | 3632730856 | `3632730856` | Hanamogera_Infinity_Power_Generator | Non déclarée (RW 1.6) | Extension existante ; détail historique non consigné. |
| Non documentée | 3639007008 | `3639007008` | GrimWorld 40,000 - Masters of Mankind | Non déclarée (RW 1.6) | Extension existante ; détail historique non consigné. |
| 15/11/2026 | 3696948139 | `3696948139` | Better Context Menu | Non déclarée (RW 1.6, 1.7) | Audit : amélioration du menu contextuel, aucune ressource linguistique à traduire. |
| 15/11/2026 | 3742906203 | `3742906203` | Modern Quest Menu | Non déclarée (RW 1.6) | Traduction FR complète des chaînes de l'interface de quêtes. |
| Non documentée | 3745434121 | `3745434121` | Yet Another Research Tree (YART) | Non déclarée (RW 1.6, 1.7) | Extension existante ; détail historique non consigné. |
| Non documentée | 3825828888 | `3825828888` | [B98] Auto Turrets – No Crew, No Shells, No Friendly Fire | 1.3.0 (RW 1.6, 1.7) | Extension existante ; détail historique non consigné. |
| Date | ID | Dossier | Mod | Version déclarée / versions de RimWorld | Travail effectué |
|---|---:|---|---|---|---|
| 17/11/2026 | 1447140290 | `1447140290` | OgreStack | Non déclarée (RW 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7) | Traduction FR des réglages : 57 chaînes traduites ; 12 valeurs identiques conservées après examen. |
| 17/11/2026 | 1888778429 | `1888778429` | Vault-Tec - Handy things for your vault! | Non déclarée (RW 1.0, 1.1, 1.2, 1.3, 1.4, 1.5, 1.6) | Traduction FR des 4 clés de réglages ; les textes littéraux des Defs restent hors de ce lot. |
| 17/11/2026 | 3538894636 | `3538894636` | Legendary Boss | Non déclarée (RW 1.6, 1.7) | Traduction FR des 11 clés du fichier d'interface Keyed. |
| 17/11/2026 | 3688553480 | `3688553480` | Research Icons | 1.2.0 (RW 1.6, 1.7) | Traduction FR du libellé de chargement dans les dossiers v1.6 et v1.7. |
| 17/11/2026 | 3730168769 | `3730168769` | Leo - Events | Non déclarée (RW 1.6, 1.7) | Traduction FR de 8 clés sur 9 ; le modèle de coordonnées reste inchangé. |
| 17/11/2026 | 3760683002 | `3760683002` | FIP - Corvega | Non déclarée (RW 1.6, 1.7) | Traduction FR des ressources chargées : 27 entrées ; 12 noms de modèles conservés. XML validé et export SHA256 vérifié. |
| 17/11/2026 | 1541721856 | `1541721856` | Alpha Animals | Non déclarée (RW 1.7, 1.6) | Misc_Gameplay : 218 clés traduites ; jetons vérifiés, dont {0_possessive} dans AA_LetterMime. |
| 17/11/2026 | 3760676309 | `3760676309` | FIP - Big MT | Non déclarée (RW 1.6) | Traduction FR des Defs Anomaly, Anomaly_WestTek et BigMT Keyed : 10 fichiers ; XML et jetons validés. |
| 17/11/2026 | 3760677126 | `3760677126` | FIP - Future-Tec | Non déclarée (RW 1.6, 1.7) | 17 fichiers (Ancients, Cryptoforge, Deadlife, Generator) ; jeton [PAWN_possessive] corrigé ; XML et jetons validés. |
| 17/11/2026 | 3592202704 | `3592202704` | FIP - Arktos | Non déclarée (RW 1.6, 1.7) | 17 fichiers ; XML et jetons validés. |
| 17/11/2026 | 3592202124 | `3592202124` | FIP - Donaustahl | Non déclarée (RW 1.6, 1.7) | 27 fichiers ; XML validé. 8 écarts de jetons pronoms au niveau de la phrase conservés (Backstories x6, LegendaryBlacksmith, Traits). |
| 17/11/2026 | 3592202407 | `3592202407` | FIP - WestTek | Non déclarée (RW 1.6, 1.7) | 18 fichiers ; XML et jetons validés. |
| 17/11/2026 | 3700612546 | `3700612546` | FIP - Poseidon | Non déclarée (RW 1.6, 1.7) | 28 fichiers ; XML et jetons validés. |
| 17/11/2026 | 3698413567 | `3698413567` | FIP - Greenway | Non déclarée (RW 1.6, 1.7) | 47 fichiers ; XML et jetons validés. Mojibake source « SamsÄra »/« MahÄ » corrigé en « Saṃsāra »/« Mahā ». |
| 17/11/2026 | 3700613098 | `3700613098` | FIP - Lucky 38 | Non déclarée (RW 1.6, 1.7) | 46 fichiers ; XML et jetons validés. Coquille « Cette colone » corrigée en « Ce colon ». |
| 17/11/2026 | 3688034883 | `3688034883` | FIP - Repconn | Non déclarée (RW 1.6, 1.7) | 80 fichiers ; XML et jetons validés. Coquille « de améliorer » corrigée en « d'améliorer ». |
| 17/11/2026 | 3687522435 | `3687522435` | FIP - Whitespring | Non déclarée (RW 1.6, 1.7) | 81 fichiers ; XML validé, clés et jetons vérifiés. 23 écarts de jetons pronoms au niveau de la phrase conservés (Backstories, VFEE_FirstOfName, Intro_Wimp, décrets Keyed corrigés). |
| 17/11/2026 | 3807049445 | `3807049445` | Arsenal - Core | 1.6.0 (RW 1.6, 1.7) | Traduction FR complète sans dossier English : 1520 entrées extraites des Defs (labels, descriptions, étapes d'hediff, recettes, catégories), 92 fichiers dont la partie CE. Jetons contrôlés, XML valide. 296 valeurs identiques conservées (calibres et désignations). Descriptions des bases abstraites injectées sur chaque Def enfant. |
RW = versions de RimWorld déclarées dans About.xml. « Non déclarée » signifie que le mod ne précise pas sa version propre ; les dates historiques non disponibles restent indiquées comme telles.
