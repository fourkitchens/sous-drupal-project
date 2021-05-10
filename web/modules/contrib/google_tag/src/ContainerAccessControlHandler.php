<?php

namespace Drupal\google_tag;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Component\Plugin\Exception\MissingValueContextException;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
// use Drupal\Core\Condition\ConditionAccessResolverTrait;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines access control for the container configuration entity type.
 *
 * @see \Drupal\google_tag\Entity\Container
 */
class ContainerAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  // Comment next to declare resolveConditions() here.
  // use ConditionAccessResolverTrait;

  /**
   * The plugin context handler.
   *
   * @var \Drupal\Core\Plugin\Context\ContextHandlerInterface
   */
  protected $contextHandler;

  /**
   * The context manager service.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * The container entity for which to check access.
   *
   * @var \Drupal\google_tag\Entity\Container
   */
  protected $entity;

  /**
   * Constructs a container access control handler.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Plugin\Context\ContextHandlerInterface $context_handler
   *   The ContextHandler for applying contexts to conditions properly.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $context_repository
   *   The lazy context repository service.
   */
  public function __construct(EntityTypeInterface $entity_type, ContextHandlerInterface $context_handler, ContextRepositoryInterface $context_repository) {
    parent::__construct($entity_type);
    $this->contextHandler = $context_handler;
    $this->contextRepository = $context_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('context.handler'),
      $container->get('context.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($operation != 'view') {
      return parent::checkAccess($entity, $operation, $account);
    }
    if (!$entity->status()) {
      // Deny access to disabled containers.
      return AccessResult::forbidden()->addCacheableDependency($entity);
    }

    // @todo Why is this not default code for an entity that uses the condition
    // plugin interface? Most of it applies generally.

    // Store entity to have access in resolveConditions().
    /** @var \Drupal\google_tag\Entity\Container $entity */
    $this->entity = $entity;

    $conditions = [];
    $missing_context = FALSE;
    $missing_value = FALSE;
    foreach ($entity->getInsertionConditions() as $condition_id => $condition) {
      if ($condition instanceof ContextAwarePluginInterface) {
        try {
          $contexts = $this->contextRepository->getRuntimeContexts(array_values($condition->getContextMapping()));
          $this->contextHandler->applyContextMapping($condition, $contexts);
        }
        catch (MissingValueContextException $e) {
          $missing_value = TRUE;
        }
        catch (ContextException $e) {
          $missing_context = TRUE;
        }
      }
      $conditions[$condition_id] = $condition;
    }

    if ($missing_context) {
      // Because cacheable metadata might be missing, forbid cache write.
      $access = AccessResult::forbidden()->setCacheMaxAge(0);
    }
/*
    elseif ($missing_value) {
      // The context exists but has no value. For example, the node type
      // condition will have a missing context value on any non-node route like
      // the frontpage.
      // @todo Checking this state here prevents the evaluation of other
      // conditions. If this condition does not apply, then that should NOT
      // preclude further evaluation NOR result in automatic access.
      $access = AccessResult::allowed();
    }
*/
    elseif ($this->resolveConditions($conditions, 'and') !== FALSE) {
      $access = AccessResult::allowed();
    }
    else {
      $reason = count($conditions) > 1
        ? "One of the container insertion conditions ('%s') denied access."
        : "The container insertion condition '%s' denied access.";
      $access = AccessResult::forbidden(sprintf($reason, implode("', '", array_keys($conditions))));
    }

    $this->mergeCacheabilityFromConditions($access, $conditions);

    // Ensure access is re-evaluated when the container changes.
    return $access->addCacheableDependency($entity);
  }

  /**
   * Merges cacheable metadata from conditions onto the access result object.
   *
   * @param \Drupal\Core\Access\AccessResult $access
   *   The access result object.
   * @param \Drupal\Core\Condition\ConditionInterface[] $conditions
   *   List of insertion conditions.
   */
  protected function mergeCacheabilityFromConditions(AccessResult $access, array $conditions) {
    foreach ($conditions as $condition) {
      if ($condition instanceof CacheableDependencyInterface) {
        $access->addCacheTags($condition->getCacheTags());
        $access->addCacheContexts($condition->getCacheContexts());
        $access->setCacheMaxAge(Cache::mergeMaxAges($access->getCacheMaxAge(), $condition->getCacheMaxAge()));
      }
    }
  }

  /**
   * Override the resolveConditions() routine.
   *
   * Avoid these calls:
   *   $condition->execute()
   *   $this->executableManager->execute($this);
   * on plugins defined by this module.
   *
   * The latter plugins omit the 'negate' configuration item and unlike core do
   * not treat an empty list of values in an inconsistent manner. With an empty
   * list core toggles the 'negate' value. For example, with negate = FALSE core
   * treats the condition as 'insert snippet except on listed items' and the
   * latter is empty so access is TRUE. With our plugins this configuration
   * equates to 'insert snippet only on listed items' and the latter is empty so
   * access is FALSE.
   *
   * Drupal/Core/Condition/ConditionManager::execute()
   *   $result = $condition->evaluate();
   *   return $condition->isNegated() ? !$result : $result;
   *
   * Core evaluate() routines do NOT return what the documentation comment
   * indicates because the final negate processing is provided by the condition
   * manager execute() routine. This misplaced code is NOT a best practice; and
   * is confusing to someone trying to create a condition plugin.
   *
   * For whatever benefits OOP provides, it still does NOT allow for changes to
   * or replacement of a base class. For example core sets the 'negate' item in
   * the default configuration and expects it to exist in other routines. This
   * prevents a child class from easily changing or removing this item. For this
   * and the above reason, this module replaces the ConditionPluginBase class.
   * Core does not provide a mechanism to replace many of its services such that
   * the new base class can be extended by the existing child classes.
   */

  /**
   * {@inheritdoc}
   */
  protected function resolveConditions(array $conditions, $condition_logic) {
    foreach ($conditions as $condition_id => $condition) {
      try {
        if (in_array($condition_id, ['gtag_domain', 'gtag_language'])) {
          // Avoid call to execute() as it involves the 'negate' element removed
          // from our condition plugins.
          $pass = $condition->evaluate();
        }
        else {
          // The condition plugin is not defined by this module.
          $pass = $condition->execute();
        }
      }
      catch (ContextException $e) {
        // The condition is missing context; consider that a pass.
        // Example: node bundle condition and the page request is not a node.
        // Because the context is missing, the condition is not applicable.
        $pass = TRUE;
      }

      $this->entity->displayMessage('@condition check @satisfied', ['@condition' => str_replace('gtag_', '', $condition_id), '@satisfied' => $pass]);

      if (!$pass && $condition_logic == 'and') {
        // This condition failed and all conditions are needed; deny access.
        return FALSE;
      }
      elseif ($pass && $condition_logic == 'or') {
        // This condition passed and only one condition is needed; grant access.
        return TRUE;
      }
    }

    // Return TRUE if logic was 'and', meaning all rules passed.
    // Return FALSE if logic was 'or', meaning no rule passed.
    return $condition_logic == 'and';
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowed();
  }

}
