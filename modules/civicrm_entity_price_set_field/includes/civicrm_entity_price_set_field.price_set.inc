<?php
/**
 * @file
 * CiviCRM Entity Price Set Field, Price Set Form generation
 */

/**
 * Helper function to add price set FAPI elements on form
 *
 * @param $form
 * @param $form_state
 * @param $default_values
 * @param $parent
 * @param $count
 */
function _civicrm_entity_price_set_field_setup_price_set_fapi(&$form, &$form_state, $default_values, $parent, $count) {
  $data = $form_state['price_set_data'][$count];
  if (!empty($data)) {
    $form[$parent]['contacts'][$count]['price_set'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array(
          'civicrm-price-set',
          'civicrm-price-set-' . $data['price_set']->id,
        ),
      ),
    );
    foreach ($data['price_fields'] as $pf_id => $price_field) {
      if (!empty($price_field['pf_entity']->active_on)) {
        if (time() < strtotime($price_field['pf_entity']->active_on)) {
          continue;
        }
      }
      if (!empty($price_field['pf_entity']->expire_on)) {
        if (time() > strtotime($price_field['pf_entity']->expire_on)) {
          continue;
        }
      }
      $form[$parent]['contacts'][$count]['price_set'][$pf_id] = array(
        '#type' => 'container',
        '#attributes' => array(
          'class' => array(
            'civicrm-price-field',
            'price-field-type-' . drupal_html_class(strtolower($price_field['pf_entity']->html_type)),
            'price-field-' . drupal_html_class(strtolower($price_field['pf_entity']->name))
          ),
        ),
      );

      $form[$parent]['contacts'][$count]['price_set'][$pf_id]['price_field_values'] = array();
      $form[$parent]['contacts'][$count]['price_set'][$pf_id]['price_field_values']['#title'] = t($price_field['pf_entity']->label);
      if (!empty($price_field['pf_entity']->help_post)) {
        $form[$parent]['contacts'][$count]['price_set'][$pf_id]['price_field_values']['#description'] = t($price_field['pf_entity']->help_post);
      }
      if (!empty($price_field['pf_entity']->is_required)) {
        $form[$parent]['contacts'][$count]['price_set'][$pf_id]['price_field_values']['#required'] = TRUE;

      };

      if ($price_field['pf_entity']->html_type == 'Text') {
        $form[$parent]['contacts'][$count]['price_set'][$pf_id]['price_field_values']['#type'] = 'textfield';
        $form[$parent]['contacts'][$count]['price_set'][$pf_id]['price_field_values']['#size'] = 7;
        $form[$parent]['contacts'][$count]['price_set'][$pf_id]['price_field_values']['#maxlength'] = 5;
      }
      else {
        switch ($price_field['pf_entity']->html_type) {
          case 'Radio':
            $form[$parent]['contacts'][$count]['price_set'][$pf_id]['price_field_values']['#type'] = 'radios';
            //$default_value = array();
            $default_value = '';
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
      }
      if (empty($default_values['contacts'][$count]['price_set'][$pf_id]['price_field_values'])) {
        $form[$parent]['contacts'][$count]['price_set'][$pf_id]['price_field_values']['#default_value'] = $default_value;
      }
      else {
        $form[$parent]['contacts'][$count]['price_set'][$pf_id]['price_field_values']['#default_value'] = $default_values['contacts'][$count]['price_set'][$pf_id]['price_field_values'];
      }
    }
  }
}