<?php

namespace Drupal\civicrm_entity\Entity\Sql;

use Drupal\civicrm_entity\CiviEntityStorage;
use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Exception\FieldStorageDefinitionUpdateForbiddenException;
use Drupal\Core\Entity\Schema\DynamicallyFieldableEntityStorageSchemaInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldException;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

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
   * Constructs a SqlContentEntityStorageSchema.
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

  // TDODO: see what may need to be overirdden.
//  public function requiresFieldStorageSchemaChanges(FieldStorageDefinitionInterface $storage_definition, FieldStorageDefinitionInterface $original) {
//    // TODO: Implement requiresFieldStorageSchemaChanges() method.
//  }
//
//  public function requiresFieldDataMigration(FieldStorageDefinitionInterface $storage_definition, FieldStorageDefinitionInterface $original) {
//    // TODO: Implement requiresFieldDataMigration() method.
//  }
//
//  public function finalizePurge(FieldStorageDefinitionInterface $storage_definition) {
//    // TODO: Implement finalizePurge() method.
//  }
//
//  public function requiresEntityStorageSchemaChanges(EntityTypeInterface $entity_type, EntityTypeInterface $original) {
//    // TODO: Implement requiresEntityStorageSchemaChanges() method.
//  }
//
//  public function requiresEntityDataMigration(EntityTypeInterface $entity_type, EntityTypeInterface $original) {
//    // TODO: Implement requiresEntityDataMigration() method.
//  }
//
//  public function onEntityTypeCreate(EntityTypeInterface $entity_type) {
//    // TODO: Implement onEntityTypeCreate() method.
//  }
//
//  public function onEntityTypeUpdate(EntityTypeInterface $entity_type, EntityTypeInterface $original) {
//    // TODO: Implement onEntityTypeUpdate() method.
//  }
//
//  public function onEntityTypeDelete(EntityTypeInterface $entity_type) {
//    // TODO: Implement onEntityTypeDelete() method.
//  }
//
//  public function onFieldStorageDefinitionCreate(FieldStorageDefinitionInterface $storage_definition) {
//    // TODO: Implement onFieldStorageDefinitionCreate() method.
//  }
//
//  public function onFieldStorageDefinitionUpdate(FieldStorageDefinitionInterface $storage_definition, FieldStorageDefinitionInterface $original) {
//    // TODO: Implement onFieldStorageDefinitionUpdate() method.
//  }
//
//  public function onFieldStorageDefinitionDelete(FieldStorageDefinitionInterface $storage_definition) {
//    // TODO: Implement onFieldStorageDefinitionDelete() method.
//  }
}
