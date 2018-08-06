<?php

namespace Drupal\civicrm_entity\Entity\Query\CiviCRM;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryBase;
use Drupal\Core\Entity\Query\QueryException;
use Drupal\Core\Entity\Query\QueryFactoryInterface;
use Drupal\civicrm_entity\CiviCrmApiInterface;

/**
 * Factory class creating entity query objects in CiviCRM.
 *
 * @see \Drupal\civicrm_entity\Entity\Query\CiviCRM\Query
 */
class QueryFactory implements QueryFactoryInterface {

  /**
   * The namespace of this class, the parent class etc.
   *
   * @var array
   */
  protected $namespaces;

  /**
   * The CiviCRM API service.
   *
   * @var \Drupal\civicrm_entity\CiviCrmApiInterface
   */
  protected $civicrmApi;

  /**
   * Constructs a QueryFactory object.
   *
   * @param \Drupal\civicrm_entity\CiviCrmApiInterface $civicrm_api
   *   The CiviCRM API bridge.
   */
  public function __construct(CiviCrmApiInterface $civicrm_api) {
    $this->namespaces = QueryBase::getNamespaces($this);
    $this->civicrmApi = $civicrm_api;
  }

  /**
   * {@inheritdoc}
   */
  public function get(EntityTypeInterface $entity_type, $conjunction) {
    // @todo copy paste, evaluate if they do.
    if ($conjunction == 'OR') {
      throw new QueryException("CiviCRM entity queries do not support OR conditions.");
    }
    $class = QueryBase::getClass($this->namespaces, 'Query');
    return new $class($entity_type, $conjunction, $this->namespaces, $this->civicrmApi);
  }

  /**
   * {@inheritdoc}
   */
  public function getAggregate(EntityTypeInterface $entity_type, $conjunction) {
    // @todo copy paste, evaluate if they do.
    throw new QueryException("CiviCRM entity queries do not support aggregate queries.");
  }

}
