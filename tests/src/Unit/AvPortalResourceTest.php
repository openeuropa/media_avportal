<?php

declare(strict_types = 1);

namespace Drupal\Tests\media_avportal\src\Unit;

use Drupal\media_avportal\AvPortalResource;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the AvPortalResource class.
 */
class AvPortalResourceTest extends UnitTestCase {

  /**
   * Tests the photo thumbnail preferred resolution.
   */
  public function testAvPortalPhotoThumbnailUrl(): void {
    // Photo resource data array with all resolutions.
    $data = [
      'ref' => 'P-038924/00-15',
      'doc_ref' => 'P-038924',
      'titles_json' =>
        [
          'EN' => 'Euro with miniature figurines',
        ],
      'summary_json' =>
        [
          'EN' => 'Euro with miniature figurines',
        ],
      'type' => 'PHOTO',
      'shootstartdate' => '20181130 00:00',
      'media_json' =>
        [
          'MED' =>
            [
              'PIXH' => 426,
              'PIXL' => 640,
              'PATH' => 'medium.jpg',
            ],
          'HIGH' =>
            [
              'PIXH' => 3455,
              'PIXL' => 5183,
              'PATH' => 'high.jpg',
            ],
          'LOW' =>
            [
              'PIXH' => 133,
              'PIXL' => 200,
              'PATH' => 'low.jpg',
            ],
        ],
    ];

    // Instantiate the photo resource with all resolutions.
    $resource = new AvPortalResource($data);
    // Assert we get the medium resolution when all resolution are present.
    $this->assertEquals('medium.jpg', $resource->getThumbnailUrl());

    // Remove medium, assert for low resolution.
    unset($data['media_json']['MED']);
    $resource = new AvPortalResource($data);
    $this->assertEquals('low.jpg', $resource->getThumbnailUrl());

    // Remove low, assert for high resolution.
    unset($data['media_json']['LOW']);
    $resource = new AvPortalResource($data);
    $this->assertEquals('high.jpg', $resource->getThumbnailUrl());

    // Unset all resolutions.
    unset($data['media_json']['HIGH']);
    $resource = new AvPortalResource($data);
    $this->assertNull($resource->getThumbnailUrl());
  }

}
