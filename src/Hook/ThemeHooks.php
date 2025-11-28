<?php

namespace Drupal\civicrm_entity\Hook;

use Drupal\civicrm_entity\Entity\CivicrmEntity;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\Element;

/**
 * Hook implementations for themes.
 */
class ThemeHooks {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  function theme() {
    return [
      'civicrm_entity_entity_form' => [
        'render element' => 'form',
      ],
      'civicrm_entity' => [
        'render element' => 'elements',
      ],
    ];
  }

  /**
   * Implements hook_theme_suggestions_HOOK_alter().
   */
  #[Hook('theme_suggestions_civicrm_entity_alter')]
  public function themeSuggestionsCivicrmEntityAlter(array &$suggestions, array $variables) {
    $view_mode = $variables['elements']['#view_mode'];
    $hook = $variables['theme_hook_original'];

    // Add a suggestion based on the entity type.
    if ($entity_type = $this->getEntityTyeFromElements($variables['elements'])) {
      $suggestions[] = $hook . '__' . $entity_type;

      // Add a suggestion based on the view mode.
      $suggestions[] = $hook . '__' . $entity_type . '__' . $view_mode;
    }
  }

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_civicrm_entity')]
  public function preprocessCivicrmEntity(&$variables) {
    // Add fields as content to template.
    $variables += ['content' => []];
    foreach (Element::children($variables['elements']) as $key) {
      $variables['content'][$key] = $variables['elements'][$key];
    }

    // Add the view_mode to the template.
    $variables['view_mode'] = $variables['elements']['#view_mode'];

    // Add the bundle to the template.
    $variables['entity_type'] = $this->getEntityTyeFromElements($variables['elements']);
  }

  /**
   * Implements hook_theme_registry_alter().
   */
  #[Hook('theme_registry_alter')]
  public function themeRegistryAlter(&$theme_registry) {
    $theme_registry['civicrm_entity']['preprocess functions'][] = 'field_group_build_entity_groups';
  }

  /**
   * Helper to find the entity type from $variables['elements'].
   */
  protected function getEntityTyeFromElements($elements) {
    if (isset($elements['#entity_type'])) {
      return $elements['#entity_type'];
    }

    // Find the CivicrmEntity from elements if #entity_type is not set.
    foreach ($elements as $element) {
      if ($element instanceof CivicrmEntity) {
        /** @var \Drupal\civicrm_entity\Entity\CivicrmEntity $element */
        return $element->getEntityTypeId();
      }
    }

    return NULL;
  }


}
