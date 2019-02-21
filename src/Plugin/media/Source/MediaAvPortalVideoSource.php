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
  public function getSupportedUrlsFormat(): array {
    return [
      'https://ec.europa.eu/avservices/video/player.cfm?sitelang=en&ref=[REF]',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedUrlsPatterns(): array {
    return [
      '@ec\.europa\.eu/avservices/video/player\.cfm\?(.+)@i',
      '@ec\.europa\.eu/avservices/play\.cfm\?(.+)@i',
    ];
  }

}
