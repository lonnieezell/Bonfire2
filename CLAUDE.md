# CLAUDE.md

Bonfire 2 is a drop-in admin panel **package** for CodeIgniter 4 (`Bonfire\` → `src/`), not an app. Tests boot a stand-in app from `tests/_support/` (Paths config, migrations, seeds).

## Modules

Each folder in `src/` is a module. Its `Module.php` extends `Bonfire\Core\BaseModule` and hooks into the admin from `initAdmin()`: sidebar menu items, dashboard widgets. `src/Users/Module.php` is the reference.

Give stats widgets lazy values with `StatsItem::addValue()` / `addValueByFreeQuery()`, so the query runs only when the dashboard shows the widget.

## Themes

Themes live in `themes/` (Admin, App, Auth), outside `src/`. The admin UI is View Components (`<x-…>` tags defined in `themes/Admin/Components/`) on Bootstrap 5, Alpine.js and htmx. Installs publish their own copy of the theme, so changing a component's definition is a breaking change.

## Tests

- Database tests extend `Tests\Support\DatabaseTestCase`; the test DB is SQLite in-memory.
- Tachycardia reports any test slower than 0.5s.
- Pass `--no-coverage` to phpunit for a tight loop; the config turns coverage on.

## Before committing

CI runs `composer test`, `composer style`, `composer rector`, `composer inspect` (deptrac layer rules) and phpcpd. `composer clean` applies the lint, style and rector fixes.

## Docs

- User docs are MkDocs in `docs/`. `mkdocs.yml` has an explicit `nav`, so add every new page to it.
- Log user-facing changes in `docs/intro/changelog.md` under `## D Month YYYY`. Mark breaking ones `## D Month YYYY (breaking change)`: `spark notify:breaking-changes` parses that exact heading to warn installs after `composer update`.

## Agent skills

### Issue tracker

Issues are tracked in GitHub Issues on lonnieezell/Bonfire2 via the `gh` CLI. See `docs/agents/issue-tracker.md`.

### Triage labels

Uses the default label vocabulary (needs-triage, needs-info, ready-for-agent, ready-for-human, wontfix). See `docs/agents/triage-labels.md`.

### Domain docs

Single-context: one `CONTEXT.md` and `docs/adr/` at the repo root. See `docs/agents/domain.md`.
