# Sous Admin Drupal Recipe
A recipe that contains modules, themes, and configuration for the Sous administration experience.

## Configuring Drupal for Recipes

See https://www.drupal.org/files/issues/2023-10-01/Configuring%20Drupal%20to%20Apply%20Recipes.md

## Installing this Recipe

`composer require sous-starter/sous-admin`

## Applying this Recipe

If you used the Sous Project as your starterkit:
- `lando install-recipe sous-admin` 

Manually applying the recipe to your own project:
From your webroot run: 
- `php core/scripts/drupal recipe recipes/sous-admin`
- `drush cr`
- `composer unpack fourkitchens/sous-admin`
