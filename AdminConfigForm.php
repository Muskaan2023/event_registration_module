<?php

namespace Drupal\event_registration\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Admin configuration form.
 */
class AdminConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'event_registration_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['event_registration.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('event_registration.settings');

    $form['admin_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Admin Notification Email'),
      '#default_value' => $config->get('admin_email'),
      '#required' => TRUE,
      '#description' => $this->t('Email address to receive registration notifications.'),
    ];

    $form['admin_notifications'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable admin notifications'),
      '#default_value' => $config->get('admin_notifications'),
      '#description' => $this->t('Send email notifications to admin for new registrations.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('event_registration.settings')
      ->set('admin_email', $form_state->getValue('admin_email'))
      ->set('admin_notifications', $form_state->getValue('admin_notifications'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}