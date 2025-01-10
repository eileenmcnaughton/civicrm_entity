<?php

namespace Drupal\civicrm_entity\Plugin\Field\FieldFormatter;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\Entity\User;

/**
 * Plugin implementation of the 'civicrm_entity_contact_reference' formatter.
 *
 * @FieldFormatter(
 *   id = "civicrm_entity_contact_reference",
 *   label = @Translation("CiviCRM custom contact reference field"),
 *   field_types = {
 *     "entity_reference",
 *   }
 * )
 */
#[FieldFormatter(
  id: "civicrm_entity_contact_reference",
  label: new TranslatableMarkup("CiviCRM custom contact reference field"),
  field_types: ["entity_reference"]
)]
class ContactReferenceFormatter extends EntityReferenceLabelFormatter {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity) {
    $permissions = [
      'view all contacts',
      'access all custom data',
    ];
    $account = User::load(\Drupal::currentUser()->id());
    return AccessResult::allowedIfHasPermissions($account, $permissions, 'OR');
  }

}
