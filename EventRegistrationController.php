<?php

namespace Drupal\event_registration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\event_registration\EventRegistrationService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Database\Connection;

/**
 * Returns responses for Event Registration routes.
 */
class EventRegistrationController extends ControllerBase {
  
  protected $eventRegistrationService;
  protected $database;
  
  /**
   * {@inheritdoc}
   */
  public function __construct(EventRegistrationService $event_registration_service, Connection $database) {
    $this->eventRegistrationService = $event_registration_service;
    $this->database = $database;
  }
  
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('event_registration.service'),
      $container->get('database')
    );
  }
  
  /**
   * Admin registrations page.
   */
  public function adminRegistrations() {
    $build = [];
    
    // Get unique event dates for dropdown
    $query = $this->database->select('event_config', 'ec');
    $query->addField('ec', 'event_date');
    $query->distinct();
    $query->orderBy('ec.event_date', 'DESC');
    $results = $query->execute()->fetchAll();
    
    $event_dates = ['' => $this->t('- Select Date -')];
    foreach ($results as $result) {
      $date = new \DateTime($result->event_date);
      $date_str = $date->format('Y-m-d');
      $formatted_date = $date->format('F j, Y');
      $event_dates[$date_str] = $formatted_date;
    }
    
    $build['filters'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['event-registration-filters']],
    ];
    
    $build['filters']['event_date'] = [
      '#type' => 'select',
      '#title' => $this->t('Event Date'),
      '#options' => $event_dates,
      '#attributes' => ['id' => 'admin-event-date'],
      '#ajax' => [
        'callback' => '::ajaxEventNamesCallback',
        'wrapper' => 'admin-event-names-wrapper',
      ],
    ];
    
    $build['filters']['event_names_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'admin-event-names-wrapper'],
    ];
    
    $build['count_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'registration-count-wrapper'],
      '#markup' => '<div id="registration-count"></div>',
    ];
    
    $build['export'] = [
      '#type' => 'link',
      '#title' => $this->t('Export as CSV'),
      '#url' => \Drupal\Core\Url::fromRoute('event_registration.export'),
      '#attributes' => [
        'class' => ['button'],
        'id' => 'export-csv',
        'style' => 'display: none;',
      ],
    ];
    
    $build['table_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'registrations-table-wrapper'],
    ];
    
    $build['#attached']['library'][] = 'event_registration/admin';
    
    return $build;
  }
  
  /**
   * AJAX callback for event names.
   */
  public function ajaxEventNamesCallback(array &$form, FormStateInterface $form_state) {
    $selected_date = \Drupal::request()->request->get('event_date');
    
    if ($selected_date) {
      $query = $this->database->select('event_config', 'ec');
      $query->fields('ec', ['id', 'event_name']);
      $query->condition('ec.event_date', $selected_date . ' 00:00:00', '>=');
      $query->condition('ec.event_date', $selected_date . ' 23:59:59', '<=');
      $results = $query->execute()->fetchAll();
      
      $event_names = ['' => $this->t('- Select Event -')];
      foreach ($results as $result) {
        $event_names[$result->id] = $result->event_name;
      }
      
      $response = [
        '#type' => 'select',
        '#title' => $this->t('Event Name'),
        '#options' => $event_names,
        '#attributes' => ['id' => 'admin-event-name'],
        '#ajax' => [
          'callback' => '::ajaxRegistrationsCallback',
          'wrapper' => 'registrations-table-wrapper',
        ],
        '#empty_option' => $this->t('- Select -'),
      ];
    }
    else {
      $response = [
        '#markup' => '',
      ];
    }
    
    return $response;
  }
  
  /**
   * AJAX callback for registrations table.
   */
  public function ajaxRegistrationsCallback(array &$form, FormStateInterface $form_state) {
    $selected_date = \Drupal::request()->request->get('event_date');
    $selected_event = \Drupal::request()->request->get('event_name');
    
    if ($selected_event) {
      $registrations = $this->eventRegistrationService->getRegistrations($selected_date, $selected_event);
      $count = $this->eventRegistrationService->getRegistrationCount($selected_event);
      
      $header = [
        $this->t('Name'),
        $this->t('Email'),
        $this->t('Event Date'),
        $this->t('College Name'),
        $this->t('Department'),
        $this->t('Submission Date'),
      ];
      
      $rows = [];
      foreach ($registrations as $registration) {
        $event_date = new \DateTime($registration->event_date);
        $created_date = \Drupal::service('date.formatter')->format($registration->created, 'medium');
        
        $rows[] = [
          $registration->full_name,
          $registration->email,
          $event_date->format('F j, Y'),
          $registration->college_name,
          $registration->department,
          $created_date,
        ];
      }
      
      $response = [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#empty' => $this->t('No registrations found.'),
        '#attributes' => ['class' => ['event-registrations-table']],
        '#attached' => [
          'drupalSettings' => [
            'eventRegistration' => [
              'count' => $count,
              'eventId' => $selected_event,
            ],
          ],
        ],
      ];
    }
    else {
      $response = [
        '#markup' => '<p>' . $this->t('Please select an event to view registrations.') . '</p>',
      ];
    }
    
    return $response;
  }
  
  /**
   * Export registrations as CSV.
   */
  public function exportCsv() {
    $event_id = \Drupal::request()->query->get('event_id');
    
    if (!$event_id) {
      return new JsonResponse(['error' => 'Event ID required'], 400);
    }
    
    $registrations = $this->eventRegistrationService->getRegistrations(NULL, $event_id);
    
    $headers = [
      'Content-Type' => 'text/csv',
      'Content-Disposition' => 'attachment; filename="registrations_' . date('Y-m-d') . '.csv"',
    ];
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Name', 'Email', 'Event Date', 'Event Name', 'College', 'Department', 'Registration Date']);
    
    foreach ($registrations as $registration) {
      $event_date = new \DateTime($registration->event_date);
      $created_date = \Drupal::service('date.formatter')->format($registration->created, 'medium');
      
      fputcsv($output, [
        $registration->full_name,
        $registration->email,
        $event_date->format('Y-m-d'),
        $registration->event_name,
        $registration->college_name,
        $registration->department,
        $created_date,
      ]);
    }
    
    fclose($output);
    
    return new Response();
  }
}