<?php
/**
 * admin_delete.php
 * Handle deletion of templates and signed contracts
 */
header('Content-Type: application/json');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['type']) || !isset($input['filename'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Paramètres manquants'
    ]);
    exit;
}

$type = $input['type'];
$filename = basename($input['filename']); // Sanitize filename

// Determine directory based on type
if ($type === 'template') {
    $directory = __DIR__ . '/../../assets/contracts/template/';
} elseif ($type === 'signed') {
    $directory = __DIR__ . '/../../assets/contracts/signed/';
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Type de fichier invalide'
    ]);
    exit;
}

$filePath = $directory . $filename;

// Check if file exists
if (!file_exists($filePath)) {
    echo json_encode([
        'success' => false,
        'message' => 'Fichier introuvable'
    ]);
    exit;
}

// Ensure it's a PDF file
if (pathinfo($filePath, PATHINFO_EXTENSION) !== 'pdf') {
    echo json_encode([
        'success' => false,
        'message' => 'Type de fichier non autorisé'
    ]);
    exit;
}

// Delete the file
if (unlink($filePath)) {
    // If deleting a signed contract, also clean up session data if it matches
    if ($type === 'signed') {
        session_start();
        if (isset($_SESSION['signed_contract_path']) && $_SESSION['signed_contract_path'] === $filePath) {
            unset($_SESSION['contract_signed']);
            unset($_SESSION['signed_contract_path']);
            unset($_SESSION['signature_date']);
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Fichier supprimé avec succès'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la suppression du fichier'
    ]);
}