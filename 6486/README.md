# MediToken: Doctor Appointment Booking & Digital Token Generation System

**Final Year BSc IT Capstone Project**  
*A Real-World, Database-Driven Web Application built with HTML, CSS, JavaScript, PHP, and MySQL for Apache/XAMPP.*

---

## Project Overview

**MediToken** is a comprehensive, production-ready digital appointment booking and token management system designed for clinics and hospitals. It replaces obsolete paper token slips and manual appointment registers with an automated, synchronized digital queue.

Every token is dynamically calculated, strictly assigned to an active doctor schedule, stored in relational MySQL tables, and tracked live through real-time AJAX client polling.

---

## Technology Stack

- **Frontend:** Vanilla HTML5, Modern CSS3 (Healthcare Design System: Green/White/Light Neutral palette), Vanilla JavaScript (Fetch API & DOM polling)
- **Backend:** PHP 8+ (PDO with prepared statements, bcrypt hashing, role-based session authentication)
- **Database:** MySQL / MariaDB (`meditoken_db`)
- **Server:** Apache on XAMPP (Local URL: `http://localhost/MediToken/`)

---

## Project Directory Structure

```
MediToken/
├── index.php                 # Professional healthcare homepage
├── doctors.php               # Doctor directory connected to MySQL
├── services.php              # Clinic services overview
├── about.php                 # Project vision and architecture
├── login.php                 # Unified portal sign-in (Patient, Doctor, Admin)
├── register.php              # Real patient registration into MySQL
├── logout.php                # Session destruction and secure sign-out
├── setup.php                 # Secure initial administrator setup
├── config/
│   └── database.php          # Database PDO connection configuration
├── includes/
│   ├── auth.php              # Role-based session and access control
│   ├── functions.php         # Core helpers, slot generator, token generator
│   ├── header.php            # Shared responsive navigation header
│   ├── footer.php            # Shared footer and scripts
│   ├── patient_nav.php       # Patient portal sidebar navigation
│   ├── doctor_nav.php        # Doctor consultation desk sidebar
│   └── admin_nav.php         # Admin control panel sidebar
├── patient/
│   ├── dashboard.php         # Live patient dashboard with real token cards
│   ├── book_appointment.php # Slot selection, race-condition safe booking
│   ├── appointments.php      # Active appointment list and cancellation
│   ├── queue.php             # Live digital token queue board (auto-refresh)
│   ├── history.php           # Historical consultation records
│   └── profile.php           # Profile information and password update
├── doctor/
│   ├── dashboard.php         # Doctor consultation metrics from MySQL
│   ├── appointments.php      # All patient appointments with search/filters
│   ├── queue.php             # Live consultation desk (Call Next, Complete, Skip)
│   ├── patients.php          # Consulting patient records
│   └── profile.php           # Schedule timing and availability toggle
├── admin/
│   ├── dashboard.php         # Clinic metrics & SQL aggregation statistics
│   ├── doctors.php           # Full Doctor CRUD (Add, Edit, Delete, Toggle)
│   ├── patients.php          # Patient directory and record management
│   ├── appointments.php      # Comprehensive booking log with filters
│   ├── schedules.php         # Live doctor schedule configuration
│   ├── queue.php             # System-wide multi-doctor queue monitor
│   └── reports.php           # Audit reports and efficiency calculations
├── api/
│   ├── queue_status.php      # Real-time queue polling endpoint (JSON)
│   ├── call_next.php         # Doctor queue advance endpoint
│   ├── book_appointment.php  # Booking and slot reservation API
│   ├── cancel_appointment.php# Appointment cancellation API
│   └── notifications.php     # In-app live notification delivery
├── assets/
│   ├── css/
│   │   └── style.css         # Modern healthcare CSS design system
│   └── js/
│       └── script.js         # Queue polling, slot selection & toast logic
├── database/
│   └── meditoken_db.sql      # Clean relational schema (No fake records)
└── README.md                 # Setup and operational instructions
```

---

## Setup Instructions (Step-by-Step)

### 1. Install & Launch XAMPP
1. Download and install [XAMPP](https://www.apachefriends.org/) (with PHP 8.x and MySQL).
2. Open the **XAMPP Control Panel**.
3. Start **Apache** and **MySQL**.

### 2. Place Project in `htdocs`
Copy the `MediToken` project folder into your XAMPP web root directory:
```
C:/xampp/htdocs/MediToken/
```
*(Alternatively, create a directory junction if storing in another drive).*

### 3. Import MySQL Database Schema
1. Open your web browser and navigate to **phpMyAdmin**:
   `http://localhost/phpmyadmin/`
2. Create a new database named:
   ```sql
   meditoken_db
   ```
3. Click on the `meditoken_db` database, go to the **Import** tab.
4. Select the file:
   ```
   C:/xampp/htdocs/MediToken/database/meditoken_db.sql
   ```
5. Click **Import / Go**.  
   *The schema will create all 5 relational tables: `patients`, `doctors`, `appointments`, `admins`, `notifications` without any fake demo data.*

### 4. Database Configuration
If your XAMPP MySQL has a root password, open:
`config/database.php` and update:
```php
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'meditoken_db');
define('DB_USER', 'root');
define('DB_PASS', ''); // Enter your password if set, default XAMPP is empty
```

### 5. Access the Application
Open your browser and navigate to:
[http://localhost/MediToken/](http://localhost/MediToken/)

---

## Real-World User Flows & Operations

### 1. First-Time Administrator Initialization
- Visit `http://localhost/MediToken/login.php?role=admin` or `http://localhost/MediToken/setup.php`.
- Because the database starts clean with 0 administrators, the system displays the **Secure Initial Administrator Setup** screen.
- Enter your admin username and password. This creates the primary administrator in MySQL and automatically locks `setup.php` against unauthorized reuse.

### 2. Administrator Creates Doctor Accounts
1. Sign in to the **Admin Portal** using your admin credentials.
2. Navigate to **Doctors** (`admin/doctors.php`).
3. Click **Add New Doctor**.
4. Fill in:
   - Doctor Name (e.g. `Sarah Jenkins`)
   - Email (e.g. `dr.sarah@clinic.com`)
   - Initial Password
   - Specialization (e.g. `Cardiologist`)
   - Available Timing (e.g. `09:00 AM - 01:00 PM`)
   - Availability Status (`Available`)
5. Save the record. The doctor is immediately saved to MySQL and visible on the public doctors directory.

### 3. Patient Registration & Appointment Booking
1. Visit the home page and click **Register** (`register.php`).
2. Provide Full Name, Mobile Number, Email, and Password.
3. Upon registration, credentials are encrypted via `password_hash()` and the user is logged into their **Patient Dashboard**.
4. Go to **Book Appointment** (`patient/book_appointment.php`):
   - Select the consulting doctor from the database dropdown.
   - Choose the consultation date.
   - The system dynamically parses doctor consultation hours into discrete 20-minute slots and checks MySQL for conflicts.
   - Pick an open time slot and click **Confirm Appointment & Get Token**.
5. The system performs a transactional lock, generates a unique doctor-prefixed token (e.g. `A-001`), creates the appointment in `Waiting` status, and shows the confirmation screen.

### 4. Tracking the Live Queue
1. The patient opens **Live Queue** (`patient/queue.php`).
2. The page renders:
   - **Currently Serving Token** (Live status in consultation room)
   - **Your Token** (Assigned token number)
   - **Next In Line** (Upcoming waiting token)
   - **Patients Ahead** (Real-time count of patients waiting before you)
   - **Estimated Wait** (Calculated based on queue length)
3. JavaScript polls `api/queue_status.php` every 4 seconds, updating the display seamlessly without full-page reloads.

### 5. Doctor Calling Tokens at the Consultation Desk
1. The doctor logs in at `login.php?role=doctor` using their registered email and password.
2. The doctor opens **Live Patient Queue** (`doctor/queue.php`).
3. Clicking **CALL NEXT TOKEN**:
   - Updates the previously serving patient to `Completed` (if applicable).
   - Finds the earliest valid `Waiting` appointment in MySQL.
   - Updates its status to `Serving`.
   - Sends an in-app alert to the patient.
   - Patient screens update immediately to show the new serving token.
4. The doctor can click **Mark Completed** when the consultation ends or **Skip** if a patient is not present.

---

## Security Features Implemented

- **Password Encryption:** Bcrypt hashing (`PASSWORD_BCRYPT`) via `password_hash()` and `password_verify()`.
- **SQL Injection Prevention:** 100% prepared statements via PHP PDO for all queries.
- **Session Protection:** Strict role-based isolation preventing cross-role access (e.g. patients cannot access `/doctor/` or `/admin/`).
- **Slot Conflict Prevention:** Transactional verification preventing double-booking of identical doctor/date/time slots.
- **Clean Architecture:** Zero mock/demo data; all counts and metrics query MySQL directly.
