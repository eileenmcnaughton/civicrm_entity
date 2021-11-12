<?php

namespace Drupal\civicrm_entity\Plugin\views\field;

use Drupal\views\Plugin\views\field\NumericField;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class for MailingJobOpened.
 *
 * @ViewsField("civicrm_entity_mailing_event")
 */
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
   * @var \Drupal\civicrm_entity\CiviCrmApi
   */
  protected $civicrmApi;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->civicrmApi = $container->get('civicrm_entity.api');
    $instance->database = $container->get('database');

    return $instance;
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
