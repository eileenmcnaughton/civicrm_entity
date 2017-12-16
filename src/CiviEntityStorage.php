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
    /** @var \Drupal\Core\Entity\EntityInterface$entity */
    foreach ($entities as $entity) {
      try {
        $params['id'] = $entity->id();
        $result = civicrm_api3(substr($this->entityTypeId, 8), 'delete', $params);
        if (!civicrm_error($result)) {
          // @todo anything more to do?
          // @todo throw an event?
        }
      }
      catch (\Exception $e) {
        throw $e;
      }
    }
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
    // @todo create a query service based off of API3
    return NULL;
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
