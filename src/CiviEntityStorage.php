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
    if ($entity->isNew()) {
      // @todo decide on special handling?
      $return = SAVED_NEW;
    }
    else {
      $return = SAVED_UPDATED;
    }

    // @todo ->toArray will provide ['is_new' => ['value' => TRUE]]
    $params = $entity->toArray();
    $params = array_map(function ($value) {
      if (empty($value)) {
        return NULL;
      }
      else {
        if (is_array($value)) {
          $value = reset($value);
          if (is_array($value)) {
            return reset($value);
          }
        }
        return $value;
      }
    }, $params);
    $this->civicrmApi->save($this->entityType->get('civicrm_entity'), $params);

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


}
