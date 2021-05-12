<?php

declare(strict_types = 1);

namespace Drupal\Tests\media_avportal\src\Kernel;

use Drupal\file\Entity\File;
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
  protected function setUp(): void {
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
   * Tests the media updater service.
   */
  public function testAvPortalMediaUpdaterService(): void {
    // When a media is created and it has no thumbnail, a default one is used.
    // Create a file to use as default thumbnail.
    // @see \Drupal\media\Entity\Media::loadThumbnail()
    file_put_contents('public://default-thumbnail.jpg', '');
    $thumbnail = File::create([
      'uri' => 'public://default-thumbnail.jpg',
    ]);
    $thumbnail->save();

    $empty_media = Media::create([
      'oe_media_avportal_video' => 'I-056847',
      'bundle' => 'av_portal_video',
    ]);
    $empty_media->save();
    // Store the correct thumbnail to have a comparison later.
    $empty_media_thumbnail = $empty_media->get('thumbnail')->entity;
    // Simulate that the media was created without name and thumbnail.
    \Drupal::database()->update('media_field_data')
      ->fields([
        'name' => NULL,
        'thumbnail__target_id' => NULL,
      ])
      ->condition('mid', $empty_media->id())
      ->execute();
    \Drupal::database()->update('media_field_revision')
      ->fields([
        'name' => NULL,
        'thumbnail__target_id' => NULL,
      ])
      ->condition('mid', $empty_media->id())
      ->execute();

    // Create another entity with an outdated thumbnail.
    $outdated_media = Media::create([
      'oe_media_avportal_video' => 'I-129872',
      'bundle' => 'av_portal_video',
    ]);
    $outdated_media->save();
    // Store the correct thumbnail.
    $outdated_media_thumbnail = $outdated_media->get('thumbnail')->entity;
    \Drupal::database()->update('media_field_data')
      ->fields([
        'thumbnail__target_id' => $thumbnail->id(),
      ])
      ->condition('mid', $outdated_media->id())
      ->execute();
    \Drupal::database()->update('media_field_revision')
      ->fields([
        'thumbnail__target_id' => $thumbnail->id(),
      ])
      ->condition('mid', $outdated_media->id())
      ->execute();

    /** @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage $media_storage */
    $media_storage = $this->container->get('entity_type.manager')->getStorage('media');
    $media_storage->resetCache();

    // Convert the entity to an array. This will extract the current values and
    // avoid the Media class to use fallbacks for the title.
    $empty_media_data = $media_storage->load($empty_media->id())->toArray();
    // Verify that the test data has been successfully altered.
    $this->assertEquals([], $empty_media_data['name']);
    $this->assertNull($empty_media_data['thumbnail'][0]['target_id']);

    $outdated_media_data = $media_storage->load($outdated_media->id())->toArray();
    $this->assertEquals('European Solidarity Corps - Teaser 2', $outdated_media_data['name'][0]['value']);
    $this->assertEquals($thumbnail->id(), $outdated_media_data['thumbnail'][0]['target_id']);

    // Run the import.
    \Drupal::service('media_avportal.media_updater')->refreshMappedFields();

    // Verify that the media entities have been updated.
    $media_storage->resetCache();
    $empty_media = $media_storage->load($empty_media->id());
    $empty_media_data = $empty_media->toArray();
    $this->assertEquals('Space and You (short version)', $empty_media_data['name'][0]['value']);
    $this->assertEquals($empty_media_thumbnail->id(), $empty_media_data['thumbnail'][0]['target_id']);

    $outdated_media_data = $media_storage->load($outdated_media->id())->toArray();
    $this->assertEquals('European Solidarity Corps - Teaser 2', $outdated_media_data['name'][0]['value']);
    // The correct thumbnail has been associated back (file is similar so a
    // a new one is not created).
    $this->assertEquals($outdated_media_thumbnail->id(), $outdated_media_data['thumbnail'][0]['target_id']);
  }

}
