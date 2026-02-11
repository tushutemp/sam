<?php
require_once 'config/database.php';

// Check if user is logged in
session_start();
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get opinion ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid opinion ID']);
    exit;
}

$opinion_id = intval($_GET['id']);

// Fetch opinion from database
$sql = "SELECT * FROM opinions WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $opinion_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Opinion not found']);
    exit;
}

$opinion = $result->fetch_assoc();

// Return opinion data as JSON
header('Content-Type: application/json');
echo json_encode($opinion);
?>