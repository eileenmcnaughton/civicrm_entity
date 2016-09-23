jQuery(document).ready(function ($) {
  $('.form-item-registration-form-pay-later input').click(function () {
    $('.civicrm-entity-price-set-field-cc-block').toggle();
  });
});

(function ($, Drupal) {
  // Our function name is prototyped as part of the Drupal.ajax namespace, adding to the commands:
  Drupal.ajax.prototype.commands.afterPriceSetDisplayFormAjaxReplaceCallback = function (ajax, response, status) {
    if ($('.form-item-registration-form-pay-later input').is(':checked')) {
      $('.civicrm-entity-price-set-field-cc-block').css("display", "none");
    }
    $('.form-item-registration-form-pay-later input').click(function () {
      $('.civicrm-entity-price-set-field-cc-block').toggle();
    });

  };


}(jQuery, Drupal));