<?php

namespace Drupal\civicrm_entity;

use Drupal\Core\Extension\ModuleInstaller as ExtensionModuleInstaller;

/**
 * Class for ModuleInstaller.
 */
class ModuleInstaller extends ExtensionModuleInstaller {

  /**
   * {@inheritdoc}
   */
  public function validateUninstall(array $module_list) {
    $reasons = parent::validateUninstall($module_list);

    unset($reasons['civicrm_entity']);

    return $reasons;
  }

}
