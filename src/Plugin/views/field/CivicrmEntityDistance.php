<?php

/**
 * @file
 * Issue: 3541996 - Provides a new "Distance" field for CiviCRM proximity filters.
 */

namespace Drupal\civicrm_entity\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Form\FormStateInterface;

/**
 * Field handler to display calculated distance from proximity filter.
 *
 * @ViewsField("civicrm_entity_distance")
 */
#[ViewsField("civicrm_entity_distance")]
class CivicrmEntityDistance extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function canExpose() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Get coordinates from proximity filter
    $center_lat = $center_lon = NULL;
    
    // Method 1: Check view storage (set by proximity filter)
    if (!empty($this->view->proximity_center)) {
      $center_lat = $this->view->proximity_center['latitude'];
      $center_lon = $this->view->proximity_center['longitude'];
    } else {
      // Method 2: Get directly from proximity filter
      $proximity_filter = $this->getProximityFilter();
      if ($proximity_filter && method_exists($proximity_filter, 'getProximityValues')) {
        $values = $proximity_filter->getProximityValues();
        if (!empty($values['latitude']) && !empty($values['longitude'])) {
          $center_lat = $values['latitude'];
          $center_lon = $values['longitude'];
        }
      }
    }

    if ($center_lat && $center_lon) {
      // Get the correct table alias for civicrm_address
      $address_table = 'contact_id_civicrm_contact';
      
      // Create distance formula with proper table alias
      $formula = $this->getDistanceFormula($center_lat, $center_lon, $address_table);
      
      // Add the field to the query
      $this->field_alias = $this->query->addField(NULL, $formula, 'distance_calculated');
      $this->addAdditionalFields();
    }
  }

  /**
   * Get the proximity filter from the current view.
   */
  protected function getProximityFilter() {
    $filters = $this->view->display_handler->getHandlers('filter');
    
    foreach ($filters as $filter_id => $filter) {
      if ($filter->getPluginId() == 'civicrm_entity_civicrm_address_proximity') {
        return $filter;
      }
    }
    
    return NULL;
  }

  /**
   * Calculate distance formula with proper table alias and units.
   */
  protected function getDistanceFormula($center_lat, $center_lon, $address_table) {
    // Make sure we have a table alias
    if (empty($address_table)) {
      $address_table = 'civicrm_address';
    }
    
    // Determine the unit to use
    $unit = $this->getDistanceUnit();
    
    // Set earth radius based on unit
    if ($unit == 'mi' || $unit == 'miles') {
      $earth_radius = 3958.8; // Earth radius in miles
    } else {
      $earth_radius = 6378.137; // Earth radius in kilometers
    }
    
    // Distance formula with proper units
    $formula = "
      (ACOS(
        COS(RADIANS({$address_table}.geo_code_1)) *
        COS(RADIANS($center_lat)) *
        COS(RADIANS({$address_table}.geo_code_2) - RADIANS($center_lon)) +
        SIN(RADIANS({$address_table}.geo_code_1)) *
        SIN(RADIANS($center_lat))
      ) * $earth_radius)
    ";
    
    return $formula;
  }

  /**
   * Get the distance unit to use for calculations.
   */
  protected function getDistanceUnit() {
    // Priority 1: Field configuration
    if (!empty($this->options['unit'])) {
      return $this->options['unit'];
    }
    
    // Priority 2: Proximity filter settings
    if (!empty($this->view->proximity_center['distance_unit'])) {
      $proximity_unit = $this->view->proximity_center['distance_unit'];
      // Convert proximity filter units to field units
      if ($proximity_unit == 'miles') {
        return 'mi';
      } elseif ($proximity_unit == 'kilometers') {
        return 'km';
      }
    }
    
    // Priority 3: Get from proximity filter directly
    $proximity_filter = $this->getProximityFilter();
    if ($proximity_filter && !empty($proximity_filter->value['distance_unit'])) {
      $proximity_unit = $proximity_filter->value['distance_unit'];
      if ($proximity_unit == 'miles') {
        return 'mi';
      } elseif ($proximity_unit == 'kilometers') {
        return 'km';
      }
    }
    
    // Default to kilometers
    return 'km';
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    
    if ($value !== NULL && $value !== '') {
      $distance = round((float)$value, (int)($this->options['precision'] ?? 2));
      $unit = $this->getDistanceUnit();
      
      // Display unit labels
      $unit_label = ($unit == 'mi' || $unit == 'miles') ? 'mi' : 'km';
      
      return $distance . ' ' . $unit_label;
    }
    
    return '';
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['unit'] = ['default' => '']; // Empty = use proximity filter unit
    $options['precision'] = ['default' => 2];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    
    $form['unit'] = [
      '#type' => 'select',
      '#title' => $this->t('Distance unit'),
      '#description' => $this->t('Override the unit from the proximity filter. Leave empty to use the proximity filter\'s unit setting.'),
      '#options' => [
        '' => $this->t('- Use proximity filter unit -'),
        'km' => $this->t('Kilometers'),
        'mi' => $this->t('Miles'),
      ],
      '#default_value' => $this->options['unit'],
    ];
    
    $form['precision'] = [
      '#type' => 'number',
      '#title' => $this->t('Decimal places'),
      '#description' => $this->t('Number of decimal places to display.'),
      '#default_value' => $this->options['precision'],
      '#min' => 0,
      '#max' => 5,
    ];
  }
}