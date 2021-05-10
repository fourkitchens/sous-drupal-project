<?php

namespace Drupal\taxonomy_manager\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Managing the advanced options for the taxonomy_manager module.
 */
class TaxonomyManagerAdmin extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['taxonomy_manager.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('taxonomy_manager.settings');
    $form['taxonomy_manager_disable_mouseover'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable mouse-over effect for terms (weights and direct link)'),
      '#default_value' => $config->get('taxonomy_manager_disable_mouseover'),
      '#description' => $this->t('Disabling this feature speeds up the Taxonomy Manager'),
    ];
    $form['taxonomy_manager_pager_tree_page_size'] = [
      '#type' => 'select',
      '#title' => $this->t('Pager count'),
      '#options' => [
        25 => 25,
        50 => 50,
        75 => 75,
        100 => 100,
        150 => 150,
        200 => 200,
        250 => 250,
        300 => 300,
        400 => 400,
        500 => 500,
        1000 => 1000,
        2500 => 2500,
        5000 => 5000,
        10000 => 10000,
      ],
      '#default_value' => $config->get('taxonomy_manager_pager_tree_page_size'),
      '#description' => $this->t('Select how many terms should be listed on one page. Huge page counts can slow down the Taxonomy Manager'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('taxonomy_manager.settings');
    $mouse_over = $form_state->getValue('taxonomy_manager_disable_mouseover');
    $page_size = $form_state->getValue('taxonomy_manager_pager_tree_page_size');
    $config->set('taxonomy_manager_disable_mouseover', $mouse_over);
    $config->set('taxonomy_manager_pager_tree_page_size', $page_size);
    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'taxonomy_manager_settings_form';
  }

}
