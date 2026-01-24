<?php
/**
 * admin_handler.php
 * Handler for contract viewing, downloading, and deletion
 * Now includes preview with test data overlay
 */
session_start();

// Check if user has admin privileges
// Uncomment and modify based on your permission system
// if (!User('PROFILE') == 'admin') {
//     die('Access denied');
// }

$action = isset($_GET['action']) ? $_GET['action'] : '';

// Handle view action with optional preview
if ($action === 'view') {
    $type = isset($_GET['type']) ? $_GET['type'] : '';
    $file = isset($_GET['file']) ? $_GET['file'] : '';
    $subtype = isset($_GET['subtype']) ? $_GET['subtype'] : ''; // primaire or secondaire
    $preview = isset($_GET['preview']) && $_GET['preview'] === 'true'; // New preview parameter
    
    if (!in_array($type, ['template', 'signed']) || empty($file)) {
        die('Invalid parameters');
    }
    
    // Sanitize filename
    $file = basename($file);
    
    // Set directory based on type
    if ($type === 'template') {
        if (!in_array($subtype, ['primaire', 'secondaire'])) {
            die('Invalid template subtype');
        }
        $dir = __DIR__ . '/../../assets/contracts/template/' . $subtype . '/';
    } else {
        $dir = __DIR__ . '/../../assets/contracts/signed/';
    }
    
    $filepath = $dir . $file;
    
    if (!file_exists($filepath) || pathinfo($filepath, PATHINFO_EXTENSION) !== 'pdf') {
        die('File not found');
    }
    
    // If preview mode is enabled for templates, use ContractManager
    if ($preview && $type === 'template') {
        try {
            require_once(__DIR__ . '/ContractManager.php');
            
            // Create test data based on template type
            $testData = [
                'nom_client' => 'Papa Client',
                'nom_eleve' => 'Enfant Client',
                'adresse' => "123 Rue Example\nVille, Province, H0H 0H0"
            ];
            
            // Create ContractManager instance
            $contractManager = new ContractManager($filepath);
            
            // Fill contract with test data
            $pdfContent = $contractManager->fillContract($testData);
            
            // Output the preview PDF
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="preview_' . $file . '"');
            header('Content-Length: ' . strlen($pdfContent));
            echo $pdfContent;
            exit;
            
        } catch (Exception $e) {
            // If preview fails, fall back to showing original template
            error_log("Preview generation failed: " . $e->getMessage());
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $file . '"');
            header('Content-Length: ' . filesize($filepath));
            readfile($filepath);
            exit;
        }
    }
    
    // Normal view mode - show original PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $file . '"');
    header('Content-Length: ' . filesize($filepath));
    readfile($filepath);
    exit;
}

// Handle download action
if ($action === 'download') {
    $file = isset($_GET['file']) ? $_GET['file'] : '';
    
    if (empty($file)) {
        die('Invalid parameters');
    }
    
    // Sanitize filename
    $file = basename($file);
    $dir = __DIR__ . '/../../assets/contracts/signed/';
    $filepath = $dir . $file;
    
    if (!file_exists($filepath) || pathinfo($filepath, PATHINFO_EXTENSION) !== 'pdf') {
        die('File not found');
    }
    
    // Output PDF for download
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $file . '"');
    header('Content-Length: ' . filesize($filepath));
    readfile($filepath);
    exit;
}

// Handle delete action
if ($action === 'delete') {
    header('Content-Type: application/json');
    
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    $type = isset($input['type']) ? $input['type'] : '';
    $filename = isset($input['filename']) ? $input['filename'] : '';
    $subtype = isset($input['subtype']) ? $input['subtype'] : ''; // primaire or secondaire
    
    if (!in_array($type, ['template', 'signed']) || empty($filename)) {
        echo json_encode(['success' => false, 'message' => 'Paramètres invalides']);
        exit;
    }
    
    // Sanitize filename
    $filename = basename($filename);
    
    // Set directory based on type
    if ($type === 'template') {
        if (!in_array($subtype, ['primaire', 'secondaire'])) {
            echo json_encode(['success' => false, 'message' => 'Type de template invalide']);
            exit;
        }
        $dir = __DIR__ . '/../../assets/contracts/template/' . $subtype . '/';
    } else {
        $dir = __DIR__ . '/../../assets/contracts/signed/';
    }
    
    $filepath = $dir . $filename;
    
    if (!file_exists($filepath) || pathinfo($filepath, PATHINFO_EXTENSION) !== 'pdf') {
        echo json_encode(['success' => false, 'message' => 'Fichier non trouvé']);
        exit;
    }
    
    // Delete file
    if (unlink($filepath)) {
        echo json_encode(['success' => true, 'message' => 'Fichier supprimé avec succès']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression']);
    }
    exit;
}

// Invalid action
die('Invalid action');
?>