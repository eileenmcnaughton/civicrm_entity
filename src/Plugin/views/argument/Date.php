<?php

namespace Drupal\civicrm_entity\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\Date as BaseDate;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Argument handler for CiviCRM dates.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("civicrm_entity_date")
 */
class Date extends BaseDate {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match, DateFormatterInterface $date_formatter) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $route_match, $date_formatter);
    $this->argFormat = 'Y-m-d h:i:s';
  }

  /**
   * {@inheritdoc}
   */
  public function getDateFormat($format) {
    return $this->query->getDateFormat($this->getDateField(), $format);
  }

  /**
   * {@inheritdoc}
   */
  public function getDateField() {
    return $this->query->getDateField("$this->tableAlias.$this->realField", TRUE, FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function query($group_by = FALSE) {
    $this->ensureMyTable();

    // Override the query so we can use a simple expression rather than
    // placeholders.
    $value = $this->query->getDateFormat($this->query->getDateField("'" . $this->argument . "'", TRUE, FALSE), $this->argFormat);
    $this->query->addWhereExpression(0, "{$this->getFormula()} = $value");
  }

}
