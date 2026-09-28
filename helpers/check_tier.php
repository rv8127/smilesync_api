<?php
// helpers/check_tier.php - functions for checking if a clinic has the required tier
//for Smilesync, it has two tiers: Basic and Pro. Basic only gets the web admin, whereas Pro gets the web admin and the mobile app. 

function requireProTier($clinicId, $conn) {
    $result = pg_query_params($conn, "SELECT tier FROM clinic WHERE clinicid = $1", array($clinicId));
    $row = pg_fetch_assoc($result);

    if (!$row || strtolower($row['tier']) !== 'pro') {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "This feature requires the Pro tier"]);
        exit;
    }
}