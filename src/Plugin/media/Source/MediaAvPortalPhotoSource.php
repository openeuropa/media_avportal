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
 *   thumbnail_alt_metadata_attribute = "thumbnail_alt_value",
 * )
 */
class MediaAvPortalPhotoSource extends MediaAvPortalSourceBase {

  /**
   * {@inheritdoc}
   */
  public function getSupportedUrlFormats(): array {
    return [
      'https://audiovisual.ec.europa.eu/en/photo/[REF]',
      'https://audiovisual.ec.europa.eu/en/album/[album-id]/[REF]',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedUrlPatterns(): array {
    return [
      '@audiovisual\.ec\.europa\.eu/(.*)/photo/(P\-.*\~2F.*)@i' => 'handlePhotoFullUrlPattern',
      '@audiovisual\.ec\.europa\.eu/(.*)/album/M\-[0-9]*/(P\-.*\~2F.*)@i' => 'handlePhotoFullUrlPattern',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes(): array {
    return parent::getMetadataAttributes() + [
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

    switch ($name) {
      case 'photo_uri':
        return $resource->getPhotoUri();

      case 'thumbnail_alt_value':
        return $resource->getTitle();

    }

    return parent::getMetadata($media, $name);
  }

  /**
   * Callback function that handles Full Url Photo patterns.
   *
   * @param string $pattern
   *   The pattern to check.
   * @param string $url
   *   The url.
   *
   * @return string
   *   The reference extracted from the url.
   */
  public function handlePhotoFullUrlPattern(string $pattern, string $url): string {

    preg_match_all($pattern, $url, $matches);
    if (!empty($matches)) {
      // Converts the slash in the photo id.
      return str_replace("~2F", "/", $matches[2][0]);
    }

    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function transformReferenceToUrl(string $reference): string {

    $formats = $this->getSupportedUrlFormats();
    $reference_url = reset($formats);
    $matches = [];

    if (preg_match('/(P\-\d+)\/(\d+)\-(\d+)/', $reference, $matches)) {
      return str_replace('[REF]', $matches[1] . '~2F' . $matches[2] . '-' . $matches[3], $reference_url);
    }

    return '';
  }

}
