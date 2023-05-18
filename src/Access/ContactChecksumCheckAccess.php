<?php

namespace Drupal\civicrm_entity\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

use Symfony\Component\Routing\Route;




/**
 * Checks access for displaying views using the ContactChecksum plugin
 */
class ContactChecksumCheckAccess implements AccessInterface {   

  /**
   * A custom access check
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param \Symfony\Component\Routing\Route $route
   *   The route for which an access check is being done.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account, Route $route) {
    $options = unserialize($route->getRequirement('var_options'));
    $account_roles = $account->getRoles();

    $access_by_role = !empty(array_intersect(array_filter($options['role']), $account->getRoles()));
    if ($access_by_role) {
      \Drupal::logger('ContactChecksumCheckAccess')->info('Access by role');
      return AccessResult::allowed();
    }

    $cid1 = filter_var(\Drupal::request()->query->get('cid1'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $checksum =  \Drupal::request()->query->get('cs');

    if (empty($cid1) || empty($checksum)) {
      \Drupal::logger('ContactChecksumCheckAccess')->info('No cid1 or cs param');
      return AccessResult::forbidden();
    }

    $civicrmAPI = \Drupal::service('civicrm_entity.api');
    $civicrmAPI->getFields('Contact');  // This forces a call to Civicrm initialize.

    $results = \Civi\Api4\Contact::validateChecksum(FALSE)
             ->setContactId($cid1)
             ->setChecksum($checksum)
             ->execute();
    return empty($results[0]['valid']) ? AccessResult::forbidden() : AccessResult::allowed();
  }
}
