<?php

namespace Drupal\civicrm_entity\Entity\Query\CiviCRM;

use Drupal\civicrm_entity\CiviCrmApi;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryBase;
use Drupal\Core\Entity\Query\QueryException;
use Drupal\Core\Entity\Query\QueryInterface;

class Query extends QueryBase implements QueryInterface {

  protected $civicrmApi;

  public function __construct(EntityTypeInterface $entity_type, $conjunction, array $namespaces, CiviCrmApi $civicrm_api) {
    parent::__construct($entity_type, $conjunction, $namespaces);
    $this->civicrmApi = $civicrm_api;
  }

  public function execute() {
    $result = $this->civicrmApi->get($this->entityType->get('civicrm_entity'));
    return array_keys($result);
  }

}
