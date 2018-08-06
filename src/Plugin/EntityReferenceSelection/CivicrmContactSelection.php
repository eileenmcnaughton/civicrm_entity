<?php

namespace Drupal\civicrm_entity\Plugin\EntityReferenceSelection;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Provides specific access control for the civicrm_contact entity type.
 *
 * This is because `display_name` does not work on LIKE queries, but the
 * `=` condition is treated as one.
 *
 * @EntityReferenceSelection(
 *   id = "default:civicrm_contact",
 *   label = @Translation("CiviCRM Contact selection"),
 *   entity_types = {"civicrm_contact"},
 *   group = "default",
 *   weight = 1
 * )
 */
class CivicrmContactSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    return parent::getReferenceableEntities($match, '=', $limit);
  }

  public function countReferenceableEntities($match = NULL, $match_operator = 'CONTAINS') {
    return parent::countReferenceableEntities($match, '=');
  }

}
