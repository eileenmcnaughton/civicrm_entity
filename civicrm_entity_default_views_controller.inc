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

  /**
   * Comes up with views information based on the given schema and property
   * info.
   */
  protected function map_from_schema_info($property_name, $schema_field_info, $property_info) {
    $type = isset($property_info['type']) ? $property_info['type'] : 'text';
    $views_field_name = $property_info['schema field'];
    $return = array();

    if (!empty($schema_field_info['serialize'])) {
      return FALSE;
    }

    $description = array(
      'title' => $property_info['label'],
      'help' => 'Provided by CiviCRM Entity' . (isset($property_info['description']) ? ': ' . $property_info['description'] : NULL),
    );

    // Add in relationships to related entities.
    if (($info = entity_get_info($type)) && !empty($info['base table'])) {

      // Prepare reversed relationship data.
      $label_lowercase = drupal_strtolower($this->info['label'][0]) . drupal_substr($this->info['label'], 1);
      $property_label_lowercase = drupal_strtolower($property_info['label'][0]) . drupal_substr($property_info['label'], 1);

      // We name the field of the first reverse-relationship just with the
      // base table to be backward compatible, for subsequents relationships we
      // append the views field name in order to get a unique name.
      $name = !isset($this->relationships[$info['base table']][$this->info['base table']]) ? $this->info['base table'] : $this->info['base table'] . '_' . $views_field_name;
      $this->relationships[$info['base table']][$name] = array(
        'title' => $this->info['label'],
        'help' => t("Associated @label via the @label's @property.", array('@label' => $label_lowercase, '@property' => $property_label_lowercase)),
        'relationship' => array(
          'label' => $this->info['label'],
          'handler' => $this->getRelationshipHandlerClass($this->type, $type),
          'base' => $this->info['base table'],
          'base field' => $views_field_name,
          'relationship field' => isset($info['entity keys']['name']) ? $info['entity keys']['name'] : $info['entity keys']['id'],
        ),
      );

      $return['relationship'] = array(
        'label' => drupal_ucfirst($info['label']),
        'handler' => $this->getRelationshipHandlerClass($type, $this->type),
        'base' => $info['base table'],
        'base field' => isset($info['entity keys']['name']) ? $info['entity keys']['name'] : $info['entity keys']['id'],
        'relationship field' => $views_field_name,
      );

      // Add in direct field/filters/sorts for the id itself too.
      $type = isset($info['entity keys']['name']) ? 'token' : 'integer';
      // Append the views-field-name to the title if it is different to the
      // property name.
      if ($property_name != $views_field_name) {
        $description['title'] .= ' ' . $views_field_name;
      }
    }
    if (CRM_Utils_Array::value('mysql_type', $property_info)) {
      $type = $property_info['mysql_type'];
    }
    switch ($type) {
      case 'token':
      case 'text':
        $return += $description + array(
            'field' => array(
              'real field' => $views_field_name,
              'handler' => 'views_handler_field',
              'click sortable' => TRUE,
            ),
            'sort' => array(
              'real field' => $views_field_name,
              'handler' => 'views_handler_sort',
            ),
            'filter' => array(
              'real field' => $views_field_name,
              'handler' => 'views_handler_filter_string',
            ),
            'argument' => array(
              'real field' => $views_field_name,
              'handler' => 'views_handler_argument_string',
            ),
          );
        break;

      case 'decimal':
      case 'integer':
        $return += $description + array(
            'field' => array(
              'real field' => $views_field_name,
              'handler' => 'views_handler_field_numeric',
              'click sortable' => TRUE,
              'float' => ($type == 'decimal'),
            ),
            'sort' => array(
              'real field' => $views_field_name,
              'handler' => 'views_handler_sort',
            ),
            'filter' => array(
              'real field' => $views_field_name,
              'handler' => 'views_handler_filter_numeric',
            ),
            'argument' => array(
              'real field' => $views_field_name,
              'handler' => 'views_handler_argument_numeric',
            ),
          );
        break;
      case 'datetime':
        $return += $description + array(
            'field' => array(
              'real field' => $views_field_name,
              'handler' => 'civicrm_handler_field_datetime',
              'click sortable' => TRUE,
            ),
            'sort' => array(
              'real field' => $views_field_name,
              'handler' => 'civicrm_handler_sort_date',
            ),
            'filter' => array(
              'real field' => $views_field_name,
              'handler' => 'civicrm_handler_filter_datetime',
              'is date' => TRUE,
            ),
            'argument' => array(
              'real field' => $views_field_name,
              'handler' => 'views_handler_argument_date',
            ),
          );
        break;
      case 'date':
        $return += $description + array(
            'field' => array(
              'real field' => $views_field_name,
              'handler' => 'views_handler_field_date',
              'click sortable' => TRUE,
            ),
            'sort' => array(
              'real field' => $views_field_name,
              'handler' => 'views_handler_sort_date',
            ),
            'filter' => array(
              'real field' => $views_field_name,
              'handler' => 'views_handler_filter_date',
            ),
            'argument' => array(
              'real field' => $views_field_name,
              'handler' => 'views_handler_argument_date',
            ),
          );
        break;

      case 'uri':
        $return += $description + array(
            'field' => array(
              'real field' => $views_field_name,
              'handler' => 'views_handler_field_url',
              'click sortable' => TRUE,
            ),
            'sort' => array(
              'real field' => $views_field_name,
              'handler' => 'views_handler_sort',
            ),
            'filter' => array(
              'real field' => $views_field_name,
              'handler' => 'views_handler_filter_string',
            ),
            'argument' => array(
              'real field' => $views_field_name,
              'handler' => 'views_handler_argument_string',
            ),
          );
        break;

      case 'boolean':
        $return += $description + array(
            'field' => array(
              'real field' => $views_field_name,
              'handler' => 'views_handler_field_boolean',
              'click sortable' => TRUE,
            ),
            'sort' => array(
              'real field' => $views_field_name,
              'handler' => 'views_handler_sort',
            ),
            'filter' => array(
              'real field' => $views_field_name,
              'handler' => 'views_handler_filter_boolean_operator',
            ),
            'argument' => array(
              'real field' => $views_field_name,
              'handler' => 'views_handler_argument_string',
            ),
          );
        break;
    }


    // If there is an options list callback, add to the filter and field.
    if (isset($return['filter']) && !empty($property_info['options list'])) {
      $return['filter']['handler'] = 'views_handler_filter_in_operator';
      $return['filter']['options callback'] = array('EntityDefaultViewsController', 'optionsListCallback');
      $return['filter']['options arguments'] = array($this->type, $property_name, 'view');
    }
    // @todo: This class_exists is needed until views 3.2.
    if (isset($return['field']) && !empty($property_info['options list']) && class_exists('views_handler_field_machine_name')) {
      $return['field']['handler'] = 'views_handler_field_machine_name';
      $return['field']['options callback'] = array('EntityDefaultViewsController', 'optionsListCallback');
      $return['field']['options arguments'] = array($this->type, $property_name, 'view');
    }
    return $return;
  }

}
