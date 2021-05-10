<?php

namespace Drupal\Tests\blazy\Traits;

/**
 * A Trait common for Blazy tests.
 */
trait BlazyPropertiesTestTrait {

  /**
   * The blazy admin service.
   *
   * @var \Drupal\blazy\Form\BlazyAdminInterface
   */
  protected $blazyAdmin;

  /**
   * The blazy admin service.
   *
   * @var \Drupal\blazy\Form\BlazyAdminFormatter
   */
  protected $blazyAdminFormatter;

  /**
   * The blazy manager service.
   *
   * @var \Drupal\blazy\BlazyManagerInterface
   */
  protected $blazyManager;

  /**
   * The blazy entity service.
   *
   * @var \Drupal\blazy\BlazyEntity
   */
  protected $blazyEntity;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity display.
   *
   * @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface
   */
  protected $display;

  /**
   * The node entity.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * The entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * The entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entities;

  /**
   * The node entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $referencingEntity;

  /**
   * The referenced node entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $referencedEntity;

  /**
   * The bundle name.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The target bundle names.
   *
   * @var array
   */
  protected $targetBundles;

  /**
   * The tested entity type.
   *
   * @var string
   */
  protected $entityType;

  /**
   * The created item.
   *
   * @var \Drupal\image\Plugin\Field\FieldType\ImageItem
   */
  protected $testItem;

  /**
   * The created image item.
   *
   * @var \Drupal\image\Plugin\Field\FieldType\ImageItem
   */
  protected $image;

  /**
   * The created items.
   *
   * @var array
   */
  protected $testItems = [];

  /**
   * The formatter definition.
   *
   * @var array
   */
  protected $formatterDefinition = [];

  /**
   * The formatter plugin manager.
   *
   * @var \Drupal\Core\Field\FormatterPluginManager
   */
  protected $formatterPluginManager;

  /**
   * The tested type definitions.
   *
   * @var array
   */
  protected $typeDefinition = [];

  /**
   * The tested field name.
   *
   * @var string
   */
  protected $testFieldName;

  /**
   * The tested field type.
   *
   * @var string
   */
  protected $testFieldType;

  /**
   * The tested empty field name.
   *
   * @var string
   */
  protected $testEmptyName;

  /**
   * The tested empty field type.
   *
   * @var string
   */
  protected $testEmptyType;

  /**
   * The tested formatter ID.
   *
   * @var string
   */
  protected $testPluginId;

  /**
   * The tested entity reference formatter ID.
   *
   * @var string
   */
  protected $entityPluginId;

  /**
   * The maximum number of created paragraphs.
   *
   * @var int
   */
  protected $maxParagraphs = 1;

  /**
   * The maximum number of created images.
   *
   * @var int
   */
  protected $maxItems = 1;

  /**
   * The tested skins.
   *
   * @var array
   */
  protected $skins = [];

  /**
   * The filter format.
   *
   * @var \Drupal\filter\Entity\FilterFormat
   */
  protected $filterFormatFull = NULL;

  /**
   * The filter format.
   *
   * @var \Drupal\filter\Entity\FilterFormat
   */
  protected $filterFormatRestricted = NULL;

}
