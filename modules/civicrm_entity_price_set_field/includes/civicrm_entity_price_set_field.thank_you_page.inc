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

  /*$header = array(
    'fee' => 'Fee',
    'name' => 'Name',
    'email' => 'Email',
    'phone' => 'Phone',
  );*/
  //$rows = array();

  foreach ($vars['form_data']['contacts'] as $id => $contact) {
    $html .= '<h3>' . 'Participant ' . ((int)$id + 1) . ':</h3>';
    $html .= '<div class="profile-field"><span class="field-label">Fee: </span><span class="field-item>">' . '$' . number_format($vars['form_data']['transaction']['fee_amounts'][$id], 2) . '</span></div>';
    if (count($contact['contact_info'])) {
      foreach($contact['contact_info'] as $profile_id => $profile) {
        $pids = explode('-', $profile_id);
        $pid = $pids[1];
        $html .= '<h4>' . _civicrm_entity_price_set_field_get_profile_title($pid) . ':</h4>';
        if (count($contact['contact_info'][$profile_id]['profile'])) {
          foreach ($contact['contact_info'][$profile_id]['profile'] as $name => $value) {
            $html .= '<div class="profile-field"><span class="field-label">' . $vars['profiles'][$profile_id][$name]['title'] . ':</span> <span class="field-item>">' . $value . '</span></div>';
          }
        }
      }
    }
    /*
    $rows[$id]['data']['fee'] = '$' . number_format($vars['form_data']['transaction']['fee_amounts'][$id], 2);
    $rows[$id]['data']['name'] = implode(' ', array(
      $contact['contact_info']['first_name'],
      $contact['contact_info']['last_name'],
    ));
    $rows[$id]['data']['email'] = $contact['contact_info']['email'];
    $rows[$id]['data']['phone'] = $contact['contact_info']['phone'];*/
  }
  //$html .= '<h4>' . 'Registered participants:' . '</h4>';
  //$html .= theme('table', array('header' => $header, 'rows' => $rows));

  $html .= '<h3 class="total"><strong class="label">Total: </strong>' . '$' . number_format($vars['form_data']['transaction']['total'], 2) . '</h3>';

  if (!empty($vars['entity']->thankyou_footer_text)) {
    $html .= $vars['entity']->thankyou_footer_text;
  }

  return $html;
}
