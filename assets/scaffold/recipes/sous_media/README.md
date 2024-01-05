# Sous Media Drupal Recipe
A recipe that contains modules and configuration for the Sous media experience.

This module configures three media types:
- file
- image
- video

## Configuring Drupal for Recipes

See https://www.drupal.org/files/issues/2023-10-01/Configuring%20Drupal%20to%20Apply%20Recipes.md

## Installing this Recipe

`composer require fourkitchens/sous_media`

## Applying this Recipe

If you used the Sous Project as your starterkit:
- `lando install-recipe sous_media` 

Manually applying the recipe to your own project:
From your webroot run: 
- `php core/scripts/drupal recipe recipes/sous_media`
- `drush cr`
- `composer unpack fourkitchens/sous_media`
