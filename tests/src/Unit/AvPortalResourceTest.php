<?php

declare(strict_types=1);

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
   * Tests getCaption() method.
   *
   * @param array $data
   *   The AV Portal resource with all possible captions.
   * @param string|null $langcode
   *   The language in which to return the caption.
   * @param string|null $expected
   *   The expected value.
   *
   * @dataProvider captionResourceDataProvider
   * @covers ::getCaption
   */
  public function testGetCaption(array $data, ?string $langcode, ?string $expected): void {
    $resource = new AvPortalResource($data);

    // Pass the language parameter only if it has been specified.
    $title = $langcode === NULL ? $resource->getCaption() : $resource->getCaption($langcode);
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

  /**
   * Data provider for getCaption() test method.
   *
   * @return array
   *   Test data and expectations.
   */
  public function captionResourceDataProvider(): array {
    return [
      // Data for not supported resource type.
      'not supported resource type' => [
        'data' => [
          'type' => 'VIDEO',
          'ref' => 'P-038924/00-15',
        ],
        'langcode' => NULL,
        'expected_caption' => NULL,
      ],

      // Data for Photo type.
      'missing summary_json' => [
        'data' => [
          'type' => 'PHOTO',
          'ref' => 'P-038924/00-15',
        ],
        'langcode' => NULL,
        'expected_caption' => NULL,
      ],
      'non-array summary_json' => [
        'data' => [
          'type' => 'PHOTO',
          'ref' => 'P-038924/00-15',
          'legend_json' => 'invalid title',
        ],
        'langcode' => NULL,
        'expected_caption' => NULL,
      ],
      'empty summary_json' => [
        'data' => [
          'type' => 'PHOTO',
          'ref' => 'P-038924/00-15',
          'summary_json' => [],
        ],
        'langcode' => NULL,
        'expected_caption' => NULL,
      ],
      'summary_json with NULL values' => [
        'data' => [
          'type' => 'PHOTO',
          'ref' => 'P-038924/00-15',
          'summary_json' => [
            'FR' => NULL,
            'EN' => NULL,
          ],
        ],
        'langcode' => 'FR',
        'expected_caption' => NULL,
      ],
      // @see https://www.php.net/manual/en/language.types.boolean.php#language.types.boolean.casting
      'summary_json evaluable to FALSE on casting' => [
        'data' => [
          'type' => 'PHOTO',
          'ref' => 'P-038924/00-15',
          'summary_json' => [
            'IT' => '0',
          ],
        ],
        'langcode' => 'IT',
        'expected_caption' => '0',
      ],
      'summary_json boolean caption' => [
        'data' => [
          'type' => 'PHOTO',
          'ref' => 'P-038924/00-15',
          'summary_json' => [
            'IT' => FALSE,
          ],
        ],
        'langcode' => 'IT',
        'expected_caption' => NULL,
      ],
      'summary_json scalar caption' => [
        'data' => [
          'type' => 'PHOTO',
          'ref' => 'P-038924/00-15',
          'summary_json' => [
            'EN' => ['English caption.'],
          ],
        ],
        'langcode' => 'EN',
        'expected_caption' => NULL,
      ],
      // English is the default langcode used when none is passed.
      'no langcode specified for summary_json / existing caption' => [
        'data' => [
          'type' => 'PHOTO',
          'ref' => 'P-038924/00-15',
          'summary_json' => [
            'FR' => 'French caption.',
            'EN' => 'English caption.',
          ],
        ],
        'langcode' => NULL,
        'expected_caption' => 'English caption.',
      ],
      'langcode specified for summary_json / existing caption' => [
        'data' => [
          'type' => 'PHOTO',
          'ref' => 'P-038924/00-15',
          'summary_json' => [
            'EN' => 'English title.',
            'FR' => 'French title.',
          ],
        ],
        'langcode' => 'FR',
        'expected_caption' => 'French title.',
      ],
      // English is the fallback langcode used when the requested is not found.
      'langcode specified for summary_json / not existing caption' => [
        'data' => [
          'type' => 'PHOTO',
          'ref' => 'P-038924/00-15',
          'summary_json' => [
            'IT' => 'Italian caption.',
            'EN' => 'English caption.',
          ],
        ],
        'langcode' => 'FR',
        'expected_caption' => 'English caption.',
      ],
      // The first title available is returned when the fallback is not found.
      'no langcode specified for summary_json / fallback caption not existing' => [
        'data' => [
          'type' => 'PHOTO',
          'ref' => 'P-038924/00-15',
          'summary_json' => [
            'IT' => 'Italian caption.',
            'FR' => 'French caption.',
          ],
        ],
        'langcode' => NULL,
        'expected_caption' => 'Italian caption.',
      ],
      // Another iteration of the above with a different titles order.
      'no langcode specified in summary_json / different order of caption / fallback caption not existing' => [
        'data' => [
          'type' => 'PHOTO',
          'ref' => 'P-038924/00-15',
          'summary_json' => [
            'FR' => 'French caption.',
            'IT' => 'Italian caption.',
          ],
        ],
        'langcode' => NULL,
        'expected_caption' => 'French caption.',
      ],
      // When the requested language and the fallback one are not found, the
      // first available caption is returned.
      'langcode specified for summary_json / caption and fallback caption not existing' => [
        'data' => [
          'type' => 'PHOTO',
          'ref' => 'P-038924/00-15',
          'summary_json' => [
            'IT' => 'Italian title.',
            'DE' => 'German title.',
          ],
        ],
        'langcode' => 'FR',
        'expected_caption' => 'Italian title.',
      ],
      // Another iteration of the above with a different caption order.
      'langcode specified / different order of captions in summary_json / caption and fallback caption not existing' => [
        'data' => [
          'type' => 'PHOTO',
          'ref' => 'P-038924/00-15',
          'summary_json' => [
            'DE' => 'German caption.',
            'IT' => 'Italian caption.',
          ],
        ],
        'langcode' => 'FR',
        'expected_caption' => 'German caption.',
      ],
      'caption with encoded characters and markup in summary_json / langcode specified' => [
        'data' => [
          'type' => 'PHOTO',
          'ref' => 'P-038924/00-15',
          'summary_json' => [
            'FR' => 'French caption <br />&lt;strong&gt;with&lt;/strong&gt; markup, encoded &#39;characters&#39; &amp; letters čö&įię.',
          ],
        ],
        'langcode' => 'FR',
        'expected_caption' => 'French caption with markup, encoded \'characters\' & letters čö&įię.',
      ],
      'caption with encoded characters and markup in summary_json / fallback caption' => [
        'data' => [
          'type' => 'PHOTO',
          'ref' => 'P-038924/00-15',
          'summary_json' => [
            'EN' => 'English capture <br />&lt;strong&gt;with&lt;/strong&gt; markup, encoded &#39;characters&#39; &amp; letters čö&įię.',
          ],
        ],
        'langcode' => 'FR',
        'expected_caption' => 'English capture with markup, encoded \'characters\' & letters čö&įię.',
      ],
      'caption with encoded characters and markup in summary_json / first available caption' => [
        'data' => [
          'type' => 'PHOTO',
          'ref' => 'P-038924/00-15',
          'summary_json' => [
            'IT' => 'Italian capture <br />&lt;strong&gt;with&lt;/strong&gt; markup, encoded &#39;characters&#39; &amp; letters čö&įię.',
          ],
        ],
        'langcode' => 'FR',
        'expected_caption' => 'Italian capture with markup, encoded \'characters\' & letters čö&įię.',
      ],

      // Data for Reportage type.
      'missing legend_json' => [
        'data' => [
          'type' => 'REPORTAGE',
          'ref' => 'P-038924/00-15',
        ],
        'langcode' => NULL,
        'expected_caption' => NULL,
      ],
      'non-array legend_json' => [
        'data' => [
          'type' => 'REPORTAGE',
          'ref' => 'P-038924/00-15',
          'legend_json' => 'invalid title',
        ],
        'langcode' => NULL,
        'expected_caption' => NULL,
      ],
      'empty legend_json' => [
        'data' => [
          'type' => 'REPORTAGE',
          'ref' => 'P-038924/00-15',
          'legend_json' => [],
        ],
        'langcode' => NULL,
        'expected_caption' => NULL,
      ],
      'legend_json with NULL values' => [
        'data' => [
          'type' => 'REPORTAGE',
          'ref' => 'P-038924/00-15',
          'legend_json' => [
            'FR' => NULL,
            'EN' => NULL,
          ],
        ],
        'langcode' => 'FR',
        'expected_caption' => NULL,
      ],
      // @see https://www.php.net/manual/en/language.types.boolean.php#language.types.boolean.casting
      'caption evaluable to FALSE on casting' => [
        'data' => [
          'type' => 'REPORTAGE',
          'ref' => 'P-038924/00-15',
          'legend_json' => [
            'IT' => '0',
          ],
        ],
        'langcode' => 'IT',
        'expected_caption' => '0',
      ],
      'boolean caption' => [
        'data' => [
          'type' => 'REPORTAGE',
          'ref' => 'P-038924/00-15',
          'legend_json' => [
            'IT' => FALSE,
          ],
        ],
        'langcode' => 'IT',
        'expected_caption' => NULL,
      ],
      'scalar caption' => [
        'data' => [
          'type' => 'REPORTAGE',
          'ref' => 'P-038924/00-15',
          'legend_json' => [
            'EN' => ['English caption.'],
          ],
        ],
        'langcode' => 'EN',
        'expected_caption' => NULL,
      ],
      // English is the default langcode used when none is passed.
      'no langcode specified / existing caption' => [
        'data' => [
          'type' => 'REPORTAGE',
          'ref' => 'P-038924/00-15',
          'legend_json' => [
            'FR' => 'French caption.',
            'EN' => 'English caption.',
          ],
        ],
        'langcode' => NULL,
        'expected_caption' => 'English caption.',
      ],
      'langcode specified / existing caption' => [
        'data' => [
          'type' => 'REPORTAGE',
          'ref' => 'P-038924/00-15',
          'legend_json' => [
            'EN' => 'English title.',
            'FR' => 'French title.',
          ],
        ],
        'langcode' => 'FR',
        'expected_caption' => 'French title.',
      ],
      // English is the fallback langcode used when the requested is not found.
      'langcode specified / not existing caption' => [
        'data' => [
          'type' => 'REPORTAGE',
          'ref' => 'P-038924/00-15',
          'legend_json' => [
            'IT' => 'Italian caption.',
            'EN' => 'English caption.',
          ],
        ],
        'langcode' => 'FR',
        'expected_caption' => 'English caption.',
      ],
      // The first title available is returned when the fallback is not found.
      'no langcode specified / fallback caption not existing' => [
        'data' => [
          'type' => 'REPORTAGE',
          'ref' => 'P-038924/00-15',
          'legend_json' => [
            'IT' => 'Italian caption.',
            'FR' => 'French caption.',
          ],
        ],
        'langcode' => NULL,
        'expected_caption' => 'Italian caption.',
      ],
      // Another iteration of the above with a different titles order.
      'no langcode specified / different order of caption / fallback caption not existing' => [
        'data' => [
          'type' => 'REPORTAGE',
          'ref' => 'P-038924/00-15',
          'legend_json' => [
            'FR' => 'French caption.',
            'IT' => 'Italian caption.',
          ],
        ],
        'langcode' => NULL,
        'expected_caption' => 'French caption.',
      ],
      // When the requested language and the fallback one are not found, the
      // first available caption is returned.
      'langcode specified / caption and fallback caption not existing' => [
        'data' => [
          'type' => 'REPORTAGE',
          'ref' => 'P-038924/00-15',
          'legend_json' => [
            'IT' => 'Italian title.',
            'DE' => 'German title.',
          ],
        ],
        'langcode' => 'FR',
        'expected_caption' => 'Italian title.',
      ],
      // Another iteration of the above with a different caption order.
      'langcode specified / different order of captions / caption and fallback caption not existing' => [
        'data' => [
          'type' => 'REPORTAGE',
          'ref' => 'P-038924/00-15',
          'legend_json' => [
            'DE' => 'German caption.',
            'IT' => 'Italian caption.',
          ],
        ],
        'langcode' => 'FR',
        'expected_caption' => 'German caption.',
      ],
      'caption with encoded characters and markup / langcode specified' => [
        'data' => [
          'type' => 'REPORTAGE',
          'ref' => 'P-038924/00-15',
          'legend_json' => [
            'FR' => 'French caption <br />&lt;strong&gt;with&lt;/strong&gt; markup, encoded &#39;characters&#39; &amp; letters čö&įię.',
          ],
        ],
        'langcode' => 'FR',
        'expected_caption' => 'French caption with markup, encoded \'characters\' & letters čö&įię.',
      ],
      'caption with encoded characters and markup / fallback caption' => [
        'data' => [
          'type' => 'REPORTAGE',
          'ref' => 'P-038924/00-15',
          'legend_json' => [
            'EN' => 'English capture <br />&lt;strong&gt;with&lt;/strong&gt; markup, encoded &#39;characters&#39; &amp; letters čö&įię.',
          ],
        ],
        'langcode' => 'FR',
        'expected_caption' => 'English capture with markup, encoded \'characters\' & letters čö&įię.',
      ],
      'caption with encoded characters and markup / first available caption' => [
        'data' => [
          'type' => 'REPORTAGE',
          'ref' => 'P-038924/00-15',
          'legend_json' => [
            'IT' => 'Italian capture <br />&lt;strong&gt;with&lt;/strong&gt; markup, encoded &#39;characters&#39; &amp; letters čö&įię.',
          ],
        ],
        'langcode' => 'FR',
        'expected_caption' => 'Italian capture with markup, encoded \'characters\' & letters čö&įię.',
      ],
    ];
  }

}
