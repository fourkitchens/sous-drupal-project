<?php

namespace Drupal\blazy\Dejavu;

/**
 * A Trait common for file, image or media to handle dependencies.
 */
trait BlazyDependenciesTrait {

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    $style_ids = [];
    foreach (['box', 'box_media', 'image', 'thumbnail'] as $key) {
      if (!empty($this->getSetting($key . '_style'))) {
        $style_ids[] = $this->getSetting($key . '_style');
      }
    }

    /** @var \Drupal\image\ImageStyleInterface $style */
    foreach ($style_ids as $style_id) {
      if ($style_id && $style = $this->formatter->entityLoad($style_id, 'image_style')) {
        // If this formatter uses a valid image style to display the image, add
        // the image style configuration entity as dependency of this formatter.
        $dependencies[$style->getConfigDependencyKey()][] = $style->getConfigDependencyName();
      }
    }

    $style_id = $this->getSetting('responsive_image_style');
    /** @var \Drupal\responsive_image\ResponsiveImageStyleInterface $style */
    if ($style_id && $style = $this->formatter->entityLoad($style_id, 'responsive_image_style')) {
      // Add the responsive image style as dependency.
      $dependencies[$style->getConfigDependencyKey()][] = $style->getConfigDependencyName();
    }
    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    $changed = parent::onDependencyRemoval($dependencies);
    $style_ids = [];
    foreach (['box', 'box_media', 'image', 'thumbnail'] as $key) {
      if (!empty($this->getSetting($key . '_style'))) {
        $style_ids[] = $this->getSetting($key . '_style');
      }
    }

    /** @var \Drupal\image\ImageStyleInterface $style */
    foreach ($style_ids as $name => $style_id) {
      if ($style_id && $style = $this->formatter->entityLoad($style_id, 'image_style')) {
        if (!empty($dependencies[$style->getConfigDependencyKey()][$style->getConfigDependencyName()])) {
          $replacement_id = $this->formatter->getEntityTypeManager()->getStorage('image_style')->getReplacementId($style_id);
          // If a valid replacement has been provided in the storage, replace
          // the image style with the replacement and signal that the formatter
          // plugin settings were updated.
          if ($replacement_id && $this->formatter->entityLoad($replacement_id, 'image_style')) {
            $this->setSetting($name, $replacement_id);
            $changed = TRUE;
          }
        }
      }
    }
    return $changed;
  }

}
