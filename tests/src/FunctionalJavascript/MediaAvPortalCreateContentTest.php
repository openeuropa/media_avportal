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
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $config_factory = \Drupal::configFactory();
    $config = $config_factory->getEditable('media.settings');
    $config->set('standalone_url', TRUE);
    $config->save();

    $this->container->get('router.builder')->rebuild();
  }

  /**
   * Tests the AV Portal video media entity.
   */
  public function testAvPortalVideoMediaEntity(): void {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    // Log in as an administrator.
    $user = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($user);

    // Create the Media AV portal media video bundle.
    $this->drupalGet('admin/structure/media/add');
    $page->fillField('label', 'Media AV Portal video');
    $this->assertNotEmpty(
      $assert_session->waitForElementVisible('css', '.machine-name-value')
    );
    $assert_session->selectExists('Media source')->selectOption('media_avportal_video');
    $this->assertNotEmpty(
      $assert_session->waitForElementVisible('css', 'fieldset[data-drupal-selector="edit-source-configuration"]')
    );
    $assert_session->selectExists('Resource title')->selectOption('name');
    $page->pressButton('Save');
    $page->hasContent('The media type Media AV Portal video Test has been added.');

    // Set the formatter so that we can view Media of this type.
    $config = $this->config('core.entity_view_display.media.media_av_portal_video.default');
    $config->set('content.field_media_media_avportal_video.type', 'avportal_video');
    $config->set('content.field_media_media_avportal_video.settings', []);
    $config->save();

    // Create a media content with a valid reference.
    $this->drupalGet('media/add/media_av_portal_video');
    $page->fillField('Media AV Portal Video', 'https://audiovisual.ec.europa.eu/en/video/I-162747');
    $page->pressButton('Save');

    // Visit the new media content.
    $page->clickLink('Midday press briefing from 25/10/2018');

    // Check the iframe URL.
    $iframe_url = $assert_session->elementExists('css', 'iframe')->getAttribute('src');
    $this->assertContains('ec.europa.eu/avservices/play.cfm', $iframe_url);
    $this->assertContains('ref=I-162747', $iframe_url);

    // @todo assert the width and height of the iframe.

    // Edit the newly created media.
    $this->drupalGet('media/1/edit');

    // Update the field.
    $page->fillField('Media AV Portal Video', 'https://audiovisual.ec.europa.eu/en/video/I-163162');
    $page->pressButton('Save');

    // Visit the updated media content.
    $page->clickLink('Economic and Financial Affairs Council - Arrivals');

    // Check the iframe URL.
    $iframe_url = $assert_session->elementExists('css', 'iframe')->getAttribute('src');
    $this->assertContains('ec.europa.eu/avservices/play.cfm', $iframe_url);
    $this->assertContains('ref=I-163162', $iframe_url);

    // Create a media content with an invalid reference.
    $this->drupalGet('media/add/media_av_portal_video');
    $page->fillField('Media AV Portal Video', 'https://audiovisual.ec.europa.eu/en/video/I-12345678987654321');
    $page->pressButton('Save');

    $assert_session->pageTextContains('The given URL does not match an AV Portal URL.');

    // Create a media content with an invalid resource URL.
    $this->drupalGet('media/add/media_av_portal_video');
    $page->fillField('Media AV Portal Video', 'https://example.com/en/video/I-12345678987654321');
    $page->pressButton('Save');

    $assert_session->pageTextContains('Invalid URL format specified.');
  }

  /**
   * Tests the AV Portal photo media entity.
   */
  public function testAvPortalPhotoMediaEntity(): void {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    // Log in as an administrator.
    $user = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($user);

    // Create the Media AV portal media photo bundle.
    $this->drupalGet('admin/structure/media/add');
    $page->fillField('label', 'Media AV Portal Photo');
    $this->assertNotEmpty(
      $assert_session->waitForElementVisible('css', '.machine-name-value')
    );
    $assert_session->selectExists('Media source')->selectOption('media_avportal_photo');
    $this->assertNotEmpty(
      $assert_session->waitForElementVisible('css', 'fieldset[data-drupal-selector="edit-source-configuration"]')
    );
    $assert_session->selectExists('Resource title')->selectOption('name');
    $page->pressButton('Save');
    $page->hasContent('The media type Media AV Portal photo Test has been added.');

    // Set the formatter so that we can view Media of this type.
    $config = $this->config('core.entity_view_display.media.media_av_portal_photo.default');
    $config->set('content.field_media_media_avportal_photo.type', 'avportal_photo');
    $config->set('content.field_media_media_avportal_photo.settings', [
      'image_style' => '',
    ]);
    $config->save();

    // Create a media content with a valid reference.
    $this->drupalGet('media/add/media_av_portal_photo');
    $page->fillField('Media AV Portal Photo', 'https://audiovisual.ec.europa.eu/en/photo/P-038924~2F00-15');
    $page->pressButton('Save');

    // Visit the new media content.
    $page->clickLink('Euro with miniature figurines');

    // Check the image URL.
    $image_url = $assert_session->elementExists('css', 'img.avportal-photo')->getAttribute('src');
    $this->assertContains('ec.europa.eu/avservices/avs/files/video6/repository/prod/photo/store/', $image_url);
    $this->assertContains('P038924-352937.jpg', $image_url);

    // Edit the newly created media.
    $this->drupalGet('media/1/edit');

    // Update the field.
    $page->fillField('Media AV Portal Photo', 'https://audiovisual.ec.europa.eu/en/photo/P-039162~2F00-12');
    $page->pressButton('Save');

    // Visit the updated media content.
    $page->clickLink('Andrus Ansip Vice-President of the EC addresses the Plenary of the European Parliament on the beginning of the Romanian Presidency of the Council of the EU');

    // Check the image URL.
    $image_url = $assert_session->elementExists('css', 'img.avportal-photo')->getAttribute('src');
    $this->assertContains('ec.europa.eu/avservices/avs/files/video6/repository/prod/photo/store/', $image_url);
    $this->assertContains('P039162-137797.jpg', $image_url);

    // Create a media content with an invalid reference.
    $this->drupalGet('media/add/media_av_portal_photo');
    $page->fillField('Media AV Portal Photo', 'https://audiovisual.ec.europa.eu/en/photo/P-0391620~2F00-12');
    $page->pressButton('Save');

    $assert_session->pageTextContains('The given URL does not match an AV Portal URL.');

    // Create a media content with an invalid resource URL.
    $this->drupalGet('media/add/media_av_portal_photo');
    $page->fillField('Media AV Portal Photo', 'https://example.com/en/photo/P-0391620~2F00-12');
    $page->pressButton('Save');

    $assert_session->pageTextContains('Invalid URL format specified.');

    // Test that the formatter works with the image styles.
    $config = $this->config('core.entity_view_display.media.media_av_portal_photo.default');
    $config->set('content.field_media_media_avportal_photo.settings', [
      'image_style' => 'large',
    ]);
    $config->save();
    // We need to invalidate this cache tag because otherwise the change in
    // config does not show up. This is normally handled by the Entity display
    // form save which we are not reproducing here.
    $this->container->get('cache_tags.invalidator')->invalidateTags(['media_view']);
    $this->drupalGet('media/1');
    $image_url = $assert_session->elementExists('css', 'img.avportal-photo')->getAttribute('src');
    $this->assertContains('files/styles/large/avportal/P-039162/00-12.jpg', $image_url);
  }

}
