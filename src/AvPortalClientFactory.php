<?php

declare(strict_types = 1);

namespace Drupal\media_avportal;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Factory service to instantiate AV Portal clients.
 */
class AvPortalClientFactory implements ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * Returns a new client instance.
   *
   * @param bool $useCaches
   *   True if the client instance should use caches for responses.
   *
   * @return \Drupal\media_avportal\AvPortalClientInterface
   *   A new client instance.
   */
  public function getClient(bool $useCaches = TRUE): AvPortalClientInterface {
    return new AvPortalClient(
      $this->container->get('http_client'),
      $this->container->get('config.factory'),
      $this->container->get('cache.default'),
      $this->container->get('datetime.time'),
      $useCaches
    );
  }

}
