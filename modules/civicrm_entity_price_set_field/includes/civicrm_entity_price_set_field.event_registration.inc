<?php
/**
 * @file
 * CiviCRM Entity Price Set Field, Form display formatter, event registration
 */


/**
 * CiviCRM Entity Price Set Field Get Registration Access Callback Info
 *
 * Sets up info hook to gather registration access callbacks
 *
 * @param null $id
 * @return array
 */
function civicrm_entity_price_set_field_get_registration_access_callbacks_info($id = NULL) {
  $results = array();
  foreach (module_implements('civicrm_entity_price_set_field_registration_access_callback_info') as $module) {
    // Calling all modules implementing hook_hook_name and
    // Returning results than pushing them into the $result array:
    $result = module_invoke($module, 'civicrm_entity_price_set_field_registration_access_callback_info');
    foreach ($result as $id => $r) {
      if (!empty($r['callback'])) {
        $results[$id] = $result;
      }
    }
  }
  if (empty($id)) {
    return $results;
  }
  else {
    return $results[$id];
  }
}

/**
 * Utility function to find modules with custom event registration access callbacks
 *
 * Designed to make exceptions to access, user still required to have 'register for events' permission that comes with CiviCRM
 *
 * @param $entity_type
 * @param $entity
 * @param $field
 * @param $instance
 * @param null $account
 * @return bool
 */
function civicrm_entity_price_set_field_allow_registration($entity_type, $entity, $field, $instance, $account = NULL) {
  if (is_null($account)) {
    global $user;
    $account = $user;
  }

  $callbacks = civicrm_entity_price_set_field_get_registration_access_callbacks_info();

  if (!empty($callbacks)) {
    foreach($callbacks as $key => $info) {
      if (!empty($info['callback'])) {
        $result = call_user_func($info['callback'], $entity_type, $entity, $field, $instance, $account);
        if($result === FALSE) {
          return FALSE;
        }
      }
    }
  }

  return TRUE;
}

/**
 * Form callback for event registration form
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
    if (user_access('register for events') && civicrm_entity_price_set_field_allow_registration($host_entity_type, $event, $form_state['field'], $form_state['instance'])) {
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
      // check if max_participants has been reached, and if so, close the form down, good messaging
      if (isset($event->max_participants)) {
        $registered_count = _civicrm_entity_price_set_field_get_participant_count_for_event($event->id);
        if ($registered_count >= $event->max_participants) {
          $form['message'] = array(
            '#type' => 'markup',
            '#message' => 'max_participants',
            '#markup' => !empty($event->event_full_text) ? $event->event_full_text : 'The event is currently full',
          );
          return $form;
        }
      }

      // paid event
      if (!empty($event->is_monetary)) {
        if ($price_set_id) {
          if (empty($form_state['contacts'])) {
            $form_state['price_set_data'][0] = _civicrm_entity_price_set_field_get_relevant_entities_clone($price_set_id);
          }
          else {
            foreach ($form_state['contacts'] as $count => $contact) {
              $form_state['price_set_data'][$count] = _civicrm_entity_price_set_field_get_relevant_entities_clone($price_set_id);
            }
          }
          foreach ($form_state['price_set_data'] as $count => $ps_data) {
            $context = array(
              'participant_count' => $count,
              'entity_type' => $host_entity_type,
              'entity' => $event,
              'field' => $form_state['field'],
              'instance' => $form_state['instance'],
              'display_settings' => $form_state['display_settings'],
            );
            drupal_alter('civicrm_entity_price_set_field_registration_form_price_set_data', $form_state['price_set_data'][$count], $context);
          }
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
        if (empty($form_state['contacts'])) {
          $form_state['price_set_data'][0] = array();
        }
        else {
          foreach ($form_state['contacts'] as $count => $contact) {
            $form_state['price_set_data'][$count] = array();
          }
        }
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
              $already_registered = _civicrm_entity_price_set_field_get_participant_count_for_event($event->id, $contact->id);
              if (!empty($already_registered)) {
                drupal_set_message(t('You have already registered for this event.'), 'status', FALSE);
                $form_state['main_registrant_already_registered'] = TRUE;
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
 * @param $default_values
 */
function _civicrm_entity_price_set_field_setup_event_registration_form_fapi(&$form, &$form_state, $default_values) {
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
    if ($user->uid && empty($form_state['main_registrant_already_registered'])) {
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
    if ($user->uid && empty($form_state['main_registrant_already_registered'])) {
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
    if (empty($form_state['main_registrant_already_registered'])) {
      $form['registration_form']['register_as_another']['#description'] = '<a href="/user/login">Login</a> if you already have an account.';
    }
    else {
      $form['registration_form']['register_as_another']['#description'] = 'You are already registered, you can only register for another.';
    }
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

  //Fetch event profiles
  $main_profiles = $add_profiles = array();
  if (module_exists('civicrm_entity_profile')) {
    $main_profiles = _civicrm_entity_profile_get_profiles('civicrm_event', $form_state['event']->id, 'CiviEvent');
    if (!empty($form_state['event']->is_multiple_registrations)) {
      $add_profiles = _civicrm_entity_profile_get_profiles('civicrm_event', $form_state['event']->id, 'CiviEvent_Additional');
    }
  }
  foreach ($form_state['contacts'] as $count => $contact) {
    if ($count) {
      $form['registration_form']['contacts'][$count]['heading'] = array(
        '#type' => 'markup',
        '#markup' => '<h2>' . 'Participant ' . ($count + 1) . '</h2>',
      );
    }
    if (!empty($form_state['price_set_data'][$count])) {
      _civicrm_entity_price_set_field_setup_price_set_fapi($form, $form_state, $default_values, 'registration_form', $count);
    }

    // setup profiles
    $form['registration_form']['contacts'][$count]['contact_info'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array(
          'civicrm-entity-price-set-field-participant-form',
        ),
      ),
    );

    if ($count == 0) {
      $profile_list = $main_profiles;
    }
    else {
      $profile_list = $add_profiles;
    }
    // need at least one contact profile, if not, don't display form (should also not be able to add additional participant if this is the case!)
    $profile_list_has_contact_entity = 0;
    if (count($profile_list)) {
      foreach ($profile_list as $index => $profile_id) {
        // first check for 'mixed' profiles, if mixed, then don't render
        $profile_entity_type = _civicrm_entity_profile_determine_profile_entity($profile_id);
        if (!$profile_entity_type) {
          unset($profile_list[$index]);
        }
        elseif ($profile_entity_type == 'contact') {
          $profile_list_has_contact_entity = 1;
        }
      }
    }
    $additional_participant_profiles_has_contact_entity = 0;
    if (count($add_profiles)) {
      foreach ($add_profiles as $index => $profile_id) {
        $profile_entity_type = _civicrm_entity_profile_determine_profile_entity($profile_id);
        if (!$profile_entity_type) {
          unset($profile_list[$index]);
        }
        elseif ($profile_entity_type == 'contact') {
          $additional_participant_profiles_has_contact_entity = 1;
        }
      }
    }
    // render profiles
    if (count($profile_list)) {
      if (!$profile_list_has_contact_entity) {
        $form['message'] = array(
          '#type' => 'markup',
          '#message' => 'online_registration_no_profiles',
          '#markup' => 'Event must be configured with at least one contact profile',
        );
        return;
      }
      foreach ($profile_list as $profile_id) {
        $profile_title = _civicrm_entity_profile_get_profile_title($profile_id);
        // setup profile container
        $form['registration_form']['contacts'][$count]['contact_info']['profile-' . $profile_id] = array(
          '#type' => 'container',
          '#attributes' => array(
            'class' => array(
              'civicrm-entity-price-set-field-profile-fapi',
            ),
          ),
          '#prefix' => '<div class="civicrm-entity-price-set-field-profile">',
          '#suffix' => '</div>',

        );
        // Title for the form set
        if (!empty($profile_title)) {
          $form['registration_form']['contacts'][$count]['contact_info']['profile-' . $profile_id]['#prefix'] = '<div class="civicrm-entity-price-set-field-profile">' . '<h3>' . $profile_title . '</h3>';
        }
        // Save profile metadata for future use
        $form_state['profiles']['profile-' . $profile_id] = _civicrm_entity_profile_get_profile_metadata($profile_id, 'event');
        // Get profile FAPI
        $profile_fapi = _civicrm_entity_profile_generate_profile_fapi($profile_id, 'event');

        if (!empty($profile_fapi)) {
          // add profile FAPI to main form
          $form['registration_form']['contacts'][$count]['contact_info']['profile-' . $profile_id]['profile'] = $profile_fapi;

          // Setup default values
          foreach ($form['registration_form']['contacts'][$count]['contact_info']['profile-' . $profile_id]['profile'] as $name => $field_fapi) {
            if (!empty($default_values['contacts'][$count]['contact_info']['profile-' . $profile_id]['profile'][$name])) {
              $form['registration_form']['contacts'][$count]['contact_info']['profile-' . $profile_id]['profile'][$name]['#default_value'] = $default_values['contacts'][$count]['contact_info']['profile-' . $profile_id]['profile'][$name];
            }
            elseif (!empty($form_state['contacts'][$count]->{$name})) {
              $form['registration_form']['contacts'][$count]['contact_info']['profile-' . $profile_id]['profile'][$name]['#default_value'] = $form_state['contacts'][$count]->{$name};
            }
            else {
              $form['registration_form']['contacts'][$count]['contact_info']['profile-' . $profile_id]['profile'][$name]['#default_value'] = '';
            }
          }
        }
      }
    }
    else {
      $form['message'] = array(
        '#type' => 'markup',
        '#message' => 'online_registration_no_profiles',
        '#markup' => 'Event must be configured with at least one contact profile',
      );
      return;
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
  if (!empty($form_state['event']->is_multiple_registrations) && !empty($additional_participant_profiles_has_contact_entity)) {
    // if max_participants has been reached disable add participant button, will be queried total of participants for event + number of participants being registered already by form
    $registered_count = _civicrm_entity_price_set_field_get_participant_count_for_event($event->id);
    if (!isset($event->max_participants) || (isset($event->max_participants) && (($registered_count + count($form_state['contacts'])) < $event->max_participants))) {
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
    $form['registration_form']['registration_cc_block']['first_name_on_card'] = array(
      '#type' => 'textfield',
      '#title' => 'First Name',
      '#description' => 'First name as it appears on card. Required.',
      '#default_value' => '',
      '#size' => 36,
      '#maxlength' => 100,
      '#prefix' => '<h3>Credit Card Information</h3><div class="name-and-number-section">',
    );
    $form['registration_form']['registration_cc_block']['last_name_on_card'] = array(
      '#type' => 'textfield',
      '#title' => 'Last Name',
      '#description' => 'Last name as it appears on card. Required.',
      '#default_value' => '',
      '#size' => 36,
      '#maxlength' => 100,
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
      $event = $form_state['event'];
      $success = TRUE;

      //  Dedupe the participants, and make sure nobody has already registered, if so kick it back to the register form, messaging
      foreach ($form_state['contacts'] as $count => $contact) {
        if (empty($contact->id)) {
          // the de-dupe api way
          $params = array('sequential' => 1, 'check_permission' => FALSE);
          $params['match']['contact_type'] = 'Individual';
          if (!empty($form_state['event']->dedupe_rule_group_id)) {
            $params['dedupe_rule_id'] = $form_state['event']->dedupe_rule_group_id;
          }

          // build up match param array with values for each contact profile field
          $first_pid = 0;
          foreach ($form_state['storage']['registration_form']['contacts'][$count]['contact_info'] as $profile_id => $profile) {
            $pids = explode('-', $profile_id);
            $pid = $pids[1];
            $profile_entity_type = _civicrm_entity_profile_determine_profile_entity($pid);
            if ($profile_entity_type == 'contact') {
              if (empty($first_pid)) {
                $first_pid = $pid;
                $first_profile = $profile;
              }
              // should we gather up all the contact profile fields and add those to the 'match' param array for the dedupe, instead of just the first?
              foreach ($profile['profile'] as $name => $value) {
                $params['match'][$name] = $value;
              }
            }
          }

          $dedupe_result = civicrm_api3('Contact', 'duplicatecheck', $params);
          if (!empty($dedupe_result['id'])) {
            // should we check here if the contact is already registered, and if so throw back a message?
            $contact_registrations_for_event = _civicrm_entity_price_set_field_get_participant_count_for_event($event->id, $dedupe_result['id']);
            if ($contact_registrations_for_event) {
              drupal_set_message('Participant ' . ($count + 1) . ' has already registered for this event.');
              $form_state['step'] = 1;
              $form_state['rebuild'] = TRUE;
              return;
            }

            $form_state['contacts'][$count] = entity_load_single('civicrm_contact', $dedupe_result['id']);
          }
          // submit main registrant contact profile here, if there isn't a contact_id yet (its new)
          // we need the contact_id to run the transaction and create a contribution
          // if not, $success = false, and it displays an error and goes back to the registration form
          if ($count == 0) {
            if (empty($form_state['contacts'][$count]->id)) {
              $submitted_result = 0;
              if (!empty($first_pid) && !empty($first_profile)) {
                $submitted_result = _civicrm_entity_profile_process_profile_submitted_data($first_pid, $first_profile['profile']);
              }
              if (!empty($submitted_result)) {
                $form_state['contacts'][0] = $submitted_result;
                $form_state['first_profile_submitted'] = 1;
              }
              else {
                $success = FALSE;
              }
            }
          }
        }
      }

      if (!empty($form_state['storage']['registration_form']['transaction']['total']) &&
        is_numeric($form_state['storage']['registration_form']['transaction']['total']) &&
        $form_state['storage']['registration_form']['transaction']['total'] > 0
      ) {
        if (empty($form_state['storage']['registration_form']['pay_later'])) {
          // if we have a main registrant contact id we continue to process the transaction
          if ($success) {
            $transact_result = _civicrm_entity_price_set_field_run_cc_transaction($form_state['display_settings'], $form_state['price_set_data'], 'civicrm_event', $event, $form_state['contacts'], $form_state['storage']['registration_form']);
            if ($transact_result !== FALSE && !empty($transact_result['contribution']) && is_object($transact_result['contribution'])) {
              $form_state['contribution'] = $transact_result['contribution'];
            }
            else {
              $success = FALSE;
            }
          }
        }
        else {
          // if is pay later, make a contribution with status Pending
          if ($success) {
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
      }


      if (!empty($success)) {
        _civicrm_entity_price_set_field_process_event_registration($form_state);
        $form_state['step']++;
      }
      else {
        drupal_set_message(t('Error processing registration. Contact Site Administrator.'));
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
 * Process event registration (after contacts deduped and reg status checked, successful cc transaction if paid event)
 *
 * @param $form_state
 */
function _civicrm_entity_price_set_field_process_event_registration(&$form_state) {
  $form_state['participants'] = array();
  if (empty($form_state['contribution'])) {
    $form_state['contribution'] = NULL;
  }

  $line_items = array();

  foreach ($form_state['contacts'] as $count => $contact) {
    //_civicrm_entity_price_set_field_display_form_create_update_contact('other', $count, $form_state);
    $submitted_count = 0;
    foreach ($form_state['storage']['registration_form']['contacts'][$count]['contact_info'] as $profile_id => $profile_data) {
      $pids = explode('-', $profile_id);
      $pid = $pids[1];
      $profile_entity_type =  _civicrm_entity_profile_determine_profile_entity($pid);
      if ($profile_entity_type != 'contact') {
        continue;
      }
      if ($submitted_count == 0 && !empty($form_state['first_profile_submitted'])) {
        continue;
      }
      // here we want to submit only the contact profiles first
      if (!empty($contact->id)) {
        $submit_result = _civicrm_entity_profile_process_profile_submitted_data($pid, $profile_data['profile'], 'contact', $contact->id);
      }
      else {
        $submit_result = _civicrm_entity_profile_process_profile_submitted_data($pid, $profile_data['profile']);
      }
      if (!empty($submit_result)) {
        $form_state['contacts'][$count] = $contact = $submit_result;
        unset($submit_result);
      }

      $submitted_count += 1;
    }

    try {
      // now create the participant record
      $params = array(
        'is_new' => TRUE,
        'event_id' => $form_state['event']->id,
        'contact_id' => $form_state['contacts'][$count]->id,
        'source' => "CiviCRM Entity Price Set Field Event Registration Form",
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

      // here run the participant profiles
      foreach ($form_state['storage']['registration_form']['contacts'][$count]['contact_info'] as $profile_id => $profile_data) {
        $pids = explode('-', $profile_id);
        $pid = $pids[1];
        $profile_entity_type = _civicrm_entity_profile_determine_profile_entity($pid);
        if ($profile_entity_type != 'participant') {
          continue;
        }
        $profile_data['profile']['participant_id'] = $form_state['participants'][$count]->id;
        if (!empty($form_state['contacts'][$count]->id)) {
          $submit_result = _civicrm_entity_profile_process_profile_submitted_data($pid, $profile_data['profile'], 'participant', $form_state['contacts'][$count]->id);
          if (!empty($submit_result)) {
            $form_state['participants'][$count] = $submit_result;
            unset($submit_result);
          }
        }
      }

      // if there was a contribution make the LineItem and ParticipantPayment records
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
        // create line items
        foreach ($form_state['storage']['registration_form']['transaction']['line_items'][$count] as $pf_id => $price_field) {
          foreach ($price_field as $pfv_id => $price_field_value) {
            $line_item_params = array(
              'entity_table' => 'civicrm_participant',
              'entity_id' => $form_state['participants'][$count]->id,
              'label' => $form_state['price_set_data'][$count]['price_fields'][$pf_id]['price_field_values'][$price_field_value['price_field_value_id']]->label,
              'qty' => $price_field_value['qty'],
              'contribution_id' => $form_state['contribution']->id,
              'price_field_id' => $price_field_value['price_field_id'],
              'price_field_value_id' => $price_field_value['price_field_value_id'],
              'financial_type_id' => !empty($price_field_value['financial_type_id']) ? $price_field_value['financial_type_id'] : $form_state['event']->financial_type_id,
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
      drupal_set_message(check_plain('Error creating participant record or financial records for registration for contact: ' . $form_state['contacts'][$count]->first_name . ' ' . $form_state['contacts'][$count]->last_name . '  -- ' . $e->getMessage()));
    }
  }

  // trigger rule event with all the pertinent data here
  $event_variables = array(
    'event' => $form_state['event'],
    'contacts' => $form_state['contacts'],
    'participants' => $form_state['participants'],
  );

  rules_invoke_event_by_args('civicrm_entity_price_set_field_event_registration', $event_variables);
}

/**
 * Validation Handler for the CiviCRM entity price set event registration form display
 *
 * @param $form
 * @param $form_state
 */
function civicrm_entity_price_set_field_display_form_event_validate($form, $form_state) {
  // cc card block validation
  if ($form_state['step'] == 1 && !empty($form_state['event']->is_monetary) && empty($form_state['values']['registration_form']['pay_later'])) {
    if (empty($form_state['values']['registration_form']['registration_cc_block']['first_name_on_card'])) {
      form_set_error('registration_form][registration_cc_block][first_name_on_card', 'First name on card field required.');
    }
    if (empty($form_state['values']['registration_form']['registration_cc_block']['last_name_on_card'])) {
      form_set_error('registration_form][registration_cc_block][last_name_on_card', 'Last name on card field required.');
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
  // price set field text widget validation
  if ($form_state['step'] == 1) {
    foreach ($form_state['price_set_data'] as $count => $ps_data) {
      foreach ($form_state['price_set_data'][$count]['price_fields'] as $pf_id => $pf_data) {
        if ($pf_data['pf_entity']->html_type == 'Text') {
          foreach ($form_state['values']['registration_form']['contacts'] as $index => $submitted_values) {
            if (!empty($submitted_values['price_set'][$pf_id]['price_field_values']) && !is_numeric($submitted_values['price_set'][$pf_id]['price_field_values'])) {
              form_set_error("registration_form][contacts][$index][price_set][$pf_id][price_field_values", 'Must be numeric.');
            }
          }
        }
      }
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
    if (count($form_state['input']['registration_form']['contacts'][0]['contact_info'])) {
      foreach ($form_state['input']['registration_form']['contacts'][0]['contact_info'] as $profile => $fapi) {
        if (count($form_state['input']['registration_form']['contacts'][0]['contact_info'][$profile]['profile'])) {
          foreach ($form_state['input']['registration_form']['contacts'][0]['contact_info'][$profile]['profile'] as $name => $value) {
            $form_state['input']['registration_form']['contacts'][0]['contact_info'][$profile]['profile'][$name] = '';
          }
        }
      }
    }

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
        if (count($form_state['input']['registration_form']['contacts'][0]['contact_info'])) {
          foreach ($form_state['input']['registration_form']['contacts'][0]['contact_info'] as $profile => $fapi) {
            if (count($form_state['input']['registration_form']['contacts'][0]['contact_info'][$profile]['profile'])) {
              foreach ($form_state['input']['registration_form']['contacts'][0]['contact_info'][$profile]['profile'] as $name => $value) {
                $form_state['input']['registration_form']['contacts'][0]['contact_info'][$profile]['profile'][$name] = !empty($contact->{$name}) ? $contact->{$name} : '';
              }
            }
          }
        }
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
 * Function to query number of participants for an event, with optional contact_id
 *
 * @param $event_id
 * @param int $contact_id
 * @return int
 */
function _civicrm_entity_price_set_field_get_participant_count_for_event($event_id, $contact_id = 0) {
  if (!empty($event_id) && is_numeric($event_id)) {
    $query = new EntityFieldQuery();
    $query->entityCondition('entity_type', 'civicrm_participant')
      ->propertyCondition('event_id', $event_id);
    if (!empty($contact_id) && is_numeric($contact_id)) {
      $query->propertyCondition('contact_id', $contact_id);
    }
    $results = $query->execute();
    if (!empty($results['civicrm_participant'])) {
      return count($results['civicrm_participant']);
    }
  }
  return 0;
}
