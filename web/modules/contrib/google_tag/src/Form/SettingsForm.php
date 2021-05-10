<?php

namespace Drupal\google_tag\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Defines the Google tag manager module and default container settings form.
 */
class SettingsForm extends ConfigFormBase {

  use ContainerTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_tag_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['google_tag.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->container = $this->config('google_tag.settings');
    $this->prefix = '_default_container.';

    // Build form elements.
    $description = $this->t('<br />After configuring the module settings and default properties for a new container, <strong>add a container</strong> on the <a href=":url">container management page</a>.', [':url' => Url::fromRoute('entity.google_tag_container.collection')->toString()]);

    $form['instruction'] = [
      '#type' => 'markup',
      '#markup' => $description,
    ];

    $form['module'] = $this->moduleFieldset($form_state);

    $form['settings'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Default container settings'),
      '#description' => $this->t('The default container settings that apply to a new container.'),
      '#attributes' => ['class' => ['google-tag']],
    ];

    $form['conditions'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Default insertion conditions'),
      '#description' => $this->t('The default snippet insertion conditions that apply to a new container.'),
      '#attributes' => ['class' => ['google-tag']],
      '#attached' => [
        'library' => ['google_tag/drupal.settings_form'],
      ],
    ];

    $form['advanced'] = $this->advancedFieldset($form_state);
    $form['path'] = $this->pathFieldset($form_state);
    $form['role'] = $this->roleFieldset($form_state);
    $form['status'] = $this->statusFieldset($form_state);

    return parent::buildForm($form, $form_state);
  }

  /**
   * Fieldset builder for the module settings form.
   */
  public function moduleFieldset(FormStateInterface $form_state) {
    $config = $this->config('google_tag.settings');

    // Build form elements.
    $fieldset = [
      '#type' => 'fieldset',
      '#title' => $this->t('Module settings'),
      '#description' => $this->t('The settings that apply to all containers.'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    $fieldset['uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Snippet parent URI'),
      '#description' => $this->t('The parent URI for saving snippet files. Snippet files will be saved to "[uri]/google_tag". Enter a plain stream wrapper with a single trailing slash like "public:/".'),
      '#default_value' => $config->get('uri'),
      '#attributes' => ['placeholder' => ['public:/']],
      '#required' => TRUE,
    ];

    $fieldset['compact_snippet'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Compact the JavaScript snippet'),
      '#description' => $this->t('If checked, then the JavaScript snippet will be compacted to remove unnecessary whitespace. This is <strong>recommended on production sites</strong>. Leave unchecked to output a snippet that can be examined using a JavaScript debugger in the browser.'),
      '#default_value' => $config->get('compact_snippet'),
    ];

    $fieldset['include_file'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include the snippet as a file'),
      '#description' => $this->t('If checked, then each JavaScript snippet will be included as a file. This is <strong>recommended</strong>. Leave unchecked to inline each snippet into the page. This only applies to data layer and script snippets.'),
      '#default_value' => $config->get('include_file'),
    ];

    $fieldset['rebuild_snippets'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Recreate snippets on cache rebuild'),
      '#description' => $this->t('If checked, then the JavaScript snippet files will be created during a cache rebuild. This is <strong>recommended on production sites</strong>. If not checked, any missing snippet files will be created during a page response.'),
      '#default_value' => $config->get('rebuild_snippets'),
    ];

    $fieldset['flush_snippets'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Flush snippet directory on cache rebuild'),
      '#description' => $this->t('If checked, then the snippet directory will be deleted during a cache rebuild. If not checked, then manual intervention may be required to tidy up the snippet directory (e.g. remove snippet files for a deleted container).'),
      '#default_value' => $config->get('flush_snippets'),
    ];

    $fieldset['debug_output'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show debug output'),
      '#description' => $this->t('If checked, then the result of each snippet insertion condition will be shown in the message area. Enable <strong>only for development</strong> purposes.'),
      '#default_value' => $config->get('debug_output'),
    ];

    return $fieldset;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $this->validateFormValues($form, $form_state);
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('google_tag.settings');
    $old_uri = $config->get('uri');

    $settings = $config->get();
    unset($settings['_default_container'], $settings['_core']);
    foreach (array_keys($settings) as $key) {
      $config->set($key, $form_state->getValue($key));
    }
    $default_container = $config->get('_default_container');
    unset($default_container['container_id']);
    foreach (array_keys($default_container) as $key) {
      $config->set("_default_container.$key", $form_state->getValue($key));
    }
    $config->save();

    parent::submitForm($form, $form_state);
    // @todo Only display if a container exists?
    $message = 'Changes to default container settings and insertion conditions <strong>only apply to new containers</strong>. To modify settings for existing containers, click the container management link below.';
    $args = ['%directory' => $old_uri . '/google_tag'];
    $this->messenger()->addWarning($this->t($message, $args));

    $new_uri = $config->get('uri');
    if ($old_uri != $new_uri) {
      // The snippet uri changed; recreate snippets for all containers.
      global $_google_tag_display_message;
      $_google_tag_display_message = TRUE;
      _google_tag_assets_create();

      $message = 'The snippet directory was changed and the snippet files were created in the new directory. The old directory at %directory was not deleted.';
      $args = ['%directory' => $old_uri . '/google_tag'];
      $this->messenger()->addWarning($this->t($message, $args));
    }
  }

}
