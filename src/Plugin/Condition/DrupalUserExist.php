<?php

namespace Drupal\civicrm_entity\Plugin\Condition;

use Drupal\rules\Core\RulesConditionBase;
use Drupal\user\UserInterface;
use Drupal\civicrm\Civicrm;


/**
 * Provides a 'Drupal linked User exist' condition.
 *
 * @Condition(
 *   id = "rules_drupal_user_exist",
 *   label = @Translation("Drupal linked User exist"),
 *   category = @Translation("CiviCRM"),
 *   context_definitions = {
 *     "civi_contact" = @ContextDefinition("entity_reference",
 *        label = @Translation("CiviCRM contact entity"),
 *        description = @Translation("The CiviCRM contact entity."),
 *        required = TRUE
 *      )
 *   }
 * )
 *
 * @todo Add access callback information from Drupal 7.
 */
class DrupalUserExist extends RulesConditionBase {

  /**
   * Check if user is blocked.
   *
   * @param int $contac-id
   *   The account to check.
   *
   * @return bool
   *   TRUE if the contact_id is linked to a drupal account.
   */
  protected function doEvaluate($civi_contact) {
    $id = (int)$civi_contact->get('id')->getString();
    if (is_int($id)) {
      $result = civicrm_api3('UFMatch', 'get', [
        'sequential' => 1,
        'return' => ["uf_id"],
        'contact_id' => $id,
      ]);

      return $result['is_error'] == 0 && $result['count'] == 1;
    }
    else {
      return false;
    }
  }

}
