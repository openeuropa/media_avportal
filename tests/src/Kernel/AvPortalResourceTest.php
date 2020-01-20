<?php

declare(strict_types = 1);

namespace Drupal\Tests\media_avportal\src\Kernel;

use Drupal\media_avportal\AvPortalResource;
use Drupal\Tests\media\Kernel\MediaKernelTestBase;

/**
 * Tests the AvPortalResourceTest.
 */
class AvPortalResourceTest extends MediaKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'remote_stream_wrapper',
    'media_avportal',
    'media_avportal_mock',
    'media_avportal_test',
    'media',
    'field',
    'text',
    'image',
    'user',
    'file',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig([
      'field',
      'text',
      'image',
      'user',
      'file',
      'media_avportal',
      'media_avportal_test',
      'media',
    ]);
  }

  /**
   * Tests the photo thumbnail preferred resolution.
   */
  public function testAvPortalPhotoThumbnailUrl(): void {
    // Load a resource from the Mock and assert we have it.
    $resource = $this->container->get('media_avportal.client')->getResource('P-038924/00-15');
    $this->assertInstanceOf(AvPortalResource::class, $resource);

    // Assert we receive the medium sized thumbnail url.
    $this->assertEquals($resource->getThumbnailUrl(), 'store2/4/P038924-35966.jpg');
  }

}
