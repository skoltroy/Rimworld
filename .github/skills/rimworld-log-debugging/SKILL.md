---
name: rimworld-log-debugging
description: Diagnose RimWorld mod and French-language errors from user-provided logs, translation reports, definitions, and exact mod versions without attributing symptoms beyond the evidence.
---

# RimWorld log diagnosis

Use this skill when the user reports a RimWorld error, a translation warning, a broken mod, or provides a game log.

## Evidence first

- Ask for the complete relevant error and surrounding log lines if the supplied snippet is only a stack trace or summary.
- For translation warnings, request the generated translation report when available. A line such as “Translation data for language French has N errors” establishes that the loaded French data has errors; it does not identify which mod or entries caused them.
- Establish the RimWorld version, mod version, active mod list/load order, language, and whether the problem occurs on a new or existing save when those facts affect the diagnosis.
- Distinguish XML parse errors, missing keys, duplicate keys, invalid DefInjected identifiers, unresolved Def references, and runtime exceptions.

## Trace the failure

- Anchor the analysis in the first relevant error, affected package/Def/key, and complete exception chain. Do not treat a later generic Harmony or loading-progress frame as the cause without supporting evidence.
- Search the installed mod and its English/French resources for the exact key or Def name. Inspect `LoadFolders.xml` and version-specific folders to confirm that the resource is active for the user’s RimWorld version.
- Compare source and translation keys and preserve placeholders, markup, and tokens. A valid XML file with matching keys does not by itself prove a translation warning is unrelated; use the translation report to identify the actual entries.
- Separate confirmed findings from hypotheses. If a snippet does not identify the mod, say so and request the missing report/log instead of guessing.

## Safe remediation

- Make a minimal change only when the relevant source and failing translation/definition have been identified.
- Validate XML, key parity, placeholder parity, and DefInjected target names after any localization edit.
- Do not modify saves, delete logs, alter load order, or change unrelated mods as a diagnostic shortcut.
- Protect privacy: analyze logs locally, avoid external upload, and warn the user that logs may contain local usernames or mod details before they share them.
