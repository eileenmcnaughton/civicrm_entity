<?php

namespace Drupal\Tests\civicrm_entity\Kernel\Handler;

use Drupal\Tests\civicrm_entity\Traits\CivicrmEntityTrait;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;

/**
 * Test base for views kernel test.
 */
abstract class KernelHandlerTestBase extends ViewsKernelTestBase {

  use CivicrmEntityTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp();
    var_dump($this->siteDirectory);
    $this->setUpCivicrm();
  }

  /**
   * {@inheritdoc}
   */
  protected function bootEnvironment() {
    parent::bootEnvironment();
    $this->bootEnvironmentCivicrm();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    $this->tearDownCivicrm();
  }

}
