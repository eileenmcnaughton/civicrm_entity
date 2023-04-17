<?php

namespace Drupal\civicrm_entity\Entity\Sql;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Implementation of the SqlContentEntityStorageSchema for CiviCRM entities.
 *
 * This allows CiviCRM entities to support dedicated field storage while using
 * CiviCRM for full data of the entity.
 */
class CivicrmEntityStorageSchema extends SqlContentEntityStorageSchema {

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
   */
  public function requiresFieldStorageSchemaChanges(FieldStorageDefinitionInterface $storage_definition, FieldStorageDefinitionInterface $original) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldableEntityTypeCreate(EntityTypeInterface $entity_type, array $field_storage_definitions) {
    return;
  }

}
