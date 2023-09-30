<?php

namespace Drupal\civicrm_entity\Plugin\search_api\datasource;

use Drupal\Core\Database\Database;
use Drupal\search_api\Plugin\search_api\datasource\ContentEntity;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CivicrmEntity extends ContentEntity {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $datasource = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $civicrm_connection_name = drupal_valid_test_ua() ? 'civicrm_test' : 'civicrm';
    $civicrm_database_info = Database::getConnectionInfo($civicrm_connection_name);
    if (isset($civicrm_database_info['default'])) {
      $datasource->setDatabaseConnection(Database::getConnection('default', $civicrm_connection_name));
    }

    return $datasource;
  }

}
