<?php

namespace Drupal\civicrm_entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Entity access handler for CiviCRM entities.
 */
class CivicrmEntityAccessHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $entity, $operation, AccountInterface $account = NULL, $return_as_object = FALSE) {
    if (!$this->entityType->get('civicrm_entity_ui_exposed')) {
      $result = AccessResult::forbidden();
      return $return_as_object ? $result : $result->isAllowed();
    }
    return parent::access($entity, $operation, $account, $return_as_object);
  }

}
