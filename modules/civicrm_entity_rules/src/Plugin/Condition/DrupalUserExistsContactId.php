<?php

namespace Drupal\civicrm_entity_rules\Plugin\Condition;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\rules\Context\ContextDefinition;
use Drupal\rules\Core\Attribute\Condition;
use Drupal\rules\Core\RulesConditionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\civicrm_entity\CiviCrmApiInterface;
use Drupal\user\Entity\User;

/**
 * Provides a 'Drupal linked User exists' condition.
 */
#[Condition(
  id: "civicrm_contact_id_drupal_user_exists",
  label: new TranslatableMarkup("CiviCRM Contact Id linked User exists"),
  category: new TranslatableMarkup("CiviCRM"),
  context_definitions: [
    "civicrm_contact_id" => new ContextDefinition(
      data_type: "integer",
      label: new TranslatableMarkup("CiviCRM contact ID"),
      required: TRUE,
      description: new TranslatableMarkup("The CiviCRM contact ID.")
    ),
  ]
)]
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
