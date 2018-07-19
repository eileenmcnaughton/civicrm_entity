<?php

namespace Drupal\civicrm_entity;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Database\SchemaException;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityStorageBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Schema\DynamicallyFieldableEntityStorageSchemaInterface;
use Drupal\Core\Entity\Sql\DefaultTableMapping;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\TypedData\Plugin\DataType\DateTimeIso8601;
use Drupal\field\FieldStorageConfigInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines entity class for external CiviCRM entities.
 */
class CiviEntityStorage extends ContentEntityStorageBase implements DynamicallyFieldableEntityStorageSchemaInterface {

  /**
   * The CiviCRM API.
   *
   * @var \Drupal\civicrm_entity\CiviCrmApi
   */
  protected $civicrmApi;

  /**
   * Active database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The entity type's storage schema object.
   *
   * @var \Drupal\Core\Entity\Schema\EntityStorageSchemaInterface
   */
  protected $storageSchema;

  /**
   * The mapping of field columns to SQL tables.
   *
   * @var \Drupal\Core\Entity\Sql\TableMappingInterface
   */
  protected $tableMapping;

  /**
   * Constructs a ContentEntityStorageBase object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend to be used.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\civicrm_entity\CiviCrmApiInterface $civicrm_api
   *   The CiviCRM API.
   */
  public function __construct(EntityTypeInterface $entity_type, Connection $database, EntityManagerInterface $entity_manager, CacheBackendInterface $cache, LanguageManagerInterface $language_manager, CiviCrmApiInterface $civicrm_api) {
    parent::__construct($entity_type, $entity_manager, $cache);
    $this->database = $database;
    $this->languageManager = $language_manager;
    $this->civicrmApi = $civicrm_api;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('database'),
      $container->get('entity.manager'),
      $container->get('cache.entity'),
      $container->get('language_manager'),
      $container->get('civicrm_entity.api')
    );
  }

  /**
   * Updates the wrapped entity type definition.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The update entity type.
   *
   * @see \Drupal\Core\Entity\Sql\SqlContentEntityStorage::setEntityType()
   */
  public function setEntityType(EntityTypeInterface $entity_type) {
    if ($this->entityType->id() == $entity_type->id()) {
      $this->entityType = $entity_type;
    }
    else {
      throw new EntityStorageException("Unsupported entity type {$entity_type->id()}");
    }
  }

  /**
   * Gets the entity type's storage schema object.
   *
   * @return \Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema
   *   The schema object.
   */
  public function getStorageSchema() {
    if (!isset($this->storageSchema)) {
      $class = '\Drupal\civicrm_entity\Entity\Sql\CivicrmEntityStorageSchema';
      $this->storageSchema = new $class($this->entityManager, $this->entityType, $this, $this->database);
    }
    return $this->storageSchema;
  }

  /**
   * {@inheritdoc}
   */
  protected function doDelete($entities) {
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    foreach ($entities as $entity) {
      try {
        $params['id'] = $entity->id();
        $this->civicrmApi->delete($this->entityType->get('civicrm_entity'), $params);
      }
      catch (\Exception $e) {
        throw $e;
      }
    }
    $this->doDeleteFieldItems($entities);
  }

  /**
   * {@inheritdoc}
   */
  protected function doDeleteFieldItems($entities) {
    $table_mapping = $this->getTableMapping();

    foreach ($entities as $entity) {
      foreach ($this->entityManager->getFieldDefinitions($entity->getEntityTypeId(), $entity->bundle()) as $field_definition) {
        $storage_definition = $field_definition->getFieldStorageDefinition();
        if (!$table_mapping->requiresDedicatedTableStorage($storage_definition)) {
          continue;
        }
        $table_name = $table_mapping->getDedicatedDataTableName($storage_definition);
        $this->database->delete($table_name)
          ->condition('entity_id', $entity->id())
          ->execute();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doSave($id, EntityInterface $entity) {
    /** @var \Drupal\civicrm_entity\Entity\CivicrmEntity $entity */
    $return = $entity->isNew() ? SAVED_NEW : SAVED_UPDATED;

    $params = $entity->civicrmApiNormalize();
    $non_base_fields = array_filter($entity->getFieldDefinitions(), function (FieldDefinitionInterface $definition) {
      return !$definition->getFieldStorageDefinition()->isBaseField();
    });
    $non_base_fields = array_map(function (FieldDefinitionInterface $definition) {
      return $definition->getName();
    }, $non_base_fields);

    $result = $this->civicrmApi->save($this->entityType->get('civicrm_entity'), $params);
    if ($entity->isNew()) {
      $entity->{$this->idKey} = (string) $result['id'];
    }
    $this->doSaveFieldItems($entity, $non_base_fields);

    return $return;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\Sql\SqlContentEntityStorageException
   */
  protected function doLoadMultiple(array $ids = NULL) {
    $entities = [];
    if ($ids === NULL) {
      $civicrm_entities = $this->civicrmApi->get($this->entityType->get('civicrm_entity'));
      foreach ($civicrm_entities as $civicrm_entity) {
        $civicrm_entity = reset($civicrm_entity);
        $entity = $this->prepareLoadedEntity($civicrm_entity);
        $entities[$entity->id()] = $entity;
      }
    }
    foreach ($ids as $id) {
      $civicrm_entity = $this->civicrmApi->get($this->entityType->get('civicrm_entity'), ['id' => $id]);
      $civicrm_entity = reset($civicrm_entity);
      $entity = $this->prepareLoadedEntity($civicrm_entity);
      $entities[$entity->id()] = $entity;
    }
    return $entities;
  }

  /**
   * Prepares a loaded entity.
   *
   * @param array $civicrm_entity
   *   The entity data.
   *
   * @return \Drupal\civicrm_entity\Entity\CivicrmEntity
   *   The prepared entity.
   *
   * @throws \Drupal\Core\Entity\Sql\SqlContentEntityStorageException
   */
  protected function prepareLoadedEntity(array $civicrm_entity) {
    $this->loadFromDedicatedTables($civicrm_entity);
    $entity = new $this->entityClass([], $this->entityTypeId);
    // Use initFieldValues to fix CiviCRM data array to Drupal.
    $this->initFieldValues($entity, $civicrm_entity);
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function has($id, EntityInterface $entity) {
    return !$entity->isNew();
  }

  /**
   * {@inheritdoc}
   */
  protected function getQueryServiceName() {
    return 'entity.query.civicrm_entity';
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\Sql\SqlContentEntityStorageException
   */
  public function countFieldData($storage_definition, $as_bool = FALSE) {
    // The table mapping contains stale data during a request when a field
    // storage definition is added, so bypass the internal storage definitions
    // and fetch the table mapping using the passed in storage definition.
    // @todo Fix this in https://www.drupal.org/node/2705205.
    $table_mapping = $this->getTableMapping();

    if ($table_mapping->requiresDedicatedTableStorage($storage_definition)) {
      $is_deleted = $storage_definition instanceof FieldStorageConfigInterface && $storage_definition->isDeleted();
      $table_name = $table_mapping->getDedicatedDataTableName($storage_definition, $is_deleted);
      $query = $this->database->select($table_name, 't');
      $or = $query->orConditionGroup();
      foreach ($storage_definition->getColumns() as $column_name => $data) {
        $or->isNotNull($table_mapping->getFieldColumnName($storage_definition, $column_name));
      }
      $query->condition($or);
      if (!$as_bool) {
        $query
          ->fields('t', ['entity_id'])
          ->distinct(TRUE);
      }
    }

    // @todo Find a way to count field data also for fields having custom
    //   storage. See https://www.drupal.org/node/2337753.
    $count = 0;
    if (isset($query)) {
      // If we are performing the query just to check if the field has data
      // limit the number of rows.
      if ($as_bool) {
        $query
          ->range(0, 1)
          ->addExpression('1');
      }
      else {
        // Otherwise count the number of rows.
        $query = $query->countQuery();
      }
      $count = $query->execute()->fetchField();
    }
    return $as_bool ? (bool) $count : (int) $count;
  }

  /**
   * {@inheritdoc}
   */
  public function hasData() {
    // @todo query API and get actual count.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function readFieldItemsToPurge(FieldDefinitionInterface $field_definition, $batch_size) {
    // Check whether the whole field storage definition is gone, or just some
    // bundle fields.
    $storage_definition = $field_definition->getFieldStorageDefinition();
    $is_deleted = $storage_definition instanceof FieldStorageConfigInterface && $storage_definition->isDeleted();
    $table_mapping = $this->getTableMapping();
    $table_name = $table_mapping->getDedicatedDataTableName($storage_definition, $is_deleted);

    // Get the entities which we want to purge first.
    $entity_query = $this->database->select($table_name, 't', ['fetch' => \PDO::FETCH_ASSOC]);
    $or = $entity_query->orConditionGroup();
    foreach ($storage_definition->getColumns() as $column_name => $data) {
      $or->isNotNull($table_mapping->getFieldColumnName($storage_definition, $column_name));
    }
    $entity_query
      ->distinct(TRUE)
      ->fields('t', ['entity_id'])
      ->condition('bundle', $field_definition->getTargetBundle())
      ->range(0, $batch_size);

    // Create a map of field data table column names to field column names.
    $column_map = [];
    foreach ($storage_definition->getColumns() as $column_name => $data) {
      $column_map[$table_mapping->getFieldColumnName($storage_definition, $column_name)] = $column_name;
    }

    $entities = [];
    $items_by_entity = [];
    foreach ($entity_query->execute() as $row) {
      $item_query = $this->database->select($table_name, 't', ['fetch' => \PDO::FETCH_ASSOC])
        ->fields('t')
        ->condition('entity_id', $row['entity_id'])
        ->condition('deleted', 1)
        ->orderBy('delta');

      foreach ($item_query->execute() as $item_row) {
        if (!isset($entities[$item_row['revision_id']])) {
          // Create entity with the right revision id and entity id combination.
          $item_row['entity_type'] = $this->entityTypeId;
          // @todo: Replace this by an entity object created via an entity
          // factory, see https://www.drupal.org/node/1867228.
          $entities[$item_row['revision_id']] = _field_create_entity_from_ids((object) $item_row);
        }
        $item = [];
        foreach ($column_map as $db_column => $field_column) {
          $item[$field_column] = $item_row[$db_column];
        }
        $items_by_entity[$item_row['revision_id']][] = $item;
      }
    }

    // Create field item objects and return.
    foreach ($items_by_entity as $revision_id => $values) {
      $entity_adapter = $entities[$revision_id]->getTypedData();
      $items_by_entity[$revision_id] = \Drupal::typedDataManager()->create($field_definition, $values, $field_definition->getName(), $entity_adapter);
    }
    return $items_by_entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function purgeFieldItems(ContentEntityInterface $entity, FieldDefinitionInterface $field_definition) {
    $storage_definition = $field_definition->getFieldStorageDefinition();
    $is_deleted = $this->storageDefinitionIsDeleted($storage_definition);
    $table_mapping = $this->getTableMapping();
    $table_name = $table_mapping->getDedicatedDataTableName($storage_definition, $is_deleted);
    $revision_name = $table_mapping->getDedicatedRevisionTableName($storage_definition, $is_deleted);
    $revision_id = $this->entityType->isRevisionable() ? $entity->getRevisionId() : $entity->id();
    $this->database->delete($table_name)
      ->condition('revision_id', $revision_id)
      ->condition('deleted', 1)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  protected function doLoadRevisionFieldItems($revision_id) {}

  /**
   * {@inheritdoc}
   */
  protected function doSaveFieldItems(ContentEntityInterface $entity, array $names = []) {
    $update = !$entity->isNew();
    $table_mapping = $this->getTableMapping();
    $storage_definitions = $this->entityManager->getFieldStorageDefinitions($this->entityTypeId);
    $dedicated_table_fields = [];

    // Collect the name of fields to be written in dedicated tables and check
    // whether shared table records need to be updated.
    foreach ($names as $name) {
      $storage_definition = $storage_definitions[$name];
      if ($table_mapping->requiresDedicatedTableStorage($storage_definition)) {
        $dedicated_table_fields[] = $name;
      }
    }

    // Update dedicated table records if necessary.
    if ($dedicated_table_fields) {
      $names = is_array($dedicated_table_fields) ? $dedicated_table_fields : [];
      $this->saveToDedicatedTables($entity, $update, $names);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doDeleteRevisionFieldItems(ContentEntityInterface $revision) {}

  /**
   * {@inheritdoc}
   */
  public function onEntityTypeCreate(EntityTypeInterface $entity_type) {
    $this->wrapSchemaException(function () use ($entity_type) {
      $this->getStorageSchema()->onEntityTypeCreate($entity_type);
    });
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityTypeUpdate(EntityTypeInterface $entity_type, EntityTypeInterface $original) {
    $this->wrapSchemaException(function () use ($entity_type, $original) {
      $this->getStorageSchema()->onEntityTypeUpdate($entity_type, $original);
    });
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityTypeDelete(EntityTypeInterface $entity_type) {
    $this->wrapSchemaException(function () use ($entity_type) {
      $this->getStorageSchema()->onEntityTypeDelete($entity_type);
    });
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldStorageDefinitionCreate(FieldStorageDefinitionInterface $storage_definition) {
    $this->wrapSchemaException(function () use ($storage_definition) {
      $this->getStorageSchema()->onFieldStorageDefinitionCreate($storage_definition);
    });
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldStorageDefinitionUpdate(FieldStorageDefinitionInterface $storage_definition, FieldStorageDefinitionInterface $original) {
    $this->wrapSchemaException(function () use ($storage_definition, $original) {
      $this->getStorageSchema()->onFieldStorageDefinitionUpdate($storage_definition, $original);
    });
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldStorageDefinitionDelete(FieldStorageDefinitionInterface $storage_definition) {
    $table_mapping = $this->getTableMapping(
      $this->entityManager->getLastInstalledFieldStorageDefinitions($this->entityType->id())
    );

    if ($storage_definition instanceof FieldStorageConfigInterface && $table_mapping->requiresDedicatedTableStorage($storage_definition)) {
      // Mark all data associated with the field for deletion.
      $table = $table_mapping->getDedicatedDataTableName($storage_definition);
      $this->database->update($table)
        ->fields(['deleted' => 1])
        ->execute();
    }

    // Update the field schema.
    $this->wrapSchemaException(function () use ($storage_definition) {
      $this->getStorageSchema()->onFieldStorageDefinitionDelete($storage_definition);
    });
  }

  /**
   * Wraps a database schema exception into an entity storage exception.
   *
   * @param callable $callback
   *   The callback to be executed.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   When a database schema exception is thrown.
   */
  protected function wrapSchemaException(callable $callback) {
    $message = 'Exception thrown while performing a schema update.';
    try {
      $callback();
    }
    catch (SchemaException $e) {
      $message .= ' ' . $e->getMessage();
      throw new EntityStorageException($message, 0, $e);
    }
    catch (DatabaseExceptionWrapper $e) {
      $message .= ' ' . $e->getMessage();
      throw new EntityStorageException($message, 0, $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldDefinitionDelete(FieldDefinitionInterface $field_definition) {
    $table_mapping = $this->getTableMapping();
    $storage_definition = $field_definition->getFieldStorageDefinition();
    // Mark field data as deleted.
    if ($table_mapping->requiresDedicatedTableStorage($storage_definition)) {
      $table_name = $table_mapping->getDedicatedDataTableName($storage_definition);
      $this->database->update($table_name)
        ->fields(['deleted' => 1])
        ->condition('bundle', $field_definition->getTargetBundle())
        ->execute();
    }
  }

  /**
   * {@inheritdoc}
   *
   * Provide any additional processing of values from CiviCRM API.
   */
  protected function initFieldValues(ContentEntityInterface $entity, array $values = [], array $field_names = []) {
    parent::initFieldValues($entity, $values, $field_names);
    foreach ($entity->getFieldDefinitions() as $definition) {
      $items = $entity->get($definition->getName());
      if ($items->isEmpty()) {
        continue;
      }
      $main_property_name = $definition->getFieldStorageDefinition()->getMainPropertyName();

      // Fix DateTime values for Drupal format.
      if ($definition->getType() == 'datetime') {
        $item_values = $items->getValue();
        foreach ($item_values as $delta => $item) {
          // Handle if the value provided is a timestamp.
          // @note: This only occurred during test migrations.
          if (is_numeric($item[$main_property_name])) {
            $item_values[$delta][$main_property_name] = (new \DateTime())->setTimestamp($item[$main_property_name])->format(DATETIME_DATETIME_STORAGE_FORMAT);
          }
          // Date time formats from CiviCRM do not match the storage
          // format for Drupal's date time fields. Add in missing "T" marker.
          else {
            $item_values[$delta][$main_property_name] = str_replace(' ', 'T', $item[$main_property_name]);
          }
        }
        $items->setValue($item_values);
      }
    }
  }

  /**
   * Gets a table mapping for the entity's field config SQL tables.
   *
   * @return \Drupal\Core\Entity\Sql\TableMappingInterface|\Drupal\Core\Entity\Sql\DefaultTableMapping
   *   A table mapping object for the entity's tables.
   */
  public function getTableMapping() {
    $table_mapping = $this->tableMapping;

    if ($table_mapping) {
      return $table_mapping;
    }

    $table_mapping_class = DefaultTableMapping::class;
    $definitions = $this->entityManager->getFieldStorageDefinitions($this->entityTypeId);
    /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping|\Drupal\Core\Entity\Sql\TemporaryTableMapping $table_mapping */
    $table_mapping = new $table_mapping_class($this->entityType, $definitions);

    // Add dedicated tables.
    $dedicated_table_definitions = array_filter($definitions, function (FieldStorageDefinitionInterface $definition) use ($table_mapping) {
      return $table_mapping->requiresDedicatedTableStorage($definition);
    });
    $extra_columns = [
      'bundle',
      'deleted',
      'entity_id',
      'revision_id',
      'langcode',
      'delta',
    ];
    foreach ($dedicated_table_definitions as $field_name => $definition) {
      $tables = [$table_mapping->getDedicatedDataTableName($definition)];
      foreach ($tables as $table_name) {
        $table_mapping->setFieldNames($table_name, [$field_name]);
        $table_mapping->setExtraColumns($table_name, $extra_columns);
      }
    }
    $this->tableMapping = $table_mapping;
    return $table_mapping;
  }

  /**
   * Loads values of fields stored in dedicated tables for a group of entities.
   *
   * @param array &$values
   *   An array of values keyed by entity ID.
   *   defaults to FALSE.
   *
   * @throws \Drupal\Core\Entity\Sql\SqlContentEntityStorageException
   */
  protected function loadFromDedicatedTables(array &$values) {
    if (empty($values)) {
      return;
    }

    // Collect impacted fields.
    $storage_definitions = [];
    $table_mapping = $this->getTableMapping();

    $definitions = $this->entityManager->getFieldDefinitions($this->entityTypeId, $this->entityTypeId);
    foreach ($definitions as $field_name => $field_definition) {
      $storage_definition = $field_definition->getFieldStorageDefinition();
      if ($table_mapping->requiresDedicatedTableStorage($storage_definition)) {
        $storage_definitions[$field_name] = $storage_definition;
      }
    }

    // Load field data.
    $langcodes = array_keys($this->languageManager->getLanguages(LanguageInterface::STATE_ALL));
    foreach ($storage_definitions as $field_name => $storage_definition) {
      $table = $table_mapping->getDedicatedDataTableName($storage_definition);

      // Ensure that only values having valid languages are retrieved. Since we
      // are loading values for multiple entities, we cannot limit the query to
      // the available translations.
      $results = $this->database->select($table, 't')
        ->fields('t')
        ->condition('entity_id', [$values[$this->getEntityType()->getKey('id')]], 'IN')
        ->condition('deleted', 0)
        ->condition('langcode', $langcodes, 'IN')
        ->orderBy('delta')
        ->execute();

      foreach ($results as $row) {
        if (!isset($values[$field_name])) {
          $values[$field_name] = [];
        }

        if ($storage_definition->getCardinality() == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED || count($values[$field_name]) < $storage_definition->getCardinality()) {
          $item = [];
          // For each column declared by the field, populate the item from the
          // prefixed database column.
          foreach ($storage_definition->getColumns() as $column => $attributes) {
            $column_name = $table_mapping->getFieldColumnName($storage_definition, $column);
            // Unserialize the value if specified in the column schema.
            $item[$column] = (!empty($attributes['serialize'])) ? unserialize($row->$column_name) : $row->$column_name;
          }

          // Add the item to the field values for the entity.
          $values[$field_name][] = $item;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function requiresFieldStorageSchemaChanges(FieldStorageDefinitionInterface $storage_definition, FieldStorageDefinitionInterface $original) {
    return $this->getStorageSchema()->requiresFieldStorageSchemaChanges($storage_definition, $original);
  }

  /**
   * {@inheritdoc}
   */
  public function requiresFieldDataMigration(FieldStorageDefinitionInterface $storage_definition, FieldStorageDefinitionInterface $original) {
    return $this->getStorageSchema()->requiresFieldDataMigration($storage_definition, $original);
  }

  /**
   * {@inheritdoc}
   */
  public function requiresEntityStorageSchemaChanges(EntityTypeInterface $entity_type, EntityTypeInterface $original) {
    // There is no base table.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function requiresEntityDataMigration(EntityTypeInterface $entity_type, EntityTypeInterface $original) {
    // There is no base table.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function finalizePurge(FieldStorageDefinitionInterface $storage_definition) {
    $this->getStorageSchema()->finalizePurge($storage_definition);
  }

  /**
   * Saves values of fields that use dedicated tables.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param bool $update
   *   TRUE if the entity is being updated, FALSE if it is being inserted.
   * @param string[] $names
   *   (optional) The names of the fields to be stored. Defaults to all the
   *   available fields.
   *
   * @throws \Drupal\Core\Entity\Sql\SqlContentEntityStorageException
   */
  protected function saveToDedicatedTables(ContentEntityInterface $entity, $update = TRUE, array $names = []) {
    $vid = $entity->getRevisionId();
    $id = $entity->id();
    $bundle = $entity->bundle();
    $entity_type = $entity->getEntityTypeId();
    $default_langcode = $entity->getUntranslated()->language()->getId();
    $translation_langcodes = array_keys($entity->getTranslationLanguages());
    $table_mapping = $this->getTableMapping();

    if (!isset($vid)) {
      $vid = $id;
    }

    $original = !empty($entity->original) ? $entity->original : NULL;

    // Determine which fields should be actually stored.
    $definitions = $this->entityManager->getFieldDefinitions($entity_type, $bundle);
    if ($names) {
      $definitions = array_intersect_key($definitions, array_flip($names));
    }

    foreach ($definitions as $field_name => $field_definition) {
      $storage_definition = $field_definition->getFieldStorageDefinition();
      if (!$table_mapping->requiresDedicatedTableStorage($storage_definition)) {
        continue;
      }

      // When updating an existing revision, keep the existing records if the
      // field values did not change.
      if (!$entity->isNewRevision() && $original && !$this->hasFieldValueChanged($field_definition, $entity, $original)) {
        continue;
      }

      $table_name = $table_mapping->getDedicatedDataTableName($storage_definition);
      $revision_name = $table_mapping->getDedicatedRevisionTableName($storage_definition);

      // Delete and insert, rather than update, in case a value was added.
      if ($update) {
        // Only overwrite the field's base table if saving the default revision
        // of an entity.
        if ($entity->isDefaultRevision()) {
          $this->database->delete($table_name)
            ->condition('entity_id', $id)
            ->execute();
        }
        if ($this->entityType->isRevisionable()) {
          $this->database->delete($revision_name)
            ->condition('entity_id', $id)
            ->condition('revision_id', $vid)
            ->execute();
        }
      }

      // Prepare the multi-insert query.
      $do_insert = FALSE;
      $columns = ['entity_id', 'revision_id', 'bundle', 'delta', 'langcode'];
      foreach ($storage_definition->getColumns() as $column => $attributes) {
        $columns[] = $table_mapping->getFieldColumnName($storage_definition, $column);
      }
      $query = $this->database->insert($table_name)->fields($columns);
      if ($this->entityType->isRevisionable()) {
        $revision_query = $this->database->insert($revision_name)->fields($columns);
      }

      $langcodes = $field_definition->isTranslatable() ? $translation_langcodes : [$default_langcode];
      foreach ($langcodes as $langcode) {
        $delta_count = 0;
        $items = $entity->getTranslation($langcode)->get($field_name);
        $items->filterEmptyItems();
        foreach ($items as $delta => $item) {
          // We now know we have something to insert.
          $do_insert = TRUE;
          $record = [
            'entity_id' => $id,
            'revision_id' => $vid,
            'bundle' => $bundle,
            'delta' => $delta,
            'langcode' => $langcode,
          ];
          foreach ($storage_definition->getColumns() as $column => $attributes) {
            $column_name = $table_mapping->getFieldColumnName($storage_definition, $column);
            // Serialize the value if specified in the column schema.
            $value = $item->$column;
            if (!empty($attributes['serialize'])) {
              $value = serialize($value);
            }
            $record[$column_name] = drupal_schema_get_field_value($attributes, $value);
          }
          $query->values($record);
          if ($this->entityType->isRevisionable()) {
            $revision_query->values($record);
          }

          if ($storage_definition->getCardinality() != FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED && ++$delta_count == $storage_definition->getCardinality()) {
            break;
          }
        }
      }

      // Execute the query if we have values to insert.
      if ($do_insert) {
        // Only overwrite the field's base table if saving the default revision
        // of an entity.
        if ($entity->isDefaultRevision()) {
          $query->execute();
        }
        if ($this->entityType->isRevisionable()) {
          $revision_query->execute();
        }
      }
    }
  }

}
