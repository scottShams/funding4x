<?php
require_once '../functions/auth.php';
checkAdminAuth();
require_once '../../database.php';

// Get database connection
$pdo = getPDO();

// Handle GET request to fetch templates
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['id'])) {
        // Get specific template
        $stmt = $pdo->prepare("SELECT * FROM email_templates WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($template) {
            echo json_encode($template);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Template not found']);
        }
    } else {
        // Get all templates
        $stmt = $pdo->prepare("SELECT id, name FROM email_templates ORDER BY name ASC");
        $stmt->execute();
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($templates);
    }
    exit;
}

// Handle POST request to save template
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $name = trim($_POST['name']);
    $subject = trim($_POST['subject']);
    $body = trim($_POST['body']);

    if (empty($name) || empty($subject) || empty($body)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }

    // Check if template name already exists
    $stmt = $pdo->prepare("SELECT id FROM email_templates WHERE name = ?");
    $stmt->execute([$name]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        echo json_encode(['success' => false, 'message' => 'Template name already exists']);
        exit;
    }

    // Save template
    $stmt = $pdo->prepare("INSERT INTO email_templates (name, subject, body, created_at) VALUES (?, ?, ?, NOW())");
    $success = $stmt->execute([$name, $subject, $body]);

    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Template saved successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save template']);
    }
    exit;
}
?>
