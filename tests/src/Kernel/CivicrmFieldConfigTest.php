<?php

namespace Drupal\Tests\civicrm_entity\Kernel;

use Drupal\civicrm_entity\Entity\CivicrmEntity;
use Drupal\Core\Language\Language;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests the storage.
 *
 * @group civicrim_entity
 */
class CivicrmFieldConfigTest extends CivicrmEntityTestBase {

  /**
   * Make sure that creating a field does not explode the entity storage.
   */
  public function testCreateField() {
    // Create a field.
    $field_name = mb_strtolower($this->randomMachineName());
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'civicrm_event',
      'type' => 'string',
    ]);
    $field_storage->save();
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'civicrm_event',
      'label' => $this->randomMachineName() . '_label',
    ])->save();

    /** @var \Drupal\civicrm_entity\CiviEntityStorage $civi_entity_storage */
    $civi_entity_storage = $this->container->get('entity_type.manager')->getStorage('civicrm_event');
    /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
    $table_mapping = $civi_entity_storage->getTableMapping();
    $db_schema = $this->container->get('database')->schema();

    $this->assertTrue(
      $db_schema->tableExists($table_mapping->getDedicatedDataTableName($field_storage))
    );
  }

  /**
   * Test saving and loading field config.
   */
  public function testSaveAndLoadFieldConfig() {
    // Create a field.
    $field_name = mb_strtolower($this->randomMachineName());
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'civicrm_event',
      'type' => 'string',
    ]);
    $field_storage->save();
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'civicrm_event',
      'label' => $this->randomMachineName() . '_label',
    ])->save();

    $storage = $this->container->get('entity_type.manager')
      ->getStorage('civicrm_event');
    /** @var \Drupal\civicrm_entity\Entity\CivicrmEntity $entity */
    $entity = $storage->load(1);
    $this->assertInstanceOf(CivicrmEntity::class, $entity);
    $this->assertEquals($entity->id(), 1);

    $this->assertTrue($entity->get($field_name)->isEmpty());

    $entity->get($field_name)->setValue('Testing value');
    $entity->save();

    /** @var \Drupal\civicrm_entity\CiviEntityStorage $civi_entity_storage */
    $civi_entity_storage = $this->container->get('entity_type.manager')->getStorage('civicrm_event');
    $database = $this->container->get('database');
    /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
    $table_mapping = $civi_entity_storage->getTableMapping();
    $db_schema = $database->schema();

    $this->assertTrue(
      $db_schema->tableExists($table_mapping->getDedicatedDataTableName($field_storage))
    );

    $this->assertEquals(1,
      $database->select($table_mapping->getDedicatedDataTableName($field_storage))->countQuery()->execute()->fetchField()
    );

    $raw_values = $database->select($table_mapping->getDedicatedDataTableName($field_storage), 't')->fields('t')->execute()->fetchAssoc();
    $this->assertEquals([
      'bundle' => $entity->bundle(),
      'deleted' => '0',
      'entity_id' => $entity->id(),
      'revision_id' => $entity->id(),
      'langcode' => Language::LANGCODE_NOT_SPECIFIED,
      'delta' => '0',
      "{$field_name}_value" => 'Testing value',
    ], $raw_values);

    /** @var \Drupal\civicrm_entity\Entity\CivicrmEntity $entity */
    $entity = $storage->load($entity->id());
    $this->assertEquals('Testing value', $entity->get($field_name)->value);

    $entity->delete();

    $this->assertEquals(0,
      $database->select($table_mapping->getDedicatedDataTableName($field_storage))->countQuery()->execute()->fetchField()
    );
  }

}
