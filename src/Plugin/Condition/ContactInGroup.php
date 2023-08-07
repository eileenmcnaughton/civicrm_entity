<?php

namespace Drupal\civicrm_entity\Plugin\Condition;

use Drupal\civicrm_entity\CiviCrmApi;
use Drupal\civicrm_entity\Entity\CivicrmEntity;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\rules\Core\RulesConditionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'CiviCRM Contact in Group' condition.
 *
 * @Condition(
 *   id = "civicrm_entity_contact_in_group",
 *   label = @Translation("CiviCRM Contact in Group"),
 *   category = @Translation("CiviCRM"),
 *   context_definitions = {
 *     "civicrm_contact" = @ContextDefinition("entity:civicrm_contact",
 *        label = @Translation("CiviCRM contact entity"),
 *        description = @Translation("The CiviCRM contact entity."),
 *        required = TRUE
 *      ),
 *     "group" = @ContextDefinition("string",
 *       label = @Translation("Group"),
 *       description = @Translation("The group the contact is in."),
 *       options_provider = "\Drupal\civicrm_entity\TypedData\Options\CivicrmGroupOptions",
 *       multiple = FALSE,
 *       required = TRUE
 *     ),
 *   }
 * )
 */
class ContactInGroup extends RulesConditionBase implements ContainerFactoryPluginInterface {

  /**
   * The CiviCRM API service.
   *
   * @var \Drupal\civicrm_entity\CiviCrmApi
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
   * @param \Drupal\civicrm_entity\CiviCrmApi $civicrm_api
   *   The CiviCRM API service interface.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CiviCrmApi $civicrm_api) {
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
