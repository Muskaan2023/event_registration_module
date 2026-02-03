<?php

namespace Drupal\event_registration;

use Drupal\Core\Database\Connection;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Service class for Event Registration.
 */
class EventRegistrationService {
  
  use StringTranslationTrait;
  
  protected $database;
  protected $configFactory;
  protected $mailManager;
  
  /**
   * Constructs a new EventRegistrationService object.
   */
  public function __construct(Connection $database, ConfigFactoryInterface $config_factory, MailManagerInterface $mail_manager) {
    $this->database = $database;
    $this->configFactory = $config_factory;
    $this->mailManager = $mail_manager;
  }
  
  /**
   * Check if a registration already exists.
   */
  public function checkDuplicateRegistration($email, $event_config_id) {
    $query = $this->database->select('event_registration', 'er')
      ->condition('er.email', $email)
      ->condition('er.event_config_id', $event_config_id)
      ->countQuery();
    
    return (bool) $query->execute()->fetchField();
  }
  
  /**
   * Save registration.
   */
  public function saveRegistration(array $data) {
    $data['created'] = \Drupal::time()->getRequestTime();
    return $this->database->insert('event_registration')
      ->fields($data)
      ->execute();
  }
  
  /**
   * Get events by category and date.
   */
  public function getEventsByCategoryAndDate($category = NULL, $date = NULL) {
    $query = $this->database->select('event_config', 'ec');
    $query->fields('ec', ['id', 'event_name', 'event_date', 'category']);
    $query->condition('ec.start_date', \Drupal::time()->getRequestTime(), '<=');
    $query->condition('ec.end_date', \Drupal::time()->getRequestTime(), '>=');
    
    if ($category) {
      $query->condition('ec.category', $category);
    }
    
    if ($date) {
      $query->condition('ec.event_date', $date . ' 00:00:00', '>=');
      $query->condition('ec.event_date', $date . ' 23:59:59', '<=');
    }
    
    return $query->execute()->fetchAll();
  }
  
  /**
   * Send confirmation emails.
   */
  public function sendConfirmationEmails(array $registration_data, $event_config) {
    $config = $this->configFactory->get('event_registration.settings');
    
    // Email to user
    $params = [
      'subject' => $this->t('Event Registration Confirmation'),
      'body' => [
        '#theme' => 'event_registration_email',
        '#registration' => $registration_data,
        '#event' => $event_config,
      ],
    ];
    
    $this->mailManager->mail('event_registration', 'registration_confirmation', 
      $registration_data['email'], 'en', $params);
    
    // Email to admin if enabled
    if ($config->get('enable_admin_notifications')) {
      $admin_email = $config->get('admin_email');
      if ($admin_email) {
        $params['subject'] = $this->t('New Event Registration: @event', ['@event' => $event_config->event_name]);
        $this->mailManager->mail('event_registration', 'admin_notification', 
          $admin_email, 'en', $params);
      }
    }
  }
  
  /**
   * Get all registrations with filters.
   */
  public function getRegistrations($event_date = NULL, $event_name = NULL) {
    $query = $this->database->select('event_registration', 'er');
    $query->fields('er', ['id', 'full_name', 'email', 'college_name', 'department', 'created']);
    $query->addField('ec', 'event_name', 'event_name');
    $query->addField('ec', 'event_date', 'event_date');
    $query->join('event_config', 'ec', 'er.event_config_id = ec.id');
    
    if ($event_date) {
      $query->condition('ec.event_date', $event_date . ' 00:00:00', '>=');
      $query->condition('ec.event_date', $event_date . ' 23:59:59', '<=');
    }
    
    if ($event_name) {
      $query->condition('ec.id', $event_name);
    }
    
    $query->orderBy('er.created', 'DESC');
    
    return $query->execute()->fetchAll();
  }
  
  /**
   * Get registration count.
   */
  public function getRegistrationCount($event_config_id = NULL) {
    $query = $this->database->select('event_registration', 'er');
    $query->addExpression('COUNT(*)', 'count');
    
    if ($event_config_id) {
      $query->condition('er.event_config_id', $event_config_id);
    }
    
    return $query->execute()->fetchField();
  }
}