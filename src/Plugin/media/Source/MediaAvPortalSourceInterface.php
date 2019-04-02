<?php

declare(strict_types = 1);

namespace Drupal\media_avportal\Plugin\media\Source;

use Drupal\media\MediaSourceFieldConstraintsInterface;

/**
 * Common interface for source plugins that use the AV Portal.
 */
interface MediaAvPortalSourceInterface extends MediaSourceFieldConstraintsInterface {

  /**
   * Gets list of supported url formats.
   */
  public function getSupportedUrlFormats(): array;

  /**
   * Gets list of supported url patterns and associated callbacks.
   */
  public function getSupportedUrlPatterns(): array;

  /**
   * Transforms url with resource reference to a simple reference string.
   *
   * @param string $url
   *   The url to transform.
   *
   * @return string
   *   The transformed url.
   */
  public function transformUrlToReference(string $url): string;

  /**
   * Transforms a resource reference string to full url with reference.
   *
   * @param string $reference
   *   The reference to transform.
   *
   * @return string
   *   The transformed Url.
   */
  public function transformReferenceToUrl(string $reference): string;

}
