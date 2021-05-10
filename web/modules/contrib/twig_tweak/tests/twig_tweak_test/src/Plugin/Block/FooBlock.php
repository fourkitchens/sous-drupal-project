<?php

namespace Drupal\twig_tweak_test\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a foo block.
 *
 * @Block(
 *   id = "twig_tweak_test_foo",
 *   admin_label = @Translation("Foo"),
 *   category = @Translation("Twig Tweak")
 * )
 */
class FooBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $result = AccessResult::allowedIf($account->getAccountName() == 'User 1');
    $result->addCacheTags(['tag_from_' . __FUNCTION__]);
    $result->setCacheMaxAge(35);
    $result->cachePerUser();
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#markup' => 'Foo',
      '#cache' => [
        'contexts' => ['url'],
        'tags' => ['tag_from_' . __FUNCTION__],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return ['tag_twig_tweak_test_foo_plugin'];
  }

}
