<?php

declare(strict_types = 1);

namespace Drupal\media_avportal;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Service to update AV Portal media entities.
 */
class AvPortalMediaUpdater {

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
   * Creates a new AvPortalMediaUpdater objects.
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
   * Refresh from the source all the mapped fields of AV Portal medias.
   *
   * @param array $media_ids
   *   Array of media ids.
   */
  public function refreshMappedFields(array $media_ids = NULL): void {
    /** @var \Drupal\media\Entity\Media[] $medias */
    $medias = $this->entityTypeManager->getStorage('media')->loadMultiple($media_ids);
    foreach ($medias as $entity) {
      if ($entity->getSource()->getPluginDefinition()['provider'] === 'media_avportal') {
        // Force the entity to update the mapped fields from the source metadata
        // by marking the source field as changed.
        // @see \Drupal\media\Entity\Media::prepareSave()
        // @see \Drupal\media\Entity\Media::hasSourceFieldChanged()
        $entity->original = clone $entity;
        $source_field_name = $entity->getSource()->getConfiguration()['source_field'];
        $entity->original->get($source_field_name)->setValue(NULL);

        $entity->save();
        $this->loggerChannelFactory->get('avportal_media')->notice('Media with ID %mid have been updated.', ['%mid' => $entity->id()]);
      }
    }
  }

}
