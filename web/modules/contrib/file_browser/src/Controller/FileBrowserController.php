<?php

namespace Drupal\file_browser\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Drupal\file_browser\Plugin\views\field\FileBrowserPreview;

/**
 * Endpoints for the File Browser module.
 */
class FileBrowserController extends ControllerBase {

  /**
   * Renders a preview of a file for use with File Browser.
   *
   * @param \Drupal\file\FileInterface $file
   *   The requested file.
   * @param string $image_style
   *   (Optional) An image style to preview the (image) file in.
   *
   * @return array
   *   A render array representing the preview.
   */
  public function preview(FileInterface $file, $image_style = '') {
    $build = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'file-browser-preview-wrapper',
      ],
      '#attached' => [
        'library' => [
          'file_browser/preview',
        ],
        'drupalSettings' => [
          'file_browser' => [
            'preview_path' => Url::fromRoute('file_browser.preview', [
              'file' => $file->id(),
            ])->toString(),
          ],
        ],
      ],
    ];

    $preview = FileBrowserPreview::getFilePreview($file, $image_style);

    if ($preview['#theme'] === 'image_formatter') {
      $build['image_style'] = [
        '#type' => 'select',
        '#options' => image_style_options(),
        '#value' => $image_style,
      ];
      $build['image_style']['#options'][''] = $this->t('No image style');
    }

    $build['preview'] = $preview;

    return $build;
  }

}
