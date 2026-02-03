(function($, Drupal, drupalSettings) {
  'use strict';
  
  Drupal.behaviors.eventRegistrationAdmin = {
    attach: function(context, settings) {
      // Update registration count
      if (drupalSettings.eventRegistration) {
        var count = drupalSettings.eventRegistration.count;
        var eventId = drupalSettings.eventRegistration.eventId;
        
        if (count > 0) {
          $('#registration-count').html('<h3>' + Drupal.t('Total Participants: @count', {'@count': count}) + '</h3>');
          $('#export-csv').show();
          $('#export-csv').attr('href', '/admin/content/event-registrations/export?event_id=' + eventId);
        }
      }
      
      // Event date change handler
      $('#admin-event-date', context).once('event-date-change').change(function() {
        var eventDate = $(this).val();
        if (eventDate) {
          $.ajax({
            url: Drupal.url('admin/content/event-registrations/ajax/event-names'),
            type: 'POST',
            data: { event_date: eventDate },
            success: function(response) {
              $('#admin-event-names-wrapper').html(response);
            }
          });
        }
      });
    }
  };
})(jQuery, Drupal, drupalSettings);