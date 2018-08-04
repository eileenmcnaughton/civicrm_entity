<?php

namespace Drupal\civicrm_entity\Entity\Sql;

use Drupal\civicrm_entity\CiviEntityStorage;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;

/**
 * Implementation of the SqlContentEntityStorageSchema for CiviCRM entities.
 *
 * This allows CiviCRM entities to support dedicated field storage while using
 * CiviCRM for full data of the entity.
 */
class CivicrmEntityStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * The storage field definitions for this entity type.
   *
   * @var \Drupal\Core\Field\FieldStorageDefinitionInterface[]
   */
  protected $fieldStorageDefinitions;

  /**
   * The storage object for the given entity type.
   *
   * @var \Drupal\civicrm_entity\CiviEntityStorage
   */
  protected $storage;

  /**
   * Constructs a CivicrmEntityStorageSchema.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\ContentEntityTypeInterface $entity_type
   *   The entity type.
   * @param \Drupal\civicrm_entity\CiviEntityStorage $storage
   *   The storage of the entity type. This must be an SQL-based storage.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection to be used.
   */
  public function __construct(EntityManagerInterface $entity_manager, ContentEntityTypeInterface $entity_type, CiviEntityStorage $storage, Connection $database) {
    $this->entityManager = $entity_manager;
    $this->entityType = $entity_type;
    $this->fieldStorageDefinitions = $entity_manager->getFieldStorageDefinitions($entity_type->id());
    $this->storage = $storage;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityTypeCreate(EntityTypeInterface $entity_type) {
    $this->checkEntityType($entity_type);

    // Create dedicated field tables.
    $table_mapping = $this->storage->getTableMapping();
    foreach ($this->fieldStorageDefinitions as $field_storage_definition) {
      if ($table_mapping->requiresDedicatedTableStorage($field_storage_definition)) {
        $this->createDedicatedTableSchema($field_storage_definition);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityTypeUpdate(EntityTypeInterface $entity_type, EntityTypeInterface $original) {
    $this->checkEntityType($entity_type);
    $this->checkEntityType($original);
  }

  /**
   * {@inheritdoc}
   */
  public function requiresEntityStorageSchemaChanges(EntityTypeInterface $entity_type, EntityTypeInterface $original) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   *
   * The entity table schema is managed by CiviCRM.
   */
  protected function getEntitySchemaTables() {
    return [];
  }

}
