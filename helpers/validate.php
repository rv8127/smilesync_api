<?php
// helpers/validate.php - 
// Lightweight request validation + sanitization -- plays the same role
// Pydantic models play in the FastAPI version of this stack.
require_once __DIR__ . '/response.php';

/**
 * Reads the raw request body and JSON-decodes it. Sends a 400 (malformed
 * request) and stops if the body is empty or not valid JSON.
 */
function get_json_body(): array {
    $raw = file_get_contents("php://input");
    $data = json_decode($raw, true);

    if ($raw === '' || $raw === false || !is_array($data)) {
        send_error(400, "Request body must be valid JSON");
    }

    return $data;
}

/**
 * Validates $data against $rules and returns ['field' => 'error message']
 * for every failing field. Empty return = validation passed. Every string
 * value is trimmed in place first (the sanitization step).
 *
 * Rule shape per field:
 *   'fieldName' => [
 *     'required' => true|false,                              // default false
 *     'type'     => 'string'|'email'|'int'|'number'|'date'|'time', // default 'string'
 *     'minlen'   => 8,                                        // min string length
 *   ]
 */
function validate_input(array &$data, array $rules): array {
    $errors = [];

    foreach ($rules as $field => $rule) {
        $required = $rule['required'] ?? false;
        $type = $rule['type'] ?? 'string';
        $value = $data[$field] ?? null;

        if (is_string($value)) {
            $value = trim($value);
            $data[$field] = $value;
        }

        $isEmpty = ($value === null || $value === '');

        if ($isEmpty) {
            if ($required) {
                $errors[$field] = "is required";
            }
            continue;
        }

        switch ($type) {
            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field] = "must be a valid email address";
                }
                break;

            case 'int':
                if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                    $errors[$field] = "must be a whole number";
                }
                break;

            case 'number':
                if (!is_numeric($value)) {
                    $errors[$field] = "must be a number";
                }
                break;

            case 'date':
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) || strtotime($value) === false) {
                    $errors[$field] = "must be a valid date (YYYY-MM-DD)";
                }
                break;

            case 'time':
                if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)(:[0-5]\d)?$/', $value)) {
                    $errors[$field] = "must be a valid time (HH:MM)";
                }
                break;

            case 'string':
            default:
                break; // presence already confirmed above
        }

        if (isset($errors[$field])) {
            continue;
        }

        if (isset($rule['minlen']) && is_string($value) && strlen($value) < $rule['minlen']) {
            $errors[$field] = "must be at least {$rule['minlen']} characters";
        }
    }

    return $errors;
}

/**
 * Runs validate_input() and, on any failure, sends a 422 with every failed
 * field + reason and stops execution. On success, returns the sanitized data.
 */
function validate_or_fail(array $data, array $rules): array {
    $errors = validate_input($data, $rules);
    if (!empty($errors)) {
        send_error(422, "Validation failed", $errors);
    }
    return $data;
}

/**
 * Looks up a clinic by clinic_code. Sends a 404 and stops if not found,
 * or a 403 if the clinic was deactivated by System Admin. Shared helper
 * since nearly every clinic-staff/patient-app endpoint needs this exact
 * lookup -- so this one check here is what actually enforces "a
 * deactivated clinic loses access to the system" everywhere this helper
 * is used, without having to repeat the check per endpoint.
 */
function require_clinic($conn, string $clinicCode): array {
    $result = pg_query_params($conn, "SELECT clinic_id, status, tier FROM clinic WHERE clinic_code = $1", array($clinicCode));
    fail_on_db_error($result, 'require_clinic', $conn);
    $clinic = pg_fetch_assoc($result);
    if (!$clinic) {
        send_error(404, "Clinic code not found");
    }
    if ($clinic['status'] !== 'active') {
        send_error(403, "This clinic has been deactivated. Contact SmileSync support to reactivate it.", ["reason" => "clinic_inactive"]);
    }
    return $clinic;
}

/**
 * Patient app (login/register) is a Pro-tier-only feature -- Basic-tier
 * clinics never get a clinic code to hand out in the first place, only its for them to verify their own clinic, but not for patients, 
 * Sends the same generic
 * "not found" a caller would get for a clinic that doesn't exist at all,
 * rather than confirming the clinic exists but is the wrong tier.
 */
function require_pro_tier_clinic(array $clinic): void {
    if (strtolower($clinic['tier'] ?? '') !== 'pro') {
        send_error(404, "No clinic found for this account.");
    }
}

/**
 * Same check as require_clinic(), but for endpoints that only have a
 * clinic_id on hand (looked up via an appointment/patient row) rather than
 * a clinicCode from the request -- e.g. cancel/reschedule, where the patient
 * app only sends appointmentId/patientId. Kept as a separate helper so
 * require_clinic()'s existing callers/behavior are untouched.
 */
function require_clinic_active_by_id($conn, int $clinicId): array {
    $result = pg_query_params($conn, "SELECT clinic_id, status FROM clinic WHERE clinic_id = $1", array($clinicId));
    fail_on_db_error($result, 'require_clinic_active_by_id', $conn);
    $clinic = pg_fetch_assoc($result);
    if (!$clinic) {
        send_error(404, "Clinic not found");
    }
    if ($clinic['status'] !== 'active') {
        send_error(403, "This clinic has been deactivated. Contact SmileSync support to reactivate it.", ["reason" => "clinic_inactive"]);
    }
    return $clinic;
}

/**
 * Looks up a patient and confirms they are not archived. Sends a 404 if the
 * patient doesn't exist, or a 422 if they're archived (a resigned/departed
 * patient shouldn't be bookable for new appointments -- same idea as an
 * inactive dentist or service, just a different flag since a patient's
 * existing "status" pill already means something else -- has an app account
 * or not, not whether they're still a clinic patient).
 */
function require_active_patient($conn, int $patientId): array {
    $result = pg_query_params($conn, "SELECT patient_id, is_archived FROM patient WHERE patient_id = $1", array($patientId));
    fail_on_db_error($result, 'require_active_patient', $conn);
    $patient = pg_fetch_assoc($result);
    if (!$patient) {
        send_error(404, "Patient not found");
    }
    if ($patient['is_archived'] === 't' || $patient['is_archived'] === true) {
        send_error(422, "This patient is archived and cannot be booked for a new appointment", ["reason" => "patient_archived"]);
    }
    return $patient;
}

/**
 * Reads a required GET query parameter. Sends a 422 (missing required value)
 * and stops if absent.
 */
function require_query_param(string $name): string {
    if (empty($_GET[$name])) {
        send_error(422, "Validation failed", [$name => "is required"]);
    }
    return trim($_GET[$name]);
}

/**
 * Converts "HH:MM" or "HH:MM:SS" into minutes since midnight, for safe
 * numeric time comparison (string comparison breaks on exact-boundary times
 * like "09:00" vs "09:00:00").
 */
function time_to_minutes(?string $time): ?int {
    if (!$time) return null;
    $parts = explode(':', $time);
    if (count($parts) < 2) return null;
    return ((int) $parts[0]) * 60 + ((int) $parts[1]);
}

/**
 * Full server-side check that an appointment CAN actually be booked:
 *   1. the clinic isn't closed that date (clinic_closure)
 *   2. the dentist exists and is active
 *   3. the service exists and is active
 *   4. the dentist has a schedule entry for that day of the week, and the
 *      requested time falls within it (dentist_schedule)
 *   5. the dentist isn't already booked for an overlapping window that same
 *      day (dentist_double_booked) -- server-side twin of the client-side
 *      overlap check in appointment-form.html; closes the gap where a
 *      conflicting appointment gets created outside that one form
 *      (concurrent staff, direct API calls, or the patient app)
 * Sends a 404/422 and stops on the first failing check. This is the
 * server-side enforcement of the same rule the appointment form already
 * checks client-side -- this closes the gap where someone bypassing the UI
 * (or a bug in it) could still book something the schedule doesn't allow.
 *
 * $excludeAppointmentId: pass the appointment's own ID when re-checking on
 * an edit (update_appointment.php), so it doesn't conflict with itself.
 */
function require_appointment_availability($conn, int $clinicId, int $dentistId, int $serviceId, string $date, string $time, ?int $excludeAppointmentId = null) {
    $closureResult = pg_query_params($conn,
        "SELECT reason FROM clinic_closure WHERE clinic_id = $1 AND closure_date = $2",
        array($clinicId, $date)
    );
    fail_on_db_error($closureResult, 'require_appointment_availability: closure check', $conn);
    $closure = pg_fetch_assoc($closureResult);
    if ($closure) {
        $reasonSuffix = $closure['reason'] ? " ({$closure['reason']})" : "";
        send_error(422, "The clinic is closed on this date{$reasonSuffix}", ["reason" => "clinic_closed"]);
    }

    $dentistResult = pg_query_params($conn, "SELECT status FROM dentist WHERE dentist_id = $1", array($dentistId));
    fail_on_db_error($dentistResult, 'require_appointment_availability: dentist check', $conn);
    $dentist = pg_fetch_assoc($dentistResult);
    if (!$dentist) {
        send_error(404, "Dentist not found");
    }
    if ($dentist['status'] !== 'active') {
        send_error(422, "This dentist is not currently active", ["reason" => "dentist_inactive"]);
    }

    $serviceResult = pg_query_params($conn, "SELECT status, duration_minutes FROM service WHERE service_id = $1", array($serviceId));
    fail_on_db_error($serviceResult, 'require_appointment_availability: service check', $conn);
    $service = pg_fetch_assoc($serviceResult);
    if (!$service) {
        send_error(404, "Service not found");
    }
    if ($service['status'] !== 'active') {
        send_error(422, "This service is not currently active", ["reason" => "service_inactive"]);
    }

    $dayName = date('l', strtotime($date)); // 'Monday' etc -- matches dentist_schedule.day_of_week exactly
    $scheduleResult = pg_query_params($conn,
        "SELECT start_time, end_time FROM dentist_schedule WHERE dentist_id = $1 AND day_of_week = $2",
        array($dentistId, $dayName)
    );
    fail_on_db_error($scheduleResult, 'require_appointment_availability: schedule check', $conn);
    $entries = pg_fetch_all($scheduleResult) ?: [];

    if (empty($entries)) {
        send_error(422, "This dentist is not scheduled to work on {$dayName}", ["reason" => "dentist_not_scheduled", "day" => $dayName]);
    }

    $timeMins = time_to_minutes($time);
    $withinHours = false;
    foreach ($entries as $entry) {
        $start = time_to_minutes($entry['start_time']);
        $end = time_to_minutes($entry['end_time']);
        if ($start !== null && $end !== null && $timeMins >= $start && $timeMins <= $end) {
            $withinHours = true;
            break;
        }
    }

    if (!$withinHours) {
        send_error(422, "This dentist's working hours on {$dayName} don't cover this time", ["reason" => "outside_working_hours", "day" => $dayName]);
    }

    // Double-booking check: does this dentist already have another
    // pending/confirmed appointment that day whose window overlaps the
    // incoming one? Each existing appointment's window is its own
    // appointment_time to appointment_time + that appointment's service
    // duration -- hence the join to service for duration_minutes.
    $conflictSql = "SELECT a.appointment_id, a.appointment_time, sv.duration_minutes
                     FROM appointment a
                     JOIN service sv ON sv.service_id = a.service_id
                     WHERE a.dentist_id = $1 AND a.appointment_date = $2
                       AND a.status IN ('pending', 'confirmed')";
    $conflictParams = array($dentistId, $date);
    if ($excludeAppointmentId !== null) {
        $conflictSql .= " AND a.appointment_id != $3";
        $conflictParams[] = $excludeAppointmentId;
    }

    $conflictResult = pg_query_params($conn, $conflictSql, $conflictParams);
    fail_on_db_error($conflictResult, 'require_appointment_availability: double-booking check', $conn);
    $existingAppointments = pg_fetch_all($conflictResult) ?: [];

    $newStart = time_to_minutes($time);
    $newEnd = $newStart + (int) $service['duration_minutes'];

    foreach ($existingAppointments as $existingAppt) {
        $existingStart = time_to_minutes($existingAppt['appointment_time']);
        $existingEnd = $existingStart + (int) $existingAppt['duration_minutes'];

        if ($newStart < $existingEnd && $existingStart < $newEnd) {
            send_error(422, "This dentist already has an overlapping appointment at this time", ["reason" => "dentist_double_booked"]);
        }
    }
}
