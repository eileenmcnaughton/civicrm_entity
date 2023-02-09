<?php

namespace Drupal\civicrm_entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Entity access handler for CiviCRM entities.
 */
class CivicrmEntityAccessHandler extends EntityAccessControlHandler {

  /**
   * The CiviCRM entity info.
   *
   * @var array
   */
  protected $civicrmEntityInfo;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type object.
   */
  public function __construct(EntityTypeInterface $entity_type) {
    parent::__construct($entity_type);
    $this->civicrmEntityInfo = SupportedEntities::getInfo();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $account = $this->prepareUser($account);
    /** @var \Drupal\Core\Access\AccessResult $result */
    $result = parent::checkAccess($entity, $operation, $account);

    if ($result->isNeutral()) {
      return $this->checkEntityPermissions($entity, $operation, $account);
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $result = parent::checkCreateAccess($account, $context, $entity_bundle);
    if ($result->isNeutral()) {
      $permissions = $this->civicrmEntityInfo[$this->entityTypeId]['permissions']['create'];
      $result = AccessResult::allowedIfHasPermissions($account, $permissions, 'OR');
    }

    return $result;
  }

  /**
   * Checks the entity operation and bundle permissions.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to check access.
   * @param string $operation
   *   The entity operation. Usually one of 'view', 'view label', 'update' or
   *   'delete'.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user for which to check access.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  protected function checkEntityPermissions(EntityInterface $entity, $operation, AccountInterface $account) {
    $permissions = [];
    if (!empty($this->civicrmEntityInfo[$this->entityTypeId]['permissions'][$operation])) {
      $permissions = $this->civicrmEntityInfo[$this->entityTypeId]['permissions'][$operation];
    }
    return AccessResult::allowedIfHasPermissions($account, $permissions, 'OR');
  }

}
