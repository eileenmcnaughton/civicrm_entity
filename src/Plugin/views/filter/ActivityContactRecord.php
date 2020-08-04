<?php

namespace Drupal\civicrm_entity\Plugin\views\filter;

use Drupal\Core\Database\Query\Condition;
use Drupal\views\Plugin\views\filter\NumericFilter;
use Drupal\views\Views;

/**
 * Filter handler for activity source contact.
 *
 * @ViewsFilter("civicrm_entity_civicrm_activity_contact_record")
 */
class ActivityContactRecord extends NumericFilter {

  /**
   * The mapping explicitly set for the record types.
   *
   * @var array
   *
   * The actual API call is:
   *
   * @code
   * $result = civicrm_api3('OptionValue', 'get', [
   *   'sequential' => 1,
   *   'option_group_id' => 'activity_contacts',
   * ]);
   * @endcode
   */
  private $recordTypeMapping = [
    'assignee_id' => 1,
    'source_contact_id' => 2,
    'target_id' => 3,
  ];

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();

    $civicrm_activity_contact_table = 'civicrm_activity_contact';

    $configuration = [
      'table' => $civicrm_activity_contact_table,
      'field' => 'activity_id',
      'left_table' => $this->tableAlias,
      'left_field' => 'id',
      'operator' => '=',
    ];

    $join = Views::pluginManager('join')->createInstance('standard', $configuration);

    $civicrm_activity_contact_table_alias = $this->query->addRelationship($civicrm_activity_contact_table, $join, $this->tableAlias);

    $field = "$civicrm_activity_contact_table_alias.contact_id";
    $info = $this->operators();

    if (!empty($info[$this->operator]['method']) && $this->recordTypeMapping[$this->realField]) {
      $condition = new Condition('AND');
      $condition->condition($field, $this->value['value'], $this->operator);
      $condition->condition("$civicrm_activity_contact_table_alias.record_type_id", $this->recordTypeMapping[$this->realField]);

      $this->query->addWhere($this->options['group'], $condition);
    }
  }

}
