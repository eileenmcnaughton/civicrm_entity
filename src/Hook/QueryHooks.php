<?php

namespace Drupal\civicrm_entity\Hook;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\Query\AlterableInterface;
use Drupal\Core\Database\Query\SelectInterface;
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
      $where = NULL;
      $class = get_class($query);
      if ($class == 'Drupal\search_api\Plugin\views\query\SearchApiQuery' && method_exists($query, "getWhere")) {
        $where = &$query->getWhere();
      }
      elseif (isset($query->where)) {
        $where = &$query->where;
      }
      if (!empty($where)) {
        $this->localizeWhereConditions($where, $affectedColumns, $dbLocale);
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
   * Rewrites localized CiviCRM fields in a Views WHERE tree.
   *
   * @param array $where
   *   The Views WHERE groups.
   * @param string[] $affected_columns
   *   The multilingual base columns keyed as "table.column".
   * @param string $db_locale
   *   The active CiviCRM DB locale suffix.
   */
  protected function localizeWhereConditions(array &$where, array $affected_columns, string $db_locale): void {
    foreach ($where as &$condition_group) {
      if (!empty($condition_group['conditions']) && is_array($condition_group['conditions'])) {
        $this->localizeConditionTree($condition_group['conditions'], $affected_columns, $db_locale);
      }
    }
  }

  /**
   * Recursively rewrites localized fields inside a condition tree.
   *
   * @param array $conditions
   *   The condition tree.
   * @param string[] $affected_columns
   *   The multilingual base columns keyed as "table.column".
   * @param string $db_locale
   *   The active CiviCRM DB locale suffix.
   */
  protected function localizeConditionTree(array &$conditions, array $affected_columns, string $db_locale): void {
    foreach ($conditions as &$condition) {
      if (!is_array($condition) || !array_key_exists('field', $condition)) {
        continue;
      }

      if (is_object($condition['field']) && method_exists($condition['field'], 'conditions')) {
        $nested_conditions = &$condition['field']->conditions();
        $this->localizeConditionTree($nested_conditions, $affected_columns, $db_locale);
        continue;
      }

      if (is_string($condition['field'])) {
        $condition['field'] = $this->localizeConditionField($condition['field'], $affected_columns, $db_locale);
      }
    }
  }

  /**
   * Rewrites a localized field reference if needed.
   *
   * @param string $field
   *   The current field expression.
   * @param string[] $affected_columns
   *   The multilingual base columns keyed as "table.column".
   * @param string $db_locale
   *   The active CiviCRM DB locale suffix.
   *
   * @return string
   *   The localized field expression.
   */
  protected function localizeConditionField(string $field, array $affected_columns, string $db_locale): string {
    foreach ($affected_columns as $affected_column) {
      $localized_column = $affected_column . $db_locale;

      if (strpos($field, $localized_column) !== FALSE) {
        continue;
      }

      if (strpos($field, $affected_column) !== FALSE) {
        return str_replace($affected_column, $localized_column, $field);
      }
    }

    return $field;
  }

  /**
   * Implements hook_query_TAG_alter().
   */
  #[Hook('query_pathauto_bulk_update_alter')]
  public function queryPathautoBulkUpdateAlter(AlterableInterface $query): void {
    assert($query instanceof SelectInterface);
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
    assert($query instanceof SelectInterface);
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
