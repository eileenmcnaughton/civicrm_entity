<?php

namespace Drupal\civicrm_entity\Plugin\Action;

use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsPreconfigurationInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\civicrm_entity\CiviCrmApi;

if (!class_exists('Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase')) {
  return;
}

/**
 * Action to add CiviCRM Contact to a CiviCRM group.
 *
 * @Action(
 *   id = "civicrm_contact_add_to_group",
 *   label = @Translation("Add Contact to Group"),
 *   type = "civicrm_contact",
 *   confirm = TRUE,
 * )
 */
class CivicrmContactAddToGroup extends ViewsBulkOperationsActionBase implements ViewsBulkOperationsPreconfigurationInterface, PluginFormInterface, ContainerFactoryPluginInterface {

  /**
   * The CiviCRM API service.
   *
   * @var \Drupal\civicrm_entity\CiviCrmApi
   */
  protected $civicrmApi;

  /**
   * CivicrmContactAddToGroup constructor.
   *
   * @param array $configuration
   *   The configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\civicrm_entity\CiviCrmApi $civicrm_entity_api
   *   The CiviCRM API service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CiviCrmApi $civicrm_entity_api) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->civicrmApi = $civicrm_entity_api;
  }

  /**
   * Create method.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container.
   * @param array $configuration
   *   The configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   *
   * @return static
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
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if (!empty($this->configuration['selected_group']) && !empty($entity)) {
      // So do we need to check if a contact is in the group already?
      try {
        $this->civicrmApi->save('GroupContact', [
          'group_id'   => $this->configuration['selected_group'],
          'contact_id' => $entity->id(),
        ]);
        $this->messenger()->addMessage('Added: ' . $entity->label() . ' to group: ' . $this->fetchGroupTitle($this->configuration['selected_group']));
      }
      catch (\Exception $e) {

      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildPreConfigurationForm(array $form, array $values, FormStateInterface $form_state): array {
    $groups = $this->fetchGroups();

    $form['allowed_groups'] = [
      '#title' => $this->t('Allowed Groups'),
      '#type' => 'select',
      '#multiple' => TRUE,
      '#options' => $groups,
      '#default_value' => $values['allowed_groups'] ?? [],
    ];
    return $form;
  }

  /**
   * Configuration form builder.
   *
   * If this method has implementation, the action is
   * considered to be configurable.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   The configuration form.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $groups = [];
    if (!empty($this->context['preconfiguration']['allowed_groups'])) {
      $groups = $this->fetchGroups($this->context['preconfiguration']['allowed_groups']);
    }
    $form['selected_group'] = [
      '#title' => t('Group'),
      '#type' => 'select',
      '#options' => $groups,
      '#default_value' => $form_state->getValue('selected_group'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $object->access('update', $account, $return_as_object);
  }

  /**
   * Fetch array of group titles, keyed by id.
   *
   * @param array $ids
   *   Array of ids.
   *
   * @return array
   *   The array of group titles.
   */
  private function fetchGroups(array $ids = []) {
    $groups = [];
    try {
      $params = [
        'sequential' => FALSE,
        'return'     => ["title"],
        'options'    => ['limit' => 0],
      ];
      if (!empty($ids)) {
        $params['id'] = ['IN' => $ids];
      }
      $api_groups = $this->civicrmApi->get('group', $params);
      if (!empty($api_groups)) {
        foreach ($api_groups as $gid => $group) {
          $groups[$gid] = $group['title'];
        }
      }
    }
    catch (\Exception $e) {

    }
    return $groups;
  }

  /**
   * Return group title given group id.
   *
   * @param int $group_id
   *   The group id.
   *
   * @return string
   *   The title.
   */
  private function fetchGroupTitle($group_id) {
    try {
      if (!empty($group_id) && is_numeric($group_id)) {
        $value = $this->civicrmApi->getSingle('Group', [
          'return' => ["title"],
          'id'     => $group_id,
        ]);
        if (!empty($value['title'])) {
          return $value['title'];
        }
      }
    }
    catch (\Exception $e) {

    }
    return '';
  }

}
