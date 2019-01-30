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
class AvPortalClient implements AvPortalClientInterface {

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
   * {@inheritdoc}
   */
  public function query(array $options = []): ?array {
    $options += [
      'fl' => 'type,ref,doc_ref,titles_json,duration,shootstartdate,media_json,mediaorder_json,summary_json',
      'hasmedia' => 1,
      'wt' => 'json',
      'index' => 1,
      'pagesize' => 15,
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
