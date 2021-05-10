<?php

namespace Drupal\taxonomy_manager;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\language\Entity\ContentLanguageSettings;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class for taxonomy manager helper.
 */
class TaxonomyManagerHelper {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $taxonomyTypeManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The manages modules.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Create an TaxonomyManagerHelper object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The manages modules.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager, AccountInterface $current_user, ModuleHandlerInterface $module_handler) {
    $this->taxonomyTypeManager = $entity_type_manager->getStorage('taxonomy_term');
    $this->languageManager = $language_manager;
    $this->currentUser = $current_user;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
      $container->get('current_user'),
      $container->get('module_handler')
    );
  }

  /**
   * Checks if voc has terms.
   *
   * @param int $vid
   *   The term ID.
   *
   * @return bool
   *   True, if terms already exists, else false
   */
  public function vocabularyIsEmpty($vid) {
    /** @var \Drupal\taxonomy\TermStorageInterface $term_storage */
    $term_storage = $this->taxonomyTypeManager;
    return empty($term_storage->loadTree($vid));
  }

  /**
   * Helper function for mass adding of terms.
   *
   * @param string $input
   *   The textual input with terms. Each line contains a single term. Child
   *   term can be prefixed with a dash '-' (one dash for each level). Term
   *   names starting with a dash and should not become a child term need
   *   to be wrapped in quotes.
   * @param int $vid
   *   The vocabulary id.
   * @param int $parents
   *   An array of parent term ids for the new inserted terms. Can be 0.
   * @param array $term_names_too_long
   *   Return value that is used to indicate that some term names were too long
   *   and truncated to 255 characters.
   *
   * @return array
   *   An array of the newly inserted term objects
   */
  public function massAddTerms($input, $vid, $parents, array &$term_names_too_long = []) {
    $new_terms = [];
    $terms = explode("\n", str_replace("\r", '', $input));
    $parents = !empty($parents) ? $parents : 0;

    // Stores the current lineage of newly inserted terms.
    $last_parents = [];
    foreach ($terms as $name) {
      if (empty($name)) {
        continue;
      }
      $matches = [];

      // Child term prefixed with one or more dashes.
      if (preg_match('/^(-){1,}/', $name, $matches)) {
        $depth = strlen($matches[0]);
        $name = substr($name, $depth);
        $current_parents = isset($last_parents[$depth - 1]) ? [$last_parents[$depth - 1]->id()] : 0;
      }
      // Parent term containing dashes at the beginning and is therefore wrapped
      // in double quotes.
      elseif (preg_match('/^\"(-){1,}.*\"/', $name, $matches)) {
        $name = substr($name, 1, -1);
        $depth = 0;
        $current_parents = $parents;
      }
      else {
        $depth = 0;
        $current_parents = $parents;
      }

      // Truncate long string names that will cause database exceptions.
      if (strlen($name) > 255) {
        $term_names_too_long[] = $name;
        $name = substr($name, 0, 255);
      }

      $filter_formats = filter_formats();
      $format = array_pop($filter_formats);

      // Set default language.
      $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED;

      // Check if site is multilingual.
      if (\Drupal::moduleHandler()->moduleExists('language')) {
        $language_configuration = ContentLanguageSettings::loadByEntityTypeBundle('taxonomy_term', $vid);
        $lang_setting = $language_configuration->getDefaultLangcode();

        if ($lang_setting == 'site_default') {
          $langcode = $this->languageManager->getDefaultLanguage()->getId();
        }
        elseif ($lang_setting == 'current_interface') {
          $langcode = $this->languageManager->getCurrentLanguage()->getId();
        }
        elseif ($lang_setting == 'authors_default') {
          $langcode = $this->currentUser->getPreferredLangcode();
        }
        elseif ($lang_setting == 'und' || $lang_setting == 'zxx') {
          $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED;
        }
        else {
          // Fixed value set like 'en'.
          $langcode = $lang_setting;
        }
      }

      $values = [
        'name' => $name,
        // @todo do we need to set a format?
        'format' => $format->id(),
        'vid' => $vid,
        'langcode' => $langcode,
      ];

      if (!empty($current_parents)) {
        foreach ($current_parents as $p) {
          $values['parent'][] = ['target_id' => $p];
        }

      }
      $term = $this->taxonomyTypeManager->create($values);
      $term->save();
      $new_terms[] = $term;
      $last_parents[$depth] = $term;
    }
    return $new_terms;
  }

  /**
   * Helper function that deletes terms.
   *
   * Optionally orphans (terms where parent get deleted) can be deleted as well.
   *
   * Difference to core: deletion of orphans optional.
   *
   * @param array $tids
   *   Array of term ids to delete.
   * @param bool $delete_orphans
   *   If TRUE, orphans get deleted.
   */
  public function deleteTerms(array $tids, $delete_orphans = FALSE) {
    $deleted_terms = [];
    $remaining_child_terms = [];

    while (count($tids) > 0) {
      $orphans = [];
      foreach ($tids as $tid) {
        $children = $this->taxonomyTypeManager->loadChildren($tid);
        if (!empty($children)) {
          foreach ($children as $child) {
            $parents = $this->taxonomyTypeManager->loadParents($child->id());
            if ($delete_orphans) {
              if (count($parents) == 1) {
                $orphans[$child->tid] = $child->id();
              }
              else {
                $remaining_child_terms[$child->id()] = $child->getName();
              }
            }
            else {
              $remaining_child_terms[$child->id()] = $child->getName();
              if ($parents) {
                // Parents structure see TermStorage::updateTermHierarchy.
                $parents_array = [];
                foreach ($parents as $parent) {
                  if ($parent->id() != $tid) {
                    $parent->target_id = $parent->id();
                    $parents_array[$parent->id()] = $parent;
                  }
                }
                if (empty($parents_array)) {
                  $parents_array = [0];
                }
                $child->parent = $parents_array;
                $this->taxonomyTypeManager->deleteTermHierarchy([$child->id()]);
                $this->taxonomyTypeManager->updateTermHierarchy($child);
              }
            }
          }
        }
        $term = $this->taxonomyTypeManager->load($tid);
        if ($term) {
          $deleted_terms[] = $term->getName();
          $term->delete();
        }
      }
      $tids = $orphans;
    }
    return ['deleted_terms' => $deleted_terms, 'remaining_child_terms' => $remaining_child_terms];
  }

}
