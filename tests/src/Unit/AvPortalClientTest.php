<?php

declare(strict_types = 1);

namespace Drupal\Tests\media_avportal\src\Unit;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\media_avportal\AvPortalClient;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;

/**
 * Tests the AvPortalClient class.
 *
 * @coversDefaultClass \Drupal\media_avportal\AvPortalClient
 */
class AvPortalClientTest extends UnitTestCase {

  /**
   * A config factory service implementation.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $configFactory;

  /**
   * A cache backend service implementation.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $cacheBackend;

  /**
   * A time service implementation.
   *
   * @var \Drupal\Component\Datetime\TimeInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $time;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->configFactory = $this->getConfigFactoryStub([
      'media_avportal.settings' => [
        'client_api_uri' => 'http://www.example.com',
        'cache_max_age' => 3600,
      ],
    ]);

    $this->cacheBackend = $this->getMockBuilder(CacheBackendInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->time = $this->getMockBuilder(TimeInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
  }

  /**
   * Tests that the proper query options are built on request time.
   *
   * @param array $input
   *   The input data.
   * @param array $expected
   *   The expected array.
   *
   * @covers ::buildOptions
   * @dataProvider queryOptionsDataProvider
   */
  public function testQueryOptions(array $input, array $expected): void {
    $http_client = $this->getMockBuilder(Client::class)
      ->setMethods(['request'])
      ->getMock();
    $http_client
      ->expects($this->once())
      ->method('request')
      ->with($this->anything(), $this->anything(), ['query' => $expected])
      ->willReturn(new Response());

    $client = new AvPortalClient($http_client, $this->configFactory, $this->cacheBackend, $this->time, FALSE);
    $client->query($input);
  }

  /**
   * Tests that invalid query options trigger an exception.
   *
   * @param array $input
   *   The input data.
   * @param string $message
   *   The expected exception message.
   *
   * @covers ::buildOptions
   * @dataProvider invalidQueryOptionsDataProvider
   */
  public function testInvalidQueryOptions(array $input, string $message) {
    $http_client = $this->getMockBuilder(Client::class)
      ->disableOriginalConstructor()
      ->getMock();

    $client = new AvPortalClient($http_client, $this->configFactory, $this->cacheBackend, $this->time, FALSE);
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage($message);
    $client->query($input);
  }

  /**
   * Provides a list of valid query options.
   *
   * @return array
   *   A list of valid query arguments and the expected resulting query options.
   */
  public function queryOptionsDataProvider(): array {
    return [
      'empty query parameters' => [
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
      'only resource reference passed' => [
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
      'resource and photo type passed' => [
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
      'resource and video type passed' => [
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
      'resource and video and photo types passed' => [
        'input' => [
          'ref' => 'I-129872',
          'type' => 'PHOTO,VIDEO',
        ],
        'expected' => [
          'ref' => 'I-129872',
          'fl' => 'type,ref,doc_ref,titles_json,duration,shootstartdate,media_json,mediaorder_json,summary_json,languages',
          'hasMedia' => 1,
          'wt' => 'json',
          'index' => 1,
          'pagesize' => 15,
          'type' => 'PHOTO,VIDEO',
        ],
      ],
    ];
  }

  /**
   * Provides a list of invalid query options.
   *
   * @return array
   *   A list of invalid query options and their related exception messages.
   */
  public function invalidQueryOptionsDataProvider(): array {
    return [
      'one invalid asset type' => [
        'input' => [
          'ref' => 'P-038924/00-15',
          'type' => 'REPORTAGEE',
        ],
        'message' => 'Invalid asset type "REPORTAGEE" requested, allowed types are "VIDEO,PHOTO,REPORTAGE".',
      ],
      'invalid asset type with one valid' => [
        'input' => [
          'ref' => 'P-038924/00-15',
          'type' => 'REPORTAGEE,REPORTAGE',
        ],
        'message' => 'Invalid asset type "REPORTAGEE,REPORTAGE" requested, allowed types are "VIDEO,PHOTO,REPORTAGE".',
      ],
      'not supported yet assert type' => [
        'input' => [
          'ref' => 'P-038924/00-15',
          'type' => 'VIDEOSHOT',
        ],
        'message' => 'Invalid asset type "VIDEOSHOT" requested, allowed types are "VIDEO,PHOTO,REPORTAGE".',
      ],
      'all supported assert types and one invalid' => [
        'input' => [
          'ref' => 'P-038924/00-15',
          'type' => 'VIDEO,PHOTO,REPORTAGE,REPORTAGEE',
        ],
        'message' => 'Invalid asset type "VIDEO,PHOTO,REPORTAGE,REPORTAGEE" requested, allowed types are "VIDEO,PHOTO,REPORTAGE".',
      ],
    ];
  }

}
