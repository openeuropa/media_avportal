<?php

declare(strict_types = 1);

namespace Drupal\Tests\media_avportal\src\Kernel;

use Drupal\media\Entity\Media;
use Drupal\Tests\media\Kernel\MediaKernelTestBase;

/**
 * Tests the AvPortalPullMediaTest.
 */
class AvPortalPullMediaTest extends MediaKernelTestBase {

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
   * Tests work of AvPortal Pull media service.
   */
  public function testAvPortalPullMediaService(): void {

    /** @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage $media_storage */
    $media_storage = $this->container->get('entity_type.manager')->getStorage('media');

    $media = Media::create([
      'name' => 'random',
      'oe_media_avportal_video' => 'I-056847',
      'bundle' => 'av_portal_video',
    ]);

    $media->save();

    // Emulate media with empty name and thumbnail.
    \Drupal::database()->update('media_field_data')
      ->fields([
        'name' => NULL,
        'thumbnail__target_id' => NULL,
      ])->execute();
    \Drupal::database()->update('media_field_revision')
      ->fields([
        'name' => NULL,
        'thumbnail__target_id' => NULL,
      ])->execute();

    $media_storage->resetCache();
    $media = $media_storage->load($media->id())->toArray();
    $this->assertEqual($media['name'], []);
    $this->assertEqual($media['thumbnail'][0]['target_id'], NULL);

    \Drupal::service('media_avportal.avportal_pull_media')->pullAvPortalMedia();

    $media_storage->resetCache();
    $media = $media_storage->load($media['mid'][0]['value'])->toArray();

    $this->assertEqual($media['name'][0]['value'], 'Space and You (short version)');
    $this->assertNotEmpty($media['thumbnail'][0]['target_id']);
  }

}
