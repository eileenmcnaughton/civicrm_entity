<?php

namespace Drupal\Tests\civicrim_entity\Kernel;

use Drupal\civicrm_entity\CiviCrmApi;
use Drupal\civicrm_entity\Entity\Events;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests base field generation.
 *
 * @group civicrim_entity
 */
class CivicrmBaseFieldTest extends KernelTestBase {

  /**
   * @var array
   */
  protected static $modules = [
    'civicrm',
    'civicrm_entity',
    'field',
    'text',
    'options',
    'link',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    require __DIR__ . '/../Type.php';

    $civicrm_api_mock = $this->prophesize(CiviCrmApi::class);
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
      'phone_number' => array(
        'name' => 'phone_number',
        'type' => 2,
        'title' => 'Phone (called) Number',
        'description' => 'Phone number in case the number does not exist in the civicrm_phone table.',
        'maxlength' => 64,
        'size' => 30,
        'table_name' => 'civicrm_activity',
        'entity' => 'Activity',
        'bao' => 'CRM_Activity_BAO_Activity',
        'html' => array(
          'type' => 'Text',
          'maxlength' => 64,
          'size' => 30,
        ),
      ),
      'birth_date' => array(
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
        'html' => array(
          'type' => 'Select Date',
          'format' => 'birth',
        ),
      ),
      'activity_date_time' => array(
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
        'html' => array(
          'type' => 'Select Date',
          'format' => 'activityDateTime',
        ),
      ),
      'is_auto' => array(
        'name' => 'is_auto',
        'type' => 16,
        'title' => 'Auto',
        'table_name' => 'civicrm_activity',
        'entity' => 'Activity',
        'bao' => 'CRM_Activity_BAO_Activity',
      ),
      'details' => array(
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
        'html' => array(
          'type' => 'RichTextEditor',
          'rows' => 2,
          'cols' => 80,
        ),
        'uniqueName' => 'activity_details',
      ),
      'refresh_date' => array(
        'name' => 'refresh_date',
        'type' => 256,
        'title' => 'Next Group Refresh Time',
        'description' => 'Date and time when we need to refresh the cache next.',
        'required' => '',
        'table_name' => 'civicrm_group',
        'entity' => 'Group',
        'bao' => 'CRM_Contact_BAO_Group',
      ),
    ]);
    $this->container->set('civicrm_entity.api', $civicrm_api_mock->reveal());
  }

  /**
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Exception
   */
  public function testBaseFields() {
    /** @var \Drupal\Core\Field\FieldDefinitionInterface[] $base_fields */
    $base_fields = Events::baseFieldDefinitions($this->container->get('entity_type.manager')->getDefinition('civicrm_event'));

    $this->assertTrue(isset($base_fields['id']));
    $this->assertEquals('integer', $base_fields['id']->getType());
    $this->assertTrue(isset($base_fields['title']));
    $this->assertEquals('text', $base_fields['title']->getType());
    $this->assertTrue(isset($base_fields['phone_number']));
    $this->assertEquals('text', $base_fields['phone_number']->getType());
    $this->assertTrue(isset($base_fields['birth_date']));
    $this->assertEquals('datetime', $base_fields['birth_date']->getType());
    $this->assertTrue(isset($base_fields['activity_date_time']));
    $this->assertEquals('datetime', $base_fields['activity_date_time']->getType());
    $this->assertTrue(isset($base_fields['is_auto']));
    $this->assertEquals('boolean', $base_fields['is_auto']->getType());
    $this->assertTrue(isset($base_fields['details']));
    $this->assertEquals('text', $base_fields['details']->getType());
    $this->assertTrue(isset($base_fields['refresh_date']));
    $this->assertEquals('timestamp', $base_fields['refresh_date']->getType());
  }
}
