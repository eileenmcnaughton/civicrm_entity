<?php

namespace Drupal\civicrm_entity\Plugin\views\field;

use Drupal\views\Plugin\views\field\EntityField;

/**
 * Display an activities computed end date.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("civicrm_entity_activity_end_datetime")
 */
class ActivityEndDatetime extends EntityField {

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query($use_groupby = FALSE) {
    // There is no query operation.
  }

}
