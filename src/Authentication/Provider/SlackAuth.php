<?php

namespace Drupal\slack_receive\Authentication\Provider;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Flood\FloodInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Slash Command authentication provider.
 *
 * @ingroup slack_receive
 */
class SlackAuth implements AuthenticationProviderInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The flood service.
   *
   * @var \Drupal\Core\Flood\FloodInterface
   */
  protected $flood;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The time service
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a Slack Authentication provider object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager service.
   * @param \Drupal\Core\Flood\FloodInterface $flood
   *   The flood service.
   * @param \Drupal\Component\Datetime\TimeInterface
   *    The time service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, FloodInterface $flood, TimeInterface $time) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->flood = $flood;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Request $request) {
    $timestamp = $request->headers->get('X-Slack-Request-Timestamp');
    // If the request timestamp is more than five minutes from local time,
    // it could be a replay attack, so let's ignore it.
    $requesTimeStamp = $this->time->getRequestTime();
    return (!empty($timestamp) && abs($requesTimeStamp - $timestamp) < 60 * 5);
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate(Request $request) {
    $flood_config = $this->configFactory->get('user.flood');
    $slack_auth_config = $this->configFactory->get('slack_receive.settings');

    // Flood protection: this is very similar to the user login form code.
    // @see \Drupal\user\Form\UserLoginForm::validateAuthentication()
    // Do not allow any login from the current user's IP if the limit has been
    // reached. Default is 50 failed attempts allowed in one hour. This is
    // independent of the per-user limit to catch attempts from one IP to log
    // in to many different user accounts.  We have a reasonably high limit
    // since there may be only one apparent IP for all users at an institution.
    // NOTE: We won't register a per-user flood event here since Slack operates
    // as an anonymous user.
    if ($this->flood->isAllowed('slack_auth.failed_login_ip', $flood_config->get('ip_limit'), $flood_config->get('ip_window'))) {

      if (!$slack_auth_config->get('authenticate')) {
        // Always authorize.
        // Slack does not authentify in itself, so we use the anonymous user for requesting.
        return $this->entityTypeManager->getStorage('user')->load(0);
      }
      // Retrieve authorized applications.
      $applications = $this->entityTypeManager->getStorage('slack_receive_application')
        ->loadByProperties([
          'status' => TRUE,
        ]);
      /** @var \Drupal\slack_receive\Entity\SlackApplicationInterface $app */
      foreach ((array) $applications as $app) {
        // Slack authentication control.
        $timestamp = $request->headers->get('X-Slack-Request-Timestamp');
        $verified_signature = $request->headers->get('X-Slack-Signature');
        $concat = "v0:$timestamp:" . $request->getContent();
        $computed_signature = hash_hmac('sha256', $concat, $app->getKey());
        if ($verified_signature == "v0=$computed_signature") {
          // Slack does not authentify in itself, so we use the anonymous user for requesting.
          return $this->entityTypeManager->getStorage('user')->load(0);
        }
      }
    }
    // Always register an IP-based failed login event.
    $this->flood->register('slack_receive.slack_auth', $flood_config->get('ip_window'));
    throw new AccessDeniedHttpException('Invalid consumer origin.');
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function handleException(GetResponseForExceptionEvent $event) {
    $exception = $event->getException();
    if ($exception instanceof AccessDeniedHttpException) {
      $event->setException(new UnauthorizedHttpException('Invalid consumer origin.', $exception));
      return TRUE;
    }
    return FALSE;
  }
}