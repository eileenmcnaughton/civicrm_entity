<?php

namespace Drupal\civicrm_entity\EventSubscriber;

use Drupal\search_api\Event\GatheringPluginInfoEvent;
use Drupal\search_api\Event\SearchApiEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\civicrm_entity\Plugin\search_api\datasource\CivicrmEntity as DatasourceCivicrmEntity;

/**
 * CiviCRM Entity event subscriber.
 */
class SearchApiSubscriber implements EventSubscriberInterface {

  /**
   * Gathering plugin info event handler.
   *
   * @param \Drupal\search_api\Event\GatheringPluginInfoEvent $event
   *   Gathering info event.
   */
  public function onGatheringDataSources(GatheringPluginInfoEvent $event) {
    foreach ($event->getDefinitions() as $entity_type => &$definition) {
      if (strpos($entity_type, 'entity:civicrm_') !== FALSE) {
        unset($definition['deriver']);
        $definition['class'] = DatasourceCivicrmEntity::class;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      SearchApiEvents::GATHERING_DATA_SOURCES => ['onGatheringDataSources'],
    ];
  }

}
