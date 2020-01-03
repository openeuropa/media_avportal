<?php

declare(strict_types = 1);

namespace Drupal\Tests\media_avportal\src\Kernel;

use Drupal\Core\Http\ClientFactory;
use Drupal\KernelTests\KernelTestBase;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;

/**
 * Tests the AV Portal client.
 *
 * @group media_avportal
 */
class AvPortalClientTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'media_avportal',
    'media_avportal_mock',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['media_avportal']);
  }

  /**
   * Tests the AV Portal client cache.
   */
  public function testResponseCache(): void {
    $http_client_mock = $this->getMockBuilder(Client::class)->setMethods(['_call', 'request'])->getMock();
    $http_client_mock
      ->expects($this->once())
      ->method('request')
      ->willReturnCallback(function ($method, $uri = '', array $options = []) {
        $filename = drupal_get_path('module', 'media_avportal_mock') . '/responses/resources/I-053547.json';
        return new Response(200, [], file_get_contents($filename));
      });

    $http_client_factory_mock = $this->getMockBuilder(ClientFactory::class)
      ->disableOriginalConstructor()
      ->getMock();
    $http_client_factory_mock->method('fromOptions')
      ->willReturn($http_client_mock);

    $this->container->set('http_client_factory', $http_client_factory_mock);

    $client = $this->container->get('media_avportal.client');
    $resource = $client->query(['ref' => 'I-053547']);
    $resource = $client->query(['ref' => 'I-053547']);
  }

  /**
   * Tests the AV Portal client cache.
   */
  public function testResponseCacheTakeTwo(): void {
    // Create a new handler stack for Guzzle. The Drupal service is private so
    // we need to use the static method.
    $handler_stack = HandlerStack::create();

    // Keep track of requests with the history middleware.
    $guzzle_history = [];
    $handler_stack->push(Middleware::history($guzzle_history));

    // Enable the AV Portal mock middleware.
    $mock_middleware = $this->container->get('media_avportal_mock.client_middleware');
    $handler_stack->push($mock_middleware());

    // Create a client that uses the custom handler stack and have it returned
    // by the client factory.
    $http_client = $this->container->get('http_client_factory')->fromOptions(['handler' => $handler_stack]);
    $http_client_factory_mock = $this->getMockBuilder(ClientFactory::class)
      ->disableOriginalConstructor()
      ->getMock();
    $http_client_factory_mock
      ->method('fromOptions')
      ->willReturn($http_client);
    $this->container->set('http_client_factory', $http_client_factory_mock);

    $client = $this->container->get('media_avportal.client');
    $client->query(['ref' => 'I-053547']);
    // One HTTP request has been fired to the AV Portal service.
    $this->assertCount(1, $guzzle_history);

    // Requesting the same resource should not launch a new HTTP request.
    $client->query(['ref' => 'I-053547']);
    $this->assertCount(1, $guzzle_history);

    // Requesting another resource would trigger another request.
    $client->query(['ref' => 'P-038924/00-15']);
    $this->assertCount(2, $guzzle_history);
    // The request has been cached too.
    $client->query(['ref' => 'P-038924/00-15']);
    $this->assertCount(2, $guzzle_history);

    // Force a new request by disabling the cache for this query.
    $client->query(['ref' => 'P-038924/00-15'], FALSE);
    $this->assertCount(3, $guzzle_history);

    // Verify that the cache IDs are different. Request again the first resource
    // to verify that is still cached.
    $client->query(['ref' => 'I-053547']);
    $this->assertCount(3, $guzzle_history);

    // All the request parameters are taken into account to generate the cache
    // IDs.
    $client->query(['ref' => 'I-053547', 'random_parameter' => 1]);
    $this->assertCount(4, $guzzle_history);
  }

  /**
   * Tests the AV Portal client cache.
   */
  public function testResponseCacheTakeThree(): void {
    $history_middleware = $this->container->get('media_avportal_mock.history_middleware');

    $client = $this->container->get('media_avportal.client');
    $client->query(['ref' => 'I-053547']);
    // One HTTP request has been fired to the AV Portal service.
    $this->assertCount(1, $history_middleware->getHistoryContainer());

    // Requesting the same resource should not launch a new HTTP request.
    $client->query(['ref' => 'I-053547']);
    $this->assertCount(1, $history_middleware->getHistoryContainer());

    // Requesting another resource would trigger another request.
    $client->query(['ref' => 'P-038924/00-15']);
    $this->assertCount(2, $history_middleware->getHistoryContainer());
    // The request has been cached too.
    $client->query(['ref' => 'P-038924/00-15']);
    $this->assertCount(2, $history_middleware->getHistoryContainer());

    // Force a new request by disabling the cache for this query.
    $client->query(['ref' => 'P-038924/00-15'], FALSE);
    $this->assertCount(3, $history_middleware->getHistoryContainer());

    // Verify that the cache IDs are different. Request again the first resource
    // to verify that is still cached.
    $client->query(['ref' => 'I-053547']);
    $this->assertCount(3, $history_middleware->getHistoryContainer());

    // All the request parameters are taken into account to generate the cache
    // IDs.
    $client->query(['ref' => 'I-053547', 'random_parameter' => 1]);
    $this->assertCount(4, $history_middleware->getHistoryContainer());
  }

}
