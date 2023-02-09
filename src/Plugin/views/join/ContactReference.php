<?php

namespace Drupal\civicrm_entity\Plugin\views\join;

use Drupal\civicrm_entity\CiviCrmApiInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\views\Plugin\views\join\JoinPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Join handler for relationships for "Contact Reference" data type.
 *
 * @ingroup views_join_handlers
 * @ViewsJoin("civicrm_entity_contact_reference")
 */
class ContactReference extends JoinPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The CiviCRM API.
   *
   * @var \Drupal\civicrm_entity\CiviCrmApiInterface
   */
  protected $civicrmApi;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, CiviCrmApiInterface $civicrm_api) {
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
   * {@inheritdoc}
   */
  public function buildJoin($select_query, $table, $view_query) {
    if (empty($this->configuration['table formula'])) {
      $right_table = $this->table;
    }
    else {
      $right_table = $this->configuration['table formula'];
    }

    if ($this->leftTable) {
      $left_table = $view_query->getTableInfo($this->leftTable);
      $left_field = $this->leftFormula ?: "$left_table[alias].$this->leftField";
    }
    else {
      // This can be used if left_field is a formula or something.
      // It should be used only *very* rarely.
      $left_field = $this->leftField;
      $left_table = NULL;
    }

    $this->civicrmApi->civicrmInitialize();

    $condition = "CAST($left_field AS BINARY) RLIKE BINARY CONCAT('" . \CRM_Core_DAO::VALUE_SEPARATOR . "', " . "$table[alias].$this->field" . ", '" . \CRM_Core_DAO::VALUE_SEPARATOR . "')";

    $arguments = [];

    // Tack on the extra.
    if (isset($this->extra)) {
      $this->joinAddExtra($arguments, $condition, $table, $select_query, $left_table);
    }

    $select_query->addJoin($this->type, $right_table, $table['alias'], $condition, $arguments);
  }

}
