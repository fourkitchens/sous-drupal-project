<?php

namespace Drupal\blazy\Utility;

use Drupal\Component\Utility\Xss;
use Michelf\MarkdownExtra;
use League\CommonMark\CommonMarkConverter;

/**
 * Provides markdown utilities only useful for the help text.
 */
class BlazyMarkdown {

  /**
   * Checks if we have the needed classes.
   */
  public static function isApplicable() {
    return class_exists('Michelf\MarkdownExtra') || class_exists('League\CommonMark\CommonMarkConverter');
  }

  /**
   * Processes Markdown text, and convert into HTML suitable for the help text.
   *
   * @param string $string
   *   The string to apply the Markdown filter to.
   * @param bool $sanitize
   *   True, if the string should be sanitized.
   *
   * @return string
   *   The filtered, or raw converted string.
   */
  public static function parse($string = '', $sanitize = TRUE) {
    if (!self::isApplicable()) {
      return '<pre>' . $string . '</pre>';
    }

    if (class_exists('Michelf\MarkdownExtra')) {
      $string = MarkdownExtra::defaultTransform($string);
    }
    elseif (class_exists('League\CommonMark\CommonMarkConverter')) {
      $converter = new CommonMarkConverter();
      $string = $converter->convertToHtml($string);
    }

    // We do not pass it to FilterProcessResult, as this is meant simple.
    return $sanitize ? Xss::filterAdmin($string) : $string;
  }

}
