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
   * @param array $config
   *   An array of configuration options for the client.
   *
   * @return \Drupal\media_avportal\AvPortalClientInterface
   *   A new client instance.
   */
  public function getClient(array $config = []): AvPortalClientInterface;

}
