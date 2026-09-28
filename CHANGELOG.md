# Changelog

All notable changes to `ksfraser/ksf-modules-dao` are recorded here.

## v0.5.3

Release identical in code to v0.5.2 (`d2bcfaf`). This version exists only to
correct a packaging problem, and should be used in place of v0.5.2.

### Why this version exists

Packagist indexed **v0.5.2** against commit `80aa280`, which is a stale branch
(`fix/placeholder-prefix-collision`) that predates the PSR-4 namespace
reorganisation. Its `composer.json` maps `Ksfraser\ModulesDAO\` to `src/`,
whereas `main` maps it to `src/Ksfraser/`. Both trees are internally coherent,
so Composer installs succeeded, but anyone resolving `^0.5.2` received the
divergent older code line rather than what `main` serves — and that line does
not include the placeholder pre-split fix from `630ebb4`.

Composer and Packagist treat a published version's commit reference as
immutable, so re-pointing the `v0.5.2` tag does not correct the indexed
metadata. Publishing a new version is the only remedy. The `v0.5.2` tag is
deliberately left in place and is not being rewritten.

**Consumers should move to `^0.5.3`.**

### Content (unchanged from v0.5.2, commit d2bcfaf)

- PHP 7.3 compatibility floor: no typed properties, arrow functions, or other
  7.4+ syntax.
- `FrontAccountingDbAdapter` escapes through FA's connection-aware `db_escape()`
  and fails closed if unavailable, replacing the charset-unaware
  `addslashes()` path. The legacy `match()` helper is gone.
- `QueryBuilder` pre-splits `?` placeholders so a literal `?` inside a bound
  value cannot corrupt the generated SQL (`630ebb4`).
- QueryBuilder with accompanying test coverage.
