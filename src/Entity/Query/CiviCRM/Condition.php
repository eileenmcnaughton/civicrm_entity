<?php

namespace Drupal\civicrm_entity\Entity\Query\CiviCRM;

use Drupal\Core\Entity\Query\ConditionBase;
use Drupal\Core\Entity\Query\ConditionInterface;

/**
 * Implements entity query conditions for CiviCRM.
 */
class Condition extends ConditionBase {

  /**
   * The CiviCRM entity query object this condition belongs to.
   *
   * @var \Drupal\civicrm_entity\Entity\Query\CiviCRM\Query
   */
  protected $query;

  /**
   * {@inheritdoc}
   */
  public function compile($condition_container) {
    // If this is not the top level condition group then the sql query is
    // added to the $conditionContainer object by this function itself. The
    // SQL query object is only necessary to pass to Query::addField() so it
    // can join tables as necessary. On the other hand, conditions need to be
    // added to the $conditionContainer object to keep grouping.
    foreach ($this->conditions as $condition) {
      if ($condition['field'] instanceof ConditionInterface) {
        $query_condition = new static('AND', $this->query);
        $query_condition->compile($condition_container);
      }
      else {
        $condition_container->setParameter($condition['field'], $condition['value']);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function exists($field, $langcode = NULL) {
    return $this->condition($field, NULL, 'IS NOT NULL', $langcode);
  }

  /**
   * {@inheritdoc}
   */
  public function notExists($field, $langcode = NULL) {
    return $this->condition($field, NULL, 'IS NULL', $langcode);
  }

}
