<?php

/**
 * @file
 * CiviCRM Entity admin settings form callbacks
 */

/**
 * Settings form callback function
 *
 * @param $form
 * @param $form_state
 * @return mixed
 */
function civicrm_entity_admin_settings_form($form, &$form_state) {
  $available_entities = _civicrm_entity_available_entities();

  $available_entities_option_set = _civicrm_entity_available_entities_get_option_set($available_entities);

  $options = array();
  foreach ($available_entities as $entity_type => $api_entity) {
    $options[$entity_type] = ucwords(str_replace('_', ' ', $api_entity));
  }

  $default_value = variable_get('civicrm_entity_admin_enabled_entities', $available_entities_option_set);

  $form['civicrm_entity_admin_enabled_entities']  = array(
    '#type' => 'fieldset',
    '#title' => 'Enabled Entities',
    '#description' => 'Check all CiviCRM API entities that you would like to expose as Drupal entity types. If a checkbox is disabled, it is because a submodule requires it.' . "<br/>" .' This page does not check for existing Drupal Rules, Views, or 3rd party modules using CiviCRM entity types. Ask your web consultant if unsure.',
    '#collapsible' => FALSE,
    '#tree' => TRUE,
  );

  foreach ($options as $drupal_entity_type => $entity_type_label) {
    $form['civicrm_entity_admin_enabled_entities'][$drupal_entity_type] = array(
      '#type' => 'checkbox',
      '#title' => $entity_type_label,
      '#default_value' => !empty($default_value[$drupal_entity_type]) ?  1 : 0,
    );
  }

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save settings'),
  );

  return $form;
}

/**
 * Form submit callback for the CiviCRM Entity admin config page
 *
 * @param $form
 * @param $form_state
 */
function civicrm_entity_admin_settings_form_submit($form, $form_state) {
  $settings = array();
  foreach ($form_state['values']['civicrm_entity_admin_enabled_entities'] as $drupal_entity_type => $value) {
    $settings[$drupal_entity_type] = !empty($value) ? $drupal_entity_type : 0;
  }

  variable_set('civicrm_entity_admin_enabled_entities', $settings);
  drupal_flush_all_caches();
}
