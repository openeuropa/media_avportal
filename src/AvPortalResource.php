<?php

declare(strict_types = 1);

namespace Drupal\media_avportal;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;

/**
 * Value object representing an AvPortal resource.
 */
class AvPortalResource {

  /**
   * The data coming from the AV portal service for this resource.
   *
   * @var array
   */
  protected $data;

  /**
   * AvPortalResource constructor.
   *
   * @param array $data
   *   The resource data.
   */
  public function __construct(array $data) {
    if (!isset($data['ref'])) {
      throw new \InvalidArgumentException('Invalid resource data.');
    }

    $this->data = $data;
  }

  /**
   * Get the reference.
   *
   * @return string
   *   The reference.
   */
  public function getRef(): string {
    return $this->data['ref'];
  }

  /**
   * Get the reference.
   *
   * @return string
   *   The reference.
   */
  public function getType(): string {
    return $this->data['type'];
  }

  /**
   * Get the Photo URI (for PHOTOS).
   *
   * @return string
   *   The photo Uri
   */
  public function getPhotoUri(): string {
    return $this->data['media_json']['HIGH']['PATH'] ?? '';
  }

  /**
   * Returns the title of the resource.
   *
   * @param string $langcode
   *   The language in which to return the title.
   *
   * @return string
   *   The title.
   */
  public function getTitle(string $langcode = 'EN'): ?string {
    if (!isset($this->data['titles_json']) || !is_array($this->data['titles_json'])) {
      return NULL;
    }

    // Filter out any NULL, TRUE, FALSE and non-scalar titles.
    $titles = array_filter($this->data['titles_json'], function ($value): bool {
      return $value !== NULL && is_scalar($value) && !is_bool($value);
    });
    if (empty($titles)) {
      return NULL;
    }

    // The first available title will be used as fallback in case the specified
    // langcode is not present.
    $title = reset($titles);

    // English is used as fallback in case the specified langcode is not
    // available.
    $langcodes = array_unique([$langcode, 'EN']);
    foreach ($langcodes as $langcode) {
      if (isset($titles[$langcode])) {
        $title = $titles[$langcode];
        break;
      }
    }

    return strip_tags(Html::decodeEntities($title));
  }

  /**
   * Returns the thumbnail URL.
   *
   * @return null|string
   *   The thumbnail URL if it exists, NULL otherwise.
   */
  public function getThumbnailUrl(): ?string {
    if (!isset($this->data['media_json']) || !is_array($this->data['media_json'])) {
      return NULL;
    }

    if ($this->getType() == 'VIDEO') {
      return $this->getVideoThumbnailUrl();
    }
    elseif (in_array($this->getType(), ['PHOTO', 'REPORTAGE'])) {
      return $this->getPhotoThumbnailUrl();
    }

    return NULL;
  }

  /**
   * Returns a thumbnail for video.
   *
   * @return string|null
   *   URL of the thumbnail.
   */
  protected function getVideoThumbnailUrl(): ?string {
    $first_media_json = reset($this->data['media_json']);

    $languages = [
      'INT',
      mb_strtoupper(\Drupal::languageManager()->getDefaultLanguage()->getId()),
      'EN',
    ];
    $languages = array_merge($languages, $this->data['languages'] ?? []);
    $languages = array_unique(array_filter($languages));

    foreach ($languages as $langcode) {
      if (isset($first_media_json[$langcode]['THUMB'])) {
        return UrlHelper::parse($first_media_json[$langcode]['THUMB'])['path'] ?? NULL;
      }
    }

    return NULL;
  }

  /**
   * Returns a thumbnail for photo.
   *
   * @return string|null
   *   URL of the thumbnail.
   */
  protected function getPhotoThumbnailUrl(): ?string {
    // We default to the first aspect ratio.
    $media_json = $this->data['media_json'];

    $resolutions = ['MED', 'LOW', 'HIGH'];
    foreach ($resolutions as $resolution) {
      if (isset($media_json[$resolution]['PATH'])) {
        return $media_json[$resolution]['PATH'];
      }
    }

    return NULL;
  }

  /**
   * Returns resource data.
   *
   * @return array
   *   The resource data.
   */
  public function getData(): array {
    return $this->data;
  }

}
