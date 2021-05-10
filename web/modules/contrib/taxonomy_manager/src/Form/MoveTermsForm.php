<?php

namespace Drupal\taxonomy_manager\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\TermStorage;
use Drupal\taxonomy\VocabularyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for deleting given terms.
 */
class MoveTermsForm extends FormBase {

  /**
   * The current request.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $termStorage;

  /**
   * MoveTermsForm constructor.
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
   *
   * @TODO: Add autocomplete to select/add parent term.
   */
  public function buildForm(array $form, FormStateInterface $form_state, VocabularyInterface $taxonomy_vocabulary = NULL, $selected_terms = []) {
    if (empty($selected_terms)) {
      $form['info'] = [
        '#markup' => $this->t('Please select the terms you want to move.'),
      ];
      return $form;
    }

    // Cache form state so that we keep the parents in the modal dialog.
    $form_state->setCached(TRUE);
    $form['voc'] = ['#type' => 'value', '#value' => $taxonomy_vocabulary];
    $form['selected_terms']['#tree'] = TRUE;

    $items = [];
    foreach ($this->termStorage->loadMultiple($selected_terms) as $term) {
      $items[] = $term->label();
      $form['selected_terms'][$term->id()] = ['#type' => 'value', '#value' => $term->id()];
    }

    $form['terms'] = [
      '#theme' => 'item_list',
      '#items' => $items,
      '#title' => $this->t('Selected terms to move:'),
    ];

    $form['keep_old_parents'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Keep old parents and add new ones (multi-parent). Otherwise old parents get replaced.'),
    ];

    $form['delete'] = [
      '#type' => 'submit',
      '#value' => $this->t('Move'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $taxonomy_vocabulary = $form_state->getValue('voc');

    $this->messenger()->addError($this->t('Move operation not yet implemented.'));
    $form_state->setRedirect('taxonomy_manager.admin_vocabulary', ['taxonomy_vocabulary' => $taxonomy_vocabulary->id()]);

  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'taxonomy_manager_move_form';
  }

}
