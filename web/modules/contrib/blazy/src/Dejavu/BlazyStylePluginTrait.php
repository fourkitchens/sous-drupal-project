<?php

namespace Drupal\blazy\Dejavu;

use Drupal\Component\Utility\Xss;
use Drupal\blazy\Blazy;

/**
 * A Trait common for optional views style plugins.
 */
trait BlazyStylePluginTrait {

  /**
   * Returns the modified renderable image_formatter to support lazyload.
   */
  public function getImageRenderable(array &$settings, $row, $index) {
    $image = $this->isImageRenderable($row, $index, $settings['image']);

    /** @var Drupal\image\Plugin\Field\FieldType\ImageItem $item */
    if (empty($image['raw'])) {
      return $image;
    }

    // If the image has #item property, lazyload may work, otherwise skip.
    // This hustle is to lazyload tons of images -- grids, large galleries,
    // gridstack, mason, with multimedia/ lightboxes for free.
    if ($item = $this->getImageItem($image)) {
      // Supports multiple image styles within a single view such as GridStack,
      // else fallbacks to the defined image style if available.
      if (empty($settings['image_style'])) {
        $image_style = isset($image['rendered']['#image_style']) ? $image['rendered']['#image_style'] : '';
        $settings['image_style'] = empty($settings['image_style']) ? $image_style : $settings['image_style'];
      }

      // Converts image formatter for blazy to reduce complexity with CSS
      // background option, and other options, and still lazyload it.
      $theme = isset($image['rendered']['#theme']) ? $image['rendered']['#theme'] : '';
      if (in_array($theme, ['blazy', 'image_formatter'])) {
        $settings['uri'] = Blazy::uri($item);
        $settings['cache_tags'] = isset($image['rendered']['#cache']['tags']) ? $image['rendered']['#cache']['tags'] : [];

        if ($theme == 'blazy') {
          // Pass Blazy field formatter settings into Views style plugin.
          // This allows richer contents such as multimedia/ lightbox for free.
          // Yet, ensures the Views style plugin wins over Blazy formatter,
          // such as with GridStack which may have its own breakpoints.
          $item_settings = array_filter($image['rendered']['#build']['settings']);
          $settings = array_merge($item_settings, array_filter($settings));
        }
        elseif ($theme == 'image_formatter') {
          // Deals with "link to content/image" by formatters.
          $settings['content_url'] = isset($image['rendered']['#url']) ? $image['rendered']['#url'] : '';
          // Prevent images from having absurd height when being lazyloaded.
          // Allows to disables it by _noratio such as enforced CSS background.
          $settings['ratio'] = empty($settings['_noratio']) ? 'fluid' : '';
          if (empty($settings['media_switch']) && !empty($settings['content_url'])) {
            $settings['media_switch'] = 'content';
          }
        }

        // Rebuilds the image for the brand new richer Blazy.
        // With the working Views cache, nothing to worry much.
        $build = ['item' => $item, 'settings' => $settings];
        $image['rendered'] = $this->blazyManager->getBlazy($build);
      }
    }

    return $image;
  }

  /**
   * Checks if we can work with this formatter, otherwise no go if flattened.
   */
  public function isImageRenderable($row, $index, $field_image = '') {
    if (!empty($field_image) && $image = $this->getFieldRenderable($row, $index, $field_image)) {
      if ($this->getImageItem($image)) {
        return $image;
      }

      // Dump Video embed thumbnail/video/colorbox as is.
      if (isset($image['rendered'])) {
        return $image;
      }
    }
    return [];
  }

  /**
   * Get the image item to work with out of this formatter.
   *
   * All this mess is because Views may render/flatten images earlier.
   */
  public function getImageItem($image) {
    $item = [];

    // Image formatter.
    if (isset($image['raw'])) {
      $item = empty($image['rendered']['#item']) ? [] : $image['rendered']['#item'];

      // Blazy formatter.
      if (isset($image['rendered']['#build'])) {
        $item = $image['rendered']['#build']['item'];
      }
    }

    // Don't know other reasonable formatters to work with.
    if (!is_object($item)) {
      return [];
    }
    return $item;
  }

  /**
   * Returns the rendered caption fields.
   */
  public function getCaption($index, $settings = []) {
    $items = [];
    $keys = array_keys($this->view->field);

    if (!empty($settings['caption'])) {
      // Exclude non-caption fields so that theme_views_view_fields() kicks in
      // and only render expected caption fields. As long as not-hidden, each
      // caption field should be wrapped with Views markups.
      $excludes = array_diff_assoc(array_combine($keys, $keys), $settings['caption']);
      foreach ($excludes as $field) {
        $this->view->field[$field]->options['exclude'] = TRUE;
      }

      $items['data'] = $this->view->rowPlugin->render($this->view->result[$index]);
    }

    $items['link']    = empty($settings['link']) ? [] : $this->getFieldRendered($index, $settings['link']);
    $items['title']   = empty($settings['title']) ? [] : $this->getFieldRendered($index, $settings['title'], TRUE);
    $items['overlay'] = empty($settings['overlay']) ? [] : $this->getFieldRendered($index, $settings['overlay']);

    return $items;
  }

  /**
   * Returns the rendered layout fields.
   */
  public function getLayout(array &$settings, $index) {
    if (strpos($settings['layout'], 'field_') !== FALSE) {
      $settings['layout'] = strip_tags($this->getField($index, $settings['layout']));
    }
  }

  /**
   * Returns the rendered field, either string or array.
   */
  public function getFieldRendered($index, $field_name = '', $restricted = FALSE) {
    if (!empty($field_name) && $output = $this->getField($index, $field_name)) {
      return is_array($output) ? $output : ['#markup' => ($restricted ? Xss::filterAdmin($output) : $output)];
    }
    return [];
  }

}
