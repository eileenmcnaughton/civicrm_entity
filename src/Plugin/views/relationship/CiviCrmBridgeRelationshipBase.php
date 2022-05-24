<?php

namespace Drupal\civicrm_entity\Plugin\views\relationship;

use Drupal\views\Plugin\views\relationship\RelationshipPluginBase;
use Drupal\views\Views;

/**
 * Relationship for referencing two CiviCRM entities using a "bridge" table.
 *
 * Additional definition items:
 * - base: The new table to relate to.
 * - base field: The field to use in the relationship.
 * - table: The table to use for joining.
 * - first field: The field in the 'civicrm_uf_match' table to match against the
 *   original table.
 * - second field: The field in the 'civicrm_uf_match' table to match against
 *   the base table.
 *
 * @ingroup views_relationship_handlers
 *
 * @ViewsRelationship("civicrm_entity_civicrm_bridge")
 */
class CiviCrmBridgeRelationshipBase extends RelationshipPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();

    $views_data = Views::viewsData()->get($this->table);
    $left_field = $views_data['table']['base']['field'];

    $first = [
      'left_table' => $this->tableAlias,
      'left_field' => $left_field,
      'table' => $this->definition['table'],
      'field' => $this->definition['first field'],
      'adjusted' => TRUE,
    ];

    $first['extra'] = $this->getExtras();

    if (!empty($this->options['required'])) {
      $first['type'] = 'INNER';
    }

    $first_join = Views::pluginManager('join')->createInstance('standard', $first);
    $first_alias = $this->query->addTable($this->definition['table'], $this->relationship, $first_join);

    $second = [
      'left_table' => $first_alias,
      'left_field' => $this->definition['second field'],
      'table' => $this->definition['base'],
      'field' => $this->definition['base field'],
      'adjusted' => TRUE,
    ];

    if (!empty($this->options['required'])) {
      $second['type'] = 'INNER';
    }

    $second_join = Views::pluginManager('join')->createInstance('standard', $second);
    $second_join->adjusted = TRUE;

    $alias = $this->definition['base'] . '_' . $this->definition['table'];

    $this->alias = $this->query->addRelationship($alias, $second_join, $this->definition['base'], $this->relationship);
  }

  /**
   * Gets extra conditions.
   */
  protected function getExtras() {
    return $this->definition['extra'] ?? NULL;
  }

}
