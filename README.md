# Event Registration Module for Drupal 10

## Overview
This module allows users to register for events via a custom form, stores registrations in a database, and sends email notifications.

## Installation
1. Copy the `event_registration` folder to `/modules/custom/` in your Drupal installation
2. Enable the module at `/admin/modules`
3. The module will create necessary database tables automatically

## URLs
- **Event Registration Form**: `/event/registration`
- **Event Configuration**: `/admin/config/event-registration/event`
- **Admin Settings**: `/admin/config/event-registration/settings`
- **Registration List**: `/admin/event-registration/list`

## Database Tables
The module creates two tables:

### 1. event_config
Stores event configuration:
- ID (primary key)
- Event Name
- Event Category (Online Workshop, Hackathon, Conference, One-day Workshop)
- Registration Start Date
- Registration End Date
- Event Date
- Created Timestamp

### 2. event_registration
Stores user registrations:
- ID (primary key)
- Full Name
- Email Address
- College Name
- Department
- Event Category
- Event Date
- Event Name
- Foreign Key to event_config
- Created Timestamp

## Validation Logic
1. **Duplicate Prevention**: Checks email + event date combination
2. **Email Format**: Validates email format using Drupal's email validator
3. **Special Characters**: Blocks special characters in text fields using regex patterns
4. **Date Validation**: Ensures registration dates are logical

## Email Logic
1. Uses Drupal Mail API
2. Sends confirmation email to user upon registration
3. Optionally sends notification to admin
4. Email includes all registration details

## Permissions
Three custom permissions are created:
1. `access event registration form` - Access the registration form
2. `view event registration list` - View registration list
3. `administer event registration` - Full admin access

## Features Implemented
✅ Custom Drupal 10 module  
✅ Event configuration page with dates  
✅ Registration form with validation  
✅ Database storage with two tables  
✅ Email notifications  
✅ Admin configuration page  
✅ Permission-based access control  
✅ Basic admin listing page  

## Usage Instructions
1. **Admin Setup**:
   - Go to `/admin/config/event-registration/settings` to set admin email
   - Go to `/admin/config/event-registration/event` to create events

2. **User Registration**:
   - Users visit `/event/registration`
   - Fill out the form with valid details
   - Receive confirmation email

3. **View Registrations**:
   - Admins can view registrations at `/admin/event-registration/list`

## Technical Details
- Built for Drupal 10.x
- No contributed modules required
- Follows Drupal coding standards
- Uses Drupal Form API
- Implements Drupal Mail API
- Config API for settings