<?php

declare(strict_types=1);

/**
 * Exercise starter defaults only inside an explicitly disposable Drupal site.
 *
 * The shared integration runner supplies SOUS_INTEGRATION_COMBINATION. Owned
 * nodes, Paragraphs and their generated aliases are removed even on failure.
 */

use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\pathauto\PathautoState;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Drupal\views\Entity\View;

if (getenv('SOUS_DISPOSABLE_TEST') !== '1') {
  throw new RuntimeException('Set SOUS_DISPOSABLE_TEST=1 only for a disposable installation.');
}
$combination = getenv('SOUS_INTEGRATION_COMBINATION') ?: 'default';
if (!in_array($combination, ['default', 'paragraphs', 'core-layout-builder', 'admin-only'], TRUE)) {
  throw new InvalidArgumentException('Unknown starter integration combination: ' . $combination);
}
$check = static function (bool $condition, string $message): void {
  if (!$condition) {
    throw new RuntimeException($message);
  }
};
$role = Role::load('superuser');
$check($role !== NULL && $role->isAdmin(), 'Sous Admin must provide the unrestricted Superuser role.');
if ($combination === 'admin-only') {
  print "PASS: admin-only composition retains the Admin-owned Superuser role.\n";
  return;
}
foreach (['pathauto', 'menu_ui', 'views', 'login_history'] as $module) {
  $check(\Drupal::moduleHandler()->moduleExists($module), 'Missing starter module: ' . $module);
}
foreach (['block_content', 'content', 'glossary', 'files', 'media', 'media_library', 'taxonomy_term', 'who_s_new', 'who_s_online', 'login_history'] as $id) {
  $view = View::load($id);
  $check($view !== NULL, 'Missing native management View: ' . $id);
  $check($view->getExecutable()->initDisplay(), 'Native management View cannot initialize its displays: ' . $id);
}
$displays = \Drupal::service('entity_display.repository');
$storage = \Drupal::entityTypeManager()->getStorage('node');
$nodes = $paragraphs = [];
$switcher = \Drupal::service('account_switcher');
$switcher->switchTo(User::load(1));
try {
  foreach (['page', 'frontpage'] as $bundle) {
    $check(NodeType::load($bundle) !== NULL, 'Missing starter content type: ' . $bundle);
    $marker = 'Sous starter ' . $bundle . ' ' . bin2hex(random_bytes(6));
    $node = Node::create([
      'type' => $bundle,
      'title' => $marker,
      'field_seo_title' => $marker,
      'field_teaser_text' => 'Starter authoring regression.',
      'body' => ['value' => '<p>' . $marker . '</p>', 'format' => 'basic_html'],
      'path' => ['pathauto' => PathautoState::CREATE],
      'uid' => 1,
      'status' => 1,
    ]);
    $nodes[] = $node;
    $has_components = $node->hasField('field_content');
    $check($has_components === ($combination === 'paragraphs'), 'The selected installation option has unexpected Paragraphs host fields: ' . $bundle);
    if ($has_components) {
      $paragraph = Paragraph::create([
        'type' => 'text',
        'field_text' => ['value' => '<p>Component ' . $marker . '</p>', 'format' => 'basic_html'],
      ]);
      $violations = $paragraph->validate();
      $check(count($violations) === 0, 'New Paragraph failed validation: ' . (string) $violations);
      $paragraph->save();
      $paragraphs[] = $paragraph;
      $node->set('field_content', ['target_id' => $paragraph->id(), 'target_revision_id' => $paragraph->getRevisionId()]);
    }
    $form_display = $displays->getFormDisplay('node', $bundle);
    $children = $form_display->getThirdPartySetting('field_group', 'group_content', [])['children'] ?? [];
    $check(in_array('body', $children, TRUE), 'Body is missing from the Content tab: ' . $bundle);
    $form = \Drupal::service('entity.form_builder')->getForm($node);
    foreach (['title', 'body', 'field_seo_title', 'field_teaser_image', 'path'] as $field) {
      $check(isset($form[$field]['widget']), 'Starter form cannot build the ' . $bundle . ' widget: ' . $field);
    }
    if ($has_components) {
      $check(isset($form['field_content']['widget']) && in_array('field_content', $children, TRUE), 'Paragraphs authoring is not reachable: ' . $bundle);
    }
    $display = $displays->getViewDisplay('node', $bundle);
    if ($combination === 'core-layout-builder') {
      $check($display instanceof LayoutBuilderEntityViewDisplay && $display->isLayoutBuilderEnabled() && $display->isOverridable(), 'Starter Layout Builder must be enabled and overridable: ' . $bundle);
      $check($node->hasField('layout_builder__layout'), 'Missing per-node layout override field: ' . $bundle);
      $body_block = FALSE;
      foreach ($display->getSections() as $section) {
        foreach ($section->getComponents() as $component) {
          $body_block = $body_block || $component->getPluginId() === 'field_block:node:' . $bundle . ':body';
        }
      }
      $check($body_block, 'The default layout lost the Body field block: ' . $bundle);
    }
    else {
      $check(($display->getComponent('body')['type'] ?? NULL) === 'text_default', 'Body has no visible formatter: ' . $bundle);
    }
    $violations = $node->validate();
    $check(count($violations) === 0, 'New ' . $bundle . ' failed validation: ' . (string) $violations);
    $node->save();
    $storage->resetCache([$node->id()]);
    $saved = $storage->load($node->id());
    $check($saved->body->value === '<p>' . $marker . '</p>', 'Saved starter Body changed: ' . $bundle);
    $render = static function (Node $entity): string {
      $build = \Drupal::entityTypeManager()->getViewBuilder('node')->view($entity);
      return (string) \Drupal::service('renderer')->renderInIsolation($build);
    };
    $html = $render($saved);
    $check(str_contains($html, '<p>' . $marker . '</p>'), 'Saved starter Body does not render: ' . $bundle);
    $check(!$has_components || str_contains($html, '<p>Component ' . $marker . '</p>'), 'Saved starter Paragraph does not render: ' . $bundle);
    $path = '/node/' . $saved->id();
    $alias = \Drupal::service('path_alias.manager')->getAliasByPath($path, $saved->language()->getId());
    $check($alias !== $path && str_contains($alias, 'sous-starter-' . $bundle), 'The starter alias pattern did not create an alias: ' . $bundle);
    $edit = \Drupal::service('entity.form_builder')->getForm($saved);
    $check(isset($edit['body']['widget']), 'Saved starter content cannot build an edit form: ' . $bundle);
    $saved->set('body', ['value' => '<p>Edited ' . $marker . '</p>', 'format' => 'basic_html']);
    $saved->setNewRevision(TRUE);
    $violations = $saved->validate();
    $check(count($violations) === 0, 'Edited ' . $bundle . ' failed validation: ' . (string) $violations);
    $saved->save();
    $storage->resetCache([$saved->id()]);
    $check(str_contains($render($storage->load($saved->id())), '<p>Edited ' . $marker . '</p>'), 'Edited starter Body does not render: ' . $bundle);
  }
  print 'PASS: Page and Frontpage form/create/edit/validate/save/render, aliases, native management Views and Superuser ownership with ' . $combination . ".\n";
}
finally {
  foreach ($nodes as $node) {
    if (!$node->isNew() && $storage->load($node->id())) {
      $storage->load($node->id())->delete();
    }
  }
  foreach ($paragraphs as $paragraph) {
    if (Paragraph::load($paragraph->id())) {
      Paragraph::load($paragraph->id())->delete();
    }
  }
  $switcher->switchBack();
}
