<?php

namespace Drupal\civicrm_entity\Plugin\views\filter;

use Drupal\Core\Database\Connection;
use Drupal\civicrm_entity\CiviCrmApiInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\StringFilter as BaseStringFilter;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\views\Views;

/**
 * An "In" handler to include CiviCRM API.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("civicrm_entity_contact_reference")
 */
class ContactReference extends BaseStringFilter {

  /**
   * The CiviCRM API.
   *
   * @var \Drupal\civicrm_entity\CiviCrmApiInterface
   */
  protected $civicrmApi;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, Connection $connection, CiviCrmApiInterface $civicrm_api) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $connection);
    $this->civicrmApi = $civicrm_api;
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->civicrmApi->civicrmInitialize();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('civicrm_entity.api')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();

    $civicrm_contact_table = 'civicrm_contact';

    $configuration = [
      'table' => $civicrm_contact_table,
      'field' => 'id',
      'left_table' => $this->tableAlias,
      'left_field' => $this->realField,
      'operator' => '=',
    ];

    $join = Views::pluginManager('join')->createInstance('standard', $configuration);

    $civicrm_contact_table_alias = $this->query->addRelationship($civicrm_contact_table, $join, $this->tableAlias);

    $field = "$civicrm_contact_table_alias.sort_name";

    $info = $this->operators();
    if (!empty($info[$this->operator]['method'])) {
      $this->{$info[$this->operator]['method']}($field);
    }
  }

}
