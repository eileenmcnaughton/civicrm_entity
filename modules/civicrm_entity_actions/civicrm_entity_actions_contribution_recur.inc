<?php

/**
 *
 * @return multitype:multitype:string boolean NULL
 */

function civicrm_entity_actions_contribution_recur_action_info() {
  return array(
    //Crud operations
    'civicrm_entity_actions_contribution_recur_view_action' => array(
        'type' => 'civicrm_contribution_recur',
        'label' => t('View Contribution Recur'),
        'configurable' => FALSE,
        'behavior' => array('views_property'),
        'triggers' => array('any'),
    ),
    'civicrm_entity_actions_contribution_recur_add_action' => array(
        'type' => 'civicrm_contribution_recur',
        'label' => t('Add Contribution Recur'),
        'configurable' => FALSE,
        'behavior' => array('creates_property'),
        'triggers' => array('any'),
    ),
    'civicrm_entity_actions_contribution_recur_edit_action' => array(
        'type' => 'civicrm_contribution_recur',
        'label' => t('Edit Contribution Recur'),
        'configurable' => FALSE,
        'behavior' => array('changes_property'),
        'triggers' => array('any'),
    ),
    'civicrm_entity_actions_contribution_recur_delete_action' => array(
        'type' => 'civicrm_contribution_recur',
        'label' => t('Delete Contribution Recur'),
        'configurable' => FALSE,
        'behavior' => array('deletes_property'),
        'triggers' => array('any'),
    ),
  );
}

function civicrm_entity_actions_contribution_recur_add_action($entity, $context = array()) {
  $base_url = str_replace('_', '-', $context['entity_type']); // civicrm-contribution
  drupal_goto($base_url . '/add');
}

function civicrm_entity_actions_contribution_recur_view_action($entity, $context = array()) {
  $info = entity_get_info($context['entity_type']);
  $entity_id = $entity->{$info['entity keys']['id']};
  $base_url = str_replace('_', '-', $context['entity_type']); // civicrm-contribution
  drupal_goto($base_url . '/' . $entity_id);
}

function civicrm_entity_actions_contribution_recur_edit_action($entity, $context = array()) {
  $info = entity_get_info($context['entity_type']);
  $entity_id = $entity->{$info['entity keys']['id']};
  $base_url = str_replace('_', '-', $context['entity_type']); // civicrm-contribution
  drupal_goto($base_url . '/' . $entity_id . '/edit');
}

function civicrm_entity_actions_contribution_recur_delete_action($entity, $context = array()) {
  $info = entity_get_info($context['entity_type']);
  $entity_id = $entity->{$info['entity keys']['id']};
  $base_url = str_replace('_', '-', $context['entity_type']); // civicrm-contribution
  drupal_goto($base_url . '/' . $entity_id . '/delete');
}