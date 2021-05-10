<?php

namespace Drupal\google_tag\Plugin\Condition;

use Drupal\google_tag\ConditionBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Language' condition.
 *
 * @Condition(
 *   id = "gtag_language",
 *   label = @Translation("Language"),
 *   context_definitions = {
 *     "language" = @ContextDefinition("language", label = @Translation("Language"))
 *   }
 * )
 */
class Language extends ConditionBase implements ContainerFactoryPluginInterface {

  /**
   * The Language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a language condition plugin.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(LanguageManagerInterface $language_manager, array $configuration, $plugin_id, $plugin_definition) {
    $this->toggle = 'language_toggle';
    $this->list = 'language_list';
    $this->singular = 'language';
    $this->plural = 'languages';
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->languageManager = $language_manager;
    $this->options = $this->languageOptions();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('language_manager'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    if (!$this->languageManager->isMultilingual()) {
      $form['language_list'] = [
        '#type' => 'value',
        '#default_value' => $this->configuration['language_list'],
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    $languages = $this->languageManager->getLanguages(LanguageInterface::STATE_ALL);
    $selected = $this->configuration['language_list'];
    // Reduce the language object list to a language name list.
    $this->values = array_reduce($languages, function (&$names, $language) use ($selected) {
      if (!empty($selected[$language->getId()])) {
        $names[$language->getId()] = $language->getName();
      }
      return $names;
    }, []);
    return parent::summary();
  }

  /**
   * Returns associative array of language names keyed by language ID.
   *
   * @return array
   *   The associative array of language names keyed by language ID.
   */
  public function languageOptions() {
    $options = [];
    if ($this->languageManager->isMultilingual()) {
      $languages = $this->languageManager->getLanguages();
      foreach ($languages as $language) {
        $options[$language->getId()] = $language->getName();
      }
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function contextToEvaluate() {
    return $this->getContextValue('language')->getId();
  }

}
