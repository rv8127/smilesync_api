<?php
// dashboard/get_dashboard_summary.php - Retrieves a summary of dashboard information for a specific clinic
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';

$clinicCode = require_query_param('clinicCode');

$conn = getConnection();
$clinic = require_clinic($conn, $clinicCode);
$clinicId = $clinic['clinic_id'];

$todayResult = pg_query_params($conn,
    "SELECT a.appointment_id, a.appointment_time, a.status,
            p.first_name AS patient_first_name, p.last_name AS patient_last_name,
            d.first_name AS dentist_first_name, d.last_name AS dentist_last_name,
            s.service_name
     FROM appointment a
     JOIN patient p ON p.patient_id = a.patient_id
     JOIN dentist d ON d.dentist_id = a.dentist_id
     JOIN service s ON s.service_id = a.service_id
     WHERE a.clinic_id = $1 AND a.appointment_date = CURRENT_DATE
     ORDER BY a.appointment_time",
    array($clinicId)
);
fail_on_db_error($todayResult, 'dashboard: today appointments', $conn);
$todayAppointments = pg_fetch_all($todayResult) ?: [];
$todayConfirmedCount = count(array_filter($todayAppointments, fn($a) => $a['status'] === 'confirmed'));

$pendingApptResult = pg_query_params($conn,
    "SELECT COUNT(*) AS count FROM appointment WHERE clinic_id = $1 AND status = 'pending'",
    array($clinicId)
);
fail_on_db_error($pendingApptResult, 'dashboard: pending approval count', $conn);
$pendingApprovalCount = (int) pg_fetch_assoc($pendingApptResult)['count'];

$patientCountsResult = pg_query_params($conn,
    "SELECT COUNT(*) AS total,
            COUNT(*) FILTER (WHERE pa.patient_account_id IS NULL) AS pending
     FROM patient p
     LEFT JOIN patient_account pa ON pa.patient_id = p.patient_id
     WHERE p.clinic_id = $1",
    array($clinicId)
);
fail_on_db_error($patientCountsResult, 'dashboard: patient counts', $conn);
$patientCounts = pg_fetch_assoc($patientCountsResult);

$dentistCountsResult = pg_query_params($conn,
    "SELECT COUNT(*) AS total, COUNT(*) FILTER (WHERE status = 'active') AS active
     FROM dentist WHERE clinic_id = $1",
    array($clinicId)
);
fail_on_db_error($dentistCountsResult, 'dashboard: dentist counts', $conn);
$dentistCounts = pg_fetch_assoc($dentistCountsResult);

$serviceCountResult = pg_query_params($conn,
    "SELECT COUNT(*) AS count FROM service WHERE clinic_id = $1 AND status = 'active'",
    array($clinicId)
);
fail_on_db_error($serviceCountResult, 'dashboard: active services', $conn);
$activeServicesCount = (int) pg_fetch_assoc($serviceCountResult)['count'];

$totalApptResult = pg_query_params($conn,
    "SELECT COUNT(*) AS count FROM appointment WHERE clinic_id = $1",
    array($clinicId)
);
fail_on_db_error($totalApptResult, 'dashboard: total appointments', $conn);
$totalAppointmentsCount = (int) pg_fetch_assoc($totalApptResult)['count'];

$needsAckResult = pg_query_params($conn,
    "SELECT COUNT(*) AS count FROM appointment WHERE clinic_id = $1 AND needs_staff_ack = true",
    array($clinicId)
);
fail_on_db_error($needsAckResult, 'dashboard: needs staff ack count', $conn);
$needsAckCount = (int) pg_fetch_assoc($needsAckResult)['count'];

$annResult = pg_query_params($conn,
    "SELECT title, type, announcement_date FROM announcement
     WHERE clinic_id = $1 ORDER BY announcement_date DESC LIMIT 2",
    array($clinicId)
);
fail_on_db_error($annResult, 'dashboard: recent announcements', $conn);
$recentAnnouncements = pg_fetch_all($annResult) ?: [];

send_success([
    "todayAppointments" => $todayAppointments,
    "todayConfirmedCount" => $todayConfirmedCount,
    "pendingApprovalCount" => $pendingApprovalCount,
    "totalPatients" => (int) $patientCounts['total'],
    "pendingPatients" => (int) $patientCounts['pending'],
    "totalDentists" => (int) $dentistCounts['total'],
    "activeDentists" => (int) $dentistCounts['active'],
    "activeServicesCount" => $activeServicesCount,
    "totalAppointmentsCount" => $totalAppointmentsCount,
    "needsAckCount" => $needsAckCount,
    "recentAnnouncements" => $recentAnnouncements
]);
