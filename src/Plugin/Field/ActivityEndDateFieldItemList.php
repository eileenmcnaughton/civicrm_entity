<?php

namespace Drupal\civicrm_entity\Plugin\Field;

use Drupal\civicrm_entity\Entity\CivicrmEntity;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * A computed field item list for Activities to provide an end date and time.
 */
class ActivityEndDateFieldItemList extends FieldItemList {
  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    $entity = $this->getEntity();
    assert($entity instanceof CivicrmEntity);
    $activity_date_time = $entity->get('activity_date_time')->value;
    $duration = $entity->get('duration')->value;
    if (!$activity_date_time) {
      return;
    }
    // If there was no duration entered, set the value as the start time.
    if (!$duration || !is_numeric($duration)) {
      $this->list[0] = $this->createItem(0, $activity_date_time);
    }
    else {
      $date = new DrupalDateTime($activity_date_time);
      $date->modify("+$duration minutes");
      $this->list[0] = $this->createItem(0, $date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT));
    }
  }

  /**
   * {@inheritdoc}
   *
   * Recalculate the activities duration if the end date has been changed.
   */
  public function onChange($delta) {
    $entity = $this->getEntity();
    assert($entity instanceof CivicrmEntity);
    $activity_date_time = strtotime($entity->get('activity_date_time')->value);
    $new_end_date = strtotime($this->get($delta)->value);
    $diff = $new_end_date - $activity_date_time;
    // Update the duration of the activity.
    $minutes = $diff / 60;
    $entity->get('duration')->setValue($minutes);
    parent::onChange($delta);
  }

}
