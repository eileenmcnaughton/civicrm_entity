<?php

namespace Drupal\Tests\civicrm_entity\Kernel;

use Drupal\civicrm_entity\CiviCrmApiInterface;
use Drupal\civicrm_entity\Entity\CivicrmEntity;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * Tests the storage.
 *
 * @group civicrm_entity
 */
class CivicrmStorageGetTest extends CivicrmEntityTestBase {

  /**
   * Tests getting a single entity.
   */
  public function testGet() {
    $result = $this->container->get('civicrm_entity.api')->get('event', [
      'id' => [
        'IN' => [1],
      ],
      'return' => array_keys($this->sampleEventsGetFields()),
      'options' => ['limit' => 0],
    ]);
    $this->assertEquals('Fall Fundraiser Dinner', $result[0]['title']);

    $result = $this->container->get('civicrm_entity.api')->get('contact', [
      'id' => [
        'IN' => [10],
      ],
      'return' => array_keys($this->sampleContactGetFields()),
      'options' => ['limit' => 0],
    ]);
    $this->assertEquals('Emma Neal', $result[0]['display_name']);
  }

  /**
   * Tests loading an entity through storage.
   */
  public function testLoadEvent() {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('civicrm_event');
    /** @var \Drupal\civicrm_entity\Entity\CivicrmEntity $entity */
    $entity = $storage->load(1);
    $this->assertInstanceOf(CivicrmEntity::class, $entity);
    $this->assertEquals($entity->id(), 1);
    $this->assertEquals($entity->get('title')->value, 'Fall Fundraiser Dinner');
    $this->assertEquals('2018-05-02T07:00:00', $entity->get('start_date')->value);
    $this->assertEquals('2018/05/02', $entity->get('start_date')->date->format('Y/m/d'));
    $this->assertTrue((bool) $entity->get('is_public')->first()->value);
  }

  /**
   * Tests loading a contact.
   */
  public function testLoadContact() {
    $storage = $this->container->get('entity_type.manager')->getStorage('civicrm_contact');
    /** @var \Drupal\civicrm_entity\Entity\CivicrmEntity $entity */
    $entity = $storage->load(10);
    $this->assertInstanceOf(CivicrmEntity::class, $entity);
    $this->assertEquals($entity->id(), 10);
    $this->assertEquals($entity->get('display_name')->value, 'Emma Neal');
    $this->assertEquals('1982/06/28', $entity->get('birth_date')->date->format('Y/m/d'));
  }

  /**
   * Tests datetime fields and timezone conversions.
   *
   * CiviCRM stores times in the user's timezone. However Drupal assumes all
   * times are in UTC. When loading a date time, CiviEntityStorage converts
   * the time into UTC so that Drupal handles the timezone correctly.
   *
   * @param array $original_datetimes
   *   Original datetimes.
   * @param array $expected_utc_datetime
   *   Expected datetimes.
   * @param string $timezone
   *   The timezone.
   *
   * @dataProvider datetimeTimezoneDataProvider
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function testDatetimeTimezone(array $original_datetimes, array $expected_utc_datetime, $timezone) {
    date_default_timezone_set($timezone);
    $civicrm_api_mock = $this->prophesize(CiviCrmApiInterface::class);
    $civicrm_api_mock->get('event', [
      'id' => 1,
      'return' => array_keys($this->sampleEventsGetFields()),
    ])->willReturn([$original_datetimes]);

    $storage = $this->container->get('entity_type.manager')
      ->getStorage('civicrm_event');
    /** @var \Drupal\civicrm_entity\Entity\CivicrmEntity $entity */
    $entity = $storage->load(1);
    foreach ($expected_utc_datetime as $field_name => $field_data) {
      $this->assertEquals(
        $field_data,
        $entity->get($field_name)->date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT)
      );
    }
  }

  /**
   * Provides datetime and timezone test data.
   */
  public function datetimeTimezoneDataProvider() {
    yield [
      [
        'start_date' => '2018-05-02 17:00:00',
        'end_date' => '2018-05-04 17:00:00',
      ],
      // America/Chicago is UTC-5.
      [
        'start_date' => '2018-05-02T22:00:00',
        'end_date' => '2018-05-04T22:00:00',
      ],
      'America/Chicago',
    ];
    yield [
      [
        'start_date' => '2018-05-02 17:00:00',
        'end_date' => '2018-05-04 17:00:00',
      ],
      [
        'start_date' => '2018-05-02T17:00:00',
        'end_date' => '2018-05-04T17:00:00',
      ],
      'UTC',
    ];
    yield [
      [
        'start_date' => '2018-05-02 17:00:00',
        'end_date' => '2018-05-04 17:00:00',
      ],
      // Europe/Berlin if UTC-2.
      [
        'start_date' => '2018-05-02T15:00:00',
        'end_date' => '2018-05-04T15:00:00',
      ],
      'Europe/Berlin',
    ];
  }

}
