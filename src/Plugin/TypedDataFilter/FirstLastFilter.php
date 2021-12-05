<?php

namespace Drupal\civicrm_entity\Plugin\TypedDataFilter;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\Type\StringInterface;
use Drupal\typed_data\DataFilterBase;

/**
 * A data filter providing a default value if no value is set.
 *
 * @DataFilter(
 *   id = "FirstLast",
 *   label = @Translation("Drupal Username : FirstnameLastname."),
 * )
 */
class FirstLAstFilter extends DataFilterBase {

  /**
   * {@inheritdoc}
   */
    public function canFilter(DataDefinitionInterface $definition) {
      if ($definition->getConstraints()['EntityType'] == "civicrm_contact") {
          return true;
      }
      else {
          return false;
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
    $login = str_replace(' ', '', ucfirst(strtolower($value->get('first_name')->getString()))) . ucfirst(strtolower($value->get('last_name')->getString()));

    return filter_var($login, FILTER_SANITIZE_EMAIL);
  }

}
