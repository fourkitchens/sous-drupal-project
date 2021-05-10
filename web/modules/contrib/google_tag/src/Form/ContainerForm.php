<?php

namespace Drupal\google_tag\Form;

use Drupal\Core\Condition\ConditionInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Executable\ExecutableManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the Google tag manager container settings form.
 */
class ContainerForm extends EntityForm {

  use ContainerTrait;

  /**
   * The condition plugin manager.
   *
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $conditionManager;

  /**
   * The context repository service.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_tag_container';
  }

  /**
   * Constructs a ContainerForm object.
   *
   * @param \Drupal\Core\Executable\ExecutableManagerInterface $condition_manager
   *   The ConditionManager for building the insertion conditions.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $context_repository
   *   The lazy context repository service.
   */
  public function __construct(ExecutableManagerInterface $condition_manager, ContextRepositoryInterface $context_repository) {
    $this->conditionManager = $condition_manager;
    $this->contextRepository = $context_repository;
  }

  /**
   * {@inheritdoc}
   *
   * This routine is the trick to DependencyInjection in Drupal. Without it the
   * __construct method complains of no arguments instead of three.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.condition'),
      $container->get('context.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $container = $this->container = $this->entity;
    $this->prefix = '';

    // Store the contexts for other objects to use during form building.
    $form_state->setTemporaryValue('gathered_contexts', $this->contextRepository->getAvailableContexts());

    // The main premise of entity forms is that we get to work with an entity
    // object at all times instead of checking submitted values from the form
    // state.

    // Build form elements.
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => 'Label',
      '#default_value' => $container->label(),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $container->id(),
      '#required' => TRUE,
      '#machine_name' => [
        'exists' => [$this, 'containerExists'],
        'replace_pattern' => '[^a-z0-9_.]+',
      ],
    ];

    $form['settings'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Container settings'),
      '#description' => $this->t('The settings affecting the snippet contents for this container.'),
      '#attributes' => ['class' => ['google-tag']],
    ];

    $form['conditions'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Insertion conditions'),
      '#description' => $this->t('The snippet insertion conditions for this container.'),
      '#attributes' => ['class' => ['google-tag']],
      '#attached' => [
        'library' => ['google_tag/drupal.settings_form'],
      ],
    ];

    $form['general'] = $this->generalFieldset($form_state);
    $form['advanced'] = $this->advancedFieldset($form_state);
    $form['path'] = $this->pathFieldset($form_state);
    $form['role'] = $this->roleFieldset($form_state);
    $form['status'] = $this->statusFieldset($form_state);

    $form += $this->conditionsForm([], $form_state);

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => 'Save',
    ];
    $form['actions']['delete'] = [
      '#type' => 'submit',
      '#value' => 'Delete',
    ];

    return $form;
  }

  /**
   * Fieldset builder for the container settings form.
   */
  public function generalFieldset(FormStateInterface &$form_state) {
    $container = $this->entity;

    // Build form elements.
    $fieldset = [
      '#type' => 'details',
      '#title' => $this->t('General'),
      '#group' => 'settings',
    ];

    $fieldset['container_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Container ID'),
      '#description' => $this->t('The ID assigned by Google Tag Manager (GTM) for this website container. To get a container ID, <a href="https://tagmanager.google.com/">sign up for GTM</a> and create a container for your website.'),
      '#default_value' => $container->get('container_id'),
      '#attributes' => ['placeholder' => ['GTM-xxxxxx']],
      '#size' => 12,
      '#maxlength' => 15,
      '#required' => TRUE,
    ];

    $fieldset['weight'] = [
      '#type' => 'weight',
      '#title' => 'Weight',
      '#default_value' => $container->get('weight'),
    ];

    return $fieldset;
  }

  /**
   * Builds the form elements for the insertion conditions.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The augmented form array with the insertion condition elements.
   */
  protected function conditionsForm(array $form, FormStateInterface $form_state) {
    $conditions = $this->entity->getInsertionConditions();
    // See core/lib/Drupal/Core/Plugin/FilteredPluginManagerTrait.php
    // The next method calls alter hooks to filter the definitions.
    // Implement one of the hooks in this module.
    $definitions = $this->conditionManager->getFilteredDefinitions('google_tag', $form_state->getTemporaryValue('gathered_contexts'), ['google_tag_container' => $this->entity]);
    ksort($definitions);
    $form_state->setTemporaryValue('filtered_conditions', array_keys($definitions));
    foreach ($definitions as $condition_id => $definition) {
      if ($conditions->has($condition_id)) {
        $condition = $conditions->get($condition_id);
      }
      else {
        /** @var \Drupal\Core\Condition\ConditionInterface $condition */
        $condition = $this->conditionManager->createInstance($condition_id, []);
      }
      $form_state->set(['conditions', $condition_id], $condition);
      $form[$condition_id] = $this->conditionFieldset($condition, $form_state);
    }
/*
    // Add comment to first condition tab.
    // @todo This would apply if all insertion conditions were converted to
    // condition plugins.
    $description = $this->t('On this and the following tabs, specify the conditions on which the GTM JavaScript snippet will either be inserted on or omitted from the page response, thereby enabling or disabling tracking and other analytics. All conditions must be satisfied for the snippet to be inserted. The snippet will be omitted if any condition is not met.');
    $condition_id = current(array_keys($definitions));
    $form[$condition_id]['#description'] = $description;
*/
    return $form;
  }

  /**
   * Returns the form elements from the condition plugin object.
   *
   * @param \Drupal\Core\Condition\ConditionInterface $condition
   *   The condition plugin.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form array for the insertion condition.
   */
  public function conditionFieldset(ConditionInterface $condition, FormStateInterface $form_state) {
    // Build form elements.
    $fieldset = [
      '#type' => 'details',
      '#title' => $condition->getPluginDefinition()['label'],
      '#group' => 'conditions',
      '#tree' => TRUE,
    ] + $condition->buildConfigurationForm([], $form_state);

    return $fieldset;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $this->validateFormValues($form, $form_state);
    parent::validateForm($form, $form_state);
    $this->validateConditionsForm($form, $form_state);
  }

  /**
   * Form validation handler for the insertion conditions.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function validateConditionsForm(array $form, FormStateInterface $form_state) {
    // Validate the insertion condition settings.
    $condition_ids = $form_state->getTemporaryValue('filtered_conditions');
    foreach ($condition_ids as $condition_id) {
      // Allow the condition to validate the form.
      $condition = $form_state->get(['conditions', $condition_id]);
      $condition->validateConfigurationForm($form[$condition_id], SubformState::createForSubform($form[$condition_id], $form, $form_state));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->submitConditionsForm($form, $form_state);
  }

  /**
   * Form submission handler for the insertion conditions.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function submitConditionsForm(array $form, FormStateInterface $form_state) {
    $condition_ids = $form_state->getTemporaryValue('filtered_conditions');
    foreach ($condition_ids as $condition_id) {
      $values = $form_state->getValue($condition_id);
      // Allow the condition to submit the form.
      $condition = $form_state->get(['conditions', $condition_id]);
      $condition->submitConfigurationForm($form[$condition_id], SubformState::createForSubform($form[$condition_id], $form, $form_state));
      $configuration = $condition->getConfiguration();
      // Update the insertion conditions on the container.
      $this->entity->setInsertionCondition($condition_id, $configuration);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // Drupal/Core/Condition/ConditionPluginCollection.php
    // On save, above class filters any condition with default configuration.
    // See ::getConfiguration()
    // The database row omits such conditions from the container 'conditions'.
    // google_tag/src/ContainerAccessControlHandler.php
    // On access check, the list of conditions only includes those in database.
    // Those with default configuration are assumed not to apply as the default
    // values should produce no restriction.
    // However, core treats an empty values list opposite this module.
    parent::save($form, $form_state);

    // @todo This could be done in container::postSave() method.
    global $_google_tag_display_message;
    $_google_tag_display_message = TRUE;
    $manager = \Drupal::service('google_tag.container_manager');
    $manager->createAssets($this->entity);

    // Redirect to collection page.
    $form_state->setRedirect('entity.google_tag_container.collection');
  }

  /**
   * Checks if a container machine name is taken.
   *
   * @param string $value
   *   The machine name.
   * @param array $element
   *   An array containing the structure of the 'id' element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return bool
   *   Whether or not the container machine name is taken.
   */
  public function containerExists($value, array $element, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $container */
    $container = $form_state->getFormObject()->getEntity();
    return (bool) $this->entityTypeManager->getStorage($container->getEntityTypeId())
      ->getQuery()
      ->condition($container->getEntityType()->getKey('id'), $value)
      ->execute();
  }

}
