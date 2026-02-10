# ksf_ModulesDAO

Cross-platform DAO abstraction for KS Fraser modules.

## Related docs (keep in sync)

- Overall split plan and repo map: [../../LIBRARY_SPLIT_ANALYSIS.md](../../LIBRARY_SPLIT_ANALYSIS.md)
- FA-specific migration notes (schemas + shims): [../fa_classes/MIGRATION_NOTES.md](../fa_classes/MIGRATION_NOTES.md)

## Goal

Provide small, composable interfaces + adapters so modules can read/write data via:

- Generic DB tables (PDO)
- WordPress (options/settings APIs)
- SuiteCRM (Administration settings)
- FrontAccounting (sys prefs or DB tables)
- File-backed key/value stores (INI/JSON/XML/CSV/YAML)
- CSV (tabular record store)
- XML (record/document store)

## Legacy migration helpers

To help migrate older FrontAccounting-oriented code that builds SQL from arrays (e.g. `select_array`, `where_array`, `fields_array`), ModulesDAO includes:

- `Ksfraser\ModulesDAO\Sql\LegacyArraySqlBuilder`
- `Ksfraser\ModulesDAO\Sql\BuiltQuery`

These helpers keep schema descriptions in the FA-specific package (e.g. `fa_classes`) while centralizing SQL construction in one place to eliminate duplication.

### Note on `Origin` and validation

Historically, many classes inherited from a shared `origin` base to get permissive setters/getters plus runtime validation helpers.

For ModulesDAO, the goal is narrower: adapters + query building. DTO validation should be handled via small validators/helpers (composition) rather than forcing DTOs/Repositories to inherit from `origin`.

Also, while a `ksfraser/origin` package exists, it currently uses newer PHP language features and should not be assumed compatible with PHP 7.3 without an explicit compatibility pass.

This is scaffolding intended to be expanded in the dedicated repo:
https://github.com/ksfraser/ksf_ModulesDAO
