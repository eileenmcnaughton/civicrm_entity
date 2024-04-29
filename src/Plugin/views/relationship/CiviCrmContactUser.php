<?php

namespace Drupal\civicrm_entity\Plugin\views\relationship;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Utility\Error;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Relationship for referencing civicrm_contact and user.
 *
 * @ingroup views_relationship_handlers
 *
 * @ViewsRelationship("civicrm_entity_civicrm_contact_user")
 */
class CiviCrmContactUser extends CiviCrmBridgeRelationshipBase {

  /**
   * The CiviCRM API.
   *
   * @var \Drupal\civicrm_entity\CiviCrmApiInterface
   */
  protected $civicrmApi;

  /**
   * The Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->civicrmApi = $container->get('civicrm_entity.api');
    $instance->logger = $container->get('logger.factory')->get('civicrm_entity');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $options = parent::defineOptions();
    $options['domain_id'] = ['default' => NULL];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $this->civicrmApi->civicrmInitialize();

    $options = [];
    try {
      $result = $this->civicrmApi->get('Domain', [
        'sequential' => 1,
        'return' => ['id', 'name'],
      ]);

      if (!empty($result)) {
        $options = array_combine(
          array_column($result, 'id'),
          array_column($result, 'name')
        );
      }
    }
    catch (\Exception $e) {
      Error::logException($this->logger, $e);
    }

    $form['domain_id'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#options' => ['_current_' => $this->t('Use current domain')] + $options,
      '#title' => $this->t('Domain ID'),
      '#default_value' => $this->options['domain_id'],
    ];

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getExtras() {
    $extras = parent::getExtras();

    $domain_id = $this->options['domain_id'];

    if (empty($domain_id)) {
      return $extras;
    }

    foreach ($domain_id as &$id) {
      if ($id == '_current_') {
        $this->civicrmApi->civicrmInitialize();

        try {
          $result = $this->civicrmApi->get('Domain', [
            'current_domain' => TRUE,
          ]);

          if (!empty($result)) {
            $id = reset($result)['id'];
          }
        }
        catch (\Exception $e) {
          Error::logException($this->logger, $e);
        }
      }
    }

    $extras[] = [
      'field' => 'domain_id',
      'value' => array_unique($domain_id),
      'numeric' => TRUE,
    ];

    return $extras;
  }

}
