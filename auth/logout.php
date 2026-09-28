<?php
// auth/logout.php
require_once '../helpers/headers.php';
require_once '../helpers/response.php';

session_start();
session_unset();
session_destroy();

send_success(["message" => "Logged out"]);
