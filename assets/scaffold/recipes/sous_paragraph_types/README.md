# Sous Paragraphs Drupal Recipe
A recipe that contains modules and configuration for the default Sous paragraphs.

This module configures four paragraph types:
- image
- text
- text with media  
- video

## Configuring Drupal for Recipes

See https://www.drupal.org/files/issues/2023-10-01/Configuring%20Drupal%20to%20Apply%20Recipes.md

## Installing this Recipe

`composer require fourkitchens/sous_paragraphs`

## Applying this Recipe

If you used the Sous Project as your starterkit:
- `lando install-recipe sous_paragraphs` 

Manually applying the recipe to your own project:
From your webroot run: 
- `php core/scripts/drupal recipe recipes/sous_paragraphs`
- `drush cr`
- `composer unpack fourkitchens/sous_paragraphs`
