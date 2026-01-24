<?php
/**
 * generate_pdf.php
 * Generates contract PDF for preview or signed version
 */
// Start output buffering before anything else
ob_start();

session_start();
include("../../Data.php");
include("../../Warehouse.php");
require_once('ContractManager.php');

// Get student_id and year from session (set by Contract.php)
if (!isset($_SESSION['contract_student_id']) || !isset($_SESSION['contract_syear'])) {
    ob_end_clean();
    header('Content-Type: text/html');
    echo '<h2>Erreur</h2>';
    echo '<p>Session expirée. Veuillez recharger la page du contrat.</p>';
    exit;
}

$student_id = $_SESSION['contract_student_id'];
$syear = $_SESSION['contract_syear'];

// Get contract ID from session (must be set by Contract.php)
if (!isset($_SESSION['contract_id']) || empty($_SESSION['contract_id'])) {
    ob_end_clean();
    header('Content-Type: text/html');
    echo '<h2>Erreur</h2>';
    echo '<p>ID de contrat manquant. Veuillez recharger la page.</p>';
    exit;
}

$contractId = $_SESSION['contract_id'];

// Get template path from session (set by Contract.php)
if (!isset($_SESSION['contract_template_path']) || !file_exists($_SESSION['contract_template_path'])) {
    ob_end_clean();
    header('Content-Type: text/html');
    echo '<h2>Erreur</h2>';
    echo '<p>Modèle de contrat introuvable. Veuillez recharger la page.</p>';
    exit;
}

$templatePath = $_SESSION['contract_template_path'];

// Get student name for display filename
$stuRET = DBGet(DBQuery('SELECT CONCAT(FIRST_NAME, \' \', LAST_NAME) AS FULL_NAME FROM students WHERE STUDENT_ID = ' . $student_id));
$studentName = $stuRET[1]['FULL_NAME'] ?? 'Unknown';
$display_name = $studentName . '-'  . ($syear+1) . '-' . ($syear+2);

try {
    // Check if already signed - verify contract ID matches
    if (isset($_SESSION['contract_signed']) && $_SESSION['contract_signed'] === true) {
        $signedPath = $_SESSION['signed_contract_path'] ?? null;
        
        // Verify the signed file matches the current contract ID
        if ($signedPath && file_exists($signedPath) && strpos($signedPath, $contractId) !== false) {
            // Clear output buffer
            ob_end_clean();
            
            // Send signed PDF
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="'.$display_name.'.pdf"');
            header('Content-Length: ' . filesize($signedPath));
            readfile($signedPath);
            exit;
        }
    }
    
    // Get client data from database
    $addrRET = DBGet(DBQuery('SELECT STREET_ADDRESS_1, CITY, STATE, ZIPCODE FROM student_address WHERE STUDENT_ID = ' . $student_id));
    $parentRET = DBGet(DBQuery('SELECT CONCAT(FIRST_NAME, \' \', LAST_NAME) AS FULL_NAME FROM people WHERE STAFF_ID = ' . UserID()));
    
    // Prepare client data
    $clientData = [
        'nom_client' => $parentRET[1]['FULL_NAME'] ?? 'Parent/Tuteur',
        'nom_eleve' => $studentName,
        'adresse' => ($addrRET[1]['STREET_ADDRESS_1'] ?? '') . "\n" . 
                     ($addrRET[1]['CITY'] ?? '') . ", " . 
                     ($addrRET[1]['STATE'] ?? '') . " " . 
                     ($addrRET[1]['ZIPCODE'] ?? '')
    ];
    
    // Generate the PDF using template path from session
    $manager = new ContractManager($templatePath);
    $filledPDF = $manager->fillContract($clientData);
    
    // Save preview for later signing using the consistent contractId
    $previewFilename = "{$contractId}_preview.pdf";
    $previewPath = "../../assets/contracts/preview/" . $previewFilename;
    file_put_contents($previewPath, $filledPDF);
    
    // Store path in session
    $_SESSION['preview_contract_path'] = $previewPath;
    
    // Clear ALL output buffers before sending headers
    ob_end_clean();
    
    // Send PDF to browser
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="contrat_preview.pdf"');
    header('Content-Length: ' . strlen($filledPDF));
    echo $filledPDF;
    
} catch (Exception $e) {
    // Clear output buffer
    ob_end_clean();
    
    // Log error
    error_log("PDF Generation Error: " . $e->getMessage());
    
    // Show error to user
    header('Content-Type: text/html');
    echo '<h2>Erreur de génération du PDF</h2>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
}
exit;