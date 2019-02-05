<?php

declare(strict_types = 1);

namespace Drupal\media_avportal_mock;

use Psr\Http\Message\RequestInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event used to collect the mocking JSON data.
 */
class AvPortalMockEvent extends Event {

  /**
   * Event name.
   */
  const AV_PORTAL_MOCK_EVENT = 'media_avportal_mock.event';

  /**
   * The Guzzle request.
   *
   * @var \Psr\Http\Message\RequestInterface
   */
  protected $request;

  /**
   * The resources JSON data.
   *
   * @var array
   */
  protected $resources;

  /**
   * The searches JSON data.
   *
   * @var array
   */
  protected $searches;

  /**
   * The default JSON response data.
   *
   * @var array
   */
  protected $default;

  /**
   * AvPortalMockEvent constructor.
   *
   * @param \Psr\Http\Message\RequestInterface $request
   *   The Guzzle request.
   * @param array $resources
   *   The resources JSON data.
   * @param array $searches
   *   The searches JSON data.
   * @param string $default
   *   The default JSON response data.
   */
  public function __construct(RequestInterface $request, array $resources = [], array $searches = [], string $default = NULL) {
    $this->resources = $resources;
    $this->searches = $searches;
    $this->default = $default;
    $this->request = $request;
  }

  /**
   * Getter.
   *
   * @return array
   *   The resources.
   */
  public function getResources(): array {
    return $this->resources;
  }

  /**
   * Setter.
   *
   * @param array $resources
   *   The resources.
   */
  public function setResources(array $resources): void {
    $this->resources = $resources;
  }

  /**
   * Getter.
   *
   * @return array
   *   The searches.
   */
  public function getSearches(): array {
    return $this->searches;
  }

  /**
   * Setter.
   *
   * @param array $searches
   *   The searches.
   */
  public function setSearches(array $searches): void {
    $this->searches = $searches;
  }

  /**
   * Getter.
   *
   * @param string $type
   *   The type of default to get.
   *
   * @return string
   *   The default JSON.
   */
  public function getDefault(string $type = 'video'): ?string {
    return $this->default[$type] ?? NULL;
  }

  /**
   * Setter.
   *
   * @param string $default
   *   The default JSON.
   * @param string $type
   *   The type of default to set.
   */
  public function setDefault(string $default, string $type = 'video'): void {
    $this->default[$type] = $default;
  }

}
