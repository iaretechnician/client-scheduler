# Client Scheduler - Barber Shop Queue + Appointments (PHP)

A beginner-friendly PHP 8.3 web app for barber-shop walk-ins, appointments, queue management, and customer history.

## Features

### Customer features
- Online walk-in queue check-in
- Appointment booking with slot conflict prevention
- Estimated wait time display
- Optional SMS opt-in (currently logs to `/data/notifications.log`)
- Confirmation page after booking/check-in
- Shop info (address, directions, hours, phone)

### Admin features
- Login-protected admin pages
- Dashboard (queue, appointments, completed, no-shows/cancellations)
- Queue status updates: `waiting`, `notified`, `arrived`, `in_chair`, `completed`, `cancelled`, `no_show`
- Manual walk-in add/edit
- Assign barber + adjust service duration
- Pause online check-ins
- Block time slots for breaks/unavailability
- Customer list + filters + marketing placeholders

### Tablet display
- Public queue display mode (`/tablet.php`)
- Current + next customer
- Approx wait times
- Auto-refresh every 30 seconds
- No contact details displayed

## Architecture

- **Storage abstraction** in `includes/storage/StorageInterface.php`
- **JSON storage implementation** in `includes/storage/JsonStorage.php`
- **MySQL placeholder** in `includes/storage/MysqlStorage.php.placeholder`
- **Central wait logic** in `includes/services/WaitTimeService.php`
- **Notification abstraction** in `includes/services/NotificationService.php`

This allows replacing JSON with MySQL later without rewriting page logic.

## Directory layout

```
/
  index.php
  checkin.php
  appointment.php
  confirmation.php
  tablet.php
  admin/
    login.php
    index.php
    queue.php
    appointments.php
    customers.php
    settings.php
    logout.php
  includes/
    bootstrap.php
    storage/
      StorageInterface.php
      JsonStorage.php
      MysqlStorage.php.placeholder
    services/
      WaitTimeService.php
      NotificationService.php
      SmsLogger.php
      AppointmentService.php
      CustomerService.php
    auth.php
    helpers.php
  config/
    shop.php
  data/
    customers.json
    queue.json
    appointments.json
    services.json
    settings.json
    notifications.log
  assets/
    css/style.css
    js/app.js
  .github/workflows/deploy.yml
  .htaccess
  README.md
```

## Setup

1. Serve with Apache + PHP 8.3.
2. Ensure web server can write to `/data`.
3. Update `/config/shop.php` values.
4. Visit:
   - `/` customer homepage
   - `/admin/login.php` admin login

## Development admin login

- Username: `admin`
- Password: set in `config/shop.php` (`admin.password`)

> **Security note:** For production, use a password hash and environment variables.

## Data and privacy

- `/data` is protected by `.htaccess`.
- Public display (`tablet.php`) does not show phone/email.

## SMS provider abstraction

Current behavior logs outgoing messages to `/data/notifications.log`.
Future provider integration can replace internals in `NotificationService` without changing page controllers.

## Deploy workflow

Workflow file: `.github/workflows/deploy.yml`

On push to `main`, it:
- checks out code
- loads SSH key from secret
- adds host to known_hosts
- deploys via `rsync`
- runs safe permission commands

Required repository secrets:
- `SSH_HOST`
- `SSH_USER`
- `SSH_PRIVATE_KEY`
- `SSH_PORT`
- `DEPLOY_PATH`

> Deployment intentionally avoids `--delete` so server-side data is not removed.
