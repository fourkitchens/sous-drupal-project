# Generated-site configuration

This directory belongs to the generated Drupal site. Reusable defaults live in
Sous recipes; the starter does not ship a partial site export here.

Fresh installation exports the site's complete active configuration here after
applying its chosen recipes. Commit the complete export, including
`core.extension.yml`, `system.site.yml`, and the site's configuration UUIDs.
Use `ddev drush config-export --yes` to export later project-owned changes and
review the complete diff before committing it.

After installing reviewed package updates and backing up the database,
`npm run confim` runs native `drush deploy` against the site's configured sync
directory. Existing sites keep their complete exports and local overrides.
Updating a recipe package does not update configuration already applied to a
site; adopt changes through an explicit, reviewed configuration migration.
