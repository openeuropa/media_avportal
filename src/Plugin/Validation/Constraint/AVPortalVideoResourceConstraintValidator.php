<?php

declare(strict_types = 1);

namespace Drupal\media_avportal\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\media_avportal\AvPortalClient;
use Drupal\media_avportal\Plugin\media\Source\MediaAvPortalInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates AVPortal resource URLs.
 */
class AVPortalVideoResourceConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {
  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The AV portal client.
   *
   * @var \Drupal\media_avportal\AvPortalClient
   */
  private $avPortalClient;

  /**
   * Constructs a new AVPortalResourceConstraintValidator.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger service.
   * @param \Drupal\media_avportal\AvPortalClient $avPortalClient
   *   The AV portal client.
   */
  public function __construct(LoggerChannelFactoryInterface $logger_factory, AvPortalClient $avPortalClient) {
    $this->logger = $logger_factory->get('media');
    $this->avPortalClient = $avPortalClient;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.factory'),
      $container->get('media_avportal.client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    /** @var \Drupal\media\MediaInterface $media */
    $media = $items->getEntity();
    /** @var \Drupal\media_avportal\Plugin\media\Source\MediaAvPortalVideo $source */
    $source = $media->getSource();

    if (!($source instanceof MediaAvPortalInterface)) {
      throw new \LogicException('Media source must implement ' . MediaAvPortalInterface::class);
    }

    $reference = $source->getSourceFieldValue($media);
    $resource = $this->avPortalClient->getResource($reference);

    if (NULL === $resource) {
      $this->context->addViolation($constraint->unknownProviderMessage, ['%ref%' => $reference]);
    }
  }

}
