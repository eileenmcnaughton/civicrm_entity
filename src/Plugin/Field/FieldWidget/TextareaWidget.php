<?php

namespace Drupal\civicrm_entity\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\text\Plugin\Field\FieldWidget\TextareaWidget as CoreTextareaWidget;

/**
 * Plugin implementation of the 'text_textarea' widget.
 *
 * @FieldWidget(
 *   id = "civicrm_entity_textarea",
 *   label = @Translation("Text area (multiple rows)"),
 *   field_types = {
 *     "text_long"
 *   }
 * )
 */
class TextareaWidget extends CoreTextareaWidget {

  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $formats = filter_formats();
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // @todo Load the format from the config factory.
    // Allow CiviCRM to configure a default format to be used for text
    // fields. Currently we pick the default which would display on a new
    // field.
    $element['#allowed_formats'] = [
      reset($formats)->id()
    ];
    return $element;
  }

}
