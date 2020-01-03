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
