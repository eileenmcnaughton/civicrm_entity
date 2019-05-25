<?php

namespace Drupal\civicrm_entity\Entity\Sql;

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

}
