<?php
// helpers/db_connect.php - function to connect to the database
function getConnection() {
    $config = file_exists(__DIR__ . '/config.php') ? require __DIR__ . '/config.php' : [];
    $db = $config['db'] ?? [];

    $host     = getenv('DB_HOST')     ?: ($db['host'] ?? 'localhost');
    $port     = getenv('DB_PORT')     ?: ($db['port'] ?? '5432');
    $dbname   = getenv('DB_NAME')     ?: ($db['name'] ?? '');
    $user     = getenv('DB_USER')     ?: ($db['user'] ?? '');
    $password = getenv('DB_PASSWORD') ?: ($db['password'] ?? '');
    $sslmode  = getenv('DB_SSLMODE')  ?: 'prefer';

    $connString = "host=$host port=$port dbname=$dbname user=$user password=$password sslmode=$sslmode";
    $conn = pg_connect($connString);

    if (!$conn) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Database connection failed"]);
        exit;
    }
    return $conn;
}