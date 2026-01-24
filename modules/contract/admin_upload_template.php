<?php
/**
 * admin_upload_template.php
 * Handler for template uploads
 */
session_start();

header('Content-Type: application/json');

// Check if user has admin privileges
// Uncomment and modify based on your permission system
// if (!User('PROFILE') == 'admin') {
//     echo json_encode(['success' => false, 'message' => 'Accès refusé']);
//     exit;
// }

// Check if file was uploaded
if (!isset($_FILES['template']) || $_FILES['template']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Aucun fichier reçu']);
    exit;
}

// Validate file type
$fileType = $_FILES['template']['type'];
$fileExtension = strtolower(pathinfo($_FILES['template']['name'], PATHINFO_EXTENSION));

if ($fileType !== 'application/pdf' || $fileExtension !== 'pdf') {
    echo json_encode(['success' => false, 'message' => 'Seuls les fichiers PDF sont acceptés']);
    exit;
}

// Validate file size (10 MB max)
if ($_FILES['template']['size'] > 10 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'Le fichier ne doit pas dépasser 10 MB']);
    exit;
}

// Get template type (primaire or secondaire)
$type = isset($_POST['type']) ? $_POST['type'] : '';
if (!in_array($type, ['primaire', 'secondaire'])) {
    echo json_encode(['success' => false, 'message' => 'Type de template invalide']);
    exit;
}

// Set upload directory with subdirectory for type
$uploadDir = __DIR__ . '/../../assets/contracts/template/' . $type . '/';

// Create directory if it doesn't exist
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        echo json_encode(['success' => false, 'message' => 'Impossible de créer le répertoire']);
        exit;
    }
}

// Use original filename
$originalFilename = $_FILES['template']['name'];
$destination = $uploadDir . $originalFilename;

// Delete all existing templates in this type folder (since only one template per type)
$files = scandir($uploadDir);
foreach ($files as $file) {
    if (pathinfo($file, PATHINFO_EXTENSION) === 'pdf') {
        @unlink($uploadDir . $file);
    }
}

// Move uploaded file
if (move_uploaded_file($_FILES['template']['tmp_name'], $destination)) {
    echo json_encode([
        'success' => true,
        'message' => 'Template téléversé avec succès',
        'filename' => $originalFilename
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Erreur lors du téléversement']);
}
?>