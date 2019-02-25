<?php

declare(strict_types = 1);

namespace Drupal\media_avportal\Plugin\media\Source;

/**
 * Provides a media source plugin for Media AV Portal resources.
 *
 * @MediaSource(
 *   id = "media_avportal_video",
 *   label = @Translation("Media AV Portal Video"),
 *   description = @Translation("Media AV portal video plugin."),
 *   allowed_field_types = {"string"},
 *   default_thumbnail_filename = "no-thumbnail.png",
 * )
 */
class MediaAvPortalVideoSource extends MediaAvPortalSourceBase {

  /**
   * {@inheritdoc}
   */
  public function getSupportedUrlFormats(): array {
    return [
      'https://ec.europa.eu/avservices/video/player.cfm?sitelang=en&ref=[REF]',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedUrlPatterns(): array {
    return [
      '@ec\.europa\.eu/avservices/video/player\.cfm\?(.+)@i',
      '@ec\.europa\.eu/avservices/play\.cfm\?(.+)@i',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function transformUrlToReference(array $url): ?string {
    preg_match('/(\d+)/', $url['query']['ref'], $matches);

    // The reference should be in the format I-xxxx where x are numbers.
    // Sometimes no dash is present, so we have to normalise the reference
    // back.
    if (isset($matches[0])) {
      return 'I-' . $matches[0];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function transformReferenceToUrl(string $reference): ?string {

    $formats = $this->getSupportedUrlFormats();
    $reference_url = reset($formats);

    if (preg_match('/I\-(\d+)/', $reference)) {
      return str_replace('[REF]', $reference, $reference_url);
    }
  }

}
