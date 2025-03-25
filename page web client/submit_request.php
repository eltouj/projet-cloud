<?php
// Database connection details
$server = "10.0.0.6";  // IP of the DB VM
$username = "root";     
$password = "";         
$dbname = "transportdb"; 

$conn = new mysqli($server, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get form data safely
$name = $_POST['name'] ?? null;
$email = $_POST['email'] ?? null;
$phone = $_POST['phone'] ?? null;
$package = $_POST['package'] ?? null;
$startLocation = $_POST['startLocation'] ?? null;
$endLocation = $_POST['endLocation'] ?? null;
$status = "En cours"; // Default status

if (!$name || !$email || !$phone || !$package || !$startLocation || !$endLocation) {
    die("Erreur: Tous les champs sont obligatoires.");
}

$stmt = $conn->prepare("INSERT INTO Clients (Name, Email, Phone, PackageDetails, StartLocation, EndLocation, Status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)");

$stmt->bind_param("sssssss", $name, $email, $phone, $package, $startLocation, $endLocation, $status);

// Execute the query and show a confirmation message
$success = false;
if ($stmt->execute()) {
    $success = true;
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation de la Demande</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* General Styling */
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #2c3e50,rgb(127, 163, 187));
            text-align: center;
            padding: 50px;
            color: #fff;
        }
        
        /* Container Box */
        .container {
            width: 50%;
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0px 10px 20px rgba(0, 0, 0, 0.2);
            text-align: left;
            display: inline-block;
            animation: fadeIn 0.6s ease-in-out;
        }
        
        /* Headings */
        h2 {
            font-size: 26px;
            font-weight: 600;
            text-align: center;
        }

        .success h2 { color:rgb(39, 158, 146); }
        .error h2 { color: #e74c3c; }

        /* Paragraph Styling */
        p {
            font-size: 18px;
            margin: 10px 0;
            color: #333;
            line-height: 1.6;
        }

        strong {
            color: #2980b9;
        }

        /* Button Styling */
        .btn {
            display: block;
            width: 100%;
            text-align: center;
            margin-top: 20px;
            padding: 12px;
            background: #3498db;
            color: white;
            font-size: 18px;
            font-weight: 600;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn:hover {
            background: #2980b9;
            transform: scale(1.05);
        }

        /* Fade-in Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                width: 80%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($success): ?>
            <div class="success">
                <h2>✔️ Demande envoyée avec succès !</h2>
                <p><strong>Nom du client :</strong> <?= htmlspecialchars($name) ?></p>
                <p><strong>Email :</strong> <?= htmlspecialchars($email) ?></p>
                <p><strong>Téléphone :</strong> <?= htmlspecialchars($phone) ?></p>
                <p><strong>Colis :</strong> <?= htmlspecialchars($package) ?></p>
                <p><strong>Lieu de départ :</strong> <?= htmlspecialchars($startLocation) ?></p>
                <p><strong>Lieu d'arrivée :</strong> <?= htmlspecialchars($endLocation) ?></p>
                <p><strong>Statut :</strong> <span style="color: #f39c12; font-weight: bold;"><?= htmlspecialchars($status) ?></span></p>
                <a href="index.html" class="btn">Retour à l'accueil</a>
            </div>
        <?php else: ?>
            <div class="error">
                <h2>❌ Erreur lors de l'envoi</h2>
                <p>Une erreur s'est produite, veuillez réessayer.</p>
                <a href="index.html" class="btn">Retour</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
