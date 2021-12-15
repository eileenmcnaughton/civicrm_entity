<?php

namespace Drupal\Tests\civicrm_entity\Kernel\Handler;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\civicrm_entity\Traits\CivicrmEntityTrait;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Tests\ViewTestData;

/**
 * Test base for views kernel test.
 */
abstract class KernelHandlerTestBase extends ViewsKernelTestBase {

  use CivicrmEntityTrait;

  public static $modules = [
    'system',
    'user',
    'civicrm',
    'civicrm_entity',
    'field',
    'filter',
    'text',
    'options',
    'link',
    'datetime',
    'civicrm_entity_test_views',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    KernelTestBase::setUp();
    $this->setUpCivicrm();

    $this->installSchema('system', ['sequences']);
    $this->setUpFixtures();

    if ($import_test_views) {
      ViewTestData::createTestViews(static::class, ['civicrm_entity_test_views']);
    }
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
