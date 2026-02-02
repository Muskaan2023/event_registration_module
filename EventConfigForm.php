<?php

namespace Drupal\event_registration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Event configuration form.
 */
class EventConfigForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'event_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['event_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Event Name'),
      '#required' => TRUE,
      '#maxlength' => 255,
    ];

    $form['event_category'] = [
      '#type' => 'select',
      '#title' => $this->t('Category of the event'),
      '#options' => [
        'Online Workshop' => $this->t('Online Workshop'),
        'Hackathon' => $this->t('Hackathon'),
        'Conference' => $this->t('Conference'),
        'One-day Workshop' => $this->t('One-day Workshop'),
      ],
      '#required' => TRUE,
    ];

    $form['registration_start_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Registration Start Date'),
      '#required' => TRUE,
    ];

    $form['registration_end_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Registration End Date'),
      '#required' => TRUE,
    ];

    $form['event_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Event Date'),
      '#required' => TRUE,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Event'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $start_date = $form_state->getValue('registration_start_date');
    $end_date = $form_state->getValue('registration_end_date');
    $event_date = $form_state->getValue('event_date');

    if ($start_date && $end_date && strtotime($end_date) < strtotime($start_date)) {
      $form_state->setErrorByName('registration_end_date', 
        $this->t('Registration end date must be after start date.'));
    }

    if ($end_date && $event_date && strtotime($event_date) < strtotime($end_date)) {
      $form_state->setErrorByName('event_date',
        $this->t('Event date must be after registration end date.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save to database
    $database = \Drupal::database();
    $database->insert('event_config')
      ->fields([
        'event_name' => $form_state->getValue('event_name'),
        'event_category' => $form_state->getValue('event_category'),
        'registration_start_date' => $form_state->getValue('registration_start_date'),
        'registration_end_date' => $form_state->getValue('registration_end_date'),
        'event_date' => $form_state->getValue('event_date'),
      ])
      ->execute();

    // Show success message
    \Drupal::messenger()->addMessage($this->t('Event saved successfully!'));
  }

}