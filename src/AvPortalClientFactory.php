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
   * {@inheritdoc}
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
