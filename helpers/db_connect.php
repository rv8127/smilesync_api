<?php
// helpers/db_connect.php - function to connect to the database
function getConnection() {
    $config = require __DIR__ . '/config.php';
    $db = $config['db'];
    
    $connString = "host={$db['host']} port={$db['port']} dbname={$db['name']} user={$db['user']} password={$db['password']}";
    $conn = pg_connect($connString);

    if (!$conn) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Database connection failed"]);
        exit;
    }
    return $conn;
}