<?php

namespace Drupal\taxonomy_manager\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\VocabularyInterface;
use Drupal\taxonomy_manager\TaxonomyManagerHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for adding terms to a given vocabulary.
 */
class AddTermsToVocabularyForm extends FormBase {

  /**
   * The taxonomy messenger helper.
   *
   * @var \Drupal\taxonomy_manager\TaxonomyManagerHelper
   */
  protected $taxonomyManagerHelper;

  /**
   * AddTermsToVocabularyForm constructor.
   *
   * @param \Drupal\taxonomy_manager\TaxonomyManagerHelper $taxonomy_manager_helper
   *   The taxonomy messenger helper.
   */
  public function __construct(TaxonomyManagerHelper $taxonomy_manager_helper) {
    $this->taxonomyManagerHelper = $taxonomy_manager_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('taxonomy_manager.helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'taxonomy_manager_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, VocabularyInterface $taxonomy_vocabulary = NULL, $parents = []) {
    // Cache form state so that we keep the parents in the modal dialog.
    // For non modals (non POST request), form state caching on is not allowed.
    // @see FormState::setCached()
    if ($this->getRequest()->getMethod() == 'POST') {
      $form_state->setCached(TRUE);
    }

    $form['voc'] = ['#type' => 'value', '#value' => $taxonomy_vocabulary];
    $form['parents']['#tree'] = TRUE;
    foreach ($parents as $p) {
      $form['parents'][$p] = ['#type' => 'value', '#value' => $p];
    }

    $description = $this->t("If you have selected one or more terms in the tree view, the new terms are automatically children of those.");
    $form['help'] = [
      '#markup' => $description,
    ];

    $form['mass_add'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Terms'),
      '#description' => $this->t("One term per line. Child terms can be prefixed with a
        dash '-' (one dash per hierarchy level). Terms that should not become
        child terms and start with a dash need to be wrapped in double quotes.
        <br />Example:<br />
        animals<br />
        -canine<br />
        --dog<br />
        --wolf<br />
        -feline<br />
        --cat"),
      '#rows' => 10,
      '#required' => TRUE,
    ];
    $form['add'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add'),
      '#attributes' => array(
        'onclick' => 'javascript:var s=this;setTimeout(function(){s.value="Saving...";s.disabled=true;},1);',
      ),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $term_names_too_long = [];
    $term_names = [];

    $taxonomy_vocabulary = $form_state->getValue('voc');
    $parents = $form_state->getValue('parents');
    $mass_terms = $form_state->getValue('mass_add');

    $new_terms = $this->taxonomyManagerHelper->massAddTerms($mass_terms, $taxonomy_vocabulary->id(), $parents, $term_names_too_long);
    foreach ($new_terms as $term) {
      $term_names[] = $term->label();
    }

    if (count($term_names_too_long)) {
      $this->messenger()->addWarning($this->t("Following term names were too long and truncated to 255 characters: %names.", ['%names' => implode(', ', $term_names_too_long)]));
    }
    $this->messenger()->addMessage($this->t("Terms added: %terms", ['%terms' => implode(', ', $term_names)]));
    $form_state->setRedirect('taxonomy_manager.admin_vocabulary', ['taxonomy_vocabulary' => $taxonomy_vocabulary->id()]);
  }

}
