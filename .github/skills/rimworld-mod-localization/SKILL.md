---
name: rimworld-mod-localization
description: Translate, complete, audit, and export RimWorld mod localization files while preserving game keys, format tokens, version-specific load paths, and existing French work. Covers mods with no Languages\English tree (Defs-based DefInjected extraction), patch-only mods, batch writing, validation and export to the backup repo.
---

# RimWorld mod localization

Use this skill when the user requests French translations or corrections for RimWorld mods.

- Workspace mapping: **Rimworld/Mods** is `D:\Games\RimWorld\Mods`; **Skoltroy/Extensions** is the repository root (`.`), with backup archives in `Extensions\`.

## TradFix naming and packaging

- A translation-only package is a **French TradFix**, not a ModFix. For a Workshop mod with numeric ID `<ID>`, name its folder `<ID> - French TradFix` and set `About\About.xml`'s `<name>` to the source mod's exact display name from its `About.xml`, followed by ` - French TradFix` (for example, `EdB Prepare Carefully - French TradFix`). Preserve its package ID and dependencies unless a separate change is explicitly required.
- Put all French translations created or maintained by this workflow only in the separate TradFix package. Treat the upstream/source mod as read-only: never add, edit, or export workflow translations into its `Languages\French` tree. Existing French shipped by the upstream author is not ours to remove; inspect it and avoid unnecessary duplicate keys. If prior workflow output was mistakenly added to a source mod, first verify every affected file is byte-identical to a copy in its TradFix, preserve a backup outside the source folder, then remove only those verified duplicates when the user has authorized the cleanup.
- Every user-owned mod or translation package must set `<url>https://rimworld.skoltroy.com</url>` in `About\About.xml`. If replacing a different upstream/source URL, preserve that link as a short reference in `<description>` so the original project remains credited; never alter upstream Workshop source mods.
- Every TradFix package preview must be derived from the source mod's `About\Preview.png` (or its existing preview path if it uses a different filename), without replacing or modifying the source mod preview.
- Place a French tricolor flag in the top-right corner of `About\Preview.png` for the TradFix package, separate from the credit panel.
- Put the credit text in the top-left corner of `About\Preview.png`, inside a clearly visible bordered text panel:
  - complete translation: `French Trad by Skoltroy`
  - incomplete/supplement translation: `French TradFix by Skoltroy`
- When a package is a recompilation or an independently packaged French version without a RimWorld language file, use `French Version by Skoltroy` instead of a TradFix credit.
- For this non-language package format, keep `<modDependencies>` empty and preserve only a reference to the original mod in the metadata, such as `<url>` or a short `<description>`, rather than adding a dependency on the original mod.
- Preserve enough contrast and margins for the flag and text to remain readable, and keep the original mod preview artwork as the background.
- If the source mod has no preview image, generate a rectangular 16:9 PNG with a plain gray background. Show the short component `<name>` centered along the bottom, removing suffixes such as ` - French TradFix`, ` - French Translation`, and other French/version labels. Put the French flag at the top-right and the bordered credit panel immediately to its left at the top-right; do not use a missing icon or invent background artwork.
- Keep the live package unpacked at `D:\Games\RimWorld\Mods\<ID> - French TradFix`. For new or updated backups, write `<ID> - French TradFix.zip` to **Skoltroy/Extensions** under `Extensions\`, containing the package contents directly (no wrapper directory). Non-ID backup packages use the same rule: `<package folder name>.zip`.
- For non-numeric standalone translations, use a descriptive folder and matching About name ending in ` - French TradFix` (the BTD package is `BTD Gravship Descriptions - French TradFix`).
- A package that contains only a gameplay, compatibility, or loading correction is not a TradFix. Keep it as a separately named ModFix; do not mislabel it as a translation.
- When renaming an existing TradFix, rename the game folder and matching backup ZIP, update the About `<name>`, and update documentation references. Never change `packageId` merely to match the folder or display name.
- Do not create new unpacked backup folders in `Extensions`. Preserve any existing legacy extracted backup folders; do not delete or migrate them without explicit user authorization. To inspect or update a ZIP, extract it to a temporary working directory outside `Extensions`; after edits and validation, recreate the ZIP with package-root paths and verify the archive before replacing the backup. Keep the ZIP contents synchronized with the live game package.
- Before claiming a translation loads, resolve every French resource path against the active `LoadFolders.xml` entry for the user's RimWorld version. A root-level `Languages\French` tree requires `/` (or an equivalent root path) in the selected version's folder list. Check conditional/version-specific paths separately; do not infer loading from the file's presence.

Helper scripts live in `scripts\` next to this file:

- `scripts\Extract-DefTranslatables.ps1` : lists every translatable Def string of a mod that has no English tree (`-Root <mod>`, `-OutFile <tsv>`).
- `scripts\Build-DefInjected.ps1` : writes `Languages\<Lang>\DefInjected\<DefType>\<FileName>` from the extraction and aligned translation batches. It refuses any key mismatch and touches only the files it writes.
- `scripts\Validate-Fr.ps1` : for mods with an English tree, checks XML, key parity, duplicate keys, token parity and counts identical EN/FR candidates (`-ModPath <mod>`).
- `.tools\Update-RimWorldModTranslations.ps1` : discovery, plan, and export (see Project workflow).

## Select mods and scope

- If the user names numeric Workshop IDs, use those IDs and verify each folder exists under `D:\Games\RimWorld\Mods`.
- If the user says a mod was **added** ("j'ai ajouté N mods") and gives no ID, find the new folders by `LastWriteTime` in the game folder, filter out IDs already in `Extensions\` or `Extensions\ReadMe.md`, and show the candidates with their names (`About\About.xml`). Confirm the list when more than N candidates match. The discovery report lists dozens of untracked IDs, so do not treat it as the answer on its own.
- Discovery is a comparison between the game Mods folder and IDs already tracked in the project README, `Extensions\ReadMe.md`, and backup ZIPs named `<ID> - French TradFix.zip`. Do not recreate unpacked backup folders.
- Ask whether the requested work is **complete** or a **supplement** when the user has not already specified it:
  - Complete: audit coverage and review every loaded source user-facing entry. Add or correct translations where needed, while preserving valid French; it does not mean blindly replacing every value.
  - Supplement: preserve correct French and add missing entries; review exact English/French duplicates as candidates, not as proof of errors.
- In either mode, distinguish missing keys from identical-English candidates and from valid French. Do not claim a complete translation from key counts alone; unresolved or unverified strings must be reported.
- Never infer that an untranslated value is a defect solely because English and French are identical. Proper names, technical terms, and shared labels may be correct.

## Choose the source before writing

- Prefer native, loadable `Languages\French` resources in the separate TradFix whenever technically possible. If the mod has no English tree, that alone is not a reason to skip localization: inspect its Defs, patches, existing keyed strings and available source code, then add suitable French resources to the TradFix.
- If user-facing strings are hard-coded in a compiled DLL and cannot reasonably be supplied through French language resources, first inspect available source code; otherwise decompile the relevant assembly, translate only user-facing strings while preserving identifiers and format tokens, then recompile against the matching RimWorld/framework references. Keep the original mod assembly untouched and deliver the rebuilt assembly only in the separate French package. If decompilation or a compatible build is not feasible, report the limitation and do not claim those strings are translated.
- Keep source mods read-only. Author resources in an isolated staging copy outside `D:\Games\RimWorld\Mods\<ID>`, then put only the validated French resources in the TradFix. Preserve the source mod's version-specific and conditional relative paths. For DefInjected work, point `Build-DefInjected.ps1 -ModRoot` at the staging copy, never the source mod.
1. **English tree present** (`Languages\English`, including nested bundles and version folders listed in `LoadFolders.xml`): compare source English files with the corresponding staged/TradFix French paths, then run `Validate-Fr.ps1` against an isolated validation copy or the TradFix.
2. **No English tree**: run `Extract-DefTranslatables.ps1` on the mod root. Its output is the source of truth for coverage. Keys are `defName.field`, with nested paths such as `defName.stages.0.label`, `defName.rulesStrings.N` and `defName.rulePack.rulesStrings.0`. Abstract defs are skipped, and fields inherited through `ParentName` inside the mod are added under the child's own defName.
3. **Patch-only mod** (only `Patches\*.xml`, whose targets live in another mod): translate the text values of the patch operations as DefInjected keys for the defs they patch, and say in the report that the base mod was absent. Keys whose list index depends on the base mod (for example `questNameRules.rulesStrings.N` when the patch uses `li[contains(text(),...)]`) cannot be keyed and must be reported as not translated.
4. Always also check which tree is more complete before syncing: in one case the game copy had 6 of 22 keys while the backup had 24. Sync in the direction that keeps the most valid content, and say which one was used.

## Read the source

- Inspect `About\About.xml`, `LoadFolders.xml`, English and French trees, and the Defs that carry text. Respect `IfModActive` conditions: a folder loaded only with another mod is still source material, but say so.
- Text can also sit in `Patches\*.xml` values and in `Compatibility\<Mod>\Defs`. Include them, or report them as excluded.
- Treat XML parse errors, duplicate keys, missing keys, and mismatched format tokens as distinct findings.

## Translate

- Translate user-facing prose naturally into French; keep established terminology from the mod and from RimWorld's French (check existing French in the same mod or in `Extensions\` for the same term first).
- Keep proper names and game terms when French usage keeps them (`Wraith`, `Asuran`, `Goa'uld`, `Odyssey`, `Chemfuel`).
- Preserve placeholders exactly: `{0}`, `{0_possessive}`, `{USER_pronoun}`, `[PAWN_possessive]`, `[asker_nameDef]`, `\n` escapes, `-&gt;` or `->` prefixes in rulesStrings (translate the value after the prefix only), XML entities, hotkeys, delimiters, and code-like identifiers.
- Do not translate defNames, package IDs, class names, file paths, texture paths, or identifiers such as `D108`.
- A `\n` in an English string stays a literal `\n` in the French one; RimWorld turns it into a newline.
- Do not hide source errors: a mojibake such as `SamsÄra` or a typo in the source must be corrected in the French output and disclosed in the report.

### Batching large extractions (hundreds of keys)

- Write translations in batches of about 150 to 180 lines, one file per batch, as `Key<TAB>French` in the same order as the extraction. Keep the batch folder separate from the source TSV, because `Build-DefInjected.ps1` reads every `*.tsv` in its translation folder.
- After each batch, count lines and check the key sequence against the source (the same check as in the build script). A batch written with a typo or a runaway repeated line is caught here, not later. Fix with a script, then re-check.
- Build once every batch is aligned: `Build-DefInjected.ps1 -SourceTsv <extraction> -TranslationDir <batch folder> -ModRoot <mod> -FileName <name>.xml`. It writes one file per DefType and re-reads each XML.

## Verify and export

- Parse every modified XML file.
- With an English tree, run `Validate-Fr.ps1 -ModPath <mod>`. Without one, rely on the build check (key sequence, empty values, XML parse) and report that token and key coverage was checked against the extraction only.
- Check placeholder parity on the extraction: every `{...}`, `[...]` and `\n` in the English value must appear in the French one, or the difference must be explained in the report.
- Expected and accepted differences (for example a sentence that drops a pronoun token) are reported, not forced.
- For a DLL-based French version, require a successful build against the intended game version's references, verify the rebuilt assembly is packaged, and label its preview `French Version by Skoltroy`; do not replace the original mod's DLL.
- After the staged French resources are validated, export only `Languages\French` resources with `.tools\Update-RimWorldModTranslations.ps1 -Action Export -ModId <one ID> -SourceModsPath <staging-root> -ProjectPath '.'`. The staging root must contain `<ID>\Languages\French`; never point this export at the live upstream Mods directory. Run it once per ID: the script rejects comma-separated lists. It stages files under `.tools\exports\<ID>\...` (outside `Extensions`) and verifies each copy by SHA-256.
- Build/update the live `<ID> - French TradFix` package from its package metadata (`About`, applicable `LoadFolders`) and the validated export. Create its backup ZIP from an isolated staging copy outside `Extensions`, with package-root paths and no wrapper directory. Reopen the ZIP, validate XML and SHA-256 against the live package, then replace the prior archive. Do not create a new extracted backup folder in `Extensions`; preserve existing legacy folders. Keep TSV batches, extraction reports, and helper scripts out of the live package and ZIP.
- Never copy the whole mod or delete unrelated files.
- Update `Extensions\ReadMe.md`: add rows at the top of the first table (newest first) with the date, declared mod version and RimWorld versions from `About.xml` (do not invent versions), and a concise summary with validation results and limitations. Keep the existing file name; Git tracks it as `Extensions/README.md`.

## Project workflow

- Discovery and staged-resource export use `.tools\Update-RimWorldModTranslations.ps1`. For a shell outside the repository, pass `-ProjectPath '.'` from the repository root (or an absolute project path) explicitly. `-ProjectPath` selects the backup workspace; it is the repository root, whose `Extensions` subfolder contains ZIP backups.
- Discovery may read the live source Mods directory; for export, set `-SourceModsPath` to the isolated staging root, never the live source Mods directory. The script prepares plans and exports; it does not translate.
- Keep each TradFix backup ZIP synchronized with its live package: `D:\Games\RimWorld\Mods\<ID> - French TradFix` is the unpacked live package and `Skoltroy/Extensions\Extensions\<ID> - French TradFix.zip` is the backup archive. Source Workshop mods remain read-only in `D:\Games\RimWorld\Mods\<ID>` and must not receive workflow-authored French resources.
- Never run `git commit` or `git push`. The user manages commits and pushes; leave the changes in the working tree and say which paths were touched.
