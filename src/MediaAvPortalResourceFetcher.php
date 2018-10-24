<?php

declare(strict_types = 1);

namespace Drupal\media_avportal;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\media\OEmbed\ProviderRepositoryInterface;
use Drupal\media\OEmbed\ResourceException;
use Drupal\media\OEmbed\ResourceFetcher;
use GuzzleHttp\ClientInterface;

/**
 * Fetches and caches AVPortal resources.
 */
class MediaAvPortalResourceFetcher extends ResourceFetcher {
  /**
   * The Media AV Portal client.
   *
   * @var \Drupal\media_avportal\MediaAvPortalClient
   */
  private $avPortalClient;

  /**
   * Constructs a ResourceFetcher object.
   *
   * @param \Drupal\media_avportal\MediaAvPortalClient $mediaAvPortalClient
   *   The Media AV Portal client.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\media\OEmbed\ProviderRepositoryInterface $providers
   *   The repository providers. (unused here)
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   (optional) The cache backend.
   */
  public function __construct(MediaAvPortalClient $mediaAvPortalClient, ClientInterface $http_client, ProviderRepositoryInterface $providers, CacheBackendInterface $cache_backend = NULL) {
    $this->avPortalClient = $mediaAvPortalClient;

    parent::__construct($http_client, $providers, $cache_backend);
  }

  /**
   * {@inheritdoc}
   */
  public function fetchResource($ref): MediaAvPortalResource {
    $cache_id = 'media:avportal:' . $ref;

    if (FALSE !== ($cached = $this->cacheGet($cache_id))) {
      return $this->createResource($cached->data, $ref);
    }

    $data = $this->avPortalClient->query(['ref' => $ref]);

    $this->cacheSet($cache_id, $data);

    return $this->createResource($data, $ref);
  }

  /**
   * Creates a Resource object from raw resource data.
   *
   * @param array $data
   *   The resource data returned by the provider.
   * @param string $url
   *   The URL of the resource.
   *
   * @return \Drupal\media_avportal\MediaAvPortalResource
   *   A value object representing the resource.
   *
   * @throws \Drupal\media\OEmbed\ResourceException
   *   If the resource cannot be created.
   */
  protected function createResource(array $data, $url): MediaAvPortalResource {
    $data += [
      'provider' => NULL,
      'author_name' => NULL,
      'author_url' => NULL,
      'title' => NULL,
      'summary' => NULL,
      'cache_age' => NULL,
      'thumbnail_url' => NULL,
      'thumbnail_width' => 1,
      'thumbnail_height' => 1,
      'width' => 1,
      'height' => 1,
      'url' => NULL,
      'html' => '<!-- empty for now -->',
      'version' => NULL,
    ];

    $data['type'] = strtolower($data['type']);

    switch ($data['type']) {
      case MediaAvPortalResource::TYPE_PHOTO:
        throw new \DomainException('Photo media type unsupported for now.');

      case MediaAvPortalResource::TYPE_VIDEO:

        if (isset($data['media_json']['16:9']['INT']['THUMB'])) {
          $data['thumbnail_url'] = UrlHelper::parse($data['media_json']['16:9']['INT']['THUMB'])['path'];
        }

        $resource = MediaAvPortalResource::video(
          $data['html'],
          $data['width'],
          $data['height'],
          $data['provider'],
          $data['titles_json']['EN'],
          $data['author_name'],
          $data['author_url'],
          $data['cache_age'],
          $data['thumbnail_url'],
          $data['thumbnail_width'],
          $data['thumbnail_height']
        );

        $resource->setSummary($data['summary_json']['EN']);
        $resource->setRef($url);

        return $resource;

      default:
        throw new ResourceException('Unknown resource type: ' . $data['type'], $url, $data);
    }
  }

}
