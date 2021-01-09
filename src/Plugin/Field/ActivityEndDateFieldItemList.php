<?php

namespace Drupal\civicrm_entity\Plugin\Field;

use Drupal\civicrm_entity\Entity\CivicrmEntity;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * A computed field item list for Activities to provide an end date and time.
 *
 * In this class, you will notice timezone conversions from the default timezone
 * to UTC. CiviCRM stores dates in the expected timezone, and Drupal always
 * stores them in UTC. So we have to do a bit of conversion.
 *
 * This is also automatically done when a CivicrmEntity instance is saved and
 * when an entity is loaded.
 *
 * @see \Drupal\civicrm_entity\Entity\CivicrmEntity::civicrmApiNormalize
 * @see \Drupal\civicrm_entity\CiviEntityStorage::initFieldValues
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
    // The time is already in UTC due to ::initFieldValues in storage.
    // @see \Drupal\civicrm_entity\CiviEntityStorage::initFieldValues
    $date = new \DateTime($activity_date_time, new \DateTimeZone('UTC'));
    // We have to change this _back_ to the default timezone, as initFieldValues
    // will be called again and it assumes the value is from CiviCRM, in the
    // default timezone.
    $date->setTimezone(new \DateTimeZone(date_default_timezone_get()));

    if (is_numeric($duration)) {
      $date->add(new \DateInterval("PT{$duration}M"));
    }
    $this->list[0] = $this->createItem(0, $date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT));
  }

  /**
   * {@inheritdoc}
   *
   * Recalculate the activities duration if the end date has been changed.
   */
  public function onChange($delta) {
    $entity = $this->getEntity();
    assert($entity instanceof CivicrmEntity);
    // Since we're calculating a difference in times, we can use UTC.
    $activity_date_time = new \DateTime($entity->get('activity_date_time')->value, new \DateTimeZone('UTC'));
    $new_end_date = new \DateTime($this->get($delta)->value, new \DateTimeZone('UTC'));
    $diff = $new_end_date->getTimestamp() - $activity_date_time->getTimestamp();

    $minutes = $diff / 60;
    $entity->get('duration')->setValue($minutes);
    parent::onChange($delta);
  }

}
