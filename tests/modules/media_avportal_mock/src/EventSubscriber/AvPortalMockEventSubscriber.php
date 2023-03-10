<?php

declare(strict_types = 1);

namespace Drupal\media_avportal_mock\EventSubscriber;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\media_avportal_mock\AvPortalMockEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Default event subscriber for the AV Portal mock.
 */
class AvPortalMockEventSubscriber implements EventSubscriberInterface {

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected ModuleExtensionList $moduleExtensionList;

  /**
   * Creates a new instance of the class.
   *
   * @param \Drupal\Core\Extension\ModuleExtensionList $moduleExtensionList
   *   The module extension list.
   */
  public function __construct(ModuleExtensionList $moduleExtensionList) {
    $this->moduleExtensionList = $moduleExtensionList;
  }

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
    foreach (glob($this->moduleExtensionList->getPath('media_avportal_mock') . '/responses/resources/*.json') as $file) {
      $ref = str_replace('.json', '', basename($file));
      $resources[$ref] = file_get_contents($file);
    }

    $event->setResources($resources);

    $searches = $event->getSearches();
    foreach (glob($this->moduleExtensionList->getPath('media_avportal_mock') . '/responses/searches/*.json') as $file) {
      $ref = str_replace('.json', '', basename($file));
      if (!isset($searches[$ref])) {
        // We only add the default search if another subscriber did not yet
        // already provide a JSON for this search term.
        $searches[$ref] = file_get_contents($file);
      }
    }
    $event->setSearches($searches);
    if (!$event->getDefault('video')) {
      $event->setDefault(file_get_contents($this->moduleExtensionList->getPath('media_avportal_mock') . '/responses/video-default.json'), 'video');
    }
    if (!$event->getDefault('all')) {
      $event->setDefault(file_get_contents($this->moduleExtensionList->getPath('media_avportal_mock') . '/responses/all-default.json'), 'all');
    }
    if (!$event->getDefault('photo')) {
      $event->setDefault(file_get_contents($this->moduleExtensionList->getPath('media_avportal_mock') . '/responses/photo-default.json'), 'photo');
    }
  }

}
