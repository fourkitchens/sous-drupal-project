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
 * @see https://getcomposer.org/doc/articles/scripts.md@beck
 */
class Starter {

public static function sousPrep() {
  // New DrupalFinder to get the Composer root path.
  $drupalFinder = new DrupalFinder();
  $drupalFinder->locateRoot(getcwd());
  $unsafeChars = array(".", "!", "=", "|", "<", ">", "/");
  $spacingChars = array("-", "_", " ", "--", "  ");
  $composerRoot = str_replace($unsafeChars, '', strtolower(basename($drupalFinder->getComposerRoot())));
  // EmulsifyCLI strips out all unsafe and spacing chars when generating
  // the theme. We need to replicate this output.
  $emulsify_project_name = str_replace($spacingChars, '', $composerRoot);
  $dashed_project_name = str_replace(' ','-', str_replace('_', '-', $composerRoot));
  // Remove contrib theme after theme generation.
  shell_exec ("sed -i.bak 's/sous-theme/$emulsify_project_name/g' .lando.yml && rm -f .lando.yml.bak");
  shell_exec ("sed -i.bak 's/sous-project/$dashed_project_name/g' .lando.yml && rm -f .lando.yml.bak");
  // Theme scripts.
  shell_exec ("sed -i.bak 's/sous-project/$emulsify_project_name/g' scripts/sous/theme-build.sh && rm -f scripts/sous/theme-build.sh.bak");
  shell_exec ("sed -i.bak 's/sous-project/$emulsify_project_name/g' scripts/sous/theme-watch.sh && rm -f scripts/sous/theme-watch.sh.bak");
  // Setup script.
  shell_exec ("sed -i.bak 's/sous-project/$emulsify_project_name/g' scripts/sous/setup.sh && rm -f scripts/sous/setup.sh.bak");
  // Composer project name replace
  shell_exec ("sed -i.bak 's/sous-project/$emulsify_project_name/g' composer.json && rm -f composer.json.bak");
  shell_exec ("sed -i.bak 's:fourkitchens/sous-drupal-project:project/$dashed_project_name:g' composer.json && rm -f composer.json.bak");
  }
}
