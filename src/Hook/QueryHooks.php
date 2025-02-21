<?php

namespace Drupal\civicrm_entity\Hook;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\Query\AlterableInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ViewExecutable;

/**
 * Hook implementations for queries.
 */
class QueryHooks {

  /**
   * Implements hook_views_query_alter().
   */
  #[Hook('views_query_alter')]
  public function viewsQueryAlter(ViewExecutable $view, QueryPluginBase $query) {
    // Provide fully qualified table name in all Views queries.
    // If CiviCRM tables in a separate database.
    $civicrm_connection_name = drupal_valid_test_ua() ? 'civicrm_test' : 'civicrm';
    $civicrm_database_info = Database::getConnectionInfo($civicrm_connection_name);
    if (isset($civicrm_database_info['default']) && method_exists($query, "getTableQueue")) {
      $civicrm_connection = Database::getConnection('default', $civicrm_connection_name);
      $table_queue =& $query->getTableQueue();
      foreach ($table_queue as $alias => &$table_info) {
        if (!empty($table_info['table']) && ((strpos($table_info['table'], 'civicrm_') === 0 && strpos($table_info['table'], '.') === FALSE && strpos($table_info['table'], '__') === FALSE) || strpos($table_info['table'], 'civicrm_value_') === 0)) {
          $table_info['table'] = $civicrm_connection->getFullQualifiedTableName($table_info['table']);
        }
        if (!empty($table_info['join']->table) && ((strpos($table_info['join']->table, 'civicrm_') === 0 && strpos($table_info['join']->table, '.') === FALSE && strpos($table_info['join']->table, '__') === FALSE) || strpos($table_info['join']->table, 'civicrm_value_') === 0)) {
          $table_info['join']->table = $civicrm_connection->getFullQualifiedTableName($table_info['join']->table);
        }
      }
    }

    \Drupal::service('civicrm')->initialize();
    $multilingual = \CRM_Core_I18n::isMultilingual();

    if ($multilingual) {
      // @codingStandardsIgnoreStart
      global $dbLocale;
      // @codingStandardsIgnoreEnd
      $columns = \CRM_Core_I18n_SchemaStructure::columns();
      $affectedColumns = [];
      foreach ($columns as $table => $hash) {
        foreach (array_keys($hash) as $column) {
          $affectedColumns[] = "{$table}.{$column}";
        }
      }
      $class = get_class($query);
      if ($class == 'Drupal\search_api\Plugin\views\query\SearchApiQuery' && method_exists($query, "getWhere")) {
        $where = $query->getWhere();
      }
      elseif (isset($query->where)) {
        $where = $query->where;
      }
      if (!empty($where)) {
        foreach ($where as &$condition_group) {
          foreach ($condition_group['conditions'] as &$condition) {
            if (!is_object($condition['field'])) {
              foreach ($affectedColumns as $aff_column) {
                if (strpos($aff_column, $condition['field']) !== FALSE) {
                  $condition['field'] = str_replace($aff_column, $aff_column . $dbLocale, $condition['field']);
                }
              }
            }
          }
        }
      }

      if (!empty($query->fields)) {
        foreach ($query->fields as &$field) {
          if (array_key_exists($field['table'], $columns) && array_key_exists($field['field'], $columns[$field['table']])) {
            $field['field'] .= $dbLocale;
          }
        }
      }
    }
  }

  /**
   * Implements hook_query_TAG_alter().
   */
  #[Hook('query_pathauto_bulk_update_alter')]
  public function queryPathautoBulkUpdateAlter(AlterableInterface $query): void {
    $tables = &$query->getTables();

    if (strpos($tables['base_table']['table'], 'civicrm_') !== FALSE) {
      $civicrm_connection_name = drupal_valid_test_ua() ? 'civicrm_test' : 'civicrm';
      $civicrm_database_info = Database::getConnectionInfo($civicrm_connection_name);
      if (isset($civicrm_database_info['default'])) {
        $connection = Database::getConnection('default', $civicrm_connection_name);
        $tables['base_table']['table'] = $connection->getFullQualifiedTableName($tables['base_table']['table']);
      }
    }
  }

  /**
   * Implements hook_query_TAG_alter().
   */
  #[Hook('query_pathauto_bulk_delete_alter')]
  public function queryPathautoBulkDeleteAlter(AlterableInterface $query): void {
    $tables = &$query->getTables();

    if (strpos($tables['base_table']['table'], 'civicrm_') !== FALSE) {
      $civicrm_connection_name = drupal_valid_test_ua() ? 'civicrm_test' : 'civicrm';
      $civicrm_database_info = Database::getConnectionInfo($civicrm_connection_name);
      if (isset($civicrm_database_info['default'])) {
        $connection = Database::getConnection('default', $civicrm_connection_name);
        $tables['base_table']['table'] = $connection->getFullQualifiedTableName($tables['base_table']['table']);
      }
    }
  }

}
