<?php

namespace Drupal\google_tag\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;

/**
 * Provides an interface for a Google tag container manager.
 */
interface ContainerManagerInterface {

  /**
   * Constructs a ContainerManager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, FileSystemInterface $file_system, MessengerInterface $messenger, LoggerChannelFactoryInterface $logger_factory);

  /**
   * Prepares directory for and saves snippet files for a container.
   *
   * @todo Which class-interface to use on @param?
   *
   * @param Drupal\Core\Config\Entity\ConfigEntityInterface $container
   *   The container configuration entity.
   *
   * @return bool
   *   Whether the files were saved.
   */
  public function createAssets(ConfigEntityInterface $container);

  /**
   * Saves JS snippet files based on current settings.
   *
   * @param Drupal\Core\Config\Entity\ConfigEntityInterface $container
   *   The container configuration entity.
   *
   * @return bool
   *   Whether the files were saved.
   */
  public function saveSnippets(ConfigEntityInterface $container);

  /**
   * Adds render array items of page attachments.
   *
   * @param array $attachments
   *   The attachments render array.
   */
  public function getScriptAttachments(array &$attachments);

  /**
   * Adds render array items of page top attachments.
   *
   * @param array $page
   *   The page render array.
   */
  public function getNoScriptAttachments(array &$page);

  /**
   * Prepares directory for and saves snippet files for all containers.
   *
   * @return bool
   *   Whether the files were saved.
   */
  public function createAllAssets();

  /**
   * Deletes snippet files for all containers.
   *
   * @return bool
   *   Whether the files were deleted.
   */
  public function deleteAllAssets();

  /**
   * Deletes snippet files for a container.
   *
   * @param Drupal\Core\Config\Entity\ConfigEntityInterface $container
   *   The container configuration entity.
   *
   * @return bool
   *   Whether the files were deleted.
   */
  public function deleteAssets(ConfigEntityInterface $container);

  /**
   * Finds snippet files for a container.
   *
   * @param Drupal\Core\Config\Entity\ConfigEntityInterface $container
   *   The container configuration entity.
   *
   * @return bool
   *   Whether the files were found.
   */
  public function findAssets(ConfigEntityInterface $container);

}
