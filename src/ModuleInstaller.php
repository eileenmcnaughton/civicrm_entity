<?php

namespace Drupal\civicrm_entity;

use Drupal\Core\DrupalKernelInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ModuleInstaller as ExtensionModuleInstaller;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Update\UpdateHookRegistry;

/**
 * Class ContentUninstallValidator.
 */
#[AsDecorator(decorates: ExtensionModuleInstaller::class)]
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
