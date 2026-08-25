<?php

namespace Drupal\civicrm_entity;

use Drupal\Core\Extension\ModuleInstaller as ExtensionModuleInstaller;

/**
 * Legacy class retained so cached pre-update containers can still bootstrap.
 *
 * New containers use Drupal core's module installer and the targeted
 * ContentUninstallValidator override instead.
 *
 * New containers use Drupal core's module installer directly. This class may
 * be removed after supported upgrade paths no longer reference old containers.
 */
class ModuleInstaller extends ExtensionModuleInstaller {

}
