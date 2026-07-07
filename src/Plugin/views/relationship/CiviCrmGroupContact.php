<?php

namespace Drupal\civicrm_entity\Plugin\views\relationship;

use Drupal\views\Attribute\ViewsRelationship;

/**
 * Relationship for referencing civicrm_contact and civicrm_group.
 */
#[ViewsRelationship("civicrm_entity_civicrm_group_contact")]
class CiviCrmGroupContact extends CiviCrmBridgeRelationshipBase {
}
