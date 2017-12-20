<?php

namespace Drupal\civicrm_entity\Entity;

use Drupal\Core\Annotation\PluralTranslation;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\Annotation\ContentEntityType;

/**
 * Defines the comment entity class.
 *
 * @ContentEntityType(
 *   id = "civicrm_event",
 *   civicrm_entity = "event",
 *   label = @Translation("CiviCRM Event"),
 *   label_singular = @Translation("event"),
 *   label_plural = @Translation("events"),
 *   label_count = @PluralTranslation(
 *     singular = "@count event",
 *     plural = "@count events",
 *   ),
 * )
 */
class Events extends CivicrmEntityBase {

}
