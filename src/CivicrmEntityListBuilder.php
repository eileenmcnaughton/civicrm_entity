<?php

namespace Drupal\civicrm_entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;

class CivicrmEntityListBuilder extends EntityListBuilder {

  protected $limit = 25;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    if ($this->entityType->hasKey('bundle')) {
      return [
          'id' => $this->t('ID'),
          'bundle' => $this->entityType->getBundleLabel(),
          'label' => $this->t('Label'),
        ] + parent::buildHeader();
    }
    return [
      'id' => $this->t('ID'),
      'label' => $this->t('Label'),
    ] + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    if ($this->entityType->hasKey('bundle')) {
      return [
          'id' => $entity->id(),
          'bundle' => $entity->bundle(),
          'label' => $entity->toLink(),
        ] + parent::buildRow($entity);
    }
    return [
      'id' => $entity->id(),
      'label' => $entity->toLink(),
    ] + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    $operations['view'] = [
      'title' => $this->t('View'),
      'weight' => 50,
      'url' => $entity->toUrl(),
    ];

    return $operations;
  }

}
