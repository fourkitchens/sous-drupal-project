<?php

namespace Drupal\google_tag;

use Drupal\Core\Condition\ConditionInterface;
use Drupal\Core\Executable\ExecutableManagerInterface;
use Drupal\Core\Executable\ExecutablePluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformStateInterface;
use Drupal\Core\Plugin\ContextAwarePluginAssignmentTrait;

/**
 * Provides a basis for fulfilling contexts for condition plugins.
 *
 * @see \Drupal\Core\Condition\Annotation\Condition
 * @see \Drupal\Core\Condition\ConditionInterface
 * @see \Drupal\Core\Condition\ConditionManager
 *
 * @ingroup plugin_api
 */
abstract class ConditionBase extends ExecutablePluginBase implements ConditionInterface {

  use ContextAwarePluginAssignmentTrait;

  /**
   * The condition manager to proxy execute calls through.
   *
   * @var \Drupal\Core\Executable\ExecutableManagerInterface
   */
  protected $executableManager;

  /**
   * The toggle element name.
   *
   * @var string
   */
  protected $toggle;

  /**
   * The list element name.
   *
   * @var string
   */
  protected $list;

  /**
   * The singular form of condition type.
   *
   * @var string
   */
  protected $singular;

  /**
   * The plural form of condition type.
   *
   * @var string
   */
  protected $plural;

  /**
   * The options for the list element.
   *
   * @var array
   */
  protected $options = [];

  /**
   * The selected options (for the summary message).
   *
   * @var array
   */
  protected $values = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return [
      'id' => $this->getPluginId(),
    ] + $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration + $this->defaultConfiguration();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setExecutableManager(ExecutableManagerInterface $executableManager) {
    $this->executableManager = $executableManager;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [$this->toggle => GOOGLE_TAG_EXCLUDE_LISTED, $this->list => []];
  }

  /**
   * {@inheritdoc}
   */
  public function isNegated() {
    return $this->configuration[$this->toggle] == GOOGLE_TAG_EXCLUDE_LISTED;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Gather data.
    if ($form_state instanceof SubformStateInterface) {
      $form_state = $form_state->getCompleteFormState();
    }
    $contexts = $form_state->getTemporaryValue('gathered_contexts') ?: [];

    // Build form elements.
    $form[$this->toggle] = [
      '#type' => 'radios',
      '#title' => $this->specialT('Insert snippet for specific @plural'),
      '#options' => [
        GOOGLE_TAG_EXCLUDE_LISTED => $this->specialT('All @plural except the selected @plural'),
        GOOGLE_TAG_INCLUDE_LISTED => $this->specialT('Only the selected @plural'),
      ],
      '#default_value' => $this->configuration[$this->toggle],
    ];

    $form[$this->list] = [
      '#type' => 'checkboxes',
      '#title' => $this->specialT('Selected @plural'),
      '#options' => $this->options,
      '#default_value' => $this->configuration[$this->list],
    ];

    $form['context_mapping'] = $this->addContextAssignmentElement($this, $contexts);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration[$this->toggle] = $form_state->getValue($this->toggle);
    $this->configuration[$this->list] = array_filter($form_state->getValue($this->list));
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->hasValue('context_mapping')) {
      $this->setContextMapping($form_state->getValue('context_mapping'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    // @todo Remove this routine? It does the quirky negate.
    return $this->executableManager->execute($this);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    // @todo Convert a string list of items to an array and reuse this code.
    $toggle = $this->configuration[$this->toggle];
    $values = $this->configuration[$this->list];

    if (empty($values)) {
      $satisfied = $this->isNegated();
    }
    else {
      $satisfied = in_array($this->contextToEvaluate(), $values);
      $satisfied = $this->isNegated() ? !$satisfied : $satisfied;
    }
    return $satisfied;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    $string = 'The @singular is @adverb@verb "@list".';
    $args = [
      '@singular' => $this->singular,
      '@adverb' => $this->isNegated() ? 'not ' : '',
      '@verb' => count($this->values) > 1 ? 'in' : '',
    ];
    return $this->t(strtr($string, $args), ['@list' => implode(', ', $this->values)]);
  }

  /**
   * Returns a TranslatableMarkup object after placeholder substitution.
   *
   * @param string $string
   *   The string to manipulate.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The markup object.
   */
  public function specialT($string) {
    return $this->t(strtr($string, ['@plural' => $this->plural]));
  }

  /**
   * Returns the entity ID of the context value.
   *
   * @return string
   *   The entity ID of the context value.
   */
  public function contextToEvaluate() {
    return '';
  }

}
