<?php

namespace Drupal\civicrm_entity\Plugin\RulesAction;

use Drupal\civicrm_entity\CiviCrmApiInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\rules\Core\RulesActionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Load Linked User' action.
 *
 * @RulesAction(
 *   id = "civicrm_entity_load_linked_user",
 *   label = @Translation("Load Linked User"),
 *   category = @Translation("CiviCRM"),
 *   context_definitions = {
 *     "contact_id" = @ContextDefinition("integer",
 *       label = @Translation("Contact ID"),
 *       description = @Translation("The numeric contact id."),
 *       required = TRUE
 *     ),
 *   },
 *   provides = {
 *     "user_fetched" = @ContextDefinition("entity:user",
 *       label = @Translation("Fetched user")
 *     ),
 *   }
 * )
 */
class LoadLinkedUser extends RulesActionBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The CiviCRM API service.
   *
   * @var \Drupal\civicrm_entity\CiviCrmApiInterface
   */
  protected $civicrmApi;

  /**
   * Constructs a LoadLinkedUser object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \\Drupal\civicrm_entity\CiviCrmApiInterface $civicrm_api
   *   The civicrm api service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, CiviCrmApiInterface $civicrm_api) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
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
      $container->get('entity_type.manager'),
      $container->get('civicrm_entity.api')
    );
  }

  /**
   * Executes the action with the given context.
   *
   * @param int $contact_id
   *   The contact id.
   */
  protected function doExecute($contact_id) {
    $result = $this->civicrmApi->get('UfMatch', [
      'sequential' => TRUE,
      'contact_id' => $contact_id,
    ]);
    if (!empty($result[0]['uf_id'])) {
      $user_storage = $this->entityTypeManager->getStorage('user');
      $account = $user_storage->load($result[0]['uf_id']);
      $this->setProvidedValue('user_fetched', $account);
    }
  }

}
