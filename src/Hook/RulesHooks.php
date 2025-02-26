<?php

namespace Drupal\civicrm_entity\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for rules.
 */
class RulesHooks {

  /**
   * Implements hook_rules_action_info_alter().
   */
  #[Hook('rules_action_info_alter')]
  public function rulesActionInfoAlter(array &$rules_actions): void {
    $definitions = \Drupal::service('plugin.manager.typed_data_filter')->getDefinitions();
    $filters = "";
    foreach ($definitions as $key) {
      if ($key['provider'] == 'civicrm_entity') {
        $filters .= ($filters == '' ? '' : ', ') . $key['id'];
      }
    }
    if (array_key_exists('format', $rules_actions['civicrm_entity_user_create']['context_definitions'])) {
      // Drupal 9 use 'context_definitions' instead of 'context'.
      $rules_actions['civicrm_entity_user_create']['context_definitions']['format']->setDescription(t('Format of the username. Use <a href="@url">Twig style</a> tokens for using the available data.<br>Civicrm Entity filter available : @filters.',
        [
          '@url' => 'https://www.drupal.org/docs/8/modules/typed-data-api-enhancements/typeddata-tokens',
          '@filters' => $filters,
        ]));
    }
    else {
      $rules_actions['civicrm_entity_user_create']['context']['format']->setDescription(t('Format of the username. Use <a href="@url">Twig style</a> tokens for using the available data.<br>Civicrm Entity filter available : @filters.',
        [
          '@url' => 'https://www.drupal.org/docs/8/modules/typed-data-api-enhancements/typeddata-tokens',
          '@filters' => $filters,
        ]));
    }
  }

}
