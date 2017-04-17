<?php
/**
 * @file
 * CiviCRM Entity Price Set Field, Credit Card Transaction code
 */

/**
 * CiviCRM Entity Price Set Field Get Processor Info
 *
 * Sets up info and alter hooks to gather payment processor handlers
 *
 * @param null $id
 * @return array
 */
function civicrm_entity_price_set_field_get_processor_handler_info($id = NULL) {
  $results = array();
  foreach (module_implements('civicrm_entity_price_set_field_processor_info') as $module) {
    // Calling all modules implementing hook_hook_name and
    // Returning results than pushing them into the $result array:
    $result = module_invoke($module, 'civicrm_entity_price_set_field_processor_info');
    foreach ($result as $id => $r) {
      if (!empty($r['callback'])) {
        $results[$id] = $result;
      }
    }
  }

  drupal_alter('civicrm_entity_price_set_field_processor_info', $results);

  if ($id && !empty($result[$id])) {
    return $results[$id];
  }
  else {
    return $results;
  }
}

/**
 * Helper function to calculate line items and transaction totals
 *
 * @param $price_set_data
 * @param $entity_type
 * @param $entity
 * @param $form_state
 * @return float
 */
function _civicrm_entity_price_set_field_calculate_total($price_set_data, $entity_type, $entity, $form_state) {
  $total = array();
  $total['line_items'] = array();
  $total['fee_amounts'] = array();
  $total['total'] = 0.00;
  //calculate the total, each line item and its total, and each "fee amount" (sum of all line items for a participant)
  // TODO, when is participant_count something other than 0?
  if (!empty($entity->is_monetary)) {
    foreach ($form_state['contacts'] as $id => $contact) {
      $total['fee_amounts'][$id] = 0.00;
      if (!empty($contact['price_set'])) {
        foreach ($contact['price_set'] as $pf_id => $value) {
          $line_item = array();
          $line_item['price_field_id'] = $pf_id;


          switch ($price_set_data[$id]['price_fields'][$pf_id]['pf_entity']->html_type) {
            case 'Radio':
              if (!empty($value['price_field_values']) && !is_array($value['price_field_values']) && is_numeric($value['price_field_values'])) {
                $line_item['label'] = $price_set_data[$id]['price_fields'][$pf_id]['price_field_values'][$value['price_field_values']]->label;

                if (!empty($price_set_data[$id]['price_fields'][$pf_id]['price_field_values'][$value['price_field_values']]->financial_type_id)) {
                  $line_item['financial_type_id'] = $price_set_data[$id]['price_fields'][$pf_id]['price_field_values'][$value['price_field_values']]->financial_type_id;
                }
                elseif (!empty($entity->financial_type_id)) {
                  $line_item['financial_type_id'] = $entity->financial_type_id;
                }
                $line_item['price_field_value_id'] = $value['price_field_values'];
                $line_item['unit_price'] = $line_item['line_total'] = $price_set_data[$id]['price_fields'][$pf_id]['price_field_values'][$value['price_field_values']]->amount;
                $line_item['participant_count'] = 0;
                $line_item['qty'] = 1;
                $total['total'] += $line_item['line_total'];
                $total['fee_amounts'][$id] += $line_item['line_total'];
                $total['line_items'][$id][$pf_id][$line_item['price_field_value_id']] = $line_item;
              }
              break;
            case 'Select':
              if (!empty($value['price_field_values']) && !is_array($value['price_field_values']) && is_numeric($value['price_field_values'])) {
                $line_item['label'] = $price_set_data[$id]['price_fields'][$pf_id]['price_field_values'][$value['price_field_values']]->label;

                if (!empty($price_set_data[$id]['price_fields'][$pf_id]['price_field_values'][$value['price_field_values']]->financial_type_id)) {
                  $line_item['financial_type_id'] = $price_set_data[$id]['price_fields'][$pf_id]['price_field_values'][$value['price_field_values']]->financial_type_id;
                }
                elseif (!empty($entity->financial_type_id)) {
                  $line_item['financial_type_id'] = $entity->financial_type_id;
                }
                $line_item['price_field_value_id'] = $value['price_field_values'];
                $line_item['unit_price'] = $line_item['line_total'] = $price_set_data[$id]['price_fields'][$pf_id]['price_field_values'][$value['price_field_values']]->amount;
                $line_item['participant_count'] = 0;
                $line_item['qty'] = 1;
                $total['total'] += $line_item['line_total'];
                $total['fee_amounts'][$id] += $line_item['line_total'];
                $total['line_items'][$id][$pf_id][$line_item['price_field_value_id']] = $line_item;
              }
              break;
            case 'CheckBox':
              if (!empty($value['price_field_values']) && is_array($value['price_field_values'])) {
                foreach ($value['price_field_values'] as $pfv_id => $v) {
                  if ($v) {
                    $line_item = array();
                    $line_item['label'] = $price_set_data[$id]['price_fields'][$pf_id]['price_field_values'][$pfv_id]->label;

                    if (!empty($price_set_data[$id]['price_fields'][$pf_id]['price_field_values'][$pfv_id]->financial_type_id)) {
                      $line_item['financial_type_id'] = $price_set_data[$id]['price_fields'][$pf_id]['price_field_values'][$pfv_id]->financial_type_id;
                    }
                    elseif (!empty($entity->financial_type_id)) {
                      $line_item['financial_type_id'] = $entity->financial_type_id;
                    }
                    $line_item['price_field_id'] = $pf_id;
                    $line_item['unit_price'] = 0.0;
                    $line_item['price_field_value_id'] = $pfv_id;
                    $line_item['line_total'] = $line_item['unit_price'] = $price_set_data[$id]['price_fields'][$pf_id]['price_field_values'][$pfv_id]->amount;
                    $line_item['qty'] = 1;
                    $line_item['participant_count'] = 0;
                    $total['total'] += $line_item['line_total'];
                    $total['fee_amounts'][$id] += $line_item['line_total'];
                    $total['line_items'][$id][$pf_id][$pfv_id] = $line_item;
                  }
                }
                continue;
              }
              break;
            case 'Text':
              if (!empty($value['price_field_values']) && !is_array($value['price_field_values']) && is_numeric($value['price_field_values'])) {
                $pfv_ids = array_keys($price_set_data[$id]['price_fields'][$pf_id]['price_field_values']);
                $pfv_id = $pfv_ids[0];
                $line_item['label'] = $price_set_data[$id]['price_fields'][$pf_id]['price_field_values'][$pfv_id]->label;
                $line_item['price_field_id'] = $pf_id;
                $line_item['price_field_value_id'] = $pfv_id;
                $line_item['unit_price'] = $price_set_data[$id]['price_fields'][$pf_id]['price_field_values'][$pfv_id]->amount;
                $line_item['line_total'] = $line_item['unit_price'] * $value['price_field_values'];
                $line_item['qty'] = $value['price_field_values'];
                $line_item['participant_count'] = 0;
                $total['line_items'][$id][$pf_id][$line_item['price_field_value_id']] = $line_item;
                $total['fee_amounts'][$id] += $line_item['line_total'];
                $total['fee_levels'][$id] = $price_set_data[$id]['price_fields'][$pf_id]['price_field_values'][$pfv_id]->label;
                $total['total'] += $line_item['line_total'];
              }
              break;
          }

        }
      }
    }
  }
  // invoke alter hook to let people alter the total
  $context = array(
    'participant_count' => $id,
    'price_set_data' => $price_set_data[$id],
    'entity_type' => $entity_type,
    'entity' => $entity,
    'form_state' => $form_state,
  );
  drupal_alter('civicrm_entity_price_set_field_calculate_total', $total, $context);
  return $total;
}

/**
 * Run the credit card transaction. Find a handler for the payment processor type if one is available and invoke its callback
 *
 * @param $price_set_data
 * @param $entity_type
 * @param $entity
 * @param $form_state
 * @return bool|mixed
 */
function _civicrm_entity_price_set_field_run_cc_transaction($display, $price_set_data, $entity_type, $entity, $contacts, $form_state) {
  $civicrm_entity_prise_set_field_processor_types = civicrm_entity_price_set_field_get_processor_handler_info();
  if (!empty($entity->payment_processor)) {
    $processor = entity_load_single('civicrm_payment_processor', $entity->payment_processor);
    if (!empty($processor->payment_processor_type_id)) {
      $processor_type = entity_load_single('civicrm_payment_processor_type', $processor->payment_processor_type_id);
      foreach ($civicrm_entity_prise_set_field_processor_types as $id => $type) {
        if ($type['payment_processor_type'] == $processor_type->name) {
          $invoke_type = $type;
        }
      }
      if (!empty($invoke_type)) {
        $result = call_user_func($invoke_type['callback'], $display, $processor, $processor_type, $price_set_data, $entity_type, $entity, $contacts, $form_state);
      }
      if (!empty($result)) {
        // trigger rule event with all the pertinent data here
        if(!empty($result['contribution']->id)) {
          $rule_variables = array(
            'contribution' => $result['contribution'],
          );
          
          rules_invoke_event_by_args('civicrm_entity_price_set_field_successful_cc_transaction', $rule_variables);
        }
        return $result;
      }
    }
  }
  return FALSE;
}


/**
 * Implements hook_civicrm_entity_price_set_field_processor_info().
 *
 * Dummy processor
 *
 */
function civicrm_entity_price_set_field_civicrm_entity_price_set_field_processor_info() {
  return array(
    'dummy' => array(
      'payment_processor_type' => 'Dummy',
      'callback' => 'civicrm_entity_price_set_field_transact_payment_processing',
    ),
    'authnet' => array(
      'payment_processor_type' => 'AuthNet',
      'callback' => 'civicrm_entity_price_set_field_transact_payment_processing',
    ),
    'payflowpro' => array(
      'payment_processor_type' => 'PayflowPro',
      'callback' => 'civicrm_entity_price_set_field_transact_payment_processing',
    ),
    'paypal' => array(
      'payment_processor_type' => 'Pay Pal',
      'callback' => 'civicrm_entity_price_set_field_transact_payment_processing',
    ),
    'iats' => array(
      'payment_processor_type' => 'iATS Payments Credit Card',
      'callback' => 'civicrm_entity_price_set_field_transact_payment_processing',
    ),
  );
}

/**
 * Call back for Dummy processor cc transaction processing
 *
 * @param $processor
 * @param $processor_type
 * @param $price_set_data
 * @param $entity_type
 * @param $entity
 * @param $contacts
 * @param $form_state
 * @return bool|array
 */
function civicrm_entity_price_set_field_transact_payment_processing($display, $processor, $processor_type, $price_set_data, $entity_type, $entity, $contacts, $form_state) {
  // Set the required params for the payment processor
  $params = array(
    'sequential' => 1,
    'financial_type_id' => $entity->financial_type_id,
    'total_amount' => $form_state['transaction']['total'],
    'contact_id' => $contacts[0]->id,
    'payment_processor' => $processor->id,
    'payment_processor_id' => $processor->id,
    'credit_card_number' => $form_state['registration_cc_block']['cc_number'],
    'cvv2' => $form_state['registration_cc_block']['cvv'],
    'month' => $form_state['registration_cc_block']['cc_exp']['month'],
    'year' => $form_state['registration_cc_block']['cc_exp']['year'],
    'invoice_id' => md5(uniqid(rand(), TRUE)),
    'skipLineItem' => 1,
    'source' => 'CiviCRM Entity Price Set Field -- Event Registration',
    'billing_first_name' => $form_state['registration_cc_block']['first_name_on_card'],
    'billing_last_name' => $form_state['registration_cc_block']['last_name_on_card'],
  );

  $params['currency'] = $params['currencyID'] = !empty($entity->currency) ? $entity->currency : 'USD';

  if (!empty($display['settings']['test_transaction'])) {
    $params['is_test'] = 1;
  }
  $result = _civicrm_entity_price_set_field_api_transact_call($params);

  if (!empty($result['error'])) {
    drupal_set_message($result['error']);
    return FALSE;
  }

  return $result;
}

/**
 * Utility function to make a transact Contribution API call
 *
 * Accepts a array of API parameters and returns a Drupal CivicrmEntity object if successful
 *
 * @param $params
 * @return array
 */
function _civicrm_entity_price_set_field_api_transact_call($params) {
  $result = array();
  try {
    $api_contribution = civicrm_api3('Contribution', 'transact', $params);
  } catch (CiviCRM_API3_Exception $e) {
    return array('error' => $e->getMessage());
  }

  if (!empty($api_contribution['values'][0])) {
    $result['contribution'] = new CivicrmEntity($api_contribution['values'][0], 'civicrm_contribution');
  }

  return $result;
}