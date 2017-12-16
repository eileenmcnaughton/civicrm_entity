<?php

namespace Drupal\civicrm_entity;

// @todo these should be plugins.
final class SupportedEntities {

  public static function getInfo() {
    $civicrm_entity_info = [];
    $civicrm_entity_info['civicrm_activity'] = [
      'civicrm entity name' => 'activity',
      'label property' => 'subject',
      'permissions' => [
        'view' => ['view all activities'],
        'edit' => [],
        'update' => [],
        'create' => [],
        'delete' => ['delete activities'],
      ],
      'theme' => [
        'template' => 'civicrm-activity',
        'path' => drupal_get_path('module', 'civicrm_entity') . '/templates',
      ],
      'display suite' => [
        'link fields' => [
          [
            'link_field' => 'source_contact_id',
            'target' => 'civicrm_contact',
          ],
          [
            'link_field' => 'assignee_contact_id',
            'target' => 'civicrm_contact',
          ],
          [
            'link_field' => 'target_contact_id',
            'target' => 'civicrm_contact',
          ],
          [
            'link_field' => 'relationship_id',
            'target' => 'civicrm_relationship',
          ],
          [
            'link_field' => 'parent_id',
            'target' => 'civicrm_activity',
          ],
          [
            'link_field' => 'original_id',
            'target' => 'civicrm_activity',
          ],
        ],
        'option fields' => [
          'activity_type_id',
          'status_id',
          'medium_id',
          'priority_id',
          'engagement_level',
        ],
        'boolean fields' => [
          'is_auto',
          'is_current_revision',
          'is_test',
          'is_deleted',
        ],
        'date fields' => [
          'activity_date_time',
        ],
      ],
    ];
    $civicrm_entity_info['civicrm_action_schedule'] = [
      'civicrm entity name' => 'action_schedule',
      'label property' => 'name',
      'permissions' => [
        'view' => [],
        'edit' => [],
        'update' => [],
        'create' => [],
        'delete' => [],
      ],
      'display suite' => [
        'date fields' => [
          'absolute_date',
        ],
      ],
    ];
    $civicrm_entity_info['civicrm_address'] = [
      'civicrm entity name' => 'address',
      'label property' => 'name',
      'permissions' => [
        'view' => ['view all contacts'],
        'edit' => ['edit all contacts'],
        'update' => ['edit all contacts'],
        'create' => ['edit all contacts'],
        'delete' => ['delete contacts'],
      ],
      'theme' => [
        'template' => 'civicrm-address',
        'path' => drupal_get_path('module', 'civicrm_entity') . '/templates',
      ],
      'display suite' => [
        'link fields' => [
          [
            'link_field' => 'contact_id',
            'target' => 'civicrm_contact',
          ],
          [
            'link_field' => 'contact_id_contact',
            'target' => 'civicrm_contact',
          ],
          [
            'link_field' => 'master_id',
            'target' => 'civicrm_contact',
          ],
        ],
        'option fields' => [
          'location_type_id',
          'county_id',
          'state_province_id',
          'country_id',
        ],
        'boolean fields' => ['is_primary', 'is_billing'],
      ],
    ];
    $civicrm_entity_info['civicrm_campaign'] = [
      'civicrm entity name' => 'campaign',
      'label property' => 'title',
      'permissions' => [
        'view' => [],
        'edit' => [],
        'update' => [],
        'create' => [],
        'delete' => [],
      ],
      'theme' => [
        'template' => 'civicrm-campaign',
        'path' => drupal_get_path('module', 'civicrm_entity') . '/templates',
      ],
      'display suite' => [
        'date fields' => [
          'start_date',
          'end_date',
          'created_date',
          'last_modified_date',
        ],
      ],
    ];
    $civicrm_entity_info['civicrm_case'] = [
      'civicrm entity name' => 'case',
      'label property' => 'subject',
      'permissions' => [
        'view' => ['access all cases and activities'],
        'edit' => ['access all cases and activities'],
        'update' => ['access all cases and activities'],
        'create' => ['add cases', 'access all cases and activities'],
        'delete' => [
          'delete in CiviCase',
          'access all cases and activities',
        ],
      ],
      'theme' => [
        'template' => 'civicrm-case',
        'path' => drupal_get_path('module', 'civicrm_entity') . '/templates',
      ],
      'display suite' => [
        'option fields' => ['case_type_id', 'status_id'],
        'boolean fields' => ['is_deleted'],
        'date fields' => [
          'start_date',
          'end_date',
        ],
      ],
    ];
    $civicrm_entity_info['civicrm_contact'] = [
      'civicrm entity name' => 'contact',
      'label property' => 'display_name',
      'permissions' => [
        'view' => ['view all contacts'],
        'edit' => ['edit all contacts'],
        'update' => ['edit all contacts'],
        'create' => ['edit all contacts'],
        'delete' => ['delete contacts'],
      ],
      'theme' => [
        'template' => 'civicrm-contact',
        'path' => drupal_get_path('module', 'civicrm_entity') . '/templates',
      ],
      'display suite' => [
        'link fields' => [
          [
            'link_field' => 'employer_id_contact',
            'target' => 'civicrm_contact',
          ],
        ],
        'option fields' => [
          'preferred_communication_method',
          'prefix_id',
          'suffix_id',
          'communication_style_id',
          'gender_id',
          'country_id',
          'state_province_id',
        ],
        'boolean fields' => [
          'is_deceased',
          'do_not_email',
          'do_not_phone',
          'do_not_sms',
          'do_not_trade',
          'do_not_mail',
          'is_opt_out',
          'is_deleted',
          'contact_is_deleted',
        ],
        'date fields' => [
          'birth_date',
          'deceased_date',
        ],
        'timestamp fields' => [
          'created_date',
          'modified_date',
        ],
      ],
    ];
    $civicrm_entity_info['civicrm_contribution'] = [
      'civicrm entity name' => 'contribution',
      'label property' => 'source',
      'permissions' => [
        'view' => ['access CiviContribute', 'administer CiviCRM'],
        'edit' => ['edit contributions', 'administer CiviCRM'],
        'update' => ['edit contributions', 'administer CiviCRM'],
        'create' => ['edit contributions', 'administer CiviCRM'],
        'delete' => [
          'edit contributions',
          'delete in CiviContribute',
          'administer CiviCRM',
        ],
      ],
      'theme' => [
        'template' => 'civicrm-contribution',
        'path' => drupal_get_path('module', 'civicrm_entity') . '/templates',
      ],
      'display suite' => [
        'link fields' => [
          [
            'link_field' => 'contact_id_contact',
            'target' => 'civicrm_contact',
          ],
          [
            'link_field' => 'contact_id',
            'target' => 'civicrm_contact',
          ],
          [
            'link_field' => 'payment_processor',
            'target' => 'civicrm_payment_processor',
          ],
          [
            'link_field' => 'id',
            'target' => 'civicrm_contribution',
          ],
        ],
        'option fields' => [
          'financial_type_id',
          'contribution_status_id',
          'payment_instrument_id',
        ],
        'boolean fields' => ['is_test', 'is_pay_later'],
        'date fields' => [
          'cancel_date',
          'receipt_date',
          'thankyou_date',
          'receive_date',
        ],
      ],
    ];
    $civicrm_entity_info['civicrm_contribution_recur'] = [
      'civicrm entity name' => 'contribution_recur',
      'label property' => 'id',
      'permissions' => [
        'view' => ['access CiviContribute', 'administer CiviCRM'],
        'edit' => ['edit contributions', 'administer CiviCRM'],
        'update' => ['edit contributions', 'administer CiviCRM'],
        'create' => ['edit contributions', 'administer CiviCRM'],
        'delete' => [
          'edit contributions',
          'delete in CiviContribute',
          'administer CiviCRM',
        ],
      ],
      'theme' => [
        'template' => 'civicrm-contribution-recur',
        'path' => drupal_get_path('module', 'civicrm_entity') . '/templates',
      ],
      'display suite' => [
        'link fields' => [
          [
            'link_field' => 'contact_id',
            'target' => 'civicrm_contact',
          ],
          [
            'link_field' => 'payment_processor_id',
            'target' => 'civicrm_payment_processor',
          ],
          [
            'link_field' => 'financial_type_id',
            'target' => 'civicrm_contribution',
          ],
          [
            'link_field' => 'campaign_id',
            'target' => 'civicrm_campaign',
          ],
        ],
        'option fields' => [
          'financial_type_id',
          'contribution_status_id',
          'payment_instrument_id',
        ],
        'boolean fields' => ['is_test', 'is_email_receipt', 'auto_renew'],
        'date fields' => [
          'create_date',
          'modified_date',
          'cancel_date',
          'end_date',
          'failure_retry_date',
        ],
      ],
    ];
    $civicrm_entity_info['civicrm_contribution_page'] = [
      'civicrm entity name' => 'contribution_page',
      'label property' => 'title',
      'permissions' => [
        'view' => ['make online contributions'],
        'edit' => ['access CiviContribute', 'administer CiviCRM'],
        'update' => ['access CiviContribute', 'administer CiviCRM'],
        'create' => ['access CiviContribute', 'administer CiviCRM'],
        'delete' => ['access CiviContribute', 'administer CiviCRM'],
      ],
      'theme' => [
        'template' => 'civicrm-contribution-page',
        'path' => drupal_get_path('module', 'civicrm_entity') . '/templates',
      ],
      'display suite' => [
        'link fields' => [
          [
            'link_field' => 'created_id_contact',
            'target' => 'civicrm_contact',
          ],
        ],
        'option fields' => ['financial_type_id', 'currency'],
        'boolean fields' => [
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
        ],
        'date fields' => [
          'start_date',
          'end_date',
          'created_date',
        ],
      ],
    ];
    $civicrm_entity_info['civicrm_country'] = [
      'civicrm entity name' => 'country',
      'label property' => 'name',
      'permissions' => [
        'view' => ['view all contacts'],
        'edit' => [],
        'update' => [],
        'create' => [],
        'delete' => [],
      ],
      'theme' => [
        'template' => 'civicrm-country',
        'path' => drupal_get_path('module', 'civicrm_entity') . '/templates',
      ],
      'display suite' => [
        'boolean fields' => ['is_province_abbreviated'],
      ],
    ];
    $civicrm_entity_info['civicrm_email'] = [
      'civicrm entity name' => 'email',
      'label property' => 'email',
      'permissions' => [
        'view' => ['view all contacts'],
        'edit' => ['edit all contacts'],
        'update' => ['edit all contacts'],
        'create' => ['edit all contacts'],
        'delete' => ['delete contacts'],
      ],
      'theme' => [
        'template' => 'civicrm-email',
        'path' => drupal_get_path('module', 'civicrm_entity') . '/templates',
      ],
      'display suite' => [
        'link fields' => [
          [
            'link_field' => 'contact_id',
            'target' => 'civicrm_contact',
          ],
          [
            'link_field' => 'contact_id_contact',
            'target' => 'civicrm_contact',
          ],
        ],
        'option fields' => ['location_type_id'],
        'boolean fields' => [
          'is_primary',
          'is_billing',
          'on_hold',
          'is_bulkmail',
        ],
      ],
    ];
    $civicrm_entity_info['civicrm_entity_tag'] = [
      'civicrm entity name' => 'entity_tag',
      'label property' => 'tag_id',
      'permissions' => [
        'view' => [],
        'edit' => [],
        'update' => [],
        'create' => [],
        'delete' => [],
      ],
      'theme' => [
        'template' => 'civicrm-entity-tag',
        'path' => drupal_get_path('module', 'civicrm_entity') . '/templates',
      ],
      'display suite' => [
        'link fields' => [
          [
            'link_field' => 'tag_id',
            'target' => 'civicrm_tag',
          ],
        ],
      ],
    ];
    $civicrm_entity_info['civicrm_entity_financial_trxn'] = [
      'civicrm entity name' => 'entity_financial_trxn',
      'label property' => 'id',
      'permissions' => [
        'view' => [],
        'edit' => [],
        'update' => [],
        'create' => [],
        'delete' => [],
      ],
      'theme' => [
        'template' => 'civicrm-entity-financial-trxn',
        'path' => drupal_get_path('module', 'civicrm_entity') . '/templates',
      ],
      'display suite' => [
        'link fields' => [
          [
            'link_field' => 'financial_trxn_id',
            'target' => 'civicrm_financial_trxn',
          ],
        ],
      ],
    ];
    $civicrm_entity_info['civicrm_financial_account'] = [
      'civicrm entity name' => 'financial_account',
      'label property' => 'name',
      'permissions' => [
        'view' => [],
        'edit' => [],
        'update' => [],
        'create' => [],
        'delete' => [],
      ],
      'theme' => [
        'template' => 'civicrm-financial-account',
        'path' => drupal_get_path('module', 'civicrm_entity') . '/templates',
      ],
      'display suite' => [
        'option fields' => ['financial_account_type_id'],
        'link fields' => [
          [
            'link_field' => 'contact_id',
            'target' => 'civicrm_contact',
          ],
          [
            'link_field' => 'parent_id',
            'target' => 'civicrm_financial_account',
          ],
        ],
        'date fields' => [
          'trxn_date',
        ],
        'boolean fields' => [
          'is_header_account',
          'is_deductible',
          'is_tax',
          'is_reserved',
          'is_active',
          'is_default',
        ],
      ],
    ];
    $civicrm_entity_info['civicrm_financial_trxn'] = [
      'civicrm entity name' => 'financial_trxn',
      'label property' => 'id',
      'permissions' => [
        'view' => [],
        'edit' => [],
        'update' => [],
        'create' => [],
        'delete' => [],
      ],
      'theme' => [
        'template' => 'civicrm-financial-trxn',
        'path' => drupal_get_path('module', 'civicrm_entity') . '/templates',
      ],
      'display suite' => [
        'option fields' => ['status_id'],
        'link fields' => [
          [
            'link_field' => 'payment_processor_id',
            'target' => 'civicrm_payment_processor',
          ],
        ],
        'date fields' => [
          'trxn_date',
        ],
      ],
    ];
    //dirty check for whether financialType exists
    if (!method_exists('CRM_Contribute_PseudoConstant', 'contributionType')) {
      $civicrm_entity_info['civicrm_financial_type'] = [
        'civicrm entity name' => 'financial_type',
        'label property' => 'description',
        'permissions' => [
          'view' => ['access CiviContribute', 'administer CiviCRM'],
          'edit' => ['access CiviContribute', 'administer CiviCRM'],
          'update' => ['access CiviContribute', 'administer CiviCRM'],
          'create' => ['access CiviContribute', 'administer CiviCRM'],
          'delete' => ['delete in CiviContribute', 'administer CiviCRM'],
        ],
        'theme' => [
          'template' => 'civicrm-financial-type',
          'path' => drupal_get_path('module', 'civicrm_entity') . '/templates',
        ],
        'display suite' => [
          'boolean fields' => ['is_reserved', 'is_active', 'is_deductible'],
        ],
      ];
    }

    $civicrm_entity_info['civicrm_event'] = [
      'civicrm entity name' => 'event',
      'label property' => 'title',
      'permissions' => [
        'view' => ['view event info'],
        'edit' => ['edit all events'],
        'update' => ['edit all events'],
        'create' => ['edit all events'],
        'delete' => ['edit all events', 'delete in CiviEvent'],
      ],
      'theme' => [
        'template' => 'civicrm-event',
        'path' => drupal_get_path('module', 'civicrm_entity') . '/templates',
      ],
    ];
    $civicrm_entity_info['civicrm_group'] = [
      'civicrm entity name' => 'group',
      'label property' => 'name',
      'permissions' => [
        'view' => ['edit groups'],
        'edit' => ['edit groups'],
        'update' => ['edit groups'],
        'create' => ['edit groups'],
        'delete' => ['edit groups', 'administer CiviCRM'],
      ],
      'theme' => [
        'template' => 'civicrm-group',
        'path' => drupal_get_path('module', 'civicrm_entity') . '/templates',
      ],
      'display suite' => [
        'link fields' => [
          [
            'link_field' => 'created_id',
            'target' => 'civicrm_contact',
          ],
          [
            'link_field' => 'created_id_contact',
            'target' => 'civicrm_contact',
          ],
          [
            'link_field' => 'modified_id',
            'target' => 'civicrm_contact',
          ],
          [
            'link_field' => 'modified_id_contact',
            'target' => 'civicrm_contact',
          ],
        ],
        'boolean fields' => ['is_active', 'is_hidden', 'is_reserved'],
        'timestamp fields' => [
          'cached_date',
          'refresh_date',
        ],
      ],
    ];
    $civicrm_entity_info['civicrm_grant'] = [
      'civicrm entity name' => 'grant',
      'label property' => '',
      'permissions' => [
        'view' => ['access CiviGrant', 'administer CiviCRM'],
        'edit' => ['access CiviGrant', 'edit grants'],
        'update' => ['access CiviGrant', 'edit grants'],
        'create' => ['access CiviGrant', 'edit grants'],
        'delete' => ['access CiviGrant', 'edit grants'],
      ],
      'theme' => [
        'template' => 'civicrm-grant',
        'path' => drupal_get_path('module', 'civicrm_entity') . '/templates',
      ],
      'display suite' => [
        'link fields' => [
          [
            'link_field' => 'contact_id',
            'target' => 'civicrm_contact',
          ],
          [
            'link_field' => 'contact_id_contact',
            'target' => 'civicrm_contact',
          ],
        ],
        'option fields' => [
          'status_id',
          'financial_type_id',
          'grant_type_id',
        ],
        'boolean fields' => ['grant_report_received'],
        'date fields' => [
          'application_received_date',
          'decision_date',
          'money_transfer_date',
          'grant_due_date',
        ],
      ],
    ];
    $civicrm_entity_info['civicrm_im'] = [
      'civicrm entity name' => 'im',
      'label property' => 'name',
      'permissions' => [
        'view' => ['view all contacts'],
        'edit' => ['edit all contacts'],
        'update' => ['edit all contacts'],
        'create' => ['edit all contacts'],
        'delete' => ['delete contacts'],
      ],
      'theme' => [
        'template' => 'civicrm-im',
        'path' => drupal_get_path('module', 'civicrm_entity') . '/templates',
      ],
      'display suite' => [
        'link fields' => [
          [
            'link_field' => 'contact_id',
            'target' => 'civicrm_contact',
          ],
        ],
        'option fields' => [
          'location_type_id',
          'provider_id',
        ],
        'boolean fields' => ['is_primary', 'is_billing'],
      ],
    ];
    $civicrm_entity_info['civicrm_line_item'] = [
      'civicrm entity name' => 'line_item',
      'label property' => 'label',
      'permissions' => [
        'view' => ['administer CiviCRM'],
        'edit' => ['administer CiviCRM'],
        'update' => ['administer CiviCRM'],
        'create' => ['administer CiviCRM'],
        'delete' => ['administer CiviCRM'],
      ],
      'theme' => [
        'template' => 'civicrm-line-item',
        'path' => drupal_get_path('module', 'civicrm_entity') . '/templates',
      ],
      'display suite' => [
        'link fields' => [
          [
            'link_field' => 'contribution_id',
            'target' => 'civicrm_contribution',
          ],
          [
            'link_field' => 'price_field_id',
            'target' => 'civicrm_price_field',
          ],
          [
            'link_field' => 'price_field_value_id',
            'target' => 'civicrm_price_field_value',
          ],
          [
            'link_field' => 'financial_type_id',
            'target' => 'civicrm_financial_type',
          ],
        ],
      ],
    ];
    $civicrm_entity_info['civicrm_loc_block'] = [
      'civicrm entity name' => 'loc_block',
      'label property' => '',
      'permissions' => [
        'view' => ['administer CiviCRM'],
        'edit' => ['administer CiviCRM'],
        'update' => ['administer CiviCRM'],
        'create' => ['administer CiviCRM'],
        'delete' => ['administer CiviCRM'],
      ],
      'theme' => [
        'template' => 'civicrm-loc-block',
        'path' => drupal_get_path('module', 'civicrm_entity') . '/templates',
      ],
      'display suite' => [
        'link fields' => [
          [
            'link_field' => 'address_id',
            'target' => 'civicrm_address',
          ],
          [
            'link_field' => 'email_id',
            'target' => 'civicrm_email',
          ],
          [
            'link_field' => 'phone_id',
            'target' => 'civicrm_phone',
          ],
          [
            'link_field' => 'im_id',
            'target' => 'civicrm_im',
          ],
          [
            'link_field' => 'address_2_id',
            'target' => 'civicrm_address',
          ],
          [
            'link_field' => 'phone_2_id',
            'target' => 'civicrm_phone',
          ],
          [
            'link_field' => 'email_2_id',
            'target' => 'civicrm_email',
          ],
          [
            'link_field' => 'im_2_id',
            'target' => 'civicrm_im',
          ],
        ],
        'option fields' => [],
        'boolean fields' => [],
      ],
    ];
    $civicrm_entity_info['civicrm_membership'] = [
      'civicrm entity name' => 'membership',
      'permissions' => [
        'view' => ['access CiviMember'],
        'edit' => ['edit memberships', 'access CiviMember'],
        'update' => ['edit memberships', 'access CiviMember'],
        'create' => ['edit memberships', 'access CiviMember'],
        'delete' => ['delete in CiviMember', 'access CiviMember'],
      ],
      'theme' => [
        'template' => 'civicrm-membership',
        'path' => drupal_get_path('module', 'civicrm_entity') . '/templates',
      ],
      'display suite' => [
        'link fields' => [
          [
            'link_field' => 'contact_id',
            'target' => 'civicrm_contact',
          ],
          [
            'link_field' => 'contact_id_contact',
            'target' => 'civicrm_contact',
          ],
          [
            'link_field' => 'owner_membership_id',
            'target' => 'civicrm_membership',
          ],
          [
            'link_field' => 'id',
            'target' => 'civicrm_membership',
          ],
        ],
        'option fields' => ['membership_type_id', 'status_id'],
        'boolean fields' => ['is_test', 'is_pay_later', 'is_override'],
        'date fields' => [
          'start_date',
          'end_date',
          'join_date',
        ],
      ],
    ];
    $civicrm_entity_info['civicrm_membership_payment'] = [
      'civicrm entity name' => 'membership_payment',
      'permissions' => [
        'view' => ['access CiviMember', 'access CiviContribute'],
        'edit' => ['access CiviMember', 'access CiviContribute'],
        'update' => ['access CiviMember', 'access CiviContribute'],
        'create' => ['access CiviMember', 'access CiviContribute'],
        'delete' => [
          'delete in CiviMember',
          'access CiviMember',
          'access CiviContribute',
        ],
      ],
      'theme' => [
        'template' => 'civicrm-membership-payment',
        'path' => drupal_get_path('module', 'civicrm_entity') . '/templates',
      ],
      'display suite' => [
        'link fields' => [
          [
            'link_field' => 'contribution_id',
            'target' => 'civicrm_contribution',
          ],
          [
            'link_field' => 'membership_id',
            'target' => 'civicrm_membership',
          ],
        ],
        'option fields' => [],
        'boolean fields' => [],
      ],
    ];
    $civicrm_entity_info['civicrm_membership_type'] = [
      'civicrm entity name' => 'membership_type',
      'label property' => 'name',
      'permissions' => [
        'view' => ['access CiviMember'],
        'edit' => ['access CiviMember', 'administer CiviCRM'],
        'update' => ['access CiviMember', 'administer CiviCRM'],
        'create' => ['access CiviMember', 'administer CiviCRM'],
        'delete' => [
          'delete in CiviMember',
          'access CiviMember',
          'administer CiviCRM',
        ],
      ],
      'theme' => [
        'template' => 'civicrm-membership-type',
        'path' => drupal_get_path('module', 'civicrm_entity') . '/templates',
      ],
      'display suite' => [
        'link fields' => [
          [
            'link_field' => 'member_of_contact_id',
            'target' => 'civicrm_contact',
          ],
          [
            'link_field' => 'member_of_contact_id_contact',
            'target' => 'civicrm_contact',
          ],
        ],
        'option fields' => ['financial_type_id'],
        'boolean fields' => ['is_active', 'auto_renew'],
      ],
    ];
    $civicrm_entity_info['civicrm_note'] = [
      'civicrm entity name' => 'note',
      'label property' => 'subject',
      'permissions' => [
        'view' => [],
        'edit' => [],
        'update' => [],
        'create' => [],
        'delete' => [],
      ],
      'theme' => [
        'template' => 'civicrm-note',
        'path' => drupal_get_path('module', 'civicrm_entity') . '/templates',
      ],
      'display suite' => [
        'link fields' => [
          [
            'link_field' => 'contact_id',
            'target' => 'civicrm_contact',
          ],
        ],
        'date fields' => [
          'modified_date',
        ],
      ],
    ];
    $civicrm_entity_info['civicrm_participant'] = [
      'civicrm entity name' => 'participant',
      'label property' => 'source',
      'permissions' => [
        'view' => ['view event participants'],
        'edit' => ['edit event participants', 'access CiviEvent'],
        'update' => ['edit event participants', 'access CiviEvent'],
        'create' => ['edit event participants', 'access CiviEvent'],
        'delete' => ['edit event participants', 'access CiviEvent'],
      ],
      'theme' => [
        'template' => 'civicrm-participant',
        'path' => drupal_get_path('module', 'civicrm_entity') . '/templates',
      ],
      'display suite' => [
        'link fields' => [
          [
            'link_field' => 'contact_id',
            'target' => 'civicrm_contact',
          ],
          [
            'link_field' => 'contact_id_contact',
            'target' => 'civicrm_contact',
          ],
          [
            'link_field' => 'event_id',
            'target' => 'civicrm_event',
          ],
          [
            'link_field' => 'event_id_event',
            'target' => 'civicrm_event',
          ],
          [
            'link_field' => 'registered_by_id',
            'target' => 'civicrm_participant',
          ],
        ],
        'option fields' => ['status_id', 'role_id'],
        'boolean fields' => ['is_test', 'is_pay_later', 'must_wait'],
        'date fields' => [
          'register_date',
        ],
      ],
    ];
    $civicrm_entity_info['civicrm_participant_status_type'] = [
      'civicrm entity name' => 'participant_status_type',
      'label property' => 'label',
      'permissions' => [
        'view' => ['view event participants'],
        'edit' => ['edit event participants', 'access CiviEvent'],
        'update' => ['edit event participants', 'access CiviEvent'],
        'create' => ['edit event participants', 'access CiviEvent'],
        'delete' => ['edit event participants', 'access CiviEvent'],
      ],
      'theme' => [
        'template' => 'civicrm-participant-status-type',
        'path' => drupal_get_path('module', 'civicrm_entity') . '/templates',
      ],
      'display suite' => [
        'link fields' => [],
        'option fields' => ['visibility_id'],
        'boolean fields' => ['is_reserved', 'is_active', 'is_counted'],
      ],
    ];
    $civicrm_entity_info['civicrm_participant_payment'] = [
      'civicrm entity name' => 'participant_payment',
      'label property' => 'id',
      'permissions' => [
        'view' => ['administer CiviCRM'],
        'edit' => ['administer CiviCRM'],
        'update' => ['administer CiviCRM'],
        'create' => ['administer CiviCRM'],
        'delete' => ['administer CiviCRM'],
      ],
      'theme' => [
        'template' => 'civicrm-participant-payment',
        'path' => drupal_get_path('module', 'civicrm_entity') . '/templates',
      ],
      'display suite' => [
        'link fields' => [
          [
            'link_field' => 'participant_id',
            'target' => 'civicrm_participant',
          ],
          [
            'link_field' => 'contact_id',
            'target' => 'civicrm_contact',
          ],
        ],
      ],
    ];
    $civicrm_entity_info['civicrm_payment_processor'] = [
      'civicrm entity name' => 'payment_processor',
      'label property' => 'name',
      'permissions' => [
        'view' => ['administer CiviCRM'],
        'edit' => ['administer CiviCRM'],
        'update' => ['administer CiviCRM'],
        'create' => ['administer CiviCRM'],
        'delete' => ['administer CiviCRM'],
      ],
      'theme' => [
        'template' => 'civicrm-payment-processor',
        'path' => drupal_get_path('module', 'civicrm_entity') . '/templates',
      ],
      'display suite' => [
        'link fields' => [
          [
            'link_field' => 'payment_processor_type_id',
            'target' => 'civicrm_payment_processor_type',
          ],
        ],
        'option fields' => [],
        'boolean fields' => ['is_active', 'is_default', 'is_test'],
      ],
    ];
    $civicrm_entity_info['civicrm_payment_processor_type'] = [
      'civicrm entity name' => 'payment_processor_type',
      'label property' => 'title',
      'permissions' => [
        'view' => ['administer CiviCRM'],
        'edit' => ['administer CiviCRM'],
        'update' => ['administer CiviCRM'],
        'create' => ['administer CiviCRM'],
        'delete' => ['administer CiviCRM'],
      ],
      'theme' => [
        'template' => 'civicrm-payment-processor-type',
        'path' => drupal_get_path('module', 'civicrm_entity') . '/templates',
      ],
      'display suite' => [
        'link fields' => [],
        'option fields' => [],
        'boolean fields' => ['is_active', 'is_default'],
      ],
    ];
    $civicrm_entity_info['civicrm_phone'] = [
      'civicrm entity name' => 'phone',
      'label property' => 'phone',
      'permissions' => [
        'view' => ['view all contacts'],
        'edit' => ['edit all contacts'],
        'update' => ['edit all contacts'],
        'create' => ['edit all contacts'],
        'delete' => ['delete contacts'],
      ],
      'theme' => [
        'template' => 'civicrm-phone',
        'path' => drupal_get_path('module', 'civicrm_entity') . '/templates',
      ],
      'display suite' => [
        'link fields' => [
          [
            'link_field' => 'contact_id',
            'target' => 'civicrm_contact',
          ],
        ],
        'option fields' => [
          'location_type_id',
          'mobile_provider_id',
          'phone_type_id',
        ],
        'boolean fields' => ['is_primary', 'is_billing'],
      ],
    ];
    $civicrm_entity_info['civicrm_pledge'] = [
      'civicrm entity name' => 'pledge',
      'permissions' => [
        'view' => ['access CiviPledge'],
        'edit' => ['edit pledges'],
        'update' => ['edit pledges'],
        'create' => ['edit pledges'],
        'delete' => ['edit pledges', 'administer CiviCRM'],
      ],
      'theme' => [
        'template' => 'civicrm-pledge',
        'path' => drupal_get_path('module', 'civicrm_entity') . '/templates',
      ],
      'display suite' => [
        'date fields' => [
          'start_date',
          'end_date',
          'cancel_date',
          'modified_date',
          'created_date',
          'acknowledge_date',
        ],
      ],
    ];
    $civicrm_entity_info['civicrm_pledge_payment'] = [
      'civicrm entity name' => 'pledge_payment',
      'permissions' => [
        'view' => [],
        'edit' => [],
        'update' => [],
        'create' => [],
        'delete' => [],
      ],
      'theme' => [
        'template' => 'civicrm-pledge-payment',
        'path' => drupal_get_path('module', 'civicrm_entity') . '/templates',
      ],
      'display suite' => [
        'date fields' => [
          'scheduled_date',
          'reminder_date',
        ],
      ],
    ];
    $civicrm_entity_info['civicrm_price_set'] = [
      'civicrm entity name' => 'price_set',
      'permissions' => [
        'view' => [],
        'edit' => [],
        'update' => [],
        'create' => [],
        'delete' => [],
      ],
      'theme' => [
        'template' => 'civicrm-price-set',
        'path' => drupal_get_path('module', 'civicrm_entity') . '/templates',
      ],
      'display suite' => [
        'boolean fields' => ['is_active', 'is_quick_config', 'is_reserved'],
      ],
    ];
    $civicrm_entity_info['civicrm_price_field'] = [
      'civicrm entity name' => 'price_field',
      'permissions' => [
        'view' => [],
        'edit' => [],
        'update' => [],
        'create' => [],
        'delete' => [],
      ],
      'theme' => [
        'template' => 'civicrm-price-field',
        'path' => drupal_get_path('module', 'civicrm_entity') . '/templates',
      ],
      'display suite' => [
        'link fields' => [
          [
            'link_field' => 'price_set_id',
            'target' => 'civicrm_price_set',
          ],
        ],
        'option fields' => ['visibility_id'],
        'boolean fields' => [
          'is_enter_qty',
          'is_display_amounts',
          'is_active',
          'is_required',
        ],
        'date fields' => [
          'active_on',
          'expire_on',
        ],
      ],
    ];
    $civicrm_entity_info['civicrm_price_field_value'] = [
      'civicrm entity name' => 'price_field_value',
      'permissions' => [
        'view' => [],
        'edit' => [],
        'update' => [],
        'create' => [],
        'delete' => [],
      ],
      'theme' => [
        'template' => 'civicrm-price-field-value',
        'path' => drupal_get_path('module', 'civicrm_entity') . '/templates',
      ],
      'display suite' => [
        'link fields' => [
          [
            'link_field' => 'price_field_id',
            'target' => 'civicrm_price_field',
          ],
          [
            'link_field' => 'membership_type_id',
            'target' => 'civicrm_membership_type',
          ],
        ],
        'option fields' => ['financial_type_id'],
        'boolean fields' => ['is_default', 'is_active'],
      ],
    ];
    $civicrm_entity_info['civicrm_recurring_entity'] = [
      'civicrm entity name' => 'recurring_entity',
      'permissions' => [
        'view' => [],
        'edit' => [],
        'update' => [],
        'create' => [],
        'delete' => [],
      ],
      'theme' => [
        'template' => 'civicrm-recurring-entity',
        'path' => drupal_get_path('module', 'civicrm_entity') . '/templates',
      ],
      'display suite' => [
        'link fields' => [],
        'option fields' => [],
        'boolean fields' => [],
      ],
    ];
    $civicrm_entity_info['civicrm_relationship'] = [
      'civicrm entity name' => 'relationship',
      'label property' => 'description',
      'permissions' => [
        'view' => ['view all contacts'],
        'edit' => ['edit all contacts'],
        'update' => ['edit all contacts'],
        'create' => ['edit all contacts'],
        'delete' => ['edit all contacts'],
      ],
      'theme' => [
        'template' => 'civicrm-relationship',
        'path' => drupal_get_path('module', 'civicrm_entity') . '/templates',
      ],
      'display suite' => [
        'link fields' => [
          [
            'link_field' => 'contact_id_a',
            'target' => 'civicrm_contact',
          ],
          [
            'link_field' => 'contact_id_a_contact',
            'target' => 'civicrm_contact',
          ],
          [
            'link_field' => 'contact_id_b',
            'target' => 'civicrm_contact',
          ],
          [
            'link_field' => 'contact_id_b_contact',
            'target' => 'civicrm_contact',
          ],
          [
            'link_field' => 'relationship_type_id',
            'target' => 'civicrm_relationship_type',
          ],
        ],
        'boolean fields' => [
          'is_active',
          'is_permission_a_b',
          'is_permission_b_a',
        ],
        'date fields' => [
          'start_date',
          'end_date',
        ],
      ],
    ];
    $civicrm_entity_info['civicrm_relationship_type'] = [
      'civicrm entity name' => 'relationship_type',
      'label property' => 'description',
      'permissions' => [
        'view' => ['administer CiviCRM'],
        'edit' => ['administer CiviCRM'],
        'update' => ['administer CiviCRM'],
        'create' => ['administer CiviCRM'],
        'delete' => ['administer CiviCRM'],
      ],
      'theme' => [
        'template' => 'civicrm-relationship-type',
        'path' => drupal_get_path('module', 'civicrm_entity') . '/templates',
      ],
      'display suite' => [
        'option fields' => ['contact_sub_type_a', 'contact_sub_type_b'],
        'boolean fields' => ['is_reserved', 'is_active'],
      ],
    ];
    $civicrm_entity_info['civicrm_survey'] = [
      'civicrm entity name' => 'survey',
      'label property' => 'title',
      'permissions' => [
        'view' => ['administer CiviCampaign'],
        'edit' => ['administer CiviCampaign'],
        'update' => ['administer CiviCampaign'],
        'create' => ['administer CiviCampaign'],
        'delete' => ['administer CiviCampaign'],
      ],
      'theme' => [
        'template' => 'civicrm-survey',
        'path' => drupal_get_path('module', 'civicrm_entity') . '/templates',
      ],
      'display suite' => [
        'link fields' => [
          [
            'link_field' => 'campaign_id',
            'target' => 'civicrm_campaign',
          ],
          [
            'link_field' => 'created_id',
            'target' => 'civicrm_contact',
          ],
          [
            'link_field' => 'last_modified_id',
            'target' => 'civicrm_contact',
          ],
        ],
        'option fields' => ['activity_type_id'],
        'boolean fields' => [
          'is_active',
          'is_default',
          'bypass_confirm',
          'is_share',
        ],
        'date fields' => [
          'created_date',
          'last_modified_date',
        ],
      ],
    ];
    $civicrm_entity_info['civicrm_tag'] = [
      'civicrm entity name' => 'tag',
      'label property' => 'name',
      'permissions' => [
        'view' => ['administer Tagsets'],
        'edit' => ['administer Tagsets'],
        'update' => ['administer Tagsets'],
        'create' => ['administer Tagsets'],
        'delete' => ['administer Tagsets'],
      ],
      'theme' => [
        'template' => 'civicrm-tag',
        'path' => drupal_get_path('module', 'civicrm_entity') . '/templates',
      ],
      'display suite' => [
        'link fields' => [
          [
            'link_field' => 'created_id',
            'target' => 'civicrm_contact',
          ],
          [
            'link_field' => 'created_id_contact',
            'target' => 'civicrm_contact',
          ],
          [
            'link_field' => 'parent_id',
            'target' => 'civicrm_tag',
          ],
        ],
        'boolean fields' => ['is_reserved', 'is_tagset', 'is_selectable'],
        'date fields' => [
          'created_date',
        ],
      ],
    ];
    $civicrm_entity_info['civicrm_custom_field'] = [
      'civicrm entity name' => 'custom_field',
      'label property' => 'label',
      'permissions' => [
        'view' => [],
        'edit' => [],
        'update' => [],
        'create' => [],
        'delete' => [],
      ],
      'theme' => [
        'template' => 'civicrm-custom-field',
        'path' => drupal_get_path('module', 'civicrm_entity') . '/templates',
      ],
      'display suite' => [
        'link fields' => [
          [
            'link_field' => 'custom_group_id',
            'target' => 'civicrm_custom_group',
          ],
        ],
        'boolean fields' => [
          'is_view',
          'is_active',
          'is_required',
          'is_searchable',
          'is_search_range',
        ],
      ],
    ];
    $civicrm_entity_info['civicrm_custom_group'] = [
      'civicrm entity name' => 'custom_group',
      'label property' => 'title',
      'permissions' => [
        'view' => [],
        'edit' => [],
        'update' => [],
        'create' => [],
        'delete' => [],
      ],
      'theme' => [
        'template' => 'civicrm-custom-group',
        'path' => drupal_get_path('module', 'civicrm_entity') . '/templates',
      ],
      'display suite' => [
        'link fields' => [
          [
            'link_field' => 'created_id_contact',
            'target' => 'civicrm_contact',
          ],
          [
            'link_field' => 'membership_type_id',
            'target' => 'civicrm_membership_type',
          ],
        ],
        'boolean fields' => [
          'is_multiple',
          'is_active',
          'collapse_display',
          'collapse_adv_display',
          'is_reserved',
        ],
        'date fields' => [
          'created_date',
        ],
      ],
    ];
    $civicrm_entity_info['civicrm_website'] = [
      'civicrm entity name' => 'website',
      'label property' => 'url',
      'permissions' => [
        'view' => ['view all contacts'],
        'edit' => ['edit all contacts'],
        'update' => ['edit all contacts'],
        'create' => ['edit all contacts'],
        'delete' => ['delete contacts'],
      ],
      'theme' => [
        'template' => 'civicrm-website',
        'path' => drupal_get_path('module', 'civicrm_entity') . '/templates',
      ],
      'display suite' => [
        'link fields' => [
          [
            'link_field' => 'contact_id',
            'target' => 'civicrm_contact',
          ],
        ],
        'option fields' => [
          'website_type_id',
        ],
      ],
    ];
    return $civicrm_entity_info;
  }

}
