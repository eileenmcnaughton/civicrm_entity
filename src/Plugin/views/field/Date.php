<?php

namespace Drupal\civicrm_entity\Plugin\views\field;

use Drupal\views\ResultRow;
use Drupal\views\Plugin\views\field\Date as BaseDate;

/**
 * Display field from date.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("civicrm_entity_date")
 */
class Date extends BaseDate {

  /**
   * {@inheritdoc}
   */
  public function getValue(ResultRow $values, $field = NULL) {
    $value = parent::getValue($values, $field);
    return strtotime($value);
  }

}
