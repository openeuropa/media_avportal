<?php

declare(strict_types = 1);

namespace Drupal\media_avportal\Plugin\media\Source;

use Drupal\media\MediaInterface;

/**
 * Provides a media source plugin for Media AV Portal resources.
 *
 * @MediaSource(
 *   id = "media_avportal_photo",
 *   label = @Translation("Media AV Portal Photo"),
 *   description = @Translation("Media AV portal photo plugin."),
 *   allowed_field_types = {"string"},
 *   default_thumbnail_filename = "no-thumbnail.png",
 * )
 */
class MediaAvPortalPhotoSource extends MediaAvPortalSourceBase {

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes(): array {
    return [
      'title' => $this->t('Resource title'),
      'thumbnail_uri' => $this->t('Local URI of the thumbnail'),
      'photo_uri' => $this->t('Photo URI'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $name) {
    $media_ref = $this->getSourceFieldValue($media);
    $resource = $this->avPortalClient->getResource($media_ref);
    if (!$resource) {
      $this->messenger->addError($this->t('The Media resource was not found.'));
      return NULL;
    }

    if ($name == 'photo_uri') {
      return $resource->getPhotoUri();
    }

    return parent::getMetadata($media, $name);
  }

}
