<?php

declare(strict_types = 1);

namespace Drupal\media_avportal;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Client that interacts with the AV Portal.
 */
class AvPortalClient {

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
   * Constructs an AvPortalClient object.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(ClientInterface $http_client, ConfigFactoryInterface $configFactory) {
    $this->httpClient = $http_client;
    $this->config = $configFactory->get('media_avportal.settings');
  }

  /**
   * Executes the query call for resources.
   *
   * @param array $options
   *   The options.
   *
   * @return array|null
   *   The array if the query succeeded, NULL otherwise.
   *
   * @throws \Exception
   */
  public function query(array $options): ?array {
    $options += [
      'fl' => 'type,ref,doc_ref,titles_json,duration,shootstartdate,media_json,mediaorder_json,summary_json',
      'hasmedia' => 1,
      'wt' => 'json',
      'index' => 1,
      'pagesize' => 15,
      'type' => 'VIDEO',
    ];

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

    return $this->resourcesFromResponse($response);
  }

  /**
   * Returns a single resource.
   *
   * @param string $ref
   *   The reference.
   *
   * @return \Drupal\media_avportal\AvPortalResource|null
   *   The resource.
   *
   * @throws \Exception
   */
  public function getResource(string $ref): ?AvPortalResource {
    $result = $this->query(['ref' => $ref]);

    return $result['resources'][$ref] ?? NULL;
  }

  /**
   * Returns the thumbnail file of a given resource.
   *
   * @param \Drupal\media_avportal\AvPortalResource $resource
   *   The resource.
   *
   * @return null|string
   *   The thumbnail file if it exists, null otherwise.
   */
  public function getVideoThumbnail(AvPortalResource $resource): ?string {
    $url = $resource->getThumbnailUrl();

    if ($url === NULL) {
      return NULL;
    }

    $response = $this->httpClient->get($url);

    return $response->getStatusCode() === 200 ? (string) $response->getBody() : NULL;
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
