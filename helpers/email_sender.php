<?php
/**
 * Shared EmailJS sender — used server-side by BOTH the clinic-staff and
 * patient password-reset flows.
 *
 * WHY SERVER-SIDE FOR THE PATIENT APP (unlike the web admin):
 * The web admin's "forgot password" sends the actual email from the browser,
 * using EmailJS's JS SDK — that only works because a browser can supply an
 * Origin header, which is how EmailJS's public key stays safe to ship in
 * front-end JS.
 *
 * The Android app is not a browser: it has no Origin header, and its APK can
 * be decompiled, so a private key shipped inside the app is not safe. Instead,
 * the PHP backend calls EmailJS's plain REST API directly (this file), the
 * same way any server-to-server integration would. The private key below
 * never leaves the server.
 *
 * ONE-TIME EmailJS DASHBOARD SETUP (in addition to what's already done for
 * the Gmail service):
 *   1. Account -> Security -> enable "Allow EmailJS API for non-browser applications".
 *   2. Account -> API Keys -> copy the Private Key into EMAILJS_PRIVATE_KEY below.
 *      (Public key is the same "User ID" already used on the web admin side.)
 */

const EMAILJS_SERVICE_ID  = 'service_a5w7glg';       // same Gmail service as the staff flow
const EMAILJS_PUBLIC_KEY  = 'cy_C4WmAMZ0qSfWWo';  // = "User ID" in EmailJS dashboard
const EMAILJS_PRIVATE_KEY = 'jOts6s2pOxyYP2qACmt2-'; // Account > API Keys > Private Key

/**
 * Sends an email via EmailJS's REST API.
 *
 * @param string $templateId      EmailJS template ID to use.
 * @param array  $templateParams  Associative array matching the template's variables.
 * @return array{success: bool, message: string}
 */
function send_via_emailjs(string $templateId, array $templateParams): array {
    $payload = [
        'service_id'   => EMAILJS_SERVICE_ID,
        'template_id'  => $templateId,
        'user_id'      => EMAILJS_PUBLIC_KEY,
        'accessToken'  => EMAILJS_PRIVATE_KEY,
        'template_params' => $templateParams,
    ];

    $ch = curl_init('https://api.emailjs.com/api/v1.0/email/send');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 10,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        return ['success' => false, 'message' => 'EmailJS request failed: ' . $curlError];
    }
    if ($httpCode !== 200) {
        return ['success' => false, 'message' => 'EmailJS returned HTTP ' . $httpCode . ': ' . $response];
    }
    return ['success' => true, 'message' => 'sent'];
}
