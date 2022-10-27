<?php

namespace Drupal\civicrm_entity\Entity;

/**
 * Provides an interface for defining fields in civicrm entities.
 */
interface FieldDefinitionProviderInterface {

  /**
   * Gets an entity base field definition from a CiviCRM field definition.
   *
   * @param array $civicrm_field
   *   The CiviCRM field definition.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition
   *   The base field definition.
   */
  public function getBaseFieldDefinition(array $civicrm_field);

}
