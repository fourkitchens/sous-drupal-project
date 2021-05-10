<?php

namespace Drupal\sous;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\State\StateInterface;
use Drupal\media\Entity\Media;
use Symfony\Component\DependencyInjection\ContainerInterface;

class InstallHelper implements ContainerInjectionInterface {

  /**
   * Content types to build from CSV with the same name.
   */
  const DEFAULT_CONTENT_TYPES = ['frontpage', 'page'];

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * State.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a new InstallHelper object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\State\StateInterface $state
   *   State service.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, StateInterface $state, FileSystemInterface $fileSystem) {
    $this->entityTypeManager = $entityTypeManager;
    $this->state = $state;
    $this->fileSystem = $fileSystem;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('state'),
      $container->get('file_system')
    );
  }

  /**
   * Import some useful default content.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function importContent() {

    $profile_path = drupal_get_path('profile', 'sous');

    // Get information from the CSVs
    foreach (self::DEFAULT_CONTENT_TYPES as $content_type) {
      $keyed_content = [];
      if (file_exists($profile_path . "/default_content/$content_type.csv") &&
        ($handle = fopen($profile_path . "/default_content/$content_type.csv", 'r')) !== FALSE) {
        $header = fgetcsv($handle);
        $line_counter = 0;
        while (($content = fgetcsv($handle)) !== FALSE) {
          $keyed_content[$line_counter] = array_combine($header, $content);
          $line_counter++;
        }
        fclose($handle);

        // We can loop through here for now because the fields are identical.
        // We won't be able to do that if we choose to have other defaults.
        foreach($keyed_content as $content) {

          // Setting up the default structure.
          $values = [
            'type' => $content_type,
            'title' => $content['title'],
            'field_seo_title' => $content['seo_title'],
            'field_teaser_text' => $content['teaser_text'],
          ];

          // Setting up the media entity.
          if (!empty($content['teaser_media'])) {
            $media_entity = Media::create([
              'bundle' => 'image',
              'name' => $content['title'] . '-image',
              'uid' => 1,
              'status' => 1,
            ]);
            $media_entity->field_media_image->generateSampleItems();
            $media_entity->save();
            $values['field_teaser_media'] = [
              'target_id' => $media_entity->id(),
            ];
          }

          // Save Entity.
          $entity = $this->entityTypeManager->getStorage('node')->create($values);
          $entity->save();
        }
      }
    }
  }
}
