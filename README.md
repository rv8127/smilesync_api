# SmileSync API

SmileSync is a multi-clinic dental management system. This repository contains its **PHP API**, the backend that the clinic web admin, the System Admin panel, and the patient mobile app talk to. The API is the only layer that authenticates users, validates input, and reads or writes the PostgreSQL database.

> The web admin and the Android patient app are separate components and are not included in this repository.

## About the Project

SmileSync is a capstone project for BSIT 2-B1 (Group 8), integrating **ITE300** (Object-Oriented Programming), **ITE298** (Database Systems), and **ITE083** (IT Project Management).

It helps dental clinics manage patients, dentists, services, schedules, and appointments in one place. Each clinic gets one shared administrative account, and clinics on the higher subscription tier also get a patient mobile app for booking appointments and viewing treatment history. A separate System Admin panel registers and manages the participating clinics.

### Group Members

| Member | Role |
| --- | --- |
| Rovine Gabriel E. Alcasid | Project Lead / Backend Developer |
| John Vincent B. Lao | UI/UX Designer / Frontend Developer |
| Kevin Miralles | Documentation Lead |

## Technology Stack

| Layer | Technology |
| --- | --- |
| Backend | PHP |
| Web server | Apache (Docker container on Render) |
| Database | PostgreSQL, managed with pgAdmin 4 |
| Data format | JSON over HTTP |
| Email (password reset) | EmailJS |

## Repository Structure

```
smilesync_api/
├── admin/           System Admin endpoints (login, clinics, activity, system announcements)
├── announcements/   Clinic announcement endpoints
├── appointments/    Booking, status updates, cancel, reschedule
├── auth/            Clinic staff login, logout, password reset
├── clinic/          Clinic information and closures
├── dashboard/       Dashboard summary
├── dentists/        Dentists and their schedules
├── helpers/         Shared code: database connection, validation, auth checks, responses
├── patients/        Patient records, patient app login/registration, treatment history
├── services/        Dental services
├── treatments/      Treatment records
└── database/        smilesync.sql (database script)
```

## Setup and Run

### Requirements

- [XAMPP](https://www.apachefriends.org/) (Apache and PHP)
- [PostgreSQL](https://www.postgresql.org/) and pgAdmin 4
- Git

### 1. Get the code

Clone the repository into XAMPP's `htdocs` folder:

```bash
cd C:\xampp\htdocs
git clone https://github.com/rv8127/smilesync_api.git
```

### 2. Enable the PostgreSQL extension in PHP

Open `C:\xampp\php\php.ini`, remove the leading `;` from these lines, then restart Apache:

```
extension=pgsql
extension=pdo_pgsql
```

### 3. Create the database

In pgAdmin (Query Tool) or `psql`, create the role and database:

```sql
CREATE ROLE smilesync_team LOGIN PASSWORD 'choose-a-password';
CREATE DATABASE smilesync_database OWNER smilesync_team;
```

Then import the table definitions from the repository:

```bash
psql -U smilesync_team -d smilesync_database -f database/smilesync.sql
```

### 4. Configure the API

Credentials are not stored in the repository. Copy the sample file and fill in your own values:

```bash
cd smilesync_api/helpers
copy config.example.php config.php
```

Edit `helpers/config.php`:

- `db`: host, port, database name, user, and the password you chose in step 3.
- `emailjs`: your EmailJS service ID, public key, and private key. This is only needed for the password reset emails; the rest of the API works without it.

`config.php` is listed in `.gitignore` and must never be committed.

### 5. Start the server

Start **Apache** from the XAMPP Control Panel and make sure PostgreSQL is running. The API is now available at:

```
http://localhost/smilesync_api/
```

## Using the API

- Every endpoint is a PHP file, for example `http://localhost/smilesync_api/patients/get_patients.php`.
- Requests that send data use a JSON body. Responses are JSON and include a success flag and a message.
- Validation problems and errors return structured JSON with the matching HTTP status code (such as 401, 404, 422, or 500).
- Clinic staff and System Admin endpoints use a session cookie after login.
- Import the Postman collection in `postman/` to try each endpoint.

### Endpoint Groups

| Folder | What it covers |
| --- | --- |
| `admin/` | System Admin login, register and manage clinics, activity log, system announcements |
| `auth/` | Clinic staff login, logout, and password reset |
| `patients/` | Patient records, patient app registration and login, appointment and treatment history |
| `dentists/` | Dentist records and schedules |
| `services/` | Dental services offered by a clinic |
| `appointments/` | Book, view, update status, cancel, and reschedule appointments |
| `treatments/` | Record a treatment for a patient |
| `clinic/` | Clinic information and closures |
| `announcements/` | Clinic announcements |
| `dashboard/` | Summary figures for the clinic dashboard |

### Core Use Cases

| Use case | Primary endpoint |
| --- | --- |
| UC-01 Register Clinic | `admin/add_clinic.php` |
| UC-02 Book Appointment | `appointments/add_appointment.php` (clinic web admin), `appointments/book_appointment.php` (patient app) |
| UC-03 Complete Appointment | `appointments/update_appointment_status.php` |

## Notes

- The API and database run locally; there is no cloud deployment.
