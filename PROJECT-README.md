# Drupal application

This site uses Sous: Drupal with Gin administration and an Emulsify theme.
Record the project's URLs, contacts and access requirements here.

## Sous installation options

| Choice | Content setup |
| --- | --- |
| Default | Page and Frontpage with Body, metadata, SEO and aliases |
| Layout Builder | Page and Frontpage with native layouts and Basic inline blocks |
| Paragraphs | Page and Frontpage with Body and reusable components |
| Admin only | Administration and theme without Page or Frontpage |

Optional recipes, including Config Split, are chosen at setup with the menu or
`SOUS_RECIPES`. The site's configuration records its choices.
Setup installs the selected CI configuration at `.circleci/config.yml`,
`.github/workflows/build.yml` or `.gitlab-ci.yml`; None installs no CI file.
GitLab uses `.gitlab/merge_request_templates/Default.md` for merge requests.

## Work locally

Install Node.js from `.nvmrc`, Git, Docker and DDEV on your computer.
Clone this repository, obtain a database backup, then set it up:

```sh
npm run setup -- "reference/site-db.sql.gz"
```

Use `npm run rebuild` for a quick database reset after switching branches,
and `npm run theme-watch` while working on the theme. Run npm commands on your
computer; scripts call DDEV for PHP, Drush and database operations.

[Setup](docs/SETUP.md) covers restoration, recipes and local settings.
[Development](docs/DEVELOPMENT.md) covers theme commands and checks.
[Deployment](docs/DEPLOYMENT.md) covers builds, releases and configuration updates.

[GPL-2.0-or-later](LICENSE); dependencies retain their own notices.
