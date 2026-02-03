<?php

namespace Drupal\event_registration\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Mail\MailManagerInterface;

/**
 * Service for sending email notifications.
 */
class EmailService {

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new EmailService.
   *
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(
    MailManagerInterface $mail_manager,
    ConfigFactoryInterface $config_factory
  ) {
    $this->mailManager = $mail_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * Send registration confirmation email.
   *
   * @param array $registration_data
   *   The registration data.
   */
  public function sendRegistrationEmail(array $registration_data) {
    $config = $this->configFactory->get('event_registration.settings');
    
    // Send to user
    $this->mailManager->mail(
      'event_registration',
      'registration_confirmation',
      $registration_data['email'],
      'en',
      [
        'subject' => t('Event Registration Confirmation'),
        'body' => $this->buildEmailBody($registration_data),
      ]
    );
    
    // Send to admin if enabled
    if ($config->get('admin_notifications')) {
      $admin_email = $config->get('admin_email');
      if ($admin_email) {
        $this->mailManager->mail(
          'event_registration',
          'admin_notification',
          $admin_email,
          'en',
          [
            'subject' => t('New Event Registration: @event', ['@event' => $registration_data['event_name']]),
            'body' => $this->buildEmailBody($registration_data, TRUE),
          ]
        );
      }
    }
  }

  /**
   * Build email body content.
   *
   * @param array $data
   *   The registration data.
   * @param bool $for_admin
   *   Whether the email is for admin.
   *
   * @return string
   *   The email body.
   */
  private function buildEmailBody(array $data, $for_admin = FALSE) {
    $body = '';
    
    if ($for_admin) {
      $body .= "New Event Registration:\n\n";
    } else {
      $body .= "Dear " . $data['full_name'] . ",\n\n";
      $body .= "Thank you for registering for our event!\n\n";
    }
    
    $body .= "Registration Details:\n";
    $body .= "Name: " . $data['full_name'] . "\n";
    $body .= "Email: " . $data['email'] . "\n";
    $body .= "Event: " . $data['event_name'] . "\n";
    $body .= "Date: " . $data['event_date'] . "\n";
    $body .= "Category: " . $data['event_category'] . "\n";
    $body .= "College: " . $data['college_name'] . "\n";
    $body .= "Department: " . $data['department'] . "\n\n";
    
    if (!$for_admin) {
      $body .= "We look forward to seeing you!\n";
    }
    
    return $body;
  }

}