<?php

namespace Drupal\taxonomy_manager\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Url;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\TermStorage;
use Drupal\taxonomy\VocabularyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBuilderInterface;

/**
 * Form for exporting given terms.
 */
class ExportTermsForm extends FormBase {

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
    // Cache form state so that we keep the parents in the modal dialog.
    // $form_state->setCached(TRUE);
    $form['voc'] = [
      '#type' => 'value',
      '#value' => $taxonomy_vocabulary,
    ];
    $form['selected_terms']['#tree'] = TRUE;

    $items = [];
    foreach ($selected_terms as $t) {
      $term = $this->termStorage->load($t);
      $items[] = $term->getName();
      $form['selected_terms'][$t] = [
        '#type' => 'value',
        '#value' => $t,
      ];
    }

    if (count($items)) {
      $form['terms'] = [
        '#theme' => 'item_list',
        '#items' => $items,
        '#title' => $this->t('Selected terms for export:'),
      ];
    }

    // Terms to export element.
    $selectedExportType = 'whole';
    $exportType = [
      'whole' => $this->t('Whole Vocabulary'),
      'root' => $this->t('Root level terms only'),
    ];

    if (!empty($selected_terms)) {
      $selectedExportType = 'child';
      $exportType['child'] = $this->t('Child terms of a selected term');
    }

    $form['export_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Terms to export'),
      '#options' => $exportType,
      '#default_value' => $selectedExportType,
      // '#required' => TRUE,
      // '#description' => Url::fromRoute('taxonomy_manager.admin_vocabulary.export')->toString(),
    ];

    $form['exported_data'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Exported data'),
      '#default_value' => 'dv',
      '#rows' => 6,
      '#prefix' => '<div id="export-wrapper">',
      '#suffix' => '</div>',
    ];

    $actionUrl = Url::fromRoute('taxonomy_manager.admin_vocabulary.export', [
      'taxonomy_vocabulary' => $taxonomy_vocabulary->id(),
    ])->toString();

    $form['#action'] = $actionUrl;

    $form['export'] = [
      '#type' => 'button',
      '#value' => $this->t('Export'),
      '#ajax' => [
        'callback' => '::exportTerms',
        // 'callback' => [get_called_class(), 'exportTerms'],
        'wrapper' => 'export-wrapper',
        'event' => 'click',
    // 'disable-refocus' => FALSE,
    //        'progress' => [
    //          'type' => 'throbber',
    //          'message' => $this->t('Exporting...'),
    //        ],
        'url' => $actionUrl,
        'options' => [
          'query' => [
            FormBuilderInterface::AJAX_FORM_REQUEST => TRUE,
          ],
        ],
      ],
    ];
    $form['export']['#ajax']['options']['query'] += \Drupal::request()->query->all();

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
    /*$taxonomy_vocabulary = $form_state->getValue('voc');
    $form_state->setRedirect(
    'taxonomy_manager.admin_vocabulary',
    ['taxonomy_vocabulary' => $taxonomy_vocabulary->id()]
    );*/
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'taxonomy_manager_export_form';
  }

  /**
   * AJAX callback handler for exporting terms.
   */
  public function exportTerms(array &$form, FormStateInterface $form_state) {
    $form['exported_data']['#value'] = 'bam';
    // Return $form['exported_data'];
    // $form['#prefix'] = '<div>';
    //    $response = new AjaxResponse();
    //    $response->addCommand(new HtmlCommand('#export-wrapper', $form));
    //    return $form['exported_data'];.
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#export-wrapper', $form['exported_data']));
    return $response;
  }

  /**
   *
   */
  public function myAjaxCallback(array &$form, FormStateInterface $form_state) {
    // Prepare our textfield. check if the example select field has a selected option.
    if ($selectedValue = $form_state->getValue('example_select')) {
      // Get the text of the selected option.
      $selectedText = $form['example_select']['#options'][$selectedValue];
      // Place the text of the selected option in our textfield.
      $form['output']['#value'] = $selectedText;
    }
    // Return the prepared textfield.
    // return $form;.
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#edit-output', $form['output']));
    return $response;
  }

}
