<?php

namespace Drupal\civicrm_entity\Plugin\views\argument_default;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\civicrm_entity\CiviCrmApiInterface;

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
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The CiviCRM API.
   *
   * @var \Drupal\civicrm_entity\CiviCrmApiInterface
   */
  protected $civicrmApi;

  /**
   * Constructs a new ContactSubtype instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param Drupal\Core\Session\AccountProxy
   *   The current user.
   * @param Drupal\civicrm_entity\CiviCrmApiInterface $civicrmApi
   *   The CiviCRM Api.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountProxy $currentUser, CiviCrmApiInterface $civicrmApi) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $currentUser;
    $this->civicrmApi = $civicrmApi;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('current_user'),
      $container->get('civicrm_entity.api'));
  }

  /**
   * {@inheritdoc}
   */
  public function getArgument() {

    $current_user_contact_subtype = '<none>';

    $results = $this->civicrmApi->get('UFMatch', [
      'sequential' => 1,
      'id' => $this->currentUser->id(),
    ]);

    if (!empty($results) && !empty($results[0]['contact_id'])) {
      $cid = $results[0]['contact_id'];

      $results = $this->civicrmApi->get('contact', [
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
