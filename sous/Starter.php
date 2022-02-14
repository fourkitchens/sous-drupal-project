<?php

namespace Sous;

use DrupalFinder\DrupalFinder;
use Symfony\Component\Yaml\Yaml;


/**
 * Provides static functions for composer script events. See also
 * core/lib/Drupal/Composer/Composer.php, which contains similar
 * scripts needed by projects that include drupal/core. Scripts that
 * are only needed by drupal/drupal go here.
 *
 * @see https://getcomposer.org/doc/articles/scripts.md
 */
class Starter {

public static function installTheme() {
  // New DrupalFinder to get the Composer root path.
  $drupalFinder = new DrupalFinder();
  $drupalFinder->locateRoot(getcwd());
  $removeChars = array("-", ".", " ");
  $composerRoot = str_replace($removeChars, '_', strtolower(basename($drupalFinder->getComposerRoot())));
  shell_exec ("cd web/themes/contrib/emulsify-drupal/ && npm install");
  // Execute the Emulsify theme build based on composer create path.
  shell_exec ("cd web/themes/contrib/emulsify-drupal/ && emulsify init $composerRoot");
  shell_exec ("cd web/themes/custom/$composerRoot/ && npm install");
  shell_exec ("cd web/themes/custom/$composerRoot/ && emulsify system install --repository https://github.com/emulsify-ds/compound.git");
  // Generate  system.theme.yml and append new theme to install.
  $system_theme_yml = [
    "default" => $composerRoot,
    "admin"=> "gin"
  ];
  $yaml = Yaml::dump($system_theme_yml);
  file_put_contents('web/profiles/contrib/sous/config/install/system.theme.yml', $yaml);
  file_put_contents('web/profiles/contrib/sous/sous.info.yml', '  - '.$composerRoot.PHP_EOL, FILE_APPEND | LOCK_EX);
  // Remove contrib theme after theme generation.
  shell_exec ("rm -rf web/themes/contrib/emulsify-drupal/");
  // Write config folder location.
  shell_exec ("cat web/profiles/contrib/sous/assets/scaffold/default/additions-default.settings.txt >> web/sites/default/default.settings.php");
  shell_exec ("sed -i 's/sous-project/$composerRoot/' .lando.yml");
  }
}
