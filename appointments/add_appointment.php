<?php
// appointments/add_appointment.php -- clinic staff creates an appointment
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
    'status'     => ['type' => 'string'],
    'notes'      => ['type' => 'string'],
]);

$conn = getConnection();
$clinic = require_clinic($conn, $data['clinicCode']);
require_active_patient($conn, $data['patientId']);
require_appointment_availability($conn, $clinic['clinic_id'], $data['dentistId'], $data['serviceId'], $data['date'], $data['time']);

$insertResult = pg_query_params($conn,
    "INSERT INTO appointment (clinic_id, patient_id, dentist_id, service_id, appointment_date, appointment_time, status, notes, source)
     VALUES ($1, $2, $3, $4, $5, $6, $7, $8, 'staff') RETURNING appointment_id",
    array(
        $clinic['clinic_id'],
        $data['patientId'],
        $data['dentistId'],
        $data['serviceId'],
        $data['date'],
        $data['time'],
        $data['status'] ?? 'pending',
        $data['notes'] ?? null
    )
);
fail_on_db_error($insertResult, 'appointments/add_appointment: insert', $conn);

$newAppt = pg_fetch_assoc($insertResult);

send_success([
    "message" => "Appointment created successfully",
    "appointmentId" => $newAppt['appointment_id']
]);
