<?php

declare(strict_types=1);

namespace Drupal\civicrm_entity_field_group\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Theme hooks for the Field Group integration.
 */
final class ThemeHooks {

  /**
   * Implements hook_theme_registry_alter().
   */
  #[Hook('theme_registry_alter')]
  public function themeRegistryAlter(array &$theme_registry): void {
    if (isset($theme_registry['civicrm_entity'])) {
      $theme_registry['civicrm_entity']['preprocess functions'][] = 'field_group_build_entity_groups';
    }
  }

}
