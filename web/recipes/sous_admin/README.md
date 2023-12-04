# Sous Admin Drupal Recipe
A recipe that contains modules, themes, and configuration for Sous's administration experience.

## Configuring Drupal for Recipes

See https://www.drupal.org/files/issues/2023-10-01/Configuring%20Drupal%20to%20Apply%20Recipes.md

## Installing this Recipe

`composer require fourkitchens/cookbook:dev-sous-admin`

## Applying this Recipe

If you used the Sous Project as your starterkit:
- `lando install-recipe [recipe_name]` 

Manually applying the recipe to your own project:
From your webroot run: 
- `php core/scripts/drupal recipe recipes/sous_admin`
- `drush cr`
- `composer unpack fourkitchens/cookbook:sous_admin`
