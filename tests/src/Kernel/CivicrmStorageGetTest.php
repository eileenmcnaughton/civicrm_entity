<?php

namespace Drupal\Tests\civicrm_entity\Kernel;

use Drupal\civicrm_entity\Entity\CivicrmEntity;

/**
 * Tests the storage.
 *
 * @group civicrim_entity
 */
class CivicrmStorageGetTest extends CivicrmEntityTestBase {

  /**
   * Tests getting a single entity.
   */
  public function testGet() {
    $result = $this->container->get('civicrm_entity.api')->get('event', [
      'id' => 1,
      'return' => array_keys($this->sampleEventsGetFields()),
    ]);
    $this->assertEquals('Fall Fundraiser Dinner', $result[0]['title']);

    $result = $this->container->get('civicrm_entity.api')->get('contact', [
      'id' => 10,
      'return' => array_keys($this->sampleContactGetFields()),
    ]);
    $this->assertEquals('Emma Neal', $result[0]['display_name']);
  }

  /**
   * Tests loading an entity through storage.
   */
  public function testLoadEvent() {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('civicrm_event');
    $entity = $storage->load(1);
    $this->assertInstanceOf(CivicrmEntity::class, $entity);
    $this->assertEquals($entity->id(), 1);
    $this->assertEquals($entity->get('title')->value, 'Fall Fundraiser Dinner');
    $this->assertEquals('2018-05-02T07:00:00', $entity->get('start_date')->value);
    $this->assertEquals('2018/05/02', $entity->get('start_date')->date->format('Y/m/d'));
    $this->assertTrue($entity->get('is_public')->value);
  }

  /**
   * @group debug
   */
  public function testLoadContact() {
    $storage = $this->container->get('entity_type.manager')->getStorage('civicrm_contact');
    $entity = $storage->load(10);
    $this->assertInstanceOf(CivicrmEntity::class, $entity);
    $this->assertEquals($entity->id(), 10);
    $this->assertEquals($entity->get('display_name')->value, 'Emma Neal');
    $this->assertEquals('1982/06/27', $entity->get('birth_date')->date->format('Y/m/d'));
  }

}
