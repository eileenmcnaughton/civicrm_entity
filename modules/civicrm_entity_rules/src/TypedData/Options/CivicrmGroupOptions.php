<?php

namespace Drupal\civicrm_entity\TypedData\Options;

use Drupal\civicrm_entity\CiviCrmApi;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Options provider to list CiviCRM Groups.
 */
class CivicrmGroupOptions extends OptionsProviderBase implements ContainerInjectionInterface {

  /**
   * The CiviCRM API service interface.
   *
   * @var \Drupal\civicrm_entity\CiviCrmApi
   */
  protected $civicrmApi;

  /**
   * Constructs a CivicrmGroupOptions object.
   *
   * @param \Drupal\civicrm_entity\CiviCrmApi $civicrm_api
   *   The CiviCRM API service interface.
   */
  public function __construct(CiviCrmApi $civicrm_api) {
    $this->civicrmApi = $civicrm_api;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('civicrm_entity.api')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleOptions(AccountInterface $account = NULL) {
    $options = [];

    // Load all the node types.
    $groups = $this->civicrmApi->get('group', []);

    foreach ($groups as $group_id => $group) {
      $options[$group_id] = $group['title'];
    }

    // Sort the result by value for ease of locating and selecting.
    asort($options);

    return $options;
  }

}
