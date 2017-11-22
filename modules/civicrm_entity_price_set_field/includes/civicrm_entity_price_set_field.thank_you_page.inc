<?php
/**
 * @file
 * CiviCRM Entity Price Set Field, Thank You page generation
 */


/**
 * Helper function to setup thank you page FAPI
 *
 * @param $form
 * @param $form_state
 */
function _civicrm_entity_price_set_field_setup_event_thank_you_page_fapi(&$form, &$form_state) {
  $form['thank_you_page'] = array(
    '#type' => 'container',
    '#attributes' => array(
      'class' => array(
        'civicrm-entity-price-set-field-thank-you-page',
      ),
    ),
  );
  $markup = theme('civicrm_entity_price_set_field_price_field_display_form_thank_you_page', array(
      'form_data' => $form_state['storage']['registration_form'],
      'civicrm_event',
      'entity' => $form_state['event'],
      'price_set_data' => $form_state['price_set_data'],
      'heading' => !empty($form_state['event']->confirm_title) ? $form_state['event']->thankyou_title : 'Thank You Page',
      'profiles' => $form_state['profiles'],
    )
  );
  $form['thank_you_page']['message'] = array(
    '#type' => 'markup',
    '#markup' => $markup,
  );
}

/**
 * Theme function for Display Form -- Thank you page
 *
 * @param $vars
 * @return string
 */
function theme_civicrm_entity_price_set_field_price_field_display_form_thank_you_page($vars) {
  $html = '<h3>' . $vars['heading'] . '</h3>';
  if (!empty($vars['entity']->thankyou_text)) {
    $html .= $vars['entity']->thankyou_text;
  }

  foreach ($vars['form_data']['contacts'] as $id => $contact) {
    $html .= '<h3>' . 'Participant ' . ((int)$id + 1) . ':</h3>';
    // Price Set line items
    if (!empty($vars['form_data']['transaction']['line_items'][$id]) && count($vars['form_data']['transaction']['line_items'][$id])) {
      $header = array(
        'line_item' => 'Line Item',
        'line_item_value' => 'Choice',
        'quantity' => 'Quantity',
        'unit_price' => 'Unit Price',
        'line_total' => 'Line Total',
      );
      $rows = array();
      $row_count = 0;
      foreach ($vars['form_data']['transaction']['line_items'][$id] as $pf_id => $line_item) {
        $pfv_ids = array_keys($line_item);
        foreach($pfv_ids as $pfv_id) {
          $line_item_data = $line_item[$pfv_id];

          $rows[$row_count]['data']['line_item'] = $vars['price_set_data'][$id]['price_fields'][$pf_id]['pf_entity']->label;
          $rows[$row_count]['data']['line_item_value'] = $vars['price_set_data'][$id]['price_fields'][$pf_id]['price_field_values'][$pfv_id]->label;
          $rows[$row_count]['data']['quantity'] = $line_item_data['qty'];
          $rows[$row_count]['data']['unit_price'] = '$' . number_format($line_item_data['unit_price'], 2);
          $rows[$row_count]['data']['line_total'] = '$' . number_format($line_item_data['line_total'], 2);

          $row_count += 1;
        }
      }
      $html .= theme('table', array('header' => $header, 'rows' => $rows));
    }

    $html .= '<div class="profile-field"><span class="field-label">Participant Total Fees: </span><span class="field-item>">' . '$' . number_format($vars['form_data']['transaction']['fee_amounts'][$id], 2) . '</span></div>';

    if (!empty($contact['contact_info']) && count($contact['contact_info'])) {
      foreach ($contact['contact_info'] as $profile_id => $profile) {
        $pids = explode('-', $profile_id);
        $pid = $pids[1];
        $html .= '<h4>' . _civicrm_entity_profile_get_profile_title($pid) . ':</h4>';
        if (!empty($contact['contact_info'][$profile_id]['profile']) && count($contact['contact_info'][$profile_id]['profile'])) {
          foreach ($contact['contact_info'][$profile_id]['profile'] as $name => $value) {
            if (is_array($value)) {
              $temp_param = [];
              foreach ($value as $v) {
                if ($v !== 0) {
                  $temp_param[] = $v;
                }
              }
              if (!empty($temp_param)) {
                $output_value = implode(',', $temp_param);
              }
            }
            else {
              $output_value = $value;
            }
            $html .= '<div class="profile-field"><span class="field-label">' . $vars['profiles'][$profile_id][$name]['title'] . ':</span> <span class="field-item>">' . $output_value . '</span></div>';
          }
        }
      }
    }

  }


  $html .= '<h3 class="total"><strong class="label">Total: </strong>' . '$' . number_format($vars['form_data']['transaction']['total'], 2) . '</h3>';

  if (!empty($vars['entity']->thankyou_footer_text)) {
    $html .= $vars['entity']->thankyou_footer_text;
  }

  return $html;
}
