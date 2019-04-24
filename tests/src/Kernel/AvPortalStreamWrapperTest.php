<?php

declare(strict_types = 1);

namespace Drupal\Tests\media_avportal\src\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\media_avportal\AvPortalResource;

/**
 * Tests the AVPortalPhotoWrapper.
 */
class AvPortalStreamWrapperTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'remote_stream_wrapper',
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
   * Tests the basic methods of the AvPortalPhotoWrapper.
   */
  public function testBasicWrapperMethods(): void {
    // Load a resource from the Mock and assert we have it.
    $resource = $this->container->get('media_avportal.client')->getResource('P-038924/00-15');
    $this->assertInstanceOf(AvPortalResource::class, $resource);

    // Change the base URI of the photo store so that our stream wrapper points
    // to our local site instead of the remote server.
    $config = $this->container->get('config.factory')->getEditable('media_avportal.settings');
    $config->set('photos_base_uri', getenv('SIMPLETEST_BASE_URL') . '/modules/custom/media_avportal/tests/fixtures/');
    $config->save();

    // file_get_contents() tests both the stream_open and getExternalUrl()
    // methods of the stream wrapper.
    $image = file_get_contents('avportal://P-038924/00-15.jpg');
    $this->assertNotFalse($image);

    // Test the url_stat() method of the stream wrapper.
    $this->assertTrue(file_exists('avportal://P-038924/00-15.jpg'));
  }

}
