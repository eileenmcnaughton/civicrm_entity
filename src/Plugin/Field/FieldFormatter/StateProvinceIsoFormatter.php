<?php

namespace Drupal\civicrm_entity\Plugin\Field\FieldFormatter;

use Drupal\civicrm_entity\CiviCrmApiInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The 'civicrm_entity_state_province_iso_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "civicrm_entity_state_province_iso",
 *   label = @Translation("State province ISO"),
 *   field_types = {
 *     "list_integer"
 *   }
 * )
 */
class StateProvinceIsoFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return parent::isApplicable($field_definition) && $field_definition->getName() === 'state_province_id';
  }

  /**
   * The CiviCRM API.
   *
   * @var \Drupal\civicrm_entity\CiviCrmApiInterface
   */
  protected $civicrmApi;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, CiviCrmApiInterface $civicrm_api) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->civicrmApi = $civicrm_api;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('civicrm_entity.api')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $value = $item->value;

      try {
        $values = $this->civicrmApi->get('StateProvince', [
          'sequential' => 1,
          'return' => ['abbreviation'],
          'id' => $value,
        ]);

        $value = $values[0]['abbreviation'];
      }
      catch (\CiviCRM_API3_Exception $e) {
        // Don't do anything.
      }

      $elements[$delta]['#markup'] = $value;
    }

    return $elements;
  }

}
