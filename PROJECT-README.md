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

Config Split is independently optional. The site's configuration records its choices.

## Work locally

Clone this repository, obtain a database backup, then restore it:

```sh
sh devops/scripts/setup.sh "reference/site-db.sql.gz"
```

[Setup](docs/SETUP.md) covers restoration, recipes and local settings.
[Development](docs/DEVELOPMENT.md) covers theme commands and checks.
[Deployment](docs/DEPLOYMENT.md) covers builds, releases and configuration updates.

[GPL-2.0-or-later](LICENSE); dependencies retain their own notices.
