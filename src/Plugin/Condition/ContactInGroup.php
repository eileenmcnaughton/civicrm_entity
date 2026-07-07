<?php

namespace Drupal\civicrm_entity\Plugin\Condition;

use Drupal\civicrm_entity\CiviCrmApiInterface;
use Drupal\civicrm_entity\Entity\CivicrmEntity;
use Drupal\civicrm_entity\TypedData\Options\CivicrmGroupOptions;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\rules\Context\ContextDefinition;
use Drupal\rules\Core\RulesConditionBase;
use Drupal\rules\Core\Attribute\Condition;

/**
 * Provides a 'CiviCRM Contact in Group' condition.
 */
#[Condition(
  id: "civicrm_entity_contact_in_group",
  label: new TranslatableMarkup("CiviCRM Contact in Group"),
  category: new TranslatableMarkup("CiviCRM"),
  context_definitions: [
    "civicrm_contact" => new ContextDefinition(
      data_type: "entity:civicrm_contact",
      label: new TranslatableMarkup("CiviCRM contact entity"),
      required: TRUE,
      description: new TranslatableMarkup("The CiviCRM contact entity.")
    ),
    "group" => new ContextDefinition(
      data_type: "string",
      label: new TranslatableMarkup("Group"),
      required: TRUE,
      multiple: FALSE,
      description: new TranslatableMarkup("The group the contact is in."),
      options_provider: CivicrmGroupOptions::class,
    ),
  ]
)]
class ContactInGroup extends RulesConditionBase implements ContainerFactoryPluginInterface {

  /**
   * The CiviCRM API service.
   *
   * @var \Drupal\civicrm_entity\CiviCrmApiInterface
   */
  protected $civicrmApi;

  /**
   * Constructs a ContactInGroup object.
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
   * Check if contact is in group.
   *
   * @param \Drupal\civicrm_entity\Entity\CivicrmEntity $civicrm_contact
   *   The CiviCRM contact to check.
   * @param string $group
   *   The group id.
   *
   * @return bool
   *   TRUE if the contact is in a CiviCRM group.
   */
  protected function doEvaluate(CivicrmEntity $civicrm_contact, string $group) {
    try {
      $id = $civicrm_contact->get('id')->getString();
      if (!empty($id) && is_numeric($id)) {
        $result = $this->civicrmApi->get('GroupContact', [
          'sequential' => 1,
          'contact_id' => (int) $id,
          'group_id' => $group,
          'status' => "Added",
        ]);
        if (!empty($result[0]['id'])) {
          return TRUE;
        }
      }
    }
    catch (\CiviCRM_API3_Exception $e) {
      return FALSE;
    }
    return FALSE;
  }

}
