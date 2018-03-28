<?php

namespace Drupal\civicrm_entity\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Entity class for CiviCRM entities.
 *
 * This entity class is not annotated. Plugin definitions are created during
 * the hook_entity_type_build() process. This allows for dynamic creation of
 * multiple entity types that use one single class, without creating redundant
 * class files and annotations.
 *
 * @see civicrm_entity_entity_type_build().
 */
class CivicrmEntity extends ContentEntityBase {

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = [];
    $field_definition_provider = \Drupal::service('civicrm_entity.field_definition_provider');
    $civicrm_fields = \Drupal::service('civicrm_entity.api')->getFields($entity_type->get('civicrm_entity'));
    foreach ($civicrm_fields as $civicrm_field) {
      $fields[$civicrm_field['name']] = $field_definition_provider->getBaseFieldDefinition($civicrm_field);
    }
    return $fields;
  }
}
