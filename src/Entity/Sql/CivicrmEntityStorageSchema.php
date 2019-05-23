<?php

namespace Drupal\civicrm_entity\Entity\Sql;

use Drupal\civicrm_entity\CiviEntityStorage;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Entity\Sql\TableMappingInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\ContentEntityTypeInterface $entity_type
   *   The entity type.
   * @param \Drupal\civicrm_entity\CiviEntityStorage $storage
   *   The storage of the entity type. This must be an SQL-based storage.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection to be used.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ContentEntityTypeInterface $entity_type, CiviEntityStorage $storage, Connection $database, EntityFieldManagerInterface $entity_field_manager = NULL) {
    $this->entityTypeManager = $entity_type_manager;
    $this->storage = clone $storage;
    $this->database = $database;
    if (!$entity_field_manager) {
      @trigger_error('Calling SqlContentEntityStorageSchema::__construct() with the $entity_field_manager argument is supported in drupal:8.7.0 and will be required before drupal:9.0.0. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
      $entity_field_manager = \Drupal::service('entity_field.manager');
    }
    $this->entityFieldManager = $entity_field_manager;

    $this->entityType = $entity_type_manager->getActiveDefinition($entity_type->id());
    $this->fieldStorageDefinitions = $entity_field_manager->getActiveFieldStorageDefinitions($entity_type->id());
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
   This will get rid of the "Mismatched entity and/or field definitions
The following changes were detected in the entity type and field definitions." 
Message on the status page. I don't think it should be left in though.

  public function requiresFieldStorageSchemaChanges(FieldStorageDefinitionInterface $storage_definition, FieldStorageDefinitionInterface $original) {
    return false;
  }
  */

  /**
   * @param \Drupal\Core\Entity\Sql\TableMappingInterface $table_mapping
   *   A table mapping object.
   *
   * @return array
   *   The entity table schema is managed by CiviCRM.
   */
  protected function getEntitySchemaTables(TableMappingInterface $table_mapping) {
    return [];
  }

}
