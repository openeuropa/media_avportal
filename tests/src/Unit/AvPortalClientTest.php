<?php

declare(strict_types = 1);

namespace Drupal\Tests\media_avportal\src\Unit;

use Drupal\media_avportal\AvPortalClient;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

/**
 * Tests the AvPortalClient class.
 */
class AvPortalClientTest extends UnitTestCase {

  /**
   * The client instance.
   *
   * @var Drupal\media_avportal\AvPortalClientInterface
   */
  protected $avPortalClient;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->config_factory = $this->getConfigFactoryStub(
      [
        'media_avportal.settings' => [
          'cache_max_age' => 3600,
        ],
      ]
    );

    $this->cache_backend = $this->getMockBuilder('Drupal\Core\Cache\CacheBackendInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $this->time = $this->getMockBuilder('Drupal\Component\Datetime\TimeInterface')
      ->disableOriginalConstructor()
      ->getMock();
  }

  /**
   * Tests buildOptions() method.
   *
   * @param array $input
   *   The input data.
   * @param array $expected
   *   The expected array.
   *
   * @dataProvider buildOptionsDataProvider
   */
  public function testBuildOptions(array $input, array $expected): void {

    $mock = new MockHandler([
      new Response(200, ['X-Foo' => 'Bar'], 'Hello, World'),
    ]);
    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $avc = $this->getMockBuilder(AvPortalClient::class)
      ->setMethods(['buildOptions'])
      ->setConstructorArgs([
        $client,
        $this->config_factory,
        $this->cache_backend,
        $this->time,
        TRUE,
      ])
      ->enableProxyingToOriginalMethods()
      ->getMock();

    $avc->expects($this->once())
      ->method('buildOptions')
      ->with($this->equalTo($input))
      ->willReturn($this->equalTo($expected));

    $avc->query($input);
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
   * Provide fixtures for testing exceptions of buildOptions method.
   *
   * @return array
   *   List of test AV Portal media data.
   */
  public function buildOptionsMediaAssetTypeExceptionsDataProvider(): array {
    return [
      [
        'input' => [
          'ref' => 'P-038924/00-15',
          'type' => 'REPORTAGEE',
        ],
      ],
      [
        'input' => [
          'ref' => 'P-038924/00-15',
          'type' => 'REPORTAGEE,REPORTAGE',
        ],
      ],
      [
        'input' => [
          'ref' => 'P-038924/00-15',
          'type' => 'VIDEOSHOT',
        ],
      ],
    ];
  }

}
