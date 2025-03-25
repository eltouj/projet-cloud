<?php
require_once __DIR__.'/vendor/autoload.php';

use Azure\Identity\DefaultAzureCredential;
use Azure\KeyVault\KeyVaultClient;

header('Content-Type: text/html');
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");

try {
    // 1. Authenticate to Azure and get database credentials
    $credential = new DefaultAzureCredential();
    $keyVaultClient = new KeyVaultClient(
        vaultUrl: 'https://secukey.vault.azure.net',
        credential: $credential
    );

    $dbSecret = $keyVaultClient->getSecret('dbConfig')->value;
    $dbConfig = json_decode($dbSecret, true);

    // 2. Establish secure database connection
    $conn = new mysqli(
        $dbConfig['serverName'],
        $dbConfig['username'],
        $dbConfig['password'],
        $dbConfig['database'],
        null,
        [
            MYSQLI_OPT_SSL_VERIFY_SERVER_CERT => true,
            MYSQLI_OPT_SSL_CA => '/etc/ssl/certs/transport-db-ca.pem'
        ]
    );

    if ($conn->connect_error) {
        throw new Exception("Database connection failed");
    }

    // 3. Secure query with prepared statement
    $stmt = $conn->prepare("SELECT ClientID, Name, Email, Phone, PackageDetails, StartLocation, EndLocation, Status FROM Clients");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    // 4. Output escaping
    $html = '';
    $allowedStatuses = ['Delivered', 'Denied', 'In progress'];
    
    while ($row = $result->fetch_assoc()) {
        // Validate and escape all output
        $status = in_array($row['Status'], $allowedStatuses) ? $row['Status'] : 'In progress';
        $statusClass = match($status) {
            'Delivered' => 'status-arrived',
            'Denied' => 'status-denied',
            default => 'status-encours'
        };
        
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($row['ClientID'], ENT_QUOTES) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['Name'], ENT_QUOTES) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['Email'], ENT_QUOTES) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['Phone'], ENT_QUOTES) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['PackageDetails'], ENT_QUOTES) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['StartLocation'], ENT_QUOTES) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['EndLocation'], ENT_QUOTES) . '</td>';
        $html .= '<td class="' . htmlspecialchars($statusClass) . '">' . htmlspecialchars($status) . '</td>';
        $html .= '<td>
            <button onclick="updateStatus(' . (int)$row['ClientID'] . ',\'Delivered\')" class="btn btn-success btn-sm">Livré</button>
            <button onclick="updateStatus(' . (int)$row['ClientID'] . ',\'Denied\')" class="btn btn-danger btn-sm">Refusé</button>
            <button onclick="updateStatus(' . (int)$row['ClientID'] . ',\'In progress\')" class="btn btn-warning btn-sm">En cours</button>
        </td>';
        $html .= '</tr>';
    }

    echo $html;

} catch (Exception $e) {
    // Secure error logging
    error_log("Database error: " . $e->getMessage());
    echo '<tr><td colspan="9" class="text-danger">Error loading data</td></tr>';
} finally {
    // 5. Clean up resources
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
}
?>