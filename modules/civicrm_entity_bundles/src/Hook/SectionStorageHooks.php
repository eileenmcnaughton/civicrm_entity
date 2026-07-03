<?php

declare(strict_types=1);

namespace Drupal\civicrm_entity_bundles\Hook;

use Drupal\civicrm_entity_bundles\Plugin\SectionStorage\CivicrmEntityBundlesDefaultsSectionStorage;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Layout Builder section storage hook implementations.
 */
final class SectionStorageHooks {

  /**
   * Implements hook_layout_builder_section_storage_alter().
   */
  #[Hook('layout_builder_section_storage_alter')]
  public function sectionStorageAlter(array &$definitions): void {
    if (isset($definitions['defaults'])) {
      $definitions['defaults']->setClass(CivicrmEntityBundlesDefaultsSectionStorage::class);
    }
  }

}
