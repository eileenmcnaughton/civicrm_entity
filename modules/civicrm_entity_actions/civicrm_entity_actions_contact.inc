<?php

/**
 *
 * @return multitype:multitype:string boolean NULL
 */

function civicrm_entity_actions_contact_action_info() {
  return array(
    'civicrm_entity_actions_contact_add_to_group_action' => array(
      'label' => t('Add Contact to Group'),
      'type' => 'civicrm_contact',
      'configurable' => TRUE,
    ),
    'civicrm_entity_actions_contact_remove_from_group_action' => array(
      'label' => t('Remove Contact From Group'),
      'type' => 'civicrm_contact',
      'configurable' => TRUE,
    ),
    'civicrm_entity_actions_contact_add_entity_tag_action' => array(
      'label' => t('Tag Contact'),
      'type' => 'civicrm_contact',
      'configurable' => TRUE,
    ),
      'civicrm_entity_actions_contact_add_relationship_with_contact_action' => array(
      'label' => t('Add relationship with Contact'),
      'type' => 'civicrm_contact',
      'configurable' => TRUE,
    ),
    //Crud operations
    'civicrm_entity_actions_contact_view_action' => array(
        'type' => 'civicrm_contact',
        'label' => t('View Contact'),
        'configurable' => FALSE,
        'behavior' => array('views_property'),
        'triggers' => array('any'),
    ),
    'civicrm_entity_actions_contact_add_action' => array(
        'type' => 'civicrm_contact',
        'label' => t('Add Contact'),
        'configurable' => FALSE,
        'behavior' => array('creates_property'),
        'triggers' => array('any'),
    ),
    'civicrm_entity_actions_contact_edit_action' => array(
        'type' => 'civicrm_contact',
        'label' => t('Edit Contact'),
        'configurable' => FALSE,
        'behavior' => array('changes_property'),
        'triggers' => array('any'),
    ),
    'civicrm_entity_actions_contact_delete_action' => array(
        'type' => 'civicrm_contact',
        'label' => t('Delete Contact'),
        'configurable' => FALSE,
        'behavior' => array('deletes_property'),
        'triggers' => array('any'),
    ),
  );
}

function civicrm_entity_actions_contact_add_action($entity, $context = array()) {
  $base_url = str_replace('_', '-', $context['entity_type']); // civicrm-contact
  drupal_goto($base_url . '/add');
}

function civicrm_entity_actions_contact_view_action($entity, $context = array()) {
  $info = entity_get_info($context['entity_type']);
  $entity_id = $entity->{$info['entity keys']['id']};
  $base_url = str_replace('_', '-', $context['entity_type']); // civicrm-contact
  drupal_goto($base_url . '/' . $entity_id);
}

function civicrm_entity_actions_contact_edit_action($entity, $context = array()) {
  $info = entity_get_info($context['entity_type']);
  $entity_id = $entity->{$info['entity keys']['id']};
  $base_url = str_replace('_', '-', $context['entity_type']); // civicrm-contact
  drupal_goto($base_url . '/' . $entity_id . '/edit');
}

function civicrm_entity_actions_contact_delete_action($entity, $context = array()) {
  $info = entity_get_info($context['entity_type']);
  $entity_id = $entity->{$info['entity keys']['id']};
  $base_url = str_replace('_', '-', $context['entity_type']); // civicrm-contact
  drupal_goto($base_url . '/' . $entity_id . '/delete');
}

/**
 *
 * @param unknown $object
 * @param array $context
 */

function civicrm_entity_actions_contact_add_to_group_action(&$object, $context = array()) {
  if (!civicrm_initialize()) {
    return;
  }
  civicrm_api3('group_contact', 'create', array(
      'contact_id' => $object->id,
      'group_id' => $context['state']['entity_id'],
      'status' => 'Added',
    )
  );
}

/**
 *
 * @param array $context
 * @return multitype:string unknown
 */

function civicrm_entity_actions_contact_add_to_group_action_form( $context = array()) {
  $form['civicrm_contact']['group_id'] = array(
    '#type' => 'entityreference',
    '#title' => t('Add To Group'),
    '#era_entity_type' => 'civicrm_group',
  );
  return $form;
}

/**
 *
 * @param array $context
 * @return multitype:string unknown
 */

function civicrm_entity_actions_contact_add_to_group_action_submit($form, $form_state) {
  return array('state' => $form_state['values']['group_id']);
}

/**
 *
 * @param unknown $object
 * @param array $context
 */

function civicrm_entity_actions_contact_remove_from_group_action(&$object, $context = array()) {
  if (!civicrm_initialize()) {
    return;
  }
  civicrm_api3('group_contact', 'create', array(
  'contact_id' => $object->id,
  'group_id' => $context['state']['entity_id'],
  'status' => 'Removed',
  )
  );
}

/**
 *
 * @param array $context
 * @return multitype:string unknown
 */

function civicrm_entity_actions_contact_remove_from_group_action_form( $context = array()) {
  $form['civicrm_contact']['group_id'] = array(
      '#type' => 'entityreference',
      '#title' => t('Remove From'),
      '#era_entity_type' => 'civicrm_group',
  );
  return $form;
}

/**
 *
 * @param array $context
 * @return multitype:string unknown
 */

function civicrm_entity_actions_contact_remove_from_group_action_submit($form, $form_state) {
  return array('state' => $form_state['values']['group_id']);
}

/**
 *
 * @param unknown $object
 * @param array $context
 */

function civicrm_entity_actions_contact_add_entity_tag_action(&$object, $context = array()) {
  if (!civicrm_initialize()) {
    return;
  }
  civicrm_api3('entity_tag', 'create', array(
    'contact_id' => $object->id,
    'tag_id' => $context['state']['entity_id'],
  )
  );
}

/**
 *
 * @param array $context
 * @return multitype:string unknown
 */

function civicrm_entity_actions_contact_add_entity_tag_action_form( $context = array()) {
  $form['civicrm_contact']['tag_id'] = array(
      '#type' => 'entityreference',
      '#title' => t('Add Tag'),
      '#era_entity_type' => 'civicrm_tag',
  );
  return $form;
}

/**
 *
 * @param array $context
 * @return multitype:string unknown
 */

function civicrm_entity_actions_contact_add_entity_tag_action_submit($form, $form_state) {
  return array('state' => $form_state['values']['tag_id']);
}

/**
 *
 * @param unknown $object
 * @param array $context
 */

function civicrm_entity_actions_contact_add_relationship_with_contact_action(&$object, $context = array()) {
  if (!civicrm_initialize()) {
    return;
  }
  $contact_id_b = NULL;
  if (!empty($context['state']['contact_id_b_me'])) {
    $contact_id_b = trim(strip_tags(views_embed_view('user_contact', 'block')));;
  }
  elseif (!empty($context['state']['contact_id_b_my_team'])) {
    $contact_id_b = trim(strip_tags(views_embed_view('user_team', 'block')));;
  }
  else {
    $contact_id_b = $context['state']['contact_id_b']['entity_id'];
  }
  civicrm_api3('relationship', 'create', array(
      'contact_id_a' => $object->id,
      'contact_id_b' => $contact_id_b,
      'relationship_type_id' => $context['state']['relationship_type_id']['entity_id']
    )
  );
}

/**
 *
 * @param array $context
 * @return multitype:string unknown
 */

function civicrm_entity_actions_contact_add_relationship_with_contact_action_form( $context = array()) {
  $form['civicrm_contact']['relationship_type_id'] = array(
      '#type' => 'entityreference',
      '#title' => t('Add Relationship'),
      '#era_entity_type' => 'civicrm_relationship_type',
  );
  $form['civicrm_contact']['contact_id_b'] = array(
      '#type' => 'entityreference',
      '#title' => t('Relationship with contact'),
      '#era_entity_type' => 'civicrm_contact',
  );
  $form['civicrm_contact']['contact_id_b_me'] = array(
      '#type' => 'checkbox',
      '#title' => t('Relationship with me'),
  );
  $form['civicrm_contact']['contact_id_b_my_team'] = array(
      '#type' => 'checkbox',
      '#title' => t('Relationship with my team'),
  );
  return $form;
}

/**
 *
 * @param array $context
 * @return multitype:string unknown
 */

function civicrm_entity_actions_contact_add_relationship_with_contact_action_submit($form, $form_state) {
  return array('state' => $form_state['values']);
}

