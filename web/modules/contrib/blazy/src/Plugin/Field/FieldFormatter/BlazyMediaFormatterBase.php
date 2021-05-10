<?php

namespace Drupal\blazy\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\blazy\BlazyDefault;
use Drupal\blazy\Dejavu\BlazyEntityMediaBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for blazy/slick media ER formatters.
 *
 * @see \Drupal\blazy\Plugin\Field\FieldFormatter\BlazyMediaFormatter
 * @see \Drupal\gridstack\Plugin\Field\FieldFormatter\GridStackMediaFormatter
 */
abstract class BlazyMediaFormatterBase extends BlazyEntityMediaBase {

  use BlazyFormatterTrait;
  use BlazyFormatterViewTrait;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    return self::injectServices($instance, $container, 'entity');
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return BlazyDefault::extendedSettings() + BlazyDefault::gridSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return $field_definition->getFieldStorageDefinition()->getSetting('target_type') == 'media';
  }

}
