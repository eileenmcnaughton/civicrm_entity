<?php

/**
 *
 * @return multitype:multitype:string boolean NULL
 */

function civicrm_entity_actions_activity_action_info() {
  return array(
    'civicrm_entity_actions_activity_assign_action' => array(
      'label' => t('Assign Activity'),
      'type' => 'civicrm_activity',
      'configurable' => TRUE,
    ),
    'civicrm_entity_actions_activity_unassign_action' => array(
      'label' => t('Unassign Activity'),
      'type' => 'civicrm_activity',
      'configurable' => FALSE,
    ),
    //Crud operations
    'civicrm_entity_actions_activity_view_action' => array(
        'type' => 'civicrm_activity',
        'label' => t('View Activity'),
        'configurable' => FALSE,
        'behavior' => array('views_property'),
        'triggers' => array('any'),
    ),
    'civicrm_entity_actions_activity_add_action' => array(
        'type' => 'civicrm_activity',
        'label' => t('Add Activity'),
        'configurable' => FALSE,
        'behavior' => array('creates_property'),
        'triggers' => array('any'),
    ),
    'civicrm_entity_actions_activity_edit_action' => array(
        'type' => 'civicrm_activity',
        'label' => t('Edit Activity'),
        'configurable' => FALSE,
        'behavior' => array('changes_property'),
        'triggers' => array('any'),
    ),
    'civicrm_entity_actions_activity_delete_action' => array(
        'type' => 'civicrm_activity',
        'label' => t('Delete Activity'),
        'configurable' => FALSE,
        'behavior' => array('deletes_property'),
        'triggers' => array('any'),
    ),
  );
}

function civicrm_entity_actions_activity_add_action($entity, $context = array()) {
  $base_url = str_replace('_', '-', $context['entity_type']); // civicrm-activity
  drupal_goto($base_url . '/add');
}

function civicrm_entity_actions_activity_view_action($entity, $context = array()) {
  $info = entity_get_info($context['entity_type']);
  $entity_id = $entity->{$info['entity keys']['id']};
  $base_url = str_replace('_', '-', $context['entity_type']); // civicrm-activity
  drupal_goto($base_url . '/' . $entity_id);
}

function civicrm_entity_actions_activity_edit_action($entity, $context = array()) {
  $info = entity_get_info($context['entity_type']);
  $entity_id = $entity->{$info['entity keys']['id']};
  $base_url = str_replace('_', '-', $context['entity_type']); // civicrm-activity
  drupal_goto($base_url . '/' . $entity_id . '/edit');
}

function civicrm_entity_actions_activity_delete_action($entity, $context = array()) {
  $info = entity_get_info($context['entity_type']);
  $entity_id = $entity->{$info['entity keys']['id']};
  $base_url = str_replace('_', '-', $context['entity_type']); // civicrm-activity
  drupal_goto($base_url . '/' . $entity_id . '/delete');
}

/**
 *
 * @param unknown $object
 * @param array $context
 */

function civicrm_entity_actions_activity_assign_action(&$object, $context = array()) {
  if (!civicrm_initialize()) {
    return;
  }
  civicrm_api3('activity', 'create', array(
    'id' => $object->id,
    'assignee_contact_id' => $context['state']['entity_id'])
  );
}

/**
 *
 * @param array $context
 * @return multitype:string unknown
 */

function civicrm_entity_actions_activity_assign_action_form($context = array()) {
  $entities = _civicrm_entity_enabled_entities();
  $entity = $entities[$context['entity_type']];
  $form[$entity]['assignee_contact_id'] = array(
      '#type' => 'entityreference',
      '#title' => t('Assign To'),
      '#era_entity_type' => 'civicrm_contact',
  );
  return $form;
}

/**
 *
 * @param unknown $form
 * @param unknown $form_state
 * @return multitype:NULL
 */

function civicrm_entity_actions_activity_assign_action_submit($form, $form_state) {
  return array('state' => $form_state['values']['assignee_contact_id']);
}

/**
 *
 * @param unknown $object
 * @param array $context
 */

function civicrm_entity_actions_activity_unassign_action(&$object, $context = array()) {
  if (!civicrm_initialize()) {
    return;
  }
  civicrm_api3('activity', 'create', array(
    'id' => $object->id,
    'deleteActivityAssignment' => TRUE)
  );
}
