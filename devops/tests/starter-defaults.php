<?php

declare(strict_types=1);

/** Check package ownership and native retention without bootstrapping a site. */

$root = dirname(__DIR__, 2);
$manifest = json_decode(file_get_contents($root . '/composer.json'), TRUE, 512, JSON_THROW_ON_ERROR);
$check = static function (bool $condition, string $message): void {
  if (!$condition) {
    throw new RuntimeException($message);
  }
};
$names = ['sous-admin', 'sous-config-split', 'sous-content-types', 'sous-core-layout-builder', 'sous-emulsify', 'sous-formats', 'sous-media', 'sous-paragraph-types', 'sous-paragraphs'];
$ids = array_map(static fn (string $name): string => 'fourkitchens/' . $name, $names);
$ignored = $manifest['extra']['drupal-recipe-unpack']['ignore'] ?? [];
sort($ignored);
sort($ids);
$check($ignored === $ids, 'Native unpack exclusions must contain exactly the nine independent names.');
foreach (['sous-admin', 'sous-emulsify'] as $base) {
  $check(($manifest['require']['fourkitchens/' . $base] ?? NULL) === '^2.0', 'Starter base must retain the stable package requirement: ' . $base);
}
foreach (['sous-content-types', 'sous-paragraphs', 'sous-paragraph-types', 'sous-core-layout-builder', 'sous-config-split'] as $optional) {
  $check(!isset($manifest['require']['fourkitchens/' . $optional]), 'An optional package became an unconditional root requirement: ' . $optional);
}
$repositories = array_values(array_filter($manifest['repositories'], static fn (array $repository): bool => ($repository['type'] ?? NULL) === 'composer' && in_array('fourkitchens/sous-admin', $repository['only'] ?? [], TRUE)));
$check(count($repositories) === 1, 'The starter needs one scoped production Composer index.');
$repository = $repositories[0];
$check($repository['url'] === 'https://fourkitchens.github.io/sous-drupal-packages/' && ($repository['canonical'] ?? TRUE), 'Use the canonical public index, never an unpublished candidate override.');
$scoped = $repository['only'];
sort($scoped);
$check($scoped === $ids, 'The canonical index must own exactly the nine names.');
foreach ($manifest['repositories'] as $repository) {
  $check(!in_array($repository['type'] ?? NULL, ['path', 'vcs'], TRUE), 'Contributor repository overrides must stay outside the published root.');
}
$check(($manifest['minimum-stability'] ?? 'stable') === 'stable', 'Keep the stable dependency floor.');
foreach (['sous-starter-content', 'sous-starter-paragraphs', 'sous-layout-builder', 'sous-config-split'] as $legacy) {
  $check(!is_file($root . '/web/recipes/' . $legacy . '/recipe.yml'), 'The starter must not bundle deprecated optional recipe payloads: ' . $legacy);
}
print "PASS: stable base packages, independent choices, exact native retention and scoped public index; no site/database changed.\n";
