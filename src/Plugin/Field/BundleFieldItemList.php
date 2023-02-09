<?php

namespace Drupal\civicrm_entity\Plugin\Field;

use Drupal\civicrm_entity\Entity\CivicrmEntity;
use Drupal\civicrm_entity\SupportedEntities;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * Computed field item list for the bundle property.
 */
class BundleFieldItemList extends FieldItemList {
  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    $entity = $this->getEntity();
    assert($entity instanceof CivicrmEntity);
    $civicrm_bundle_property = $entity->getEntityType()->get('civicrm_bundle_property');
    $civicrm_entity_name = $entity->getEntityType()->get('civicrm_entity');

    $raw_bundle_value = $entity->get($civicrm_bundle_property)->value;

    /** @var \Drupal\civicrm_entity\CiviCrmApiInterface $civicrm_api */
    $civicrm_api = \Drupal::service('civicrm_entity.api');
    $options = $civicrm_api->getOptions($civicrm_entity_name, $civicrm_bundle_property);
    $bundle_value = $options[$raw_bundle_value];

    $transliteration = \Drupal::transliteration();
    $machine_name = SupportedEntities::optionToMachineName($bundle_value, $transliteration);
    $this->list[0] = $this->createItem(0, $machine_name);
  }

  /**
   * {@inheritdoc}
   *
   * This sets the bundle property if Drupal sets a value to `bundle`, which
   * means we have to transliterate the options and convert a machine name to
   * the option key.
   */
  public function setValue($values, $notify = TRUE) {
    $entity = $this->getEntity();
    assert($entity instanceof CivicrmEntity);
    $civicrm_bundle_property = $entity->getEntityType()->get('civicrm_bundle_property');
    $civicrm_entity_name = $entity->getEntityType()->get('civicrm_entity');

    /** @var \Drupal\civicrm_entity\CiviCrmApiInterface $civicrm_api */
    $civicrm_api = \Drupal::service('civicrm_entity.api');
    $options = $civicrm_api->getOptions($civicrm_entity_name, $civicrm_bundle_property);

    $transliteration = \Drupal::transliteration();
    $options = array_map(static function ($value) use ($transliteration) {
      return SupportedEntities::optionToMachineName($value, $transliteration);
    }, $options);
    $options = array_flip($options);

    if (!is_array($values)) {
      $entity->get($civicrm_bundle_property)->setValue($options[$values]);
    }

    parent::setValue($values, $notify);
    $this->valueComputed = TRUE;
  }

}
