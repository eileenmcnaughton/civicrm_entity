<?php

namespace Drupal\civicrm_entity\Plugin\views\sort;

use Drupal\views\Plugin\views\sort\SortPluginBase;

/**
 * Sort handler for distance calculations.
 *
 * @ViewsSort("civicrm_entity_distance_sort")
 */
class CivicrmEntityDistanceSort extends SortPluginBase {

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
      // Use the same formula as the distance field
      $formula = $this->getDistanceFormula($center_lat, $center_lon);
      
      // Add the sort to the query
      $this->query->addOrderBy(NULL, $formula, $this->options['order']);
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
   * Get the correct table alias for CiviCRM address table.
   * Using the same hardcoded alias as the distance field.
   */
  protected function getAddressTableAlias() {
    // Use the same alias that works in the distance field
    return 'contact_id_civicrm_contact';
  }

  /**
   * Calculate distance formula (same as distance field).
   */
  protected function getDistanceFormula($center_lat, $center_lon) {
    $address_table = $this->getAddressTableAlias();
    
    // Get the unit from proximity filter or default to kilometers
    $unit = $this->getDistanceUnit();
    
    // Set earth radius based on unit
    if ($unit == 'mi' || $unit == 'miles') {
      $earth_radius = 3958.8; // Earth radius in miles
    } else {
      $earth_radius = 6378.137; // Earth radius in kilometers
    }
    
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
   * Get the distance unit (same logic as distance field).
   */
  protected function getDistanceUnit() {
    // Check proximity filter settings
    if (!empty($this->view->proximity_center['distance_unit'])) {
      $proximity_unit = $this->view->proximity_center['distance_unit'];
      // Convert proximity filter units to field units
      if ($proximity_unit == 'miles') {
        return 'mi';
      } elseif ($proximity_unit == 'kilometers') {
        return 'km';
      }
    }
    
    // Get from proximity filter directly
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
  public function canExpose() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    if (!empty($this->options['exposed'])) {
      return $this->t('exposed');
    }
    
    $order = $this->options['order'] == 'ASC' ? $this->t('ascending') : $this->t('descending');
    return $this->t('Distance (@order)', ['@order' => $order]);
  }
}

?>