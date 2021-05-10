<?php

namespace Drupal\taxonomy_manager\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\TermStorage;
use Drupal\taxonomy\VocabularyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for exporting given terms.
 */
class ExportTermsMiniForm extends FormBase {

  /**
   * Term management.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $termStorage;

  /**
   * ExportTermsForm constructor.
   *
   * @param \Drupal\taxonomy\TermStorage $termStorage
   *   Object with convenient methods to manage terms.
   */
  public function __construct(TermStorage $termStorage) {
    $this->termStorage = $termStorage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('taxonomy_term')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, VocabularyInterface $taxonomy_vocabulary = NULL, $selected_terms = []) {

    $form['voc'] = [
      '#type' => 'value',
      '#value' => $taxonomy_vocabulary,
    ];
    $form['selected_terms']['#tree'] = TRUE;

    // Load tree.
    $tree = $this->termStorage->loadTree($taxonomy_vocabulary->id());
    $result = [];
    foreach ($tree as $term) {
      $result[] = str_repeat('-', $term->depth) . $term->name;
    }
    $desc = 'Term names with hierarchy: Only term names are exported. Child terms are prefixed with dashes.';
    $desccsv = 'CSV: The comma-separated-values export has following columns: voc id | term id | term name | description | parent id 1 | ... | parent id n';

    $form['exported_data'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Exported data'),
      '#default_value' => implode("\n", $result),
      '#rows' => 12,
      '#prefix' => '<div id="export-wrapper">',
      '#suffix' => '</div>',
      '#description' => $desc,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $taxonomy_vocabulary = $form_state->getValue('voc');
    $form_state->setRedirect(
      'taxonomy_manager.admin_vocabulary',
      ['taxonomy_vocabulary' => $taxonomy_vocabulary->id()]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'taxonomy_manager_export_form_list';
  }

}
