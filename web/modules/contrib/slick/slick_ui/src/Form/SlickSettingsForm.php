<?php

namespace Drupal\slick_ui\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the Slick admin settings form.
 */
class SlickSettingsForm extends ConfigFormBase {

  /**
   * Drupal\Core\Asset\LibraryDiscoveryInterface definition.
   *
   * @var Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /**
     * @var \Drupal\slick_ui\Form\SlickSettingsForm
     */
    $instance = parent::create($container);
    $instance->libraryDiscovery = $container->get('library.discovery');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'slick_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['slick.settings'];
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('slick.settings');

    $form['module_css'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Enable Slick module slick.theme.css'),
      '#description'   => $this->t('Uncheck to permanently disable the module slick.theme.css, normally included along with skins.'),
      '#default_value' => $config->get('module_css'),
      '#prefix'        => $this->t("Note! Slick doesn't need Slick UI to run. It is always safe to uninstall Slick UI once done with optionsets."),
    ];

    $form['slick_css'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Enable Slick library slick-theme.css'),
      '#description'   => $this->t('Uncheck to permanently disable the optional slick-theme.css, normally included along with skins.'),
      '#default_value' => $config->get('slick_css'),
    ];

    $form['disable_old_skins'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Disable deprecated skins'),
      '#description'   => $this->t('Deprecated skins are registered via the <a href=":url">to-be-deprecated hook_hook_info</a>. Now Slick uses plugin system to store its skins. Leave it unchecked if things are broken, or (y/our) sub-modules are not updated with the new plugin system, yet. If you are sure things are not broken, or never register a skin nor using Slick examples, you can check this to reduce extra join. At any rate, skins are permanently cached once, so should not impact much.', [':url' => 'https://www.drupal.org/node/2233261']),
      '#default_value' => $config->get('disable_old_skins'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->configFactory->getEditable('slick.settings')
      ->set('slick_css', $form_state->getValue('slick_css'))
      ->set('module_css', $form_state->getValue('module_css'))
      ->set('disable_old_skins', $form_state->getValue('disable_old_skins'))
      ->save();

    // Invalidate the library discovery cache to update new assets.
    $this->libraryDiscovery->clearCachedDefinitions();
    $this->configFactory->clearStaticCache();

    parent::submitForm($form, $form_state);
  }

}
