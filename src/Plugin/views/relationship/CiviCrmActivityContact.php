<?php

namespace Drupal\civicrm_entity\Plugin\views\relationship;

use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\relationship\RelationshipPluginBase;
use Drupal\views\Views;

/**
 * Reverse CiviCRM entity reference locations.
 *
 * @ingroup views_relationship_handlers
 *
 * @ViewsRelationship("civicrm_entity_activity_contact")
 */
class CiviCrmActivityContact extends RelationshipPluginBase {

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
  protected $recordTypeMapping = [
    1 => 'Assignee',
    2 => 'Source',
    3 => 'Target',
  ];

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['record_type_id'] = ['default' => ''];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['record_type_id'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#options' => $this->recordTypeMapping,
      '#title' => $this->t('Activity contact type'),
      '#default_value' => $this->options['record_type_id'],
    ];

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->definition['extra'] = [];
    if (!empty($this->options['record_type_id'])) {
      $record_type = !is_array($this->options['record_type_id']) ? [$this->options['record_type_id']] : $this->options['record_type_id'];
      foreach ($record_type as $type) {
        $this->definition['extra'][] = [
          'field' => 'record_type_id',
          'value' => $type,
          'numeric' => TRUE,
        ];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();

    $views_data = Views::viewsData()->get($this->table);
    $left_field = $views_data['table']['base']['field'];

    // Add a join to the civicrm_activity_contact table,
    // with the matching contact_id.
    $first = [
      'left_table' => $this->tableAlias,
      'left_field' => $left_field,
      'table' => 'civicrm_activity_contact',
      'field' => $this->definition['first field'],
      'adjusted' => TRUE,
    ];

    if (!empty($this->options['required'])) {
      $first['type'] = 'INNER';
    }

    if (!empty($this->definition['extra'])) {
      $first['extra'] = $this->definition['extra'];
      $first['extra_operator'] = 'OR';
    }

    $first_join = Views::pluginManager('join')->createInstance('standard', $first);
    $first_alias = $this->query->addTable('civicrm_activity_contact', $this->relationship, $first_join);

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

    $alias = $this->definition['base'] . '_civicrm_activity_contact';

    $this->alias = $this->query->addRelationship($alias, $second_join, $this->definition['base'], $this->relationship);
  }

}
