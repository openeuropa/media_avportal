<?php

declare(strict_types = 1);

namespace Drupal\Tests\media_avportal\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Base class for Media AV Portal functional JavaScript tests.
 */
class MediaAvPortalCreateContentTest extends WebDriverTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'node',
    'field_ui',
    'media_avportal',
    'media_avportal_mock',
  ];

  /**
   * Tests the AV Portal media entity.
   */
  public function testAvPortalMediaEntity(): void {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    // Log in as an administrator.
    $user = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($user);

    // Create the Media AV portal media bundle.
    $this->drupalGet('admin/structure/media/add');
    $page->fillField('label', 'Media AV Portal');
    $this->assertNotEmpty(
      $assert_session->waitForElementVisible('css', '.machine-name-value')
    );
    $assert_session->selectExists('Media source')->selectOption('media_avportal_video');
    $this->assertNotEmpty(
      $assert_session->waitForElementVisible('css', 'fieldset[data-drupal-selector="edit-source-configuration"]')
    );
    $assert_session->selectExists('Resource title')->selectOption('name');
    $page->pressButton('Save');
    $page->hasContent('The media type Media AV Portal Test has been added.');

    // Set the formatter so that we can view Media of this type.
    $config = $this->config('core.entity_view_display.media.media_av_portal.default');
    $config->set('content.field_media_media_avportal_video.type', 'avportal');
    $config->set('content.field_media_media_avportal_video.settings', []);
    $config->save();

    // Create a media content with a valid reference.
    $this->drupalGet('media/add/media_av_portal');
    $page->fillField('Media AV Portal Video', 'http//ec.europa.eu/avservices/play.cfm?autoplay=true&lg=EN&ref=I-162747');
    $page->pressButton('Save');

    // Visit the new media content.
    $page->clickLink('Midday press briefing from 25/10/2018');

    // Check the iframe URL.
    $iframe_url = $assert_session->elementExists('css', 'iframe')->getAttribute('src');
    $this->assertEquals('//ec.europa.eu/avservices/play.cfm?autoplay=true&lg=EN&ref=I-162747&sublg=none&tin=10&tout=59', $iframe_url);

    // @todo assert the width and height of the iframe.

    // Edit the newly created media.
    $this->drupalGet('media/1/edit');

    // Update the field.
    $page->fillField('Media AV Portal Video', 'http://ec.europa.eu/avservices/video/player.cfm?sitelang=en&ref=I-163162');
    $page->pressButton('Save');

    // Visit the updated media content.
    $page->clickLink('Economic and Financial Affairs Council - Arrivals');

    // Check the iframe URL.
    $iframe_url = $assert_session->elementExists('css', 'iframe')->getAttribute('src');
    $this->assertEquals('//ec.europa.eu/avservices/play.cfm?autoplay=true&lg=EN&ref=I-163162&sublg=none&tin=10&tout=59', $iframe_url);

    // Create a media content with an invalid reference.
    $this->drupalGet('media/add/media_av_portal');
    $page->fillField('Media AV Portal Video', 'http://ec.europa.eu/avservices/play.cfm?autoplay=true&lg=EN&ref=I-12345678987654321');
    $page->pressButton('Save');

    $assert_session->pageTextContains('The given URL does not match an AV Portal URL.');

    // Create a media content with an invalid resource URL.
    $this->drupalGet('media/add/media_av_portal');
    $page->fillField('Media AV Portal Video', 'http://example.com/play.cfm?autoplay=true&lg=EN&ref=I-12345678987654321');
    $page->pressButton('Save');

    $assert_session->pageTextContains('Invalid URL format specified.');
  }

}
