# Sous coding agent brief

## What lives here

The Composer project template lives here.
DDEV config and the settings scaffold support local development.
The one-time install script and restore script set up sites.
Site docs explain setup, development and maintenance.

## Simplicity rules

- Prefer deleting code to adding it. A change should leave the repo the same
  size or smaller unless it adds a requested feature.
- No new abstraction layers, environment frameworks or "policy" files.
  Use Drupal's standard `settings.php` + `settings.local.php` pattern.
- No tests that mock ddev, drush, composer or npm, and no tests that assert
  what `composer.json` or other config files contain.
  The smoke test covers end-to-end behaviour.
- Don't work around upstream bugs (Emulsify, contrib modules) with scripts
  here. Use a Composer patch with an issue link, or fix the upstream project.
- No defensive checks for situations the scripts can't reach.
  Setup, restore, import and recipe scripts stop unless DDEV is present.
  theme-build, confex, confim and composer ci also run in CI and on hosts,
  where tools run directly.
- Comments: at most two lines, plain English. Explain why; in scripts longer
  than about 40 lines, also head each function or section with what it does.
- Docs: edit an existing file rather than adding one.

## Size

Keep each PR under about 400 changed lines, excluding lockfiles and exported
config. Split bigger work.

## Commits

Use Conventional Commits: feat, fix, refactor, docs, ci, test or chore.
Use `!` for breaking changes.

## Verify

Run from the repo root. Commit candidate changes before smoke tests; they
export `HEAD`.

```sh
npm ci
npm run lint
find devops -name '*.sh' -print0 | xargs -0 shellcheck -x -P SCRIPTDIR
composer validate --no-check-publish
actionlint .github/workflows/ci.yml
bash devops/integration/run.sh --option default
bash devops/integration/run.sh --option paragraphs --config-split
```

For unpublished package changes, append `--packages /path/to/sous-drupal-packages`.
