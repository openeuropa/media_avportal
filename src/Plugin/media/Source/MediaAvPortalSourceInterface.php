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
  public function getSupportedUrlsFormat() : array;

  /**
   * Gets list of supported url patterns.
   */
  public function getSupportedUrlsPatterns() : array;

}
