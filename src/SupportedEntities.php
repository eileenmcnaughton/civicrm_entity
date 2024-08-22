<?php

namespace Drupal\civicrm_entity;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Defines supported entities.
 *
 * See `$civicrm_entity_info['civicrm_event']` for implemented usage. This might
 * not be required. Perhaps we just implement entity class definitions using
 * this information. Providing entity type derivatives seems hard, and possibly
 * overhead when we just can provide the classes directly.
 *
 * Entities could just define the `civicrm_entity` key to define their support
 * and what CiviCRM entity they map to.
 *
 * Ported for now and used in civicrm_entity_entity_type_build()
 *
 * @see civicrm_entity_entity_type_build()
 */
final class SupportedEntities {

  /**
   * Gets information about the supported CiviCRM entities.
   *
   * @return array
   *   The entity information.
   */
  public static function getInfo() {
    $civicrm_entity_info = [];
    $civicrm_entity_info['civicrm_action_schedule'] = [
      'civicrm entity label' => t('Action Schedule'),
      'civicrm entity name' => 'action_schedule',
      'label property' => 'name',
      'permissions' => [
        'view' => [],
        'update' => [],
        'create' => [],
        'delete' => [],
      ],
      'required' => [
        'title' => TRUE,
        'mapping_id' => TRUE,
        'entity_value' => TRUE,
      ],
    ];
    $civicrm_entity_info['civicrm_activity'] = [
      'civicrm entity label' => t('Activity'),
      'civicrm entity name' => 'activity',
      'label property' => 'subject',
      'bundle property' => 'activity_type_id',
      'permissions' => [
        'view' => ['view all activities'],

        'update' => [],
        'create' => [],
        'delete' => ['delete activities'],
      ],
      'required' => [
        'source_contact_id' => TRUE,
      ],
    ];
    $civicrm_entity_info['civicrm_address'] = [
      'civicrm entity label' => t('Address'),
      'civicrm entity name' => 'address',
      'label property' => 'name',
      'permissions' => [
        'view' => ['view all contacts'],
        'edit' => ['edit all contacts'],
        'update' => ['edit all contacts'],
        'create' => ['edit all contacts'],
        'delete' => ['delete contacts'],
      ],
      'required' => [
        'location_type_id' => TRUE,
        'contact_id' => TRUE,
      ],
    ];
    $civicrm_entity_info['civicrm_campaign'] = [
      'civicrm entity label' => t('Campaign'),
      'civicrm entity name' => 'campaign',
      'label property' => 'title',
      'permissions' => [
        'view' => [],

        'update' => [],
        'create' => [],
        'delete' => [],
      ],
      'required' => [
        'title' => TRUE,
      ],
    ];
    $civicrm_entity_info['civicrm_case'] = [
      'civicrm entity label' => t('Case'),
      'civicrm entity name' => 'case',
      'label property' => 'subject',
      'permissions' => [
        'view' => ['access all cases and activities'],
        'edit' => ['add cases'],
        'update' => ['add cases'],
        'create' => ['add cases'],
        'delete' => ['delete in CiviCase'],
      ],
      'required' => [
        'contact_id' => TRUE,
      ],
    ];
    $civicrm_entity_info['civicrm_case_type'] = [
      'civicrm entity label' => t('Case type'),
      'civicrm entity name' => 'case_type',
      'label property' => 'title',
      'permissions' => [
        'view' => ['access all cases and activities'],
        'edit' => ['add cases'],
        'update' => ['add cases'],
        'create' => ['add cases'],
        'delete' => ['delete in CiviCase'],
      ],
    ];
    $civicrm_entity_info['civicrm_contact'] = [
      'civicrm entity label' => t('Contact'),
      'civicrm entity name' => 'contact',
      'label property' => 'display_name',
      'permissions' => [
        'view' => ['view all contacts'],
        'edit' => ['edit all contacts'],
        'update' => ['edit all contacts'],
        'create' => ['edit all contacts'],
        'delete' => ['delete contacts'],
      ],
      'required' => [
        'contact_type' => TRUE,
        'name' => TRUE,
        'first_name' => TRUE,
        'last_name' => TRUE,
        'email' => TRUE,
        'display_name' => TRUE,
      ],
    ];
    $civicrm_entity_info['civicrm_contribution'] = [
      'civicrm entity label' => t('Contribution'),
      'civicrm entity name' => 'contribution',
      'label property' => 'contribution_source',
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
    ];
    $civicrm_entity_info['civicrm_contribution_recur'] = [
      'civicrm entity label' => t('Contribution recurring'),
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
    ];
    $civicrm_entity_info['civicrm_contribution_page'] = [
      'civicrm entity label' => t('Contribution page'),
      'civicrm entity name' => 'contribution_page',
      'label property' => 'title',
      'permissions' => [
        'view' => ['make online contributions'],
        'edit' => ['access CiviContribute', 'administer CiviCRM'],
        'update' => ['access CiviContribute', 'administer CiviCRM'],
        'create' => ['access CiviContribute', 'administer CiviCRM'],
        'delete' => ['access CiviContribute', 'administer CiviCRM'],
      ],
    ];
    $civicrm_entity_info['civicrm_country'] = [
      'civicrm entity label' => t('Country'),
      'civicrm entity name' => 'country',
      'label property' => 'name',
      'permissions' => [
        'view' => ['view all contacts'],

        'update' => [],
        'create' => [],
        'delete' => [],
      ],
    ];
    $civicrm_entity_info['civicrm_email'] = [
      'civicrm entity label' => t('Email'),
      'civicrm entity name' => 'email',
      'label property' => 'email',
      'permissions' => [
        'view' => ['view all contacts'],
        'edit' => ['edit all contacts'],
        'update' => ['edit all contacts'],
        'create' => ['edit all contacts'],
        'delete' => ['delete contacts'],
      ],
    ];
    $civicrm_entity_info['civicrm_entity_tag'] = [
      'civicrm entity label' => t('Entity tag'),
      'civicrm entity name' => 'entity_tag',
      'label property' => 'tag_id',
      'permissions' => [
        'view' => [],

        'update' => [],
        'create' => [],
        'delete' => [],
      ],
    ];
    $civicrm_entity_info['civicrm_entity_financial_trxn'] = [
      'civicrm entity label' => t('Entity financial transaction'),
      'civicrm entity name' => 'entity_financial_trxn',
      'label property' => 'id',
      'permissions' => [
        'view' => [],

        'update' => [],
        'create' => [],
        'delete' => [],
      ],
    ];
    $civicrm_entity_info['civicrm_financial_account'] = [
      'civicrm entity label' => t('Financial account'),
      'civicrm entity name' => 'financial_account',
      'label property' => 'name',
      'permissions' => [
        'view' => [],

        'update' => [],
        'create' => [],
        'delete' => [],
      ],
    ];
    $civicrm_entity_info['civicrm_financial_trxn'] = [
      'civicrm entity label' => t('Financial transaction'),
      'civicrm entity name' => 'financial_trxn',
      'label property' => 'id',
      'permissions' => [
        'view' => [],

        'update' => [],
        'create' => [],
        'delete' => [],
      ],
    ];
    // Dirty check for whether financialType exists.
    if (!method_exists('CRM_Contribute_PseudoConstant', 'contributionType')) {
      $civicrm_entity_info['civicrm_financial_type'] = [
        'civicrm entity label' => t('Financial type'),
        'civicrm entity name' => 'financial_type',
        'label property' => 'description',
        'permissions' => [
          'view' => ['access CiviContribute', 'administer CiviCRM'],
          'edit' => ['access CiviContribute', 'administer CiviCRM'],
          'update' => ['access CiviContribute', 'administer CiviCRM'],
          'create' => ['access CiviContribute', 'administer CiviCRM'],
          'delete' => ['delete in CiviContribute', 'administer CiviCRM'],
        ],
      ];
    }
    $civicrm_entity_info['civicrm_event'] = [
      'civicrm entity label' => t('Event'),
      'civicrm entity name' => 'event',
      'label property' => 'title',
      'bundle property' => 'event_type_id',
      'permissions' => [
        'view' => ['view event info'],
        'edit' => ['edit all events'],
        'update' => ['edit all events'],
        'create' => ['edit all events'],
        'delete' => ['edit all events', 'delete in CiviEvent'],
      ],
      'required' => [
        'start_date' => TRUE,
        'title' => TRUE,
        'event_type_id' => TRUE,
      ],
      'fields' => [
        'summary' => [
          'description' => 'Brief summary of event.',
        ],
      ],
    ];
    $civicrm_entity_info['civicrm_group'] = [
      'civicrm entity label' => t('Group'),
      'civicrm entity name' => 'group',
      'label property' => 'name',
      'permissions' => [
        'view' => ['edit groups'],
        'edit' => ['edit groups'],
        'update' => ['edit groups'],
        'create' => ['edit groups'],
        'delete' => ['edit groups', 'administer CiviCRM'],
      ],
    ];

    $civicrm_entity_info['civicrm_group_contact'] = [
      'civicrm entity label' => t('Group Contact'),
      'civicrm entity name' => 'group_contact',
      'label property' => 'id',
      'permissions' => [
        'view' => ['edit groups'],
        'edit' => ['edit groups'],
        'update' => ['edit groups'],
        'create' => ['edit groups'],
        'delete' => ['edit groups', 'administer CiviCRM'],
      ],
    ];

    $civicrm_entity_info['civicrm_grant'] = [
      'civicrm entity label' => t('Grant'),
      'civicrm entity name' => 'grant',
      'label property' => 'id',
      'permissions' => [
        'view' => ['access CiviGrant', 'administer CiviCRM'],
        'edit' => ['access CiviGrant', 'edit grants'],
        'update' => ['access CiviGrant', 'edit grants'],
        'create' => ['access CiviGrant', 'edit grants'],
        'delete' => ['access CiviGrant', 'edit grants'],
      ],
    ];
    $civicrm_entity_info['civicrm_im'] = [
      'civicrm entity label' => t('IM'),
      'civicrm entity name' => 'im',
      'label property' => 'name',
      'permissions' => [
        'view' => ['view all contacts'],
        'edit' => ['edit all contacts'],
        'update' => ['edit all contacts'],
        'create' => ['edit all contacts'],
        'delete' => ['delete contacts'],
      ],
    ];
    $civicrm_entity_info['civicrm_line_item'] = [
      'civicrm entity label' => t('Line item'),
      'civicrm entity name' => 'line_item',
      'label property' => 'label',
      'permissions' => [
        'view' => ['administer CiviCRM'],
        'edit' => ['administer CiviCRM'],
        'update' => ['administer CiviCRM'],
        'create' => ['administer CiviCRM'],
        'delete' => ['administer CiviCRM'],
      ],
    ];
    $civicrm_entity_info['civicrm_loc_block'] = [
      'civicrm entity label' => t('Location block'),
      'civicrm entity name' => 'loc_block',
      'label property' => 'id',
      'permissions' => [
        'view' => ['administer CiviCRM'],
        'edit' => ['administer CiviCRM'],
        'update' => ['administer CiviCRM'],
        'create' => ['administer CiviCRM'],
        'delete' => ['administer CiviCRM'],
      ],
    ];
    $civicrm_entity_info['civicrm_membership'] = [
      'civicrm entity label' => t('Membership'),
      'civicrm entity name' => 'membership',
      'label property' => 'id',
      'permissions' => [
        'view' => ['access CiviMember'],
        'edit' => ['edit memberships', 'access CiviMember'],
        'update' => ['edit memberships', 'access CiviMember'],
        'create' => ['edit memberships', 'access CiviMember'],
        'delete' => ['delete in CiviMember', 'access CiviMember'],
      ],
    ];
    $civicrm_entity_info['civicrm_membership_payment'] = [
      'civicrm entity label' => t('Membership payment'),
      'civicrm entity name' => 'membership_payment',
      'label property' => 'id',
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
    ];
    $civicrm_entity_info['civicrm_membership_type'] = [
      'civicrm entity label' => t('Membership type'),
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
    ];
    $civicrm_entity_info['civicrm_note'] = [
      'civicrm entity label' => t('Note'),
      'civicrm entity name' => 'note',
      'label property' => 'subject',
      'permissions' => [
        'view' => [],

        'update' => [],
        'create' => [],
        'delete' => [],
      ],
    ];
    $civicrm_entity_info['civicrm_participant'] = [
      'civicrm entity label' => t('Participant'),
      'civicrm entity name' => 'participant',
      'label property' => 'source',
      'permissions' => [
        'view' => ['view event participants'],
        'edit' => ['edit event participants', 'access CiviEvent'],
        'update' => ['edit event participants', 'access CiviEvent'],
        'create' => ['edit event participants', 'access CiviEvent'],
        'delete' => ['edit event participants', 'access CiviEvent'],
      ],
    ];
    $civicrm_entity_info['civicrm_participant_status_type'] = [
      'civicrm entity label' => t('Participant status type'),
      'civicrm entity name' => 'participant_status_type',
      'label property' => 'label',
      'permissions' => [
        'view' => ['view event participants'],
        'edit' => ['edit event participants', 'access CiviEvent'],
        'update' => ['edit event participants', 'access CiviEvent'],
        'create' => ['edit event participants', 'access CiviEvent'],
        'delete' => ['edit event participants', 'access CiviEvent'],
      ],
    ];
    $civicrm_entity_info['civicrm_participant_payment'] = [
      'civicrm entity label' => t('Participant payment'),
      'civicrm entity name' => 'participant_payment',
      'label property' => 'id',
      'permissions' => [
        'view' => ['administer CiviCRM'],
        'edit' => ['administer CiviCRM'],
        'update' => ['administer CiviCRM'],
        'create' => ['administer CiviCRM'],
        'delete' => ['administer CiviCRM'],
      ],
    ];
    $civicrm_entity_info['civicrm_payment_processor'] = [
      'civicrm entity label' => t('Payment processor'),
      'civicrm entity name' => 'payment_processor',
      'label property' => 'name',
      'permissions' => [
        'view' => ['administer CiviCRM'],
        'edit' => ['administer CiviCRM'],
        'update' => ['administer CiviCRM'],
        'create' => ['administer CiviCRM'],
        'delete' => ['administer CiviCRM'],
      ],
    ];
    $civicrm_entity_info['civicrm_payment_processor_type'] = [
      'civicrm entity label' => t('Payment processor type'),
      'civicrm entity name' => 'payment_processor_type',
      'label property' => 'title',
      'permissions' => [
        'view' => ['administer CiviCRM'],
        'edit' => ['administer CiviCRM'],
        'update' => ['administer CiviCRM'],
        'create' => ['administer CiviCRM'],
        'delete' => ['administer CiviCRM'],
      ],
    ];
    $civicrm_entity_info['civicrm_phone'] = [
      'civicrm entity label' => t('Phone'),
      'civicrm entity name' => 'phone',
      'label property' => 'phone',
      'permissions' => [
        'view' => ['view all contacts'],
        'edit' => ['edit all contacts'],
        'update' => ['edit all contacts'],
        'create' => ['edit all contacts'],
        'delete' => ['delete contacts'],
      ],
    ];
    $civicrm_entity_info['civicrm_pledge'] = [
      'civicrm entity label' => t('Pledge'),
      'civicrm entity name' => 'pledge',
      'label property' => 'id',
      'permissions' => [
        'view' => ['access CiviPledge'],
        'edit' => ['edit pledges'],
        'update' => ['edit pledges'],
        'create' => ['edit pledges'],
        'delete' => ['edit pledges', 'administer CiviCRM'],
      ],
      'component' => 'CiviPledge',
    ];
    $civicrm_entity_info['civicrm_pledge_payment'] = [
      'civicrm entity label' => t('Pledge payment'),
      'civicrm entity name' => 'pledge_payment',
      'label property' => 'id',
      'permissions' => [
        'view' => [],

        'update' => [],
        'create' => [],
        'delete' => [],
      ],
    ];
    $civicrm_entity_info['civicrm_price_set'] = [
      'civicrm entity label' => t('Price set'),
      'civicrm entity name' => 'price_set',
      'label property' => 'id',
      'permissions' => [
        'view' => [],

        'update' => [],
        'create' => [],
        'delete' => [],
      ],
    ];
    $civicrm_entity_info['civicrm_price_field'] = [
      'civicrm entity label' => t('Price field'),
      'civicrm entity name' => 'price_field',
      'label property' => 'id',
      'permissions' => [
        'view' => [],

        'update' => [],
        'create' => [],
        'delete' => [],
      ],
    ];
    $civicrm_entity_info['civicrm_price_field_value'] = [
      'civicrm entity label' => t('Price field value'),
      'civicrm entity name' => 'price_field_value',
      'label property' => 'id',
      'permissions' => [
        'view' => [],

        'update' => [],
        'create' => [],
        'delete' => [],
      ],
    ];
    $civicrm_entity_info['civicrm_recurring_entity'] = [
      'civicrm entity label' => t('Recurring entity'),
      'civicrm entity name' => 'recurring_entity',
      'label property' => 'id',
      'permissions' => [
        'view' => [],

        'update' => [],
        'create' => [],
        'delete' => [],
      ],
    ];
    $civicrm_entity_info['civicrm_relationship'] = [
      'civicrm entity label' => t('Relationship'),
      'civicrm entity name' => 'relationship',
      'label property' => 'description',
      'permissions' => [
        'view' => ['view all contacts'],
        'edit' => ['edit all contacts'],
        'update' => ['edit all contacts'],
        'create' => ['edit all contacts'],
        'delete' => ['edit all contacts'],
      ],
    ];
    $civicrm_entity_info['civicrm_relationship_type'] = [
      'civicrm entity label' => t('Relationship type'),
      'civicrm entity name' => 'relationship_type',
      'label property' => 'description',
      'permissions' => [
        'view' => ['administer CiviCRM'],
        'edit' => ['administer CiviCRM'],
        'update' => ['administer CiviCRM'],
        'create' => ['administer CiviCRM'],
        'delete' => ['administer CiviCRM'],
      ],
    ];
    $civicrm_entity_info['civicrm_survey'] = [
      'civicrm entity label' => t('Survey'),
      'civicrm entity name' => 'survey',
      'label property' => 'title',
      'permissions' => [
        'view' => ['administer CiviCampaign'],
        'edit' => ['administer CiviCampaign'],
        'update' => ['administer CiviCampaign'],
        'create' => ['administer CiviCampaign'],
        'delete' => ['administer CiviCampaign'],
      ],
    ];
    $civicrm_entity_info['civicrm_state_province'] = [
      'civicrm entity label' => t('State/Province'),
      'civicrm entity name' => 'state_province',
      'label property' => 'name',
      'permissions' => [
        'view' => ['view all contacts'],
        'edit' => [],
        'update' => [],
        'create' => [],
        'delete' => [],
      ],
    ];
    $civicrm_entity_info['civicrm_tag'] = [
      'civicrm entity label' => t('Tag'),
      'civicrm entity name' => 'tag',
      'label property' => 'name',
      'permissions' => [
        'view' => ['administer Tagsets'],
        'edit' => ['administer Tagsets'],
        'update' => ['administer Tagsets'],
        'create' => ['administer Tagsets'],
        'delete' => ['administer Tagsets'],
      ],
    ];
    $civicrm_entity_info['civicrm_custom_field'] = [
      'civicrm entity label' => t('Custom field'),
      'civicrm entity name' => 'custom_field',
      'label property' => 'label',
      'permissions' => [
        'view' => [],

        'update' => [],
        'create' => [],
        'delete' => [],
      ],
    ];
    $civicrm_entity_info['civicrm_custom_group'] = [
      'civicrm entity label' => t('Custom field group'),
      'civicrm entity name' => 'custom_group',
      'label property' => 'title',
      'permissions' => [
        'view' => [],

        'update' => [],
        'create' => [],
        'delete' => [],
      ],
    ];
    $civicrm_entity_info['civicrm_website'] = [
      'civicrm entity label' => t('Website'),
      'civicrm entity name' => 'website',
      'label property' => 'url',
      'permissions' => [
        'view' => ['view all contacts'],
        'edit' => ['edit all contacts'],
        'update' => ['edit all contacts'],
        'create' => ['edit all contacts'],
        'delete' => ['delete contacts'],
      ],
    ];

    $civicrm_entity_info['civicrm_mailing'] = [
      'civicrm entity label' => t('Mailing'),
      'civicrm entity name' => 'mailing',
      'label property' => 'name',
      'permissions' => [
        'view' => ['view all mailing'],
        'edit' => ['edit all mailing'],
        'update' => ['edit all mailing'],
        'create' => ['edit all mailing'],
        'delete' => ['delete mailing'],
      ],
    ];

    $civicrm_entity_info['civicrm_mailing_job'] = [
      'civicrm entity label' => t('Mailing job'),
      'civicrm entity name' => 'mailing_job',
      'label property' => 'id',
      'permissions' => [
        'view' => [],
        'update' => [],
        'create' => [],
        'delete' => [],
      ],
    ];
    $civicrm_entity_info['civicrm_option_value'] = [
      'civicrm entity label' => t('Option Value'),
      'civicrm entity name' => 'option_value',
      'label property' => 'label',
      'permissions' => [
        'view' => [],
        'update' => [],
        'create' => [],
        'delete' => [],
      ],
    ];

    $civicrm_entity_info['civicrm_contribution_soft'] = [
      'civicrm entity label' => t('Soft Contribution'),
      'civicrm entity name' => 'contribution_soft',
      'label property' => 'id',
      'permissions' => [
        'view' => [],
        'update' => [],
        'create' => [],
        'delete' => [],
      ],
    ];

    $civicrm_entity_info['civicrm_pcp'] = [
      'civicrm entity label' => t('Personal Campaign Page'),
      'civicrm entity name' => 'pcp',
      'label property' => 'title',
      'permissions' => [
        'view' => [],
        'update' => [],
        'create' => [],
        'delete' => [],
      ],
    ];

    static::alterEntityInfo($civicrm_entity_info);
    // Check if API finds each entity type.
    // Necessary for tests/civi upgrade after,
    // CiviGrant moved to extension in 5.47.
    $civicrm_api = \Drupal::service('civicrm_entity.api');
    $api_entity_types = $civicrm_api->get('entity', ['sequential' => FALSE]);
    array_walk($api_entity_types, function (&$value) {
      $value = static::getEntityNameFromCamel($value);
    });
    foreach ($civicrm_entity_info as $entity_type => $entity_info) {
      if (!in_array($entity_info['civicrm entity name'], $api_entity_types)) {
        unset($civicrm_entity_info[$entity_type]);
      }
      // Insert dblocale table names
      $multilingual = \CRM_Core_I18n::isMultilingual();
      if ($multilingual) {
        // @codingStandardsIgnoreStart
        global $dbLocale;
        // @codingStandardsIgnoreEnd
        if ($dbLocale) {
          $tables = \CRM_Core_I18n_Schema::schemaStructureTables();
          if (in_array($entity_type, $tables)) {
            $locale_table_name = $entity_type . $dbLocale;
            if (strlen($locale_table_name) <= 32) {
              $civicrm_entity_info[$locale_table_name] = $entity_info;
            }
          }
        }
      }
    }
    return $civicrm_entity_info;
  }

  /**
   * Gets default form display information.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return array
   *   The form display configuration.
   */
  public static function getFormDisplayInfo($entity_type_id = NULL) {
    $info = [];

    $info['civicrm_event'] = [
      'groups' => [
        'settings' => [
          'title' => t('Settings'),
          'group' => 'advanced',
          'open' => TRUE,
        ],
      ],
      'fields' => [
        'event_type_id' => [
          'group' => 'settings',
        ],
        'default_role_id' => [
          'group' => 'settings',
        ],
        'participant_listing_id' => [
          'group' => 'settings',
        ],
        'is_map' => [
          'group' => 'settings',
        ],
        'is_public' => [
          'group' => 'settings',
        ],
        'is_share' => [
          'group' => 'settings',
        ],
        'is_active' => [
          'group' => 'settings',
        ],
        'is_monetary' => [
          'group' => 'settings',
        ],
        'is_online_registration' => [
          'group' => 'settings',
        ],
        'financial_type_id' => [
          'group' => 'settings',
        ],
      ],
    ];

    if (!$entity_type_id) {
      return $info;
    }
    return $info[$entity_type_id] ?? [];
  }

  /**
   * Gets the Drupal entity type for a CiviCRM object name.
   *
   * @param string $objectName
   *   The CiviCRM object name.
   *
   * @return string|false
   *   The Drupal entity type, or FALSE if not available.
   */
  public static function getEntityType($objectName) {
    switch ($objectName) {
      case 'Individual':
      case 'Household':
      case 'Organization':
        $entity_type = 'civicrm_contact';
        break;

      default:
        $entity_type = 'civicrm_' . static::getEntityNameFromCamel($objectName);
        break;
    }

    if (!array_key_exists($entity_type, static::getInfo())) {
      return FALSE;
    }

    return $entity_type;
  }

  /**
   * Convert possibly camel name to underscore separated entity name.
   *
   * @param string $entity
   *   Entity name in various formats e.g:
   *     Contribution => contribution,
   *     OptionValue => option_value,
   *     UFJoin => uf_join.
   *
   * @see _civicrm_api_get_entity_name_from_camel()
   *
   * @todo Why don't we just call the above function directly?
   * Because the function is officially 'likely' to change as it is an internal
   * api function and calling api functions directly is explicitly not
   * supported.
   *
   * @return string
   *   $entity entity name in underscore separated format
   */
  public static function getEntityNameFromCamel($entity) {
    if ($entity == strtolower($entity)) {
      return $entity;
    }
    else {
      $entity = ltrim(strtolower(
        str_replace('U_F', 'uf',
          // That's CamelCase, beside an odd UFCamel that is expected as
          // 'uf_camel'.
          preg_replace('/(?=[A-Z])/', '_$0', $entity)
        )), '_');
    }
    return $entity;
  }

  /**
   * Get the DAO class for an entity type.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return string
   *   The DAO class name.
   */
  public static function getEntityTypeDaoClass($entity_type_id) {
    \Drupal::service('civicrm_entity.api')->civicrmInitialize();

    $tables = \CRM_Core_DAO_AllCoreTables::tables();
    return $tables[$entity_type_id] ?? NULL;
  }

  /**
   * Allow extensions to alter entities.
   *
   * @param array $civicrm_entity_info
   *   Supported civicrm_entity info.
   *
   * @return mixed
   *   Altered civicrm_entity entity info.
   */
  public static function alterEntityInfo(array &$civicrm_entity_info) {
    \Drupal::service('civicrm_entity.api')->civicrmInitialize();

    $code_version = explode('.', \CRM_Utils_System::version());
    if (version_compare($code_version[0] . '.' . $code_version[1], 4.5) >= 0) {
      return \CRM_Utils_Hook::singleton()->invoke(['civicrm_entity_info'], $civicrm_entity_info,
        \CRM_Core_DAO::$_nullObject, \CRM_Core_DAO::$_nullObject, \CRM_Core_DAO::$_nullObject, \CRM_Core_DAO::$_nullObject,
        \CRM_Core_DAO::$_nullObject,
        'civicrm_alter_drupal_entities'
      );
    }
    else {
      return \CRM_Utils_Hook::singleton()->invoke(1, $civicrm_entity_info,
        \CRM_Core_DAO::$_nullObject, \CRM_Core_DAO::$_nullObject, \CRM_Core_DAO::$_nullObject, \CRM_Core_DAO::$_nullObject,
        'civicrm_alter_drupal_entities'
      );
    }
  }

  /**
   * Transforms an option value to a machine name.
   *
   * This is leveraged to convert options into bundle names.
   *
   * @param string $value
   *   The option value.
   * @param \Drupal\Component\Transliteration\TransliterationInterface $transliteration
   *   The transliteration service.
   *
   * @return string
   *   The transformed string.
   */
  public static function optionToMachineName($value, TransliterationInterface $transliteration) {
    $value = $transliteration->transliterate($value, LanguageInterface::LANGCODE_DEFAULT, '_');
    $value = mb_strtolower($value);
    $value = preg_replace('/[^a-z0-9_]+/', '_', $value);
    $value = preg_replace('/_+/', '_', $value);
    return $value;
  }

}
