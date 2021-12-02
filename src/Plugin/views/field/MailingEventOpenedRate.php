<?php

namespace Drupal\civicrm_entity\Plugin\views\field;

use Drupal\views\Plugin\views\field\NumericField;
use Drupal\views\ResultRow;

/**
 * Class for MailingEventOpenedRate.
 *
 * @ViewsField("civicrm_entity_mailing_event_opened_rate")
 */
class MailingEventOpenedRate extends MailingEvent {

  /**
   * {@inheritdoc}
   */
  public function getValue(ResultRow $values, $field = NULL) {
    $value = NumericField::getValue($values);

    if (!class_exists('CRM_Mailing_Event_BAO_Delivered') || !class_exists('CRM_Mailing_Event_BAO_Opened')) {
      $this->civicrmApi->civicrmInitialize();
    }

    $delivered = \CRM_Mailing_Event_BAO_Delivered::getTotalCount($value);
    $opened = \CRM_Mailing_Event_BAO_Opened::getTotalCount($value, NULL, TRUE);

    return number_format($delivered ? (($opened / $delivered) * 100) : 0, 2);
  }

}
