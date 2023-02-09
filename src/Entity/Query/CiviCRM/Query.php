<?php

namespace Drupal\civicrm_entity\Entity\Query\CiviCRM;

use Drupal\civicrm_entity\CiviCrmApi;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryBase;
use Drupal\Core\Entity\Query\QueryInterface;

/**
 * The CiviCRM entity query class.
 */
class Query extends QueryBase implements QueryInterface {

  /**
   * The CiviCRM API service.
   *
   * @var \Drupal\civicrm_entity\CiviCrmApi
   */
  protected $civicrmApi;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type, $conjunction, array $namespaces, CiviCrmApi $civicrm_api) {
    parent::__construct($entity_type, $conjunction, $namespaces);
    $this->civicrmApi = $civicrm_api;
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $params = [];
    foreach ($this->condition->conditions() as $condition) {
      // If there's anything requiring a custom field,
      // set condition which cannot be completed.
      // @todo Introduced when supporting field config. Find something better.
      // @see \Drupal\field_ui\Form\FieldStorageConfigEditForm::validateCardinality()
      if (substr($condition['field'], 0, 6) === 'field_') {
        $params['id'] = '-1';
        break;
      }
      $operator = $condition['operator'] ?: '=';
      if ($operator == 'CONTAINS') {
        $params[$condition['field']] = ['LIKE' => '%' . $condition['value'] . '%'];
      }
      elseif ($operator != '=') {
        $params[$condition['field']] = [$operator => $condition['value']];
      }
      else {
        $params[$condition['field']] = $condition['value'];
      }
    }

    $sort = [];
    foreach ($this->sort as $s) {
      $sort[] = $s['field'] . ' ' . $s['direction'];
    }

    $params['options']['sort'] = implode(',', $sort);

    $this->initializePager();
    if ($this->range) {
      $params['options']['limit'] = $this->range['length'];
      $params['options']['offset'] = $this->range['start'];
    }

    if ($this->count) {
      unset($params['options']['sort']);
      return $this->civicrmApi->getCount($this->entityType->get('civicrm_entity'), $params);
    }
    else {
      $result = $this->civicrmApi->get($this->entityType->get('civicrm_entity'), $params);
      return array_keys($result);
    }
  }

}
