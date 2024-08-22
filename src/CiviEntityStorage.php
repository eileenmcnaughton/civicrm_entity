<?php

namespace Drupal\civicrm_entity;

use Drupal\civicrm_entity\Entity\CivicrmEntity;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Sql\DefaultTableMapping;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Utility\Error;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\field\FieldStorageConfigInterface;
use Psr\Log\LoggerInterface;

/**
 * Defines entity class for external CiviCRM entities.
 */
class CiviEntityStorage extends SqlContentEntityStorage {

  /**
   * The CiviCRM API.
   *
   * @var \Drupal\civicrm_entity\CiviCrmApi
   */
  protected $civicrmApi;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * The Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Gets the CiviCRM API.
   *
   * @return \Drupal\civicrm_entity\CiviCrmApiInterface
   *   The CiviCRM APi.
   */
  private function getCiviCrmApi() {
    if (!$this->civicrmApi) {
      $this->civicrmApi = \Drupal::service('civicrm_entity.api');
    }
    return $this->civicrmApi;
  }

  /**
   * Gets the config factory.
   *
   * @return \Drupal\Core\Config\ConfigFactoryInterface
   *   The configuration factory service.
   */
  private function getConfigFactory() {
    if (!$this->configFactory) {
      $this->configFactory = \Drupal::configFactory();
    }
    return $this->configFactory;
  }

    /**
   * Gets the config factory.
   *
   * @return \Psr\Log\LoggerInterface
   *   The logger channel.
   */
  private function getLogger() {
    if (!$this->logger) {
      $this->logger = \Drupal::logger('civicrm_entity');
    }
    return $this->logger;
  }

  /**
   * Initializes table name variables.
   */
  protected function initTableLayout() {
    $this->tableMapping = NULL;
    $this->revisionKey = NULL;
    $this->revisionTable = NULL;
    $this->dataTable = NULL;
    $this->revisionDataTable = NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function doDelete($entities) {
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    foreach ($entities as $entity) {
      try {
        $params['id'] = $entity->id();
        $this->getCiviCrmApi()->delete($this->entityType->get('civicrm_entity'), $params);
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
    /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
    $table_mapping = $this->getTableMapping();

    foreach ($entities as $entity) {
      foreach ($this->entityFieldManager->getFieldDefinitions($entity->getEntityTypeId(), $entity->bundle()) as $field_definition) {
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

    $result = $this->getCiviCrmApi()->save($this->entityType->get('civicrm_entity'), $params);
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
      $civicrm_entity = $this->getCiviCrmApi()->get($this->entityType->get('civicrm_entity'));
      $civicrm_entity = reset($civicrm_entity);
      $entity = $this->prepareLoadedEntity($civicrm_entity);
      $entities[$entity->id()] = $entity;
    }

    // Get all the fields.
    $fields = $this->getCiviCrmApi()->getFields($this->entityType->get('civicrm_entity'));

    $ids = $this->cleanIds($ids);

    if (!empty($ids)) {
      $options = [
        'id' => ['IN' => $ids],
        'return' => array_keys($fields),
        'options' => ['limit' => 0],
      ];

      if ($this->entityType->get('civicrm_entity') === 'participant') {
        unset($options['return']);
      }

      try {
        $civicrm_entities = $this->getCiviCrmApi()->get($this->entityType->get('civicrm_entity'), $options);

        foreach ($civicrm_entities as $civicrm_entity) {
          if ($this->entityType->get('civicrm_entity') === 'participant') {
            // Massage the values.
            $temporary = [];
            foreach ($civicrm_entity as $key => $value) {
              if (strpos($key, 'participant_') === 0) {
                $temporary[str_replace('participant_', '', $key)] = $value;
              }
              else {
                $temporary[$key] = $value;
              }
            }

            $civicrm_entity = $temporary;
          }

          $massaged_civicrm_entity = [];
          foreach ($fields as $name => $field) {
            if (isset($civicrm_entity[$name])) {
              $massaged_civicrm_entity[$field['name']] = $civicrm_entity[$name];
            }
          }

          $entity = $this->prepareLoadedEntity($civicrm_entity + $massaged_civicrm_entity);
          $entities[$entity->id()] = $entity;
        }
      }
      catch (\Exception $e) {
        Error::logException($this->getLogger(), $e);
      }
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
    $bundle = FALSE;
    if ($this->bundleKey) {
      $bundle_property = $this->entityType->get('civicrm_bundle_property');
      if (!isset($civicrm_entity[$bundle_property])) {
        throw new EntityStorageException('Missing bundle for entity type ' . $this->entityTypeId);
      }
      $bundle_value = $civicrm_entity[$bundle_property];
      $options = $this->civicrmApi->getOptions($this->entityType->get('civicrm_entity'), $bundle_property);
      $bundle = $options[$bundle_value];

      $transliteration = \Drupal::transliteration();
      $bundle = SupportedEntities::optionToMachineName($bundle, $transliteration);
    }
    $entity_class = $this->getEntityClass();
    $entity = new $entity_class([], $this->entityTypeId, $bundle);
    // Use initFieldValues to fix CiviCRM data array to Drupal.
    $this->initFieldValues($entity, $civicrm_entity);
    return $entity;
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
    /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
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
    if (($component = $this->entityType->get('component')) !== NULL) {
      $components = $this->getCiviCrmApi()->getValue('Setting', ['name' => 'enable_components']);
      return !in_array($component, $components) ? FALSE :
        $this->getCiviCrmApi()->getCount($this->entityType->get('civicrm_entity')) > 0;
    }
    else {
      $enabled_components_as_entity = $this->getConfigFactory()->get('civicrm_entity.settings')->get('enabled_entity_types');
      $component = $this->entityType->get('civicrm_entity');
      return !in_array($component, $enabled_components_as_entity) ? FALSE :
        $this->getCiviCrmApi()->getCount($this->entityType->get('civicrm_entity')) > 0;
    }
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
    /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
    $table_mapping = $this->getTableMapping();
    $storage_definitions = $this->entityFieldManager->getFieldStorageDefinitions($this->entityTypeId);
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
  public function onFieldStorageDefinitionCreate(FieldStorageDefinitionInterface $storage_definition) {
    $this->wrapSchemaException(function () use ($storage_definition) {
      $this->getStorageSchema()->onFieldStorageDefinitionCreate($storage_definition);
    });
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldStorageDefinitionDelete(FieldStorageDefinitionInterface $storage_definition) {
    /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
    $table_mapping = $this->getTableMapping(
      $this->entityFieldManager->getActiveFieldStorageDefinitions($this->entityType->id())
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
   * {@inheritdoc}
   *
   * Provide any additional processing of values from CiviCRM API.
   */
  protected function initFieldValues(ContentEntityInterface $entity, array $values = [], array $field_names = []) {
    parent::initFieldValues($entity, $values, $field_names);
    $civicrm_entity_settings = $this->getConfigFactory()->get('civicrm_entity.settings');
    $field_definitions = $entity->getFieldDefinitions();
    foreach ($field_definitions as $definition) {
      if ($definition->getType() == 'metatag_computed') {
        continue;
      }

      $items = $entity->get($definition->getName());
      if ($items->isEmpty()) {
        continue;
      }
      $main_property_name = $definition->getFieldStorageDefinition()->getMainPropertyName();

      // Set a default format for text fields.
      if ($definition->getType() === 'text_long') {
        $filter_format = $civicrm_entity_settings->get('filter_format') ?: filter_fallback_format();

        $item_values = $items->getValue();
        foreach ($item_values as $delta => $item) {
          $item_values[$delta]['format'] = $filter_format;
        }
        $items->setValue($item_values);
      }
      // Fix DateTime values for Drupal format.
      elseif ($definition->getType() === 'datetime') {
        $item_values = $items->getValue();
        foreach ($item_values as $delta => $item) {
          // On Contribution entities, there are dates sometimes set to the
          // string value of 'null'.
          if ($item[$main_property_name] === 'null') {
            $item_values[$delta][$main_property_name] = NULL;
          }
          // Handle if the value provided is a timestamp.
          // @note: This only occurred during test migrations.
          elseif (is_numeric($item[$main_property_name])) {
            $item_values[$delta][$main_property_name] = (new \DateTime())->setTimestamp($item[$main_property_name])->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
          }
          else {
            $datetime_format = $definition->getSetting('datetime_type') === DateTimeItem::DATETIME_TYPE_DATE ? DateTimeItemInterface::DATE_STORAGE_FORMAT : DateTimeItemInterface::DATETIME_STORAGE_FORMAT;
            $default_timezone = $definition->getSetting('datetime_type') === DateTimeItem::DATETIME_TYPE_DATE ? DateTimeItemInterface::STORAGE_TIMEZONE : \Drupal::config('system.date')->get('timezone.default') ?? date_default_timezone_get();
            $datetime_value = (new \DateTime($item[$main_property_name], new \DateTimeZone($default_timezone)))->setTimezone(new \DateTimeZone('UTC'))->format($datetime_format);
            $item_values[$delta][$main_property_name] = $datetime_value;
          }
        }
        $items->setValue($item_values);
      }
    }

    // Handle special cases for field definitions.
    foreach ($field_definitions as $definition) {
      if (($field_metadata = $definition->getSetting('civicrm_entity_field_metadata')) && isset($field_metadata['custom_group_id']) && in_array($field_metadata['data_type'],['Float', 'Money'])) {
        $items = $entity->get($definition->getName());
        $item_values = $items->getValue();
        if (!empty($item_values)) {
          $ret = [];
          foreach ($item_values as $value) {
            $v = \CRM_Utils_Rule::cleanMoney($value['value']);
            $ret[] = ['value' => $v];
          }
          if (!empty($ret)) {
            $items->setValue($ret);
          }
        }
      }
      elseif (($field_metadata = $definition->getSetting('civicrm_entity_field_metadata')) && isset($field_metadata['custom_group_id']) && $field_metadata['data_type'] === 'File') {
        $items = $entity->get($definition->getName());
        $item_values = $items->getValue();

        if (!empty($item_values)) {
          $ret = [];
          foreach ($item_values as $value) {
            if (!isset($value['fid'])) {
              continue;
            }

            $ret[] = ['value' => $value['fid']];
          }

          if (!empty($ret)) {
            $items->setValue($ret);
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTableMapping(array $storage_definitions = NULL) {
    $table_mapping = $this->tableMapping;

    if ($table_mapping) {
      return $table_mapping;
    }

    $table_mapping_class = DefaultTableMapping::class;
    $definitions = $this->entityFieldManager->getFieldStorageDefinitions($this->entityTypeId);
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
   * @param bool $load_from_revision
   *   Boolean to load from revision.
   *
   * @throws \Drupal\Core\Entity\Sql\SqlContentEntityStorageException
   */
  protected function loadFromDedicatedTables(array &$values, $load_from_revision = FALSE) {
    if (empty($values)) {
      return;
    }

    // Collect impacted fields.
    $storage_definitions = [];
    /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
    $table_mapping = $this->getTableMapping();

    $definitions = $this->entityFieldManager->getFieldDefinitions($this->entityTypeId, $this->entityTypeId);
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
  public function requiresEntityStorageSchemaChanges(EntityTypeInterface $entity_type, EntityTypeInterface $original) {
    // The entity base table is managed by CiviCRM.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function requiresEntityDataMigration(EntityTypeInterface $entity_type, EntityTypeInterface $original) {
    // The entity base table is managed by CiviCRM.
    return FALSE;
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
  protected function saveToDedicatedTables(ContentEntityInterface $entity, $update = TRUE, $names = []) {
    $vid = $entity->getRevisionId();
    $id = $entity->id();
    $bundle = $entity->bundle();
    $entity_type = $entity->getEntityTypeId();
    $default_langcode = $entity->getUntranslated()->language()->getId();
    $translation_langcodes = array_keys($entity->getTranslationLanguages());
    /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
    $table_mapping = $this->getTableMapping();

    if (!isset($vid)) {
      $vid = $id;
    }

    $original = !empty($entity->original) ? $entity->original : NULL;

    // Determine which fields should be actually stored.
    $definitions = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle);
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
            $record[$column_name] = SqlContentEntityStorageSchema::castValue($attributes, $value);
          }
          $query->values($record);
          if ($this->entityType->isRevisionable() && isset($revision_query)) {
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
        if ($this->entityType->isRevisionable() && isset($revision_query)) {
          $revision_query->execute();
        }
      }
    }
  }

  /**
   * Allows CiviCRM hook to invoke presave.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to save.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   If the entity identifier is invalid.
   *
   * @see \Drupal\Core\Entity\ContentEntityStorageBase::doPreSave
   */
  public function civiPreSave(EntityInterface $entity) {
    if (!empty($entity->drupal_crud)) {
      return;
    }
    $this->doPreSave($entity);
  }

  /**
   * Allows CiviCRM hook to invoke postsave.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The saved entity.
   * @param bool $update
   *   Specifies whether the entity is being updated or created.
   *
   * @see \Drupal\Core\Entity\ContentEntityStorageBase::doPostSave
   */
  public function civiPostSave(EntityInterface $entity, $update) {
    if (!empty($entity->drupal_crud)) {
      return;
    }
    $this->doPostSave($entity, $update);
  }

  /**
   * Allows CiviCRM hook to invoke predelete.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to be deleted.
   *
   * @see \Drupal\Core\Entity\EntityStorageInterface::delete
   */
  public function civiPreDelete(EntityInterface $entity) {
    if (!empty($entity->drupal_crud)) {
      return;
    }
    CivicrmEntity::preDelete($this, [$entity]);
    $this->invokeHook('predelete', $entity);
  }

  /**
   * Allows CiviCRM hook to invoke delete.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity deleted.
   *
   * @see \Drupal\Core\Entity\EntityStorageInterface::delete
   */
  public function civiPostDelete(EntityInterface $entity) {
    if (!empty($entity->drupal_crud)) {
      return;
    }
    $this->doDeleteFieldItems([$entity]);
    $this->resetCache([$entity->id()]);
    CivicrmEntity::postDelete($this, [$entity]);
    $this->invokeHook('delete', $entity);
  }

  /**
   * Loads the EntityTag ID.
   *
   * When saving EntityTag objects, the 'id' that's passed to CiviCRM hooks is
   * not the ID of the EntityTag, but rather the object to which the EntityTag
   * applies. This provides the lookup to determing the ID of the EntityTag
   * object itself.
   *
   * @param int $entityId
   *   The entity ID.
   * @param string $entityTable
   *   The entity table.
   *
   * @return int|null
   *   The EntityTag object's ID, or NULL if not found.
   */
  public function getEntityTagEntityId($entityId, $entityTable) {
    $api_params = [
      'sequential' => 1,
      'entity_id' => $entityId,
      'entity_table' => $entityTable,
    ];
    $api_results = civicrm_api3('EntityTag', 'get', $api_params);
    if (!empty($api_results['values'])) {
      foreach ($api_results['values'] as $delta => $result) {
        if ($result['entity_id'] == $entityId) {
          return $result['id'];
        }
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityTypeDelete(EntityTypeInterface $entity_type) {
    // Don't do anything.
  }

}
