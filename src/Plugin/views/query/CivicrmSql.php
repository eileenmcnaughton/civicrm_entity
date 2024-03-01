<?php

namespace Drupal\civicrm_entity\Plugin\views\query;

use Drupal\civicrm\Civicrm;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\Query\Select;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
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
   * The Cache service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheService;

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
   * Set the Cache service.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_service
   *   The Cache service.
   *
   * @note we use this pattern to avoid constructor overrides.
   */
  public function setCacheService(CacheBackendInterface $cache_service) {
    $this->cacheService = $cache_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    assert($instance instanceof self);
    $instance->setCivicrm($container->get('civicrm'));
    $instance->setCacheService($container->get('cache.data'));
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

  /**
   * {@inheritdoc}
   */
  public function query($get_count = FALSE) {
    $query = parent::query($get_count);
    assert($query instanceof Select);

    foreach ($query->getTables() as &$table) {
      // If the table is not prefixed with civicrm_, assume it is a Drupal table
      // and convert it to a fully qualified table name. But, make sure it has
      // not already been converted.
      // Also do not convert any drupal custom fields.
      if ((strpos($table['table'], 'civicrm_') !== 0 && strpos($table['table'], '.') === FALSE) || ((strpos($table['table'], 'civicrm_') === 0 && strpos($table['table'], '__') !== FALSE)) || strpos($table['table'], 'civicrm_value_') === 0) {
        $cache_key = "ce-qualified-table-names:$table_name";
        if ($cache = $this->cacheService->get($cache_key)) {
          $table['table'] = $cache->data;
        }
        else {
          $connection = Database::getConnection();
          $table['table'] = $connection->getFullQualifiedTableName($table['table']);
          $this->cacheService->set($cache_key, $table['table']);
        }
      }
    }
    return $query;
  }

}
