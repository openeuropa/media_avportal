<?php

declare(strict_types = 1);

namespace Drupal\media_avportal;

/**
 * Interface for clients that interact with the EC Audiovisual service.
 */
interface AvPortalClientInterface {

  /**
   * Executes the query call for resources.
   *
   * @param array $options
   *   The query options.
   *
   * @return array|null
   *   The array if the query succeeded, NULL otherwise.
   *
   * @throws \InvalidArgumentException
   *   Thrown when invalid query options are passed.
   */
  public function query(array $options): ?array;

  /**
   * Returns a single resource given its identifier.
   *
   * @param string $ref
   *   The reference identifier.
   *
   * @return \Drupal\media_avportal\AvPortalResource|null
   *   The resource.
   */
  public function getResource(string $ref): ?AvPortalResource;

  /**
   * Returns the thumbnail file of a given resource.
   *
   * @param \Drupal\media_avportal\AvPortalResource $resource
   *   The resource.
   *
   * @return null|string
   *   The thumbnail file if it exists, null otherwise.
   */
  public function getThumbnail(AvPortalResource $resource): ?string;

}
