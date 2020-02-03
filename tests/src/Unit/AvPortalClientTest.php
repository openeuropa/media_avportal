<?php

declare(strict_types = 1);

namespace Drupal\Tests\media_avportal\src\Unit;

use Drupal\media_avportal\AvPortalClient;
use Drupal\Tests\UnitTestCase;

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

    $http_client = $this->getMockBuilder('GuzzleHttp\ClientInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $config_factory = $this->getConfigFactoryStub(
      [
        'media_avportal.settings' => [
          'cache_max_age' => 3600,
        ],
      ]
    );

    $cache_backend = $this->getMockBuilder('Drupal\Core\Cache\CacheBackendInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $time = $this->getMockBuilder('Drupal\Component\Datetime\TimeInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $logger_channel_factory = $this->getMockBuilder('Drupal\Core\Logger\LoggerChannelFactoryInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $this->avPortalClient = new AvPortalClient($http_client, $config_factory, $cache_backend, $time, $logger_channel_factory, TRUE);
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
    $options = $this->avPortalClient->buildOptions($input);
    $this->assertArrayEquals($expected, $options);
  }

  /**
   * Tests exceptions of buildOptions method.
   *
   * @param array $input
   *   The array of invalid input data.
   *
   * @dataProvider buildOptionsMediaAssetTypeExceptionsDataProvider
   */
  public function testBuildOptionsExceptions(array $input): void {
    $this->setExpectedException(\OutOfRangeException::class, 'Not all of the requested asset types \'' . $input['type'] . '\' is allowed.');
    $this->avPortalClient->buildOptions($input);
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
