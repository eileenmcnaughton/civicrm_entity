<?php

namespace Drupal\civicrm_entity\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\text\Plugin\Field\FieldWidget\TextareaWidget as CoreTextareaWidget;

/**
 * Plugin implementation of the 'text_textarea' widget.
 *
 * @FieldWidget(
 *   id = "civicrm_entity_textarea",
 *   label = @Translation("Text area (multiple rows, default CiviCRM format)"),
 *   field_types = {
 *     "text_long"
 *   }
 * )
 */
class TextareaWidget extends CoreTextareaWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $config = \Drupal::config('civicrm_entity.settings');

    $element['#allowed_formats'] = [
      $config->get('filter_format') ?: filter_fallback_format(),
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
