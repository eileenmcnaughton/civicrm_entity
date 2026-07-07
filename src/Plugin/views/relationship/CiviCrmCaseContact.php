<?php

namespace Drupal\civicrm_entity\Plugin\views\relationship;

use Drupal\views\Attribute\ViewsRelationship;

/**
 * Relationship for referencing civicrm_contact and civicrm_group.
 */
#[ViewsRelationship("civicrm_entity_civicrm_case_contact")]
class CiviCrmCaseContact extends CiviCrmBridgeRelationshipBase {
}
