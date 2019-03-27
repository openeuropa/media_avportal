<?php

declare(strict_types = 1);

namespace Drupal\media_avportal\Plugin\media\Source;

use Drupal\Component\Utility\UrlHelper;
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
  public function getSupportedUrlFormats(): array {
    return [
      'https://audiovisual.ec.europa.eu/en/photo/[REF]'
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedUrlPatterns(): array {
    return [
      '@audiovisual\.ec\.europa\.eu/(.*)/photo/(P\-.*\~2F.*)@i' => 'transformFullUrlToReference'
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

    if ($name == 'photo_uri') {
      return $resource->getPhotoUri();
    }

    return parent::getMetadata($media, $name);
  }


  /**
   * Callback function to transform url to a reference
   * @param string $pattern
   *  The pattern.
   * @param string $url
   *  The url.
   * @return string
   *  The reference.
   */
  public function transformFullUrlToReference(string $pattern, string $url): string {

    preg_match_all($pattern, $url, $matches);
    if (!empty($matches)) {
      // converts the slash in the photo id
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
      return str_replace('[REF]', $matches[1] . '~2F' . $matches[2] . '-' . ($matches[3] - 1), $reference_url);
    }

    return '';
  }

}
