<?php

namespace Drupal\civicrm_entity\Plugin\views\relationship;

use Drupal\views\Plugin\ViewsHandlerManager;
use Drupal\civicrm_entity\CiviCrmApiInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Reverse CiviCRM entity reference locations.
 *
 * @ingroup views_relationship_handlers
 *
 * @ViewsRelationship("civicrm_entity_reverse_location")
 */
class EntityReverseLocation extends EntityReverse {

  /**
   * An array of CiviCRM location.
   *
   * @var array
   */
  protected $locations = [];

  /**
   * The default CiviCRM location.
   *
   * @var int|null
   */
  protected $defaultLocation = NULL;

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
    $this->defaultLocation = \CRM_Core_BAO_LocationType::getDefault()->id;
    $this->locations = \CRM_Core_BAO_Address::buildOptions('location_type_id');

    $this->definition['extra'] = [];
    if (!empty($this->options['location_type'])) {
      $this->definition['extra'][] = [
        'field' => 'location_type_id',
        'value' => (int) ($this->options['location_type'] == 'default' ? $this->defaultLocation : $this->options['location_type']),
        'numeric' => TRUE,
      ];
    }
    if (!empty($this->options['is_primary'])) {
      $this->definition['extra'][] = [
        'field' => 'is_primary',
        'value' => $this->options['is_primary'],
        'numeric' => TRUE,
      ];
    }
    if (!empty($this->options['is_billing'])) {
      $this->definition['extra'][] = [
        'field' => 'is_billing',
        'value' => $this->options['is_billing'],
        'numeric' => TRUE,
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['location_type'] = ['default' => 0];
    $options['is_billing'] = ['default' => FALSE, 'bool' => TRUE];
    $options['is_primary'] = ['default' => FALSE, 'bool' => TRUE];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['is_primary'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Is primary?'),
      '#default_value' => $this->options['is_primary'] ?? FALSE,
      '#weight' => -4,
    ];
    $form['is_billing'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Is billing?'),
      '#default_value' => $this->options['is_billing'] ?? FALSE,
      '#weight' => -3,
    ];
    $form['location_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Location type'),
      '#options' => [
        0 => $this->t('Any'),
        'default' => $this->t('Default location (@default)', ['@default' => $this->locations[$this->defaultLocation]]),
      ],
      '#default_value' => isset($this->options['location_type']) ? (int) $this->options['location_type'] : 0,
      '#weight' => -2,
    ];

    foreach ($this->locations as $id => $location) {
      $form['location_type']['#options'][$id] = $location;
    }

    parent::buildOptionsForm($form, $form_state);
  }

}
