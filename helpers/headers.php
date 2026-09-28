<?php // helpers/headers.php - gives permission to any origin like web admin to access the API, and sets the content type to JSON. 
// A wildcard '*' origin cannot be combined with credentials (session
// cookies) per the CORS spec -- browsers silently refuse to send/store the
// cookie, which breaks requireAuth()/requireAdminAuth() for anyone loading
// the frontend from a different host/port than the API. Reflecting the
// actual request origin (instead of '*') keeps this open to any origin
// during dev while still allowing credentials.
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header("Access-Control-Allow-Origin: $origin");
header('Access-Control-Allow-Credentials: true');
header('Vary: Origin');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}