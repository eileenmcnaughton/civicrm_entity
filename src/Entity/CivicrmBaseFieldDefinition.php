<?php

namespace Drupal\civicrm_entity\Entity;

use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Custom base field definition to fix custom field column names.
 */
class CivicrmBaseFieldDefinition extends BaseFieldDefinition {

  /**
   * {@inheritdoc}
   */
  public function getColumns() {
    $columns = parent::getColumns();
    $metadata = $this->getSetting('civicrm_entity_field_metadata');
    if (is_array($metadata)) {
      return [
        $metadata['column_name'] => $columns['value']
      ];
    }
    return $columns;
  }

}
