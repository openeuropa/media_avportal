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
   * Tests getPhotoThumbnailUrl() method.
   *
   * @param array $data
   *   The photo resource with all resolutions.
   *
   * @dataProvider photoThumbnailsResourceDataProvider
   */
  public function testGetPhotoThumbnailUrl(array $data): void {
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

  /**
   * Tests getTitle() method.
   *
   * @param string $expected
   *   The expected value.
   * @param array $data
   *   The AV Portal resource with all possible titles.
   *
   * @dataProvider titleResourceDataProvider
   */
  public function testGetTitle(string $expected, array $data): void {
    $resource = new AvPortalResource($data);
    $this->assertEquals($expected, $resource->getTitle());
  }

  /**
   * Provide photo resources with PHOTO and REPORTAGE types.
   *
   * @return array
   *   List of photo resources.
   */
  public function photoThumbnailsResourceDataProvider(): array {
    return [
      [
        [
          'ref' => 'P-038924/00-15',
          'type' => 'PHOTO',
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
        ],
      ],
      [
        [
          'ref' => 'P-038924/00-15',
          'type' => 'REPORTAGE',
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
        ],
      ],
    ];
  }

  /**
   * Provide AV portal resources with titles.
   *
   * @return array
   *   List of resources.
   */
  public function titleResourceDataProvider(): array {
    return [
      'not existing titles_json' =>
        [
          'expected_title' => '',
          'data' =>
            [
              'ref' => 'P-038924/00-15',
              'type' => 'PHOTO',
            ],
        ],
      'invalid titles_json' =>
        [
          'expected_title' => '',
          'data' => [
            'ref' => 'P-038924/00-15',
            'type' => 'REPORTAGE',
            'titles_json' => 'invalid title',
          ],
        ],
      'title with encoded characters (default language)' =>
        [
          'expected_title' => 'Press conference by Ursula von der Leyen, President of the European Commission, Janez Lenarčič, Stella Kyriakides, Ylva Johansson, Adina Vălean and Paolo Gentiloni, European Commissioners, on the EU\'s response to COVID-19',
          'data' => [
            'ref' => 'P-038924/00-15',
            'type' => 'REPORTAGE',
            'titles_json' => [
              'EN' => 'Press conference by Ursula von der Leyen, President of the European Commission, Janez Lenarčič, Stella Kyriakides, Ylva Johansson, Adina Vălean and Paolo Gentiloni, European Commissioners, on the EU&#39;s response to COVID-19',
            ],
          ],
        ],
      'title with encoded characters (first available language)' =>
        [
          'expected_title' => 'DE Press conference by Ursula von der Leyen, President of the European Commission, Janez Lenarčič, Stella Kyriakides, Ylva Johansson, Adina Vălean and Paolo Gentiloni, European Commissioners, on the EU\'s response to COVID-19',
          'data' =>
            [
              'ref' => 'P-038924/00-15',
              'type' => 'REPORTAGE',
              'titles_json' => [
                'DE' => 'DE Press conference by Ursula von der Leyen, President of the European Commission, Janez Lenarčič, Stella Kyriakides, Ylva Johansson, Adina Vălean and Paolo Gentiloni, European Commissioners, on the EU&#39;s response to COVID-19',
                'FR' => 'Press conference by Ursula von der Leyen, President of the European Commission, Janez Lenarčič, Stella Kyriakides, Ylva Johansson, Adina Vălean and Paolo Gentiloni, European Commissioners, on the EU&#39;s response to COVID-19',
              ],
            ],
        ],
      'title with encoded characters and html (default language)' =>
        [
          'expected_title' => 'Press conference by Ursula von der Leyen, President of the European Commission, Janez Lenarčič, Stella Kyriakides, Ylva Johansson, Adina Vălean and Paolo Gentiloni, European Commissioners, on the EU\'s response to COVID-19',
          'data' =>
            [
              'ref' => 'P-038924/00-15',
              'type' => 'REPORTAGE',
              'titles_json' => [
                'EN' => 'Press conference by &lt;strong&gt;Ursula von der Leyen&lt;/strong&gt;<br\/>, President of the European Commission, Janez Lenarčič, Stella Kyriakides, Ylva Johansson, Adina Vălean and Paolo Gentiloni, European Commissioners, on the EU&#39;s response to COVID-19',
              ],
            ],
        ],
      'title with encoded characters and html (first available language)' =>
        [
          'expected_title' => 'DE Press conference by Ursula von der Leyen, President of the European Commission, Janez Lenarčič, Stella Kyriakides, Ylva Johansson, Adina Vălean and Paolo Gentiloni, European Commissioners, on the EU\'s response to COVID-19',
          'data' =>
            [
              'ref' => 'P-038924/00-15',
              'type' => 'REPORTAGE',
              'titles_json' => [
                'DE' => 'DE Press conference by &lt;strong&gt;Ursula von der Leyen&lt;/strong&gt;<br\/>, President of the European Commission, Janez Lenarčič, Stella Kyriakides, Ylva Johansson, Adina Vălean and Paolo Gentiloni, European Commissioners, on the EU&#39;s response to COVID-19',
                'FR' => 'Press conference by Ursula von der Leyen, President of the European Commission, Janez Lenarčič, Stella Kyriakides, Ylva Johansson, Adina Vălean and Paolo Gentiloni, European Commissioners, on the EU&#39;s response to COVID-19',
              ],
            ],
        ],
    ];
  }

}
