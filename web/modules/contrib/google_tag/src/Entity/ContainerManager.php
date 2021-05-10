<?php

namespace Drupal\google_tag\Entity;

// use Drupal\google_tag\Entity\ContainerManagerInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines the Google tag container manager.
 */
class ContainerManager implements ContainerManagerInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, FileSystemInterface $file_system, MessengerInterface $messenger, LoggerChannelFactoryInterface $logger_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
    $this->fileSystem = $file_system;
    $this->messenger = $messenger;
    $this->logger = $logger_factory->get('google_tag');
  }

  /**
   * {@inheritdoc}
   */
  public function createAssets(ConfigEntityInterface $container) {
    $result = TRUE;
    $directory = $container->snippetDirectory();
    if (!is_dir($directory) || !_google_tag_is_writable($directory) || !_google_tag_is_executable($directory)) {
      $result = _file_prepare_directory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
    }
    if ($result) {
      $result = $this->saveSnippets($container);
    }
    else {
      $args = ['%directory' => $directory];
      $message = 'The directory %directory could not be prepared for use, possibly due to file system permissions. The directory either does not exist, or is not writable or searchable.';
      $this->displayMessage($message, $args, MessengerInterface::TYPE_ERROR);
      $this->logger->error($message, $args);
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function saveSnippets(ConfigEntityInterface $container) {
    // Save the altered snippets after hook_google_tag_snippets_alter().
    $result = TRUE;
    $snippets = $container->snippets();
    foreach ($snippets as $type => $snippet) {
      $uri = $container->snippetURI($type);
      $path = $this->fileSystem->saveData($snippet, $uri, FileSystemInterface::EXISTS_REPLACE);
      $result = !$path ? FALSE : $result;
    }
    $args = ['@count' => count($snippets), '%container' => $container->get('label')];
    if (!$result) {
      $message = 'An error occurred saving @count snippet files for %container container. Contact the site administrator if this persists.';
      $this->displayMessage($message, $args, MessengerInterface::TYPE_ERROR);
      $this->logger->error($message, $args);
    }
    else {
      $message = 'Created @count snippet files for %container container based on configuration.';
      $this->displayMessage($message, $args);
      // Reset the URL query argument so browsers reload snippet files.
      _drupal_flush_css_js();
    }
    return $result;
  }

  /**
   * Displays a message to admin users.
   *
   * @param string $message
   *   The message to display.
   * @param array $args
   *   (optional) An associative array of replacements.
   * @param string $type
   *   (optional) The message type. Defaults to 'status'.
   */
  public function displayMessage($message, array $args = [], $type = MessengerInterface::TYPE_STATUS) {
    global $_google_tag_display_message;
    if ($_google_tag_display_message) {
      $this->messenger->addMessage($this->t($message, $args), $type, TRUE);
    }
  }

  /**
   * Returns container entity IDs.
   *
   * @return array
   *   The entity ID array.
   */
  public function loadContainerIDs() {
    return $this->entityTypeManager
      ->getStorage('google_tag_container')
      ->getQuery()
      ->condition('status', 1)
      ->sort('weight')
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getScriptAttachments(array &$attachments) {
    $ids = $this->loadContainerIDs();
    $containers = $this->entityTypeManager->getStorage('google_tag_container')->loadMultiple($ids);
    foreach ($containers as $container) {
      if (!$container->insertSnippet()) {
        continue;
      }

      if (!$this->findAssets($container)) {
        // Create snippet files (e.g. after cache rebuild).
        $this->createAssets($container);
      }

      static $weight = 9;
      $include_script_as_file = \Drupal::config('google_tag.settings')->get('include_file');
      $include_classes = $container->get('include_classes');
      // @todo Only want one data_layer snippet even with multiple containers.
      // If user sorts containers such that the first does not define the data
      // layer, then validate this or adjust for it here.
      // Sort the items being added and put the data_layer at top?
      $types = $include_classes ? ['data_layer', 'script'] : ['script'];

      // Add data_layer and script snippets to head (no longer by default).
      if ($include_script_as_file) {
        foreach ($types as $type) {
          // @todo Will it matter if file is empty?
          // @todo Check config for the whitelist and blacklist classes before adding.
          $attachments['#attached']['html_head'][] = $container->fileTag($type, $weight++);
        }
      }
      else {
        foreach ($types as $type) {
          // @see drupal_get_js() in 7.x core.
          // For inline JavaScript to validate as XHTML, all JavaScript containing
          // XHTML needs to be wrapped in CDATA.
          $attachments['#attached']['html_head'][] = $container->inlineTag($type, $weight++);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getNoScriptAttachments(array &$page) {
    $ids = $this->loadContainerIDs();
    $containers = $this->entityTypeManager->getStorage('google_tag_container')->loadMultiple($ids);
    foreach ($containers as $container) {
      if (!$container->insertSnippet()) {
        continue;
      }

      $page += $container->noscriptTag();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createAllAssets() {
    $ids = $this->loadContainerIDs();
    if (!$ids) {
      return TRUE;
    }
    // Create snippet files for enabled containers.
    $containers = $this->entityTypeManager->getStorage('google_tag_container')->loadMultiple($ids);
    $result = TRUE;
    foreach ($containers as $container) {
      $result = !$this->createAssets($container) ? FALSE : $result;
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAllAssets() {
    if (\Drupal::config('google_tag.settings')->get('flush_snippets')) {
      $directory = \Drupal::config('google_tag.settings')->get('uri');
      if (!empty($directory)) {
        // Remove any stale files (e.g. module update or machine name change).
        return $this->fileSystem->deleteRecursive($directory . '/google_tag');
      }
    }

    $ids = $this->loadContainerIDs();
    if (!$ids) {
      return TRUE;
    }
    // Delete snippet files for enabled containers.
    $containers = $this->entityTypeManager->getStorage('google_tag_container')->loadMultiple($ids);
    $result = TRUE;
    foreach ($containers as $container) {
      $result = !$this->deleteAssets($container) ? FALSE : $result;
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAssets(ConfigEntityInterface $container) {
    $include_classes = $container->get('include_classes');
    $types = $include_classes ? ['data_layer', 'script', 'noscript'] : ['script', 'noscript'];
    $directory = $container->snippetDirectory();
    $result = $this->fileSystem->deleteRecursive($directory);

    $args = ['@count' => count($types), '%container' => $container->get('label')];
    if (!$result) {
      $message = 'An error occurred deleting @count snippet files for %container container. Contact the site administrator if this persists.';
      $this->displayMessage($message, $args, MessengerInterface::TYPE_ERROR);
      $this->logger->error($message, $args);
    }
    else {
      $message = 'Deleted @count snippet files for %container container.';
      $this->displayMessage($message, $args);
      // In case this is not called during core cache rebuild, then [OMIT?]
      // Reset the URL query argument so browsers reload snippet files.
      // @todo Do these snippet files have the js token in a query argument? Yes. [OMIT]
      _drupal_flush_css_js();
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function findAssets(ConfigEntityInterface $container) {
    $include_classes = $container->get('include_classes');
    $types = $include_classes ? ['data_layer', 'script', 'noscript'] : ['script', 'noscript'];
    $result = TRUE;
    foreach ($types as $type) {
      $uri = $container->snippetURI($type);
      $result = !is_file($uri) ? FALSE : $result;
    }
    return $result;
  }

}
