<?php

namespace Drupal\components\Template;

use Twig\Error\RuntimeError;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * A class providing components' Twig extensions.
 */
class TwigExtension extends AbstractExtension {

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      new TwigFunction('template', [$this, 'template'], ['is_variadic' => TRUE]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return [
      'recursive_merge' => new TwigFilter('recursive_merge', [
        'Drupal\components\Template\TwigExtension', 'recursiveMergeFilter',
      ]),
      'set' => new TwigFilter('set', [
        'Drupal\components\Template\TwigExtension', 'setFilter',
      ]),
      'add' => new TwigFilter('add', [
        'Drupal\components\Template\TwigExtension', 'addFilter',
      ]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'components';
  }

  /**
   * Includes the given template name or theme hook by returning a render array.
   *
   * Instead of calling the "include" function with a specific Twig template,
   * the "template" function will include the same Twig template, but after
   * running Drupal's normal preprocess and theme suggestion functions.
   *
   * Variables that you want to pass to the template should be given to the
   * template function using named arguments. For example:
   *
   * @code
   * {% set list = template(
   *     "item-list.html.twig",
   *     title = "Animals not yet in Drupal core",
   *     items = ["lemur", "weasel", "honey badger"],
   *   )
   * %}
   * @endcode
   *
   * Note that template() returns a render array. This means you can filter it
   * with Twig filters that expect arrays, e.g. `template(...)|merge(...)`. If
   * you want to use a filter that expects strings, you can use Drupal's render
   * filter first, e.g. `template(...)|render|stringFilter(...)`.
   *
   * Instead of the template name, you can pass a theme hook name or theme
   * suggestion to the first argument:
   * @code
   * {% set list = template(
   *     "item_list__node",
   *     title = "Fictional animals not yet in Drupal core",
   *     items = ["domo", "ponycorn"],
   *   )
   * %}
   * @endcode
   *
   * @param string|array $_name
   *   The template name or theme hook to render. Optionally, an array of theme
   *   suggestions can be given.
   * @param array $variables
   *   The variables to pass to the template.
   *
   * @return array
   *   The render array for the given theme hook.
   *
   * @throws \Exception
   *   When template name is prefixed with a Twig namespace, e.g. "@classy/".
   */
  public function template($_name, array $variables = []) {
    if ($_name[0] === '@') {
      throw new \Exception('Templates with namespaces are not supported; "' . $_name . '" given.');
    }
    if (is_array($_name)) {
      $hook = $_name;
    }
    else {
      $hook = str_replace('.html.twig', '', strtr($_name, '-', '_'));
    }
    $render_array = ['#theme' => $hook];
    foreach ($variables as $key => $variable) {
      $render_array['#' . $key] = $variable;
    }

    return $render_array;
  }

  /**
   * Recursively merges an array into the element, replacing existing values.
   *
   * @code
   * {{ form|recursive_merge( {'element': {'attributes': {'placeholder': 'Label'}}} ) }}
   * @endcode
   *
   * @param array|iterable|\Traversable $element
   *   The parent renderable array to merge into.
   * @param iterable|array $array
   *   The array to merge.
   *
   * @return array
   *   The merged renderable array.
   *
   * @throws \Twig\Error\RuntimeError
   *   When $element is not an array or "Traversable".
   */
  public static function recursiveMergeFilter($element, $array) {
    if (!twig_test_iterable($element)) {
      throw new RuntimeError(sprintf('The recursive_merge filter only works on arrays or "Traversable" objects, got "%s".', gettype($element)));
    }

    return array_replace_recursive($element, $array);
  }

  /**
   * Sets a deeply-nested property on an array.
   *
   * If the deeply-nested property exists, the existing data will be replaced
   * with the new value.
   *
   * @code
   * {{ form|set( 'element.#attributes.placeholder', 'Label' ) }}
   * @endcode
   *
   * @param array|iterable|\Traversable $element
   *   The parent renderable array to set into.
   * @param string|iterable|array $at
   *   The dotted-path to the deeply nested element to set. (Or an array value
   *   to merge, if using the backwards-compatible 2.x syntax.)
   * @param mixed $value
   *   The value to set.
   * @param string $path
   *   The deprecated named argument that has been replaced with "at".
   * @param iterable|array $array
   *   The deprecated named argument for the backwards-compatible 2.x syntax.
   *
   * @return array
   *   The merged renderable array.
   *
   * @throws \Twig\Error\RuntimeError
   *   When $element is not an array or "Traversable".
   */
  public static function setFilter($element, $at = NULL, $value = NULL, $path = NULL, $array = NULL) {
    if (!twig_test_iterable($element)) {
      throw new RuntimeError(sprintf('The set filter only works on arrays or "Traversable" objects, got "%s".', gettype($element)));
    }

    // Backwards-compatibility with older 8.x-2.x versions of set filter.
    if (!is_null($array)) {
      $at = $array;
    }
    if (is_null($path) && is_null($at)) {
      throw new RuntimeError('Value for argument "at" is required for filter "set".');
    }
    if (!is_null($path)) {
      @trigger_error('The "set" filter’s named "path" argument is deprecated in components:8.x-2.4 and will be removed in components:3.0.0. The named argument has been renamed from "path" to "at". See https://www.drupal.org/project/components/issues/3209575', E_USER_DEPRECATED);
      $at = $path;
    }
    if (!is_string($at)) {
      @trigger_error('Calling the "set" filter with an array is deprecated in components:8.x-2.3 and will be removed in components:3.0.0. Update to the new syntax or use the "recursive_merge" filter instead. See https://www.drupal.org/project/components/issues/3209440', E_USER_DEPRECATED);
      return self::recursiveMergeFilter($element, $at);
    }

    return self::addOrSetFilter($element, $at, $value, FALSE);
  }

  /**
   * Adds a deeply-nested property on an array.
   *
   * If the deeply-nested property exists, the existing data will be replaced
   * with the new value, unless the existing data is an array. In which case,
   * the new value will be merged into the existing array.
   *
   * @code
   * {{ form|add( 'element.#attributes.class', 'new-class' ) }}
   * @endcode
   *
   * Or using named arguments:
   * @code
   * {{ form|add( to='element.#attributes.class', value='new-class' ) }}
   * {# We accept the plural form of "values" as a grammatical convenience. #}
   * {{ form|add( to='element.#attributes.class', values=['new-class', 'new-class-2'] ) }}
   * @endcode
   *
   * @param array|iterable|\Traversable $element
   *   The parent renderable array to merge into.
   * @param string $at
   *   The dotted-path to the deeply nested element to modify.
   * @param mixed $value
   *   The value to add.
   * @param mixed $values
   *   The values to add. If this named argument is used, the "value" argument
   *   is ignored.
   * @param string $path
   *   The deprecated named argument that has been replaced with "at".
   *
   * @return array
   *   The merged renderable array.
   *
   * @throws \Twig\Error\RuntimeError
   *   When $element is not an array or "Traversable".
   */
  public static function addFilter($element, string $at = NULL, $value = NULL, $values = NULL, $path = NULL) {
    if (!twig_test_iterable($element)) {
      throw new RuntimeError(sprintf('The add filter only works on arrays or "Traversable" objects, got "%s".', gettype($element)));
    }

    // Backwards-compatibility with older 8.x-2.x versions of add filter.
    if (is_null($path) && is_null($at)) {
      throw new RuntimeError('Value for argument "at" is required for filter "add".');
    }
    if (!is_null($path)) {
      @trigger_error('The "add" filter’s named "path" argument is deprecated in components:8.x-2.4 and will be removed in components:3.0.0. The named argument has been renamed from "path" to "at". See https://www.drupal.org/project/components/issues/3209575', E_USER_DEPRECATED);
      $at = $path;
    }

    return self::addOrSetFilter($element, $at, !is_null($values) ? $values : $value, TRUE);
  }

  /**
   * Helper function for the set/add filters.
   *
   * @param array|iterable|\Traversable $element
   *   The parent renderable array to merge into.
   * @param string $at
   *   The dotted-path to the deeply nested element to replace.
   * @param mixed $value
   *   The value to set.
   * @param bool $is_add_filter
   *   Which filter is being called.
   *
   * @return array
   *   The merged renderable array.
   */
  protected static function addOrSetFilter($element, string $at, $value, $is_add_filter = FALSE) {
    if ($element instanceof \ArrayAccess) {
      $filtered_element = clone $element;
    }
    else {
      $filtered_element = $element;
    }

    // Convert the dotted path into an array of keys.
    $path = explode('.', $at);
    $last_path = array_pop($path);

    // Traverse the element down the path, creating arrays as needed.
    $child_element =& $filtered_element;
    foreach ($path as $child_path) {
      if (!isset($child_element[$child_path])) {
        $child_element[$child_path] = [];
      }
      $child_element =& $child_element[$child_path];
    }

    // If this is the add() filter and if the targeted child element is an
    // array, add the value to it.
    if ($is_add_filter && isset($child_element[$last_path]) && is_array($child_element[$last_path])) {
      if (is_array($value)) {
        $child_element[$last_path] = array_merge($child_element[$last_path], $value);
      }
      else {
        $child_element[$last_path][] = $value;
      }
    }
    else {
      // Otherwise, replace the target element with the given value.
      $child_element[$last_path] = $value;
    }

    return $filtered_element;
  }

}
