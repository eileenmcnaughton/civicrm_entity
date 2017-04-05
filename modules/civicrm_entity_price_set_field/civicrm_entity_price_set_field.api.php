<?php

/**
 * @file
 * Api information provided by CiviCRM Entity Price Set Field module
 */

/**
 * @mainpage
 * API Reference Documentation for the CiviCRM Entity Price Set Field module.
 *
 * There are 3 hooks available.
 *
 * The form can be altered via hook_form_alter().
 *
 * The confirmation page and thank you page are theme functions that work with pre_process functions according to standard Drupal conventions
 *
 * To override theme function implement
 * Confirmation Page
 * MYTHEME_civicrm_entity_price_set_field_price_field_display_form_confirmation_page($variables) in your theme
 * Thank you page
 * MYTHEME_civicrm_entity_price_set_field_price_field_display_form_thank_you_page($variables)
 */


/**
 * Add payment processor handlers for CiviCRM Entity Price Set Field
 * The API supports the following keys
 *
 *
 * -- payment_processor_type the unique name of the payment processor type (the name column of the civicrm_payment_processor_type table)
 * -- callback -- The function to invoke that makes the call to the payment processor
 *
 * see civicrm_entity_price_set_field_transact_payment_processing($display, $processor, $processor_type, $price_set_data, $entity_type, $entity, $contacts, $form_state)
 * in includes/civicrm_entity_price_set_field.transaction.inc for an example callback using the CiviCRM API Contribution transact action
 *
 * It really can be anything, doesn't necessarily have to use a CiviCRM Payment Processor.
 *
 * @return array
 */
function hook_civicrm_entity_price_set_field_processor_info() {
  return array(
    'dummy' => array(
      'payment_processor_type' => 'Dummy',
      'callback' => 'civicrm_entity_price_set_field_transact_payment_processing',
    ),
  );
}

/**
 * Alter the CiviCRM payment processor handler info
 *
 * @param $info
 */
function hook_civicrm_entity_price_set_field_processor_info_alter(&$info) {
  $info['dummy']['callback'] = 'mymodule_function_name';
}

/**
 * Alter the $total array which contains the total and each line item
 *
 * @param $total
 */
function hook_civicrm_entity_price_set_field_calculate_total($total) {
  // add 10%
  $total['total'] = $total['total'] * 1.10;
  foreach ($total['line_items'] as $index => $price_fields) {
    foreach ($price_fields as $pf_id => $line_item) {
      $line_item['line_total'] = $line_item['unit_price'] = $line_item['unit_price'] * 1.10;
    }
  }
}

/**
 * Implements hook_civicrm_entity_price_set_field_registration_form_price_set_data_alter().
 *
 * In this example, for events, every price field value for each price field is reduced in price by half
 *
 * @param $price_set_data
 * @param $context
 */
function hook_civicrm_entity_price_set_field_registration_form_price_set_data_alter(&$price_set_data, $context) {
  if ($context['entity_type'] == 'civicrm_event') {
    if (!empty($price_set_data['price_fields'])) {
      foreach ($price_set_data['price_fields'] as $pf_id => $pf_data) {
        if (!empty($pf_data['price_field_values'])) {
          foreach ($pf_data['price_field_values'] as $pfv_id => $pfv_data) {
            $price_set_data['price_fields'][$pf_id]['price_field_values'][$pfv_id]->amount = $price_set_data['price_fields'][$pf_id]['price_field_values'][$pfv_id]->amount / 2;
          }
        }
      }
    }
  }
}

/**
 * Implements hook_civicrm_entity_price_set_field_registration_access_callback_info().
 *
 * @return array
 */
function hook_civicrm_entity_price_set_field_registration_access_callback_info() {
  return array(
    'civicrm_customs' => array(
      'callback' => 'civicrm_customs_event_registration_access_callback',
    )
  );
}

/* example callback

// intended to be able to customize access to field registration form, in a negative way
// user still requires 'register for events' CiviCRM permission to access form
function civicrm_customs_event_registration_access_callback($entity_type, $entity, $field, $instance, $account) {
  if ($entity_type == 'civicrm_event') {
    // allow if the user can edit events
    if(user_access('edit all events', $account)) {
      return TRUE;
    }
    // disallow access if the user doesn't have role id 5 and the event type is 7 or 9
    elseif(!isset($account->roles[5]) && (!empty($entity->event_type_id) && in_array($entity->event_type_id, array(7, 9)))) {
      return FALSE;
    }
  }
}*/
