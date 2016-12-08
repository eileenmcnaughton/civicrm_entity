<?php
/**
 * @file
 * CiviCRM Entity Price Set Field, Confirmation page generation
 */


/**
 * Helper function to setup event confirmation page FAPI
 *
 * @param $form
 * @param $form_state
 */
function _civicrm_entity_price_set_field_setup_event_confirmation_page_fapi(&$form, &$form_state) {
  $form['confirmation_page'] = array(
    '#type' => 'container',
    '#attributes' => array(
      'class' => array(
        'civicrm-entity-price-set-field-confirmation-page',
      ),
    ),
    '#tree' => TRUE,
  );
  $markup = theme('civicrm_entity_price_set_field_price_field_display_form_confirmation_page', array(
      'form_data' => $form_state['storage']['registration_form'],
      'civicrm_event',
      'entity' => $form_state['event'],
      'price_set_data' => $form_state['price_set_data'],
      'heading' => !empty($form_state['event']->confirm_title) ? $form_state['event']->confirm_title : 'Confirmation Page',
      'profiles' => $form_state['profiles'],
    )
  );
  $form['confirmation_page']['message'] = array(
    '#type' => 'markup',
    '#markup' => $markup,
  );
}


/**
 * Theme function for Display Form -- Confirmation Page
 *
 * @param $vars
 * @return string
 */
function theme_civicrm_entity_price_set_field_price_field_display_form_confirmation_page($vars) {
  $html = '<h3 class="confirmation-title">' . $vars['heading'] . '</h3>';
  if (!empty($vars['entity']->confirm_text)) {
    $html .= $vars['entity']->confirm_text;
  }

  /*$header = array(
    'fee' => 'Fee',
    'name' => 'Name',
    'email' => 'Email',
    'phone' => 'Phone',
  );*/
  //$rows = array();
  $html .= '<h3 class="total"><strong class="label">Total: </strong>' . '$' . number_format($vars['form_data']['transaction']['total'], 2) . '</h3>';

  foreach ($vars['form_data']['contacts'] as $id => $contact) {
    $html .= '<h3>' . 'Participant ' . ((int)$id + 1) . ':</h3>';
    $html .= '<div class="profile-field"><span class="field-label">Fee: </span><span class="field-item>">' . '$' . number_format($vars['form_data']['transaction']['fee_amounts'][$id], 2) . '</span></div>';
    if (count($contact['contact_info'])) {
      foreach($contact['contact_info'] as $profile_id => $profile) {
        $pids = explode('-', $profile_id);
        $pid = $pids[1];
        $html .= '<h4>' . _civicrm_entity_profile_get_profile_title($pid) . ':</h4>';
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
    $rows[$id]['data']['phone'] = $contact['contact_info']['phone'];
    */
  }

  //$html .= theme('table', array('header' => $header, 'rows' => $rows));

  $html .= '<h3 class="total"><strong class="label">Total: </strong>' . '$' . number_format($vars['form_data']['transaction']['total'], 2) . '</h3>';

  if (!empty($vars['entity']->confirm_footer_text)) {
    $html .= $vars['entity']->confirm_footer_text;
  }

  return $html;
}