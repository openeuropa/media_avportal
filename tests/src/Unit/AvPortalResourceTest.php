<?php

declare(strict_types = 1);

namespace Drupal\Tests\media_avportal\src\Unit;

use Drupal\media_avportal\AvPortalResource;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the AvPortalResource class.
 *
 * @coversDefaultClass \Drupal\media_avportal\AvPortalResource
 */
class AvPortalResourceTest extends UnitTestCase {

  /**
   * Tests getPhotoThumbnailUrl() method.
   *
   * @param array $data
   *   The photo resource with all resolutions.
   *
   * @dataProvider photoThumbnailsResourceDataProvider
   * @covers ::getPhotoThumbnailUrl
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
   * @param array $data
   *   The AV Portal resource with all possible titles.
   * @param string|null $langcode
   *   The language in which to return the title.
   * @param string|null $expected
   *   The expected value.
   *
   * @dataProvider titleResourceDataProvider
   * @covers ::getTitle
   */
  public function testGetTitle(array $data, ?string $langcode, ?string $expected): void {
    $resource = new AvPortalResource($data);

    // Pass the language parameter only if it has been specified.
    $title = $langcode === NULL ? $resource->getTitle() : $resource->getTitle($langcode);
    $this->assertSame($expected, $title);
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
   * Data provider for getTitle() test method.
   *
   * @return array
   *   Test data and expectations.
   */
  public function titleResourceDataProvider(): array {
    return [
      'missing titles_json' => [
        'data' => [
          'ref' => 'P-038924/00-15',
        ],
        'langcode' => NULL,
        'expected_title' => NULL,
      ],
      'non-array titles_json' => [
        'data' => [
          'ref' => 'P-038924/00-15',
          'titles_json' => 'invalid title',
        ],
        'langcode' => NULL,
        'expected_title' => NULL,
      ],
      'empty titles_json' => [
        'data' => [
          'ref' => 'P-038924/00-15',
          'titles_json' => [],
        ],
        'langcode' => NULL,
        'expected_title' => NULL,
      ],
      'titles_json with NULL values' => [
        'data' => [
          'ref' => 'P-038924/00-15',
          'titles_json' => [
            'FR' => NULL,
            'EN' => NULL,
          ],
        ],
        'langcode' => 'FR',
        'expected_title' => NULL,
      ],
      // @see https://www.php.net/manual/en/language.types.boolean.php#language.types.boolean.casting
      'title evaluable to FALSE on casting' => [
        'data' => [
          'ref' => 'P-038924/00-15',
          'titles_json' => [
            'IT' => '0',
          ],
        ],
        'langcode' => 'IT',
        'expected_title' => '0',
      ],
      'boolean title' => [
        'data' => [
          'ref' => 'P-038924/00-15',
          'titles_json' => [
            'IT' => FALSE,
          ],
        ],
        'langcode' => 'IT',
        'expected_title' => NULL,
      ],
      'scalar title' => [
        'data' => [
          'ref' => 'P-038924/00-15',
          'titles_json' => [
            'EN' => ['English title.'],
          ],
        ],
        'langcode' => 'EN',
        'expected_title' => NULL,
      ],
      // English is the default langcode used when none is passed.
      'no langcode specified / existing title' => [
        'data' => [
          'ref' => 'P-038924/00-15',
          'titles_json' => [
            'FR' => 'French title.',
            'EN' => 'English title.',
          ],
        ],
        'langcode' => NULL,
        'expected_title' => 'English title.',
      ],
      'langcode specified / existing title' => [
        'data' => [
          'ref' => 'P-038924/00-15',
          'titles_json' => [
            'EN' => 'English title.',
            'FR' => 'French title.',
          ],
        ],
        'langcode' => 'FR',
        'expected_title' => 'French title.',
      ],
      // English is the fallback langcode used when the requested is not found.
      'langcode specified / not existing title' => [
        'data' => [
          'ref' => 'P-038924/00-15',
          'titles_json' => [
            'IT' => 'Italian title.',
            'EN' => 'English title.',
          ],
        ],
        'langcode' => 'FR',
        'expected_title' => 'English title.',
      ],
      // The first title available is returned when the fallback is not found.
      'no langcode specified / fallback title not existing' => [
        'data' => [
          'ref' => 'P-038924/00-15',
          'titles_json' => [
            'IT' => 'Italian title.',
            'FR' => 'French title.',
          ],
        ],
        'langcode' => NULL,
        'expected_title' => 'Italian title.',
      ],
      // Another iteration of the above with a different titles order.
      'no langcode specified / different order of titles / fallback title not existing' => [
        'data' => [
          'ref' => 'P-038924/00-15',
          'titles_json' => [
            'FR' => 'French title.',
            'IT' => 'Italian title.',
          ],
        ],
        'langcode' => NULL,
        'expected_title' => 'French title.',
      ],
      // When the requested language and the fallback one are not found, the
      // first available title is returned.
      'langcode specified / title and fallback title not existing' => [
        'data' => [
          'ref' => 'P-038924/00-15',
          'titles_json' => [
            'IT' => 'Italian title.',
            'DE' => 'German title.',
          ],
        ],
        'langcode' => 'FR',
        'expected_title' => 'Italian title.',
      ],
      // Another iteration of the above with a different titles order.
      'langcode specified / different order of titles / title and fallback title not existing' => [
        'data' => [
          'ref' => 'P-038924/00-15',
          'titles_json' => [
            'DE' => 'German title.',
            'IT' => 'Italian title.',
          ],
        ],
        'langcode' => 'FR',
        'expected_title' => 'German title.',
      ],
      'title with encoded characters and markup / langcode specified' => [
        'data' => [
          'ref' => 'P-038924/00-15',
          'titles_json' => [
            'FR' => 'French title <br />&lt;strong&gt;with&lt;/strong&gt; markup, encoded &#39;characters&#39; &amp; letters čö&įię.',
          ],
        ],
        'langcode' => 'FR',
        'expected_title' => 'French title with markup, encoded \'characters\' & letters čö&įię.',
      ],
      'title with encoded characters and markup / fallback title' => [
        'data' => [
          'ref' => 'P-038924/00-15',
          'titles_json' => [
            'EN' => 'English title <br />&lt;strong&gt;with&lt;/strong&gt; markup, encoded &#39;characters&#39; &amp; letters čö&įię.',
          ],
        ],
        'langcode' => 'FR',
        'expected_title' => 'English title with markup, encoded \'characters\' & letters čö&įię.',
      ],
      'title with encoded characters and markup / first available title' => [
        'data' => [
          'ref' => 'P-038924/00-15',
          'titles_json' => [
            'IT' => 'Italian title <br />&lt;strong&gt;with&lt;/strong&gt; markup, encoded &#39;characters&#39; &amp; letters čö&įię.',
          ],
        ],
        'langcode' => 'FR',
        'expected_title' => 'Italian title with markup, encoded \'characters\' & letters čö&įię.',
      ],
      'title with more than 255 characters / html, encoded and multibyte characters' => [
        'data' => [
          'ref' => 'P-047441/00-05',
          'titles_json' => [
            'FR' => 'Conférence de presse de Margrethe Vestager, vice-présidente exécutive de la Commission européenne, sur un cas de pratique anti-concurrentielle :<br /> la Commission a infligé des amendes à Teva et à Cephalon pour avoir retardé l&#39;entrée sur le marché d&#39;un médicament générique moins cher',
          ],
        ],
        'langcode' => NULL,
        'expected_title' => 'Conférence de presse de Margrethe Vestager, vice-présidente exécutive de la Commission européenne, sur un cas de pratique anti-concurrentielle : la Commission a infligé des amendes à Teva et à Cephalon pour avoir retardé l\'entrée sur le marché d\'un…',
      ],
      'title with exactly 255 characters / html, encoded and multibyte characters' => [
        'data' => [
          'ref' => 'P-047441/00-05',
          'titles_json' => [
            'FR' => 'Conférence de presse de Margrethe Vestager, vice-présidente exécutive de la Commission européenne, sur un cas de pratique anti-concurrentielle :<br /> la Commission a infligé des amendes à Teva et à Cephalon pour avoir retardé l&#39;entrée sur le marché d&#39;un to255',
          ],
        ],
        'langcode' => NULL,
        'expected_title' => 'Conférence de presse de Margrethe Vestager, vice-présidente exécutive de la Commission européenne, sur un cas de pratique anti-concurrentielle : la Commission a infligé des amendes à Teva et à Cephalon pour avoir retardé l\'entrée sur le marché d\'un to255',
      ],
    ];
  }

}
