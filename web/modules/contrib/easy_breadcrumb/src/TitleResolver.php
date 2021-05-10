<?php

namespace Drupal\easy_breadcrumb;

use Drupal\Core\Controller\TitleResolver as ControllerTitleResolver;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Component\Utility\Xss;

/**
 * Class TitleResolver.
 */
class TitleResolver extends ControllerTitleResolver {

  /**
   * The field storage config storage.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Breadcrumb config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Constructs a new EntityDisplayRebuilder.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->config = $config_factory->get(EasyBreadcrumbConstants::MODULE_SETTINGS);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle(Request $request, Route $route) {
    $url = Url::fromUri("internal:" . $request->getRequestUri());
    $route_parts = explode(".", $url->getRouteName());
    $entity = NULL;
    $params = $url->getRouteParameters();
    if ($route_parts[0] === 'entity' && $route_parts[2] === 'canonical') {
      $entity_type = $route_parts[1];
      $entity = $this->entityTypeManager->getStorage($entity_type)->load($params[$entity_type]);

    }
    if ($entity !== NULL) {
      $alternative_title_field = $this->config->get(EasyBreadcrumbConstants::ALTERNATIVE_TITLE_FIELD);
      if ($entity->hasField($alternative_title_field) && !$entity->get($alternative_title_field)
        ->isEmpty()) {
        return Xss::filter($entity->get($alternative_title_field)->value);
      }
    }
    return parent::getTitle($request, $route);
  }

}
