<?php

namespace Drupal\civicrm_entity\Plugin\views\argument_default;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Attribute\ViewsArgumentDefault;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\civicrm_entity\CiviCrmApiInterface;

/**
 * Default argument plugin to get the current user's civicrm contact ID.
 *
 * This plugin actually has no options so it does not need to do a great deal.
 *
 * @ViewsArgumentDefault(
 *   id = "current_user_contact_id",
 *   title = @Translation("Contact ID from logged in user")
 * )
 */
#[ViewsArgumentDefault(
  id: 'current_user_contact_id',
  title: new TranslatableMarkup('Contact ID from logged in user'),
)]
class ContactId extends ArgumentDefaultPluginBase implements CacheableDependencyInterface {

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
   * Constructs a new ContactId instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountProxy $currentUser
   *   The current user.
   * @param \Drupal\civicrm_entity\CiviCrmApiInterface $civicrmApi
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
    // Default return value 0 (there is never a contact with this ID).
    $cid = 0;

    $results = $this->civicrmApi->get('UFMatch', [
      'sequential' => 1,
      'uf_id' => $this->currentUser->id(),
    ]);

    if (!empty($results) && !empty($results[0]['contact_id'])) {
      $cid = $results[0]['contact_id'];
    }

    return $cid;
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
