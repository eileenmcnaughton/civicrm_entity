<?php
/**
 * @file
 * CiviCRM Entity Profile, Profile Form generation and processing, utility functions
 */


/**
 * Returns field metadata for a profile
 *
 * @param $profile_id
 * @param string $type
 * @return array
 */
function _civicrm_entity_profile_get_profile_metadata($profile_id, $type = 'event') {
  if (!civicrm_initialize()) {
    return array();
  }
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
  $event_allowed_entities = array('contact', 'address', 'phone', 'email', 'website', 'participant');
  $contact_entities = array('contact', 'address', 'phone', 'email', 'website');

  if (!$fields_api_result['is_error'] && $fields_api_result['count']) {
    foreach ($fields_api_result['values'] as $field) {
      if (!empty($field['entity'])) {
        if ($type == 'event' && !in_array($field['entity'], $event_allowed_entities)) {
          continue;
        }
        $fields[$field['name']] = $field;
      }
    }
    return $fields;
  }
  return array();
}

/**
 * Function to determine the entity type of a profile, if mixed, return FALSE
 *
 * @param $profile_id
 * @return bool|string
 */
function _civicrm_entity_profile_determine_profile_entity($profile_id) {
  $contact_entities = array('contact', 'address', 'phone', 'email', 'website');
  $fields = _civicrm_entity_profile_get_profile_metadata($profile_id);
  $first_entity = '';
  foreach ($fields as $name => $field) {
    if (empty($first_entity)) {
      $first_entity = in_array($field['entity'], $contact_entities) ? 'contact' : $field['entity'];
    }
    else {
      $next_entity = in_array($field['entity'], $contact_entities) ? 'contact' : $field['entity'];
      if ($next_entity != $first_entity) {
        return FALSE;
      }
    }
  }
  return $first_entity;
}

/**
 * Generates the FAPI for an individual CiviCRM Profile
 *
 * @param $profile_id
 * @return array
 */
function _civicrm_entity_profile_generate_profile_fapi($profile_id, $type = 'event') {
  if (!civicrm_initialize()) {
    return array();
  }
  $fields =_civicrm_entity_profile_get_profile_metadata($profile_id, $type);
  $contact_entities = array('contact', 'address', 'phone', 'email', 'website');
  $form = array();
  if (count($fields)) {
    foreach ($fields as $field) {
      $widget_info = civicrm_entity_get_field_widget($field, $field['entity']);
      if (!empty($widget_info['widget'])) {
        // type
        $form[$field['name']]['#type'] = $widget_info['widget'];
        if ($widget_info['widget'] == 'date_select' || $widget_info == 'date_popup') {
          if (module_exists('date_popup')) {
            $form[$field['name']]['#type'] = 'date_popup';
          }
          if (!empty($field['date_format'])) {
            $form[$field['name']]['#date_format'] = 'Y-m-d';
          }
          if (!empty($field['time_format'])) {
            if (!empty($form[$field['name']]['#date_format'])) {
              $form[$field['name']]['#date_format'] .= ' H:i:s';
            }
            else {
              $form[$field['name']]['#date_format'] = 'H:i:s';
            }
          }
        }
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
function _civicrm_entity_profile_process_profile_submitted_data($profile_id, $submitted_data, $entity_type = 'contact', $contact_id = 0) {
  try {
    $params = array(
      'sequential' => 1,
      'profile_id' => $profile_id,
    );
    foreach ($submitted_data as $name => $value) {
      if (is_array($value)) {
        $temp_param = array();
        foreach ($value as $v) {
           if(!empty($v)) {
             $temp_param[] = $v;
           }
        }
        if (!empty($temp_param)) {
          $params[$name] =  implode(CRM_Core_DAO::VALUE_SEPARATOR, $temp_param);
        }
      }
      else {
        $params[$name] = $value;
      }
    }
    if ($contact_id) {
      $params['contact_id'] = $contact_id;
    }
    $result = civicrm_api3('Profile', 'submit', $params);

    if (!empty($result['id'])) {
      switch ($entity_type) {
        case 'contact':
            $contact = entity_load_single('civicrm_contact', $result['id']);
            return $contact;
        case 'participant':
          if (!empty($result['values'][$contact_id]['api.participant.create']['id'])) {
            $participant = entity_load_single('civicrm_participant', $result['values'][$contact_id]['api.participant.create']['id']);
            return $participant;
          }
          else return FALSE;
      }
    }
    else {
      return FALSE;
    }
  }
  catch (CiviCRM_API3_Exception $e) {
    drupal_set_message($e->getMessage(), 'warning');
    return FALSE;
  }
}


/**
 * Gets the profiles for an event
 *
 * @param $entity_type
 * @param $id
 * @param $type
 * @return array
 */
function _civicrm_entity_profile_get_ufjoins($entity_type, $id, $type = 'CiviEvent') {
  $uf_join_query = new EntityFieldQuery();
  $uf_join_results = $uf_join_query->entityCondition('entity_type', 'civicrm_uf_join')
    //->propertyCondition('is_active', 1)
    ->propertyCondition('module', $type)
    ->propertyCondition('entity_table', $entity_type)
    ->propertyCondition('entity_id', $id)
    ->propertyOrderBy('weight')
    ->execute();

  if (!empty($uf_join_results['civicrm_uf_join']) && count($uf_join_results['civicrm_uf_join'])) {
    return array_keys($uf_join_results['civicrm_uf_join']);
  }
  else {
    return array();
  }
}


/**
 * Gets the profiles (uf_group) for an entity
 *
 * @param $entity_type
 * @param $id
 * @param $type
 * @return array
 */
function _civicrm_entity_profile_get_profiles($entity_type, $id, $type) {
  $profiles = array();
  $uf_join_query = new EntityFieldQuery();
  $uf_join_results = $uf_join_query->entityCondition('entity_type', 'civicrm_uf_join')
    ->propertyCondition('is_active', 1)
    ->propertyCondition('module', $type)
    ->propertyCondition('entity_table', $entity_type)
    ->propertyCondition('entity_id', $id)
    ->propertyOrderBy('weight')
    ->execute();

  if (!empty($uf_join_results['civicrm_uf_join'])) {
    $uf_joins = entity_load('civicrm_uf_join', array_keys($uf_join_results['civicrm_uf_join']));
    foreach ($uf_joins as $id => $uf_join) {
      $profiles[] = $uf_join->uf_group_id;
    }
  }
  return $profiles;
}

/**
 * Returns the title for a Profile
 *
 * @param $profile_id
 * @return string
 */
function _civicrm_entity_profile_get_profile_title($profile_id) {
  $uf_group = entity_load_single('civicrm_uf_group', $profile_id);
  if (!empty($uf_group->title)) {
    return $uf_group->title;
  }
  return '';
}

/**
 * Returns an array of entity types for a Profile
 *
 * @param $profile_id
 * @return array
 */
function _civicrm_entity_profile_get_profile_entity_types($profile_id) {
  $uf_group = entity_load_single('civicrm_uf_group', $profile_id);
  if (!empty($uf_group->group_type)) {
    return !empty($uf_group->group_type) ? explode(',', $uf_group->group_type) : array();
  }
  return array();
}

/**
 * Utility function to build an array of Profile (civicrm_uf_group) titles, keyed by entity id
 *
 * @return array
 */
function _civicrm_entity_profile_get_profiles_for_options_list() {
  $uf_group_query = new EntityFieldQuery();
  $uf_group_results = $uf_group_query->entityCondition('entity_type', 'civicrm_uf_group')
    ->propertyCondition('is_active', 1)
    ->propertyOrderBy('title')
    ->execute();
  $options = array('' => '- None -');
  if (!empty($uf_group_results['civicrm_uf_group'])) {
    $uf_groups = entity_load('civicrm_uf_group', array_keys($uf_group_results['civicrm_uf_group']));
    foreach ($uf_groups as $id => $uf_group) {
      $options[$id] = $uf_group->title;
    }
  }
  return $options;
}
