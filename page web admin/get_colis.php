<?php
$serverName = "10.0.0.6";
$username = "root"; 
$password = ""; 
$database = "transportdb"; 

$conn = new mysqli($serverName, $username, $password, $database);

if ($conn->connect_error) {
    die("Échec de la connexion : " . $conn->connect_error);
}

$sql = "SELECT ClientID, Name, Email, Phone, PackageDetails, StartLocation, EndLocation, Status FROM Clients";
$result = $conn->query($sql);

$html = '';
while ($row = $result->fetch_assoc()) {
    // Determine status class
    $statusClass = '';
    if ($row['Status'] == 'Delivered') $statusClass = 'status-arrived';
    elseif ($row['Status'] == 'Denied') $statusClass = 'status-denied';
    else $statusClass = 'status-encours';
    
    $html .= '<tr>';
    $html .= '<td>'.$row['ClientID'].'</td>';
    $html .= '<td>'.$row['Name'].'</td>';
    $html .= '<td>'.$row['Email'].'</td>';
    $html .= '<td>'.$row['Phone'].'</td>';
    $html .= '<td>'.$row['PackageDetails'].'</td>';
    $html .= '<td>'.$row['StartLocation'].'</td>';
    $html .= '<td>'.$row['EndLocation'].'</td>';
    $html .= '<td class="'.$statusClass.'">'.$row['Status'].'</td>';
    $html .= '<td>
    <button onclick="updateStatus('.$row['ClientID'].',\'Delivered\')" class="btn btn-success btn-sm">Livré</button>
    <button onclick="updateStatus('.$row['ClientID'].',\'Denied\')" class="btn btn-danger btn-sm">Refusé</button>
    <button onclick="updateStatus('.$row['ClientID'].',\'In progress\')" class="btn btn-warning btn-sm">En cours</button>
</td>';
    $html .= '</tr>';
}

echo $html;
$conn->close();
?>