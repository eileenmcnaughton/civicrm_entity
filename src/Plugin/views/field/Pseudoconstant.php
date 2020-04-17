<?php

namespace Drupal\civicrm_entity\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\civicrm_entity\CiviCrmApiInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Display CiviCRM pseudoconstant.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("civicrm_entity_pseudoconstant")
 */
class Pseudoconstant extends FieldPluginBase {

  /**
   * An array of values.
   *
   * @var array|mixed
   */
  protected $pseudoValues = [];

  /**
   * The CiviCRM API.
   *
   * @var \Drupal\civicrm_entity\CiviCrmApiInterface
   */
  protected $civicrmApi;

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['format'] = ['default' => 'raw'];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['format'] = [
      '#type' => 'select',
      '#title' => $this->t('Display format'),
      '#description' => $this->t('Select a format to display the field.'),
      '#default_value' => $this->options['format'],
      '#options' => [
        'raw' => $this->t('Raw value'),
        'pseudoconstant' => $this->t('Human friendly'),
      ],
    ];

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, CiviCrmApiInterface $civicrm_api) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->civicrmApi = $civicrm_api;
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->civicrmApi->civicrmInitialize();

    if (isset($this->definition['pseudo callback']) && is_callable($this->definition['pseudo callback'])) {
      if (isset($this->definition['pseudo arguments'])) {
        $this->pseudoValues = call_user_func_array($this->definition['pseudo callback'], [$this->definition['pseudo arguments']]);
      }
      else {
        $this->pseudoValues = call_user_func($this->definition['pseudo callback']);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);

    if ($this->options['format'] === 'pseudoconstant') {
      $custom_field_id = \CRM_Core_DAO::getFieldValue('CRM_Core_BAO_CustomField', $this->realField, 'id', 'column_name');
      return \CRM_Core_BAO_CustomField::displayValue($value, $custom_field_id);
    }
    else {
      // Sanitize the values so that we can format it to something friendlier
      // specifically for multi-value fields.
      $values = trim($value, \CRM_Core_DAO::VALUE_SEPARATOR);
      $values = explode(\CRM_Core_DAO::VALUE_SEPARATOR, $value);

      $items = array_filter($values, function ($item) {
        return isset($this->pseudoValues[$item]) && !empty($this->pseudoValues[$item]);
      });

      if (!empty($items)) {
        return implode(', ', $items);
      }
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('civicrm_entity.api')
    );
  }

}
