<?php

declare(strict_types=1);

namespace Drupal\civicrm_entity_bundles\Plugin\SectionStorage;

use Drupal\layout_builder\LayoutBuilderEnabledInterface;
use Drupal\layout_builder\Plugin\SectionStorage\DefaultsSectionStorage;

/**
 * Defaults section storage with dynamic CiviCRM Entity bundle support.
 *
 * CiviCRM Entity bundle displays are derived from the generic-bundle display
 * at runtime, so dynamic bundle displays are not necessarily saved in config.
 * Core's defaults section storage checks config storage directly before
 * rendering Layout Builder output. This fallback allows dynamic bundles to use
 * the generic-bundle display when that display has Layout Builder enabled.
 *
 * @internal
 *   Extends an internal core Layout Builder plugin to keep dynamic bundle
 *   support compatible with core's support checks.
 */
final class CivicrmEntityBundlesDefaultsSectionStorage extends DefaultsSectionStorage {

  /**
   * {@inheritdoc}
   */
  public function isSupported(string $entity_type_id, string $bundle, string $view_mode): bool {
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id, FALSE);
    if (!$entity_type || !$entity_type->get('civicrm_entity') || !$entity_type->hasKey('bundle')) {
      return parent::isSupported($entity_type_id, $bundle, $view_mode);
    }

    if (parent::isSupported($entity_type_id, $bundle, $view_mode)) {
      return TRUE;
    }

    $generic_display = $this->entityTypeManager
      ->getStorage('entity_view_display')
      ->load(sprintf('%s.%s.default', $entity_type_id, $entity_type_id));

    return $generic_display instanceof LayoutBuilderEnabledInterface
      && $generic_display->isLayoutBuilderEnabled();
  }

}
