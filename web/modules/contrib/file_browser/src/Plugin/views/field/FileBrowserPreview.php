<?php

namespace Drupal\file_browser\Plugin\views\field;

use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Defines a custom field that renders a preview of a file, for the purposes of.
 *
 * @ViewsField("file_browser_preview")
 */
class FileBrowserPreview extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    /** @var \Drupal\file\Entity\File $file */
    $file = $this->getEntity($values);
    $build = [];

    $build['image'] = $this->getFilePreview($file, 'file_entity_browser_thumbnail');

    $build['preview'] = [
      '#title' => $this->t('Preview'),
      '#type' => 'link',
      '#url' => Url::fromRoute('file_browser.preview', [
        'file' => $file->id(),
      ]),
      '#attributes' => [
        'class' => ['file-browser-preview-button', 'use-ajax'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => '{"classes": {"ui-dialog": "ui-corner-all file-browser-preview-dialog"}, "show": "fadeIn", "hide": "fadeOut"}',
      ],
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {}

  /**
   * {@inheritdoc}
   */
  public function clickSortable() {
    return FALSE;
  }

  /**
   * Renders a preview of an arbitrary file.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file you want to render a preview of.
   * @param string $image_style
   *   (Optional) An image style to render the preview in.
   *
   * @return array
   *   A render array representing the file preview.
   */
  public static function getFilePreview(FileInterface $file, $image_style = '') {
    // Check if this file is an image.
    $image_factory = \Drupal::service('image.factory');

    // Loading large files is slow, make sure it is an image mime type before
    // doing that.
    list($type,) = explode('/', $file->getMimeType(), 2);
    if ($type == 'image' && ($image = $image_factory->get($file->getFileUri())) && $image->isValid()) {
      // Fake an ImageItem object.
      $item = new \stdClass();
      $item->width = $image->getWidth();
      $item->height = $image->getHeight();
      $item->alt = '';
      $item->title = $file->getFilename();
      $item->entity = $file;

      $build = [
        '#theme' => 'image_formatter',
        '#item' => $item,
        '#image_style' => $image_style,
      ];
    }
    // Use a placeholder image for now.
    // @todo See if we can use fallback formatters for this.
    else {
      $path = drupal_get_path('module', 'file_browser');
      $build = [
        '#theme' => 'image',
        '#attributes' => [
          'src' => base_path() . $path . '/images/document_placeholder.svg',
        ],
      ];
    }
    return $build;
  }

}
