<?php

namespace Drupal\civicrm_entity\Plugin\views\relationship;

use Drupal\views\Attribute\ViewsRelationship;

/**
 * Relationship for referencing civicrm_case and civicrm_activity.
 */
#[ViewsRelationship("civicrm_entity_civicrm_case_activity")]
class CiviCrmCaseActivity extends CiviCrmBridgeRelationshipBase {
}
