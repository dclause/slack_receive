<?php

namespace Drupal\slack_receive\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Slack Application entity.
 *
 * @ConfigEntityType(
 *   id = "slack_receive_application",
 *   label = @Translation("Slack Application"),
 *   label_plural = @Translation("Slack Applications"),
 *   admin_permission = "administer slack receive",
 *   handlers = {
 *     "list_builder" =
 *   "Drupal\slack_receive\Controller\SlackApplicationListBuilder",
 *     "form" = {
 *       "add" = "Drupal\slack_receive\Form\SlackApplicationForm",
 *       "edit" = "Drupal\slack_receive\Form\SlackApplicationForm",
 *       "delete" = "Drupal\slack_receive\Form\SlackApplicationDeleteForm"
 *     }
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "key" = "key",
 *     "status" = "status"
 *   },
 *   links = {
 *     "add-form" = "/admin/config/services/slack_receive/application/add",
 *     "edit-form" = "/admin/config/services/slack_receive/application/{slack_receive_application}",
 *     "delete-form" = "/admin/config/services/slack_receive/application/{slack_receive_application}/delete"
 *   }
 * )
 */
class SlackApplication extends ConfigEntityBase implements SlackApplicationInterface {

  /**
   * The Slack Application ID.
   *
   * @var string
   */
  public $id;

  /**
   * The Slack application name.
   *
   * @var string
   */
  public $label;

  /**
   * The Slack application key.
   *
   * @var string
   */
  public $key;

  /**
   * The Slack application status.
   *
   * @var string
   */
  public $status;

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function getKey() {
    return $this->key;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * {@inheritdoc}
   */
  public function setId($id) {
    $this->id = $id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->label = $label;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setKey($key) {
    $this->key = $key;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status) {
    $this->status = $status;
    return $this;
  }
}
