<?php

/**
 *
 * @return multitype:multitype:string boolean NULL
 */

function civicrm_entity_actions_relationship_type_action_info() {
  return array(
    //Crud operations
    'civicrm_entity_actions_relationship_type_view_action' => array(
        'type' => 'civicrm_relationship_type',
        'label' => t('View Relationship_type'),
        'configurable' => FALSE,
        'behavior' => array('views_property'),
        'triggers' => array('any'),
    ),
    'civicrm_entity_actions_relationship_type_add_action' => array(
        'type' => 'civicrm_relationship_type',
        'label' => t('Add Relationship_type'),
        'configurable' => FALSE,
        'behavior' => array('creates_property'),
        'triggers' => array('any'),
    ),
    'civicrm_entity_actions_relationship_type_edit_action' => array(
        'type' => 'civicrm_relationship_type',
        'label' => t('Edit Relationship_type'),
        'configurable' => FALSE,
        'behavior' => array('changes_property'),
        'triggers' => array('any'),
    ),
    'civicrm_entity_actions_relationship_type_delete_action' => array(
        'type' => 'civicrm_relationship_type',
        'label' => t('Delete Relationship_type'),
        'configurable' => FALSE,
        'behavior' => array('deletes_property'),
        'triggers' => array('any'),
    ),
  );
}

function civicrm_entity_actions_relationship_type_add_action($entity, $context = array()) {
  $base_url = str_replace('_', '-', $context['entity_type']); // civicrm-relationship_type
  drupal_goto($base_url . '/add');
}

function civicrm_entity_actions_relationship_type_view_action($entity, $context = array()) {
  $info = entity_get_info($context['entity_type']);
  $entity_id = $entity->{$info['entity keys']['id']};
  $base_url = str_replace('_', '-', $context['entity_type']); // civicrm-relationship_type
  drupal_goto($base_url . '/' . $entity_id);
}

function civicrm_entity_actions_relationship_type_edit_action($entity, $context = array()) {
  $info = entity_get_info($context['entity_type']);
  $entity_id = $entity->{$info['entity keys']['id']};
  $base_url = str_replace('_', '-', $context['entity_type']); // civicrm-relationship_type
  drupal_goto($base_url . '/' . $entity_id . '/edit');
}

function civicrm_entity_actions_relationship_type_delete_action($entity, $context = array()) {
  $info = entity_get_info($context['entity_type']);
  $entity_id = $entity->{$info['entity keys']['id']};
  $base_url = str_replace('_', '-', $context['entity_type']); // civicrm-relationship_type
  drupal_goto($base_url . '/' . $entity_id . '/delete');
}