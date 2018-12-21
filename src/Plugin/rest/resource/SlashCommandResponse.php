<?php

namespace Drupal\slack_receive\Plugin\rest\resource;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Provides a resource for receiving slash commands from slack.
 *
 * @ingroup slack_receive
 *
 * @RestResource(
 *   id = "slack_receive",
 *   label = @Translation("Slash command entry point."),
 *   uri_paths = {
 *     "create" = "/api/slash/command"
 *   }
 * )
 */
class SlashCommandResponse extends ResourceBase {

  /**
   * The ModuleHandler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface
   *   The module handler service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->getParameter('serializer.formats'), $container->get('logger.factory')
      ->get('rest'), $container->get('module_handler'));
  }

  /**
   * Responds to POST requests.
   */
  public function post(array $data) {
    if (!empty($data)) {

      $command = $data['command'] ?? NULL;
      $text = $data['text'] ?? NULL;

      // Check command validity (should be not empty and start by /)
      if (!$command || substr($command, 0, 1) !== '/' || !$text) {
        throw new BadRequestHttpException(t('No command was sent'));
      }

      // Pass command to all registered modules.
      $result = $this->moduleHandler->invokeAll('slack_receive_slash_command', [
        substr($command, 1),
        trim($text),
      ]);
      return new ModifiedResourceResponse($result);
    }
    throw new BadRequestHttpException(t('No entry was provided'));
  }
}
