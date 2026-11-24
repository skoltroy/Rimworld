---
name: rimworld-log-debugging
description: Diagnose RimWorld mod and French-language errors from user-provided logs, translation reports, definitions, and exact mod versions without attributing symptoms beyond the evidence.
---

# RimWorld log diagnosis

Use this skill when the user reports a RimWorld error, a translation warning, a broken mod, or provides a game log.

- Workspace mapping: **Rimworld/Mods** is `D:\Games\RimWorld\Mods`; **Skoltroy/Extensions** is the repository root (`.`), with backup archives in `Extensions\`.

## Evidence first

- Ask for the complete relevant error and surrounding log lines if the supplied snippet is only a stack trace or summary.
- For translation warnings, request the generated translation report when available. A line such as “Translation data for language French has N errors” establishes that the loaded French data has errors; it does not identify which mod or entries caused them.
- Establish the RimWorld version, mod version, active mod list/load order, language, and whether the problem occurs on a new or existing save when those facts affect the diagnosis.
- Distinguish XML parse errors, missing keys, duplicate keys, invalid DefInjected identifiers, unresolved Def references, and runtime exceptions.

## Trace the failure

- For every reported “did not load any content” or missing translation, check all French resources against the selected version's `LoadFolders.xml` paths, including root `Languages\French` files (which require `/` or an equivalent root path). Also inspect version-specific and conditional paths before declaring the audit complete.
- Anchor the analysis in the first relevant error, affected package/Def/key, and complete exception chain. Do not treat a later generic Harmony or loading-progress frame as the cause without supporting evidence.
- Search the installed mod and its English/French resources for the exact key or Def name. Inspect `LoadFolders.xml` and version-specific folders to confirm that the resource is active for the user’s RimWorld version.
- Compare source and translation keys and preserve placeholders, markup, and tokens. A valid XML file with matching keys does not by itself prove a translation warning is unrelated; use the translation report to identify the actual entries.
- Separate confirmed findings from hypotheses. If a snippet does not identify the mod, say so and request the missing report/log instead of guessing.

## TradFix and ModFix naming

- Translation packages use the `French TradFix` convention: Workshop ID folders are `<ID> - French TradFix`; their About `<name>` is the source mod's exact display name plus ` - French TradFix`. The non-ID BTD translation package is `BTD Gravship Descriptions - French TradFix`.
- Keep live package folder names and backup ZIP basenames aligned. Live packages are under `D:\Games\RimWorld\Mods` in **Rimworld/Mods**; **Skoltroy/Extensions** contains only `<package folder name>.zip` backups under `Extensions\`, whose entries are package-root relative paths. Extract backups only to a temporary location outside `Extensions`; do not leave unpacked package folders there.
- Do not call a gameplay/config/compatibility patch a TradFix unless it actually contains translation resources. Keep such corrections in a distinct ModFix package (for example `ModFix - Refugee Weapon Budget Fix`).
- When fixing metadata or load paths, preserve the existing `packageId` unless the log proves it is wrong; sync the live folder and its backup ZIP, then verify the archive contents against the live package.

## Patterns confirmed in past sessions

- **Non-mod folders in `Mods`**: a folder with only `.rsc` files is a scenario, not a mod. A "Mod is missing supported versions list in About.xml" warning can come from such a folder, from a folder without `About.xml`, or from a real mod. Check which before blaming a mod; all `About.xml` files may already list the version.
- **Stray debug DLLs**: search the DLL strings for hard-coded user paths (for example `C:\Users\<name>\...`). A debug tool left in a published mod can throw on the user's machine; report it before removing anything.
- **Def name collisions**: a mod that uses `Name="X"` identical to a vanilla or other-mod def, combined with a patch on `ThingDef[@Name="X"]`, changes several defs at once and can cause missing textures. Check every `Name` attribute the patch matches.
- **Wrong Def type in a reference**: a `soundCast` pointing to a ThingDef (a gun) fails; confirm a SoundDef exists in `Data\<DLC>\Defs` before writing a reference.
- **Vanilla-owned errors**: an error on a vanilla def (for example a pawn kind's `weaponMoney` against its weapon tags) is fixed by a small named local patch mod in both trees, not by editing vanilla.
- **Two copies of the same mod**: the game copy can hold an older partial translation while the backup holds the complete one. Compare key counts in both trees before syncing and sync in the direction that keeps the most valid content.
- **Removing a mod**: when asked, back up the folder and its `ModsConfig.xml` line first, then remove the line and the folder, and report that the deletion is permanent.

## Safe remediation

- Make a minimal change only when the relevant source and failing translation/definition have been identified.
- Validate XML, key parity, placeholder parity, and DefInjected target names after any localization edit.
- Do not modify saves, delete logs, alter load order, or change unrelated mods as a diagnostic shortcut.
- Never run `git commit` or `git push`. Report touched paths and leave the commit to the user.
- Protect privacy: analyze logs locally, avoid external upload, and warn the user that logs may contain local usernames or mod details before they share them.
