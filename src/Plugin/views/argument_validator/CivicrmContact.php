<?php

namespace Drupal\civicrm_entity\Plugin\views\argument_validator;

use Drupal\civicrm_entity\CiviCrmApiInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\views\Plugin\views\argument_validator\Entity;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Validates whether the argument matches a contact type.
 */
class CivicrmContact extends Entity {

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

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    AccountProxy $currentUser,
    CiviCrmApiInterface $civicrmApi,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_type_bundle_info);
    $this->currentUser = $currentUser;
    $this->civicrmApi = $civicrmApi;
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
      $container->get('entity_type.bundle.info'),
      $container->get('current_user'),
      $container->get('civicrm_entity.api')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['contact_type'] = ['default' => []];
    $options['limit_own_contact'] = ['default' => FALSE];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $contact_types = ['Individual', 'Organization', 'Household'];
    $form['contact_type'] = [
      '#title' => $this->t('Contact type'),
      '#default_value' => $this->options['contact_type'],
      '#type' => 'checkboxes',
      '#options' => array_combine($contact_types, $contact_types),
    ];
    $form['limit_own_contact'] = [
      '#title' => $this->t("Limit to current user's own contact"),
      '#default_value' => $this->options['limit_own_contact'],
      '#type' => 'checkbox',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state, &$options = []) {
    $options['contact_type'] = array_filter($options['contact_type']);
  }

  /**
   * {@inheritdoc}
   */
  protected function validateEntity(EntityInterface $entity) {
    /** @var \Drupal\civicrm_entity\Entity\CivicrmEntity $entity */
    $valid = TRUE;

    if (!empty($this->options['contact_type']) && !in_array($entity->get('contact_type')->value, $this->options['contact_type'])) {
      $valid = FALSE;
    }

    if ($valid && !empty($this->options['limit_own_contact'])) {
      $results = $this->civicrmApi->get('UFMatch', [
        'sequential' => 1,
        'contact_id' => $entity->id(),
      ]);
      if (empty($results) || empty($results[0]['contact_id'])
        || $results[0]['uf_id'] != $this->currentUser->id()
      ) {
        $valid = FALSE;
      }
    }

    return $valid && parent::validateEntity($entity);
  }

}
