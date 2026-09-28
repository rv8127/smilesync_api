<?php
// helpers/db_connect.php - function to connect to the database
function getConnection() {
    $host = "localhost"; 
    $port = "5432";
    $dbname = "smilesync_database";
    $user = "smilesync_team"; 
    $password = "sm1le_sync726!";

    $connString = "host=$host port=$port dbname=$dbname user=$user password=$password";
    $conn = pg_connect($connString);

    if (!$conn) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Database connection failed"]);
        exit;
    }
    return $conn;
}