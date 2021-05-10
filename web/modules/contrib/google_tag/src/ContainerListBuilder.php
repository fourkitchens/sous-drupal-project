<?php

namespace Drupal\google_tag;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines a listing of container configuration entities.
 *
 * @see \Drupal\google_tag\Entity\Container
 */
class ContainerListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = t('Label');
    $header['id'] = t('Machine name');
    $header['container_id'] = t('Container ID');
    $header['weight'] = t('Weight');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    // @todo Add JS for drag handle on weight.
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['container_id'] = $entity->get('container_id');
    $row['weight'] = $entity->get('weight');
    return $row + parent::buildRow($entity);
  }

}
