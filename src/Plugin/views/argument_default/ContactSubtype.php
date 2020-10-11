<?php

namespace Drupal\civicrm_entity\Plugin\views\argument_default;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;

/**
 * Default argument plugin to extract the current user's civicrm contact subtype
 *
 * This plugin actually has no options so it does not need to do a great deal.
 *
 * @ViewsArgumentDefault(
 *   id = "current_user_contact_subtype",
 *   title = @Translation("Contact subtype from logged in user")
 * )
 */
class ContactSubtype extends ArgumentDefaultPluginBase implements CacheableDependencyInterface {

  /**
   * {@inheritdoc}
   */
  public function getArgument() {

    $current_user_contact_subtype = '';

    $civicrm_api = \Drupal::getContainer()->get('civicrm_entity.api');                                                 

    $results = $civicrm_api->get('UFMatch', [
      'sequential' => 1,
      'id' => \Drupal::currentUser()->id(),
    ]);

    if (!empty($results) && !empty($results[0]['contact_id'])) {
      $cid = $results[0]['contact_id'];

      $results = $civicrm_api->get('contact', [
        'sequential' => 1,
        'return' => ['contact_sub_type'],
        'id' => $cid,
      ]);

      if (!empty($results) && !empty($results[0]['contact_sub_type'])) {

         // Get first contact subtype.
         $current_user_contact_subtype = reset($results[0]['contact_sub_type']);
      }
    }

    return $current_user_contact_subtype;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['user'];
  }

}
