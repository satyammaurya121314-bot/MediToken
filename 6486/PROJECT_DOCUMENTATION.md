# MEDITOKEN: Doctor Appointment Booking & Digital Token Generation System
## Comprehensive Technical Project Documentation & System Manual
**Academic Classification:** TY BSc IT (Information Technology) Final-Year Capstone Project  
**System Type:** Full-Stack Relational Web Application  
**Runtime Environment:** Apache HTTP Server & MySQL via XAMPP  
**Local Target URL:** `http://localhost/MediToken/`  

---

## 1. Executive Summary

**MediToken** is a real-world, database-driven digital healthcare appointment booking and intelligent queue management system. Developed to overcome the severe limitations of manual register logging and paper-based token slips in outpatient departments (OPDs), polyclinics, and hospital consultancies, MediToken introduces an end-to-end digital lifecycle for healthcare queues.

Patients can register, explore verified medical practitioners, book available consultation time slots, receive dynamically generated non-conflicting tokens, and monitor their live queue progress remotely via real-time AJAX polling. Consulting physicians operate a specialized queue control desk enabling single-click token calling, patient examination status updates, and consultation completion tracking. Simultaneously, clinic administrators retain comprehensive oversight through live multi-doctor queue monitoring, schedule management, doctor CRUD workflows, and analytical performance reports.

The entire platform strictly adheres to production software standards: **zero hardcoded dummy records, zero simulated frontend arrays, and 100% database-driven transactions** where every single action is processed and permanently stored in MySQL.

---

## 2. Technology Stack & Technical Rationale

| Layer | Technologies Used | Detailed Rationale & Purpose |
| :--- | :--- | :--- |
| **Frontend Structure** | **HTML5 (Semantic)** | Provides accessible, standards-compliant structure using semantic markup (`<main>`, `<section>`, `<aside>`, `<nav>`, `<header>`, `<footer>`). Form elements are strictly typed with client-side pattern constraints for immediate feedback. |
| **Frontend Styling** | **Vanilla CSS3 (Custom Design System)** | Tailored healthcare design system adopting an emerald green (`#059669`), crisp white (`#ffffff`), and slate neutral (`#f8fafc`) palette. Built without bloated third-party CSS frameworks to maximize performance, custom layout control, responsive CSS Grid/Flexbox, and high-contrast accessibility. |
| **Frontend Scripting** | **Vanilla JavaScript (ES6+)** | Handles asynchronous DOM manipulation, time slot selection, modal dialogs, and continuous background client polling using the native `fetch()` API every 4 seconds without screen flickering or page reload overhead. |
| **Backend Engine** | **PHP 8.2+ (OOP & Procedural)** | Robust server-side request processing, session authentication, algorithmic token generation, business logic verification, and parameterized database transactions. |
| **Database Access Layer**| **PHP Data Objects (PDO)** | Employs PDO with prepared statements (`PDO::ATTR_EMULATE_PREPARES => false`) and strict exception handling (`PDO::ERRMODE_EXCEPTION`) to completely eliminate SQL Injection vulnerabilities and support future RDBMS portability. |
| **Database Management**| **MySQL / MariaDB 10.4+** | Relational data repository (`meditoken_db`) enforcing relational foreign key constraints with cascading logic, transactional concurrency locks, unique composite indexes, and strict ACID compliance. |
| **Local Web Server** | **Apache 2.4 (XAMPP Suite)** | Local HTTP server handling routing, session cookies, directory resolution, and PHP script execution on port 80. |
| **Security Cryptography**| **Bcrypt (`PASSWORD_BCRYPT`)** | One-way salted cryptographic hashing for patient, doctor, and administrator passwords via `password_hash()` and `password_verify()`. |

---

## 3. System Architecture & Relational Database Design

### 3.1 Architecture Model
MediToken utilizes a decoupled multi-tier web application architecture:
1. **Presentation Layer (Client Browser):** HTML5/CSS3 responsive UI with AJAX polling controllers.
2. **Controller & API Layer (PHP REST-Style Endpoints):** Input validation, role authorization, and JSON response delivery.
3. **Business Logic & Service Layer (PHP Functions):** Slot reservation algorithms, queue mathematics, and token numbering logic.
4. **Data Persistence Layer (MySQL RDBMS):** Relational tables with foreign key referential integrity.

---

### 3.2 Database Schema Specifications (`meditoken_db`)

The database consists of **5 relational tables** designed to Third Normal Form (3NF):

#### 1. `patients` Table
Stores registered patient demographic and authentication records.
* `patient_id` (INT, Primary Key, Auto Increment): Unique identifier for the patient.
* `name` (VARCHAR(100), NOT NULL): Full legal name of the patient.
* `mobile_no` (VARCHAR(20), NOT NULL): Validated 10–15 digit contact number.
* `email` (VARCHAR(100), NOT NULL, UNIQUE): Unique login email address.
* `password` (VARCHAR(255), NOT NULL): 60-character Bcrypt encrypted password hash.
* `created_at` (DATETIME, DEFAULT CURRENT_TIMESTAMP): Account registration timestamp.

#### 2. `doctors` Table
Stores credentialed medical specialists and their daily consultation schedules.
* `doctor_id` (INT, Primary Key, Auto Increment): Unique identifier for the doctor.
* `doctor_name` (VARCHAR(100), NOT NULL): Full name of the medical practitioner.
* `email` (VARCHAR(100), NOT NULL, UNIQUE): Unique login email address for the doctor portal.
* `password` (VARCHAR(255), NOT NULL): 60-character Bcrypt encrypted password hash.
* `specialization` (VARCHAR(100), NOT NULL): Medical department (e.g., Cardiology, Pediatrics).
* `available_time` (VARCHAR(100), NOT NULL, Default: `'09:00 AM - 01:00 PM'`): Operational consultation window.
* `availability_status` (ENUM('Available', 'Unavailable'), NOT NULL, Default: `'Available'`): Real-time availability flag.
* `created_at` (DATETIME, DEFAULT CURRENT_TIMESTAMP): Profile creation timestamp.

#### 3. `appointments` Table
The central relational junction entity connecting patients and doctors for specific consultation dates and tokens.
* `appointment_id` (INT, Primary Key, Auto Increment): Unique booking reference identifier.
* `patient_id` (INT, Foreign Key referencing `patients(patient_id)` ON DELETE CASCADE).
* `doctor_id` (INT, Foreign Key referencing `doctors(doctor_id)` ON DELETE CASCADE).
* `appointment_date` (DATE, NOT NULL): The scheduled date of the consultation.
* `appointment_time` (VARCHAR(20), NOT NULL): The specific time slot reserved (e.g., `'09:20 AM'`).
* `token_number` (VARCHAR(20), NOT NULL): The unique formatted digital token (e.g., `'A-001'`).
* `status` (ENUM('Waiting', 'Serving', 'Completed', 'Cancelled', 'Skipped', 'No-Show'), NOT NULL, Default: `'Waiting'`).
* `created_at` (DATETIME, DEFAULT CURRENT_TIMESTAMP): Transaction creation timestamp.
* **Constraints & Indexes:**
  * `uq_doc_date_token`: UNIQUE (`doctor_id`, `appointment_date`, `token_number`) ensuring absolute token uniqueness per doctor per date.
  * `idx_doc_date_status`: Composite index on (`doctor_id`, `appointment_date`, `status`) optimizing real-time queue queries.
  * `idx_patient_date`: Index on (`patient_id`, `appointment_date`) optimizing patient appointment lookups.

#### 4. `admins` Table
Stores administrative credentials for clinic governance and audit access.
* `admin_id` (INT, Primary Key, Auto Increment): Primary admin identifier.
* `username` (VARCHAR(50), NOT NULL, UNIQUE): Master admin username/email.
* `password` (VARCHAR(255), NOT NULL): Bcrypt hashed administrative secret.
* `created_at` (DATETIME, DEFAULT CURRENT_TIMESTAMP): Account creation timestamp.

#### 5. `notifications` Table
Stores automated queue status events, approaching token warnings, and cancellation notices.
* `notification_id` (INT, Primary Key, Auto Increment): Notification reference identifier.
* `patient_id` (INT, Foreign Key referencing `patients(patient_id)` ON DELETE CASCADE).
* `doctor_id` (INT, NULLABLE): Associated doctor.
* `appointment_id` (INT, NULLABLE): Associated appointment booking.
* `message` (VARCHAR(255), NOT NULL): Notification text delivered to client.
* `type` (VARCHAR(50), NOT NULL, Default: `'info'`): Event category (`appointment_confirmed`, `token_called`, `completed`, `cancelled`).
* `is_read` (TINYINT(1), NOT NULL, Default: `0`): Read acknowledgment flag.
* `created_at` (DATETIME, DEFAULT CURRENT_TIMESTAMP): Event generation timestamp.

---

## 4. Detailed Functional Modules & Capabilities

```
+---------------------------------------------------------------------------------+
|                                 MEDITOKEN SYSTEM                                |
+---------------------------------------------------------------------------------+
          |                                  |                               |
          v                                  v                               v
   PATIENT PORTAL                      DOCTOR PORTAL                   ADMIN PORTAL
- Online Registration              - Live Queue Desk               - Master Clinic Overview
- Doctor Directory Search          - "Call Next Token" Action      - Doctor CRUD & Schedules
- Dynamic Slot Picker              - Mark Completed / Skip         - Patient Registry
- Sequential Token Assignment      - Patient Record Inspection     - Comprehensive Booking Log
- Live Queue Board (4s Sync)       - Historical Consultation Log   - Live Multi-Doctor Monitor
- Appointment Cancellation         - Timing & Availability Toggle  - Statistical Audit Reports
```

### 4.1 Public Healthcare Portal
* **Homepage (`index.php`):** Professional healthcare presentation highlighting clinic capabilities, a live active queue showcase, a 4-step walkthrough of digital queuing, clinical service breakdowns, and clear call-to-action buttons.
* **Doctor Directory (`doctors.php`):** Dynamically queries the `doctors` table in MySQL. Allows patients to search by doctor name, filter by medical specialization, check live consulting hours, and view real-time availability badges (`Available` / `Unavailable`). Displays clean empty states if no records exist.
* **Clinical Services (`services.php`):** Outlines outpatient consultations, digital token generation, patient record auditing, and administrative governance.
* **About Us (`about.php`):** Details the core mission of replacing crowded waiting lines with digitized transparent workflows.
* **Unified Authentication Portal (`login.php`):** Single, secure authentication hub with instant tab switching between Patient, Doctor, and Administrator roles. Evaluates input against respective MySQL tables using `password_verify()`.

---

### 4.2 Patient Module (`/patient/`)
* **Dashboard (`patient/dashboard.php`):** Real-time command center displaying the patient's active token, currently serving token in the clinic, count of patients ahead, live appointment status badge, upcoming appointment card, and recent in-app queue alerts. Explicitly identifies the patient via a persistent `Patient ID: #X` badge.
* **Appointment Booking (`patient/book_appointment.php`):**
  1. Patient selects a verified doctor and consultation date.
  2. The system reads the doctor's consultation hours (e.g., `09:00 AM - 01:00 PM`) and divides the duration into discrete 20-minute time slots.
  3. The system queries MySQL for all active appointments for that doctor and date. Booked slots are rendered disabled with a strike-through indicator.
  4. Upon selecting an open slot, the submission runs inside a MySQL database transaction with `FOR UPDATE` row-level locking to guarantee no concurrent double-booking.
  5. The next sequential token (e.g., `A-001`) is generated, saved as `Waiting`, and an instant booking confirmation card is rendered.
* **Live Queue Board (`patient/queue.php`):** High-impact hospital-style digital board displaying:
  * **CURRENTLY SERVING:** Token active inside the consultation room.
  * **YOUR TOKEN:** Prominently highlighted token number assigned to the patient.
  * **NEXT IN LINE:** Upcoming token approaching next.
  * **PATIENTS AHEAD:** Calculated count of waiting patients preceding this token.
  * **ESTIMATED WAIT:** Dynamic algorithmic waiting estimate.
  * Auto-refreshes every 4 seconds via background AJAX requests to `api/queue_status.php`.
* **My Appointments (`patient/appointments.php`):** Tabular view of active appointments with one-click cancellation capability. Cancellation instantly updates database status to `Cancelled` and updates the queue for all subsequent patients.
* **Appointment History (`patient/history.php`):** Permanent audit ledger of completed, skipped, and past appointments with date, doctor, time, token, and status badges.
* **Patient Profile (`patient/profile.php`):** Displays unique `Patient ID: #X`, registered email, registration timestamp, editable contact number, and a secure password update utility.

---

### 4.3 Doctor Consultation Module (`/doctor/`)
* **Doctor Dashboard (`doctor/dashboard.php`):** Displays the doctor's verified identity, specialization, persistent `Doctor ID: #X` badge, and 4 real SQL metrics: *Today's Appointments*, *Waiting Patients*, *Current Serving Token*, and *Completed Consultations*.
* **Live Patient Queue Desk (`doctor/queue.php`):** The consultation control room.
  * Displays prominent banner showing current serving patient, next in line, and waiting count.
  * **CALL NEXT TOKEN Button:** Automatically marks the currently serving patient as `Completed`, fetches the earliest `Waiting` patient in MySQL, updates their status to `Serving`, and dispatches an automated in-app notification.
  * **Individual Row Actions:** Direct calling of specific patients, *Mark Completed*, *Skip*, and *Recall*.
  * **Patient Info Modal:** Allows doctors to inspect patient details (Name, Contact Number, Email, Token, Scheduled Slot Time, and Registration Date).
* **Consultation Registry (`doctor/appointments.php`):** Searchable, filterable ledger of all patient appointments assigned to this doctor with multi-criteria filters (Search text, Status, Date).
* **My Patients Directory (`doctor/patients.php`):** Filtered patient list restricted strictly to patients who have booked consultations with this doctor, showing total visits and last visit date.
* **Availability & Profile (`doctor/profile.php`):** Enables the doctor to configure consultation timing (e.g., `09:00 AM - 01:00 PM`), toggle live availability (`Available` / `Unavailable`), update specialization, and change their login password.

---

### 4.4 Administrator Module (`/admin/`)
* **Overview Dashboard (`admin/dashboard.php`):** Comprehensive clinic management dashboard with 6 live SQL aggregate metrics: *Total Doctors*, *Total Patients*, *Today's Appointments*, *Waiting Patients (Today)*, *Completed Consultations*, and *Cancelled Appointments*.
* **Doctor Management (`admin/doctors.php`):** Full CRUD capability (Create Doctor with initial password and schedule, Edit Doctor details, Delete Doctor, and One-Click Availability Toggle).
* **Patient Records (`admin/patients.php`):** Master patient directory showing registered accounts, contact details, total booking counts, and account deletion options.
* **Appointments Registry (`admin/appointments.php`):** Clinic-wide appointment master log with multi-parameter filtering (by doctor dropdown, status dropdown, date picker, search string) and administrative cancellation authority.
* **Doctor Schedules (`admin/schedules.php`):** Centralized scheduling hub allowing clinic administrators to update consultation timing strings and toggle doctor availability. Changes immediately reflect on the patient booking slot engine.
* **Live Queue Monitor (`admin/queue.php`):** Multi-doctor live queue command center displaying doctor-by-doctor status: Doctor Name, Specialization, Current Serving Token, Next in Line Token, Waiting Count, Completed Count, and Total Scheduled Bookings.
* **Clinic Reports & Analytics (`admin/reports.php`):** Statistical intelligence report providing total volume breakdown (Waiting, Serving, Completed, Cancelled, Skipped), completion rate percentage, cancellation rate percentage, average consultation time, and a doctor-wise comparative breakdown table with print-ready CSS support.

---

### 4.5 REST-Style API Layer (`/api/`)
* **`api/queue_status.php`:** Accepts `doctor_id` and `appointment_id`. Queries MySQL in real time and returns JSON:
  ```json
  {
    "success": true,
    "doctor_id": 1,
    "appointment_id": 1,
    "current_token": "A-001",
    "next_token": "A-002",
    "your_token": "A-001",
    "patients_ahead": 0,
    "status": "Serving",
    "status_html": "<span class=\"status-badge badge-serving\">...</span>",
    "estimated_wait": "Now being served",
    "timestamp": 1727584800
  }
  ```
* **`api/call_next.php`:** Advances the doctor's queue via an atomic update and returns the newly active serving token.
* **`api/book_appointment.php`:** Programmatic appointment reservation and token generation endpoint.
* **`api/cancel_appointment.php`:** Validates ownership and transitions appointment status to `Cancelled`.
* **`api/notifications.php`:** Fetches unread patient alerts and marks them as read (`is_read = 1`).

---

## 5. Core Algorithms & Mathematical Logic

### 5.1 Dynamic Token Generation Algorithm
Token numbers in MediToken are formatted, doctor-prefixed, sequential alphanumeric identifiers ensuring distinct queue sequences for every practitioner:
```
Token Format = [Doctor Letter Prefix]-[3-Digit Zero-Padded Sequence]
Example: Doctor ID 1 -> 'A-001', 'A-002' | Doctor ID 2 -> 'B-001', 'B-002'
```
* **Prefix Generation:**
  $$\text{Prefix} = \text{chr}\left(65 + ((\text{doctor\_id} - 1) \pmod{26})\right)$$
* **Sequence Calculation:**
  The system queries `appointments` for `WHERE doctor_id = ? AND appointment_date = ?`. It parses existing tokens, identifies the highest existing numeric suffix ($N_{\max}$), and sets:
  $$N_{\text{next}} = N_{\max} + 1$$
* **Uniqueness Verification:**
  A while-loop re-checks the database to guarantee uniqueness before insertion, backed by the composite database constraint `uq_doc_date_token`.

---

### 5.2 Time Slot Engine & Concurrency Control
* **Slot Parsing:**
  Given a doctor's timing string (e.g., `"09:00 AM - 01:00 PM"`), the algorithm converts start and end boundaries into Unix epoch timestamps and generates intervals at 20-minute increments:
  $$T_i = T_{\text{start}} + (i \times 20 \text{ minutes}) \quad \forall \ T_i < T_{\text{end}}$$
* **Race Condition Prevention:**
  When two patients click the same slot simultaneously, the reservation executes within a database transaction:
  ```sql
  SELECT appointment_id FROM appointments 
  WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND status != 'Cancelled' 
  FOR UPDATE;
  ```
  The first transaction locks the record; the second transaction is blocked and subsequently aborted with the error: *"This appointment slot is already booked."*

---

### 5.3 Dynamic Estimated Wait Time Calculation
$$\text{Estimated Wait (minutes)} = \text{Patients Ahead} \times 15 \text{ minutes}$$
* If $\text{status} = \text{'Serving'}$: Display *"Now being served"*.
* If $\text{status} = \text{'Waiting'}$ and $\text{Patients Ahead} = 0$: Display *"Next in line (< 5 mins)"*.
* If $\text{Estimated Wait} \ge 60$ minutes: Format as *"~Xh Ym wait"*.

---

## 6. Security & Defensive Design Implementations

1. **Defense Against SQL Injection:**
   100% of database interactions utilize PDO Prepared Statements with bound parameters. Raw user inputs are never concatenated directly into SQL queries.
2. **Cryptographic Password Security:**
   Plaintext passwords are never saved. Passwords are processed through PHP's `password_hash()` implementing one-way Bcrypt with an automatic per-user salt. Login evaluation uses constant-time comparison via `password_verify()`.
3. **Role-Based Access Control (RBAC):**
   Protected views enforce role validation via `require_role('patient')`, `require_role('doctor')`, or `require_role('admin')`. Any unauthorized attempt triggers an automatic HTTP 302 redirect to the login portal.
4. **Session Sanitization & Hijacking Mitigation:**
   Configured with `ini_set('session.cookie_httponly', 1)` preventing client-side JavaScript theft of session cookies. Active sessions regenerate session IDs upon login via `session_regenerate_id(true)`.
5. **Cross-Site Scripting (XSS) Prevention:**
   All dynamic outputs rendered in HTML views pass through `htmlspecialchars($data, ENT_QUOTES, 'UTF-8')`.
6. **Secure First-Time Administrator Bootstrap (`setup.php`):**
   `setup.php` dynamically evaluates `SELECT COUNT(*) FROM admins`. If an administrator already exists, the script permanently locks itself and redirects to `login.php`.

---

## 7. Complete Project File Manifest

```
d:/6486/
├── index.php                      # Public homepage with hero, features & live board demo
├── doctors.php                    # Doctor directory with specialization filter & MySQL query
├── services.php                   # Clinical healthcare services breakdown
├── about.php                      # Project architecture & problem statement
├── login.php                      # Unified role-based authentication portal (Patient, Doctor, Admin)
├── register.php                   # Patient account registration inserting into MySQL
├── logout.php                     # Session termination script
├── setup.php                      # Secure master admin initialization (one-time lockable)
├── README.md                      # Quickstart setup instructions & workflow summary
├── PROJECT_DOCUMENTATION.md       # Complete in-depth technical documentation (This file)
│
├── config/
│   └── database.php               # PDO database connection configuration
│
├── includes/
│   ├── auth.php                   # Session management, RBAC enforcement & ID sync
│   ├── functions.php              # Helper routines, slot calculator, token generator, wait estimator
│   ├── header.php                 # Global responsive top navigation bar with SVG icons
│   ├── footer.php                 # Global footer with navigation links & script imports
│   ├── patient_nav.php            # Patient portal sidebar navigation with Patient ID
│   ├── doctor_nav.php             # Doctor desk sidebar navigation with Doctor ID
│   └── admin_nav.php              # Administrator control panel sidebar navigation
│
├── patient/
│   ├── dashboard.php              # Patient command center with real token statistics
│   ├── book_appointment.php      # Appointment slot reservation & sequential token assignment
│   ├── appointments.php           # Active appointments registry & cancellation handler
│   ├── queue.php                  # Live digital token queue board with 4s AJAX polling
│   ├── history.php                # Complete past consultation ledger
│   └── profile.php                # Patient personal information & password management
│
├── doctor/
│   ├── dashboard.php              # Doctor desk metrics calculated live from MySQL
│   ├── queue.php                  # Live consultation control desk (Call Next, Complete, Skip)
│   ├── appointments.php           # Doctor's appointment ledger with multi-parameter filters
│   ├── patients.php               # Consultation patient history directory
│   └── profile.php                # Schedule hours & live availability toggle
│
├── admin/
│   ├── dashboard.php              # Master clinic metrics & SQL aggregation statistics
│   ├── doctors.php                # Full Doctor CRUD & one-click availability toggle
│   ├── patients.php               # Registered patient directory & record management
│   ├── appointments.php           # Master appointment log with search & cancellation authority
│   ├── schedules.php              # Doctor consultation hours & availability controller
│   ├── queue.php                  # Multi-doctor simultaneous live queue monitor
│   └── reports.php                # Clinic performance reports, efficiency rates & print view
│
├── api/
│   ├── queue_status.php           # JSON endpoint for background real-time queue calculation
│   ├── call_next.php              # Endpoint for doctors to advance queue in MySQL
│   ├── book_appointment.php       # Programmatic booking & slot validation endpoint
│   ├── cancel_appointment.php     # Programmatic cancellation endpoint
│   └── notifications.php          # Real-time in-app notification delivery endpoint
│
├── assets/
│   ├── css/
│   │   └── style.css              # Custom Healthcare Design System (Green/White/Slate)
│   └── js/
│       └── script.js              # Client-side 4s queue polling & slot interaction controller
│
└── database/
    └── meditoken_db.sql           # Clean relational database schema without mock data
```

---

## 8. Installation, Execution & Demonstration Guide

### Step 1: Launch Local Services
1. Open the **XAMPP Control Panel**.
2. Start the **Apache** web server module.
3. Start the **MySQL** database server module.

### Step 2: Database Initialization
1. Navigate to phpMyAdmin: `http://localhost/phpmyadmin/`
2. Create database `meditoken_db`.
3. Import the clean schema file: `d:/6486/database/meditoken_db.sql`.

### Step 3: Access Application
Open any modern web browser and navigate to:
```
http://localhost/MediToken/
```

### Step 4: Step-by-Step Live Examination Demo Flow
1. **Master Admin Setup:** Open `http://localhost/MediToken/setup.php` to initialize the primary administrator account.
2. **Doctor Onboarding:** Sign in to `login.php?role=admin`, open `admin/doctors.php`, and register a doctor (e.g., Dr. Rajesh, Cardiologist, `09:00 AM - 01:00 PM`).
3. **Patient Registration:** Open `register.php`, register a patient (e.g., Satyam, Mobile: `9876543210`), and automatically enter the Patient Dashboard.
4. **Slot Booking & Token Generation:** Open `patient/book_appointment.php`, select Dr. Rajesh, choose today's date, select `09:00 AM`, and confirm. The system transactionally commits the booking and generates Token `A-001`.
5. **Live Queue Tracking:** Open `patient/queue.php`. Observe the live board showing *Token A-001*, *Currently Serving: None*, *Next in Line: A-001*.
6. **Doctor Desk Calling:** In a second window, log into `login.php?role=doctor`. Open `doctor/queue.php` and click **CALL NEXT TOKEN**.
7. **Instant Real-Time Sync:** Without refreshing the patient window, observe the patient queue board dynamically transition to **Currently Serving: A-001** and display the alert: *"Your token is now being served!"*.
8. **Consultation Completion:** In the doctor window, click **Mark Completed**. The appointment transitions to `Completed` and is archived in `patient/history.php`.

---

## 9. Academic Defense & Viva Voce Quick Reference

* **Q: What is the core USP of MediToken?**  
  *A: Synchronized digital queuing that calculates sequential tokens per doctor per date, prevents slot collisions with transactional database locks, and updates patient screens in real time via AJAX polling.*
* **Q: Why was PDO selected over MySQLi?**  
  *A: PDO provides uniform database abstraction, robust exception handling, and native prepared statements to completely eliminate SQL Injection.*
* **Q: How is real-time synchronization achieved without WebSockets?**  
  *A: Through high-frequency asynchronous client polling using the native JavaScript `fetch()` API targeting a dedicated REST-style JSON endpoint (`api/queue_status.php`) every 4 seconds.*
* **Q: What is the future scope?**  
  *A: SMS/WhatsApp notification webhooks (Twilio/Fast2SMS), online consultation fee payment processing (Razorpay/Stripe), QR-code token slips, and patient digital prescriptions.*
