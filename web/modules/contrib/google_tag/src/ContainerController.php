<?php

namespace Drupal\google_tag;

use Drupal\Core\Entity\Controller\EntityController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\RouteMatchInterface;

use Drupal\google_tag\Entity\Container;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller for the container configuration entity type.
 */
class ContainerController extends EntityController {

  /**
   * Route title callback.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return string
   *   The title for the add entity page.
   */
  public function addTitle($entity_type_id) {
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    return $this->t('Add @entity-type', ['@entity-type' => $entity_type->getSingularLabel()]);
  }

  /**
   * Route title callback.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Entity\EntityInterface $_entity
   *   (optional) An entity, passed in directly from the request attributes.
   *
   * @return string|null
   *   The title for the entity edit page, if an entity was found.
   */
  public function editTitle(RouteMatchInterface $route_match, EntityInterface $_entity = NULL) {
    if ($entity = $this->doGetEntity($route_match, $_entity)) {
      return $this->t('Edit %label container', ['%label' => $entity->label()]);
    }
  }

  /**
   * Enables a Container object.
   *
   * @param \Drupal\google_tag\Entity\Container $google_tag_container
   *   The Container object to enable.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response to the google_tag_container listing page.
   *
   * @todo The parameter name must match that used in routing.yml although the
   *   documentation suggests otherwise.
   */
  public function enable(Container $google_tag_container) {
    $google_tag_container->enable()->save();
    return new RedirectResponse($google_tag_container->toUrl('collection', ['absolute' => TRUE])->toString());
  }

  /**
   * Disables a Container object.
   *
   * @param \Drupal\google_tag\Entity\Container $google_tag_container
   *   The Container object to disable.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response to the google_tag_container listing page.
   */
  public function disable(Container $google_tag_container) {
    $google_tag_container->disable()->save();
    return new RedirectResponse($google_tag_container->toUrl('collection', ['absolute' => TRUE])->toString());
  }

}
