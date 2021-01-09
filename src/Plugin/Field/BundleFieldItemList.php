<?php

namespace Drupal\civicrm_entity\Plugin\Field;

use Drupal\civicrm_entity\Entity\CivicrmEntity;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Language\LanguageInterface;
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
    $machine_name = $transliteration->transliterate($bundle_value, LanguageInterface::LANGCODE_DEFAULT, '_');
    $machine_name = mb_strtolower($machine_name);
    $machine_name = preg_replace('/[^a-z0-9_]+/', '_', $machine_name);
    $machine_name = preg_replace('/_+/', '_', $machine_name);
    $this->list[0] = $this->createItem(0, $machine_name);
  }


}
