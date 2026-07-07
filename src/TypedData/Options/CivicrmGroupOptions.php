<?php

namespace Drupal\civicrm_entity\TypedData\Options;

use Drupal\civicrm_entity\CiviCrmApiInterface;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\rules\TypedData\Options\OptionsProviderBase;

/**
 * Options provider to list CiviCRM Groups.
 */
class CivicrmGroupOptions extends OptionsProviderBase implements ContainerInjectionInterface {

  use AutowireTrait;

  /**
   * The CiviCRM API service interface.
   *
   * @var \Drupal\civicrm_entity\CiviCrmApiInterface
   */
  protected $civicrmApi;

  /**
   * Constructs a CivicrmGroupOptions object.
   *
   * @param \Drupal\civicrm_entity\CiviCrmApiInterface $civicrm_api
   *   The CiviCRM API service interface.
   */
  public function __construct(CiviCrmApiInterface $civicrm_api) {
    $this->civicrmApi = $civicrm_api;
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleOptions(?AccountInterface $account = NULL) {
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
