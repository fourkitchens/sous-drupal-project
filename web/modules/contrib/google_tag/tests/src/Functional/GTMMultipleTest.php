<?php

namespace Drupal\Tests\google_tag\Functional;

/**
 * Tests the Google Tag Manager for a site with multiple containers.
 *
 * @group GoogleTag
 */
class GTMMultipleTest extends GTMTestBase {

  /**
   * {@inheritdoc}
   */
  protected function createData() {
    parent::createData();

    $this->variables['default'] = (object) [
      'id' => 'default',
      'label' => 'Default',
      'weight' => 3,
      'container_id' => 'GTM-default',
      'include_environment' => '1',
      'environment_id' => 'env-7',
      'environment_token' => 'ddddddddddddddddddddd',
    ];

    $this->variables['primary'] = (object) [
      'id' => 'primary',
      'label' => 'Primary',
      'weight' => 2,
      'container_id' => 'GTM-primary',
      'include_environment' => '1',
      'environment_id' => 'env-1',
      'environment_token' => 'ppppppppppppppppppppp',
    ];

    $this->variables['secondary'] = (object) [
      'id' => 'secondary',
      'label' => 'Secondary',
      'weight' => 1,
      'container_id' => 'GTM-secondary',
      'include_environment' => '1',
      'environment_id' => 'env-2',
      'environment_token' => 'sssssssssssssssssssss',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function checkSnippetFiles() {
    foreach ($this->variables as $key => $variables) {
      $message = "Start on container $key";
      parent::assertTrue(TRUE, $message);
      foreach ($this->types as $type) {
        $url = "$this->basePath/google_tag/{$key}/google_tag.$type.js";
        $contents = @file_get_contents($url);
        $function = "verify{$type}Snippet";
        $this->$function($contents, $this->variables[$key]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkPageResponse() {
    parent::checkPageResponse();

    foreach ($this->variables as $key => $variables) {
      $this->drupalGet('');
      $message = "Start on container $key";
      parent::assertTrue(TRUE, $message);
      foreach ($this->types as $type) {
        $uri = "$this->basePath/google_tag/{$key}/google_tag.$type.js";
        $url = file_url_transform_relative(file_create_url($uri));
        $function = "verify{$type}Tag";
        $this->$function($url, $this->variables[$key]);
      }
    }
  }

}
