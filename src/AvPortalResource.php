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

    // We default to the first aspect ratio.
    $data = reset($this->data['media_json']);
    if (isset($data['INT']['THUMB'])) {
      $parsed = UrlHelper::parse($data['INT']['THUMB']);

      return $parsed['path'] ?? NULL;
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
