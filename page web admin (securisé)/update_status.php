<?php
require_once __DIR__.'/vendor/autoload.php';

use Azure\Identity\DefaultAzureCredential;
use Azure\KeyVault\KeyVaultClient;

header('Content-Type: application/json');
header("Strict-Transport-Security: max-age=63072000; includeSubDomains; preload");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");

// 1. Authenticate to Azure and get secrets
try {
    $credential = new DefaultAzureCredential();
    $keyVaultClient = new KeyVaultClient(
        vaultUrl: 'https://secukey.vault.azure.net',
        credential: $credential
    );

    $dbSecret = $keyVaultClient->getSecret('dbConfig')->value;
    $dbConfig = json_decode($dbSecret, true);

    // 2. Secure DB connection
    $conn = new mysqli(
        $dbConfig['serverName'],
        $dbConfig['username'],
        $dbConfig['password'],
        $dbConfig['database'],
        null,
        [
            MYSQLI_OPT_SSL_VERIFY_SERVER_CERT => true,
            MYSQLI_OPT_SSL_KEY => '/etc/ssl/private/mysql-client-key.pem',
            MYSQLI_OPT_SSL_CERT => '/etc/ssl/certs/mysql-client-cert.pem'
        ]
    );

    if ($conn->connect_error) {
        throw new Exception("Database connection failed");
    }

    // 3. Validate input
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Method not allowed", 405);
    }

    $colis_id = filter_input(INPUT_POST, 'colis_id', FILTER_VALIDATE_INT);
    $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_SPECIAL_CHARS);

    if (!$colis_id || !$status) {
        throw new Exception("Missing ID or status", 400);
    }

    // 4. Validate status against allowed values
    $allowedStatuses = ['Delivered', 'In progress', 'Denied'];
    if (!in_array($status, $allowedStatuses)) {
        throw new Exception("Invalid status value", 400);
    }

    // 5. Secure database operation
    $stmt = $conn->prepare("UPDATE Clients SET Status = ? WHERE ClientID = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: ".$conn->error);
    }

    $stmt->bind_param("si", $status, $colis_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Execution failed: ".$stmt->error);
    }

    // 6. Audit log
    file_put_contents(
        '/var/log/transport_audit.log',
        date('[Y-m-d H:i:s]')." Status updated - ID: $colis_id, New Status: $status, IP: ".$_SERVER['REMOTE_ADDR'].PHP_EOL,
        FILE_APPEND
    );

    echo json_encode([
        "status" => "success",
        "message" => "Status updated successfully",
        "data" => [
            "id" => $colis_id,
            "new_status" => $status
        ]
    ]);

} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage(),
        "code" => $e->getCode()
    ]);
    
    // Log full error with stack trace
    error_log($e->__toString());
} finally {
    // 7. Clean up resources
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
}
?>