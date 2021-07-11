<?php

namespace Drupal\civicrm_entity\Plugin\views\query;

use Drupal\civicrm\Civicrm;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\Query\Select;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\join\JoinPluginBase;
use Drupal\views\Plugin\views\query\Sql;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Views query plugin for a CiviCRM Entity SQL query.
 *
 * @ingroup views_query_plugins
 *
 * @ViewsQuery(
 *   id = "civicrm_views_query",
 *   title = @Translation("CiviCRM SQL Query"),
 *   help = @Translation("Query will be generated and run using the Drupal database API against the CiviCRM database.")
 * )
 */
class CivicrmSql extends Sql {

  /**
   * The CiviCRM service.
   *
   * @var \Drupal\civicrm\Civicrm
   */
  protected $civicrm;

  /**
   * Set the CiviCRM service.
   *
   * @param \Drupal\civicrm\Civicrm $civicrm
   *   The CiviCRM service.
   *
   * @note we use this pattern to avoid constructor overrides.
   */
  public function setCivicrm(Civicrm $civicrm) {
    $this->civicrm = $civicrm;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->setCivicrm($container->get('civicrm'));
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    // Ensure that Drupal is aware of the CiviCRM database connection.
    // This should be added into the settings.php, but we provide a backwards
    // compatibility layer here.
    // @todo can we get this upstream in the CiviCRM module on initialize?
    $this->civicrm->initialize();
    $connection_name = drupal_valid_test_ua() ? 'civicrm_test' : 'civicrm';
    if (!Database::getConnectionInfo($connection_name)) {
      $civicrm_connection_info = Database::convertDbUrlToConnectionInfo(CIVICRM_DSN, DRUPAL_ROOT);
      Database::addConnectionInfo($connection_name, 'default', $civicrm_connection_info);
    }
    parent::init($view, $display, $options);
  }

  public function addRelationship($alias, JoinPluginBase $join, $base, $link_point = NULL) {
    // Do not modify $base as it'll ruin aliases and other look ups down the
    // road, like when fetching Views data about the table.
    if (strpos($join->table, 'civicrm_') !== 0) {
      $connection = Database::getConnection();
//      $join->table = $connection->getFullQualifiedTableName($join->table);
    }
    return parent::addRelationship($alias, $join, $base, $link_point);
  }

  protected function adjustJoin($join, $relationship) {
    parent::adjustJoin($join, $relationship);
    if (strpos($join->table, 'civicrm_') !== 0 && strpos($join->table, '.') === FALSE) {
      $connection = Database::getConnection();
//      $join->table = $connection->getFullQualifiedTableName($join->table);
    }
    return $join;
  }

  public function query($get_count = FALSE) {
    $query = parent::query($get_count);
    assert($query instanceof Select);
    $connection = Database::getConnection();

    foreach ($query->getTables() as &$table) {
      if (strpos($table['table'], 'civicrm_') !== 0 && strpos($table['table'], '.') === FALSE) {
        $table['table'] = $connection->getFullQualifiedTableName($table['table']);
      }
      $stop = null;
    }
    return $query;
  }

}
