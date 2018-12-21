<?php

namespace Drupal\slack_receive\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SlackApplicationFormBase.
 *
 * @ingroup slack_receive
 */
class SlackApplicationForm extends EntityForm {

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityStorageInterface $entity_storage) {
    $this->entityStorage = $entity_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity.manager')
      ->getStorage('slack_receive_application'));
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $app = $this->entity;

    // Label.
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Application name'),
      '#description' => $this->t('Enter an arbitrary application name to refer to the Slack application you wish to register.'),
      '#maxlength' => 255,
      '#default_value' => $app->label(),
      '#required' => TRUE,
    ];

    // ID.
    $form['id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Machine name'),
      '#default_value' => $app->id(),
      '#machine_name' => [
        'exists' => [$this->entityStorage, 'load'],
        'replace_pattern' => '([^a-z0-9_]+)|(^custom$)',
        'error' => 'The machine-readable name must be unique, and can only contain lowercase letters, numbers, and underscores. Additionally, it can not be the reserved word "custom".',
      ],
      '#disabled' => !$app->isNew(),
    ];

    $form['key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Signing secret'),
      '#description' => $this->t('Enter a valid signing secret key. It can be found at @link.', ['@link' => Link::fromTextAndUrl('https://api.slack.com/apps', Url::fromUri('https://api.slack.com/apps'))->toString()]),
      '#default_value' => $app->getKey(),
      '#required' => TRUE,
    ];

    // Return the form.
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    if ($this->operation == 'add') {
      $actions['submit']['#value'] = $this->t('Register');
    } else {
      $actions['submit']['#value'] = $this->t('Save');
    }
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var $app \Drupal\slack_receive\Entity\SlackApplicationInterface */
    $values = $form_state->getValues();
    $app = $this->entity;
    $app->setLabel($values['label']);
    $app->setId($values['id']);
    $app->setKey($values['key']);
    $app->setStatus(TRUE);
    $status = $app->save();

    if ($status == SAVED_UPDATED) {
      $this->messenger()->addStatus($this->t('Slack application %label has been updated.', ['%label' => $app->label()]));
    }
    else {
      $this->messenger()->addStatus('Slack application %label has been added.', ['%label' => $app->label()]);
    }
    $form_state->setRedirect('entity.slack_receive.slack_receive_application.list');
  }

}
