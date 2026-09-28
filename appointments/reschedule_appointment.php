<?php
// appointments/reschedule_appointment.php -- patient reschedules their own
// upcoming appointment to a new date/time, same dentist and service.
// Re-validates the new slot exactly like book_appointment.php does (clinic
// open, dentist/service active, inside the dentist's real schedule, nothing
// else already booked there) -- same reasoning as booking: if it passes
// availability, there's nothing left for staff to approve. Auto-applies the
// new slot and flags needs_staff_ack so staff still gets notified their
// patient moved to a different day/time, alongside the old slot for context.
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';

$data = get_json_body();

$data = validate_or_fail($data, [
    'appointmentId' => ['required' => true, 'type' => 'int'],
    'patientId'     => ['required' => true, 'type' => 'int'],
    'date'          => ['required' => true, 'type' => 'date'],
    'time'          => ['required' => true, 'type' => 'time'],
]);

$conn = getConnection();

// Ownership check - a patient can only reschedule their own appointment.
$apptResult = pg_query_params($conn,
    "SELECT appointment_id, status, clinic_id, dentist_id, service_id, appointment_date, appointment_time
     FROM appointment WHERE appointment_id = $1 AND patient_id = $2",
    array($data['appointmentId'], $data['patientId'])
);
fail_on_db_error($apptResult, 'appointments/reschedule_appointment: lookup', $conn);
$appt = pg_fetch_assoc($apptResult);

if (!$appt) {
    send_error(404, "Appointment not found");
}

require_clinic_active_by_id($conn, (int) $appt['clinic_id']);

if ($appt['status'] !== 'pending' && $appt['status'] !== 'confirmed') {
    send_error(422, "This appointment can no longer be rescheduled", ["reason" => "invalid_state", "currentStatus" => $appt['status']]);
}

require_appointment_availability($conn, $appt['clinic_id'], $appt['dentist_id'], $appt['service_id'], $data['date'], $data['time']);

$updateResult = pg_query_params($conn,
    "UPDATE appointment
     SET appointment_date = $1, appointment_time = $2, status = 'confirmed',
         needs_staff_ack = true, previous_date = $3, previous_time = $4
     WHERE appointment_id = $5",
    array($data['date'], $data['time'], $appt['appointment_date'], $appt['appointment_time'], $data['appointmentId'])
);
fail_on_db_error($updateResult, 'appointments/reschedule_appointment: update', $conn);

send_success(["message" => "Appointment rescheduled"]);
