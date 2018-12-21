<?php

namespace Drupal\slack_receive\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines the Slack Application entity.
 */
interface SlackApplicationInterface extends ConfigEntityInterface {

  /**
   * Get application ID.
   */
  public function getId();

  /**
   * Get application name.
   */
  public function getLabel();

  /**
   * Get application signing secret key.
   */
  public function getKey();

  /**
   * Get application status.
   */
  public function getStatus();

  /**
   * Set the application ID.
   *
   * @param $id (string)
   *    The application ID.
   */
  public function setId($id);

  /**
   * Set application name.
   *
   * @param $label
   *    The application name.
   */
  public function setLabel($label);

  /**
   * Set application signing secret key.
   *
   * @param $key string
   *    The signing secret key.
   */
  public function setKey($key);

  /**
   * Set application status.
   *
   * @param $status bool
   *    The status.
   */
  public function setStatus($status);
}
