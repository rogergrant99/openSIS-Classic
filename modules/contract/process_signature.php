<?php
/**
 * process_signature.php
 * Processes contract signature submission
 */
// Must be first line
ob_start();
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
try {
    // Include ContractManager
    require_once(__DIR__ . '/ContractManager.php');
    
    // Validate inputs
    if (!isset($_POST['signature']) || empty($_POST['signature'])) {
        throw new Exception('Signature is required');
    }
    if (!isset($_POST['contract_id']) || empty($_POST['contract_id'])) {
        throw new Exception('Contract ID is required');
    }
    
    $contractId = $_POST['contract_id'];
    $signatureData = $_POST['signature'];
    
    // Validate signature format
    if (!preg_match('/^data:image\/png;base64,/', $signatureData)) {
        throw new Exception('Invalid signature format');
    }
    
    // Find preview PDF
    $previewPath = $_SESSION['preview_contract_path'] ?? null;
    if (!$previewPath || !file_exists($previewPath)) {
        // Try to find by contract ID in preview directory
        $previewPath = __DIR__ . "/../../assets/contracts/preview/{$contractId}_preview.pdf";
    }
    
    if (!file_exists($previewPath)) {
        throw new Exception('Preview PDF not found. Please refresh the page.');
    }
    
    // Read preview PDF content
    $previewContent = file_get_contents($previewPath);
    $previewSize = strlen($previewContent);
    if ($previewSize < 100) {
        throw new Exception("Preview PDF is corrupted");
    }
    
    // Get template path from session (set in Contract.php)
    $templatePath = $_SESSION['contract_template_path'] ?? null;
    if (!$templatePath || !file_exists($templatePath)) {
        throw new Exception('Template not found in session');
    }
    
    // Determine grade level from template path
    $gradeLevel = 'unknown';
    if (strpos($templatePath, '/primaire/') !== false) {
        $gradeLevel = 'Primaire';
    } elseif (strpos($templatePath, '/secondaire/') !== false) {
        $gradeLevel = 'Secondaire';
    }
    
    // Create manager and add signature
    $manager = new ContractManager($templatePath);
    
    // Add signature to PDF
    ob_start();
    $signedContent = $manager->addSignatureToPDF($previewContent, $signatureData);
    $captured = ob_get_clean();
    $signedSize = strlen($signedContent);
    if ($signedSize < 100) {
        throw new Exception("Failed to generate signed PDF - output is empty");
    }
    
    // Get signed directory from session (set in Contract.php)
    $signedDir = $_SESSION['signed_contract_dir'] ?? null;
    if (!$signedDir) {
        throw new Exception('Signed contract directory not found in session');
    }
    
    // Create directory if it doesn't exist
    if (!is_dir($signedDir)) {
        mkdir($signedDir, 0755, true);
    }
    
    // Save signed PDF with grade level in filename
    $timestamp = date('Y-m-d_His');
    $signedFilename = "{$contractId}-{$gradeLevel}.pdf";
    $signedPath = $signedDir . $signedFilename;
    $written = file_put_contents($signedPath, $signedContent);
    if ($written === false || $written === 0) {
        throw new Exception('Failed to save signed PDF');
    }
    
    // Verify file
    if (!file_exists($signedPath) || filesize($signedPath) < 100) {
        throw new Exception('Signed PDF verification failed');
    }
    
    // Update session
    $_SESSION['contract_signed'] = true;
    $_SESSION['signed_contract_path'] = $signedPath;
    $_SESSION['signature_date'] = date('Y-m-d H:i:s');
    
    // Clean up preview
    if (file_exists($previewPath)) {
        unlink($previewPath);
    }
    unset($_SESSION['preview_contract_path']);
    
    // Clear ALL buffers
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    // Send response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Contrat signé avec succès',
        'contract_path' => $signedPath,
        'file_size' => filesize($signedPath),
        'redirect' => 'sign_contract.php'
    ]);
} catch (Exception $e) {
    // Clear buffers
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
exit;