<?php

class CivicrmInlineEntityFormController extends EntityInlineEntityFormController {
  /**
   * Overrides EntityInlineEntityFormController::entityForm().
   */
  public function entityForm($entity_form, &$form_state) {
    $entity_type = $entity_form['#entity_type'];

    $entity =  $entity_form['#entity'];
    $entity->type = $entity_type;
    $op = $entity_form['#op'];

    field_attach_form($entity_type, $entity, $entity_form, $form_state);

    if ($op == 'edit') {
      if (is_object($entity)) {
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
        if (isset($info['widget']['widget'])) {
          $entity_form[$name] = array(
            '#type' => $info['widget']['widget'],
            '#title' => $info['label'],
            '#description' => !empty($info['description']) ? $info['description'] : '',
          );

          // set api required fields to be required on form
          if (!empty($info['required'])) {
            $entity_form[$name]['#required'] = TRUE;
          }

          // FK Reference field handling
          if ($entity_form[$name]['#type'] == 'civi_fk_reference') {
            switch ($info['widget']['subtype']) {
              case 'autocomplete':
                $entity_form[$name]['#type'] = 'textfield';
                $entity_form[$name]['#autocomplete_path'] = 'civicrm-entity/autocomplete/' . $info['widget']['entity'];
                break;
              case 'select':
                $form[$name]['#type'] = 'select';
                $form[$name]['#options'] = !empty($entity_form[$name]['#required']) ? _civicrm_entity_get_entities_list($info['widget']['entity']) : array(NULL => 'None') + _civicrm_entity_get_entities_list($info['widget']['entity']);
                break;
              default:
                $entity_form[$name]['#type'] = 'textfield';
                break;
            }
          }

          if ($op == 'edit') {
            $entity_form[$name]['#default_value'] = $child->value();
            if ($entity_type == 'civicrm_participant') {
              if ($name == 'status_id' && !empty($entity->participant_status_id)) {
                $entity_form[$name]['#default_value'] = $entity->participant_status_id;
              }
              if ($name == 'role_id' && !empty($entity->participant_role_id)) {
                $entity_form[$name]['#default_value'] = $entity->participant_role_id;
              }
              if ($name == 'source' && !empty($entity->participant_source)) {
                $entity_form[$name]['#default_value'] = $entity->participant_source;
              }
              if ($name == 'register_date' && !empty($entity->participant_register_date)) {
                $entity_form[$name]['#default_value'] = $entity->participant_register_date;
              }
              if ($name == 'registered_by_id' && !empty($entity->participant_registered_by_id)) {
                $entity_form[$name]['#default_value'] = $entity->participant_registered_by_id;
              }
              if ($name == 'fee_amount' && !empty($entity->participant_fee_amount)) {
                $entity_form[$name]['#default_value'] = $entity->participant_fee_amount;
              }
            }
          }
          elseif ($op == 'create' || $op == 'add') {
            if (isset($info['default_value'])) {
              $entity_form[$name]['#default_value'] = $info['default_value'];
            }
            else {
              $entity_form[$name]['#default_value'] = NULL;
            }
          }

          // set the #size and #maxlength FAPI properties if the #type is a textfield
          if ($entity_form[$name]['#type'] == 'textfield') {
            if (!empty($info['size'])) {
              $entity_form[$name]['#size'] = $info['size'];
            }
            if (!empty($info['maxlength'])) {
              $entity_form[$name]['#maxlength'] = $info['maxlength'];
            }
          }

          //set rows and cols if available for textarea or text_format
          if ($entity_form[$name]['#type'] == 'textarea' || $entity_form[$name]['#type'] == 'text_format') {
            if ($entity_form[$name]['#type'] == 'text_format') {
              $entity_form[$name]['#format'] = 'full_html';
            }
            // set rows value if not empty
            if (!empty($info['rows'])) {
              $entity_form[$name]['#rows'] = $info['rows'];
            }
            if (!empty($info['cols'])) {
              $entity_form[$name]['#cols'] = $info['cols'];
            }
          }

          //set the options for select options, radios, and checkboxes
          if ($entity_form[$name]['#type'] == 'select') {
            if (!empty($info['widget']['options'])) {
              $entity_form[$name]['#options'] = $info['widget']['options'];
              if (!empty($info['required'])) {
                if (isset($entity_form[$name]['#options'][''])) {
                  unset($entity_form[$name]['#options']['']);
                }
              }
            }
          }
          elseif ($entity_form[$name]['#type'] == 'radios') {
            $entity_form[$name]['#options'] = $info['widget']['options'];
            if (!empty($info['required'])) {
              if (isset($entity_form[$name]['#options'][''])) {
                unset($entity_form[$name]['#options']['']);
              }
            }
          }
          elseif ($entity_form[$name]['#type'] == 'checkboxes') {
            if ($op == 'edit') {
              $value = $child->value();
              if (!empty($value)) {
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
            elseif ($op == 'create' || $op == 'add') {
              if (isset($info['default_value'])) {
                $entity_form[$name]['#default_value'] = explode(CRM_Core_DAO::VALUE_SEPARATOR, $entity_form[$name]['#default_value']);
              }
            }

            $entity_form[$name]['#options'] = $info['widget']['options'];
            if (!empty($info['required'])) {
              if (isset($entity_form[$name]['#options'][''])) {
                unset($entity_form[$name]['#options']['']);
              }
            }
          }
          // Date field handling, check for date_api module to use date select widget
          // else fallback to textfield
          elseif ($entity_form[$name]['#type'] == 'date_select') {
            if (module_exists('date_api')) {
              if (module_exists('date_popup')) {
                $entity_form[$name]['#type'] = 'date_popup';
              }
              $entity_form[$name]['#format'] = $info['widget']['format'];
              $entity_form[$name]['#date_year_range'] = '-115:+10';
            }
            else {
              $entity_form[$name]['#type'] = 'textfield';
            }
          }

          if ($entity_type == 'civicrm_contact') {
            //for some reason the is_deleted column of the contact record is coming to the entity
            // as contact_is_deleted ...special handling to have the form value set properly
            if ($name == 'is_deleted') {
              $entity_form[$name]['#default_value'] = isset($entity->contact_is_deleted) ? $entity->contact_is_deleted : 0;
            }
            if ($name == 'contact_type') {
              $entity_form[$name]['#ajax'] = array(
                'event' => 'change',
                'wrapper' => 'contact-subtype-wrapper',
                'callback' => '_civicrm_entity_form_contact_subtype_ajax_callback',
                'method' => 'replace',
              );
            }
            if ($name == 'contact_sub_type') {
              $contact_type = !empty($entity_form['contact_type']['#default_value']) ? $entity_form['contact_type']['#default_value'] : 'Individual';
              $entity_form[$name]['#type'] = 'select';
              $entity_form[$name]['#options'] = _civicrm_entity_form_contact_get_subtypes($contact_type);
              $entity_form[$name]['#prefix'] = '<div id="contact-subtype-wrapper">';
              $entity_form[$name]['#suffix'] = '</div>';
              $entity_form[$name]['#validated'] = TRUE;
              unset($entity_form[$name]['#size']);
            }
          }

          // event special handling
          if ($entity_type == 'civicrm_event') {
            //if($name == 'thankyou_text' || $name == 'thankyou_footer_text') {
            //  $entity_form[$name]['#type'] = 'text_format';
            // }
            if ($name == 'event_type_id') {
              $entity_form[$name]['#required'] = TRUE;
            }
            if ($name == 'default_role_id') {
              $entity_form[$name]['#required'] = TRUE;
            }
          }

          // make sure to disable id field, and any custom field with is_view = 1
          if ($name == 'id' || !empty($info['is_view'])) {
            $entity_form[$name]['#disabled'] = TRUE;
          }

        } // end if isset widget
      } // end else not a drupal field
    } // end foreach

    return $entity_form;
  }

  public function entityFormValidate($entity_form, &$form_state) {
    $entity_type = $entity_form['#entity_type'];
    $ief_id = $entity_form['#ief_id'];
    $field_name = $form_state['inline_entity_form'][$ief_id]['instance']['field_name'];

    $op = $entity_form['#op'];
    if ($op == 'edit') {
      foreach ($form_state['inline_entity_form'][$ief_id]['entities'] as $delta => &$entity_array) {
        if (isset($form_state['values'][$field_name]['und']['entities'][$delta]['form'])) {
          //$entity = $entity_array['entity'];
          foreach ($form_state['values'][$field_name]['und']['entities'][$delta]['form'] as $key => $value) {
            //$entity->{$key} = $value;
            if (!is_array($value)) {
              $entity_array['entity']->{$key} = $value;
            }
            else {
              if (isset($value['value'])) {
                $entity_array['entity']->{$key} = $value['value'];
              }
              else {
                $entity_array['entity']->{$key} = $value;
              }
            }
          }
        }
      }
    }
    elseif ($op == 'create' || $op == 'add') {

      $entity = $entity_form['#entity'];

      $child_form_state_values = drupal_array_get_nested_value($form_state['values'], $entity_form['#parents']);
      foreach ($child_form_state_values as $key => $value) {
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
    }

    if ($op == 'create' || $op == 'add' || $op == 'edit') {
      // validate civicrm data
      $entity_metadata_info = entity_get_property_info($entity_type);
      foreach ($form_state['inline_entity_form'][$ief_id]['entities'] as $delta => $ent_array) {
        foreach ($entity_metadata_info['properties'] as $name => $info) {
          if (!empty($info['type'])) {
            // integer validation
            if ($info['type'] == 'integer') {
              //text field validation
              if (isset($entity_form[$name]['#type']) && $entity_form[$name]['#type'] == 'textfield') {
                if (isset($form_state['values'][$field_name][LANGUAGE_NONE]['entities'][$delta]['form'][$name]) &&
                  strlen($form_state['values'][$field_name][LANGUAGE_NONE]['entities'][$delta]['form'][$name]) &&
                  (string) (int) $form_state['values'][$field_name][LANGUAGE_NONE]['entities'][$delta]['form'][$name] !== (string) $form_state['values'][$field_name][LANGUAGE_NONE]['entities'][$delta]['form'][$name]
                ) {
                  form_set_error($field_name . '][' . LANGUAGE_NONE . '][entities][' . $delta . '][form][' . $name, t('Value must be an integer. e.g (1, 154, 0)'));
                }
              }
            }
            // number validation
            // money validation
            elseif ($info['type'] == 'decimal') {
              //text field validation
              if (isset($entity_form[$name]['#type']) && $entity_form[$name]['#type'] == 'textfield') {
                if (isset($form_state['values'][$field_name][LANGUAGE_NONE]['entities'][$delta]['form'][$name]) &&
                  strlen($form_state['values'][$field_name][LANGUAGE_NONE]['entities'][$delta]['form'][$name]) &&
                  !is_numeric($form_state['values'][$field_name][LANGUAGE_NONE]['entities'][$delta]['form'][$name])
                ) {
                  form_set_error($field_name . '][' . LANGUAGE_NONE . '][entities][' . $delta . '][form][' . $name, t('Value must be an number. e.g (1, 1.26, -1.3)'));
                }
              }
            }
          }
        }
        // validate name for contact
        if ($entity_type == 'civicrm_contact' && isset($form_state['values'][$field_name][LANGUAGE_NONE]['entities'][$delta]['form']['contact_type'])) {
          if ($form_state['values'][$field_name][LANGUAGE_NONE]['entities'][$delta]['form']['contact_type'] == 'Individual') {
            if (!isset($entity_form['first_name']) && !isset($entity_form['middle_name']) && !isset($entity_form['last_name'])) {
              form_set_error($field_name . '][' . LANGUAGE_NONE . '][entities][' . $delta . '][form][' . 'contact_type', t('Individual contact type, form must include at least one of first, middle, or last name fields.'));
            }
            elseif (empty($form_state['values'][$field_name][LANGUAGE_NONE]['entities'][$delta]['form']['first_name']) && empty($form_state['values'][$field_name][LANGUAGE_NONE]['entities'][$delta]['form']['middle_name']) && empty($form_state['values'][$field_name][LANGUAGE_NONE]['entities'][$delta]['form']['last_name'])) {
              form_set_error($field_name . '][' . LANGUAGE_NONE . '][entities][' . $delta . '][form][' . 'first_name', t('At least one of first, middle, or last names must be set when contact type is set to Individual.'));
              form_set_error($field_name . '][' . LANGUAGE_NONE . '][entities][' . $delta . '][form][' . 'middle_name');
              form_set_error($field_name . '][' . LANGUAGE_NONE . '][entities][' . $delta . '][form][' . 'last_name');
            }
          }
          elseif ($form_state['values'][$field_name][LANGUAGE_NONE]['entities'][$delta]['form']['contact_type'] == 'Household') {
            if (!isset($entity_form['household_name'])) {
              form_set_error($field_name . '][' . LANGUAGE_NONE . '][entities][' . $delta . '][form][' . 'contact_type', t('Household contact type, form must include the household name field.'));
            }
            elseif (empty($form_state['values'][$field_name][LANGUAGE_NONE]['entities'][$delta]['form']['household_name'])) {
              form_set_error($field_name . '][' . LANGUAGE_NONE . '][entities][' . $delta . '][form][' . 'household_name', t('Household name is required when contact type is set to Household'));
            }
          }
          elseif ($form_state['values'][$field_name][LANGUAGE_NONE]['entities'][$delta]['form']['contact_type'] == 'Organization') {
            if (!isset($entity_form['organization_name'])) {
              form_set_error($field_name . '][' . LANGUAGE_NONE . '][entities][' . $delta . '][form][' . 'contact_type', t('Organization contact type, form must include the organization name field.'));
            }
            elseif (empty($form_state['values'][$field_name][LANGUAGE_NONE]['entities'][$delta]['form']['organization_name'])) {
              form_set_error($field_name . '][' . LANGUAGE_NONE . '][entities][' . $delta . '][form][' . 'organization_name', t('Organization name is required when contact type is set to Organization.'));
            }
          }
        }
        field_attach_form_validate($entity_type, $ent_array['entity'], $entity_form, $form_state);
      }

    }

    parent::entityFormValidate($entity_form, $form_state);
  }

  public function entityFormSubmit(&$entity_form, &$form_state) {
    //$entity_type = $form_state['entity_type'];
    $entity_type = $entity_form['#entity_type'];
    $ief_id = $entity_form['#ief_id'];
    $field_name = $form_state['inline_entity_form'][$ief_id]['instance']['field_name'];
    $op = $entity_form['#op'];
    civicrm_initialize();
    $civicrm_entity_fields = civicrm_api(substr_replace($entity_type, '', 0, 8), 'getfields', array(
      'version' => 3,
      'action' => 'create',
    ));
    if ($op == 'edit') {
      foreach ($form_state['inline_entity_form'][$ief_id]['entities'] as $k => &$entity_array) {
        if (isset($form_state['values'][$field_name]['und']['entities'][$k]['form'])) {
          foreach ($form_state['values'][$field_name]['und']['entities'][$k]['form'] as $key => $value) {
            if (substr($key, 0, 7) == 'custom_') {
              if ($civicrm_entity_fields['values'][$key]['html_type'] == 'CheckBox') {
                if (is_array($value)) {
                  $stored_value = array();
                  foreach ($value as $option => $checkbox_value) {
                    if ($checkbox_value) {
                      $stored_value[] = $option;
                    }
                  }
                  $entity_array['entity']->{$key} = !empty($stored_value) ? $stored_value : array('');
                }

              }
              else {
                if (is_array($value) && isset($value['value'])) {
                  $value = $value['value'];
                  $form_state['values'][$field_name]['und']['entities'][$k]['form'][$key] = $value;
                }
                $entity_array['entity']->{$key} = $value;

              }

              if (!empty($civicrm_entity_fields['values'][$key]['is_view'])) {
                unset($entity_array['entity']->{$key});
              }

            }
            else {
              if (is_array($value) && isset($value['value'])) {
                $value = $value['value'];
                $form_state['values'][$field_name]['und']['entities'][$k]['form'][$key] = $value;
              }
              $entity_array['entity']->{$key} = $value;
            }
          }

          if ($entity_array['entity']->is_new = isset($entity_array['entity']->is_new) ? $entity_array['entity']->is_new : 0) {
            $entity_array['entity']->created = time();
          }
          $entity_array['entity']->changed = time();

          field_attach_submit($entity_type, $entity_array['entity'], $entity_form, $form_state);

        }
      }
    }
    elseif ($op == 'create' || $op == 'add') {
      $entity = $entity_form['#entity'];

      $child_form_state_values = drupal_array_get_nested_value($form_state['values'], $entity_form['#parents']);
      $field_info = field_info_field($field_name);
      $field_target_id_column = !empty($field_info['settings']['target_id_column']) ? $field_info['settings']['target_id_column'] : '';
      $field_host_source_id = !empty($field_info['settings']['host_source_id']) ? $field_info['settings']['host_source_id'] : '';

      $this->fillEntityValues($child_form_state_values, $entity, $civicrm_entity_fields);

      if(!empty($entity->is_new) && !empty($entity->id)) {
        $entity->is_new = FALSE;
        if($entity_type == 'civicrm_contact') {
          $entity->contact_id = $entity->id;
        }
      }

      if ($entity->is_new = isset($entity->is_new) ? $entity->is_new : 0) {
        $entity->created = time();
      }
      $entity->changed = time();

      // First parent name is not the field name, then this reference field is nested inside another
      if ($field_name != $entity_form['#parents'][0]) {
        $parent_field_parents = array();
        foreach($entity_form['#parents'] as $index => $name) {
          $parent_field_parents[] = $name;
          if ($name == $field_name) {
            unset($parent_field_parents[$index]);
            $parent_field_name = $entity_form['#parents'][$index-3];
            break;
          }
        }

        $parent_field_info = field_info_field($parent_field_name);
        if (!empty($parent_field_info['settings']['target_type'])) {
          $parent_field_entity_type = $parent_field_info['settings']['target_type'];
        }
        elseif (!empty($parent_field_info['settings']['target_entity_type'])) {
          $parent_field_entity_type = $parent_field_info['settings']['target_entity_type'];
        }
        if(!empty($parent_field_parents) && !empty($parent_field_entity_type) && !empty($field_host_source_id) && !empty($field_target_id_column) ) {
          $parent_submitted_values = drupal_array_get_nested_value($form_state['values'], $parent_field_parents);
          if(empty($parent_submitted_values[$field_host_source_id])) {
            // save parent entity and update something?
            $parent_field_entity = new CivicrmEntity(array(), $parent_field_entity_type);
            $this->fillEntityValues($parent_submitted_values, $parent_field_entity, $civicrm_entity_fields);

            $parent_field_wrapper = entity_metadata_wrapper($parent_field_entity_type, $parent_field_entity);

            $parent_field_wrapper->save();

            $parent_submitted_values[$field_host_source_id] = $parent_field_wrapper->{$field_host_source_id}->value();
            if ($parent_field_entity_type == 'civicrm_contact') {
              $parent_field_wrapper->contact_id->set($parent_field_wrapper->{$field_host_source_id}->value());
              $parent_submitted_values['contact_id'] = $parent_field_wrapper->{$field_host_source_id}->value();
            }
            $child_form_state_values[$field_target_id_column] = $parent_field_wrapper->{$field_host_source_id}->value();

            //$entity->{$field_target_id_column} = $parent_field_wrapper->{$field_host_source_id}->value();


            drupal_array_set_nested_value($form_state['values'], $parent_field_parents, $parent_submitted_values);
            foreach($form_state['inline_entity_form'] as $iefid => $ief_data) {
              if($ief_data['instance']['field_name'] == $parent_field_name) {
                $entity_count = count($form_state['inline_entity_form'][$iefid]['entities']);
                //$form_state['inline_entity_form'][$iefid]['entities'][$entity_count]['entity'] = $parent_field_wrapper->value();
                if ($parent_field_entity_type == 'civicrm_contact') {
                  //$form_state['inline_entity_form'][$iefid]['entities'][$entity_count]['entity']->contact_id = $parent_field_wrapper->{$field_host_source_id}->value();
                }
                //$form_state['inline_entity_form'][$iefid]['entities'][$entity_count]['needs_save'] = FALSE;
              }
            }

          }
          else {
            $child_form_state_values[$field_target_id_column] = $parent_submitted_values[$field_host_source_id];

            //$entity->{$field_target_id_column} = $parent_submitted_values[$field_host_source_id];
          }
        }

      }

      drupal_array_set_nested_value($form_state['values'], $entity_form['#parents'], $child_form_state_values);

    }
    // Add in created and changed times.
    parent::entityFormSubmit($entity_form, $form_state);
  }

  /**
   * Utility function to fill entity with form values
   *
   * @param $values
   * @param $entity
   * @param $civicrm_entity_fields
   * @param string $entity_type
   */
  private function fillEntityValues(&$values, $entity, $civicrm_entity_fields, $entity_type = '') {

    foreach ($values as $key => $value) {

      if (substr($key, 0, 7) == 'custom_') {
        if (!empty($civicrm_entity_fields['values'][$key]['html_type']) && $civicrm_entity_fields['values'][$key]['html_type'] == 'CheckBox') {
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
            //$form_state['values'][$field_name]['und']['form'][$key] = $value;
            $values[$key] = $value;
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
          //$form_state['values'][$field_name]['und']['form'][$key] = $value;
          $values[$key] = $value;
        }
        $entity->{$key} = $value;
      }
    }
  }

}
