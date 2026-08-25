<?php

namespace Drupal\civicrm_entity\Plugin\Field\FieldWidget;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\filter\FilterFormatRepositoryInterface;
use Drupal\text\Plugin\Field\FieldWidget\TextareaWidget as CoreTextareaWidget;

/**
 * Plugin implementation of the 'text_textarea' widget.
 */
#[FieldWidget(
  id: "civicrm_entity_textarea",
  label: new TranslatableMarkup("Text area (multiple rows, default CiviCRM format 1)"),
  field_types: [
    "text_long",
  ]
)]
class TextareaWidget extends CoreTextareaWidget {

  /**
   * Constructs a CiviCRM Entity textarea widget.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    protected ConfigFactoryInterface $configFactory,
    protected FilterFormatRepositoryInterface $filterFormatRepository,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $config = $this->configFactory->get('civicrm_entity.settings');

    $element['#allowed_formats'] = [
      $config->get('filter_format')
        ?: $this->filterFormatRepository->getFallbackFormatId(),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return strpos($field_definition->getTargetEntityTypeId(), 'civicrm_') !== FALSE && $field_definition->getFieldStorageDefinition()->isBaseField();
  }

}
