# Sous Emulsify Drupal Recipe
A recipe that installs Emulsify dependencies and sets Emulsify as the default theme.

## Configuring Drupal for Recipes

See https://www.drupal.org/files/issues/2023-10-01/Configuring%20Drupal%20to%20Apply%20Recipes.md
 
## Installing this Recipe

`composer require fourkitchens/sous-emulsify`

## Applying this Recipe

If you used the Sous Project as your starterkit:
- `lando install-recipe sous-emulsify` 

Manually applying the recipe to your own project:
From your webroot run: 
- `php core/scripts/drupal recipe recipes/sous-emulsify`
- `drush cr`
- `composer unpack fourkitchens/sous-emulsify`
