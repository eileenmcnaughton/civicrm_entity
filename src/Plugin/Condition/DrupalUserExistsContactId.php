<?php

namespace Drupal\civicrm_entity\Plugin\Condition;

use Drupal\rules\Core\RulesConditionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\civicrm_entity\CiviCrmApiInterface;
use Drupal\user\Entity\User;

/**
 * Provides a 'Drupal linked User exists' condition.
 *
 * @Condition(
 *   id = "civicrm_contact_id_drupal_user_exists",
 *   label = @Translation("CiviCRM Contact Id linked User exists"),
 *   category = @Translation("CiviCRM"),
 *   context_definitions = {
 *     "civicrm_contact_id" = @ContextDefinition("integer",
 *        label = @Translation("CiviCRM contact ID"),
 *        description = @Translation("The CiviCRM contact ID."),
 *        required = TRUE
 *      )
 *   }
 * )
 */
class DrupalUserExistsContactId extends RulesConditionBase implements ContainerFactoryPluginInterface {

  /**
   * The CiviCRM API service interface.
   *
   * @var \Drupal\civicrm_entity\CiviCrmApiInterface
   */
  protected $civicrmApi;

  /**
   * Constructs a DrupalUserExist object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\civicrm_entity\CiviCrmApiInterface $civicrm_api
   *   The CiviCRM API service interface.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CiviCrmApiInterface $civicrm_api) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->civicrmApi = $civicrm_api;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('civicrm_entity.api')
    );
  }

  /**
   * Check if linked Drupal user exists.
   *
   * @param int $civicrm_contact_id
   *   The CiviCRM contact to check.
   *
   * @return bool
   *   TRUE if the contact_id is linked to a drupal account.
   */
  protected function doEvaluate(int $civicrm_contact_id) {
    try {
      $id = $civicrm_contact_id;
      if (!empty($id) && is_numeric($id)) {
        $result = $this->civicrmApi->get('UFMatch', [
          'sequential' => 1,
          'return' => ["uf_id"],
          'contact_id' => (int) $id,
        ]);
        if (!empty($result[0]['uf_id'])) {
          $account = User::load($result[0]['uf_id']);
          if (is_object($account)) {
            // In future we could return the User object to Rules.
            // To use in other Conditions or Actions.
            return TRUE;
          }
        }
      }
    }
    catch (\CiviCRM_API3_Exception $e) {
      return FALSE;
    }
    return FALSE;
  }

}
