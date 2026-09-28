<?php
// helpers/response.php
// helpers/response.php - structured success/error responses, similar to FastAPI's HTTPException
// Structured success/error responses -- plays the same role HTTPException
// plays in the FastAPI version of this stack.
//
// HTTP status codes used by this API:
//   200 - successful request (send_success uses this by default)
//   201 - a resource was created successfully
//   400 - malformed request (e.g. request body is not valid JSON)
//   401 - authentication failure, such as invalid credentials
//   403 - access denied, such as a deactivated clinic or unavailable tier
//   404 - a referenced clinic/patient/dentist/service/appointment/etc. was not found
//   422 - the request failed validation or a business rule, such as no duplicates
//   500 - unexpected server/DB error

/**
 * Sends an error response and stops execution.
 *
 * "success" and "message" stay at the TOP LEVEL because every existing
 * endpoint and every frontend fetch() call already reads data.success /
 * data.message directly -- changing that would break the whole site.
 * The nested "error" object is the structured (Pydantic/HTTPException-style)
 * shape this activity asks for, included ALONGSIDE the original shape rather
 * than replacing it, so nothing already built breaks.
 */
function send_error(int $status, string $message, array $details = []) {
    http_response_code($status);
    echo json_encode([
        "success" => false,
        "message" => $message,
        "error" => [
            "status" => $status,
            "message" => $message,
            "details" => $details
        ]
    ]);
    exit;
}

/**
 * Sends a success response and stops execution. $extra fields are merged at
 * the TOP LEVEL (not nested under "data") for the same reason above -- every
 * page's JS reads fields like data.patients, data.clinicId directly.
 */
function send_success(array $extra = [], int $status = 200) {
    http_response_code($status);
    echo json_encode(array_merge(["success" => true], $extra));
    exit;
}

/**
 * If $result is falsy (a failed pg_query/pg_query_params call), logs the real
 * Postgres error server-side and sends a generic 500 to the client -- so raw
 * DB error details are never leaked in the response.
 */
function fail_on_db_error($result, string $context, $conn = null) {
    if (!$result) {
        if ($conn) {
            error_log("[$context] " . pg_last_error($conn));
        }
        send_error(500, "Something went wrong on our end. Please try again.");
    }
}
