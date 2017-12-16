<?php

namespace Drupal\civicrm_entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

class CivicrmEntityListBuilder extends EntityListBuilder {

  public function buildHeader() {
    return [
      'id' => $this->t('ID'),
      'label' => $this->t('Label'),
    ] + parent::buildHeader();
  }

  public function buildRow(EntityInterface $entity) {
    return [
      'id' => $entity->id(),
      'label' => $entity->label(),
    ] + parent::buildRow($entity);
  }


}
