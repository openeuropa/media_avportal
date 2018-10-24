<?php

declare(strict_types = 1);

namespace Drupal\media_avportal;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;

/**
 * MediaAvPortalClient class.
 *
 * HTTP Client decorator to facilitate querying the AV Portal.
 */
class MediaAvPortalClient {
  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface|\GuzzleHttp\Client
   */
  private $httpClient;

  /**
   * The module configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  /**
   * Constructs an AVPortalClient object.
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
   * Executes the query call.
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

    $response = $this->getHttpClient()->get(
      $this->config->get('client_api_uri'),
      [
        'query' => $options,
      ]
    );

    $response = Json::decode((string) $response->getBody())['response'];

    if (0 === $response['numFound']) {
      // @todo Better exception and better error message.
      throw new \Exception('Could not retrieve the AV Portal resource reference.');
    }

    return $response['docs'][0];
  }

  /**
   * Get the HTTP client.
   *
   * @return \GuzzleHttp\ClientInterface|\GuzzleHttp\Client
   *   The HTTP client.
   */
  protected function getHttpClient(): ClientInterface {
    return $this->httpClient;
  }

}
