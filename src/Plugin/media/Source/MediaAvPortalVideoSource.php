<?php

declare(strict_types = 1);

namespace Drupal\media_avportal\Plugin\media\Source;
use Drupal\Component\Utility\UrlHelper;

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
      'https://audiovisual.ec.europa.eu/en/video/[REF]',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedUrlPatterns(): array {
    return [
      '@audiovisual\.ec\.europa\.eu\/(.*)\/video\/(I\-\d+)@i' => 'transformFullUrlToReference'
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function transformFullUrlToReference(string $pattern,string $url): string {

    preg_match_all($pattern, $url, $matches);
    if (!empty($matches)) {
      return $matches[2][0];
    }

    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function transformReferenceToUrl(string $reference): string {

    $formats = $this->getSupportedUrlFormats();
    $reference_url = reset($formats);

    if (preg_match('/I\-(\d+)/', $reference)) {
      return str_replace('[REF]', $reference, $reference_url);
    }

    return '';
  }

}
