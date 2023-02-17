<?php

namespace Drupal\civicrm_entity\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\Date as BaseDate;
use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Core\Datetime\DateFormatterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * An "Date" handler to include CiviCRM API.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("civicrm_entity_date")
 */
class Date extends BaseDate {
  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Date format for comparison.
   *
   * @var string
   */
  protected $dateFormat = 'Y-m-d H:i:s';

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DateFormatterInterface $date_formatter) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function opBetween($field) {
    $a = intval(strtotime($this->value['min'], 0));
    $b = intval(strtotime($this->value['max'], 0));

    if ($this->value['type'] == 'offset') {
      $now = time();
      $a = $now + (int) sprintf('%+d', $a);
      $b = $now + (int) sprintf('%+d', $b);
    }

    $a = DateTimePlus::createFromTimestamp($a);
    $a = $this
      ->dateFormatter
      ->format($a->getTimestamp(), 'custom', $this->dateFormat);
    $a = $this->getFieldDateFormat("'" . $a . "'");

    $b = DateTimePlus::createFromTimestamp($b);
    $b = $this
      ->dateFormatter
      ->format($b->getTimestamp(), 'custom', $this->dateFormat);
    $b = $this->getFieldDateFormat("'" . $b . "'");

    $operator = strtoupper($this->operator);
    $field = $this->getFieldDateFormat($field);
    $this->query->addWhereExpression($this->options['group'], "$field $operator $a AND $b");
  }

  /**
   * {@inheritdoc}
   */
  protected function opSimple($field) {
    $value = intval(strtotime($this->value['value'], 0));

    if (!empty($this->value['type']) && $this->value['type'] == 'offset') {
      $value = time() + (int) sprintf('%+d', $value);
    }

    $value = DateTimePlus::createFromTimestamp($value);
    $value = $this
      ->dateFormatter
      ->format($value->getTimestamp(), 'custom', $this->dateFormat);

    $value = $this->getFieldDateFormat("'" . $value . "'");
    $field = $this->getFieldDateFormat($field);

    $this->query->addWhereExpression($this->options['group'], "$field $this->operator $value");
  }

  /**
   * Get date field.
   *
   * @param string $field
   *   The column field.
   *
   * @return string
   *   Field formatted as date.
   *
   * @see \Drupal\views\Plugin\views\query::getDateFormat()
   */
  protected function getFieldDateFormat($field) {
    return $this
      ->query
      ->getDateFormat(
        $this->query->getDateField($field, TRUE, FALSE),
        $this->dateFormat
      );
  }

}
