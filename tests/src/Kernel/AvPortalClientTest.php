<?php

declare(strict_types = 1);

namespace Drupal\Tests\media_avportal\src\Kernel;

use Drupal\KernelTests\KernelTestBase;

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
    $history_middleware = $this->container->get('media_avportal_mock.history_middleware');

    $client = $this->container->get('media_avportal.client');
    $response = $client->query(['ref' => 'I-053547']);
    // One HTTP request has been fired to the AV Portal service.
    $this->assertCount(1, $history_middleware->getHistoryContainer());

    // Requesting the same resource should not launch a new HTTP request.
    $cached_response = $client->query(['ref' => 'I-053547']);
    $this->assertCount(1, $history_middleware->getHistoryContainer());
    $this->assertEquals($response, $cached_response);

    // Requesting another resource would trigger a new HTTP request.
    $response = $client->query(['ref' => 'P-038924/00-15']);
    $this->assertCount(2, $history_middleware->getHistoryContainer());
    $this->assertNotEquals($cached_response, $response);

    // The latest request has been cached too.
    $client->query(['ref' => 'P-038924/00-15']);
    $this->assertCount(2, $history_middleware->getHistoryContainer());

    // Cache entries use unique IDs. Request again the first resource to verify
    // that the correct cached response is returned.
    $response = $client->query(['ref' => 'I-053547']);
    $this->assertCount(2, $history_middleware->getHistoryContainer());
    $this->assertEquals($cached_response, $response);

    // All request parameters are taken into account to generate the cache IDs.
    $client->query(['ref' => 'I-053547', 'random_parameter' => 1]);
    $this->assertCount(3, $history_middleware->getHistoryContainer());

    // Make a request with cache forcefully disabled.
    $no_cache_client = $this->container->get('media_avportal.client_factory')->getClient(FALSE);
    $no_cache_client->query(['ref' => 'P-039162|00-12']);
    $this->assertCount(4, $history_middleware->getHistoryContainer());

    // Make the same request again but let the caches to be used. A new HTTP
    // request will be made, since the previous one was not stored.
    $client->query(['ref' => 'P-039162|00-12']);
    $this->assertCount(5, $history_middleware->getHistoryContainer());
  }

}
