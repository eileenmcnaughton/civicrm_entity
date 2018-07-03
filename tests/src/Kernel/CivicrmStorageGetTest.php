<?php

namespace Drupal\Tests\civicrm_entity\Kernel;

use Drupal\civicrm_entity\Entity\CivicrmEntity;

/**
 * Tests the storage.
 *
 * @group civicrim_entity
 */
class CivicrmStorageGetTest extends CivicrmEntityTestBase {

  protected static $modules = [
    'civicrm',
    'civicrm_entity',
    'field',
    'text',
    'options',
    'link',
    'datetime',
  ];

  /**
   * Tests getting a single entity.
   */
  public function testGet() {
    $result = $this->container->get('civicrm_entity.api')
      ->get('event', ['id' => 1]);
    $this->assertEquals('Fall Fundraiser Dinner', $result[0]['title']);
  }

  /**
   * Tests loading an entity through storage.
   */
  public function testLoad() {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('civicrm_event');
    $entity = $storage->load(1);
    $this->assertInstanceOf(CivicrmEntity::class, $entity);
    $this->assertEquals($entity->id(), 1);
    $this->assertEquals($entity->get('title')->value, 'Fall Fundraiser Dinner');
    $this->assertEquals('2018-05-02T17:00:00', $entity->get('start_date')->value);
    $this->assertEquals('2018/05/02', $entity->get('start_date')->date->format('Y/m/d'));
    $this->assertTrue($entity->get('is_public')->value);
  }

}
