<?php

namespace Drupal\Tests\civicrim_entity\Kernel;

use Drupal\civicrm_entity\CiviCrmApi;
use Drupal\civicrm_entity\Entity\Events;
use Drupal\KernelTests\KernelTestBase;

class CivicrmStorageGetTest extends KernelTestBase {
  protected static $modules = [
    'civicrm',
    'civicrm_entity',
    'field',
    'text',
    'options',
    'link',
  ];

  protected function setUp() {
    parent::setUp();

    require __DIR__ . '/../Type.php';

    $civicrm_api_mock = $this->prophesize(CiviCrmApi::class);
    $civicrm_api_mock->get('event', ['id' => 1])->willReturn([
      '0' => [
        'id' => '1',
        'title' => 'Annual CiviCRM meet',
        'event_title' => 'Annual CiviCRM meet',
        'event_description' => '',
        'event_type_id' => '1',
        'participant_listing_id' => 0,
        'is_public' => '1',
        'start_date' => '2013-07-29 00:00:00',
        'event_start_date' => '2013-07-29 00:00:00',
        'event_end_date' => '',
        'is_online_registration' => 0,
        'is_monetary' => 0,
        'is_map' => 0,
        'is_active' => '1',
        'is_show_location' => '1',
        'default_role_id' => '1',
        'is_email_confirm' => 0,
        'is_pay_later' => 0,
        'is_partial_payment' => 0,
        'is_multiple_registrations' => 0,
        'max_additional_participants' => 0,
        'allow_same_participant_emails' => 0,
        'allow_selfcancelxfer' => 0,
        'selfcancelxfer_time' => 0,
        'is_template' => 0,
        'created_date' => '2013-07-28 08:49:19',
        'is_share' => '1',
        'is_confirm_enabled' => '1',
        'is_billing_required' => 0,
      ],
    ]);
    $civicrm_api_mock->getFields("event")->willReturn([
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
      'title' => [
        'name' => 'title',
        'type' => 2,
        'title' => 'Group Title',
        'description' => 'Name of Group.',
        'maxlength' => 64,
        'size' => 30,
        'table_name' => 'civicrm_group',
        'entity' => 'Group',
        'bao' => 'CRM_Contact_BAO_Group',
        'api.required' => 1,
      ],
      'event_title' => [
        'name' => 'event_title',
        'type' => 2,
        'title' => 'Group Title',
        'description' => 'Name of Group.',
        'maxlength' => 64,
        'size' => 30,
        'table_name' => 'civicrm_group',
        'entity' => 'Group',
        'bao' => 'CRM_Contact_BAO_Group',
        'api.required' => 1,
      ],
    ]);
    $this->container->set('civicrm_entity.api', $civicrm_api_mock->reveal());
  }

  public function testGet() {
    $result = $this->container->get('civicrm_entity.api')->get('event', ['id' => 1]);
    $this->assertEquals('Annual CiviCRM meet', $result[0]['title']);
  }

  public function testLoad() {
    $storage = $this->container->get('entity_type.manager')->getStorage('civicrm_event');
    $entity = $storage->load(1);
    $this->assertInstanceOf(Events::class, $entity);
    $this->assertEquals($entity->id(), 1);
    $this->assertEquals($entity->get('title')->value, 'Annual CiviCRM meet');
  }
}
