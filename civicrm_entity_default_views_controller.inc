<?php

/**
 * @TODO Document this class.
 */
class CiviCRMEntityDefaultViewsController extends EntityDefaultViewsController {

  /**
   * Defines the result for hook_views_data().
   */
  public function views_data() {
    $data = array();
    $this->relationships = array();
    if (!empty($this->info['base table'])) {
      $table = $this->info['base table'];
      // Define the base group of this table. Fields that don't
      // have a group defined will go into this field by default.
      $data[$table]['table']['group'] = drupal_ucfirst($this->info['label']);
      $data[$table]['table']['entity type'] = $this->type;

      // If the plural label isn't available, use the regular label.
      $label = isset($this->info['plural label']) ? $this->info['plural label'] : $this->info['label'];
      $data[$table]['table']['base'] = array(
        'field' => $this->info['entity keys']['id'],
        'title' => drupal_ucfirst($label),
        'help' => isset($this->info['description']) ? $this->info['description'] : '',
      );
      $data[$table]['table']['entity type'] = $this->type;
      $data[$table] += $this->schema_fields();

      // Add in any reverse-relationships which have been determined.
      $data += $this->relationships;
    }
    return $data;
  }

  /**
   * Find views fields using schema & entity property information.
   */
  protected function schema_fields() {
    // We are not using the 'normal' schema function here due to the
    // problems discussed in the readme.
    if (empty($this->info['base table'])) {
      return array();
    }
    $schema = civicrm_entity_get_schema($this->info['base table']);
    $properties = entity_get_property_info($this->type) + array('properties' => array());
    $data = array();
    // We will take out the reliance on other schema related checks
    // here, ideally we would 'read' getfields output to determine if
    // we are looking at a DB field or a custom / pseudofield.
    foreach ($properties['properties'] as $name => $property_info) {
      if (!empty($property_info['schema field']) && !empty($schema['fields']) &&
        isset($schema['fields'][$property_info['schema field']])
      ) {
        if ($views_info = $this->map_from_schema_info($name, $schema['fields'][$property_info['schema field']], $property_info)) {
          $data[$name] = $views_info;
        }
      }
    }
    return $data;
  }
}
