<?php

declare(strict_types = 1);

namespace Drupal\media_avportal;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\UseCacheBackendTrait;
use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Client that interacts with the AV Portal.
 */
class AvPortalClient implements AvPortalClientInterface {

  use UseCacheBackendTrait;

  /**
   * The list of allowed media assets.
   *
   * @var array
   */
  public const ALLOWED_TYPES = [
    'VIDEO',
    'PHOTO',
    'REPORTAGE',
  ];

  /**
   * The module configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs an AvPortalClient object.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The HTTP client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   The cache backend.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param bool $useCaches
   *   If the client should use caches for storing and retrieving responses.
   */
  public function __construct(ClientInterface $httpClient, ConfigFactoryInterface $configFactory, CacheBackendInterface $cacheBackend = NULL, TimeInterface $time = NULL, bool $useCaches = TRUE) {
    $this->httpClient = $httpClient;
    $this->config = $configFactory->get('media_avportal.settings');
    $this->cacheBackend = $cacheBackend ?? \Drupal::service('cache.default');
    $this->time = $time ?? \Drupal::service('datetime.time');
    // Disable caches if the cache max age is set to 0.
    $this->useCaches = $useCaches && $this->config->get('cache_max_age') !== 0;
  }

  /**
   * {@inheritdoc}
   */
  public function query(array $options = []): ?array {
    $options = $this->buildOptions($options);

    // Generate a cache ID that takes into consideration all the query
    // parameters.
    $cid = 'media_avportal:client:query:' . serialize($options);
    $cached = $this->cacheGet($cid);
    if ($cached) {
      return $this->resourcesFromResponse($cached->data);
    }

    try {
      $raw_response = $this->httpClient->get($this->config->get('client_api_uri'), ['query' => $options]);
      // @todo log if the response is not valid JSON.
      $response = Json::decode((string) $raw_response->getBody());
    }
    catch (RequestException $exception) {
      // @todo Log the exception.
      $response = NULL;
    }

    // Convert invalid responses to empty ones.
    if ($response === NULL) {
      $response = [];
    }

    if ($response !== []) {
      // Calculate the expire time if the cache is not permanent.
      $expire = $this->config->get('cache_max_age') === Cache::PERMANENT
        ? Cache::PERMANENT
        : $this->time->getRequestTime() + $this->config->get('cache_max_age');
      $this->cacheSet($cid, $response, $expire, $this->config->getCacheTags());
    }

    return $this->resourcesFromResponse($response);
  }

  /**
   * {@inheritdoc}
   */
  public function getResource(string $ref): ?AvPortalResource {
    $result = $this->query(['ref' => $ref]);

    return $result['resources'][$ref] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getThumbnail(AvPortalResource $resource): ?string {
    $url = $resource->getThumbnailUrl();

    if ($url === NULL) {
      return NULL;
    }

    if (in_array($resource->getType(), ['PHOTO', 'REPORTAGE'])) {
      $url = $this->config->get('photos_base_uri') . $url;
    }

    $response = $this->httpClient->get($url);

    return $response->getStatusCode() === 200 ? (string) $response->getBody() : NULL;
  }

  /**
   * Returns a array of options which we will use for queries.
   *
   * @param array $options
   *   The defined options.
   *
   * @return array|null
   *   The array of query options.
   */
  protected function buildOptions(array $options = []): ?array {
    $options += [
      'fl' => 'type,ref,doc_ref,titles_json,duration,shootstartdate,media_json,mediaorder_json,summary_json,languages',
      'hasMedia' => 1,
      'wt' => 'json',
      'index' => 1,
      'pagesize' => 15,
      'type' => implode(',', self::ALLOWED_TYPES),
    ];

    // Make sure that we are requesting a specified and supported asset type.
    $asset_types = array_map('mb_strtoupper', explode(',', (string) $options['type']));
    if (array_diff($asset_types, self::ALLOWED_TYPES)) {
      throw new \InvalidArgumentException(sprintf('Invalid asset type "%s" requested, allowed types are "%s".', $options['type'], implode(',', self::ALLOWED_TYPES)));
    }

    return $options;
  }

  /**
   * Returns the resources from a given response.
   *
   * @param array $response
   *   The response array.
   *
   * @return array
   *   The resources array with references and their corresponding object.
   */
  protected function resourcesFromResponse(array $response): array {
    if (!isset($response['response']) || empty($response['response']['numFound']) || !isset($response['response']['docs'])) {
      return [
        'num_found' => 0,
        'resources' => [],
      ];
    }

    $payload = $response['response'];
    $resources = [];
    foreach ($payload['docs'] as $doc) {
      $resources[$doc['ref']] = new AvPortalResource($doc);
    }

    return [
      'num_found' => $payload['numFound'],
      'resources' => $resources,
    ];
  }

}
