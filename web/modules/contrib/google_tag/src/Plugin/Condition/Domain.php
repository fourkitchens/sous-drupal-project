<?php

namespace Drupal\google_tag\Plugin\Condition;

use Drupal\google_tag\ConditionBase;
use Drupal\domain\DomainNegotiator;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Domain' condition.
 *
 * @Condition(
 *   id = "gtag_domain",
 *   label = @Translation("Domain"),
 *   context_definitions = {
 *     "entity:domain" = @ContextDefinition("entity:domain", label = @Translation("Domain"), required = TRUE)
 *   }
 * )
 */
class Domain extends ConditionBase implements ContainerFactoryPluginInterface {

  /**
   * The domain negotiator.
   *
   * @var \Drupal\domain\DomainNegotiator
   */
  protected $domainNegotiator;

  /**
   * Constructs a domain condition plugin.
   *
   * @param \Drupal\domain\DomainNegotiator $domain_negotiator
   *   The domain negotiator service.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage_manager
   *   The entity storage manager.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(DomainNegotiator $domain_negotiator, EntityStorageInterface $storage_manager, array $configuration, $plugin_id, $plugin_definition) {
    $this->toggle = 'domain_toggle';
    $this->list = 'domain_list';
    $this->singular = 'domain';
    $this->plural = 'domains';
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->domainNegotiator = $domain_negotiator;
    $this->options = array_map('\Drupal\Component\Utility\Html::escape', $storage_manager->loadOptionsList());
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('domain.negotiator'),
      $container->get('entity_type.manager')->getStorage('domain'),
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
    if (isset($form['context_mapping']['entity:domain']['#title'])) {
      $form['context_mapping']['entity:domain']['#title'] = $this->t('Select the Domain context');
      $form['context_mapping']['entity:domain']['#description'] = $this->t('This value must be set to "Active domain" for the context to work.');
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    $this->values = array_intersect_key($this->options, $this->configuration['domain_list']);
    return parent::summary();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();
    $contexts[] = 'url.site';
    return $contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function contextToEvaluate() {
    $domain = $this->getContextValue('entity:domain');
    // @todo Is this checking necessary? Does it reflect brittleness by domain?
    if (!$domain) {
      // The context did not load; try to derive it from the request.
      $domain = $this->domainNegotiator->getActiveDomain();
    }
    if (empty($domain)) {
      return FALSE;
    }
    return $domain->id();
  }

}
