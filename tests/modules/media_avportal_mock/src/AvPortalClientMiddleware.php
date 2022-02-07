<?php

declare(strict_types = 1);

namespace Drupal\media_avportal_mock;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * A Guzzle middleware for testing the AV Portal service.
 *
 * This is used to intercept requests made to AV Portal and return responses
 * stored in JSON files.
 *
 * This is not intended for production use.
 */
class AvPortalClientMiddleware {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * AvPortalClientMiddleware constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   */
  public function __construct(ConfigFactoryInterface $configFactory, EventDispatcherInterface $eventDispatcher) {
    $this->configFactory = $configFactory;
    $this->eventDispatcher = $eventDispatcher;
  }

  /**
   * HTTP middleware that returns pre-saved data for AV Portal requests.
   */
  public function __invoke() {
    // For AV Portal requests, we need to skip the execution to the remote
    // service and instead return pre-saved values.
    return function ($handler) {
      $config = $this->configFactory->get('media_avportal.settings');
      return function (RequestInterface $request, array $options) use ($handler, $config) {
        $uri = $request->getUri();

        // AV Portal API.
        if ($uri->getScheme() . '://' . $uri->getHost() . $uri->getPath() === $config->get('client_api_uri')) {
          return $this->createServicePromise($request);
        }

        // AV Portal thumbnails.
        if ($uri->getHost() === 'defiris.ec.streamcloud.be' || strpos($uri->getPath(), 'avservices/avs/files/video6/repository/prod/photo/store/')) {
          $thumbnail = file_get_contents(drupal_get_path('module', 'media') . '/images/icons/no-thumbnail.png');
          $response = new Response(200, [], $thumbnail);
          return new FulfilledPromise($response);
        }

        // Otherwise, no intervention. We defer to the handler stack.
        return $handler($request, $options)
          ->then(function (ResponseInterface $response) use ($request, $config) {
            return $response;
          });
      };
    };
  }

  /**
   * Creates responses from pre-saved JSON data.
   *
   * @param \Psr\Http\Message\RequestInterface $request
   *   The Guzzle request.
   *
   * @return \GuzzleHttp\Promise\PromiseInterface
   *   The Guzzle promise.
   */
  protected function createServicePromise(RequestInterface $request): PromiseInterface {
    // Dispatch event to gather the JSON data for responses.
    $event = new AvPortalMockEvent($request);
    $event = $this->eventDispatcher->dispatch(AvPortalMockEvent::AV_PORTAL_MOCK_EVENT, $event);

    $uri = $request->getUri();
    $query = $uri->getQuery();
    $params = [];
    parse_str($query, $params);

    // Replace | with / .
    if (isset($params['ref'])) {
      $params['ref'] = str_replace('/', '|', $params['ref']);
      // It means we are requesting a particular resource.
      return $this->createIndividualResourcePromise($event->getResources(), $params['ref']);
    }

    $resource_type = 'all';
    if (!empty($params['type']) && $params['type'] == 'VIDEO') {
      $resource_type = 'video';
    }
    elseif (!empty($params['type']) && $params['type'] == 'PHOTO') {
      $resource_type = 'photo';
    }

    // If we are searching, we need to look at some search responses.
    if (isset($params['kwgg'])) {
      $searches = $event->getSearches();
      $json = $searches[$resource_type . '-' . $params['kwgg']] ?? $searches[$resource_type . '-empty'];
    }
    else {
      // Otherwise, we default to the regular response.
      $json = $event->getDefault($resource_type);
    }

    return $this->createPaginatedJsonPromise($json, $params);
  }

  /**
   * Handles the case of a request to a single resource.
   *
   * @param array $resources
   *   Mocked and available resources.
   * @param string $ref
   *   The resource reference.
   *
   * @return \GuzzleHttp\Promise\FulfilledPromise
   *   The middleware promise.
   */
  protected function createIndividualResourcePromise(array $resources, string $ref): PromiseInterface {
    if (isset($resources[$ref])) {
      $resource = $resources[$ref];
      $response = new Response(200, [], $resource);
      return new FulfilledPromise($response);
    }

    // If our ref is not mocked, we consider it as a not found resource.
    $resource = $resources['not-found'];
    $response = new Response(200, [], $resource);
    return new FulfilledPromise($response);
  }

  /**
   * Creates a paginated JSON response from an existing mocked JSON response.
   *
   * Responses with multiple resources can be paginated so this method takes
   * care of the mocked responses to return only the relevant items.
   *
   * @param string $json
   *   The mocked JSON response.
   * @param array $params
   *   The request parameters that contain the pagination info.
   *
   * @return \GuzzleHttp\Promise\FulfilledPromise
   *   The middleware promise.
   */
  protected function createPaginatedJsonPromise(string $json, array $params): PromiseInterface {
    // For both default and search query, we need to account for pagination.
    $decoded = json_decode($json);
    // Index starts with 1 in AV Portal so we need to subtract 1.
    $index = (int) $params['index'] - 1;
    $length = (int) $params['pagesize'];
    $docs = array_slice($decoded->response->docs, $index, $length);
    $decoded->response->docs = $docs;
    $decoded->responseHeader->params->index = $params['index'];
    $decoded->responseHeader->params->pagesize = $params['pagesize'];
    $json = json_encode($decoded);

    $response = new Response(200, [], $json);
    return new FulfilledPromise($response);
  }

}
