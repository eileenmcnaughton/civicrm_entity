/**
 * @file
 * civicrm_entity.states.js
 */

(function ($, Drupal) {

  /**
   * Sets states depending on the chosen country.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   */
  Drupal.behaviors.civicrmEntityStates = {
    attach(context, settings) {
      $(once('loadStates', '.views-exposed-form [data-drupal-selector="edit-country-id"]', context)).on('change', function() {
        var countries = $(this).val();

        if (countries != Drupal.t('All')) {
          var $stateElement = $('[data-drupal-selector="edit-state-province-id"]', $(this).parents('form'));
          $stateElement.find('option').not(':first').remove();
          countries = Array.isArray(countries) ? countries : [countries];
          countries.forEach((v) => {
            $stateElement.append(settings.civicrm_entity.states[v]);
          });
        }
      }).trigger('change');
    },
  };

})(jQuery, Drupal, drupalSettings);
