---
name: rimworld-mod-localization
description: Translate, complete, audit, and export RimWorld mod localization files while preserving game keys, format tokens, version-specific load paths, and existing French work. Covers mods with no Languages\English tree (Defs-based DefInjected extraction), patch-only mods, batch writing, validation and export to the backup repo.
---

# RimWorld mod localization

Use this skill when the user requests French translations or corrections for RimWorld mods.

Helper scripts live in `scripts\` next to this file:

- `scripts\Extract-DefTranslatables.ps1` : lists every translatable Def string of a mod that has no English tree (`-Root <mod>`, `-OutFile <tsv>`).
- `scripts\Build-DefInjected.ps1` : writes `Languages\<Lang>\DefInjected\<DefType>\<FileName>` from the extraction and aligned translation batches. It refuses any key mismatch and touches only the files it writes.
- `scripts\Validate-Fr.ps1` : for mods with an English tree, checks XML, key parity, duplicate keys, token parity and counts identical EN/FR candidates (`-ModPath <mod>`).
- `tools\Update-RimWorldModTranslations.ps1` : discovery, plan, and export (see Project workflow).

## Select mods and scope

- If the user names numeric Workshop IDs, use those IDs and verify each folder exists under `D:\Games\RimWorld\Mods`.
- If the user says a mod was **added** ("j'ai ajouté N mods") and gives no ID, find the new folders by `LastWriteTime` in the game folder, filter out IDs already in `Extensions\` or `Extensions\ReadMe.md`, and show the candidates with their names (`About\About.xml`). Confirm the list when more than N candidates match. The discovery report lists dozens of untracked IDs, so do not treat it as the answer on its own.
- Discovery is a comparison between the game Mods folder and IDs already tracked in the project README, `Extensions\ReadMe.md`, and numeric extension folders.
- Ask whether the requested work is **complete** or a **supplement** when the user has not already specified it:
  - Complete: audit coverage and review every loaded source user-facing entry. Add or correct translations where needed, while preserving valid French; it does not mean blindly replacing every value.
  - Supplement: preserve correct French and add missing entries; review exact English/French duplicates as candidates, not as proof of errors.
- In either mode, distinguish missing keys from identical-English candidates and from valid French. Do not claim a complete translation from key counts alone; unresolved or unverified strings must be reported.
- Never infer that an untranslated value is a defect solely because English and French are identical. Proper names, technical terms, and shared labels may be correct.

## Choose the source before writing

1. **English tree present** (`Languages\English`, including nested bundles and version folders listed in `LoadFolders.xml`): compare by relative path with the French tree, then run `Validate-Fr.ps1`.
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
- Export only `Languages\French` resources with `tools\Update-RimWorldModTranslations.ps1 -Action Export -ModId <one ID>`. Run it once per ID: the script rejects comma-separated lists. The copy keeps the path relative to the mod root, so files land in `Extensions\<ID>\Languages\French\...`, and each copy is verified by SHA-256. Verify the export yourself by comparing hashes with the same relative path.
- Never copy the whole mod or delete unrelated files.
- Update `Extensions\ReadMe.md`: add rows at the top of the first table (newest first) with the date, declared mod version and RimWorld versions from `About.xml` (do not invent versions), and a concise summary with validation results and limitations. Keep the existing file name; Git tracks it as `Extensions/README.md`.

## Project workflow

- Discovery and export use `tools\Update-RimWorldModTranslations.ps1`. Always pass `-ProjectPath 'D:\Downloads\RimWorld\Mods'` explicitly; without it the default `Split-Path $PSScriptRoot` is empty when the script is launched from a shell in this environment and the script stops.
- The script prepares plans and exports; it does not translate.
- Keep the backup and the game copy identical for each fix: `D:\Games\RimWorld\Mods\<ID>` is the live copy and `D:\Downloads\RimWorld\Mods\Extensions\<ID>` the backup.
- Never run `git commit` or `git push`. The user manages commits and pushes; leave the changes in the working tree and say which paths were touched.
