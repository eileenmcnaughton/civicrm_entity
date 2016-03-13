<?php

/**
 * Default Display Suite Render handler for CiviCRM "fields".
 */
function _civicrm_entity_render_fields($field) {
  $settings = isset($field['formatter_settings']) ? $field['formatter_settings'] : array();
  $settings += $field['properties']['default'];

  $wrapper = entity_metadata_wrapper($field['entity_type'], $field['entity']->id);


  //civicrm_contact civi_user field needs special handling
  switch($field['entity_type']) {
    case 'civicrm_contact':
      $formatted_value = _civicrm_entity_contact_get_formatted_values($field,$wrapper);
      break;
    case 'civicrm_participant':
      $formatted_value = _civicrm_entity_participant_get_formatted_values($field,$wrapper,$field['entity']);
      break;
    default: $formatted_value = $wrapper->{$field['field_name']}->value();
      break;
  }

  // primary_contact_id_contact and other fields are objects produced by the meta wrapper
  // Will be ease to generate links to the entities if it is consistent across entities
  if(is_object($formatted_value)){
    $formatted_value = $wrapper->{$field['field_name']}->id->value();
  }
  if($field['formatter'] == 'civicrm_link') {
    global $base_url;
    if($formatted_value){
      $formatted_value = '<a href="'. $base_url . '/'. str_replace('_','-',$field['properties']['link_entity']) . '/' . $formatted_value . '">'. _civicrm_entity_get_entity_label($field['properties']['link_entity'],$formatted_value) . '</a>';
    }
  }

  if($field['formatter'] == 'civicrm_yes_no') {
    if($formatted_value) {
      $formatted_value = 'Yes';
    }
    else {
      $formatted_value = 'No';
    }
  }
  if($field['formatter'] == 'civicrm_true_false') {
    if($formatted_value) {
      $formatted_value = 'True';
    }
    else {
      $formatted_value = 'False';
    }
  }

  if($field['formatter'] == 'civicrm_option_value') {
    if($formatted_value){
      $formatted_value = _civicrm_entity_option_lookup($field,explode(',', $formatted_value));
    }
  }

  // Wrapper and class.
  if (!empty($settings['wrapper'])) {
    $wrapper = check_plain($settings['wrapper']);
    $class = (!empty($settings['class'])) ? ' class="' . check_plain($settings['class']) . '"' : '';
    $output = '<' . $wrapper . $class . '>' . $formatted_value . '</' . $wrapper . '>';
  }

  return $output;
}

/**
 * Utiltiy function to add provided boolean fields with yes/no and true/false formatters
 *
 * @param $entity_type
 * @param $yes_no_fields
 * @param $fields
 */
function _civicrm_entity_yesno_addformatters($entity_type,$yes_no_fields,&$fields){
  foreach($yes_no_fields as $name) {
    $fields[$entity_type][$name]['properties']['formatters'] = array (
        'default' => 'Raw Value',
        'civicrm_yes_no' => 'Yes/No',
        'civicrm_true_false' => 'True/False',
    );
  }
}

/**
 * Utility function to provide option fields with option value or option id formatters
 *
 * @param $entity_type
 * @param $option_fields
 * @param $fields
 */
function _civicrm_entity_option_addformatters($entity_type,$option_fields,&$fields){
  foreach($option_fields as $name) {
    $fields[$entity_type][$name]['properties']['formatters'] = array (
        'default' => 'Option ID',
        'civicrm_option_value' => 'Option Value',
    );
  }
}

/**
 * Utility function to provide fields with a link formatter
 *
 * @param $entity_type
 * @param $link_fields
 * @param $fields
 */
function _civicrm_entity_link_addformatters($entity_type,$link_fields,&$fields){
  foreach($link_fields as $link_field) {
    $fields[$entity_type][$link_field['link_field']]['properties']['formatters'] = array (
        'default' => 'Default',
        'civicrm_link' => 'Link',
    );
    $fields[$entity_type][$link_field['link_field']]['properties']['link_entity'] = $link_field['target'];
  }
}

/**
 * Utility function to return an entity's label
 *
 * @param $entity_type
 * @param $id
 * @return mixed
 */
function _civicrm_entity_get_entity_label($entity_type,$id) {
  $wrapper = entity_metadata_wrapper($entity_type,$id);
  return $wrapper->label();
}

/**
 * Returns a comma delimited list of option labels from id values
 *
 * @param $field
 * @param $values
 * @return string
 * @throws CiviCRM_API3_Exception
 */
function _civicrm_entity_option_lookup($field,$values){
  $result = civicrm_api3(substr($field['entity_type'],8), 'getoptions', array(
      'field' => $field['field_name'],
  ));
  $fm = array();
  foreach($values as $value) {
    $fm[] = $result['values'][$value];
  }
  return implode(",",$fm);
}

/**
 * Contact entity field formatted value special handling
 *
 * @param $field
 * @param $wrapper
 * @return string
 */
function _civicrm_entity_contact_get_formatted_values(&$field,$wrapper){
  switch($field['field_name']){
    case 'contact_sub_type':
    case 'preferred_communication_method':
      return _civicrm_entity_formatted_output_of_arrays($field,$wrapper);
    default: return $wrapper->{$field['field_name']}->value();
  }
}

/**
 * Participant entity field formatted value special handling
 *
 * @param $field
 * @param $wrapper
 * @param $entity
 * @return mixed
 */
function _civicrm_entity_participant_get_formatted_values(&$field,$wrapper,$entity){
  switch($field['field_name']) {
    case 'role_id':
    case 'status_id':
    case 'registered_date':
    case 'source':
    case 'fee_currency':
    case 'is_pay_later':
    case 'registered_by_id':
    case 'is_test':
      return $entity->{'participant_'.$field['field_name']};
    case 'fee_level':
      return $entity->{'participant_'.$field['field_name']}[0];
    default: return $wrapper->{$field['field_name']}->value();
  }
}

/**
 * Returns a comma delimited list of field values which are arrays
 *
 * @param $field
 * @param $wrapper
 * @return string
 */
function _civicrm_entity_formatted_output_of_arrays(&$field,$wrapper) {
  $values = $wrapper->{$field['field_name']}->value();
  if(is_array($values)) {
    return  implode(",",$values );
  }
  else {
    return '';
  }
}
