<?php

namespace Drupal\taxonomy_manager\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Render\Element;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Url;
use Drupal\taxonomy\VocabularyInterface;
use Drupal\taxonomy_manager\TaxonomyManagerHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Taxonomy manager class.
 */
class TaxonomyManagerForm extends FormBase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * The entity form builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $taxonomyTypeManager;

  /**
   * Current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * The url generator service.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The taxonomy messenger helper.
   *
   * @var \Drupal\taxonomy_manager\TaxonomyManagerHelper
   */
  protected $taxonomyManagerHelper;

  /**
   * Constructs a new TaxonomyManagerForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $form_builder
   *   The entity form builder.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The url generator service.
   * @param \Drupal\taxonomy_manager\TaxonomyManagerHelper $taxonomy_manager_helper
   *   The taxonomy messenger helper.
   */
  public function __construct(ConfigFactoryInterface $config_factory, FormBuilderInterface $form_builder, EntityFormBuilderInterface $entity_form_builder, EntityTypeManagerInterface $entity_type_manager, CurrentPathStack $current_path, UrlGeneratorInterface $url_generator, TaxonomyManagerHelper $taxonomy_manager_helper) {
    $this->configFactory = $config_factory;
    $this->formBuilder = $form_builder;
    $this->entityFormBuilder = $entity_form_builder;
    $this->taxonomyTypeManager = $entity_type_manager->getStorage('taxonomy_term');
    $this->currentPath = $current_path;
    $this->urlGenerator = $url_generator;
    $this->taxonomyManagerHelper = $taxonomy_manager_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('form_builder'),
      $container->get('entity.form_builder'),
      $container->get('entity_type.manager'),
      $container->get('path.current'),
      $container->get('url_generator'),
      $container->get('taxonomy_manager.helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'taxonomy_manager_vocabulary_terms_form';
  }

  /**
   * Returns the title for the whole page.
   *
   * @param string $taxonomy_vocabulary
   *   The name of the vocabulary.
   *
   * @return string
   *   The title, itself
   */
  public function getTitle($taxonomy_vocabulary) {
    return $this->t("Taxonomy Manager - %voc_name", ["%voc_name" => $taxonomy_vocabulary->label()]);
  }

  /**
   * Form constructor.
   *
   * Display a tree of all the terms in a vocabulary, with options to edit
   * each one. The form implements the Taxonomy Manager intefrace.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\taxonomy\VocabularyInterface $taxonomy_vocabulary
   *   The vocabulary being with worked with.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, VocabularyInterface $taxonomy_vocabulary = NULL) {
    $form['voc'] = [
      '#type' => 'value',
      "#value" => $taxonomy_vocabulary,
    ];
    $form['#attached']['library'][] = 'taxonomy_manager/form';

    if ($this->taxonomyManagerHelper->vocabularyIsEmpty($taxonomy_vocabulary->id())) {
      $form['text'] = [
        '#markup' => $this->t('No terms available'),
      ];
      $form[] = $this->formBuilder->getForm('Drupal\taxonomy_manager\Form\AddTermsToVocabularyForm', $taxonomy_vocabulary);
      return $form;
    }

    $form['toolbar'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Toolbar'),
    ];

    $form['toolbar']['add'] = [
      '#type' => 'submit',
      '#name' => 'add',
      '#value' => $this->t('Add'),
      '#ajax' => [
        'callback' => '::addFormCallback',
      ],
    ];

    $form['toolbar']['delete'] = [
      '#type' => 'submit',
      '#name' => 'delete',
      '#value' => $this->t('Delete'),
      '#attributes' => [
        'disabled' => TRUE,
      ],
      '#ajax' => [
        'callback' => '::deleteFormCallback',
      ],
    ];

    $form['toolbar']['move'] = [
      '#type' => 'submit',
      '#name' => 'move',
      '#value' => $this->t('Move'),
      '#ajax' => [
        'callback' => '::moveFormCallback',
      ],
    ];

    $form['toolbar']['export'] = [
      '#type' => 'submit',
      '#name' => 'export',
      '#value' => $this->t('Export'),
      '#ajax' => [
        'callback' => '::exportFormCallback',
      ],
    ];

    $form['toolbar']['miniexport'] = [
      '#type' => 'submit',
      '#name' => 'export',
      '#value' => $this->t('Export all'),
      '#ajax' => [
        'callback' => '::exportListFormCallback',
      ],
    ];
    /* Vocabulary switcher */
    $vocabularies = \Drupal::entityTypeManager()->getStorage('taxonomy_vocabulary')->loadMultiple();
    foreach ($vocabularies as $voc) {
      $voc_list[$voc->id()] = $voc->label();
    }

    $current_path = \Drupal::service('path.current')->getPath();
    $url_parts = explode('/',$current_path);
    $voc_id = end($url_parts);
    $form['toolbar']['vocabulary_switcher'] = [
      '#type' => 'select',
      '#title' => $this->t('Vocabulary switcher'),
      '#options' => $voc_list,
      '#attributes' => ['onchange' => "form.submit('taxonomy-manager-vocabulary-terms-form')"],
      '#default_value' => $voc_id,
    ];

    /* Autocomplete function redirecting to taxonomy term details page */
    $form['toolbar']['search_terms'] = [
      '#title' => $this->t('Search all terms in this vocabulary'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'taxonomy_term',
      '#selection_settings' => [
        'target_bundles' => [$voc_id],
      ],
    ];

    $form['toolbar']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#submit' => ['::taxonomy_term_submit_handler'],
      '#attributes' => ['class' => ['taxonomy-manager-hidden-button']],
    ];

    /* Taxonomy manager. */
    $form['taxonomy']['#tree'] = TRUE;

    $form['taxonomy']['manager'] = [
      '#type' => 'fieldset',
      '#title' => Html::escape($taxonomy_vocabulary->label()),
      '#tree' => TRUE,
    ];

    $form['taxonomy']['manager']['top'] = [
      '#markup' => '',
      '#prefix' => '<div class="taxonomy-manager-tree-top">',
      '#suffix' => '</div>',
    ];

    $form['taxonomy']['manager']['tree'] = [
      '#type' => 'taxonomy_manager_tree',
      '#vocabulary' => $taxonomy_vocabulary->id(),
      '#pager_size' => $this->configFactory
        ->get('taxonomy_manager.settings')
        ->get('taxonomy_manager_pager_tree_page_size'),
    ];

    $form['taxonomy']['manager']['pager'] = ['#type' => 'pager'];

    // Add placeholder for term data form, the load-term-data field has AJAX
    // events attached and will trigger the load of the term data form. The
    // field is hidden via CSS and the value gets set in termData.js.
    $form['term-data']['#prefix'] = '<div id="taxonomy-term-data-form">';
    $form['term-data']['#suffix'] = '</div>';
    $form['load-term-data'] = [
      '#type' => 'textfield',
      '#ajax' => [
        'callback' => '::termDataCallback',
        'event' => 'change',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function taxonomy_term_submit_handler(array &$form, FormStateInterface $form_state) {
    $tid = $form_state->getValue(['search_terms']);
    $url = Url::fromRoute('entity.taxonomy_term.edit_form', array('taxonomy_term' => $tid));
    $form_state->setRedirectUrl($url);
  }
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->getValue(['taxonomy', 'manager', 'tree']);
    $url = Url::fromRoute('taxonomy_manager.admin_vocabulary', array('taxonomy_vocabulary' => $form_state->getValue(['vocabulary_switcher'])));
    $form_state->setRedirectUrl($url);
  }

  /**
   * AJAX callback handler for add form.
   */
  public function addFormCallback($form, FormStateInterface $form_state) {
    return $this->modalHelper($form_state, 'Drupal\taxonomy_manager\Form\AddTermsToVocabularyForm', 'taxonomy_manager.admin_vocabulary.add', $this->t('Add terms'));
  }

  /**
   * AJAX callback handler for delete form.
   */
  public function deleteFormCallback($form, FormStateInterface $form_state) {
    return $this->modalHelper($form_state, 'Drupal\taxonomy_manager\Form\DeleteTermsForm', 'taxonomy_manager.admin_vocabulary.delete', $this->t('Delete terms'));
  }

  /**
   * AJAX callback handler for move form.
   */
  public function moveFormCallback($form, FormStateInterface $form_state) {
    return $this->modalHelper($form_state, 'Drupal\taxonomy_manager\Form\MoveTermsForm', 'taxonomy_manager.admin_vocabulary.move', $this->t('Move terms'));
  }

  /**
   * AJAX callback handler for export terms from a given vocabulary.
   */
  public function exportFormCallback($form, FormStateInterface $form_state) {
    return $this->modalHelper($form_state, 'Drupal\taxonomy_manager\Form\ExportTermsForm', 'taxonomy_manager.admin_vocabulary.export', $this->t('Export terms'));
  }

  /**
   * AJAX callback handler for export terms from a given vocabulary.
   */
  public function exportListFormCallback($form, FormStateInterface $form_state) {
    return $this->modalHelper($form_state, 'Drupal\taxonomy_manager\Form\ExportTermsMiniForm', 'taxonomy_manager.admin_vocabulary.exportlist', $this->t('Export terms'));
  }

  /**
   * AJAX callback handler for export terms from a given vocabulary.
   */
  public function exportCsvFormCallback($form, FormStateInterface $form_state) {
    return $this->modalHelper($form_state, 'Drupal\taxonomy_manager\Form\ExportTermsMiniForm', 'taxonomy_manager.admin_vocabulary.exportlist', $this->t('Export terms (CSV)'));
  }

  /**
   * AJAX callback handler for the term data form.
   */
  public function termDataCallback($form, FormStateInterface $form_state) {
    $taxonomy_term = $this->taxonomyTypeManager->load($form_state->getValue('load-term-data'));

    $term_form = $this->entityFormBuilder->getForm($taxonomy_term, 'default');

    // Move the term data form into a fieldset.
    $term_form['fieldset']['#type'] = 'fieldset';
    $term_form['fieldset']['#title'] = Html::escape($taxonomy_term->getName()) . ' (' . $taxonomy_term->id() . ')';
    $term_form['fieldset']['#attributes'] = [];
    foreach (Element::children($term_form) as $key) {
      if ($key != 'fieldset') {
        $term_form['fieldset'][$key] = $term_form[$key];
        unset($term_form[$key]);
      }
    }

    $term_form['#prefix'] = '<div id="taxonomy-term-data-form">';
    $term_form['#suffix'] = '</div>';
    $current_path = $this->currentPath->getPath();
    // Change the form action url form the current site to the add form.
    $term_form['#action'] = $this->urlGenerator
      ->generateFromRoute(
        'entity.taxonomy_term.edit_form',
        ['taxonomy_term' => $taxonomy_term->id()],
        [
          'query' => [
            'destination' => $current_path,
          ],
        ]
      );

    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#taxonomy-term-data-form', $term_form));
    return $response;
  }

  /**
   * Term data submit handler.
   *
   * @TODO: redirect to taxonomy manager
   */
  public static function termDataFormSubmit($form, FormStateInterface $form_state) {

  }

  /**
   * Helper function to generate a modal form within an AJAX callback.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the current (parent) form.
   * @param string $class_name
   *   The class name of the form to embed in the modal.
   * @param string $route_name
   *   The route name the form is located.
   * @param string $title
   *   The modal title.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response.
   */
  protected function modalHelper(FormStateInterface $form_state, $class_name, $route_name, $title) {
    $taxonomy_vocabulary = $form_state->getValue('voc');
    $selected_terms = $form_state->getValue(['taxonomy', 'manager', 'tree']);

    $del_form = $this->formBuilder->getForm($class_name, $taxonomy_vocabulary, $selected_terms);
    $del_form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    // Change the form action url form the current site to the current form.
    $del_form['#action'] = Url::fromRoute($route_name, ['taxonomy_vocabulary' => $taxonomy_vocabulary->id()])->toString();

    $response = new AjaxResponse();
    $response->addCommand(new OpenModalDialogCommand($title, $del_form, ['width' => '700']));
    return $response;
  }

}
