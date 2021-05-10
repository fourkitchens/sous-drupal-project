<?php

namespace Drupal\blazy;

/**
 * Provides settings object.
 *
 * @todo convert settings into BlazySettings instance at blazy:8.3 if you can.
 */
class BlazySettings implements \Countable {

  /**
   * Stores the settings.
   *
   * @var \stdClass[]
   */
  protected $storage = [];

  /**
   * Creates a new BlazySettings instance.
   *
   * @param \stdClass[] $storage
   *   The storage.
   */
  public function __construct(array $storage) {
    $this->storage = $storage;
  }

  /**
   * Counts total items.
   */
  public function count() {
    return count($this->storage);
  }

  /**
   * Gets values from a key.
   */
  public function get($id) {
    return isset($this->storage[$id]) ? $this->storage[$id] : NULL;
  }

  /**
   * Sets values for a key.
   */
  public function set($key, $value = NULL) {
    if (is_array($key) && empty($value)) {
      foreach ($key as $k => $v) {
        $this->storage[$k] = $v;
      }
    }
    else {
      $this->storage[$key] = $value;
    }

    return $this;
  }

  /**
   * Returns the whole array.
   */
  public function storage() {
    return $this->storage;
  }

}
