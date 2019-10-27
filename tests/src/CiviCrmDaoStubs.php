<?php
// @codingStandardsIgnoreFile

/**
 * Database access object for the Contact entity.
 */
class CRM_Contact_DAO_Contact {

  /**
   * Static instance to hold the table name.
   *
   * @var string
   */
  public static $_tableName = 'civicrm_contact';

  /**
   * Returns the names of this table
   *
   * @return string
   */
  public static function getTableName() {
    return self::$_tableName;
  }

}

/**
 * Database access object for the LocBlock entity.
 */
class CRM_Core_DAO_LocBlock {

  /**
   * Static instance to hold the table name.
   *
   * @var string
   */
  public static $_tableName = 'civicrm_loc_block';

  /**
   * Returns the names of this table
   *
   * @return string
   */
  public static function getTableName() {
    return self::$_tableName;
  }

}

/**
 * Database access object for the MessageTemplate entity.
 */
class CRM_Core_DAO_MessageTemplate {

  /**
   * Static instance to hold the table name.
   *
   * @var string
   */
  public static $_tableName = 'civicrm_msg_template';

  /**
   * Returns the names of this table
   *
   * @return string
   */
  public static function getTableName() {
    return self::$_tableName;
  }

}

/**
 * Database access object for the Campaign entity.
 */
class CRM_Campaign_DAO_Campaign {

  /**
   * Static instance to hold the table name.
   *
   * @var string
   */
  public static $_tableName = 'civicrm_campaign';

  /**
   * Returns the names of this table
   *
   * @return string
   */
  public static function getTableName() {
    return self::$_tableName;
  }

}

/**
 * Database access object for the RuleGroup entity.
 */
class CRM_Dedupe_DAO_RuleGroup {

  /**
   * Static instance to hold the table name.
   *
   * @var string
   */
  public static $_tableName = 'civicrm_dedupe_rule_group';

  /**
   * Returns the names of this table
   *
   * @return string
   */
  public static function getTableName() {
    return self::$_tableName;
  }

}
