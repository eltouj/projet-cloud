<?php
header('Content-Type: application/json');

$serverName = "10.0.0.6"; 
$username = "root"; 
$password = "";
$database = "transportdb";

$conn = new mysqli($serverName, $username, $password, $database);

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Échec de connexion à la base de données"]));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $colis_id = $_POST['colis_id'] ?? '';
    $status = $_POST['status'] ?? '';

    if (empty($colis_id) || empty($status)) {
        echo json_encode(["status" => "error", "message" => "ID ou statut manquant"]);
        exit;
    }

    // Use prepared statement to prevent SQL injection
    $stmt = $conn->prepare("UPDATE Clients SET Status = ? WHERE ClientID = ?");
    $stmt->bind_param("si", $status, $colis_id);
    
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Statut mis à jour avec succès"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Erreur lors de la mise à jour"]);
    }

    $stmt->close();
} else {
    echo json_encode(["status" => "error", "message" => "Méthode non autorisée"]);
}

$conn->close();
?>