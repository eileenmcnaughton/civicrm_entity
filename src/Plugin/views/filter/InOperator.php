<?php

namespace Drupal\civicrm_entity\Plugin\views\filter;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Condition;
use Drupal\civicrm_entity\CiviCrmApiInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\InOperator as BaseInOperator;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * An "In" handler to include CiviCRM API.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("civicrm_entity_in_operator")
 */
class InOperator extends BaseInOperator {

  /**
   * The CiviCRM API.
   *
   * @var \Drupal\civicrm_entity\CiviCrmApiInterface
   */
  protected $civicrmApi;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, CiviCrmApiInterface $civicrm_api, Connection $connection) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->civicrmApi = $civicrm_api;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->civicrmApi->civicrmInitialize();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('civicrm_entity.api'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    if (isset($this->valueOptions)) {
      return $this->valueOptions;
    }

    if (isset($this->definition['options callback']) && is_callable($this->definition['options callback'])) {
      if (isset($this->definition['options arguments'])) {
        // @todo We override the call to only include a single argument since
        // for some reason, if it was an array, they are merged when getting the
        // views data.
        $this->valueOptions = call_user_func_array($this->definition['options callback'], [$this->definition['options arguments']]);
      }
      else {
        $this->valueOptions = call_user_func($this->definition['options callback']);
      }
    }
    else {
      $this->valueOptions = [$this->t('Yes'), $this->t('No')];
    }

    return $this->valueOptions;
  }

  /**
   * {@inheritdoc}
   */
  protected function opSimple() {
    if (empty($this->value)) {
      return;
    }
    $this->ensureMyTable();

    $values = array_values($this->value);
    $field = "$this->tableAlias.$this->realField";

    if (!isset($this->definition['multi']) || !$this->definition['multi']) {
      $this
        ->query
        ->addWhere($this->options['group'], $field, $values, $this->operator);
    }
    // If this is a multi-value field in CiviCRM, we use 'LIKE' and 'NOT LIKE'
    // instead.
    else {

      $values = array_map(function ($value) {
        return \CRM_Core_DAO::VALUE_SEPARATOR . $value . \CRM_Core_DAO::VALUE_SEPARATOR;
      }, $values);

      switch ($this->operator) {
        case 'in':
          $this->query
            ->addWhereExpression(
              $this->options['group'],
              "CAST({$field} AS BINARY) RLIKE BINARY :" . $this->realField,
              [':' . $this->realField => implode('|', $values)]
            );

          break;

        case 'not in':
          $this->query
            ->addWhereExpression(
              $this->options['group'],
              "CAST({$field} AS BINARY) NOT RLIKE BINARY :" . $this->realField,
              [':' . $this->realField => implode('|', $values)]
            );

          break;
      }
    }
  }

  public function operators() {
    $operators = parent::operators();
    if (!empty($this->definition['allow empty'])) {
      $operators += [
        'empty string' => [
          'title' => $this->t('Is EMPTY/NULL'),
          'method' => 'opEmptyString',
          'short' => $this->t('empty string'),
          'values' => 0,
        ],
        'not empty string' => [
          'title' => $this->t('Is not EMPTY/NULL'),
          'method' => 'opEmptyString',
          'short' => $this->t('not empty string'),
          'values' => 0,
        ],
      ];
    }
    return $operators;
  }

  protected function opEmptyString() {
    $this->ensureMyTable();
    $field = "$this->tableAlias.$this->realField";

    if ($this->operator == 'empty string') {
      $operator = "=";
      $nullOP = 'IS NULL';
    }
    else {
      $operator = "!=";
      $nullOP = 'IS NOT NULL';
    }

    $condition = new Condition('OR');
    $condition->condition($field, '', $operator);
    $condition->condition($field, NULL, $nullOP);

    $this->query->addWhere($this->options['group'], $condition);
  }

}
