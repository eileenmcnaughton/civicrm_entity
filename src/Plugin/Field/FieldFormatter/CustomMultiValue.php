<?php

namespace Drupal\civicrm_entity\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\options\Plugin\Field\FieldFormatter\OptionsDefaultFormatter;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\civicrm_entity\CiviCrmApiInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'civicrm_entity_custom_multi_value' formatter.
 *
 * @FieldFormatter(
 *   id = "civicrm_entity_custom_multi_value",
 *   label = @Translation("CiviCRM custom multivalue field"),
 *   field_types = {
 *     "list_integer",
 *     "list_float",
 *     "list_string",
 *   }
 * )
 */
class CustomMultiValue extends OptionsDefaultFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return ['separator' => ', '] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['separator'] = [
      '#title' => $this->t('Separator'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('separator'),
    ];

    return $element;
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
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    if (($field_metadata = $field_definition->getSetting('civicrm_entity_field_metadata')) && $field_metadata['option_group_id']) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    try {
      $entity = $items->getEntity();

      $result = $this->civicrmApi->get('CustomValue', [
        'sequential' => 1,
        'return' => [$this->fieldDefinition->getName()],
        'entity_id' => $entity->id(),
        'entity_table' => $entity->getEntityTypeId(),
      ]);

      if (!empty($result)) {
        $result = reset($result);
        $field_id = $result['id'];

        $result = array_filter($result, function ($key) {
          return is_int($key);
        }, ARRAY_FILTER_USE_KEY);

        foreach ($result as &$value) {
          $value = \CRM_Core_BAO_CustomField::displayValue($value, $field_id, $entity->id());
          $value = is_array($value) ? implode($this->getSetting('separator'), $value) : $value;
        }

        // @todo Provide a workaround for element values for now until we can
        // have another way to handle multi-group, multi-value CiviCRM fields.
        foreach ($result as $delta => $item) {
          $elements[$delta] = [
            '#markup' => $item,
            '#allowed_tags' => FieldFilteredMarkup::allowedTags(),
          ];
        }
      }
    }
    catch (\CiviCRM_API3_Exception $e) {
      // Don't do anything.
    }

    return $elements;
  }

}
