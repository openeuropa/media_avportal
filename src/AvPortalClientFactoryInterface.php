<?php

declare(strict_types = 1);

namespace Drupal\media_avportal;

/**
 * Interface for AV Portal client factory.
 */
interface AvPortalClientFactoryInterface {

  /**
   * Returns a new client instance.
   *
   * @param bool $useCaches
   *   True if the client instance should use caches for responses.
   *
   * @return \Drupal\media_avportal\AvPortalClientInterface
   *   A new client instance.
   */
  public function getClient(bool $useCaches = TRUE): AvPortalClientInterface;

}
