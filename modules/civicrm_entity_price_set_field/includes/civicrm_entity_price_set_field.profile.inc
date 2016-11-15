<?php
/**
 * @file
 * CiviCRM Entity Price Set Field, Profile Form generation and processing
 */


/**
 * Returns field metadata for a profile
 *
 * @param $profile_id
 * @param string $type
 * @return array
 */
function _civicrm_entity_price_set_field_get_profile_metadata($profile_id, $type = 'event') {
  try {
    $fields_api_result = civicrm_api3('Profile', 'getfields', array(
      'api_action' => "submit",
      'profile_id' => $profile_id,
      'options' => array('sort' => "weight"),
    ));
  }
  catch (CiviCRM_API3_Exception $e) {
    return array();
  }
  $fields = array();
  $event_allowed_entities = array('contact', 'address', 'phone', 'email', 'website');
  $contact_entities = array('contact', 'address', 'phone', 'email', 'website');

  if (!$fields_api_result['is_error'] && $fields_api_result['count']) {
    foreach ($fields_api_result['values'] as $field) {
      if (!empty($field['entity'])) {
        if($type == 'event' && !in_array($field['entity'], $event_allowed_entities)) {
          continue;
        }
        $fields[$field['name']] = $field;
      }
    }
    return $fields;
  }
}

/**
 * Generates the FAPI for an individual CiviCRM Profile
 *
 * @param $profile_id
 * @return array
 */
function _civicrm_entity_price_set_field_generate_profile_fapi($profile_id, $type = 'event') {
  if (!civicrm_initialize()) {
    return array();
  }
  $fields =_civicrm_entity_price_set_field_get_profile_metadata($profile_id, 'event');
  $contact_entities = array('contact', 'address', 'phone', 'email', 'website');
  $form = array();
  if (count($fields)) {
    foreach ($fields as $field) {
      $widget_info = civicrm_entity_get_field_widget($field, $field['entity']);
      //dsm($field['name']);
      //dsm($widget_info);
      if (!empty($widget_info['widget'])) {
        // type
        $form[$field['name']]['#type'] = $widget_info['widget'];
        //name
        $form[$field['name']]['#title'] = $field['title'];
        // options
        if (!empty($widget_info['options'])) {
          $form[$field['name']]['#options'] = $widget_info['options'];
        }
        // description
        if (!empty($field['help_post'])) {
          $form[$field['name']]['#description'] = $field['help_post'];
        }
        // required
        if (!empty($field['api.required']) && $field['api.required'] != "0") {
          $form[$field['name']]['#required'] = TRUE;
        }
      }
    }
    //dsm($form);
  }
  return $form;
}

/**
 * Processes the submitted form data via CiviCRM Profile API
 *
 * @param $profile_id
 * @param $submitted_data
 * @param $contact_id
 *
 * @return bool|array
 */
function _civicrm_entity_price_set_field_process_profile_submitted_data($profile_id, $submitted_data, $contact_id = 0) {
  try {
    $params = array(
      'sequential' => 1,
      'profile_id' => $profile_id,
    );
    foreach ($submitted_data as $name => $value) {
      $params[$name] = $value;
    }
    if($contact_id) {
      $params['contact_id'] = $contact_id;
    }
    $result = civicrm_api3('Profile', 'submit', $params);
    dsm($result);
    if(!empty($result['values']['id'])) {
      $contact = entity_load_single('civicrm_contact', $result['values']['id']);
      return $contact;
    }
    else {
      return FALSE;
    }
  }
  catch (CiviCRM_API3_Exception $e) {
    drupal_set_message($e->getMessage(),'warning');
    return FALSE;
  }
}

/**
 * Gets the profiles for an event
 *
 * @param $event_id
 * @param $type
 * @return array
 */
function _civicrm_entity_price_set_field_get_event_profiles($event_id, $type = 'CiviEvent') {
  if (!civicrm_initialize(TRUE)) {
    return array();
  }
  $profiles = array();
  try {
    $uf_joins = civicrm_api3('UFJoin', 'get', array(
      'sequential' => 1,
      'is_active' => 1,
      'module' => $type,
      'entity_table' => "civicrm_event",
      'entity_id' => $event_id,
      'options' => array('sort' => "weight"),
    ));
    if ($uf_joins['count']) {
      foreach ($uf_joins['values'] as $index => $uf_join) {
        $profiles[] = $uf_join['uf_group_id'];
      }
    }
    return $profiles;
  }
  catch (CiviCRM_API3_Exception $e) {
    return array();
  }
}

/**
 * Returns the title for a Profile
 *
 * @param $profile_id
 * @return string
 */
function _civicrm_entity_price_set_field_get_profile_title($profile_id) {
  if (!civicrm_initialize()) {
    return '';
  }
  try {
    $result = civicrm_api3('UFGroup', 'get', array(
      'sequential' => 1,
      'id' => $profile_id,
    ));
    if($result['count']) {
      return $result['values'][0]['title'];
    }
  }
  catch (CiviCRM_API3_Exception $e) {
  }
  return '';
}

/**
 * Returns an array of entity types for a Profile
 *
 * @param $profile_id
 * @return array
 */
function _civicrm_entity_price_set_field_get_profile_entity_types($profile_id) {
  if (!civicrm_initialize()) {
    return array();
  }
  try {
    $result = civicrm_api3('UFGroup', 'get', array(
      'sequential' => 1,
      'id' => $profile_id,
    ));
    if($result['count']) {
      $group_type = $result['values'][0]['group_type'];
      return explode(',', $group_type);
    }
  }
  catch (CiviCRM_API3_Exception $e) {
  }
  return array();
}

