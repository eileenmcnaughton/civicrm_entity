<?php

function civicrm_entity_form($form, &$form_state, $entity, $op, $entity_type) {
  if ($op == 'edit' || $op == 'delete') {
    if (!is_object($entity)) {
      drupal_set_title(t('No ' . $entity_type . ' with id:' . $entity));
      return $form;
    }
    $form_state['entity'] = $entity;
    $form_state['entity_type'] = $entity_type;
    $form['#entity'] = $entity;
  }
  if ($op == 'create') {
    $entity = new CiviCRMEntity(array(), $entity_type);
    $entity->is_new = TRUE;
    $entity->type = $entity_type;
    $form_state['entity'] = $entity;
    $form_state['entity_type'] = $entity_type;
    $form['#entity'] = $entity;
  }
  // Add the field related form elements.
  if ($op == 'edit' || $op == 'create') {
    if ($op == 'edit') {
      $title = 'Edit ' . ucwords(str_replace("_", " ", $entity_type)) . ": " . entity_label($entity_type, $entity) . " (id:" . $entity->id . ")";
    }
    else {
      $title = 'Add ' . ucwords(str_replace("_", " ", $entity_type));
    }

    field_attach_form($entity_type, $entity, $form, $form_state);

    if ($op == 'edit') {
      if (is_object($entity)) {
        $entity->original = clone $entity;
      }
    }

    //get entity controller build content
    if ($op == 'edit') {
      $wrapper = entity_metadata_wrapper($entity_type, $entity);
    }
    else {
      $wrapper = entity_metadata_wrapper($entity_type);
    }

    foreach ($wrapper as $name => $child) {
      $info = $child->info();
      if (strpos($info['name'], 'field_') === 0) {
        continue;
      }
      else {
        if (isset($info['widget']['widget'])) {
          $form[$name] = array(
            '#type' => $info['widget']['widget'],
            '#title' => $info['label'],
            '#description' => !empty($info['description']) ? $info['description'] : '',
          );

          // set api required fields to be required on form
          if (!empty($info['required'])) {
            $form[$name]['#required'] = TRUE;
          }

          if ($form[$name]['#type'] == 'civi_fk_reference') {
            switch ($info['widget']['subtype']) {
              case 'autocomplete':
                $form[$name]['#type'] = 'textfield';
                $form[$name]['#autocomplete_path'] = 'civicrm-entity/autocomplete/' . $info['widget']['entity'];
                break;
              case 'select':
                $form[$name]['#type'] = 'select';
                $form[$name]['#options'] = !empty($entity_form[$name]['#required']) ? _civicrm_entity_get_entities_list($info['widget']['entity']) : array(NULL => 'None') + _civicrm_entity_get_entities_list($info['widget']['entity']);
                if ($info['widget']['entity'] == 'Event') {
                  array_shift($form[$name]['#options']);
                }
                break;
              default:
                $form[$name]['#type'] = 'textfield';
                break;
            }
          }

          // Handle default value
          if ($op == 'edit') {
            $form[$name]['#default_value'] = $child->value();
            if ($entity_type == 'civicrm_participant') {
              if ($name == 'status_id' && !empty($entity->participant_status_id)) {
                $form[$name]['#default_value'] = $entity->participant_status_id;
              }
              if ($name == 'role_id' && !empty($entity->participant_role_id)) {
                $form[$name]['#default_value'] = $entity->participant_role_id;
              }
              if ($name == 'source' && !empty($entity->participant_source)) {
                $form[$name]['#default_value'] = $entity->participant_source;
              }
              if ($name == 'register_date' && !empty($entity->participant_register_date)) {
                $form[$name]['#default_value'] = $entity->participant_register_date;
              }
              if ($name == 'registered_by_id' && !empty($entity->participant_registered_by_id)) {
                $form[$name]['#default_value'] = $entity->participant_registered_by_id;
              }
              if ($name == 'fee_amount' && !empty($entity->participant_fee_amount)) {
                $form[$name]['#default_value'] = $entity->participant_fee_amount;
              }
            }
          }
          elseif ($op == 'create') {
            if (isset($info['default_value'])) {
              $form[$name]['#default_value'] = $info['default_value'];
            }
            else {
              $form[$name]['#default_value'] = NULL;
            }
          }

          // set the #size and #maxlength FAPI properties if the #type is a textfield
          if ($form[$name]['#type'] == 'textfield') {
            if (!empty($info['size'])) {
              $form[$name]['#size'] = $info['size'];
            }
            if (!empty($info['maxlength'])) {
              $form[$name]['#maxlength'] = $info['maxlength'];
            }
          }

          //set rows and cols if available for textarea or text_format
          if ($form[$name]['#type'] == 'textarea' || $form[$name]['#type'] == 'text_format') {
            if ($form[$name]['#type'] == 'text_format') {
              $form[$name]['#format'] = 'full_html';
            }
            // set rows value if not empty
            if (!empty($info['rows'])) {
              $form[$name]['#rows'] = $info['rows'];
            }
            if (!empty($info['cols'])) {
              $form[$name]['#cols'] = $info['cols'];
            }
          }

          //set the options for select options, radios, and checkboxes
          if ($form[$name]['#type'] == 'select') {
            if (!empty($info['widget']['options'])) {
              $form[$name]['#options'] = $info['widget']['options'];
              if (!empty($info['required'])) {
                if (isset($form[$name]['#options'][''])) {
                  unset($form[$name]['#options']['']);
                }
              }
            }
          }
          elseif ($form[$name]['#type'] == 'radios') {
            $form[$name]['#options'] = $info['widget']['options'];
            if (!empty($info['required'])) {
              if (isset($form[$name]['#options'][''])) {
                unset($form[$name]['#options']['']);
              }
            }
          }
          elseif ($form[$name]['#type'] == 'checkboxes') {
            if ($op == 'edit') {
              $value = $child->value();
              if (!empty($value)) {
                foreach ($info['widget']['options'] as $key => $label) {
                  if (in_array($key, $value)) {
                    $default_values[] = $key;
                  }
                }
                $form[$name]['#default_value'] = $default_values;
              }
              else {
                $form[$name]['#default_value'] = array('');
              }
            }
            elseif ($op == 'create') {
              if (isset($info['default_value'])) {
                $form[$name]['#default_value'] = explode(CRM_Core_DAO::VALUE_SEPARATOR, $form[$name]['#default_value']);
              }
            }

            $form[$name]['#options'] = $info['widget']['options'];
            if (!empty($info['required'])) {
              if (isset($form[$name]['#options'][''])) {
                unset($form[$name]['#options']['']);
              }
            }
          }
          // Date field handling, check for date_api module to use date select widget
          // else fallback to textfield
          elseif ($form[$name]['#type'] == 'date_select') {
            if (module_exists('date_api')) {
              if (module_exists('date_popup')) {
                $form[$name]['#type'] = 'date_popup';
              }
              $form[$name]['#format'] = $info['widget']['format'];
              $form[$name]['#date_year_range'] = '-115:+10';
            }
            else {
              $form[$name]['#type'] = 'textfield';
            }
          }

          // contact special handling
          if ($entity_type == 'civicrm_contact') {
            //for some reason the is_deleted column of the contact record is coming to the entity
            // as contact_is_deleted ...special handling to have the form value set properly
            if ($name == 'is_deleted') {
              $form[$name]['#default_value'] = isset($entity->contact_is_deleted) ? $entity->contact_is_deleted : 0;
            }
            if ($name == 'contact_type') {
              $form[$name]['#ajax'] = array(
                'event' => 'change',
                'wrapper' => 'contact-subtype-wrapper',
                'callback' => '_civicrm_entity_form_contact_subtype_ajax_callback',
                'method' => 'replace',
              );
            }
            if ($name == 'contact_sub_type') {
              $contact_type = !empty($form['contact_type']['#default_value']) ? $form['contact_type']['#default_value'] : 'Individual';
              $form[$name]['#type'] = 'select';
              $form[$name]['#options'] = _civicrm_entity_form_contact_get_subtypes($contact_type);
              $form[$name]['#prefix'] = '<div id="contact-subtype-wrapper">';
              $form[$name]['#suffix'] = '</div>';
              $form[$name]['#validated'] = TRUE;
              unset($form[$name]['#size']);
            }
          }

          // event special handling
          if ($entity_type == 'civicrm_event') {
            if ($name == 'thankyou_text' || $name == 'thankyou_footer_text') {
              $form[$name]['#type'] = 'text_format';
              $form[$name]['format'] = 'full_html';
            }
            if ($name == 'event_type_id') {
              $form[$name]['#required'] = TRUE;
            }
          }

          // case special handling
          if ($entity_type == 'civicrm_case') {
            if($name == 'creator_id') {
              unset($creator_id);
            }
          }

          // make sure to disable id field, and any custom field with is_view = 1
          if ($name == 'id' || !empty($info['is_view'])) {
            $form[$name]['#disabled'] = TRUE;
          }

        } // end if isset widget
      } // end else not a drupal field
    } // end foreach

    $form['actions'] = array(
      '#type' => 'container',
      '#attributes' => array('class' => array('form-actions')),
      '#weight' => 400,
    );

    $form['#validate'] = array();
    $form['#submit'] = array();
    $form['#validate'][] = 'civicrm_entity_form_validate';
    $form['#submit'][] = 'civicrm_entity_form_submit';
    // We add the form's #submit array to this button along with the actual submit
    // handler to preserve any submit handlers added by a form callback_wrapper.
    $submit = array();

    if (!empty($form['#submit'])) {
      $submit += $form['#submit'];
    }

    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
      '#submit' => $submit + array(),
    );
  } //end if op is add or edit

  if ($op == 'delete') {

    $form['delete_markup'] = array(
      '#type' => 'markup',
      '#markup' => '<div></strong>' . t('Are you sure you want to delete') . ' ' .
        $entity_type . " id: " . $entity->id . '</strong></div>',
    );

    $form['actions'] = array(
      '#type' => 'container',
      '#attributes' => array('class' => array('form-actions')),
      '#weight' => 400,
    );

    $form['#validate'] = array();
    $form['#submit'] = array();
    $form['#validate'][] = 'civicrm_entity_delete_form_validate';
    $form['#submit'][] = 'civicrm_entity_delete_form_submit';
    // We add the form's #submit array to this button along with the actual submit
    // handler to preserve any submit handlers added by a form callback_wrapper.

    $form['actions']['cancel'] = array(
      '#type' => 'submit',
      '#value' => t('Cancel'),
      '#submit' => array('civicrm_entity_delete_form_cancel_submit'),
    );

    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Delete'),
      '#submit' => $form['#submit'],
    );

    $title = 'Delete ' . ucwords(str_replace("_", " ", $entity_type)) . ": " . entity_label($entity_type, $entity) . " (id:" . $entity->id . ")";;
  }
  $title = str_replace("Civicrm", "CiviCRM", $title);
  drupal_set_title($title);
  return $form;
}

/**
 * Form API validate callback for the entity form
 */
function civicrm_entity_form_validate(&$form, &$form_state) {
  $entity = $form['#entity'];
  $entity_type = $form['#entity_type'];

  // unset form and entity values if civicrm properties are hidden from the form by display suite
  if (module_exists('ds_forms')) {
    if ($ds_form = ds_build_load($form, $form['#form_id'])) {
      if ($layout = ds_get_layout($ds_form->entity_type, $ds_form->bundle, 'form', FALSE)) {
        foreach ($layout['settings']['fields'] as $field_name => $visibility) {
          if ($field_name != 'id' && $visibility == 'hidden' && strpos($field_name, 'field_') !== 0) {
            unset($form_state['values'][$field_name]);
            unset($entity->{$field_name});
          }
        }
      }
    }
  }

  foreach ($form_state['values'] as $key => $value) {
    if (!is_array($value)) {
      $entity->{$key} = $value;
    }
    elseif (isset($value['value'])) {
      $entity->{$key} = $value['value'];
    }
    else {
      $entity->{$key} = $value;
    }
  }

  // validate civicrm data
  $entity_metadata_info = entity_get_property_info($entity_type);
  foreach ($entity_metadata_info['properties'] as $name => $info) {
    if (!empty($info['type'])) {
      // integer validation
      if ($info['type'] == 'integer') {
        //text field validation
        if (isset($form[$name]['#type']) && $form[$name]['#type'] == 'textfield') {
          if (isset($form_state['values'][$name]) && strlen($form_state['values'][$name]) && (string) (int) $form_state['values'][$name] !== (string) $form_state['values'][$name]) {
            form_set_error($name, t('Value must be an integer. e.g (1, 154, 0)'));
          }
        }
      }
      // number validation
      // money validation
      elseif ($info['type'] == 'decimal') {
        //text field validation
        if (isset($form[$name]['#type']) && $form[$name]['#type'] == 'textfield') {
          if (isset($form_state['values'][$name]) && strlen($form_state['values'][$name]) && !is_numeric($form_state['values'][$name])) {
            form_set_error($name, t('Value must be an number. e.g (1, 1.26, -1.3)'));
          }
        }
      }
    }
  }
  // validate name for contact
  if ($entity_type == 'civicrm_contact') {
    if ($form_state['values']['contact_type'] == 'Individual') {
      if (!isset($form['first_name']) && !isset($form['middle_name']) && !isset($form['last_name']) && !isset($form['display_name'])) {
        form_set_error('contact_type', t('Individual contact type, form must include at least one of first, middle, last or display name fields.'));
      }
      elseif (empty($form_state['values']['first_name']) && empty($form_state['values']['middle_name']) && empty($form_state['values']['last_name']) && empty($form_state['values']['display_name'])) {
        form_set_error('first_name', t('At least one of first, middle, last or display names must be set when contact type is set to Individual.'));
        form_set_error('middle_name');
        form_set_error('last_name');
        form_set_error('display_name');
      }
    }
    elseif ($form_state['values']['contact_type'] == 'Household') {
      if (!isset($form['household_name'])) {
        form_set_error('contact_type', t('Household contact type, form must include the household name field.'));
      }
      elseif (empty($form_state['values']['household_name'])) {
        form_set_error('household_name', t('Household name is required when contact type is set to Household'));
      }
    }
    elseif ($form_state['values']['contact_type'] == 'Organization') {
      if (!isset($form['organization_name'])) {
        form_set_error('contact_type', t('Organization contact type, form must include the organization name field.'));
      }
      elseif (empty($form_state['values']['organization_name'])) {
        form_set_error('organization_name', t('Organization name is required when contact type is set to Organization.'));
      }
    }
  }


  // Notify field widgets to validate their data.
  field_attach_form_validate($entity_type, $entity, $form, $form_state);

}

/**
 * Form API submit callback for the entity form.
 *
 */
function civicrm_entity_form_submit(&$form, &$form_state) {
  $entity_type = $form['#entity_type'];
  $entity = $form['#entity'];
  civicrm_initialize();
  $civicrm_entity_fields = civicrm_api(substr_replace($entity_type, '', 0, 8), 'getfields', array(
    'version' => 3,
    'action' => 'create',
  ));
  // unset form and entity values if civicrm properties are hidden from the form by display suite
  if (module_exists('ds_forms')) {
    if ($ds_form = ds_build_load($form, $form['#form_id'])) {
      if ($layout = ds_get_layout($ds_form->entity_type, $ds_form->bundle, 'form', FALSE)) {
        foreach ($layout['settings']['fields'] as $field_name => $visibility) {
          if ($field_name != 'id' && $visibility == 'hidden' && strpos($field_name, 'field_') !== 0) {
            unset($form_state['values'][$field_name]);
            unset($entity->{$field_name});
          }
        }
      }
    }
  }

  foreach ($form_state['values'] as $key => $value) {
    //need to convert checkbox values to a consumable format for checkboxes custom fields
    if (substr($key, 0, 7) == 'custom_') {
      if ($civicrm_entity_fields['values'][$key]['html_type'] == 'CheckBox') {
        if (is_array($value)) {
          $stored_value = array();
          foreach ($value as $option => $checkbox_value) {
            if ($checkbox_value) {
              $stored_value[] = $option;
            }
          }
          $entity->{$key} = !empty($stored_value) ? $stored_value : array('');
        }

      }
      else {
        if (is_array($value) && isset($value['value'])) {
          $value = $value['value'];
        }
        $entity->{$key} = $value;
      }

      if (!empty($civicrm_entity_fields['values'][$key]['is_view'])) {
        unset($entity->{$key});
      }

    }
    else {
      if (is_array($value) && isset($value['value'])) {
        $value = $value['value'];
      }
      $entity->{$key} = $value;
    }
  }

  // Add in created and changed times.
  if ($entity->is_new = isset($entity->is_new) ? $entity->is_new : 0) {
    $entity->created = time();
  }
  $entity->changed = time();
  if($entity_type == 'civicrm_case') {
    unset($entity->creator_id);
  }

  $wrapper = entity_metadata_wrapper($entity_type, $entity);
  //field_attach_submit($entity_type, $entity, $form, $form_state);

  //$wrapper->save();
  // handling for civicrm entity reference fields on civicrm entities
  $target_id_columns = array();
  if ($entity->is_new) {
    foreach ($form as $key => $form_field) {
      if (strpos($key, 'field_') === 0) {
        $field = field_info_field($key);
        if ($field['type'] == 'civicrm_entity_reference') {
          $ief_id = $form_field['und']['#ief_id'];
          $host_entity_type = $form_state['inline_entity_form'][$ief_id]['instance']['entity_type'];
          if ($host_entity_type == $entity_type) {
            $target_id_columns[$key] = array($field['settings']['host_source_id'] => $field['settings']['target_id_column']);
          }
        }
      }
    }

    if (!empty($target_id_columns)) {
      // first save the entity
      $wrapper->save();
      // then update civicrm entity target id values with host id
      foreach ($target_id_columns as $key => $target_id_column) {
        $host_id = key($target_id_column);
        $target_id = reset($target_id_column);
        $ief_id = $form[$key]['und']['#ief_id'];
        foreach ($form_state['inline_entity_form'][$ief_id]['entities'] as $item_index => $item) {
          if(!empty($form_state['inline_entity_form'][$ief_id]['entities'][$item_index]['entity']->is_new)) {
            $form_state['inline_entity_form'][$ief_id]['entities'][$item_index]['entity']->{$target_id} = $entity->{$host_id};
          }
        }
      }
      // this will create the civicrm entity reference fields entities
      field_attach_submit($entity_type, $entity, $form, $form_state);
    }
    // if no civicrm entity reference fields, then handle "normally"
    else {
      field_attach_submit($entity_type, $entity, $form, $form_state);
      $wrapper->save();
    }
  }
  // is update, handle "normally"
  else {
    field_attach_submit($entity_type, $entity, $form, $form_state);
    $wrapper->save();
  }



  $t_args = array(
    '%label' => entity_label($entity_type, $entity),
  );

  $destination = drupal_get_destination();
  $request_path = request_path();
  if (isset($destination['destination']) && $destination['destination'] != '' && $destination['destination'] != $request_path) {
    $form_state['redirect'] = $destination['destination'];
  }
  else {
    $form_state['redirect'] = str_replace('_', '-', $entity_type) . '/' . $entity->id;
  }
  drupal_set_message(t('Drupal fields and %label properties have been updated.', $t_args));
  drupal_redirect_form($form_state);
}

/**
 * Form API validate callback for the entity delete form
 */
function civicrm_entity_delete_form_validate(&$form, &$form_state) {

}

/**
 * Form API submit callback for the entity delete form.
 *
 */
function civicrm_entity_delete_form_submit(&$form, &$form_state) {
  $entity_type = $form_state['entity_type'];
  $entity = $form_state['entity'];
  $wrapper = entity_metadata_wrapper($entity_type, $entity);
  $wrapper->delete();
  $destination = drupal_get_destination();
  $request_path = request_path();
  if (isset($destination['destination']) && $destination['destination'] != '' && $destination['destination'] != $request_path) {
    $form_state['redirect'] = $destination['destination'];
  }
  else {
    $form_state['redirect'] = '/';
  }
  drupal_redirect_form($form_state);
}

/**
 * Form API submit callback for the entity delete form cancel button.
 *
 */
function civicrm_entity_delete_form_cancel_submit(&$form, &$form_state) {
  $entity_type = $form_state['entity_type'];
  $entity = $form_state['entity'];


  $destination = drupal_get_destination();
  $request_path = request_path();
  if (isset($destination['destination']) && $destination['destination'] != '' && $destination['destination'] != $request_path) {
    $form_state['redirect'] = $destination['destination'];
  }
  else {
    $form_state['redirect'] = str_replace('_', '-', $entity_type) . '/' . $entity->id;
  }
  drupal_redirect_form($form_state);
}

/**
 * Ajax callback for populating contact subtype options
 *
 * @param $form
 * @param $form_state
 * @return mixed
 */
function _civicrm_entity_form_contact_subtype_ajax_callback(&$form, &$form_state) {
  $contact_type = $form_state['values']['contact_type'];
  $contact_subtypes = _civicrm_entity_form_contact_get_subtypes($contact_type);
  $form['contact_sub_type']['#options'] = $contact_subtypes;
  return $form['contact_sub_type'];
}

/**
 * Helper function to return array of contact subtypes given contact type
 *
 * @param $contact_type
 * @return array
 */
function _civicrm_entity_form_contact_get_subtypes($contact_type) {
  civicrm_initialize(TRUE);
  try {
    $result = civicrm_api3('ContactType', 'get', array(
      'parent_id' => $contact_type,
      'is_active' => 1,
    ));
    $contact_subtypes = array('' => '- None - ');
    if ($result['count']) {
      foreach ($result['values'] as $id => $subtype) {
        $contact_subtypes[$subtype['name']] = $subtype['label'];
      }
    }
  } catch (CiviCRM_API3_Exception $e) {
    $contact_subtypes = array('' => '- None - ');
  }
  return $contact_subtypes;
}
