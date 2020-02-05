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

    // Verify that caches are persisted and shared by all the client instances.
    // This also tests that the factory default options return clients that
    // use caches.
    $new_client = $this->container->get('media_avportal.client_factory')->getClient();
    $response = $new_client->query(['ref' => 'I-053547']);
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
    $no_cache_client = $this->container->get('media_avportal.client_factory')->getClient(['use_cache' => FALSE]);
    $no_cache_client->query(['ref' => 'P-039162|00-12']);
    $this->assertCount(4, $history_middleware->getHistoryContainer());

    // Requests from clients with disabled cache are never cached.
    $no_cache_client->query(['ref' => 'P-039162|00-12']);
    $this->assertCount(5, $history_middleware->getHistoryContainer());

    // Make the same request again but let the caches to be used. A new HTTP
    // request will be made, since the previous one was not stored.
    $client->query(['ref' => 'P-039162|00-12']);
    $this->assertCount(6, $history_middleware->getHistoryContainer());

    // Request again an earlier resource. Using no-cache clients didn't impact
    // existing cache entries.
    $response = $client->query(['ref' => 'I-053547']);
    $this->assertCount(6, $history_middleware->getHistoryContainer());
    $this->assertEquals($cached_response, $response);

    // Any change to the module configuration should invalidate the caches.
    // To verify that cache invalidation is not tied to any specific part of the
    // configuration, just to a simple save on the config entity. This will
    // invalidate the cache tags of the configuration.
    $this->config('media_avportal.settings')->save();
    $client->query(['ref' => 'I-053547']);
    $this->assertCount(7, $history_middleware->getHistoryContainer());
  }

  /**
   * Tests the AV Portal media assets types.
   *
   * @param array $input
   *   The input data.
   * @param array $expected
   *   The expected array.
   *
   * @dataProvider buildOptionsDataProvider
   */
  public function testMediaAssetType(array $input, array $expected): void {
    $history_middleware = $this->container->get('media_avportal_mock.history_middleware');

    // Make a request with cache forcefully disabled.
    $client = $this->container->get('media_avportal.client_factory')->getClient(['use_cache' => FALSE]);
    $response = $client->query($input);
    /** @var \GuzzleHttp\Psr7\Request $last_request */
    $last_request = $history_middleware->getLastHistoryEntry()['request'];
    $query = $last_request->getUri()->getQuery();
    $this->assertEqual($this->getQueryArray($query), $expected);
  }

  /**
   * Provide fixtures for buildOptions method.
   *
   * @return array
   *   List of test AV Portal media data.
   */
  public function buildOptionsDataProvider(): array {
    return [
      [
        'input' => [],
        'expected' => [
          'fl' => 'type,ref,doc_ref,titles_json,duration,shootstartdate,media_json,mediaorder_json,summary_json,languages',
          'hasMedia' => 1,
          'wt' => 'json',
          'index' => 1,
          'pagesize' => 15,
          'type' => 'VIDEO,PHOTO,REPORTAGE',
        ],
      ],
      [
        'input' => [
          'ref' => 'P-038924/00-15',
        ],
        'expected' => [
          'ref' => 'P-038924/00-15',
          'fl' => 'type,ref,doc_ref,titles_json,duration,shootstartdate,media_json,mediaorder_json,summary_json,languages',
          'hasMedia' => 1,
          'wt' => 'json',
          'index' => 1,
          'pagesize' => 15,
          'type' => 'VIDEO,PHOTO,REPORTAGE',
        ],
      ],
      [
        'input' => [
          'ref' => 'P-038924/00-15',
          'type' => 'PHOTO',
        ],
        'expected' => [
          'ref' => 'P-038924/00-15',
          'fl' => 'type,ref,doc_ref,titles_json,duration,shootstartdate,media_json,mediaorder_json,summary_json,languages',
          'hasMedia' => 1,
          'wt' => 'json',
          'index' => 1,
          'pagesize' => 15,
          'type' => 'PHOTO',
        ],
      ],
      [
        'input' => [
          'ref' => 'I-129872',
          'type' => 'VIDEO',
        ],
        'expected' => [
          'ref' => 'I-129872',
          'fl' => 'type,ref,doc_ref,titles_json,duration,shootstartdate,media_json,mediaorder_json,summary_json,languages',
          'hasMedia' => 1,
          'wt' => 'json',
          'index' => 1,
          'pagesize' => 15,
          'type' => 'VIDEO',
        ],
      ],
    ];
  }

  /**
   * Helper method for converting string of query to array.
   *
   * @param string $query_string
   *   Raw query string.
   *
   * @return array
   *   The array of query parameters.
   */
  protected function getQueryArray(string $query_string): array {
    $query_array = [];
    $decoded_query = urldecode($query_string);
    foreach (explode('&', $decoded_query) as $key_value) {
      $key_value_array = explode('=', $key_value);
      $query_array[$key_value_array[0]] = $key_value_array[1];
    }
    return $query_array;
  }

}
