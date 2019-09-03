<?php

declare(strict_types = 1);

namespace Drupal\media_avportal;

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

    $titles = $this->data['titles_json'];
    if (isset($titles[$langcode])) {
      return $titles[$langcode];
    }

    // Fallback to english if the specified langcode is not present.
    if (isset($titles['EN'])) {
      return $titles['EN'];
    }
    // Fallback to first available title,
    // if even english language is not present.
    if (count($titles) > 0 && $first_title = reset($titles)) {
      return is_string($first_title) ? $first_title : NULL;
    }

    return NULL;
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

    // We are trying to get a thumbnail of undefined language.
    if (isset($first_media_json['INT']['THUMB'])) {
      return UrlHelper::parse($first_media_json['INT']['THUMB'])['path'] ?? NULL;
    }

    // We are trying to get a thumbnail of current default language.
    // @todo Recheck this structure of language keys.
    if (isset($first_media_json['EN/' . mb_strtoupper(\Drupal::languageManager()->getDefaultLanguage()->getId())]['THUMB'])) {
      return UrlHelper::parse($first_media_json['EN/' . mb_strtoupper(\Drupal::languageManager()->getDefaultLanguage()->getId())]['THUMB'])['path'] ?? NULL;
    }

    // We are trying to get a thumbnail
    // for a original language (usually English).
    elseif (isset($this->data['languages'][0]) && isset($first_media_json[$this->data['languages'][0]]['THUMB'])) {
      return UrlHelper::parse($first_media_json[$this->data['languages'][0]]['THUMB'])['path'] ?? NULL;
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

    $resolutions = ['LOW', 'MED', 'HIGH'];
    foreach ($resolutions as $resolution) {
      if (isset($media_json[$resolution]['PATH'])) {
        return $media_json[$resolution]['PATH'];
      }
    }

    return NULL;
  }

  /**
   * Returns all the resource data.
   *
   * @return array
   *   The resource data.
   */
  public function getData(): array {
    return $this->data;
  }

}
