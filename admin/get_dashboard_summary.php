<?php
// admin/get_dashboard_summary.php -- platform-wide numbers for the System
// Dashboard page (dashboard.html): clinic counts, patient/appointment
// totals, the clinic list card, and the recent-activity card.
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';
require_once '../helpers/check_auth.php';

requireAdminAuth();
$conn = getConnection();

$clinicCountsResult = pg_query($conn,
    "SELECT COUNT(*) AS total,
            COUNT(*) FILTER (WHERE status = 'active') AS active,
            COUNT(*) FILTER (WHERE status = 'inactive') AS inactive
     FROM clinic"
);
fail_on_db_error($clinicCountsResult, 'admin/get_dashboard_summary: clinic counts', $conn);
$clinicCounts = pg_fetch_assoc($clinicCountsResult);

$patientCountResult = pg_query($conn, "SELECT COUNT(*) AS count FROM patient");
fail_on_db_error($patientCountResult, 'admin/get_dashboard_summary: patient count', $conn);
$totalPatients = (int) pg_fetch_assoc($patientCountResult)['count'];

$apptCountResult = pg_query($conn, "SELECT COUNT(*) AS count FROM appointment");
fail_on_db_error($apptCountResult, 'admin/get_dashboard_summary: appointment count', $conn);
$totalAppointments = (int) pg_fetch_assoc($apptCountResult)['count'];

$clinicsResult = pg_query($conn,
    "SELECT c.clinic_id, c.clinic_name, c.clinic_code, c.status,
            COUNT(p.patient_id) AS patient_count
     FROM clinic c
     LEFT JOIN patient p ON p.clinic_id = c.clinic_id
     GROUP BY c.clinic_id, c.clinic_name, c.clinic_code, c.status
     ORDER BY c.clinic_id"
);
fail_on_db_error($clinicsResult, 'admin/get_dashboard_summary: clinic list', $conn);
$clinics = pg_fetch_all($clinicsResult) ?: [];

$activityResult = pg_query($conn,
    "SELECT action, description, created_at FROM activity_log ORDER BY created_at DESC LIMIT 4"
);
fail_on_db_error($activityResult, 'admin/get_dashboard_summary: recent activity', $conn);
$recentActivity = pg_fetch_all($activityResult) ?: [];

send_success([
    "totalClinics" => (int) $clinicCounts['total'],
    "activeClinics" => (int) $clinicCounts['active'],
    "inactiveClinics" => (int) $clinicCounts['inactive'],
    "totalPatients" => $totalPatients,
    "totalAppointments" => $totalAppointments,
    "clinics" => $clinics,
    "recentActivity" => $recentActivity
]);
