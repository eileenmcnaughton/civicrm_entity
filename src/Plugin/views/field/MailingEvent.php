<?php

namespace Drupal\civicrm_entity\Plugin\views\field;

use Drupal\civicrm_entity\CiviCrmApiInterface;
use Drupal\Core\Database\Connection;
use Drupal\views\Attribute\ViewsField;
use Drupal\views\Plugin\views\field\NumericField;
use Drupal\views\ResultRow;

/**
 * Class for MailingJobOpened.
 */
#[ViewsField("civicrm_entity_mailing_event")]
class MailingEvent extends NumericField {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The CiviCRM API.
   *
   * @var \Drupal\civicrm_entity\CiviCrmApiInterface
   */
  protected $civicrmApi;

  /**
   * Constructs a MailingEvent object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\civicrm_entity\CiviCrmApiInterface $civicrm_api
   *   The CiviCRM API bridge.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, CiviCrmApiInterface $civicrm_api, Connection $connection) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->civicrmApi = $civicrm_api;
    $this->database = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(ResultRow $values, $field = NULL) {
    $value = parent::getValue($values, $field);

    if (!class_exists($this->definition['bao'])) {
      $this->civicrmApi->civicrmInitialize();
    }

    $bao = $this->definition['bao'];
    $count = $this->definition['distinct'] ? $bao::getTotalCount($value, NULL, TRUE) : $bao::getTotalCount($value);

    return $count ? $count : 0;
  }

}
