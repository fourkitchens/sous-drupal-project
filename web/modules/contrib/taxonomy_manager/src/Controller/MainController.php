<?php

namespace Drupal\taxonomy_manager\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Controller routines for taxonomy_manager routes.
 */
class MainController extends ControllerBase {

  /**
   * List of vocabularies, which link to Taxonomy Manager interface.
   *
   * @return array
   *   A render array representing the page.
   */
  public function listVocabularies() {
    $links = [];

    if ($this->currentUser()->hasPermission('administer taxonomy')) {
      $new_voc_url = Url::fromRoute('entity.taxonomy_vocabulary.add_form');
      $links[] = Link::fromTextAndUrl(
        $this->t('Add new vocabulary'),
        $new_voc_url
      )->toString();
    }

    if ($this->currentUser()->hasPermission('access taxonomy overview')) {
      $edit_voc_url = Url::fromRoute('entity.taxonomy_vocabulary.collection');
      $links[] = Link::fromTextAndUrl(
        $this->t('Edit vocabulary settings'),
        $edit_voc_url
      )->toString();
    }

    $build = [
      '#markup' => implode(" | ", $links),
    ];

    $voc_list = [];
    $vocabularies = $this->entityTypeManager()->getStorage('taxonomy_vocabulary')->loadMultiple();
    foreach ($vocabularies as $vocabulary) {
      if ($this->entityTypeManager()->getAccessControlHandler('taxonomy_term')->createAccess($vocabulary->id())) {
        $vocabulary_form = Url::fromRoute('taxonomy_manager.admin_vocabulary',
          ['taxonomy_vocabulary' => $vocabulary->id()]);
        $voc_list[] = Link::fromTextAndUrl($vocabulary->label(), $vocabulary_form);
      }
    }
    if (!count($voc_list)) {
      $voc_list[] = ['#markup' => $this->t('No Vocabularies available')];
    }

    $build['vocabularies'] = [
      '#theme' => 'item_list',
      '#items' => $voc_list,
      '#title' => $this->t('Vocabularies'),
    ];
    return $build;
  }

}
