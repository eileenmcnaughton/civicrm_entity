<?php

namespace Drupal\civicrm_entity;

use Drupal\Core\DrupalKernelInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ModuleInstaller as ExtensionModuleInstaller;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Database\Connection;
use Drupal\Core\Update\UpdateHookRegistry;

/**
 * Class ContentUninstallValidator.
 */
class ModuleInstaller extends ExtensionModuleInstaller {

  /**
   * The module installer.
   *
   * @var \Drupal\Core\Extension\ModuleInstallerInterface
   */
  protected $moduleInstaller;

  /**
   * {@inheritdoc}
   */
  public function __construct(ModuleInstallerInterface $module_installer, $root, ModuleHandlerInterface $module_handler, DrupalKernelInterface $kernel, Connection $connection, UpdateHookRegistry $update_registry) {
    parent::__construct($root, $module_handler, $kernel, $connection, $update_registry);
    $this->moduleInstaller = $module_installer;
  }

  /**
   * {@inheritdoc}
   */
  public function validateUninstall(array $module_list) {
    $reasons = parent::validateUninstall($module_list);

    unset($reasons['civicrm_entity']);

    return $reasons;
  }

}
