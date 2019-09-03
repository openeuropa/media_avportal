<?php

declare(strict_types = 1);

namespace Drupal\media_avportal;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Class of the service for fetching/refreshing AVPortal media entities.
 */
class AvPortalPullMedia {

  use DependencySerializationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerChannelFactory;

  /**
   * Creates AvPortalPullMedia objects.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_channel_factory
   *   The logger channel factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LoggerChannelFactoryInterface $logger_channel_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerChannelFactory = $logger_channel_factory;
  }

  /**
   * Pull for updating existing media from AV Portal service.
   *
   * @param array $media_ids
   *   Array of media ids.
   */
  public function pullAvPortalMedia(array $media_ids = NULL): void {
    $medias = $this->entityTypeManager->getStorage('media')->loadMultiple($media_ids);
    foreach ($medias as $media_entity) {
      if ($media_entity->getSource()->getPluginDefinition()['provider'] === 'media_avportal') {
        $media_entity->save();
        $this->loggerChannelFactory->get('avportal_media')->notice('Media with ID %mid have been updated.', ['%mid' => $media_entity->id()]);
      }
    }
  }

}
