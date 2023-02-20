<?php

namespace Drupal\civicrm_entity\Plugin\views\relationship;

use Drupal\views\Plugin\views\relationship\EntityReverse as CoreEntityReverse;

/**
 * A relationship handlers which reverse CiviCRM entity references.
 *
 * @ingroup views_relationship_handlers
 *
 * @ViewsRelationship("civicrm_entity_reverse")
 */
class EntityReverse extends CoreEntityReverse {

  /**
   * Called to implement a relationship in a query.
   */
  public function query() {
    $this->ensureMyTable();

    $join = [
      'left_table' => $this->tableAlias,
      'left_field' => $this->definition['base field'],
      'table' => $this->definition['base'],
      'field' => $this->definition['field_name'],
      'adjusted' => TRUE,
    ];

    if (!empty($this->options['required'])) {
      $join['type'] = 'INNER';
    }

    if (!empty($this->definition['extra'])) {
      $join['extra'] = $this->definition['extra'];
    }

    if (!empty($this->definition['join_id'])) {
      $id = $this->definition['join_id'];
    }
    else {
      $id = 'standard';
    }
    $join_instance = $this->joinManager->createInstance($id, $join);
    $join_instance->adjusted = TRUE;

    $alias = $this->definition['field_name'] . '_' . $this->table;
    $this->alias = $this->query->addRelationship($alias, $join_instance, $this->definition['base'], $this->relationship);
  }

}
