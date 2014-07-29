<?php
/**
 * @TODO Document this class.
 */
class CivicrmEntity extends Entity {

  /**
   * Please document this function.
   */
  protected function defaultLabel() {
    return $this->title;
  }

  /**
   * Please document this function.
   */
  protected function defaultUri() {
    return array(
      'path' => 'task/' . $this->identifier(),
    );
  }
}
