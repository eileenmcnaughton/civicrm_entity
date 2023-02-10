<?php

namespace Drupal\Tests\civicrm_entity\Traits;

use Civi\Setup\Event\InstallFilesEvent;
use Civi\Setup;
use Drupal\Core\Database\Database;

/**
 * Provides common methods for Civicrm Entity module tests.
 */
trait CivicrmEntityTrait {

  /**
   * Setup CiviCRM.
   */
  protected function setUpCivicrm() {
    define('CIVICRM_CONTAINER_CACHE', 'never');
    define('CIVICRM_TEST', 'never');

    // Add file_private_path, required to properly set templateCompilePath.
    $file_private_path = $this->siteDirectory . 'files/private';
    $this->setSetting('file_private_path', $file_private_path);
    mkdir($file_private_path, 0775);

    \Drupal::moduleHandler()->loadInclude('civicrm', 'install');
    $setup = _civicrm_setup();
    $installed = $setup->checkInstalled();
    if ($installed->isSettingInstalled() || $installed->isDatabaseInstalled()) {
      throw new \Exception("CiviCRM appears to have already been installed. Skipping full installation.");
    }

    Setup::dispatcher()
      ->addListener('civi.setup.installFiles', function (InstallFilesEvent $e) use ($file_private_path) {
        $model = $e->getModel();
        $model->settingsPath = implode(DIRECTORY_SEPARATOR, [
          $this->siteDirectory,
          'civicrm.settings.php',
        ]);
        $model->templateCompilePath = implode(DIRECTORY_SEPARATOR, [
          $file_private_path,
          'civicrm',
          'templates_c',
        ]);
      }, 900);

    $setup->installFiles();
    $setup->installDatabase();

    // @todo we need an event subscriber to rebuild definitions if this saves.
    $this->config('civicrm_entity.settings')
      ->set('enabled_entity_types', [
        'civicrm_event',
        'civicrm_address',
        'civicrm_contact',
      ])->save();
    $this->container->get('entity_type.manager')->clearCachedDefinitions();
  }

  /**
   * Boot environment for CiviCRM.
   */
  protected function bootEnvironmentCivicrm() {
    $connection_info = Database::getConnectionInfo('default');
    // CiviCRM does not leverage table prefixes, so we unset it. This way any
    // `civicrm_` tables are more easily cleaned up at the end of the test.
    $civicrm_connection_info = $connection_info['default'];
    unset($civicrm_connection_info['prefix']);
    Database::addConnectionInfo('civicrm_test', 'default', $civicrm_connection_info);
    Database::addConnectionInfo('civicrm', 'default', $civicrm_connection_info);

    // Assert that there are no `civicrm_` tables in the test database.
    $connection = Database::getConnection('default', 'civicrm_test');
    $schema = $connection->schema();
    $tables = $schema->findTables('civicrm_%');
    if (count($tables) > 0) {
      throw new \RuntimeException('The provided database connection in SIMPLETEST_DB contains CiviCRM tables, use a different database.');
    }
  }

  /**
   * Tear down for CiviCRM.
   */
  protected function tearDownCivicrm() {
    $civicrm_test_conn = Database::getConnection('default', 'civicrm_test');
    // Disable foreign key checks so that tables can be dropped.
    $civicrm_test_conn->query('SET FOREIGN_KEY_CHECKS = 0;')->execute();
    $civicrm_schema = $civicrm_test_conn->schema();
    $tables = $civicrm_schema->findTables('%');
    // Comment out if you want to view the tables/contents before deleting them
    // throw new \Exception(var_export($tables, TRUE));.
    foreach ($tables as $table) {
      if ($civicrm_schema->dropTable($table)) {
        unset($tables[$table]);
      }
    }
    $civicrm_test_conn->query('SET FOREIGN_KEY_CHECKS = 1;')->execute();
    parent::tearDown();
  }

}
