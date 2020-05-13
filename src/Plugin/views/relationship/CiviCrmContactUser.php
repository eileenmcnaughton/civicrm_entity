<?php

namespace Drupal\civicrm_entity\Plugin\views\relationship;

use Drupal\views\Plugin\views\relationship\RelationshipPluginBase;
use Drupal\views\Views;

/**
 * Relationship for referencing civicrm_contact and user.
 *
 * Additional definition items:
 * - base: The new table to relate to.
 * - base field: The field to use in the relationship.
 * - first field: The field in the 'civicrm_uf_match' table to match against the
 *   original table.
 * - second field: The field in the 'civicrm_uf_match' table to match against
 *   the base table.
 *
 * @ingroup views_relationship_handlers
 *
 * @ViewsRelationship("civicrm_entity_civicrm_contact_user")
 */
class CiviCrmContactUser extends RelationshipPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();

    $views_data = Views::viewsData()->get($this->table);
    $left_field = $views_data['table']['base']['field'];

    // Add a join to the civicrm_uf_match table with the matching contact_id.
    $first = [
      'left_table' => $this->tableAlias,
      'left_field' => $left_field,
      'table' => 'civicrm_uf_match',
      'field' => $this->definition['first field'],
      'adjusted' => TRUE,
    ];

    if (!empty($this->options['required'])) {
      $first['type'] = 'INNER';
    }

    $first_join = Views::pluginManager('join')->createInstance('standard', $first);
    $first_alias = $this->query->addTable('civicrm_uf_match', $this->relationship, $first_join);

    // Relate the first join to the base table defined.
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

    $alias = $this->definition['base'] . '_civicrm_uf_match';

    $this->alias = $this->query->addRelationship($alias, $second_join, $this->definition['base'], $this->relationship);
  }

}
