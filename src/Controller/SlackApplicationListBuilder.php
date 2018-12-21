<?php

namespace Drupal\slack_receive\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a listing of slack_receive_application entities.
 *
 * @ingroup slack_receive
 */
class SlackApplicationListBuilder extends ConfigEntityListBuilder implements FormInterface {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'slack_receive_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['slack_receive.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $settings = \Drupal::config('slack_receive.settings');

    $form['authenticate'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Require authenticated requests (<strong>DO NOT disable in production</strong>)'),
      '#default_value' => $settings->get('authenticate'),
    ];

    $form['container'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="authenticate"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Define the table of authorized apps.
    $form['container']['applications'] = [
      '#type' => 'table',
      '#title' => 'Slack Applications',
      '#header' => [
        ['data' => $this->t('Authorized'), 'style' => 'width: 1em;'],
        $this->t('Application name'),
        $this->t('Signing secret'),
        ['data' => $this->t('Operations'), 'style' => 'width: 2em;'],
      ],
      '#empty' => $this->t('There are no @label yet.', ['@label' => $this->entityType->getPluralLabel()]),
      '#cache' => [
        'contexts' => $this->entityType->getListCacheContexts(),
        'tags' => $this->entityType->getListCacheTags(),
      ],
    ];

    // Retrieve existing authorized apps.
    $app_ids = $this->getEntityIds();
    $applications = $this->storage->loadMultiple($app_ids);

    /** @var $app \Drupal\slack_receive\Entity\SlackApplicationInterface */
    foreach ($applications as $app_id => $app) {
      $form['container']['applications'][$app_id]['#attributes'] = [
        'id' => [$app_id],
      ];
      $form['container']['applications'][$app_id]['authorized'] = [
        '#type' => 'checkbox',
        '#default_value' => $app->getStatus(),
      ];
      $form['container']['applications'][$app_id]['label'] = [
        '#markup' => $app->getLabel(),
      ];
      $form['container']['applications'][$app_id]['key'] = [
        '#markup' => $app->getkey(),
      ];
      $form['container']['applications'][$app_id]['operations'] = $this->buildOperations($app);
    }

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save changes'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Nothing to validate.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Save global configurations.
    \Drupal::configFactory()
      ->getEditable('slack_receive.settings')
      ->set('authenticate', $values['authenticate'])
      ->save();

    // Save all configuration activations.
    $applications = $this->storage->loadMultiple(array_keys((array) $form_state->getValue('applications')));
    /** @var $app \Drupal\slack_receive\Entity\SlackApplicationInterface */
    foreach ((array) $applications as $app_id => $app) {
      $app_values = $form_state->getValue(['applications', $app_id]);
      $app->setStatus($app_values['authorized']);
      $app->save();
    }
    $this->messenger()->addMessage($this->t('Data have been saved.'));
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    // Overrides because we need control over table display.
    return \Drupal::formBuilder()->getForm($this);
  }
}
