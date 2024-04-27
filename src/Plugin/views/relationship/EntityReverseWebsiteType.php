<?php

namespace Drupal\civicrm_entity\Plugin\views\relationship;

use Drupal\civicrm_entity\CiviCrmApiInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\ViewsHandlerManager;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Reverse CiviCRM entity reference types for website.
 *
 * @ingroup views_relationship_handlers
 *
 * @ViewsRelationship("civicrm_entity_reverse_website_type")
 */
class EntityReverseWebsiteType extends EntityReverse {

  /**
   * An array of CiviCRM website types.
   *
   * @var array
   */
  protected $websiteTypes = [];

  /**
   * The default CiviCRM website type.
   *
   * @var int|null
   */
  protected $defaultType = NULL;

  /**
   * The CiviCRM API.
   *
   * @var \Drupal\civicrm_entity\CiviCrmApiInterface
   */
  protected $civicrmApi;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ViewsHandlerManager $join_manager, CiviCrmApiInterface $civicrm_api) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $join_manager);
    $this->civicrmApi = $civicrm_api;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.views.join'),
      $container->get('civicrm_entity.api')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->civicrmApi->civicrmInitialize();
    $this->websiteTypes = \CRM_Core_BAO_Website::buildOptions('website_type_id');

    if (!empty($this->options['website_type'])) {
      $this->definition['extra'][] = [
        'field' => 'website_type_id',
        'value' => $this->options['website_type'],
        'numeric' => TRUE,
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['website_type'] = ['default' => 0];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['website_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Website type'),
      '#options' => [0 => $this->t('Any')] + $this->websiteTypes,
      '#default_value' => isset($this->options['website_type']) ? (int) $this->options['website_type'] : 0,
      '#weight' => -2,
    ];

    parent::buildOptionsForm($form, $form_state);
  }

}
