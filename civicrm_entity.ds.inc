<?php

/**
 * Default Display Suite Render handler for CiviCRM "fields".
 */
function _civicrm_entity_render_fields($field) {
  $settings = isset($field['formatter_settings']) ? $field['formatter_settings'] : array();
  $settings += $field['properties']['default'];

  $wrapper = entity_metadata_wrapper($field['entity_type'], $field['entity']->id);


  //civicrm_contact civi_user field needs special handling
  switch($field['entity_type']) {
    case 'civicrm_contact':
      $formatted_value = _civicrm_entity_contact_get_formatted_values($field,$wrapper);
      break;
    case 'civicrm_participant':
      $formatted_value = _civicrm_entity_participant_get_formatted_values($field,$wrapper,$field['entity']);
      break;
    default: $formatted_value = $wrapper->{$field['field_name']}->value();
      break;
  }

  // primary_contact_id_contact and other fields are objects produced by the meta wrapper
  // Will be ease to generate links to the entities if it is consistent across entities
  if(is_object($formatted_value)){
    $formatted_value = $wrapper->{$field['field_name']}->id->value();
  }
  if($field['formatter'] == 'civicrm_link') {
    global $base_url;
    if($formatted_value){
      $formatted_value = '<a href="'. $base_url . '/'. str_replace('_','-',$field['properties']['link_entity']) . '/' . $formatted_value . '">'. _civicrm_entity_get_entity_label($field['properties']['link_entity'],$formatted_value) . '</a>';
    }
  }

  if($field['formatter'] == 'civicrm_yes_no') {
    if($formatted_value) {
      $formatted_value = 'Yes';
    }
    else {
      $formatted_value = 'No';
    }
  }
  if($field['formatter'] == 'civicrm_true_false') {
    if($formatted_value) {
      $formatted_value = 'True';
    }
    else {
      $formatted_value = 'False';
    }
  }

  if($field['formatter'] == 'civicrm_option_value') {
    if($formatted_value){
      $formatted_value = _civicrm_entity_option_lookup($field,explode(',', $formatted_value));
    }
  }

  // Wrapper and class.
  if (!empty($settings['wrapper'])) {
    $wrapper = check_plain($settings['wrapper']);
    $class = (!empty($settings['class'])) ? ' class="' . check_plain($settings['class']) . '"' : '';
    $output = '<' . $wrapper . $class . '>' . $formatted_value . '</' . $wrapper . '>';
  }

  return $output;
}

function _civicrm_entity_add_formatters($entity_type,&$fields) {
  switch($entity_type) {
    case 'civicrm_activity':
      _civicrm_entity_activity_formatters($fields);
      break;
    case 'civicrm_address':
      _civicrm_entity_address_formatters($fields);
      break;
    case 'civicrm_case':
      _civicrm_entity_case_formatters($fields);
      break;
    case 'civicrm_contact':
      _civicrm_entity_contact_formatters($fields);
      break;
    case 'civicrm_contribution':
      _civicrm_entity_contribution_formatters($fields);
      break;
    case 'civicrm_contribution_page':
      _civicrm_entity_contribution_page_formatters($fields);
      break;
    case 'civicrm_email':
      _civicrm_entity_email_formatters($fields);
      break;
    case 'civicrm_entity_tag':
      _civicrm_entity_entity_tag_formatters($fields);
      break;
    case 'civicrm_event':
      _civicrm_entity_event_formatters($fields);
      break;
    case 'civicrm_financial_type':
      _civicrm_entity_financial_type_formatters($fields);
      break;
    case 'civicrm_grant':
      _civicrm_entity_grant_formatters($fields);
      break;
    case 'civicrm_group':
      _civicrm_entity_group_formatters($fields);
      break;
    case 'civicrm_membership':
      _civicrm_entity_membership_formatters($fields);
      break;
    case 'civicrm_membership_type':
      _civicrm_entity_membership_type_formatters($fields);
      break;
    case 'civicrm_participant':
      _civicrm_entity_participant_formatters($fields);
      break;
    case 'civicrm_price_field':
      _civicrm_entity_price_field_formatters($fields);
      break;
    case 'civicrm_price_field_value':
      _civicrm_entity_price_field_value_formatters($fields);
      break;
    case 'civicrm_price_set':
      _civicrm_entity_price_set_formatters($fields);
      break;
    case 'civicrm_relationship':
      _civicrm_entity_relationship_formatters($fields);
      break;
    case 'civicrm_relationship_type':
      _civicrm_entity_relationship_type_formatters($fields);
      break;
    case 'civicrm_tag':
      _civicrm_entity_tag_formatters($fields);
      break;
    case 'civicrm_custom_field':
      _civicrm_entity_custom_field_formatters($fields);
      break;
    case 'civicrm_custom_group':
      _civicrm_entity_custom_group_formatters($fields);
      break;
  }
}

function _civicrm_entity_activity_formatters(&$fields){
  $link_fields = array(
      array(
          'link_field' => 'source_contact_id',
          'target' => 'civicrm_contact',
      ),
      array(
          'link_field' => 'assignee_contact_id',
          'target' => 'civicrm_contact',
      ),
      array(
          'link_field' => 'target_contact_id',
          'target' => 'civicrm_contact',
      ),
      array(
          'link_field' => 'relationship_id',
          'target' => 'civicrm_relationship',
      ),
      array(
          'link_field' => 'parent_id',
          'target' => 'civicrm_activity',
      ),
      array(
          'link_field' => 'original_id',
          'target' => 'civicrm_activity',
      ),
  );
  _civicrm_entity_link_addformatters('civicrm_activity',$link_fields,$fields);
  $option_fields = array(
      'activity_type_id',
      'status_id',
      'medium_id',
      'priority_id',
  );
  _civicrm_entity_option_addformatters('civicrm_activity',$option_fields,$fields);
  $yes_no_fields = array(
      'is_auto',
      'is_current_revision',
      'is_test',
      'is_deleted',
  );
  _civicrm_entity_yesno_addformatters('civicrm_activity',$yes_no_fields,$fields);
}

function _civicrm_entity_address_formatters(&$fields){
  $link_fields = array(
      array(
          'link_field' => 'contact_id',
          'target' => 'civicrm_contact',
      ),
      array(
          'link_field' => 'contact_id_contact',
          'target' => 'civicrm_contact',
      ),
      array(
          'link_field' => 'master_id',
          'target' => 'civicrm_contact',
      ),
  );
  _civicrm_entity_link_addformatters('civicrm_address',$link_fields,$fields);
  $option_fields = array(
      'location_type_id',
      'county_id',
      'state_province_id',
      'country_id',
  );
  _civicrm_entity_option_addformatters('civicrm_address',$option_fields,$fields);
  $yes_no_fields = array(
      'is_primary',
      'is_billing',
  );
  _civicrm_entity_yesno_addformatters('civicrm_address',$yes_no_fields,$fields);
}

function _civicrm_entity_case_formatters(&$fields){
  $link_fields = array(
  );
  _civicrm_entity_link_addformatters('civicrm_case',$link_fields,$fields);
  $option_fields = array(
      'case_type_id',
      'status_id',
  );
  _civicrm_entity_option_addformatters('civicrm_case',$option_fields,$fields);
  $yes_no_fields = array(
      'is_deleted',
  );
  _civicrm_entity_yesno_addformatters('civicrm_case',$yes_no_fields,$fields);
}


function _civicrm_entity_contact_formatters(&$fields){
  $link_fields = array(
      array(
          'link_field' => 'employer_id_contact',
          'target' => 'civicrm_contact',
      ),
  );
  _civicrm_entity_link_addformatters('civicrm_contact',$link_fields,$fields);
  $option_fields = array(
      'preferred_communication_method',
      'prefix_id',
      'suffix_id',
      'communication_style_id',
      'gender_id',
  );
  _civicrm_entity_option_addformatters('civicrm_contact',$option_fields,$fields);
  $yes_no_fields = array(
      'is_deceased',
      'do_not_email',
      'do_not_phone',
      'do_not_sms',
      'do_not_trade',
      'do_not_mail',
      'is_opt_out',
      'is_deleted',
      'contact_is_deleted',
  );
  _civicrm_entity_yesno_addformatters('civicrm_contact',$yes_no_fields,$fields);
}

function _civicrm_entity_contribution_formatters(&$fields){
  $link_fields = array(
      array(
          'link_field' => 'contact_id_contact',
          'target' => 'civicrm_contact',
      ),
      array(
          'link_field' => 'contact_id',
          'target' => 'civicrm_contact',
      ),
  );
  _civicrm_entity_link_addformatters('civicrm_contribution',$link_fields,$fields);
  $option_fields = array(
    // 'cancel_reason',
      'financial_type_id',
      'contribution_status_id',
  );
  _civicrm_entity_option_addformatters('civicrm_contribution',$option_fields,$fields);
  $yes_no_fields = array(
      'is_test',
      'is_pay_later',

  );
  _civicrm_entity_yesno_addformatters('civicrm_contribution',$yes_no_fields,$fields);
}

function _civicrm_entity_contribution_page_formatters(&$fields){
  $link_fields = array(
      array(
          'link_field' => 'created_id_contact',
          'target' => 'civicrm_contact',
      ),
  );
  _civicrm_entity_link_addformatters('civicrm_contribution_page',$link_fields,$fields);
  $option_fields = array(
      'financial_type_id',
      'currency',
  );
  _civicrm_entity_option_addformatters('civicrm_contribution_page',$option_fields,$fields);
  $yes_no_fields = array(
      'is_credit_card_only',
      'is_monetary',
      'is_recur',
      'is_confirm_enabled',
      'is_recur_interval',
      'is_recur_installments',
      'is_pay_later',
      'is_partial_payment',
      'is_allow_other_amount',
      'is_for_organization',
      'is_email_receipt',
      'is_active',
      'is_share',
      'is_billing_required',
  );
  _civicrm_entity_yesno_addformatters('civicrm_contribution_page',$yes_no_fields,$fields);
}

function _civicrm_entity_email_formatters(&$fields){
  $link_fields = array(
      array(
          'link_field' => 'contact_id',
          'target' => 'civicrm_contact',
      ),
      array(
          'link_field' => 'contact_id_contact',
          'target' => 'civicrm_contact',
      ),

  );
  _civicrm_entity_link_addformatters('civicrm_email',$link_fields,$fields);
  $option_fields = array(
      'location_type_id',
  );
  _civicrm_entity_option_addformatters('civicrm_email',$option_fields,$fields);
  $yes_no_fields = array(
      'is_primary',
      'is_billing',
      'on_hold',
      'is_bulkmail',
  );
  _civicrm_entity_yesno_addformatters('civicrm_email',$yes_no_fields,$fields);
}

function _civicrm_entity_entity_tag_formatters(&$fields){
  $link_fields = array(
      array(
          'link_field' => 'tag_id',
          'target' => 'civicrm_tag',
      ),
  );
  _civicrm_entity_link_addformatters('civicrm_entity_tag',$link_fields,$fields);
  $option_fields = array(
  );
  // _civicrm_entity_option_addformatters('civicrm_entity_tag',$option_fields,$fields);
  $yes_no_fields = array(
  );
  // _civicrm_entity_yesno_addformatters('civicrm_entity_tag',$yes_no_fields,$fields);
}

function _civicrm_entity_event_formatters(&$fields){
  $link_fields = array(
      array(
          'link_field' => 'created_id_contact',
          'target' => 'civicrm_contact',
      ),
      array(
          'link_field' => 'created_id',
          'target' => 'civicrm_contact',
      ),
      array(
          'link_field' => 'parent_event_id',
          'target' => 'civicrm_event',
      ),
  );
  _civicrm_entity_link_addformatters('civicrm_event',$link_fields,$fields);
  $option_fields = array(
    // 'cancel_reason',
      'event_type_id',
      'financial_type_id',
      'default_role_id',
  );
  _civicrm_entity_option_addformatters('civicrm_event',$option_fields,$fields);
  $yes_no_fields = array(
      'is_public',
      'is_pay_later',
      'is_online_registration',
      'is_monetary',
      'is_map',
      'is_active',
      'is_show_location',
      'is_partial_payment',
      'is_multiple_registrations',
      'allow_same_participant_emails',
      'has_waitlist',
      'requires_approval',
      'is_template',
      'is_share',
      'is_confirm_enabled',
  );
  _civicrm_entity_yesno_addformatters('civicrm_event',$yes_no_fields,$fields);
}

function _civicrm_entity_financial_type_formatters(&$fields){
  $link_fields = array(
  );
  //_civicrm_entity_link_addformatters('civicrm_financial_type',$link_fields,$fields);
  $option_fields = array(
  );
  // _civicrm_entity_option_addformatters('civicrm_financial_type',$option_fields,$fields);
  $yes_no_fields = array(
      'is_reserved',
      'is_active',
      'is_deductible',
  );
  _civicrm_entity_yesno_addformatters('civicrm_financial_type',$yes_no_fields,$fields);
}

function _civicrm_entity_grant_formatters(&$fields){
  $link_fields = array(
      array(
          'link_field' => 'contact_id',
          'target' => 'civicrm_contact',
      ),
      array(
          'link_field' => 'contact_id_contact',
          'target' => 'civicrm_contact',
      ),
  );
  _civicrm_entity_link_addformatters('civicrm_grant',$link_fields,$fields);
  $option_fields = array(
      'status_id',
      'financial_type_id',
      'grant_type_id',
  );
  _civicrm_entity_option_addformatters('civicrm_grant',$option_fields,$fields);
  $yes_no_fields = array(
      'grant_report_received',
  );
  _civicrm_entity_yesno_addformatters('civicrm_grant',$yes_no_fields,$fields);
}

function _civicrm_entity_group_formatters(&$fields){
  $link_fields = array(
      array(
          'link_field' => 'created_id',
          'target' => 'civicrm_contact',
      ),
      array(
          'link_field' => 'created_id_contact',
          'target' => 'civicrm_contact',
      ),
      array(
          'link_field' => 'modified_id',
          'target' => 'civicrm_contact',
      ),
      array(
          'link_field' => 'modified_id_contact',
          'target' => 'civicrm_contact',
      ),
  );
  _civicrm_entity_link_addformatters('civicrm_group',$link_fields,$fields);
  $option_fields = array(
  );
  // _civicrm_entity_option_addformatters('civicrm_group',$option_fields,$fields);
  $yes_no_fields = array(
      'is_active',
      'is_hidden',
      'is_reserved'
  );
  _civicrm_entity_yesno_addformatters('civicrm_group',$yes_no_fields,$fields);
}

function _civicrm_entity_membership_formatters(&$fields){
  $link_fields = array(
      array(
          'link_field' => 'contact_id',
          'target' => 'civicrm_contact',
      ),
      array(
          'link_field' => 'contact_id_contact',
          'target' => 'civicrm_contact',
      ),
      array(
          'link_field' => 'owner_membership_id',
          'target' => 'civicrm_membership',
      ),
  );
  _civicrm_entity_link_addformatters('civicrm_membership',$link_fields,$fields);
  $option_fields = array(
      'membership_type_id',
      'status_id',
  );
  _civicrm_entity_option_addformatters('civicrm_membership',$option_fields,$fields);
  $yes_no_fields = array(
      'is_test',
      'is_pay_later',
      'is_override',
  );
  _civicrm_entity_yesno_addformatters('civicrm_membership',$yes_no_fields,$fields);
}

function _civicrm_entity_membership_type_formatters(&$fields){
  $link_fields = array(
      array(
          'link_field' => 'member_of_contact_id',
          'target' => 'civicrm_contact',
      ),
      array(
          'link_field' => 'member_of_contact_id_contact',
          'target' => 'civicrm_contact',
      ),
  );
  _civicrm_entity_link_addformatters('civicrm_membership_type',$link_fields,$fields);
  $option_fields = array(
      'financial_type_id',

  );
  _civicrm_entity_option_addformatters('civicrm_membership_type',$option_fields,$fields);
  $yes_no_fields = array(
      'is_active',
      'auto_renew',
  );
  _civicrm_entity_yesno_addformatters('civicrm_membership_type',$yes_no_fields,$fields);
}

function _civicrm_entity_participant_formatters(&$fields){
  $link_fields = array(
      array(
          'link_field' => 'contact_id',
          'target' => 'civicrm_contact',
      ),
      array(
          'link_field' => 'contact_id_contact',
          'target' => 'civicrm_contact',
      ),
      array(
          'link_field' => 'event_id',
          'target' => 'civicrm_event',
      ),
      array(
          'link_field' => 'event_id_event',
          'target' => 'civicrm_event',
      ),
      array(
          'link_field' => 'registered_by_id',
          'target' => 'civicrm_contact',
      ),
  );
  _civicrm_entity_link_addformatters('civicrm_participant',$link_fields,$fields);
  $option_fields = array(
      'status_id',
      'role_id',
  );
  _civicrm_entity_option_addformatters('civicrm_participant',$option_fields,$fields);
  $yes_no_fields = array(
      'is_test',
      'is_pay_later',
      'must_wait'
  );
  _civicrm_entity_yesno_addformatters('civicrm_participant',$yes_no_fields,$fields);
}

function _civicrm_entity_price_field_formatters(&$fields){
  $link_fields = array(
      array(
          'link_field' => 'price_set_id',
          'target' => 'civicrm_price_set',
      ),
  );
  _civicrm_entity_link_addformatters('civicrm_price_field',$link_fields,$fields);
  $option_fields = array(
      'visibility_id',

  );
  _civicrm_entity_option_addformatters('civicrm_price_field',$option_fields,$fields);
  $yes_no_fields = array(
      'is_enter_qty',
      'is_display_amounts',
      'is_active',
      'is_required',
  );
  _civicrm_entity_yesno_addformatters('civicrm_price_field',$yes_no_fields,$fields);
}

function _civicrm_entity_price_field_value_formatters(&$fields){
  $link_fields = array(
      array(
          'link_field' => 'price_field_id',
          'target' => 'civicrm_price_field',
      ),
      array(
          'link_field' => 'membership_type_id',
          'target' => 'civicrm_membership_type',
      ),
  );
  _civicrm_entity_link_addformatters('civicrm_price_field_value',$link_fields,$fields);
  $option_fields = array(
      'financial_type_id',

  );
  _civicrm_entity_option_addformatters('civicrm_price_field_value',$option_fields,$fields);
  $yes_no_fields = array(
      'is_default',
      'is_active',
  );
  _civicrm_entity_yesno_addformatters('civicrm_price_field_value',$yes_no_fields,$fields);
}

function _civicrm_entity_price_set_formatters(&$fields){
  $link_fields = array(
  );
  //_civicrm_entity_link_addformatters('civicrm_price_set',$link_fields,$fields);
  $option_fields = array(
  );
  _civicrm_entity_option_addformatters('civicrm_price_set',$option_fields,$fields);
  $yes_no_fields = array(
      'is_active',
      'is_quick_config',
      'is_reserved',
  );
  _civicrm_entity_yesno_addformatters('civicrm_price_set',$yes_no_fields,$fields);
}

function _civicrm_entity_relationship_formatters(&$fields){
  $link_fields = array(
      array(
          'link_field' => 'contact_id_a',
          'target' => 'civicrm_contact',
      ),
      array(
          'link_field' => 'contact_id_a_contact',
          'target' => 'civicrm_contact',
      ),
      array(
          'link_field' => 'contact_id_b',
          'target' => 'civicrm_contact',
      ),
      array(
          'link_field' => 'contact_id_b_contact',
          'target' => 'civicrm_contact',
      ),
      array(
          'link_field' => 'relationship_type_id',
          'target' => 'civicrm_relationship_type',
      ),
  );
  _civicrm_entity_link_addformatters('civicrm_relationship',$link_fields,$fields);
  $option_fields = array(
  );
  //_civicrm_entity_option_addformatters('civicrm_relationship',$option_fields,$fields);
  $yes_no_fields = array(
      'is_active',
      'is_permission_a_b',
      'is_permission_b_a'
  );
  _civicrm_entity_yesno_addformatters('civicrm_relationship',$yes_no_fields,$fields);
}

function _civicrm_entity_relationship_type_formatters(&$fields){
  $link_fields = array(
  );
  // _civicrm_entity_link_addformatters('civicrm_relationship_type',$link_fields,$fields);
  $option_fields = array(
      'contact_sub_type_a',
      'contact_sub_type_b',
  );
  _civicrm_entity_option_addformatters('civicrm_relationship_type',$option_fields,$fields);
  $yes_no_fields = array(
      'is_reserved',
      'is_active',
  );
  _civicrm_entity_yesno_addformatters('civicrm_relationship_type',$yes_no_fields,$fields);
}

function _civicrm_entity_tag_formatters(&$fields){
  $link_fields = array(
      array(
          'link_field' => 'created_id',
          'target' => 'civicrm_contact',
      ),
      array(
          'link_field' => 'created_id_contact',
          'target' => 'civicrm_contact',
      ),
      array(
          'link_field' => 'parent_id',
          'target' => 'civicrm_tag',
      ),
  );
  _civicrm_entity_link_addformatters('civicrm_tag',$link_fields,$fields);
  $option_fields = array(
  );
  //_civicrm_entity_option_addformatters('civicrm_tag',$option_fields,$fields);
  $yes_no_fields = array(
      'is_reserved',
      'is_tagset',
      'is_selectable',
  );
  _civicrm_entity_yesno_addformatters('civicrm_tag',$yes_no_fields,$fields);
}


function _civicrm_entity_custom_field_formatters(&$fields){
  $link_fields = array(
      array(
          'link_field' => 'custom_group_id',
          'target' => 'civicrm_custom_group',
      ),
  );
  _civicrm_entity_link_addformatters('civicrm_custom_field',$link_fields,$fields);

  $yes_no_fields = array(
      'is_view',
      'is_active',
      'is_required',
      'is_searchable',
      'is_search_range',
  );
  _civicrm_entity_yesno_addformatters('civicrm_custom_field',$yes_no_fields,$fields);
}

function _civicrm_entity_custom_group_formatters(&$fields){
  $link_fields = array(
      array(
          'link_field' => 'created_id_contact',
          'target' => 'civicrm_contact',
      ),
  );
  _civicrm_entity_link_addformatters('civicrm_custom_group',$link_fields,$fields);

  $yes_no_fields = array(
      'is_multiple',
      'is_active',
      'collapse_display',
      'collapse_adv_display',
      'is_reserved',
  );
  _civicrm_entity_yesno_addformatters('civicrm_custom_group',$yes_no_fields,$fields);

}

function _civicrm_entity_yesno_addformatters($entity_type,$yes_no_fields,&$fields){
  foreach($yes_no_fields as $name) {
    $fields[$entity_type][$name]['properties']['formatters'] = array (
        'default' => 'Raw Value',
        'civicrm_yes_no' => 'Yes/No',
        'civicrm_true_false' => 'True/False',
    );
  }
}

function _civicrm_entity_option_addformatters($entity_type,$option_fields,&$fields){
  foreach($option_fields as $name) {
    $fields[$entity_type][$name]['properties']['formatters'] = array (
        'default' => 'Option ID',
        'civicrm_option_value' => 'Option Value',
    );
  }
}

function _civicrm_entity_link_addformatters($entity_type,$link_fields,&$fields){
  foreach($link_fields as $link_field) {
    $fields[$entity_type][$link_field['link_field']]['properties']['formatters'] = array (
        'default' => 'Default',
        'civicrm_link' => 'Link',
    );
    $fields[$entity_type][$link_field['link_field']]['properties']['link_entity'] = $link_field['target'];
  }
}


function _civicrm_entity_get_entity_label($entity_type,$id) {
  $wrapper = entity_metadata_wrapper($entity_type,$id);
  return $wrapper->label();
}

/*
 * Returns a comma delimited list of option labels from id values
 *
 */
function _civicrm_entity_option_lookup($field,$values){
  $result = civicrm_api3(substr($field['entity_type'],8), 'getoptions', array(
      'field' => $field['field_name'],
  ));
  $fm = array();
  foreach($values as $value) {
    $fm[] = $result['values'][$value];
  }
  return implode(",",$fm);
}

/*
 * Contact entity field formatted value special handling
 *
 */
function _civicrm_entity_contact_get_formatted_values(&$field,$wrapper){
  switch($field['field_name']){
    case 'contact_sub_type':
    case 'preferred_communication_method':
      return _civicrm_entity_formatted_output_of_arrays($field,$wrapper);
    default: return $wrapper->{$field['field_name']}->value();
  }
}


function _civicrm_entity_participant_get_formatted_values(&$field,$wrapper,$entity){
  switch($field['field_name']) {
    case 'role_id':
    case 'status_id':
    case 'registered_date':
    case 'source':
    case 'fee_currency':
    case 'is_pay_later':
    case 'registered_by_id':
    case 'is_test':
      return $entity->{'participant_'.$field['field_name']};
    case 'fee_level':
      return $entity->{'participant_'.$field['field_name']}[0];
    default: return $wrapper->{$field['field_name']}->value();
  }
}

/*
 * Returns a comma delimited list of field values which are arrays
 *
 */
function _civicrm_entity_formatted_output_of_arrays(&$field,$wrapper) {
  $values = $wrapper->{$field['field_name']}->value();
  if(is_array($values)) {
    return  implode(",",$values );
  }
  else {
    return '';
  }
}
