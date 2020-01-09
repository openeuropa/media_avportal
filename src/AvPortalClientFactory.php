<?php

declare(strict_types = 1);

namespace Drupal\media_avportal;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Factory service to instantiate AV Portal clients.
 */
class AvPortalClientFactory implements AvPortalClientFactoryInterface, ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * Returns a new client instance.
   *
   * @param array $config
   *   An array of configuration options for the client. Available options:
   *   - use_cache: whether or not to cache the AV Portal responses. Defaults
   *     to TRUE.
   *
   * @return \Drupal\media_avportal\AvPortalClientInterface
   *   A new client instance.
   */
  public function getClient(array $config = []): AvPortalClientInterface {
    $default_config = [
      'use_cache' => TRUE,
    ];
    $config = array_merge($default_config, $config);

    return new AvPortalClient(
      $this->container->get('http_client'),
      $this->container->get('config.factory'),
      $this->container->get('cache.default'),
      $this->container->get('datetime.time'),
      $config['use_cache']
    );
  }

}
