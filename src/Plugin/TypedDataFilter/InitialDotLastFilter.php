<?php

namespace Drupal\civicrm_entity\Plugin\TypedDataFilter;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\typed_data\DataFilterBase;

/**
 * A data filter providing a default value if no value is set.
 *
 * @DataFilter(
 *   id = "initialdotlast",
 *   label = @Translation("Drupal Username : initial.lastname."),
 * )
 */
class InitialDotLastFilter extends DataFilterBase {

  /**
   * {@inheritdoc}
   */
  public function canFilter(DataDefinitionInterface $definition) {
    if ($definition->getConstraints()['EntityType'] == "civicrm_contact") {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function filtersTo(DataDefinitionInterface $definition, array $arguments) {
    return DataDefinition::create('string');
  }

  /**
   * {@inheritdoc}
   */
  public function filter(DataDefinitionInterface $definition, $value, array $arguments, BubbleableMetadata $bubbleable_metadata = NULL) {
    $c = preg_match_all("/(?<=\b)[a-z]/i", ($value->get('first_name')->getString()), $m);
    if ($c > 0) {
      $login = strtolower(implode('', $m[0])) . '.' . strtolower($value->get('last_name')->getString());
    }
    else {
      $login = strtolower($value->get('last_name')->getString());
    }

    return filter_var($login, FILTER_SANITIZE_EMAIL);
  }

}
