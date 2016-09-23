<?php
/**
 * @file
 * CiviCRM Entity Price Set Field, Form display formatter, event registration
 */

/**
 * Form callback for
 *
 * @param $form
 * @param $form_state
 * @return mixed
 */
function civicrm_entity_price_set_field_display_form_event($form, &$form_state) {
  $form = array();
  $price_set_id = $form_state['build_info']['args'][0];
  $host_entity_type = $form_state['build_info']['args'][1];
  $event = $form_state['event'] = $form_state['build_info']['args'][2];
  $form_state['field'] = $form_state['build_info']['args'][3];
  $form_state['instance'] = $form_state['build_info']['args'][4];
  $form_state['display_settings'] = $form_state['build_info']['args'][5];
  // online registration
  if (!empty($event->is_online_registration)) {
    if (user_access('register for events')) {
      // check if registration date is set and passed yet
      if (!empty($event->registration_start_date)) {
        if (time() < strtotime($event->registration_start_date)) {
          $form['message'] = array(
            '#type' => 'markup',
            '#message' => 'registration_state_date',
            '#markup' => 'Registration opens ' . $event->registration_start_date,
          );
          return $form;
        }
      }
      // check if registration end date is set and passed yet
      if (!empty($event->registration_end_date)) {
        if (time() > strtotime($event->registration_end_date)) {
          $form['message'] = array(
            '#type' => 'markup',
            '#message' => 'registration_end_date',
            '#markup' => 'Registration closed on ' . $event->registration_start_date,
          );
          return $form;
        }
      }

      // paid event
      if (!empty($event->is_monetary)) {
        if ($price_set_id) {
          $form_state['price_set_data'] = _civicrm_entity_price_set_field_get_relevant_entities($price_set_id);
        }
        else {
          $form['price_set']['message'] = array(
            '#type' => 'markup',
            '#message' => 'no_price_set',
            '#markup' => 'No price options for event.',
          );
          return $form;
        }
      }
      else {
        // free event
        $form_state['price_set_data'] = array();
      }

      // setup ajax multi step
      // If $form_state['step'] is not set, we set it to 1
      $form_state['step'] = isset($form_state['step']) ? $form_state['step'] : 1;

      // Add a wrapper div that will be used by the Form API to update the form using AJAX
      $form['#prefix'] = '<div id="civicrm-price-set-field-event-registration">';
      $form['#suffix'] = '</div>';
      $form['#attached']['js'][] = drupal_get_path('module', 'civicrm_entity_price_set_field') . '/js/civicrm_entity_price_set_field_display_form.js';
      // Depending on which step of the form we are on, we output different form fields
      switch ($form_state['step']) {
        // Registration Form
        case 1:
          $default_values = array();
          if (isset($form_state['values']['registration_form'])) {
            $default_values = $form_state['values']['registration_form'];
          }
          elseif (isset($form_state['storage']['registration_form'])) {
            $default_values = $form_state['storage']['registration_form'];
          }
          global $user;
          if ($user->uid && empty($default_values['register_as_another'])) {
            $contact = civicrm_entity_user_contact_get($user, array(), '', '', '');
            if ($contact) {
              $already_registered_query = new EntityFieldQuery();
              $already_registered_query_result = $already_registered_query->entityCondition('entity_type', 'civicrm_participant')
                ->propertyCondition('contact_id', $contact->id)
                ->propertyCondition('event_id', $event->id)
                ->execute();
              if (!empty($already_registered_query_result['civicrm_participant']) && count($already_registered_query_result['civicrm_participant'])) {
                drupal_set_message('You have already registered for this event.', 'status', FALSE);
              }
            }
          }
          // setup registration form
          _civicrm_entity_price_set_field_setup_event_registration_form_fapi($form, $form_state, $default_values);
          break;
        // Confirmation Page
        case 2:
          _civicrm_entity_price_set_field_setup_event_confirmation_page_fapi($form, $form_state);
          break;
        // Thank you page
        case 3:
          _civicrm_entity_price_set_field_setup_event_thank_you_page_fapi($form, $form_state);
          break;
      }

      // Create a container for our buttons
      $form['buttons'] = array(
        '#type' => 'container',
        '#attributes' => array(
          'class' => array(
            'civicrm-entity-price-set-registration-buttons',
          ),
        ),
      );

      switch ($form_state['step']) {
        case 1:
          $form['buttons']['register'] = array(
            '#type' => 'submit',
            '#value' => t('Register'),
            '#ajax' => array(
              'wrapper' => 'civicrm-price-set-field-event-registration',
              'callback' => 'civicrm_entity_price_set_field_event_registration_form_ajax_callback',
            ),
          );
          break;
        case 2:
          $form['buttons']['confirm'] = array(
            '#type' => 'submit',
            '#value' => t('Confirm'),
            '#ajax' => array(
              'wrapper' => 'civicrm-price-set-field-event-registration',
              'callback' => 'civicrm_entity_price_set_field_event_registration_form_ajax_callback',
            ),
          );
          $form['buttons']['back'] = array(
            '#type' => 'submit',
            '#value' => t('Back'),
            '#limit_validation_errors' => array(),
            '#submit' => array('_civicrm_entity_price_set_field_event_confirmation_page_back_submit'),
            '#ajax' => array(
              'wrapper' => 'civicrm-price-set-field-event-registration',
              'callback' => 'civicrm_entity_price_set_field_event_registration_form_ajax_callback',
            ),
          );
          break;
        case 3:
          break;
      }
    }
    else {
      $form['message'] = array(
        '#type' => 'markup',
        '#message' => 'online_registration_access_denied',
        '#markup' => 'You are not authorized to register for the event.',
      );
    }
  }
  // no online registration
  else {
    $form['message'] = array(
      '#type' => 'markup',
      '#message' => 'online_registration_disabled',
      '#markup' => 'Online registration disabled.',
    );
    return $form;
  }
  return $form;
}

/**
 * Helper function to setup event registration form FAPI
 *
 * @param $form
 * @param $form_state
 * @param $event
 * @param $data
 * @param $default_values
 */
function _civicrm_entity_price_set_field_setup_event_registration_form_fapi(&$form, &$form_state, $default_values) {
  $data = $form_state['price_set_data'];
  $event = $form_state['event'];
  $form['registration_form'] = array(
    '#type' => 'container',
    '#attributes' => array(
      'class' => array(
        'civicrm-entity-price-set-field-registration-form',
      ),
    ),
    '#tree' => TRUE,
  );

  // get contacts and set form state with contacts

  global $user;
  if (empty($default_values['register_as_another'])) {
    if ($user->uid) {
      $contact = civicrm_entity_user_contact_get($user, array(), '', '', '');
      if ($contact) {
        $form_state['contacts'][0] = $contact;
        $form_state['contacts'][0]->uid = $user->uid;
      }
    }
    else {
      $form_state['contacts'][0] = array(new CivicrmEntity(array('is_new' => 1), 'civicrm_contact'));
    }
  }
  else {
    $form_state['contacts'][0] = new CivicrmEntity(array('is_new' => 1), 'civicrm_contact');
    if ($user->uid) {
      $form_state['contacts'][0]->uid = $user->uid;
    }
  }

  $form['registration_form']['register_as_another'] = array(
    '#type' => 'checkbox',
    '#title' => t('Register as new contact.'),
    '#return_value' => 1,
    '#ajax' => array(
      'wrapper' => 'civicrm-price-set-field-event-registration',
      'callback' => 'civicrm_entity_price_set_field_event_registration_form_register_as_another_ajax_callback',
      'event' => 'click',
      'method' => 'replace',
    ),
  );

  if (!empty($form_state['contacts'][0]->uid)) {
    $form['registration_form']['register_as_another']['#default_value'] = !empty($default_values['register_as_another']) ? $default_values['register_as_another'] : 0;
    $form['registration_form']['register_as_another']['#description'] = 'You are logged in.  Leaving this box unchecked will use your contact information for this registration.';
  }
  else {
    $form['registration_form']['register_as_another']['#description'] = '<a href="/user/login">Login</a> if you already have an account.';
    $form['registration_form']['register_as_another']['#default_value'] = 1;
    $form['registration_form']['register_as_another']['#disabled'] = TRUE;
  }

  $form['registration_form']['contacts'] = array(
    '#type' => 'container',
    '#attributes' => array(
      'class' => array(
        'civicrm-entity-price-set-field-registration-form-contacts',
      ),
    ),
  );

  foreach ($form_state['contacts'] as $count => $contact) {
    if ($count) {
      $form['registration_form']['contacts'][$count]['heading'] = array(
        '#type' => 'markup',
        '#markup' => '<h2>' . 'Participant ' . ($count + 1) . '</h2>',
      );
    }
    if (!empty($data)) {
      _civicrm_entity_price_set_field_setup_price_set_fapi($form, $form_state, $default_values, 'registration_form', $count);
    }
    // setup contact fields
    $form['registration_form']['contacts'][$count]['contact_info'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array(
          'civicrm-entity-price-set-field-participant-form',
        ),
      ),
      '#prefix' => '<h3>Contact Information</h3>',
    );
    $form['registration_form']['contacts'][$count]['contact_info']['first_name'] = array(
      '#type' => 'textfield',
      '#title' => 'First Name',
      '#required' => TRUE,
    );
    $form['registration_form']['contacts'][$count]['contact_info']['last_name'] = array(
      '#type' => 'textfield',
      '#title' => 'Last Name',
      '#required' => TRUE,
    );
    $form['registration_form']['contacts'][$count]['contact_info']['email'] = array(
      '#type' => 'textfield',
      '#title' => 'Email',
      '#required' => TRUE,
    );
    $form['registration_form']['contacts'][$count]['contact_info']['phone'] = array(
      '#type' => 'textfield',
      '#title' => 'Phone',
    );
    $contact_form_names = array('first_name', 'last_name', 'email', 'phone');

    foreach ($contact_form_names as $name) {
      if (!empty($default_values['contacts'][$count]['contact_info'][$name])) {
        $form['registration_form']['contacts'][$count]['contact_info'][$name]['#default_value'] = $default_values['contacts'][$count]['contact_info'][$name];
      }
      elseif (!empty($form_state['contacts'][$count]->{$name})) {
        $form['registration_form']['contacts'][$count]['contact_info'][$name]['#default_value'] = $form_state['contacts'][$count]->{$name};
      }
      else {
        $form['registration_form']['contacts'][$count]['contact_info'][$name]['#default_value'] = '';
      }
    }

    if ($count) {
      $form['registration_form']['contacts'][$count]['remove_participant'] = array(
        '#contact_count' => $count,
        '#type' => 'submit',
        '#value' => t('Remove participant ' . ($count + 1)),
        '#limit_validation_errors' => array(),
        '#submit' => array('_civicrm_entity_price_set_field_event_registration_remove_participant_submit'),
        '#ajax' => array(
          'wrapper' => 'civicrm-price-set-field-event-registration',
          'callback' => 'civicrm_entity_price_set_field_event_registration_form_ajax_callback',
        ),
      );
    }

  }
  // if event is configured to allow multiple registrations then add an "Add Participant" button
  // to ajax load another contact price set and information container to the form
  if (!empty($form_state['event']->is_multiple_registrations)) {
    $form['registration_form']['add_participant'] = array(
      '#type' => 'submit',
      '#value' => t('Add a participant'),
      '#limit_validation_errors' => array(),
      '#submit' => array('_civicrm_entity_price_set_field_event_registration_add_participant_submit'),
      '#ajax' => array(
        'wrapper' => 'civicrm-price-set-field-event-registration',
        'callback' => 'civicrm_entity_price_set_field_event_registration_form_ajax_callback',
      ),
    );
  }
  // setup pay options
  if (!empty($form_state['event']->is_monetary)) {
    if (!empty($form_state['event']->is_pay_later)) {
      $form['registration_form']['pay_later'] = array(
        '#type' => 'checkbox',
        '#title' => !empty($form_state['event']->pay_later_text) ? $form_state['event']->pay_later_text : 'Pay by check',
        '#return_value' => 1,
      );
    }
    if (!empty($default_values['pay_later'])) {
      $form['registration_form']['pay_later']['#default_value'] = $default_values['pay_later'];
    }
    else {
      $form['registration_form']['pay_later']['#default_value'] = 0;
    }

    // if cc is selected, show billing block
    $form['registration_form']['registration_cc_block'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array(
          'civicrm-entity-price-set-field-cc-block',
        ),
      ),
    );
    $form['registration_form']['registration_cc_block']['name_on_card'] = array(
      '#type' => 'textfield',
      '#title' => 'Name on card',
      '#description' => 'Name as it appears on card. Required.',
      '#default_value' => '',
      '#size' => 36,
      '#maxlength' => 100,
      '#prefix' => '<h3>Credit Card Information</h3><div class="name-and-number-section">',
    );
    $form['registration_form']['registration_cc_block']['cc_number'] = array(
      '#type' => 'textfield',
      '#title' => 'Credit Card Number',
      '#description' => 'Enter numbers only, no dashes or spaces. Required.',
      '#default_value' => '',
      '#size' => 24,
      '#maxlength' => 24,
      '#suffix' => '</div>',
    );
    $form['registration_form']['registration_cc_block']['cc_exp'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array(
          'civicrm-entity-price-set-field-cc-exp-block',
        ),
      ),
      '#prefix' => '<div class="expiration-and-cvv-section">',
    );

    $form['registration_form']['registration_cc_block']['cc_exp']['month'] = array(
      '#prefix' => '<div class="form-item"><label>Credit Card Expiration Date</label></div>',
      '#type' => 'select',
      '#title' => 'Month',
      '#default_value' => format_date(time(), 'custom', 'n'),
      '#options' => array(
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
        9 => 9,
        10 => 10,
        11 => 11,
        12 => 12,
      ),
    );
    $form['registration_form']['registration_cc_block']['cc_exp']['year'] = array(
      '#type' => 'select',
      '#title' => 'Year',
      '#default_value' => format_date(time(), 'custom', 'Y'),
      '#options' => array_combine(range(date('Y'), date('Y') + 15), range(date('Y'), date('Y') + 15)),
    );
    $form['registration_form']['registration_cc_block']['cvv'] = array(
      '#type' => 'textfield',
      '#title' => 'CVV',
      '#default_value' => '',
      '#size' => 4,
      '#maxlength' => 4,
      '#description' => '3-4 digit code from back of card. Required.',
      '#suffix' => '</div>',
    );

    // add some javascript to hide/reveal cc block depending on pay later click
    // add some css too
    $form['#attached']['css'][] = drupal_get_path('module', 'civicrm_entity_price_set_field') . '/css/billing_block.css';
    $form['#attached']['js'][] = drupal_get_path('module', 'civicrm_entity_price_set_field') . '/js/billing_block.js';
    // jquery validation of billing block?
  }
}

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
    )
  );
  $form['confirmation_page']['message'] = array(
    '#type' => 'markup',
    '#markup' => $markup,
  );
}

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
    )
  );
  $form['thank_you_page']['message'] = array(
    '#type' => 'markup',
    '#markup' => $markup,
  );
}

/**
 * Submit handler for event registration form 'Register' button
 *
 * @param $form
 * @param $form_state
 */
function civicrm_entity_price_set_field_display_form_event_submit($form, &$form_state) {
  switch ($form_state['step']) {
    case 1:
      // Registration form submit
      $form_state['storage']['registration_form'] = $form_state['values']['registration_form'];
      $form_state['storage']['registration_form']['transaction'] = _civicrm_entity_price_set_field_calculate_total($form_state['price_set_data'], 'civicrm_event', $form_state['event'], $form_state['storage']['registration_form']);
      $form_state['step']++;
      break;
    case 2:
      // confirmation page submit
      $data = $form_state['price_set_data'];
      $event = $form_state['event'];
      $success = TRUE;

      if (!empty($form_state['storage']['registration_form']['transaction']['total']) &&
        is_numeric($form_state['storage']['registration_form']['transaction']['total']) &&
        $form_state['storage']['registration_form']['transaction']['total'] > 0
      ) {
        if (empty($form_state['storage']['registration_form']['pay_later'])) {
          _civicrm_entity_price_set_field_display_form_create_update_contact('first', 0, $form_state);
          $transact_result = _civicrm_entity_price_set_field_run_cc_transaction($form_state['display_settings'], $data, 'civicrm_event', $event, $form_state['contacts'], $form_state['storage']['registration_form']);
          if ($transact_result !== FALSE && !empty($transact_result['contribution']) && is_object($transact_result['contribution'])) {
            $form_state['contribution'] = $transact_result['contribution'];
          }
          else {
            $success = FALSE;
          }
        }
        else {
          $contribution_params = array(
            'is_new' => TRUE,
            'financial_type_id' => $form_state['event']->financial_type_id,
            'total_amount' => $form_state['storage']['registration_form']['transaction']['total'],
            'contact_id' => $form_state['contacts'][0]->id,
            'invoice_id' => md5(uniqid(rand(), TRUE)),
            'source' => 'CiviCRM Entity Price Set Field -- Event Registration',
            'is_pay_later' => 1,
            'receive_date' => date('Y-m-d H:i:s'),
            'contribution_status_id' => "Pending",
            'skipLineItem' => 1,
          );
          $contribution = new CivicrmEntity($contribution_params, 'civicrm_contribution');
          $contribution_wrapper = entity_metadata_wrapper('civicrm_contribution', $contribution);
          $contribution_wrapper->save();
          $form_state['contribution'] = $contribution_wrapper->value();
        }
      }
      if (!empty($success)) {
        _civicrm_entity_price_set_field_process_event_registration($form_state);
        $form_state['step']++;
      }
      else {
        drupal_set_message('Error running credit card transaction. Contact Site Administrator.');
        $form_state['step']--;
      }
      break;
    case 3:
      $form_state['step'] = 1;
      break;
  }
  $form_state['rebuild'] = TRUE;
}

/**
 * Utility function to handle contact create/update
 *
 * @param $action
 * @param $count
 * @param $form_state
 * @throws CiviCRM_API3_Exception
 * @throws EntityMetadataWrapperException
 */
function _civicrm_entity_price_set_field_display_form_create_update_contact($action, $count, &$form_state) {
  if (!empty($form_state['contacts'][$count]->is_new) || !empty($form_state['contacts'][$count]->is_new_temp)) {
    civicrm_initialize();
    $params = array(
      'match' => array(
        'contact_type' => 'Individual',
        'first_name' => $form_state['storage']['registration_form']['contacts'][$count]['contact_info']['first_name'],
        'last_name' => $form_state['storage']['registration_form']['contacts'][$count]['contact_info']['last_name'],
        'email' => $form_state['storage']['registration_form']['contacts'][$count]['contact_info']['email'],
      ),
    );
    if (!empty($form_state['storage']['registration_form']['contacts'][$count]['contact_info']['phone'])) {
      $params['match']['phone'] = $form_state['storage']['registration_form']['contacts'][$count]['contact_info']['phone'];
    }
    if (!empty($form_state['event']->dedupe_rule_group_id)) {
      $params['dedupe_rule_id'] = $form_state['event']->dedupe_rule_group_id;
    }
    $result = civicrm_api3('Contact', 'duplicatecheck', $params);
    if ($result['count']) {
      $dupe_contact_ids = array_keys($result['values']);
      $form_state['contacts'][$count] = entity_load_single('civicrm_contact', reset($dupe_contact_ids));
      $form_state['contacts'][$count]->is_new_temp = TRUE;
    }
    else {
      $contact = new CivicrmEntity(array(
        'first_name' => $form_state['storage']['registration_form']['contacts'][$count]['contact_info']['first_name'],
        'last_name' => $form_state['storage']['registration_form']['contacts'][$count]['contact_info']['last_name'],
        'email' => $form_state['storage']['registration_form']['contacts'][$count]['contact_info']['email'],
        'contact_type' => 'Individual',
      ), 'civicrm_contact');
      if (!empty($form_state['storage']['registration_form']['contacts'][$count]['contact_info']['phone'])) {
        $contact->phone = $form_state['storage']['registration_form']['contacts'][$count]['contact_info']['phone'];
      }
      $contact->source = 'Created VIA CiviCRM Entity Price Set Field Display Form';
      $contact_wrapper = entity_metadata_wrapper('civicrm_contact', $contact);
      $contact_wrapper->save();
      $form_state['contacts'][$count] = $contact_wrapper->value();
      $form_state['contacts'][$count]->is_new_temp = TRUE;
      if ($count == 0 && empty($form_state['storage']['registration_form']['register_as_another'])) {
        global $user;
        if ($user->uid) {
          $form_state['contacts'][$count]->uid = $user->uid;
        }
      }
    }
  }
  elseif ((empty($form_state['contacts'][$count]->is_new) && empty($form_state['contacts'][$count]->is_new_temp)) && $action == 'other') {

    $form_state['contacts'][$count]->first_name = $form_state['storage']['registration_form']['contacts'][$count]['contact_info']['first_name'];
    $form_state['contacts'][$count]->last_name = $form_state['storage']['registration_form']['contacts'][$count]['contact_info']['last_name'];
    $form_state['contacts'][$count]->email = $form_state['storage']['registration_form']['contacts'][$count]['contact_info']['email'];
    $form_state['contacts'][$count]->phone = $form_state['storage']['registration_form']['contacts'][$count]['contact_info']['phone'];
    $contact_wrapper = entity_metadata_wrapper('civicrm_contact', $form_state['contacts'][$count]);
    $contact_wrapper->save();
    $form_state['contacts'][$count] = $contact_wrapper->value();

    if ($count == 0 && !empty($form_state['contacts'][$count]->uid)) {
      civicrm_initialize();
      $result = civicrm_api3('UFMatch', 'get', array(
        'sequential' => 1,
        'uf_id' => $form_state['contacts'][$count]->uid,
      ));
      if ($result['count'] == 0) {
        $result = civicrm_api3('UFMatch', 'create', array(
          'sequential' => 1,
          'uf_id' => $form_state['contacts'][$count]->uid,
          'contact_id' => $form_state['contacts'][$count],
          'uf_name' => $form_state['storage']['registration_form']['contacts'][$count]['contact_info']['email'],
        ));
      }
    }

  }
}

/**
 * Submit handler for event confirmation page back button
 *
 * @param $form
 * @param $form_state
 */
function _civicrm_entity_price_set_field_event_confirmation_page_back_submit($form, &$form_state) {
  $form_state['step']--;
  $form_state['rebuild'] = TRUE;
}

/**
 * Submit handler for the event registration add participant button
 *
 * @param $form
 * @param $form_state
 */
function _civicrm_entity_price_set_field_event_registration_add_participant_submit($form, &$form_state) {
  $contact = new CivicrmEntity(array('is_new' => TRUE), 'civicrm_contact');
  $form_state['contacts'][count($form_state['contacts'])] = $contact;
  $form_state['rebuild'] = TRUE;
}


/**
 * Submit handler for the event registration remove participant button
 *
 * @param $form
 * @param $form_state
 */
function _civicrm_entity_price_set_field_event_registration_remove_participant_submit($form, &$form_state) {
  $count = $form_state['triggering_element']['#contact_count'];
  unset($form_state['contacts'][$count]);
  if (isset($form_state['storage']['registration_form']['contacts'][$count])) {
    unset($form_state['storage']['registration_form']['contacts'][$count]);
  }
  $form_state['rebuild'] = TRUE;
}

/**
 * Ajax callback for Event Registration form / pages
 *
 * @param $form
 * @param $form_state
 * @return mixed
 */
function civicrm_entity_price_set_field_event_registration_form_ajax_callback($form, &$form_state) {
  $commands = array();

  $commands[] = ajax_command_html('#civicrm-price-set-field-event-registration', render($form));
  $commands[] = ajax_command_prepend('#civicrm-price-set-field-event-registration', '<div id="messages">' . theme('status_messages') . '</div>');
  $commands[] = array(
    'command' => 'afterPriceSetDisplayFormAjaxReplaceCallback',
  );
  return array('#type' => 'ajax', '#commands' => $commands);
}

/**
 * Ajax callback for Event registration form, Register as another user checkbox
 *
 * @param $form
 * @param $form_state
 * @return array
 */
function civicrm_entity_price_set_field_event_registration_form_register_as_another_ajax_callback(&$form, &$form_state) {
  if (!empty($form_state['values']['registration_form']['register_as_another'])) {

    $form_state['input']['registration_form']['contacts'][0]['contact_info']['first_name'] = '';
    $form_state['input']['registration_form']['contacts'][0]['contact_info']['last_name'] = '';
    $form_state['input']['registration_form']['contacts'][0]['contact_info']['email'] = '';
    $form_state['input']['registration_form']['contacts'][0]['contact_info']['phone'] = '';

    $form_state['contacts'][0] = new CivicrmEntity(array('is_new' => 1), 'civicrm_contact');

    global $user;
    if ($user->uid) {
      $form_state['contacts'][0]->uid = $user->uid;
    }
  }
  else {
    global $user;
    if ($user->uid && empty($default_values['register_as_another'])) {
      $contact = civicrm_entity_user_contact_get($user, array(), '', '', '');
      if ($contact) {
        $form_state['contacts'][0] = $contact;
        $form_state['input']['registration_form']['contacts'][0]['contact_info']['first_name'] = !empty($contact->first_name) ? $contact->first_name : '';
        $form_state['input']['registration_form']['contacts'][0]['contact_info']['last_name'] = !empty($contact->last_name) ? $contact->last_name : '';
        $form_state['input']['registration_form']['contacts'][0]['contact_info']['email'] = !empty($contact->email) ? $contact->email : '';;
        $form_state['input']['registration_form']['contacts'][0]['contact_info']['phone'] = !empty($contact->phone) ? $contact->phone : '';;
      }
      else {
        $form_state['contacts'][0] = array(
          new CivicrmEntity(array(
            'is_new' => 1,
            'uid' => $user->uid,
          ), 'civicrm_contact'),
        );
      }
    }
    else {
      $form_state['contacts'][0] = new CivicrmEntity(array('is_new' => 1), 'civicrm_contact');
    }
  }
  $form_state['rebuild'] = TRUE;


  $commands = array();
  $to_render = drupal_rebuild_form($form['#form_id'], $form_state, $form);
  $commands[] = ajax_command_html('#civicrm-price-set-field-event-registration', render($to_render));
  $commands[] = ajax_command_prepend('#civicrm-price-set-field-event-registration', '<div id="messages">' . theme('status_messages') . '</div>');
  $commands[] = array(
    'command' => 'afterPriceSetDisplayFormAjaxReplaceCallback',
  );
  return array('#type' => 'ajax', '#commands' => $commands);
}

/**
 * Process event registration (after successful cc transation if appropriate)
 *
 * @param $form_state
 */
function _civicrm_entity_price_set_field_process_event_registration($form_state) {
  $form_state['participants'] = array();
  if (empty($form_state['contribution'])) {
    $form_state['contribution'] = NULL;
  }

  $line_items = array();

  foreach ($form_state['contacts'] as $count => $contact) {
    _civicrm_entity_price_set_field_display_form_create_update_contact('other', $count, $form_state);
    try {
      $params = array(
        'is_new' => TRUE,
        'event_id' => $form_state['event']->id,
        'contact_id' => $form_state['contacts'][$count]->id,
        'source' => "User Created - CiviCRM Entity Price Set Field Display Form",
        'role_id' => !empty($form_state['event']->default_role_id) ? $form_state['event']->default_role_id : "Attendee",
        'status_id' => "Registered",
        'register_date' => date('Y-m-d H:i:s'),
        'is_pay_later' => !empty($form_state['storage']['registration_form']['pay_later']) ? 1 : 0,
      );

      if (isset($form_state['storage']['registration_form']['transaction']['fee_amounts'][$count])) {
        $params['fee_amount'] = $form_state['storage']['registration_form']['transaction']['fee_amounts'][$count];
      }

      if ($count) {
        $params['registered_by_id'] = $form_state['participants'][0]->id;
      }
      if (!empty($form_state['storage']['registration_form']['pay_later'])) {
        $params['status_id'] = "Pending from pay later";
      }

      $participant = new CivicrmEntity($params, 'civicrm_participant');

      $participant_wrapper = entity_metadata_wrapper('civicrm_participant', $participant);
      $participant_wrapper->save();
      $form_state['participants'][$count] = $participant_wrapper->value();

      if (!empty($form_state['contribution'])) {

        if ($count == 0) {
          // create participant_payment entity
          $participant_payment = new CivicrmEntity(array(
            'contribution_id' => $form_state['contribution']->id,
            'participant_id' => $form_state['participants'][$count]->id,
          ), 'civicrm_participant_payment');
          $participant_payment_wrapper = entity_metadata_wrapper('civicrm_participant_payment', $participant_payment);
          $participant_payment_wrapper->save();
        }

        foreach ($form_state['storage']['registration_form']['transaction']['line_items'][$count] as $pf_id => $price_field) {
          foreach ($price_field as $pfv_id => $price_field_value) {
            $line_item_params = array(
              'entity_table' => 'civicrm_participant',
              'entity_id' => $form_state['participants'][$count]->id,
              'label' => $form_state['price_set_data']['price_fields'][$pf_id]['price_field_values'][$price_field_value['price_field_value_id']]->label,
              'qty' => $price_field_value['qty'],
              'contribution_id' => $form_state['contribution']->id,
              'price_field_id' => $price_field_value['price_field_id'],
              'price_field_value_id' => $price_field_value['price_field_value_id'],
              'financial_type_id' => $price_field_value['financial_type_id'],
              'line_total' => $price_field_value['line_total'],
              'unit_price' => $price_field_value['unit_price'],
            );

            $line_item = new CivicrmEntity($line_item_params, 'civicrm_line_item');
            $line_item_wrapper = entity_metadata_wrapper('civicrm_line_item', $line_item);
            $line_item_wrapper->save();
            $line_items[] = $line_item_wrapper->value();
          }
        }

      }
    } catch (Exception $e) {
      drupal_set_message('Error creating participant record or financial records for registration for contact: ' . $form_state['contacts'][$count]->first_name . ' ' . $form_state['contacts'][$count]->last_name . '  -- ' . $e->getMessage());
    }
  }


  $price_fields = array();
  $price_field_values = array();
  if (!empty($form_state['price_set_data']['price_fields'])) {
    foreach ($form_state['price_set_data']['price_fields'] as $pf_id => $pf_data) {
      $price_fields[] = $pf_data['pf_entity'];
      if (!empty($pf_data['price_field_values'])) {
        foreach ($pf_data['price_field_values'] as $pfv_id => $pfv) {
          $price_field_values[] = $pfv;
        }
      }
    }
  }

  // trigger rule event with all the pertinent data here
  $event_variables = array(
    'event' => $form_state['event'],
    'contribution' => $form_state['contribution'],
    'contacts' => $form_state['contacts'],
    'participants' => $form_state['participants'],
  );

  rules_invoke_event_by_args('civicrm_entity_price_set_field_event_registration', $event_variables);
}

/**
 * Helper function to add price set FAPI elements on form
 *
 * @param $form
 * @param $form_state
 * @param $default_values
 * @param $parent
 * @param $contact_id
 */
function _civicrm_entity_price_set_field_setup_price_set_fapi(&$form, &$form_state, $default_values, $parent, $count) {
  $data = $form_state['price_set_data'];
  if (!empty($data)) {
    $form[$parent]['contacts'][$count]['price_set'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array(
          'civicrm-price-set',
        ),
      ),
    );
    foreach ($data['price_fields'] as $pf_id => $price_field) {
      $form[$parent]['contacts'][$count]['price_set'][$pf_id] = array(
        '#type' => 'container',
        '#attributes' => array(
          'class' => array(
            'civicrm-price-field',
            'price-field-type-' . drupal_html_class(strtolower($price_field['pf_entity']->html_type)),
          ),
        ),
      );

      $form[$parent]['contacts'][$count]['price_set'][$pf_id]['price_field_values'] = array();
      $form[$parent]['contacts'][$count]['price_set'][$pf_id]['price_field_values']['#title'] = t($price_field['pf_entity']->label);

      if (!empty($price_field['pf_entity']->is_required)) {
        $form[$parent]['contacts'][$count]['price_set'][$pf_id]['price_field_values']['#required'] = TRUE;

      };

      if ($price_field['pf_entity']->html_type == 'Text') {
        $form[$parent]['contacts'][$count]['price_set'][$pf_id]['price_field_values']['#type'] = 'textfield';
      }
      else {
        switch ($price_field['pf_entity']->html_type) {
          case 'Radio':
            $form[$parent]['contacts'][$count]['price_set'][$pf_id]['price_field_values']['#type'] = 'radios';
            $default_value = array();
            break;
          case 'Select':
            $form[$parent]['contacts'][$count]['price_set'][$pf_id]['price_field_values']['#type'] = 'select';
            $default_value = '';
            break;
          case 'CheckBox':
            $form[$parent]['contacts'][$count]['price_set'][$pf_id]['price_field_values']['#type'] = 'checkboxes';
            $default_value = array();
            break;
        }
        $options = array();
        foreach ($price_field['price_field_values'] as $id => $price_field_value) {
          $options[$id] = $price_field_value->label . ' $' . $price_field_value->amount;
          if (!empty($price_field_value->is_default)) {
            if (is_array($default_value)) {
              $default_value[$id] = $id;
            }
            else {
              $default_value = $id;
            }
          }
        }
        $form[$parent]['contacts'][$count]['price_set'][$pf_id]['price_field_values']['#options'] = $options;
        if (empty($default_values['contacts'][$count]['price_set'][$pf_id]['price_field_values'])) {
          $form[$parent]['contacts'][$count]['price_set'][$pf_id]['price_field_values']['#default_value'] = $default_value;
        }
        else {
          $form[$parent]['contacts'][$count]['price_set'][$pf_id]['price_field_values']['#default_value'] = $default_values['contacts'][$count]['price_set'][$pf_id]['price_field_values'];
        }
      }
    }
  }
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

  $header = array(
    'fee' => 'Fee',
    'name' => 'Name',
    'email' => 'Email',
    'phone' => 'Phone',
  );
  $rows = array();
  foreach ($vars['form_data']['contacts'] as $id => $contact) {
    $rows[$id]['data']['fee'] = '$' . number_format($vars['form_data']['transaction']['fee_amounts'][$id], 2);
    $rows[$id]['data']['name'] = implode(' ', array(
      $contact['contact_info']['first_name'],
      $contact['contact_info']['last_name'],
    ));
    $rows[$id]['data']['email'] = $contact['contact_info']['email'];
    $rows[$id]['data']['phone'] = $contact['contact_info']['phone'];
  }
  $html .= '<h4>' . 'Registering participants:' . '</h4>';
  $html .= theme('table', array('header' => $header, 'rows' => $rows));

  $html .= '<h4 class="total"><strong class="label">Total: </strong>' . '$' . number_format($vars['form_data']['transaction']['total'], 2) . '</h4>';

  if (!empty($vars['entity']->confirm_footer_text)) {
    $html .= $vars['entity']->confirm_footer_text;
  }

  return $html;
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

  $header = array(
    'fee' => 'Fee',
    'name' => 'Name',
    'email' => 'Email',
    'phone' => 'Phone',
  );
  $rows = array();
  foreach ($vars['form_data']['contacts'] as $id => $contact) {
    $rows[$id]['data']['fee'] = '$' . number_format($vars['form_data']['transaction']['fee_amounts'][$id], 2);
    $rows[$id]['data']['name'] = implode(' ', array(
      $contact['contact_info']['first_name'],
      $contact['contact_info']['last_name'],
    ));
    $rows[$id]['data']['email'] = $contact['contact_info']['email'];
    $rows[$id]['data']['phone'] = $contact['contact_info']['phone'];
  }
  $html .= '<h4>' . 'Registered participants:' . '</h4>';
  $html .= theme('table', array('header' => $header, 'rows' => $rows));

  $html .= '<h4 class="total"><strong class="label">Total: </strong>' . '$' . number_format($vars['form_data']['transaction']['total'], 2) . '</h4>';

  if (!empty($vars['entity']->thankyou_footer_text)) {
    $html .= $vars['entity']->thankyou_footer_text;
  }

  return $html;
}

/**
 * Validation Handler for the civicrm entity price set event registration form display
 *
 * @param $form
 * @param $form_state
 */
function civicrm_entity_price_set_field_display_form_event_validate($form, $form_state) {
  if (isset($form_state['values']['registration_form']['pay_later']) && $form_state['values']['registration_form']['pay_later'] == 0) {
    if (empty($form_state['values']['registration_form']['registration_cc_block']['name_on_card'])) {
      form_set_error('registration_form][registration_cc_block][name_on_card', 'Name on card field required.');
    }
    if (empty($form_state['values']['registration_form']['registration_cc_block']['cc_number'])) {
      form_set_error('registration_form][registration_cc_block][cc_number', 'Credit card number required.');
    }
    elseif (!is_numeric($form_state['values']['registration_form']['registration_cc_block']['cc_number'])) {
      form_set_error('registration_form][registration_cc_block][cc_number', 'Credit card number must be numeric.');
    }
    if (empty($form_state['values']['registration_form']['registration_cc_block']['cvv'])) {
      form_set_error('registration_form][registration_cc_block][cvv', 'CVV required.');
    }
    elseif (!is_numeric($form_state['values']['registration_form']['registration_cc_block']['cvv'])) {
      form_set_error('registration_form][registration_cc_block][cvv', 'CVV must be numeric.');
    }
  }
}

/**
 * Implements hook_form_FORMID_alter().
 *
 * Alter the civcrm-event edit/add form to add validation handler if a price set field is on the form
 *
 * @param $form
 * @param $form_state
 */
function civicrm_entity_price_set_field_form_civicrm_event_form_alter(&$form, &$form_state) {
  foreach ($form as $key => $element) {
    if (strpos($key, 'field_') === 0) {
      $field_info = field_info_field($key);
      if ($field_info['type'] == 'civicrm_entity_price_set_field') {
        $form['#validate'][] = 'civicrm_entity_price_set_field_event_form_additional_validation';
        break;
      }
    }
  }

}

/**
 * Additional validation handler for event form when a price set field is on the form
 *
 * @param $form
 * @param $form_state
 */
function civicrm_entity_price_set_field_event_form_additional_validation(&$form, &$form_state) {
  if (!empty($form_state['values']['is_monetary'])) {
    if (empty($form_state['values']['financial_type_id'])) {
      form_set_error('financial_type_id', 'Financial Type is required when the event is a paid event.');
    }
    if (empty($form_state['values']['fee_label'])) {
      form_set_error('fee_label', 'Fee Label is required when the event is a paid event.');
    }
    if (empty($form_state['values']['payment_processor'])) {
      form_set_error('payment_processor', 'Payment Processor is required when the event is a paid event.');
    }
  }
}