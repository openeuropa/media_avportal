<?php

declare(strict_types = 1);

namespace Drupal\Tests\media_avportal\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\media\Functional\MediaFunctionalTestTrait;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;

/**
 * Base class for Media AV Portal functional JavaScript tests.
 */
class MediaAVPortalCreateContentTest extends WebDriverTestBase {

  use MediaFunctionalTestTrait;
  use MediaTypeCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'node',
    'field_ui',
    'views_ui',
    'media_avportal',
  ];

  /**
   * Test media type "Media AV Portal" creation.
   *
   * @throws \Throwable
   */
  public function testMediaContentCreation() {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet('<front>');

    $user = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($user);

    // Create the Media AV portal media bundle
    $this->drupalGet('admin/structure/media/add');
    $page->fillField('label', 'Media AV Portal');
    $this->assertNotEmpty(
      $assert_session->waitForElementVisible('css', '.machine-name-value')
    );
    $assert_session->selectExists('Media source')->selectOption('media_avportal_video');
    $this->assertNotEmpty(
      $assert_session->waitForElementVisible('css', 'fieldset[data-drupal-selector="edit-source-configuration"]')
    );
    $page->pressButton('Save');
    $page->hasContent('The media type Media AV Portal has been added.');

    // Set the formatter.
    $config = $this->config('core.entity_view_display.media.media_av_portal.default');
    $config->set('content.field_media_media_avportal_video.type', 'avportal');
    $config->set('content.field_media_media_avportal_video.settings', []);
    $config->save();

    // Create a media content.
    $this->drupalGet('media/add');
    $page->fillField('Media AV Portal Video', 'http://ec.europa.eu/avservices/play.cfm?autoplay=true&lg=EN&ref=I-162747');
    $page->pressButton('Save');

    // Visit the new media content.
    $page->clickLink('Midday press briefing from 25/10/2018');

    // Check the iframe URL.
    $iframe_url = $assert_session->elementExists('css', 'iframe')->getAttribute('src');
    self::assertEquals('http://ec.europa.eu/avservices/play.cfm?autoplay=true&lg=EN&ref=I-162747&sublg=none&tin=10&tout=59', $iframe_url);
  }

  /**
   * Test media type "Media AV Portal" edition.
   *
   * @throws \Throwable
   */
  public function testMediaContentEdition() {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    // Create a media content with a valid reference.
    $this->drupalGet('media/add');
    $page->fillField('Media AV Portal Video', 'http://ec.europa.eu/avservices/play.cfm?autoplay=true&lg=EN&ref=I-162747');
    $page->pressButton('Save');

    // Visit the new media content.
    $page->clickLink('Midday press briefing from 25/10/2018');

    // Check the iframe URL.
    $iframe_url = $assert_session->elementExists('css', 'iframe')->getAttribute('src');
    self::assertEquals('http://ec.europa.eu/avservices/play.cfm?autoplay=true&lg=EN&ref=I-162747&sublg=none&tin=10&tout=59', $iframe_url);

    // Edit the newly created media.
    $this->drupalGet('media/1/edit');

    // Update the field.
    $page->fillField('Media AV Portal Video', 'https://ec.europa.eu/avservices/video/player.cfm?sitelang=en&ref=I-163162');
    $page->pressButton('Save');

    // Visit the updated media content.
    $page->clickLink('Economic and Financial Affairs Council - Arrivals');

    // Check the iframe URL.
    $iframe_url = $assert_session->elementExists('css', 'iframe')->getAttribute('src');
    self::assertEquals('http://ec.europa.eu/avservices/play.cfm?autoplay=true&lg=EN&ref=I-163162&sublg=none&tin=10&tout=59', $iframe_url);
  }

}
