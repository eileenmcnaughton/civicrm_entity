<?php

function civicrm_entity_form($form, &$form_state, $entity, $op, $entity_type) {
  if ($op == 'edit' || $op == 'delete') {
    if(!is_object($entity)) {
      drupal_set_title('No ' . $entity_type . ' with id:' . $entity);
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

    if ($op == 'edit'){
      if(is_object($entity)) {
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
        if (isset($info['widget']['widget'])){
          $form[$name] = array(
              '#type' => $info['widget']['widget'],
              '#title' => $info['label'],
              '#description' => !empty($info['description']) ? $info['description'] : '',
          );

          // set api required fields to be required on form
          if (!empty($info['required'])) {
            $form[$name]['#required'] = TRUE;
          }

          // FK Reference field handling
          if($form[$name]['#type'] == 'civi_fk_reference'){
            switch($info['widget']['subtype']){
              case 'autocomplete':
                $form[$name]['#type'] = 'textfield';
                $form[$name]['#autocomplete_path'] = 'civicrm-entity/autocomplete/' . $info['widget']['entity'];
                break;
              case 'select':
                $form[$name]['#type'] = 'select';
                $form[$name]['#options'] = !empty($entity_form[$name]['#required']) ? _civicrm_entity_get_entities_list($info['widget']['entity']) : array(NULL => 'None') + _civicrm_entity_get_entities_list($info['widget']['entity']);
                break;
              default:
                $form[$name]['#type'] = 'textfield';
                break;
            }
          }

          if($op == 'edit') {
            $form[$name]['#default_value'] = $child->value();
          }
          elseif($op == 'create') {
            if(isset($info['default_value'])){
              $form[$name]['#default_value'] = $info['default_value'];
            }
            else {
              $form[$name]['#default_value'] = NULL;
            }
          }

          // set the #size and #maxlength FAPI properties if the #type is a textfield
          if($form[$name]['#type'] == 'textfield') {
            if(!empty($info['size'])) {
              $form[$name]['#size'] = $info['size'];
            }
            if(!empty($info['maxlength'])) {
              $form[$name]['#maxlength'] = $info['maxlength'];
            }
          }

          //set the options for select options, radios, and checkboxes
          if ($form[$name]['#type'] == 'select') {
            if(!empty($info['widget']['options'])) {
              $form[$name]['#options'] = $info['widget']['options'];
              if (!empty($info['required'])) {
                if (isset($form[$name]['#options'][''])) {
                  unset($form[$name]['#options']['']);
                }
              }
            }
          }
          elseif ($form[$name]['#type'] == 'radios'){
            $form[$name]['#options'] = $info['widget']['options'];
            if(!empty($info['required'])) {
              if(isset($form[$name]['#options'][''])) {
                unset($form[$name]['#options']['']);
              }
            }
          }
          elseif ($form[$name]['#type'] == 'checkboxes'){
            if($op == 'edit') {
              $value = $child->value();
              if(!empty($value)) {
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
            elseif($op == 'create') {
              if(isset($info['default_value'])){
                $form[$name]['#default_value'] = explode(CRM_Core_DAO::VALUE_SEPARATOR, $form[$name]['#default_value']);
              }
            }

            $form[$name]['#options'] = $info['widget']['options'];
            if(!empty($info['required'])) {
              if(isset($form[$name]['#options'][''])) {
                unset($form[$name]['#options']['']);
              }
            }
          }
          // Date field handling, check for date_api module to use date select widget
          // else fallback to textfield
          elseif ($form[$name]['#type'] == 'date_select') {
            if (module_exists('date_api')){
              $form[$name]['#format'] = $info['widget']['format'];
            }
            else {
              $form[$name]['#type'] = 'textfield';
            }
          }

          //for some reason the is_deleted column of the contact record is coming to the entity
          // as contact_is_deleted ...special handling to have the form value set properly
          if ($name == 'is_deleted') {
            if ($entity_type=='civicrm_contact') {
              $form[$name]['#default_value'] = isset($entity->contact_is_deleted) ? $entity->contact_is_deleted : 0;
            }
          }

          // make sure to disable id field, and any custom field with is_view = 1
          if($name == 'id' || !empty($info['is_view'])) {
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
        '#markup' => '<div></strong>' . t('Are you sure you want to delete ') .
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

  foreach ($form_state['values'] as $key => $value) {
    $entity->{$key} = $value;
  }

  // validate civicrm data
  $entity_metadata_info = entity_get_property_info($entity_type);
  foreach($entity_metadata_info['properties'] as $name => $info) {
    if(!empty($info['type'])) {
      // integer validation
      if ($info['type'] == 'integer') {
        //text field validation
        if (isset($form[$name]['#type']) && $form[$name]['#type'] == 'textfield') {
          if (isset($form_state['values'][$name]) && strlen($form_state['values'][$name]) && (string)(int)$form_state['values'][$name] !== (string)$form_state['values'][$name]) {
            form_set_error($name, t('Value must be an integer. e.g (1, 154, 0)'));
          }
        }
      }
      // number validation
      // money validation
      elseif($info['type'] == 'decimal') {
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
      if (!isset($form['first_name']) && !isset($form['middle_name']) && !isset($form['last_name'])) {
        form_set_error('contact_type', t('Individual contact type, form must include at least one of first, middle, or last name fields.'));
      }
      elseif (empty($form_state['values']['first_name']) && empty($form_state['values']['middle_name']) && empty($form_state['values']['last_name'])) {
        form_set_error('first_name', t('At least one of first, middle, or last names must be set when contact type is set to Individual.'));
        form_set_error('middle_name');
        form_set_error('last_name');
      }
    }
    elseif ($form_state['values']['contact_type'] == 'Household') {
      if (!isset($form['household_name'])){
        form_set_error('contact_type', t('Household contact type, form must include the household name field.'));
      }
      elseif (empty($form_state['values']['household_name'])) {
        form_set_error('household_name', t('Household name is required when contact type is set to Household'));
      }
    }
    elseif ($form_state['values']['contact_type'] == 'Organization') {
      if (!isset($form['organization_name'])){
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
  $civicrm_entity_fields = civicrm_api(substr_replace($entity_type,'', 0, 8),  'getfields', array(
    'version' => 3,
    'action' => 'create',
  ));

  foreach ($form_state['values'] as $key => $value) {
    //need to convert checkbox values to a consumable format for checkboxes custom fields
    if(substr($key, 0, 7) == 'custom_'){
      if($civicrm_entity_fields['values'][$key]['html_type'] == 'CheckBox'){
        if(is_array($value)) {
          $stored_value = array();
          foreach($value as $option => $checkbox_value){
            if($checkbox_value) {
              $stored_value[] = $option;
            }
          }
          $entity->{$key} = !empty($stored_value) ? $stored_value : array('');
        }

      }
      else {
        $entity->{$key} = $value;
      }

      if(!empty($civicrm_entity_fields['values'][$key]['is_view'])) {
        unset($entity->{$key});
      }

    }
    else {
      $entity->{$key} = $value;
    }
  }

  // Add in created and changed times.
  if ($entity->is_new = isset($entity->is_new) ? $entity->is_new : 0) {
    $entity->created = time();
  }
  $entity->changed = time();

  $wrapper = entity_metadata_wrapper($entity_type, $entity);
  field_attach_submit($entity_type, $entity, $form, $form_state);

  $wrapper->save();

  $t_args = array(
      '%label' => entity_label($entity_type, $entity),
  );

  $destination = drupal_get_destination();
  $request_path = request_path();
  if(isset($destination['destination']) && $destination['destination'] != '' && $destination['destination'] != $request_path ) {
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
  if(isset($destination['destination']) && $destination['destination'] != '' && $destination['destination'] != $request_path ) {
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
  if(isset($destination['destination']) && $destination['destination'] != '' && $destination['destination'] != $request_path ) {
    $form_state['redirect'] = $destination['destination'];
  }
  else {
    $form_state['redirect'] = str_replace('_', '-', $entity_type) . '/' . $entity->id;
  }
  drupal_redirect_form($form_state);
}