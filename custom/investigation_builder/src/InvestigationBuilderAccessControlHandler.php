<?php

declare(strict_types=1);

namespace Drupal\investigation_builder;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the investigation builder entity type.
 *
 * phpcs:disable Drupal.Arrays.Array.LongLineDeclaration
 *
 * @see https://www.drupal.org/project/coder/issues/3185082
 */
final class InvestigationBuilderAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission($this->entityType->getAdminPermission())) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    return match($operation) {
      'view' => AccessResult::allowedIfHasPermission($account, 'view investigation_builder'),
      'update' => AccessResult::allowedIfHasPermission($account, 'edit investigation_builder'),
      'delete' => AccessResult::allowedIfHasPermission($account, 'delete investigation_builder'),
      'delete revision' => AccessResult::allowedIfHasPermission($account, 'delete investigation_builder revision'),
      'view all revisions', 'view revision' => AccessResult::allowedIfHasPermissions($account, ['view investigation_builder revision', 'view investigation_builder']),
      'revert' => AccessResult::allowedIfHasPermissions($account, ['revert investigation_builder revision', 'edit investigation_builder']),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, ['create investigation_builder', 'administer investigation_builder'], 'OR');
  }

}
