<?php

declare(strict_types = 1);

namespace Drupal\media_avportal_mock\EventSubscriber;

use Drupal\media_avportal_mock\AvPortalMockEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Default event subscriber for the AV Portal mock.
 */
class AvPortalMockEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // We want a high priority for early execution so that it acts as defaults.
    return [
      AvPortalMockEvent::AV_PORTAL_MOCK_EVENT => [
        'setMockResources',
        100,
      ],
    ];
  }

  /**
   * Sets the default resource JSON on the event.
   *
   * @param \Drupal\media_avportal_mock\AvPortalMockEvent $event
   *   The event.
   */
  public function setMockResources(AvPortalMockEvent $event) {
    $resources = $event->getResources();
    foreach (glob(drupal_get_path('module', 'media_avportal_mock') . '/responses/resources/*.json') as $file) {
      $ref = str_replace('.json', '', basename($file));
      $resources[$ref] = file_get_contents($file);
    }

    $event->setResources($resources);

    $searches = $event->getSearches();
    foreach (glob(drupal_get_path('module', 'media_avportal_mock') . '/responses/searches/*.json') as $file) {
      $ref = str_replace('.json', '', basename($file));
      if (!isset($searches[$ref])) {
        // We only add the default search if another subscriber did not yet
        // already provide a JSON for this search term.
        $searches[$ref] = file_get_contents($file);
      }
    }
    $event->setSearches($searches);
    if (!$event->getDefault('video')) {
      $event->setDefault(file_get_contents(drupal_get_path('module', 'media_avportal_mock') . '/responses/video-default.json'), 'video');
    }
    if (!$event->getDefault('all')) {
      $event->setDefault(file_get_contents(drupal_get_path('module', 'media_avportal_mock') . '/responses/all-default.json'), 'all');
    }
    if (!$event->getDefault('photo')) {
      $event->setDefault(file_get_contents(drupal_get_path('module', 'media_avportal_mock') . '/responses/photo-default.json'), 'photo');
    }
  }

}
