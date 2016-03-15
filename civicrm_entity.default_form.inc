<?php

function civicrm_entity_form($form, &$form_state, $entity_id, $op, $entity_type) {

  if ($op == 'edit' || $op == 'delete') {
    $entity = entity_load($entity_type, array($entity_id));
    $entity = $entity[$entity_id];
    $form_state['entity'] = $entity;
    $form_state['entity_type'] = $entity_type;
  }
  if ($op == 'create') {
    $entity = new CiviCRMEntity(array(), $entity_type);
    $entity->is_new = TRUE;
    $entity->type = $entity_type;
    $form_state['entity'] = $entity;
    $form_state['entity_type'] = $entity_type;
  }
  // Add the field related form elements.
  if ($op == 'edit' || $op == 'create') {
    if($op == 'edit') {
      $title = 'Edit ' . ucwords(str_replace("_", " ", $entity_type)) . ": " . entity_label($entity_type, $entity) . " (id:" . $entity->id . ")";
    }
    else {
      $title = 'Add ' . ucwords(str_replace("_", " ", $entity_type));
    }

    field_attach_form($entity_type, $entity, $form, $form_state);

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
        if(isset($info['widget']['widget'])){
          $form[$name] = array(
              '#type' => $info['widget']['widget'],
              '#title' => $info['label'],
              '#description' => !empty($info['description']) ? $info['description'] : '',
              '#default_value' => $op == 'edit' ? $child->value() : '',
          );
          // set api required fields to be required on form
          if(isset($info['required']) && $info['required']) {
            $form[$name]['#required'] = TRUE;
          }
          //set the options for select options
          if($form[$name]['#type'] == 'select') {
            $form[$name]['#options'] = $info['widget']['options'];
          }
          // Date field handling, check for date_api module to use date select widget
          // else fallback to textfield
          if($form[$name]['#type'] == 'date_select') {
            if(module_exists('date_api')){
              $form[$name]['#format'] = $info['widget']['format'];
            }
            else {
              $form[$name]['#type'] = 'textfield';
            }
          }
          //for some reason the is_deleted column of the contact record is coming to the entity
          // as contact_is_deleted ...special handling to have the form value set properly
          if($name == 'is_deleted') {
            if($entity_type=='civicrm_contact') {
              $form[$name]['#default_value'] = $entity->contact_is_deleted;
            }
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
  $entity = $form_state['entity'];
  $entity_type = $form_state['entity_type'];
  foreach ($form_state['values'] as $key => $value) {
    $entity->{$key} = $value;
  }
  // Notify field widgets to validate their data.
  field_attach_form_validate($entity_type, $entity, $form, $form_state);

}

/**
 * Form API submit callback for the entity form.
 *
 */
function civicrm_entity_form_submit(&$form, &$form_state) {
  $entity_type = $form_state['entity_type'];
  $entity = $form_state['entity'];
  foreach ($form_state['values'] as $key => $value) {
    $entity->{$key} = $value;
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