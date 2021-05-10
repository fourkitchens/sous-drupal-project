<?php

namespace Drupal\paragraphs_features\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 *
 * @package Drupal\paragraphs_features\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'paragraphs_features.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('paragraphs_features.settings');

    $form['dropdown_to_button'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Reduce actions dropdown to a button when there is only one option'),
      '#default_value' => $config->get('dropdown_to_button'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('paragraphs_features.settings')
      ->set('dropdown_to_button', $form_state->getValue('dropdown_to_button'))
      ->save();
  }

}
