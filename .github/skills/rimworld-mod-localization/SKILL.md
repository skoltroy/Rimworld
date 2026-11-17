---
name: rimworld-mod-localization
description: Translate, complete, audit, and export RimWorld mod localization files while preserving game keys, format tokens, version-specific load paths, and existing French work.
---

# RimWorld mod localization

Use this skill when the user requests French translations or corrections for RimWorld mods.

## Select mods and scope

- If the user names numeric Workshop IDs, use those IDs and verify each folder exists under `D:\Games\RimWorld\Mods`.
- If no IDs are provided, ask whether to enter IDs or discover untracked numeric mod folders. Discovery is a comparison between the game Mods folder and IDs already tracked in the project README, `Extensions\ReadMe.md`, and numeric extension folders.
- Ask whether the requested work is **complete** or a **supplement** when the user has not already specified it:
  - Complete: audit coverage and review every loaded source user-facing entry. Add or correct translations where needed, while preserving valid French; it does not mean blindly replacing every value.
  - Supplement: preserve correct French and add missing entries; review exact English/French duplicates as candidates, not as proof of errors.
- In either mode, distinguish missing keys from identical-English candidates and from valid French. Do not claim a complete translation from key counts alone; unresolved or unverified strings must be reported.
- Never infer that an untranslated value is a defect solely because English and French are identical. Proper names, technical terms, and shared labels may be correct.

## Source review

- Inspect `About\About.xml`, `LoadFolders.xml`, and all relevant `Languages\English` and `Languages\French` trees. Respect nested mod bundles and version folders that are actually loaded.
- Compare the source and French resource by relative path. For XML, preserve the root schema, key names, DefInjected identifiers, and ordering when practical.
- If an English language source is absent, inspect the loaded Defs for user-facing labels and descriptions before making any change. Report uncertainty; do not invent text or assume that every Def should be translated.
- When source text is embedded in Defs or other non-language resources, record the loaded source path and compare it with French overrides in both the mod and `Extensions\<mod-id>`. No English language tree means the workflow cannot certify French coverage by itself.
- Treat XML parse errors, duplicate keys, missing keys, and mismatched format tokens as distinct findings.

## Translation and corrections

- Translate user-facing prose naturally into French; retain the mod’s established terminology where evidence exists.
- Preserve format placeholders such as `{0}`, named placeholders, RimWorld markup, XML entities, escaped `\n`, hotkeys, delimiters, and code-like identifiers exactly.
- Do not translate defNames, package IDs, class names, file paths, texture paths, or other identifiers.
- Integrate with existing French translations rather than replacing them wholesale. Make only changes needed for the requested scope.
- Surface invalid XML or ambiguous text explicitly; do not silently omit problematic files or produce a success-shaped fallback.

## Verify and export

- Parse every modified XML file.
- For each English/French XML pair, verify key coverage and format-placeholder parity. Check nested DefInjected definitions separately.
- For text resources, preserve structure and tokens and compare the completed French resource to its English source.
- Export only `Languages\French` resources to `D:\Downloads\RimWorld\Mods\Extensions\<mod-id>`, preserving all relative paths (including nested bundles). Never copy the whole mod or remove unrelated files.
- Verify copied files with hashes. Update the project README’s mod-ID registry when applicable and update `Extensions\ReadMe.md` with the date, declared mod version/RimWorld versions, and a concise complete/supplement/audit summary. Do not invent unavailable version or date history.

## Project workflow

Use `tools\Update-RimWorldModTranslations.ps1` to discover or list candidate translation gaps and to export completed French files. The script prepares a report; it does not machine-translate. See the project README for commands and the report format.
