<?php
/**
 * @file
 * Provides Entity metadata integration.
 */

/**
 * Extend the default CiviCRM membership metadata properties.
 */
class CivicrmEntityMetadataController extends EntityDefaultMetadataController {

  /**
   * Return a set of properties for an entity based on the schema definition.
   */
  protected function convertSchema() {
    if (empty($this->info['base table'])) {
      return array();
    }
    return civicrm_entity_metadata_convert_schema($this->info['base table']);
  }
}

/**
 * Converts schema info for table to property info.
 *
 * This is based on the CiviCRM get schema function and is a very
 * limited implementation.
 *
 * @param string $table
 *   The name of the table as used in hook_schema().
 *
 * @return array
 *   Property info suitable for hook_entity_property_info().
 */
function civicrm_entity_metadata_convert_schema($table) {
  $schema = civicrm_entity_get_schema($table);
  $properties = array();
  foreach ($schema['fields'] as $name => $info) {
    if ($type = _entity_metadata_convert_schema_type($info['type'])) {
      $properties[$name] = array(
        'type' => $type,
        'label' => drupal_ucfirst($name),
        'schema field' => $name,
        // As we cannot know about any setter access, leave out the setter
        // callback. For getting usually no further access callback is needed.
      );
      if ($info['type'] == 'serial') {
        $properties[$name]['validation callback'] = 'entity_metadata_validate_integer_positive';
      }
    }
  }
  return $properties;
}

