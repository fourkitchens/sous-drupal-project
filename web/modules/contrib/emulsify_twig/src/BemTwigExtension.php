<?php

namespace Drupal\emulsify_twig;
use Drupal\Core\Template\Attribute;

/**
 * Class DefaultService
 *
 * @package Drupal\EmulsifyExt
 */
class BemTwigExtension extends \Twig_Extension {
  /**
   * {@inheritdoc}
   * This function must return the name of the extension. It must be unique.
   */
  public function getName() {
    return 'emulsify_twig_bem';
  }

  /**
   * In this function we can declare the extension function.
   */
  public function getFunctions() {
    return array(
      new \Twig_SimpleFunction('bem', array($this, 'bem'), array('needs_context' => true, 'is_safe' => array('html'))),
    );
  }

  /*
   * This function is used to set classes/attributes based on the passed options
   */
  public function bem($context, $base_class, $modifiers = array(), $blockname = '', $extra = array()) {
    $classes = [];

    // Add the ability to pass an object as the one and only argument.
    if (is_object($base_class) || is_array($base_class)) {
      $object = (object) $base_class;
      $map = [
        'block' => 'base_class',
        'element' => 'blockname',
        'modifiers' => 'modifiers',
        'extra' => 'extra',
      ];
      foreach ($map as $object_key => $arg_key) {
        if (isset($object->$object_key)) {
          $$arg_key = $object->$object_key;
        }
      }
    }
    else {
      // Ensure array arguments.
      if (!is_array($modifiers)) {
        $modifiers = [$modifiers];
      }
      if (!is_array($extra)) {
        $extra = [$extra];
      }

      // If using a blockname to override default class.
      if ($blockname) {
        // Set blockname class.
        $classes[] = $blockname . '__' . $base_class;
        // Set blockname--modifier classes for each modifier.
        if (!empty($modifiers)) {
          foreach ($modifiers as $modifier) {
            $classes[] = $blockname . '__' . $base_class . '--' . $modifier;
          };
        }
      }
      // If not overriding base class.
      else {
        // Set base class.
        $classes[] = $base_class;
        // Set base--modifier class for each modifier.
        if (!empty($modifiers)) {
          foreach ($modifiers as $modifier) {
            $classes[] = $base_class . '--' . $modifier;
          };
        }
      }
      // If extra non-BEM classes are added.
      if (!empty($extra)) {
        foreach ($extra as $extra_class) {
          $classes[] = $extra_class;
        };
      }
      if (class_exists('Drupal')) {
        $attributes = new Attribute();
        // Iterate the attributes available in context.
        foreach($context['attributes'] as $key => $value) {
          // If there are classes, add them to the classes array.
          if ($key === 'class') {
            foreach ($value as $class) {
              $classes[] = $class;
            }
          }
          // Otherwise add the attribute straightaway.
          else {
            $attributes->setAttribute($key, $value);
          }
          // Remove the attribute from context so it doesn't trickle down to
          // includes.
          $context['attributes']->removeAttribute($key);
        }
        // Add class attribute.
        if (!empty($classes)) {
          $attributes->setAttribute('class', $classes);
        }
        return $attributes;
      }
    }
  }
}
