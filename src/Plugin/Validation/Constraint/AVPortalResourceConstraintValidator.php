<?php

declare(strict_types = 1);

namespace Drupal\media_avportal\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\media_avportal\Plugin\media\Source\MediaAvPortalInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates AVPortal resource URLs.
 */
class AVPortalResourceConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {
  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs a new AVPortalResourceConstraintValidator.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger service.
   */
  public function __construct(LoggerChannelFactoryInterface $logger_factory) {
    $this->logger = $logger_factory->get('media');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    /** @var \Drupal\media\MediaInterface $media */
    $media = $items->getEntity();
    /** @var \Drupal\media_avportal\Plugin\media\Source\MediaAvPortal $source */
    $source = $media->getSource();

    if (!($source instanceof MediaAvPortalInterface)) {
      throw new \LogicException('Media source must implement ' . MediaAvPortalInterface::class);
    }

    // Todo What do we what to validate here ?
    $url = $source->getSourceFieldValue($media);
  }

}
