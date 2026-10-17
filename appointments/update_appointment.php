<?php
// appointments/update_appointment.php -- full edit (date/time/dentist/service/status/notes) / CLINIC STAFF ONLY
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';

$data = get_json_body();

$data = validate_or_fail($data, [
    'appointmentId' => ['required' => true, 'type' => 'int'],
    'patientId'     => ['type' => 'int'],
    'dentistId'     => ['type' => 'int'],
    'serviceId'     => ['type' => 'int'],
    'date'          => ['type' => 'date'],
    'time'          => ['type' => 'time'],
    'status'        => ['type' => 'string'],
    'notes'         => ['type' => 'string'],
]);

$conn = getConnection();

$existingResult = pg_query_params($conn,
    "SELECT clinic_id, patient_id, dentist_id, service_id, appointment_date, appointment_time, status, notes
     FROM appointment WHERE appointment_id = $1",
    array($data['appointmentId'])
);
fail_on_db_error($existingResult, 'appointments/update_appointment: lookup', $conn);
$existing = pg_fetch_assoc($existingResult);

if (!$existing) {
    send_error(404, "Appointment not found");
}

$patientId = $data['patientId'] ?? $existing['patient_id'];
$dentistId = $data['dentistId'] ?? $existing['dentist_id'];
$serviceId = $data['serviceId'] ?? $existing['service_id'];
$date      = $data['date']      ?? $existing['appointment_date'];
$time      = $data['time']      ?? $existing['appointment_time'];
$status    = $data['status']    ?? $existing['status'];
if ($status === 'pending') { $status = 'confirmed'; } // no 'pending' status: availability is validated when booking
$notes     = $data['notes']     ?? $existing['notes'];

// Only re-check availability if the actual scheduling facts are changing --
// a pure status/notes edit (e.g. completing an old appointment whose dentist
// has since gone inactive) must never be blocked by this.
$schedulingChanged = (
    (int) $dentistId !== (int) $existing['dentist_id'] ||
    (int) $serviceId !== (int) $existing['service_id'] ||
    $date !== $existing['appointment_date'] ||
    $time !== $existing['appointment_time']
);

// If the DATE or TIME actually changed and staff didn't explicitly pick a
// different status themselves in this same edit (the status field just
// came back as whatever it already was), treat this as a reschedule and
// set status accordingly. An explicit status choice (e.g. picking
// Confirmed) always wins. Deliberately scoped to date/time only, not
// dentist/service -- a treatment/service change alone (with date/time
// untouched) is left entirely to staff's judgement via the Status field,
// not auto-decided.
$dateTimeChanged = ($date !== $existing['appointment_date'] || $time !== $existing['appointment_time']);
$statusExplicitlyChanged = isset($data['status']) && $data['status'] !== $existing['status'];
if ($dateTimeChanged && !$statusExplicitlyChanged) {
    $status = 'rescheduled';
}

if ($schedulingChanged) {
    require_appointment_availability($conn, $existing['clinic_id'], $dentistId, $serviceId, $date, $time, (int) $data['appointmentId']);
}

// Same idea, separately -- only re-check the patient isn't archived if the
// patient is actually being reassigned to someone new, not on a pure
// status/notes edit of an existing booking.
if ((int) $patientId !== (int) $existing['patient_id']) {
    require_active_patient($conn, $patientId);
}

$updateResult = pg_query_params($conn,
    "UPDATE appointment SET patient_id = $1, dentist_id = $2, service_id = $3,
                            appointment_date = $4, appointment_time = $5, status = $6, notes = $7
     WHERE appointment_id = $8",
    array($patientId, $dentistId, $serviceId, $date, $time, $status, $notes, $data['appointmentId'])
);
fail_on_db_error($updateResult, 'appointments/update_appointment: update', $conn);

// Same rule as update_appointment_status.php: completing an appointment auto-logs a treatment record.
$treatmentCreated = false;

if ($status === 'completed') {
    $dupCheck = pg_query_params($conn,
        "SELECT treatment_record_id FROM treatment_record
         WHERE patient_id = $1 AND dentist_id = $2 AND service_id = $3 AND record_date = $4",
        array($patientId, $dentistId, $serviceId, $date)
    );
    fail_on_db_error($dupCheck, 'appointments/update_appointment: treatment dup check', $conn);

    if (pg_num_rows($dupCheck) === 0) {
        $treatmentInsert = pg_query_params($conn,
            "INSERT INTO treatment_record (patient_id, dentist_id, service_id, record_date, notes)
             VALUES ($1, $2, $3, $4, $5)",
            array($patientId, $dentistId, $serviceId, $date, 'Auto-logged from completed appointment.')
        );
        fail_on_db_error($treatmentInsert, 'appointments/update_appointment: treatment insert', $conn);
        $treatmentCreated = true;
    }
}

send_success([
    "message" => "Appointment updated successfully",
    "treatmentRecordCreated" => $treatmentCreated
]);
