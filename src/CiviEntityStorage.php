<?php
// In construct make sure to invoke initialize
namespace Drupal\civicrm_entity;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityStorageBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\TypedData\Plugin\DataType\DateTimeIso8601;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines entity class for external CiviCRM entities.
 */
class CiviEntityStorage extends ContentEntityStorageBase {

  /**
   * @var \Drupal\civicrm_entity\CiviCrmApi
   */
  protected $civicrmApi;

  public function __construct(EntityTypeInterface $entity_type, EntityManagerInterface $entity_manager, CacheBackendInterface $cache, CiviCrmApi $civicrm_api) {
    parent::__construct($entity_type, $entity_manager, $cache);
    $this->civicrmApi = $civicrm_api;
  }

  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager'),
      $container->get('cache.entity'),
      $container->get('civicrm_entity.api')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function doDelete($entities) {
    /** @var EntityInterface $entity */
    foreach ($entities as $entity) {
      try {
        $params['id'] = $entity->id();
        $this->civicrmApi->delete($this->entityType->get('civicrm_entity'), $params);
      }
      catch (\Exception $e) {
        throw $e;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doSave($id, EntityInterface $entity) {
    /** @var \Drupal\civicrm_entity\Entity\CivicrmEntity $entity */
    $return = $entity->isNew() ? SAVED_NEW : SAVED_UPDATED;

    $params = [];
    /** @var \Drupal\Core\Field\FieldItemListInterface $items */
    foreach ($entity->getFields() as $field_name => $items) {
      $items->filterEmptyItems();
      if ($items->isEmpty()) {
        continue;
      }

      $storage_definition = $items->getFieldDefinition()->getFieldStorageDefinition();
      $main_property_name = $storage_definition->getMainPropertyName();
      $list = [];
      /** @var \Drupal\Core\Field\FieldItemInterface $item */
      foreach ($items as $delta => $item) {
        $main_property = $item->get($main_property_name);
        if ($main_property instanceof DateTimeIso8601) {
          $value = $main_property->getDateTime()->format(DATETIME_DATETIME_STORAGE_FORMAT);
        }
        else {
          $value = $main_property->getValue();
        }
        $list[$delta] = $value;
      }

      // Remove the wrapping array if the field is single-valued.
      if ($storage_definition->getCardinality() === 1) {
        $list = reset($list);
      }
      if (!empty($list)) {
        $params[$field_name] = $list;
      }
    }

    $result = $this->civicrmApi->save($this->entityType->get('civicrm_entity'), $params);

    if ($entity->isNew()) {
      $entity->{$this->idKey} = (string) $result['id'];
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  protected function doLoadMultiple(array $ids = NULL) {
    $entities = [];

    if ($ids === NULL) {
      $civicrm_entities = $this->civicrmApi->get($this->entityType->get('civicrm_entity'));
      foreach ($civicrm_entities as $civicrm_entity) {
        $civicrm_entity = reset($civicrm_entity);
        /** @var \Drupal\civicrm_entity\Entity\Events $entity */
        $entity = $this->create($civicrm_entity);
        $entities[$entity->id()] = $entity;
      }
    }
    foreach ($ids as $id) {
      $civicrm_entity = $this->civicrmApi->get($this->entityType->get('civicrm_entity'), ['id' => $id]);
      $civicrm_entity = reset($civicrm_entity);
      /** @var \Drupal\civicrm_entity\Entity\Events $entity */
      $entity = $this->create($civicrm_entity);
      // We have to build entities through values using `create`, however it
      // enforces the entity as new. We must undo that.
      $entity->enforceIsNew(FALSE);
      $entities[$entity->id()] = $entity;
    }
    return $entities;
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
   */
  public function countFieldData($storage_definition, $as_bool = FALSE) {
    return $as_bool ? 0 : FALSE;
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
  }

  /**
   * {@inheritdoc}
   */
  protected function purgeFieldItems(ContentEntityInterface $entity, FieldDefinitionInterface $field_definition) {
  }

  /**
   * {@inheritdoc}
   */
  protected function doLoadRevisionFieldItems($revision_id) {
  }

  /**
   * {@inheritdoc}
   */
  protected function doSaveFieldItems(ContentEntityInterface $entity, array $names = []) {
  }

  /**
   * {@inheritdoc}
   */
  protected function doDeleteFieldItems($entities) {
  }

  /**
   * {@inheritdoc}
   */
  protected function doDeleteRevisionFieldItems(ContentEntityInterface $revision) {
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
      $main_property_name = $definition->getMainPropertyName();

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


}
