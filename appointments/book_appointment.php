<?php
// appointments/book_appointment.php -- patient app books their own appointment.
// Auto-confirms: require_appointment_availability() below already checks the
// clinic isn't closed, the dentist/service are active, the slot falls inside
// the dentist's real schedule, and nothing else is already booked there -- so
// there's nothing left for staff to approve. Still flags needs_staff_ack so
// staff get notified a new appointment was set via the app (same pattern as
// cancel/reschedule below).
// Patients still can never set status themselves; it's hardcoded here, not
// client-supplied.
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';

$data = get_json_body();

$data = validate_or_fail($data, [
    'clinicCode' => ['required' => true, 'type' => 'string'],
    'patientId'  => ['required' => true, 'type' => 'int'],
    'dentistId'  => ['required' => true, 'type' => 'int'],
    'serviceId'  => ['required' => true, 'type' => 'int'],
    'date'       => ['required' => true, 'type' => 'date'],
    'time'       => ['required' => true, 'type' => 'time'],
    'notes'      => ['type' => 'string'],
]);

$conn = getConnection();
$clinic = require_clinic($conn, $data['clinicCode']);
require_active_patient($conn, $data['patientId']);
require_appointment_availability($conn, $clinic['clinic_id'], $data['dentistId'], $data['serviceId'], $data['date'], $data['time']);

$insertResult = pg_query_params($conn,
    "INSERT INTO appointment (clinic_id, patient_id, dentist_id, service_id, appointment_date, appointment_time, status, notes, source, needs_staff_ack)
     VALUES ($1, $2, $3, $4, $5, $6, 'confirmed', $7, 'app', true) RETURNING appointment_id",
    array(
        $clinic['clinic_id'],
        $data['patientId'],
        $data['dentistId'],
        $data['serviceId'],
        $data['date'],
        $data['time'],
        $data['notes'] ?? null
    )
);
fail_on_db_error($insertResult, 'appointments/book_appointment: insert', $conn);

$newAppt = pg_fetch_assoc($insertResult);

send_success([
    "message" => "Appointment confirmed",
    "appointmentId" => $newAppt['appointment_id']
]);
