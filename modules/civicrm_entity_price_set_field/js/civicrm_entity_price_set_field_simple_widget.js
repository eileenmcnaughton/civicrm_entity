jQuery(document).ready(function ($) {

  $('.field-type-civicrm-entity-price-set-field #price_field_value_table .form-type-radio input')
    .click(function () {
      $('.field-type-civicrm-entity-price-set-field #price_field_value_table .form-type-radio input')
        .not(this)
        .prop('checked', false);
    });

});

(function ($, Drupal) {
  // Our function name is prototyped as part of the Drupal.ajax namespace, adding to the commands:
  Drupal.ajax.prototype.commands.afterPriceFieldAjaxReplaceCallback = function (ajax, response, status) {
    console.log(response);
    console.log('#edit-' + response.fieldName + ' .form-item-' + response.fieldName + '-und-0-price-set-price-field-' + response.selectedValue + '-is-default input');
    $('#edit-' + response.fieldName + ' #price_field_value_table .form-type-radio input')
      .prop('checked', false);
    $('#edit-' + response.fieldName + ' .form-item-' + response.fieldName + '-und-0-price-set-price-field-' + response.selectedValue + '-is-default input')
      .prop('checked', true);

    $('#edit-' + response.fieldName + ' #price_field_value_table .form-type-radio input')
      .click(function () {
        $('#edit-' + response.fieldName + ' #price_field_value_table .form-type-radio input')
          .not(this)
          .prop('checked', false);
      });
  };
}(jQuery, Drupal));