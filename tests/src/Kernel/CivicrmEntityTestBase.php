<?php

namespace Drupal\Tests\civicrm_entity\Kernel;

use Drupal\civicrm_entity\CiviCrmApiInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\civicrm_entity\Traits\CivicrmEntityTrait;
use Prophecy\Argument;

/**
 * Test base to aid in mocking the CiviCRM API.
 */
abstract class CivicrmEntityTestBase extends KernelTestBase implements ServiceModifierInterface {

  use CivicrmEntityTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'civicrm',
    'civicrm_entity',
    'field',
    'filter',
    'text',
    'options',
    'link',
    'datetime',
  ];

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $this->mockCiviCrmApi($container);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();
    $this->setUpCivicrm();
  }

  /**
   * {@inheritdoc}
   */
  protected function bootEnvironment() {
    parent::bootEnvironment();
    $this->bootEnvironmentCivicrm();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() : void {
    $this->tearDownCivicrm();
  }

  /**
   * Mocks the CiviCRM API.
   */
  protected function mockCiviCrmApi(ContainerBuilder $container) {
    $civicrm_api_mock = $this->prophesize(CiviCrmApiInterface::class);
    $civicrm_api_mock->civicrmInitialize()->willReturn();
    $civicrm_api_mock->getCustomFieldMetadata(Argument::any())->willReturn();

    $civicrm_api_mock->get('event', [
      'id' => [
        'IN' => [1],
      ],
      'return' => array_keys($this->sampleEventsGetFields()),
      'options' => ['limit' => 0],
    ])->willReturn($this->sampleEventsData());
    $civicrm_api_mock->get('contact', [
      'id' => [
        'IN' => [10],
      ],
      'return' => array_keys($this->sampleContactGetFields()),
      'options' => ['limit' => 0],
    ])->willReturn($this->sampleContactData());

    $civicrm_api_mock->getFields('event')->willReturn($this->sampleEventsGetFields());
    $civicrm_api_mock->getFields('event', 'create')->willReturn($this->sampleEventsGetFields());
    $civicrm_api_mock->getFields('contact')->willReturn($this->sampleContactGetFields());
    $civicrm_api_mock->getFields('contact', 'create')->willReturn($this->sampleContactGetFields());
    $civicrm_api_mock->getFields('address')->willReturn($this->sampleAddressGetFields());
    $civicrm_api_mock->getFields('address', 'create')->willReturn($this->sampleAddressGetFields());

    $civicrm_api_mock->getFields(Argument::type('string'), 'create')->willReturn([
      'id' => [
        'name' => 'id',
        'type' => 1,
        'title' => 'Fake ID',
        'description' => 'Unique Contact ID',
        'required' => TRUE,
      ],
    ]);

    $civicrm_api_mock->save('event', Argument::type('array'))->willReturn(TRUE);
    $civicrm_api_mock->delete('event', Argument::type('array'))->willReturn(TRUE);

    $civicrm_api_mock->getOptions('activity', 'activity_type_id')->willReturn([
      'Foo' => 'Foo',
      'Bar' => 'Bar',
    ]);
    $civicrm_api_mock->getOptions('event', 'event_type_id')->willReturn([
      'Baz' => 'Baz',
      'Zoo' => 'Zoo',
      'Conference' => 'Conference',
    ]);

    $civicrm_api_mock->get('entity', Argument::type('array'))->willReturn([
      'Event',
      'Activity',
      'Contact',
      'Address',
    ]);

    $container->set('civicrm_entity.api', $civicrm_api_mock->reveal());
  }

  /**
   * Json returned from sample Event getfields.
   *
   * Gathered from http://dmaster.demo.civicrm.org/civicrm/api#explorer.
   *
   * @return array
   *   The field data.
   */
  protected function sampleEventsGetFields() {
    return [
      'id' => [
        'name' => 'id',
        'type' => 1,
        'title' => 'Event ID',
        'description' => 'Event',
        'required' => TRUE,
        'table_name' => 'civicrm_event',
        'entity' => 'Event',
        'bao' => 'CRM_Event_BAO_Event',
        'localizable' => 0,
        'is_core_field' => TRUE,
        'api.aliases' => [
          0 => 'event_id',
        ],
      ],
      'summary' =>
        [
          'name' => 'summary',
          'type' => 32,
          'title' => 'Event Summary',
          'description' => 'Brief summary of event. Text and html allowed. Displayed on Event Registration form and can be used on other CMS pages which need an event summary.',
          'rows' => 4,
          'cols' => 60,
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 1,
          'html' =>
            [
              'type' => 'TextArea',
              'rows' => 4,
              'cols' => 60,
            ],
          'is_core_field' => TRUE,
        ],
      'event_type_id' =>
        [
          'name' => 'event_type_id',
          'type' => 1,
          'title' => 'Event Type',
          'description' => 'Event Type ID.Implicit FK to civicrm_option_value where option_group = event_type.',
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 0,
          'html' =>
            [
              'type' => 'Select',
              'size' => 6,
              'maxlength' => 14,
            ],
          'pseudoconstant' =>
            [
              'optionGroupName' => 'event_type',
              'optionEditPath' => 'civicrm/admin/options/event_type',
            ],
          'is_core_field' => TRUE,
        ],
      'participant_listing_id' =>
        [
          'name' => 'participant_listing_id',
          'type' => 1,
          'title' => 'Participant Listing',
          'description' => 'Should we expose the participant list? Implicit FK to civicrm_option_value where option_group = participant_listing.',
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 0,
          'html' =>
            [
              'type' => 'Select',
              'size' => 6,
              'maxlength' => 14,
            ],
          'pseudoconstant' =>
            [
              'optionGroupName' => 'participant_listing',
              'optionEditPath' => 'civicrm/admin/options/participant_listing',
            ],
          'is_core_field' => TRUE,
        ],
      'is_public' =>
        [
          'name' => 'is_public',
          'type' => 16,
          'title' => 'Is Event Public',
          'description' => 'Public events will be included in the iCal feeds. Access to private event information may be limited using ACLs.',
          'default' => '1',
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 0,
          'html' =>
            [
              'type' => 'CheckBox',
            ],
          'is_core_field' => TRUE,
        ],
      'is_online_registration' =>
        [
          'name' => 'is_online_registration',
          'type' => 16,
          'title' => 'Is Online Registration',
          'description' => 'If true, include registration link on Event Info page.',
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 0,
          'html' =>
            [
              'type' => 'CheckBox',
            ],
          'is_core_field' => TRUE,
        ],
      'registration_link_text' =>
        [
          'name' => 'registration_link_text',
          'type' => 2,
          'title' => 'Event Registration Link Text',
          'description' => 'Text for link to Event Registration form which is displayed on Event Information screen when is_online_registration is true.',
          'maxlength' => 255,
          'size' => 45,
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 1,
          'html' =>
            [
              'type' => 'Text',
              'maxlength' => 255,
              'size' => 45,
            ],
          'is_core_field' => TRUE,
        ],
      'registration_start_date' =>
        [
          'name' => 'registration_start_date',
          'type' => 12,
          'title' => 'Registration Start Date',
          'description' => 'Date and time that online registration starts.',
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 0,
          'html' =>
            [
              'type' => 'Select Date',
            ],
          'is_core_field' => TRUE,
        ],
      'registration_end_date' =>
        [
          'name' => 'registration_end_date',
          'type' => 12,
          'title' => 'Registration End Date',
          'description' => 'Date and time that online registration ends.',
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 0,
          'html' =>
            [
              'type' => 'Select Date',
            ],
          'is_core_field' => TRUE,
        ],
      'max_participants' =>
        [
          'name' => 'max_participants',
          'type' => 1,
          'title' => 'Max Participants',
          'description' => 'Maximum number of registered participants to allow. After max is reached, a custom Event Full message is displayed. If NULL, allow unlimited number of participants.',
          'default' => 'NULL',
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 0,
          'html' =>
            [
              'type' => 'Text',
              'size' => 6,
              'maxlength' => 14,
            ],
          'is_core_field' => TRUE,
        ],
      'event_full_text' =>
        [
          'name' => 'event_full_text',
          'type' => 32,
          'title' => 'Event Information',
          'description' => 'Message to display on Event Information page and INSTEAD OF Event Registration form if maximum participants are signed up. Can include email address/info about getting on a waiting list, etc. Text and html allowed.',
          'rows' => 4,
          'cols' => 60,
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 1,
          'html' =>
            [
              'type' => 'TextArea',
              'rows' => 4,
              'cols' => 60,
            ],
          'is_core_field' => TRUE,
        ],
      'is_monetary' =>
        [
          'name' => 'is_monetary',
          'type' => 16,
          'title' => 'Is this a PAID event?',
          'description' => 'If true, one or more fee amounts must be set and a Payment Processor must be configured for Online Event Registration.',
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 0,
          'html' =>
            [
              'type' => 'CheckBox',
            ],
          'is_core_field' => TRUE,
        ],
      'financial_type_id' =>
        [
          'name' => 'financial_type_id',
          'type' => 1,
          'title' => 'Financial Type',
          'description' => 'Financial type assigned to paid event registrations for this event. Required if is_monetary is true.',
          'default' => 'NULL',
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 0,
          'html' =>
            [
              'type' => 'Select',
              'size' => 6,
              'maxlength' => 14,
            ],
          'pseudoconstant' =>
            [
              'table' => 'civicrm_financial_type',
              'keyColumn' => 'id',
              'labelColumn' => 'name',
            ],
          'is_core_field' => TRUE,
          'api.aliases' =>
            [
              0 => 'contribution_type_id',
            ],
        ],
      'payment_processor' =>
        [
          'name' => 'payment_processor',
          'type' => 2,
          'title' => 'Payment Processor',
          'description' => 'Payment Processors configured for this Event (if is_monetary is true)',
          'maxlength' => 128,
          'size' => 45,
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 0,
          'html' =>
            [
              'type' => 'Select',
              'maxlength' => 128,
              'size' => 45,
            ],
          'pseudoconstant' =>
            [
              'table' => 'civicrm_payment_processor',
              'keyColumn' => 'id',
              'labelColumn' => 'name',
            ],
          'is_core_field' => TRUE,
        ],
      'is_map' =>
        [
          'name' => 'is_map',
          'type' => 16,
          'title' => 'Map Enabled',
          'description' => 'Include a map block on the Event Information page when geocode info is available and a mapping provider has been specified?',
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 0,
          'html' =>
            [
              'type' => 'CheckBox',
            ],
          'is_core_field' => TRUE,
        ],
      'is_active' =>
        [
          'name' => 'is_active',
          'type' => 16,
          'title' => 'Is Active',
          'description' => 'Is this Event enabled or disabled/cancelled?',
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 0,
          'html' =>
            [
              'type' => 'CheckBox',
            ],
          'is_core_field' => TRUE,
          'api.default' => 1,
        ],
      'fee_label' =>
        [
          'name' => 'fee_label',
          'type' => 2,
          'title' => 'Fee Label',
          'maxlength' => 255,
          'size' => 45,
          'import' => TRUE,
          'where' => 'civicrm_event.fee_label',
          'headerPattern' => '/^fee|(f(ee\s)?label)$/i',
          'export' => TRUE,
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 1,
          'html' =>
            [
              'type' => 'Text',
              'maxlength' => 255,
              'size' => 45,
            ],
          'is_core_field' => TRUE,
        ],
      'is_show_location' =>
        [
          'name' => 'is_show_location',
          'type' => 16,
          'title' => 'show location',
          'description' => 'If true, show event location.',
          'default' => '1',
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 0,
          'html' =>
            [
              'type' => 'CheckBox',
            ],
          'is_core_field' => TRUE,
        ],
      'loc_block_id' =>
        [
          'name' => 'loc_block_id',
          'type' => 1,
          'title' => 'Location Block ID',
          'description' => 'FK to Location Block ID',
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 0,
          'FKClassName' => 'CRM_Core_DAO_LocBlock',
          'is_core_field' => TRUE,
          'FKApiName' => 'LocBlock',
        ],
      'default_role_id' =>
        [
          'name' => 'default_role_id',
          'type' => 1,
          'title' => 'Default Role',
          'description' => 'Participant role ID. Implicit FK to civicrm_option_value where option_group = participant_role.',
          'import' => TRUE,
          'where' => 'civicrm_event.default_role_id',
          'export' => TRUE,
          'default' => '1',
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 0,
          'html' =>
            [
              'type' => 'Select',
              'size' => 6,
              'maxlength' => 14,
            ],
          'pseudoconstant' =>
            [
              'optionGroupName' => 'participant_role',
              'optionEditPath' => 'civicrm/admin/options/participant_role',
            ],
          'is_core_field' => TRUE,
        ],
      'intro_text' =>
        [
          'name' => 'intro_text',
          'type' => 32,
          'title' => 'Introductory Message',
          'description' => 'Introductory message for Event Registration page. Text and html allowed. Displayed at the top of Event Registration form.',
          'rows' => 6,
          'cols' => 50,
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 1,
          'html' =>
            [
              'type' => 'TextArea',
              'rows' => 6,
              'cols' => 50,
            ],
          'is_core_field' => TRUE,
        ],
      'footer_text' =>
        [
          'name' => 'footer_text',
          'type' => 32,
          'title' => 'Footer Message',
          'description' => 'Footer message for Event Registration page. Text and html allowed. Displayed at the bottom of Event Registration form.',
          'rows' => 6,
          'cols' => 50,
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 1,
          'html' =>
            [
              'type' => 'TextArea',
              'rows' => 6,
              'cols' => 50,
            ],
          'is_core_field' => TRUE,
        ],
      'confirm_title' =>
        [
          'name' => 'confirm_title',
          'type' => 2,
          'title' => 'Confirmation Title',
          'description' => 'Title for Confirmation page.',
          'maxlength' => 255,
          'size' => 45,
          'default' => 'NULL',
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 1,
          'html' =>
            [
              'type' => 'Text',
              'maxlength' => 255,
              'size' => 45,
            ],
          'is_core_field' => TRUE,
        ],
      'confirm_text' =>
        [
          'name' => 'confirm_text',
          'type' => 32,
          'title' => 'Confirm Text',
          'description' => 'Introductory message for Event Registration page. Text and html allowed. Displayed at the top of Event Registration form.',
          'rows' => 6,
          'cols' => 50,
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 1,
          'html' =>
            [
              'type' => 'TextArea',
              'rows' => 6,
              'cols' => 50,
            ],
          'is_core_field' => TRUE,
        ],
      'confirm_footer_text' =>
        [
          'name' => 'confirm_footer_text',
          'type' => 32,
          'title' => 'Footer Text',
          'description' => 'Footer message for Event Registration page. Text and html allowed. Displayed at the bottom of Event Registration form.',
          'rows' => 6,
          'cols' => 50,
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 1,
          'html' =>
            [
              'type' => 'TextArea',
              'rows' => 6,
              'cols' => 50,
            ],
          'is_core_field' => TRUE,
        ],
      'is_email_confirm' =>
        [
          'name' => 'is_email_confirm',
          'type' => 16,
          'title' => 'Is confirm email',
          'description' => 'If true, confirmation is automatically emailed to contact on successful registration.',
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 0,
          'html' =>
            [
              'type' => 'CheckBox',
            ],
          'is_core_field' => TRUE,
        ],
      'confirm_email_text' =>
        [
          'name' => 'confirm_email_text',
          'type' => 32,
          'title' => 'Confirmation Email Text',
          'description' => 'text to include above standard event info on confirmation email. emails are text-only, so do not allow html for now',
          'rows' => 4,
          'cols' => 50,
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 1,
          'html' =>
            [
              'type' => 'TextArea',
              'rows' => 4,
              'cols' => 50,
            ],
          'is_core_field' => TRUE,
        ],
      'confirm_from_name' =>
        [
          'name' => 'confirm_from_name',
          'type' => 2,
          'title' => 'Confirm From Name',
          'description' => 'FROM email name used for confirmation emails.',
          'maxlength' => 255,
          'size' => 45,
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 1,
          'html' =>
            [
              'type' => 'Text',
              'maxlength' => 255,
              'size' => 45,
            ],
          'is_core_field' => TRUE,
        ],
      'confirm_from_email' =>
        [
          'name' => 'confirm_from_email',
          'type' => 2,
          'title' => 'Confirm From Email',
          'description' => 'FROM email address used for confirmation emails.',
          'maxlength' => 255,
          'size' => 45,
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 0,
          'html' =>
            [
              'type' => 'Text',
              'maxlength' => 255,
              'size' => 45,
            ],
          'is_core_field' => TRUE,
        ],
      'cc_confirm' =>
        [
          'name' => 'cc_confirm',
          'type' => 2,
          'title' => 'Cc Confirm',
          'description' => 'comma-separated list of email addresses to cc each time a confirmation is sent',
          'maxlength' => 255,
          'size' => 45,
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 0,
          'html' =>
            [
              'type' => 'Text',
              'maxlength' => 255,
              'size' => 45,
            ],
          'is_core_field' => TRUE,
        ],
      'bcc_confirm' =>
        [
          'name' => 'bcc_confirm',
          'type' => 2,
          'title' => 'Bcc Confirm',
          'description' => 'comma-separated list of email addresses to bcc each time a confirmation is sent',
          'maxlength' => 255,
          'size' => 45,
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 0,
          'html' =>
            [
              'type' => 'Text',
              'maxlength' => 255,
              'size' => 45,
            ],
          'is_core_field' => TRUE,
        ],
      'default_fee_id' =>
        [
          'name' => 'default_fee_id',
          'type' => 1,
          'title' => 'Default Fee ID',
          'description' => 'FK to civicrm_option_value.',
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 0,
          'is_core_field' => TRUE,
        ],
      'default_discount_fee_id' =>
        [
          'name' => 'default_discount_fee_id',
          'type' => 1,
          'title' => 'Default Discount Fee ID',
          'description' => 'FK to civicrm_option_value.',
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 0,
          'is_core_field' => TRUE,
        ],
      'thankyou_title' =>
        [
          'name' => 'thankyou_title',
          'type' => 2,
          'title' => 'ThankYou Title',
          'description' => 'Title for ThankYou page.',
          'maxlength' => 255,
          'size' => 45,
          'default' => 'NULL',
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 1,
          'html' =>
            [
              'type' => 'Text',
              'maxlength' => 255,
              'size' => 45,
            ],
          'is_core_field' => TRUE,
        ],
      'thankyou_text' =>
        [
          'name' => 'thankyou_text',
          'type' => 32,
          'title' => 'ThankYou Text',
          'description' => 'ThankYou Text.',
          'rows' => 6,
          'cols' => 50,
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 1,
          'html' =>
            [
              'type' => 'TextArea',
              'rows' => 6,
              'cols' => 50,
            ],
          'is_core_field' => TRUE,
        ],
      'thankyou_footer_text' =>
        [
          'name' => 'thankyou_footer_text',
          'type' => 32,
          'title' => 'Footer Text',
          'description' => 'Footer message.',
          'rows' => 6,
          'cols' => 50,
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 1,
          'html' =>
            [
              'type' => 'TextArea',
              'rows' => 6,
              'cols' => 50,
            ],
          'is_core_field' => TRUE,
        ],
      'is_pay_later' =>
        [
          'name' => 'is_pay_later',
          'type' => 16,
          'title' => 'Pay Later Allowed',
          'description' => 'if true - allows the user to send payment directly to the org later',
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 0,
          'html' =>
            [
              'type' => 'CheckBox',
            ],
          'is_core_field' => TRUE,
        ],
      'pay_later_text' =>
        [
          'name' => 'pay_later_text',
          'type' => 32,
          'title' => 'Pay Later Text',
          'description' => 'The text displayed to the user in the main form',
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 1,
          'html' =>
            [
              'type' => 'Text',
              'rows' => 2,
              'cols' => 80,
            ],
          'is_core_field' => TRUE,
        ],
      'pay_later_receipt' =>
        [
          'name' => 'pay_later_receipt',
          'type' => 32,
          'title' => 'Pay Later Receipt Text',
          'description' => 'The receipt sent to the user instead of the normal receipt text',
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 1,
          'html' =>
            [
              'type' => 'Text',
              'rows' => 2,
              'cols' => 80,
            ],
          'is_core_field' => TRUE,
        ],
      'is_partial_payment' =>
        [
          'name' => 'is_partial_payment',
          'type' => 16,
          'title' => 'Partial Payments Enabled',
          'description' => 'is partial payment enabled for this event',
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 0,
          'html' =>
            [
              'type' => 'CheckBox',
            ],
          'is_core_field' => TRUE,
        ],
      'initial_amount_label' =>
        [
          'name' => 'initial_amount_label',
          'type' => 2,
          'title' => 'Initial Amount Label',
          'description' => 'Initial amount label for partial payment',
          'maxlength' => 255,
          'size' => 45,
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 1,
          'html' =>
            [
              'type' => 'Text',
              'maxlength' => 255,
              'size' => 45,
            ],
          'is_core_field' => TRUE,
        ],
      'initial_amount_help_text' =>
        [
          'name' => 'initial_amount_help_text',
          'type' => 32,
          'title' => 'Initial Amount Help Text',
          'description' => 'Initial amount help text for partial payment',
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 1,
          'html' =>
            [
              'type' => 'Text',
              'rows' => 2,
              'cols' => 80,
            ],
          'is_core_field' => TRUE,
        ],
      'min_initial_amount' =>
        [
          'name' => 'min_initial_amount',
          'type' => 1024,
          'title' => 'Minimum Initial Amount',
          'description' => 'Minimum initial amount for partial payment',
          'precision' =>
            [
              0 => 20,
              1 => 2,
            ],
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 0,
          'html' =>
            [
              'type' => 'Text',
              'size' => 6,
              'maxlength' => 14,
            ],
          'is_core_field' => TRUE,
        ],
      'is_multiple_registrations' =>
        [
          'name' => 'is_multiple_registrations',
          'type' => 16,
          'title' => 'Allow Multiple Registrations',
          'description' => 'if true - allows the user to register multiple participants for event',
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 0,
          'html' =>
            [
              'type' => 'CheckBox',
            ],
          'is_core_field' => TRUE,
        ],
      'max_additional_participants' =>
        [
          'name' => 'max_additional_participants',
          'type' => 1,
          'title' => 'Maximum number of additional participants per registration',
          'description' => 'Maximum number of additional participants that can be registered on a single booking',
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 0,
          'is_core_field' => TRUE,
        ],
      'allow_same_participant_emails' =>
        [
          'name' => 'allow_same_participant_emails',
          'type' => 16,
          'title' => 'Does Event allow multiple registrations from same email address?',
          'description' => 'if true - allows the user to register multiple registrations from same email address.',
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 0,
          'html' =>
            [
              'type' => 'CheckBox',
            ],
          'is_core_field' => TRUE,
        ],
      'has_waitlist' =>
        [
          'name' => 'has_waitlist',
          'type' => 16,
          'title' => 'Waitlist Enabled',
          'description' => 'Whether the event has waitlist support.',
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 0,
          'html' =>
            [
              'type' => 'CheckBox',
            ],
          'is_core_field' => TRUE,
        ],
      'requires_approval' =>
        [
          'name' => 'requires_approval',
          'type' => 16,
          'title' => 'Requires Approval',
          'description' => 'Whether participants require approval before they can finish registering.',
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 0,
          'html' =>
            [
              'type' => 'CheckBox',
            ],
          'is_core_field' => TRUE,
        ],
      'expiration_time' =>
        [
          'name' => 'expiration_time',
          'type' => 1,
          'title' => 'Expiration Time',
          'description' => 'Expire pending but unconfirmed registrations after this many hours.',
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 0,
          'html' =>
            [
              'type' => 'Text',
              'size' => 6,
              'maxlength' => 14,
            ],
          'is_core_field' => TRUE,
        ],
      'allow_selfcancelxfer' =>
        [
          'name' => 'allow_selfcancelxfer',
          'type' => 16,
          'title' => 'Allow Self-service Cancellation or Transfer',
          'description' => 'Allow self service cancellation or transfer for event?',
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 0,
          'html' =>
            [
              'type' => 'CheckBox',
            ],
          'is_core_field' => TRUE,
        ],
      'selfcancelxfer_time' =>
        [
          'name' => 'selfcancelxfer_time',
          'type' => 1,
          'title' => 'Self-service Cancellation or Transfer Time',
          'description' => 'Number of hours prior to event start date to allow self-service cancellation or transfer.',
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 0,
          'html' =>
            [
              'type' => 'Text',
              'size' => 6,
              'maxlength' => 14,
            ],
          'is_core_field' => TRUE,
        ],
      'waitlist_text' =>
        [
          'name' => 'waitlist_text',
          'type' => 32,
          'title' => 'Waitlist Text',
          'description' => 'Text to display when the event is full, but participants can signup for a waitlist.',
          'rows' => 4,
          'cols' => 60,
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 1,
          'html' =>
            [
              'type' => 'TextArea',
              'rows' => 4,
              'cols' => 60,
            ],
          'is_core_field' => TRUE,
        ],
      'approval_req_text' =>
        [
          'name' => 'approval_req_text',
          'type' => 32,
          'title' => 'Approval Req Text',
          'description' => 'Text to display when the approval is required to complete registration for an event.',
          'rows' => 4,
          'cols' => 60,
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 1,
          'html' =>
            [
              'type' => 'TextArea',
              'rows' => 4,
              'cols' => 60,
            ],
          'is_core_field' => TRUE,
        ],
      'is_template' =>
        [
          'name' => 'is_template',
          'type' => 16,
          'title' => 'Is an Event Template',
          'description' => 'whether the event has template',
          'required' => TRUE,
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 0,
          'html' =>
            [
              'type' => 'CheckBox',
            ],
          'is_core_field' => TRUE,
          'api.default' => 0,
        ],
      'template_title' =>
        [
          'name' => 'template_title',
          'type' => 2,
          'title' => 'Event Template Title',
          'description' => 'Event Template Title',
          'maxlength' => 255,
          'size' => 45,
          'import' => TRUE,
          'where' => 'civicrm_event.template_title',
          'headerPattern' => '/(template.)?title$/i',
          'export' => TRUE,
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 1,
          'html' =>
            [
              'type' => 'Text',
              'maxlength' => 255,
              'size' => 45,
            ],
          'is_core_field' => TRUE,
        ],
      'created_id' =>
        [
          'name' => 'created_id',
          'type' => 1,
          'title' => 'Created By Contact ID',
          'description' => 'FK to civicrm_contact, who created this event',
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 0,
          'FKClassName' => 'CRM_Contact_DAO_Contact',
          'is_core_field' => TRUE,
          'FKApiName' => 'Contact',
        ],
      'created_date' =>
        [
          'name' => 'created_date',
          'type' => 12,
          'title' => 'Event Created Date',
          'description' => 'Date and time that event was created.',
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 0,
          'is_core_field' => TRUE,
        ],
      'currency' =>
        [
          'name' => 'currency',
          'type' => 2,
          'title' => 'Currency',
          'description' => '3 character string, value from config setting or input via user.',
          'maxlength' => 3,
          'size' => 4,
          'import' => TRUE,
          'where' => 'civicrm_event.currency',
          'headerPattern' => '/cur(rency)?/i',
          'dataPattern' => '/^[A-Z]{3}$/i',
          'export' => TRUE,
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 0,
          'html' =>
            [
              'type' => 'Select',
              'maxlength' => 3,
              'size' => 4,
            ],
          'pseudoconstant' =>
            [
              'table' => 'civicrm_currency',
              'keyColumn' => 'name',
              'labelColumn' => 'full_name',
              'nameColumn' => 'name',
            ],
          'is_core_field' => TRUE,
        ],
      'campaign_id' =>
        [
          'name' => 'campaign_id',
          'type' => 1,
          'title' => 'Campaign',
          'description' => 'The campaign for which this event has been created.',
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 0,
          'FKClassName' => 'CRM_Campaign_DAO_Campaign',
          'html' =>
            [
              'type' => 'EntityRef',
              'size' => 6,
              'maxlength' => 14,
            ],
          'pseudoconstant' =>
            [
              'table' => 'civicrm_campaign',
              'keyColumn' => 'id',
              'labelColumn' => 'title',
            ],
          'is_core_field' => TRUE,
          'FKApiName' => 'Campaign',
        ],
      'is_share' =>
        [
          'name' => 'is_share',
          'type' => 16,
          'title' => 'Is shared through social media',
          'description' => 'Can people share the event through social media?',
          'default' => '1',
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 0,
          'html' =>
            [
              'type' => 'CheckBox',
            ],
          'is_core_field' => TRUE,
        ],
      'is_confirm_enabled' =>
        [
          'name' => 'is_confirm_enabled',
          'type' => 16,
          'title' => 'Is the booking confirmation screen enabled?',
          'description' => 'If false, the event booking confirmation screen gets skipped',
          'default' => '1',
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 0,
          'html' =>
            [
              'type' => 'CheckBox',
            ],
          'is_core_field' => TRUE,
        ],
      'parent_event_id' =>
        [
          'name' => 'parent_event_id',
          'type' => 1,
          'title' => 'Parent Event ID',
          'description' => 'Implicit FK to civicrm_event: parent event',
          'default' => 'NULL',
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 0,
          'html' =>
            [
              'type' => 'EntityRef',
              'size' => 6,
              'maxlength' => 14,
            ],
          'is_core_field' => TRUE,
        ],
      'slot_label_id' =>
        [
          'name' => 'slot_label_id',
          'type' => 1,
          'title' => 'Subevent Slot Label ID',
          'description' => 'Subevent slot label. Implicit FK to civicrm_option_value where option_group = conference_slot.',
          'default' => 'NULL',
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 0,
          'html' =>
            [
              'type' => 'Select',
              'size' => 6,
              'maxlength' => 14,
            ],
          'is_core_field' => TRUE,
        ],
      'dedupe_rule_group_id' =>
        [
          'name' => 'dedupe_rule_group_id',
          'type' => 1,
          'title' => 'Dedupe Rule',
          'description' => 'Rule to use when matching registrations for this event',
          'default' => 'NULL',
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 0,
          'FKClassName' => 'CRM_Dedupe_DAO_RuleGroup',
          'html' =>
            [
              'type' => 'Select',
              'size' => 6,
              'maxlength' => 14,
            ],
          'pseudoconstant' =>
            [
              'table' => 'civicrm_dedupe_rule_group',
              'keyColumn' => 'id',
              'labelColumn' => 'title',
              'nameColumn' => 'name',
            ],
          'is_core_field' => TRUE,
          'FKApiName' => 'RuleGroup',
        ],
      'is_billing_required' =>
        [
          'name' => 'is_billing_required',
          'type' => 16,
          'title' => 'Is billing block required',
          'description' => 'if true than billing block is required this event',
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 0,
          'html' =>
            [
              'type' => 'CheckBox',
            ],
          'is_core_field' => TRUE,
        ],
      'title' =>
        [
          'name' => 'title',
          'type' => 2,
          'title' => 'Event Title',
          'description' => 'Event Title (e.g. Fall Fundraiser Dinner)',
          'maxlength' => 255,
          'size' => 45,
          'import' => TRUE,
          'where' => 'civicrm_event.title',
          'headerPattern' => '/(event.)?title$/i',
          'export' => TRUE,
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 1,
          'html' =>
            [
              'type' => 'Text',
              'maxlength' => 255,
              'size' => 45,
            ],
          'is_core_field' => TRUE,
          'uniqueName' => 'event_title',
        ],
      'description' =>
        [
          'name' => 'description',
          'type' => 32,
          'title' => 'Event Description',
          'description' => 'Full description of event. Text and html allowed. Displayed on built-in Event Information screens.',
          'rows' => 8,
          'cols' => 60,
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 1,
          'html' =>
            [
              'type' => 'TextArea',
              'rows' => 8,
              'cols' => 60,
            ],
          'is_core_field' => TRUE,
          'uniqueName' => 'event_description',
        ],
      'start_date' =>
        [
          'name' => 'start_date',
          'type' => 12,
          'title' => 'Event Start Date',
          'description' => 'Date and time that event starts.',
          'import' => TRUE,
          'where' => 'civicrm_event.start_date',
          'headerPattern' => '/^start|(s(tart\s)?date)$/i',
          'export' => TRUE,
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 0,
          'html' =>
            [
              'type' => 'Select Date',
            ],
          'is_core_field' => TRUE,
          'uniqueName' => 'event_start_date',
        ],
      'end_date' =>
        [
          'name' => 'end_date',
          'type' => 12,
          'title' => 'Event End Date',
          'description' => 'Date and time that event ends. May be NULL if no defined end date/time',
          'import' => TRUE,
          'where' => 'civicrm_event.end_date',
          'headerPattern' => '/^end|(e(nd\s)?date)$/i',
          'export' => TRUE,
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 0,
          'html' =>
            [
              'type' => 'Select Date',
            ],
          'is_core_field' => TRUE,
          'uniqueName' => 'event_end_date',
        ],
    ];
  }

  /**
   * Json returned from sample Contact getfields.
   *
   * @return array
   *   The field data.
   */
  protected function sampleContactGetFields() {
    return [
      'id' => [
        'name' => 'id',
        'type' => 1,
        'title' => 'Contact ID',
        'description' => 'Unique Contact ID',
        'required' => TRUE,
        'import' => TRUE,
        'where' => 'civicrm_contact.id',
        'headerPattern' => '/internal|contact?|id$/i',
        'export' => TRUE,
        'table_name' => 'civicrm_contact',
        'entity' => 'Contact',
        'bao' => 'CRM_Contact_BAO_Contact',
        'api.aliases' => [
          '0' => 'contact_id',
        ],
      ],
      'display_name' => [
        'name' => 'display_name',
        'type' => 2,
        'title' => 'Display Name',
        'description' => 'Formatted name representing preferred format for display/print/other output.',
        'maxlength' => 128,
        'size' => 30,
        'where' => 'civicrm_contact.display_name',
        'export' => TRUE,
        'table_name' => 'civicrm_contact',
        'entity' => 'Contact',
        'bao' => 'CRM_Contact_BAO_Contact',
        'localizable' => 0,
        'html' =>
          [
            'type' => 'Text',
            'maxlength' => 128,
            'size' => 30,
          ],
        'is_core_field' => TRUE,
      ],
      'phone_number' => [
        'name' => 'phone_number',
        'type' => 2,
        'title' => 'Phone (called) Number',
        'description' => 'Phone number in case the number does not exist in the civicrm_phone table.',
        'maxlength' => 64,
        'size' => 30,
        'table_name' => 'civicrm_activity',
        'entity' => 'Activity',
        'bao' => 'CRM_Activity_BAO_Activity',
        'html' => [
          'type' => 'Text',
          'maxlength' => 64,
          'size' => 30,
        ],
      ],
      'birth_date' => [
        'name' => 'birth_date',
        'type' => 4,
        'title' => 'Birth Date',
        'description' => 'Date of birth',
        'import' => TRUE,
        'where' => 'civicrm_contact.birth_date',
        'headerPattern' => '/^birth|(b(irth\\s)?date)|D(\\W*)O(\\W*)B(\\W*)$/i',
        'dataPattern' => '/\\d{4}-?\\d{2}-?\\d{2}/',
        'export' => TRUE,
        'table_name' => 'civicrm_contact',
        'entity' => 'Contact',
        'bao' => 'CRM_Contact_BAO_Contact',
        'html' => [
          'type' => 'Select Date',
          'format' => 'birth',
        ],
      ],
      'activity_date_time' => [
        'name' => 'activity_date_time',
        'type' => 12,
        'title' => 'Activity Date',
        'description' => 'Date and time this activity is scheduled to occur. Formerly named scheduled_date_time.',
        'import' => TRUE,
        'where' => 'civicrm_activity.activity_date_time',
        'headerPattern' => '/(activity.)?date(.time$)?/i',
        'export' => TRUE,
        'table_name' => 'civicrm_activity',
        'entity' => 'Activity',
        'bao' => 'CRM_Activity_BAO_Activity',
        'html' => [
          'type' => 'Select Date',
          'format' => 'activityDateTime',
        ],
      ],
      'is_auto' => [
        'name' => 'is_auto',
        'type' => 16,
        'title' => 'Auto',
        'table_name' => 'civicrm_activity',
        'entity' => 'Activity',
        'bao' => 'CRM_Activity_BAO_Activity',
      ],
      'details' => [
        'name' => 'details',
        'type' => 32,
        'title' => 'Details',
        'description' => 'Details about the activity (agenda, notes, etc).',
        'import' => TRUE,
        'where' => 'civicrm_activity.details',
        'headerPattern' => '/(activity.)?detail(s)?$/i',
        'export' => TRUE,
        'table_name' => 'civicrm_activity',
        'entity' => 'Activity',
        'bao' => 'CRM_Activity_BAO_Activity',
        'html' => [
          'type' => 'RichTextEditor',
          'rows' => 2,
          'cols' => 80,
        ],
        'uniqueName' => 'activity_details',
      ],
      'refresh_date' => [
        'name' => 'refresh_date',
        'type' => 256,
        'title' => 'Next Group Refresh Time',
        'description' => 'Date and time when we need to refresh the cache next.',
        'required' => '',
        'table_name' => 'civicrm_group',
        'entity' => 'Group',
        'bao' => 'CRM_Contact_BAO_Group',
      ],
      'primary_contact_id' => [
        'name' => 'primary_contact_id',
        'type' => 1,
        'title' => 'Household Primary Contact ID',
        'description' => 'Optional FK to Primary Contact for this household.',
        'where' => 'civicrm_contact.primary_contact_id',
        'table_name' => 'civicrm_contact',
        'entity' => 'Contact',
        'bao' => 'CRM_Contact_BAO_Contact',
        'localizable' => 0,
        'FKClassName' => 'CRM_Contact_DAO_Contact',
        'html' => [
          'type' => 'Select',
          'size' => 6,
          'maxlength' => 14,
        ],
        'is_core_field' => TRUE,
        'FKApiName' => 'Contact',
      ],
      // Not on the contact fields, but used to test references to objects
      // which are not mapped to entities.
      'msg_template_id' => [
        'name' => 'msg_template_id',
        'type' => 1,
        'title' => 'Mailing Message Template',
        'description' => 'FK to the message template.',
        'where' => 'civicrm_mailing.msg_template_id',
        'table_name' => 'civicrm_mailing',
        'entity' => 'Mailing',
        'bao' => 'CRM_Mailing_BAO_Mailing',
        'localizable' => 0,
        'FKClassName' => 'CRM_Core_DAO_MessageTemplate',
      ],
    ];
  }

  /**
   * Provides sample data for generic get fields.
   *
   * @return array
   *   The array of test field data.
   */
  protected function minimalGenericGetFields() {
    return [
      'id' => [
        'name' => 'id',
        'type' => 1,
        'title' => 'Event ID',
        'description' => 'Event',
        'required' => TRUE,
        'table_name' => 'civicrm_event',
        'entity' => 'Event',
        'bao' => 'CRM_Event_BAO_Event',
        'localizable' => 0,
        'is_core_field' => TRUE,
        'api.aliases' => [
          0 => 'event_id',
        ],
      ],
    ];
  }

  /**
   * Provides sample contacts data.
   *
   * @return array
   *   The events data.
   */
  protected function sampleContactData() {
    return [
      0 => [
        'contact_id' => '10',
        'contact_type' => 'Individual',
        'contact_sub_type' => '',
        'sort_name' => 'Neal, Emma',
        'display_name' => 'Emma Neal',
        'do_not_email' => '0',
        'do_not_phone' => '0',
        'do_not_mail' => '0',
        'do_not_sms' => '0',
        'do_not_trade' => '0',
        'is_opt_out' => '0',
        'legal_identifier' => '',
        'external_identifier' => '',
        'nick_name' => '',
        'legal_name' => '',
        'image_URL' => '',
        'preferred_communication_method' =>
          [
            0 => '5',
          ],
        'preferred_language' => '',
        'preferred_mail_format' => 'Both',
        'first_name' => 'Emma',
        'middle_name' => '',
        'last_name' => 'Neal',
        'prefix_id' => '',
        'suffix_id' => '',
        'formal_title' => '',
        'communication_style_id' => '',
        'job_title' => '',
        'gender_id' => '2',
        'birth_date' => '1982-06-28',
        'is_deceased' => '0',
        'deceased_date' => '',
        'household_name' => '',
        'organization_name' => '',
        'sic_code' => '',
        'contact_is_deleted' => '0',
        'current_employer' => '',
        'address_id' => '36',
        'street_address' => '2262 Frances Ct',
        'supplemental_address_1' => '',
        'supplemental_address_2' => '',
        'supplemental_address_3' => '',
        'city' => 'Memphis',
        'postal_code_suffix' => '',
        'postal_code' => '68042',
        'geo_code_1' => '41.095604',
        'geo_code_2' => '-96.43168',
        'state_province_id' => '1026',
        'country_id' => '1228',
        'phone_id' => '62',
        'phone_type_id' => '1',
        'phone' => '(555) 555-555',
        'email_id' => '62',
        'email' => 'emma@example.com',
        'on_hold' => '0',
        'im_id' => '',
        'provider_id' => '',
        'im' => '',
        'worldregion_id' => '2',
        'world_region' => 'America South, Central, North and Caribbean',
        'languages' => '',
        'individual_prefix' => '',
        'individual_suffix' => '',
        'communication_style' => '',
        'gender' => 'Male',
        'state_province_name' => 'Nebraska',
        'state_province' => 'NE',
        'country' => 'United States',
        'id' => '10',
      ],
    ];
  }

  /**
   * Provides sample events data.
   *
   * @return array
   *   The events data.
   */
  protected function sampleEventsData() {
    return [
      0 => [
        'id' => '1',
        'title' => 'Fall Fundraiser Dinner',
        'event_title' => 'Fall Fundraiser Dinner',
        'summary' => 'Kick up your heels at our Fall Fundraiser Dinner/Dance at Glen Echo Park! Come by yourself or bring a partner, friend or the entire family!',
        'description' => 'This event benefits our teen programs. Admission includes a full 3 course meal and wine or soft drinks. Grab your dancing shoes, bring the kids and come join the party!',
        'event_description' => 'This event benefits our teen programs. Admission includes a full 3 course meal and wine or soft drinks. Grab your dancing shoes, bring the kids and come join the party!',
        'event_type_id' => 'Conference',
        'participant_listing_id' => '1',
        'is_public' => '1',
        'start_date' => '2018-05-02 17:00:00',
        'event_start_date' => '2018-05-02 17:00:00',
        'end_date' => '2018-05-04 17:00:00',
        'event_end_date' => '2018-05-04 17:00:00',
        'is_online_registration' => '1',
        'registration_link_text' => 'Register Now',
        'max_participants' => '100',
        'event_full_text' => 'Sorry! The Fall Fundraiser Dinner is full. Please call Jane at 204 222-1000 ext 33 if you want to be added to the waiting list.',
        'is_monetary' => '1',
        'financial_type_id' => '4',
        'payment_processor' => '1',
        'is_map' => '1',
        'is_active' => '1',
        'fee_label' => 'Dinner Contribution',
        'is_show_location' => '1',
        'loc_block_id' => '1',
        'default_role_id' => '1',
        'intro_text' => 'Fill in the information below to join as at this wonderful dinner event.',
        'confirm_title' => 'Confirm Your Registration Information',
        'confirm_text' => 'Review the information below carefully.',
        'is_email_confirm' => '1',
        'confirm_email_text' => 'Contact the Development Department if you need to make any changes to your registration.',
        'confirm_from_name' => 'Fundraising Dept.',
        'confirm_from_email' => 'development@example.org',
        'thankyou_title' => 'Thanks for Registering!',
        'thankyou_text' => 'Thank you',
        'is_pay_later' => '1',
        'pay_later_text' => 'I will send payment by check',
        'pay_later_receipt' => 'Send a check payable to Our Organization within 3 business days to hold your reservation. Checks should be sent to: 100 Main St., Suite 3, San Francisco CA 94110',
        'is_partial_payment' => '0',
        'is_multiple_registrations' => '1',
        'max_additional_participants' => '0',
        'allow_same_participant_emails' => '0',
        'allow_selfcancelxfer' => '0',
        'selfcancelxfer_time' => '0',
        'is_template' => '0',
        'currency' => 'USD',
        'is_share' => '1',
        'is_confirm_enabled' => '1',
        'is_billing_required' => '0',
        'contribution_type_id' => '4',
      ],
    ];
  }

  /**
   * Sample address field data.
   *
   * @return array
   *   The sample address data array.
   */
  protected function sampleAddressGetFields() {
    return [
      'id' => [
        'name' => 'id',
        'type' => 1,
        'title' => 'Address ID',
        'description' => 'Unique Address ID',
        'required' => TRUE,
        'where' => 'civicrm_address.id',
        'table_name' => 'civicrm_address',
        'entity' => 'Address',
        'bao' => 'CRM_Core_BAO_Address',
        'localizable' => 0,
        'is_core_field' => TRUE,
        'api.aliases' => [
          0 => 'address_id',
        ],
      ],
      'contact_id' => [
        'name' => 'contact_id',
        'type' => 1,
        'title' => 'Contact ID',
        'description' => 'FK to Contact ID',
        'where' => 'civicrm_address.contact_id',
        'table_name' => 'civicrm_address',
        'entity' => 'Address',
        'bao' => 'CRM_Core_BAO_Address',
        'localizable' => 0,
        'FKClassName' => 'CRM_Contact_DAO_Contact',
        'is_core_field' => TRUE,
        'FKApiName' => 'Contact',
      ],
      'location_type_id' => [
        'name' => 'location_type_id',
        'type' => 1,
        'title' => 'Address Location Type',
        'description' => 'Which Location does this address belong to.',
        'where' => 'civicrm_address.location_type_id',
        'table_name' => 'civicrm_address',
        'entity' => 'Address',
        'bao' => 'CRM_Core_BAO_Address',
        'localizable' => 0,
        'html' => [
          'type' => 'Select',
          'size' => 6,
          'maxlength' => 14,
        ],
        'pseudoconstant' => [
          'table' => 'civicrm_location_type',
          'keyColumn' => 'id',
          'labelColumn' => 'display_name',
        ],
        'is_core_field' => TRUE,
      ],
      'is_primary' => [
        'name' => 'is_primary',
        'type' => 16,
        'title' => 'Is Address Primary?',
        'description' => 'Is this the primary address.',
        'where' => 'civicrm_address.is_primary',
        'default' => '0',
        'table_name' => 'civicrm_address',
        'entity' => 'Address',
        'bao' => 'CRM_Core_BAO_Address',
        'localizable' => 0,
        'html' => [
          'type' => 'CheckBox',
        ],
        'is_core_field' => TRUE,
      ],
      'is_billing' => [
        'name' => 'is_billing',
        'type' => 16,
        'title' => 'Is Billing Address',
        'description' => 'Is this the billing address.',
        'where' => 'civicrm_address.is_billing',
        'default' => '0',
        'table_name' => 'civicrm_address',
        'entity' => 'Address',
        'bao' => 'CRM_Core_BAO_Address',
        'localizable' => 0,
        'html' => [
          'type' => 'CheckBox',
        ],
        'is_core_field' => TRUE,
      ],
      'street_address' => [
        'name' => 'street_address',
        'type' => 2,
        'title' => 'Street Address',
        'description' => 'Concatenation of all routable street address components (prefix, street number, street name, suffix, unit
      number OR P.O. Box). Apps should be able to determine physical location with this data (for mapping, mail
      delivery, etc.).',
        'maxlength' => 96,
        'size' => 45,
        'import' => TRUE,
        'where' => 'civicrm_address.street_address',
        'headerPattern' => '/(street|address)/i',
        'dataPattern' => '/^(\\d{1,5}( [0-9A-Za-z]+)+)$|^(P\\.?O\\.\\? Box \\d{1,5})$/i',
        'export' => TRUE,
        'table_name' => 'civicrm_address',
        'entity' => 'Address',
        'bao' => 'CRM_Core_BAO_Address',
        'localizable' => 0,
        'html' => [
          'type' => 'Text',
          'maxlength' => 96,
          'size' => 45,
        ],
        'is_core_field' => TRUE,
      ],
      'street_number' => [
        'name' => 'street_number',
        'type' => 1,
        'title' => 'Street Number',
        'description' => 'Numeric portion of address number on the street, e.g. For 112A Main St, the street_number = 112.',
        'where' => 'civicrm_address.street_number',
        'export' => TRUE,
        'table_name' => 'civicrm_address',
        'entity' => 'Address',
        'bao' => 'CRM_Core_BAO_Address',
        'localizable' => 0,
        'html' => [
          'type' => 'Text',
          'size' => 6,
          'maxlength' => 14,
        ],
        'is_core_field' => TRUE,
      ],
      'street_name' => [
        'name' => 'street_name',
        'type' => 2,
        'title' => 'Street Name',
        'description' => 'Actual street name, excluding St, Dr, Rd, Ave, e.g. For 112 Main St, the street_name = Main.',
        'maxlength' => 64,
        'size' => 30,
        'where' => 'civicrm_address.street_name',
        'export' => TRUE,
        'table_name' => 'civicrm_address',
        'entity' => 'Address',
        'bao' => 'CRM_Core_BAO_Address',
        'localizable' => 0,
        'html' => [
          'type' => 'Text',
          'maxlength' => 64,
          'size' => 30,
        ],
        'is_core_field' => TRUE,
      ],
      'street_type' => [
        'name' => 'street_type',
        'type' => 2,
        'title' => 'Street Type',
        'description' => 'St, Rd, Dr, etc.',
        'maxlength' => 8,
        'size' => 8,
        'where' => 'civicrm_address.street_type',
        'table_name' => 'civicrm_address',
        'entity' => 'Address',
        'bao' => 'CRM_Core_BAO_Address',
        'localizable' => 0,
        'html' => [
          'type' => 'Text',
          'maxlength' => 8,
          'size' => 8,
        ],
        'is_core_field' => TRUE,
      ],
      'street_unit' => [
        'name' => 'street_unit',
        'type' => 2,
        'title' => 'Street Unit',
        'description' => 'Secondary unit designator, e.g. Apt 3 or Unit # 14, or Bldg 1200',
        'maxlength' => 16,
        'size' => 12,
        'where' => 'civicrm_address.street_unit',
        'export' => TRUE,
        'table_name' => 'civicrm_address',
        'entity' => 'Address',
        'bao' => 'CRM_Core_BAO_Address',
        'localizable' => 0,
        'html' => [
          'type' => 'Text',
          'maxlength' => 16,
          'size' => 12,
        ],
        'is_core_field' => TRUE,
      ],
      'supplemental_address_1' => [
        'name' => 'supplemental_address_1',
        'type' => 2,
        'title' => 'Supplemental Address 1',
        'description' => 'Supplemental Address Information, Line 1',
        'maxlength' => 96,
        'size' => 45,
        'import' => TRUE,
        'where' => 'civicrm_address.supplemental_address_1',
        'headerPattern' => '/(supplemental(\\s)?)?address(\\s\\d+)?/i',
        'dataPattern' => '/unit|ap(ar)?t(ment)?\\s(\\d|\\w)+/i',
        'export' => TRUE,
        'table_name' => 'civicrm_address',
        'entity' => 'Address',
        'bao' => 'CRM_Core_BAO_Address',
        'localizable' => 0,
        'html' => [
          'type' => 'Text',
          'maxlength' => 96,
          'size' => 45,
        ],
        'is_core_field' => TRUE,
      ],
      'supplemental_address_2' => [
        'name' => 'supplemental_address_2',
        'type' => 2,
        'title' => 'Supplemental Address 2',
        'description' => 'Supplemental Address Information, Line 2',
        'maxlength' => 96,
        'size' => 45,
        'import' => TRUE,
        'where' => 'civicrm_address.supplemental_address_2',
        'headerPattern' => '/(supplemental(\\s)?)?address(\\s\\d+)?/i',
        'dataPattern' => '/unit|ap(ar)?t(ment)?\\s(\\d|\\w)+/i',
        'export' => TRUE,
        'table_name' => 'civicrm_address',
        'entity' => 'Address',
        'bao' => 'CRM_Core_BAO_Address',
        'localizable' => 0,
        'html' => [
          'type' => 'Text',
          'maxlength' => 96,
          'size' => 45,
        ],
        'is_core_field' => TRUE,
      ],
      'supplemental_address_3' => [
        'name' => 'supplemental_address_3',
        'type' => 2,
        'title' => 'Supplemental Address 3',
        'description' => 'Supplemental Address Information, Line 3',
        'maxlength' => 96,
        'size' => 45,
        'import' => TRUE,
        'where' => 'civicrm_address.supplemental_address_3',
        'headerPattern' => '/(supplemental(\\s)?)?address(\\s\\d+)?/i',
        'dataPattern' => '/unit|ap(ar)?t(ment)?\\s(\\d|\\w)+/i',
        'export' => TRUE,
        'table_name' => 'civicrm_address',
        'entity' => 'Address',
        'bao' => 'CRM_Core_BAO_Address',
        'localizable' => 0,
        'html' => [
          'type' => 'Text',
          'maxlength' => 96,
          'size' => 45,
        ],
        'is_core_field' => TRUE,
      ],
      'city' => [
        'name' => 'city',
        'type' => 2,
        'title' => 'City',
        'description' => 'City, Town or Village Name.',
        'maxlength' => 64,
        'size' => 30,
        'import' => TRUE,
        'where' => 'civicrm_address.city',
        'headerPattern' => '/city/i',
        'dataPattern' => '/^[A-Za-z]+(\\.?)(\\s?[A-Za-z]+){0,2}$/',
        'export' => TRUE,
        'table_name' => 'civicrm_address',
        'entity' => 'Address',
        'bao' => 'CRM_Core_BAO_Address',
        'localizable' => 0,
        'html' => [
          'type' => 'Text',
          'maxlength' => 64,
          'size' => 30,
        ],
        'is_core_field' => TRUE,
      ],
      'county_id' => [
        'name' => 'county_id',
        'type' => 1,
        'title' => 'County',
        'description' => 'Which County does this address belong to.',
        'where' => 'civicrm_address.county_id',
        'table_name' => 'civicrm_address',
        'entity' => 'Address',
        'bao' => 'CRM_Core_BAO_Address',
        'localizable' => 0,
        'FKClassName' => 'CRM_Core_DAO_County',
        'html' => [
          'type' => 'ChainSelect',
          'size' => 6,
          'maxlength' => 14,
        ],
        'pseudoconstant' => [
          'table' => 'civicrm_county',
          'keyColumn' => 'id',
          'labelColumn' => 'name',
        ],
        'is_core_field' => TRUE,
        'FKApiName' => 'County',
      ],
      'state_province_id' => [
        'name' => 'state_province_id',
        'type' => 1,
        'title' => 'State/Province',
        'description' => 'Which State_Province does this address belong to.',
        'where' => 'civicrm_address.state_province_id',
        'table_name' => 'civicrm_address',
        'entity' => 'Address',
        'bao' => 'CRM_Core_BAO_Address',
        'localizable' => 0,
        'localize_context' => 'province',
        'FKClassName' => 'CRM_Core_DAO_StateProvince',
        'html' => [
          'type' => 'ChainSelect',
          'size' => 6,
          'maxlength' => 14,
        ],
        'pseudoconstant' => [
          'table' => 'civicrm_state_province',
          'keyColumn' => 'id',
          'labelColumn' => 'name',
        ],
        'is_core_field' => TRUE,
        'FKApiName' => 'StateProvince',
      ],
      'postal_code_suffix' => [
        'name' => 'postal_code_suffix',
        'type' => 2,
        'title' => 'Postal Code Suffix',
        'description' => 'Store the suffix, like the +4 part in the USPS system.',
        'maxlength' => 12,
        'size' => 3,
        'import' => TRUE,
        'where' => 'civicrm_address.postal_code_suffix',
        'headerPattern' => '/p(ostal)\\sc(ode)\\ss(uffix)/i',
        'dataPattern' => '/\\d?\\d{4}(-\\d{4})?/',
        'export' => TRUE,
        'table_name' => 'civicrm_address',
        'entity' => 'Address',
        'bao' => 'CRM_Core_BAO_Address',
        'localizable' => 0,
        'html' => [
          'type' => 'Text',
          'maxlength' => 12,
          'size' => 3,
        ],
        'is_core_field' => TRUE,
      ],
      'postal_code' => [
        'name' => 'postal_code',
        'type' => 2,
        'title' => 'Postal Code',
        'description' => 'Store both US (zip5) AND international postal codes. App is responsible for country/region appropriate validation.',
        'maxlength' => 64,
        'size' => 6,
        'import' => TRUE,
        'where' => 'civicrm_address.postal_code',
        'headerPattern' => '/postal|zip/i',
        'dataPattern' => '/\\d?\\d{4}(-\\d{4})?/',
        'export' => TRUE,
        'table_name' => 'civicrm_address',
        'entity' => 'Address',
        'bao' => 'CRM_Core_BAO_Address',
        'localizable' => 0,
        'html' => [
          'type' => 'Text',
          'maxlength' => 64,
          'size' => 6,
        ],
        'is_core_field' => TRUE,
      ],
      'country_id' => [
        'name' => 'country_id',
        'type' => 1,
        'title' => 'Country',
        'description' => 'Which Country does this address belong to.',
        'where' => 'civicrm_address.country_id',
        'table_name' => 'civicrm_address',
        'entity' => 'Address',
        'bao' => 'CRM_Core_BAO_Address',
        'localizable' => 0,
        'localize_context' => 'country',
        'FKClassName' => 'CRM_Core_DAO_Country',
        'html' => [
          'type' => 'Select',
          'size' => 6,
          'maxlength' => 14,
        ],
        'pseudoconstant' => [
          'table' => 'civicrm_country',
          'keyColumn' => 'id',
          'labelColumn' => 'name',
          'nameColumn' => 'iso_code',
        ],
        'is_core_field' => TRUE,
        'FKApiName' => 'Country',
      ],
      'timezone' => [
        'name' => 'timezone',
        'type' => 2,
        'title' => 'Timezone',
        'description' => 'Timezone expressed as a UTC offset - e.g. United States CST would be written as "UTC-6".',
        'maxlength' => 8,
        'size' => 8,
        'where' => 'civicrm_address.timezone',
        'table_name' => 'civicrm_address',
        'entity' => 'Address',
        'bao' => 'CRM_Core_BAO_Address',
        'localizable' => 0,
        'html' => [
          'type' => 'Text',
          'maxlength' => 8,
          'size' => 8,
        ],
        'is_core_field' => TRUE,
      ],
      'address_name' => [
        'name' => 'name',
        'type' => 2,
        'title' => 'Address Name',
        'maxlength' => 255,
        'size' => 45,
        'import' => TRUE,
        'where' => 'civicrm_address.name',
        'headerPattern' => '/^location|(l(ocation\\s)?name)$/i',
        'dataPattern' => '/^\\w+$/',
        'export' => TRUE,
        'table_name' => 'civicrm_address',
        'entity' => 'Address',
        'bao' => 'CRM_Core_BAO_Address',
        'localizable' => 0,
        'html' => [
          'type' => 'Text',
          'maxlength' => 255,
          'size' => 45,
        ],
        'is_core_field' => TRUE,
      ],
      'master_id' => [
        'name' => 'master_id',
        'type' => 1,
        'title' => 'Master Address Belongs To',
        'description' => 'FK to Address ID',
        'import' => TRUE,
        'where' => 'civicrm_address.master_id',
        'export' => TRUE,
        'table_name' => 'civicrm_address',
        'entity' => 'Address',
        'bao' => 'CRM_Core_BAO_Address',
        'localizable' => 0,
        'FKClassName' => 'CRM_Core_DAO_Address',
        'is_core_field' => TRUE,
        'FKApiName' => 'Address',
      ],
      'world_region' => [
        'title' => 'World Region',
        'name' => 'world_region',
        'type' => 32,
      ],
    ];
  }

}
