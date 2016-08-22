<?php

class CivicrmInlineEntityFormController extends EntityInlineEntityFormController {
  /**
   * Overrides EntityInlineEntityFormController::entityForm().
   */
  public function entityForm($entity_form, &$form_state) {
    $entity_type = $form_state['entity_type'] = $entity_form['#entity_type'];
    $entity = $form_state['entity'] = $entity_form['#entity'];
    $entity->type = $entity_type;
    $op = $entity_form['#op'];

    field_attach_form($entity_type, $entity, $entity_form, $form_state);

    if ($op == 'edit'){
      if(is_object($entity)) {
        $entity->original = clone $entity;
      }
    }

    //get entity controller build content
    if ($op == 'edit') {
      $wrapper = entity_metadata_wrapper($entity_type, $entity);
      //$wrapper = entity_metadata_wrapper($entity_type, $entity_id);
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
          $entity_form[$name] = array(
            '#type' => $info['widget']['widget'],
            '#title' => $info['label'],
            '#description' => !empty($info['description']) ? $info['description'] : '',
          );
          // FK Reference field handling
          if($entity_form[$name]['#type'] == 'civi_fk_reference'){
            switch($info['widget']['subtype']){
              case 'autocomplete':
                $entity_form[$name]['#type'] = 'textfield';
                $entity_form[$name]['#autocomplete_path'] = 'civicrm-entity/autocomplete/' . $info['widget']['entity'];
                break;
              default:
                $entity_form[$name]['#type'] = 'textfield';
                break;
            }
          }

          if($op == 'edit') {
            $entity_form[$name]['#default_value'] = $child->value();
          }
          elseif($op == 'create' || $op == 'add') {
            if(isset($info['default_value'])){
              $entity_form[$name]['#default_value'] = $info['default_value'];
            }
            else {
              $entity_form[$name]['#default_value'] = NULL;
            }
          }

          // set the #size and #maxlength FAPI properties if the #type is a textfield
          if($entity_form[$name]['#type'] == 'textfield') {
            if(!empty($info['size'])) {
              $entity_form[$name]['#size'] = $info['size'];
            }
            if(!empty($info['maxlength'])) {
              $entity_form[$name]['#maxlength'] = $info['maxlength'];
            }
          }

          // set api required fields to be required on form
          if (!empty($info['required'])) {
            $entity_form[$name]['#required'] = TRUE;
          }

          //set the options for select options, radios, and checkboxes
          if ($entity_form[$name]['#type'] == 'select') {
            $entity_form[$name]['#options'] = $info['widget']['options'];
            if(!empty($info['required'])) {
              if(isset($entity_form[$name]['#options'][''])) {
                unset($entity_form[$name]['#options']['']);
              }
            }
          }
          elseif ($entity_form[$name]['#type'] == 'radios'){
            $entity_form[$name]['#options'] = $info['widget']['options'];
            if(!empty($info['required'])) {
              if(isset($entity_form[$name]['#options'][''])) {
                unset($entity_form[$name]['#options']['']);
              }
            }
          }
          elseif ($entity_form[$name]['#type'] == 'checkboxes'){
            if($op == 'edit') {
              $value = $child->value();
              if(!empty($value)) {
                foreach ($info['widget']['options'] as $key => $label) {
                  if (in_array($key, $value)) {
                    $default_values[] = $key;
                  }
                }
                $entity_form[$name]['#default_value'] = $default_values;
              }
              else {
                $entity_form[$name]['#default_value'] = array('');
              }
            }
            elseif($op == 'create' || $op == 'add') {
              if(isset($info['default_value'])){
                $entity_form[$name]['#default_value'] = explode(CRM_Core_DAO::VALUE_SEPARATOR, $entity_form[$name]['#default_value']);
              }
            }

            $entity_form[$name]['#options'] = $info['widget']['options'];
            if(!empty($info['required'])) {
              if(isset($entity_form[$name]['#options'][''])) {
                unset($entity_form[$name]['#options']['']);
              }
            }
          }
          // Date field handling, check for date_api module to use date select widget
          // else fallback to textfield
          elseif ($entity_form[$name]['#type'] == 'date_select') {
            if (module_exists('date_api')){
              $entity_form[$name]['#format'] = $info['widget']['format'];
            }
            else {
              $entity_form[$name]['#type'] = 'textfield';
            }
          }

          //for some reason the is_deleted column of the contact record is coming to the entity
          // as contact_is_deleted ...special handling to have the form value set properly
          if ($name == 'is_deleted') {
            if ($entity_type=='civicrm_contact') {
              $entity_form[$name]['#default_value'] = isset($entity->contact_is_deleted) ? $entity->contact_is_deleted : 0;
            }
          }

          // Automatically set the referenced entity target id column with the id of the host entity
          if( $op == 'add' || $op == 'create' ) {
            $field_name = $entity_form['#parents'][0];
            $field_info = field_info_field($field_name);
            $target_id_column = $field_info['settings']['target_id_column'];
            $host_id_prop = $field_info['settings']['host_source_id'];
            if ($name == $target_id_column && isset($form_state['complete form'][$host_id_prop]['#default_value'])) {
              $entity_form[$name]['#default_value'] = $form_state['complete form'][$host_id_prop]['#default_value'];
              $entity_form[$name]['#disabled'] = TRUE;
            }
          }

          // make sure to disable id field, and any custom field with is_view = 1
          if($name == 'id' || !empty($info['is_view'])) {
            $entity_form[$name]['#disabled'] = TRUE;
          }

        } // end if isset widget
      } // end else not a drupal field
    } // end foreach
    return $entity_form;
  }
  
  public function entityFormValidate($entity_form, &$form_state) {
    $entity_type = $form_state['entity_type'];
    $field_name = $entity_form['#parents'][0] ;
    $ief_id = $entity_form['#ief_id'];
    $op = $entity_form['#op'];

    if ($op == 'edit')
     {
      foreach ($form_state['inline_entity_form'][$ief_id]['entities'] as $k => $entity_array) {
        if (isset($form_state['values'][$field_name]['und']['entities'][$k]['form'])){
          $entity = $entity_array['entity'];
          foreach ($form_state['values'][$field_name]['und']['entities'][$k]['form'] as $key => $value) {
            $entity->{$key} = $value;
          }
         // field_attach_form_validate($entity_type, $entity, $entity_form, $form_state);
        }
      }
    }
    elseif ($op == 'create' || $op == 'add') {
      $entity = $form_state['entity'];
      
      foreach ($form_state['values'][$field_name]['und']['form'] as $key => $value) {
        $entity->{$key} = $value;
      }

    }

    if($op == 'create' || $op == 'add' || $op == 'edit') {
      // validate civicrm data
      $entity_metadata_info = entity_get_property_info($entity_type);
      foreach ($form_state['inline_entity_form'][$ief_id]['entities'] as $delta => $entity_array) {
        foreach ($entity_metadata_info['properties'] as $name => $info) {
          if (!empty($info['type'])) {
            // integer validation
            if ($info['type'] == 'integer') {
              //text field validation
              if (isset($entity_form[$name]['#type']) && $entity_form[$name]['#type'] == 'textfield') {
                if ( isset($form_state['values'][$field_name][LANGUAGE_NONE]['entities'][$delta]['form'][$name]) &&
                    strlen($form_state['values'][$field_name][LANGUAGE_NONE]['entities'][$delta]['form'][$name]) &&
                    (string)(int)$form_state['values'][$field_name][LANGUAGE_NONE]['entities'][$delta]['form'][$name] !== (string)$form_state['values'][$field_name][LANGUAGE_NONE]['entities'][$delta]['form'][$name] ) {
                  form_set_error($field_name . '][' . LANGUAGE_NONE . '][entities][' . $delta . '][form][' . $name, 'Value must be an integer. e.g (1, 154, 0)');
                }
              }
            }
            // number validation
            // money validation
            elseif ($info['type'] == 'decimal') {
              //text field validation
              if (isset($entity_form[$name]['#type']) && $entity_form[$name]['#type'] == 'textfield') {
                if ( isset($form_state['values'][$field_name][LANGUAGE_NONE]['entities'][$delta]['form'][$name]) &&
                    strlen($form_state['values'][$field_name][LANGUAGE_NONE]['entities'][$delta]['form'][$name]) &&
                    !is_numeric($form_state['values'][$field_name][LANGUAGE_NONE]['entities'][$delta]['form'][$name]) ) {
                  form_set_error($field_name . '][' . LANGUAGE_NONE . '][entities][' . $delta . '][form][' . $name, 'Value must be an number. e.g (1, 1.26, -1.3)');
                }
              }
            }
          }
        }
      }
      field_attach_form_validate($entity_type, $entity, $entity_form, $form_state);
    }

    parent::entityFormValidate($entity_form, $form_state);
  }
  
  public function entityFormSubmit(&$entity_form, &$form_state) {
    $entity_type = $form_state['entity_type'];
    $field_name = $entity_form['#parents'][0] ;
    $ief_id = $entity_form['#ief_id'];
    $op = $entity_form['#op'];
    $civicrm_entity_fields = civicrm_api(substr_replace($entity_type,'', 0, 8),  'getfields', array(
      'version' => 3,
      'action' => 'create',
    ));
    if ($op == 'edit') {
      foreach ($form_state['inline_entity_form'][$ief_id]['entities'] as $k => $entity_array) {
        if (isset($form_state['values'][$field_name]['und']['entities'][$k]['form'])) {
          $entity = $entity_array['entity'];
          foreach ($form_state['values'][$field_name]['und']['entities'][$k]['form'] as $key => $value) {
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

          if ($entity->is_new = isset($entity->is_new) ? $entity->is_new : 0) {
            $entity->created = time();
          }
          $entity->changed = time();

          $wrapper = entity_metadata_wrapper($entity_type, $entity);
          field_attach_submit($entity_type, $entity, $entity_form, $form_state);
        }
      }
    }
    elseif ($op == 'create' || $op == 'add') {

      $entity = $form_state['entity'];
      foreach ($form_state['values'][$field_name]['und']['form'] as $key => $value) {
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
      if ($entity->is_new = isset($entity->is_new) ? $entity->is_new : 0) {
        $entity->created = time();
      }
      $entity->changed = time();

      $wrapper = entity_metadata_wrapper($entity_type, $entity);
      field_attach_submit($entity_type, $entity, $entity_form, $form_state);
    }
    // Add in created and changed times.
    
    parent::entityFormSubmit($entity_form, $form_state);
  }

}
