<?php

namespace Drupal\event_registration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Event registration form.
 */
class EventRegistrationForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'event_registration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get events from database
    $database = \Drupal::database();
    $events = $database->select('event_config', 'ec')
      ->fields('ec', ['id', 'event_name', 'event_date'])
      ->condition('ec.registration_start_date', date('Y-m-d'), '<=')
      ->condition('ec.registration_end_date', date('Y-m-d'), '>=')
      ->execute()
      ->fetchAll();

    $event_options = [];
    foreach ($events as $event) {
      $event_options[$event->id] = $event->event_name . ' (' . $event->event_date . ')';
    }

    $form['full_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Full Name'),
      '#required' => TRUE,
      '#attributes' => [
        'pattern' => '[A-Za-z\\s]+',
        'title' => $this->t('Only letters and spaces allowed'),
      ],
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email Address'),
      '#required' => TRUE,
    ];

    $form['college_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('College Name'),
      '#required' => TRUE,
      '#attributes' => [
        'pattern' => '[A-Za-z0-9\\s\\-\\.]+',
        'title' => $this->t('Letters, numbers, spaces, hyphens and dots allowed'),
      ],
    ];

    $form['department'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Department'),
      '#required' => TRUE,
      '#attributes' => [
        'pattern' => '[A-Za-z\\s]+',
        'title' => $this->t('Only letters and spaces allowed'),
      ],
    ];

    if (!empty($event_options)) {
      $form['event_id'] = [
        '#type' => 'select',
        '#title' => $this->t('Select Event'),
        '#options' => $event_options,
        '#required' => TRUE,
        '#empty_option' => $this->t('- Select -'),
      ];
    } else {
      $form['no_events'] = [
        '#markup' => '<p>' . $this->t('No events available for registration at this time.') . '</p>',
      ];
    }

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Register'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Check for special characters
    $full_name = $form_state->getValue('full_name');
    if (preg_match('/[^A-Za-z\s]/', $full_name)) {
      $form_state->setErrorByName('full_name', 
        $this->t('Special characters are not allowed in name.'));
    }

    // Check duplicate registration
    $email = $form_state->getValue('email');
    $event_id = $form_state->getValue('event_id');
    
    if ($email && $event_id) {
      $database = \Drupal::database();
      
      // Get event date
      $event_date = $database->select('event_config', 'ec')
        ->fields('ec', ['event_date'])
        ->condition('ec.id', $event_id)
        ->execute()
        ->fetchField();

      // Check if already registered
      $count = $database->select('event_registration', 'er')
        ->condition('er.email', $email)
        ->condition('er.event_date', $event_date)
        ->countQuery()
        ->execute()
        ->fetchField();

      if ($count > 0) {
        $form_state->setErrorByName('email', 
          $this->t('You have already registered for an event on this date.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $database = \Drupal::database();
    
    // Get event details
    $event_id = $form_state->getValue('event_id');
    $event = $database->select('event_config', 'ec')
      ->fields('ec', ['event_name', 'event_category', 'event_date'])
      ->condition('ec.id', $event_id)
      ->execute()
      ->fetchObject();

    // Save registration
    $database->insert('event_registration')
      ->fields([
        'full_name' => $form_state->getValue('full_name'),
        'email' => $form_state->getValue('email'),
        'college_name' => $form_state->getValue('college_name'),
        'department' => $form_state->getValue('department'),
        'event_category' => $event->event_category,
        'event_date' => $event->event_date,
        'event_name' => $event->event_name,
        'event_config_id' => $event_id,
      ])
      ->execute();

    // Send email (basic version)
    $this->sendConfirmationEmail($form_state, $event);

    \Drupal::messenger()->addMessage($this->t('Registration successful! Confirmation email sent.'));
  }

  /**
   * Send confirmation email.
   */
  private function sendConfirmationEmail($form_state, $event) {
    $to = $form_state->getValue('email');
    $subject = 'Event Registration Confirmation';
    
    $body = "Dear " . $form_state->getValue('full_name') . ",\n\n";
    $body .= "Thank you for registering for our event!\n\n";
    $body .= "Registration Details:\n";
    $body .= "Name: " . $form_state->getValue('full_name') . "\n";
    $body .= "Event: " . $event->event_name . "\n";
    $body .= "Date: " . $event->event_date . "\n";
    $body .= "Category: " . $event->event_category . "\n";
    $body .= "College: " . $form_state->getValue('college_name') . "\n";
    $body .= "Department: " . $form_state->getValue('department') . "\n\n";
    $body .= "We look forward to seeing you!\n";

    // Simple email sending
    $mail_manager = \Drupal::service('plugin.manager.mail');
    $params = [
      'subject' => $subject,
      'body' => $body,
    ];

    $mail_manager->mail('event_registration', 'registration', $to, 'en', $params);
  }

}