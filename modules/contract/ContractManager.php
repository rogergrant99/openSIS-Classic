<?php
/**
 * ContractManager.php
 * Production version - clean code without debugging
 * Added support for test signature text in preview mode
 * Added support for multiple signature positions
 */

class ContractManager {
    private $templatePath;
    
    public function __construct($templatePath) {
        if (!file_exists($templatePath)) {
            throw new Exception("Template PDF not found: $templatePath");
        }
        
        $this->templatePath = $templatePath;
    }
    
    /**
     * Fill contract with client data
     */
    public function fillContract($clientData) {
        require_once(__DIR__ . '/vendor/autoload.php');
        
        // Create PDF instance
        $pdf = new \setasign\Fpdi\Tcpdf\Fpdi();
        
        // Configure PDF
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetAutoPageBreak(false, 0);
        
        // Load template
        $pageCount = $pdf->setSourceFile($this->templatePath);
        
        if ($pageCount == 0) {
            throw new Exception("Template has 0 pages");
        }
        
        // Process each page
        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            // Import page
            $templateId = $pdf->importPage($pageNo);
            
            // Get size
            $size = $pdf->getTemplateSize($templateId);
            
            // Add page
            $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
            $pdf->AddPage($orientation, [$size['width'], $size['height']]);
            
            // Use template
            $pdf->useTemplate($templateId);
            
            // Fill page 1
            if ($pageNo == 1) {
                $this->fillFirstPage($pdf, $clientData, $size);
            }
            
            // Fill page 5
            if ($pageNo == 5) {
                $this->addSignatureFields($pdf, $clientData);
            }
        }
        
        // Generate output via temporary file
        $tempFile = sys_get_temp_dir() . '/contract_' . uniqid() . '.pdf';
        $pdf->Output($tempFile, 'F');
        
        if (!file_exists($tempFile) || filesize($tempFile) == 0) {
            throw new Exception("Failed to generate PDF output");
        }
        
        $output = file_get_contents($tempFile);
        unlink($tempFile);
        
        return $output;
    }
    
    private function fillFirstPage($pdf, $data, $size) {
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetTextColor(0, 0, 0);
        $pageWidth = $size['width'];

        if (isset($data['nom_client'])) {
            $pdf->SetFont('helvetica', '', 14);
            $pdf->SetXY(0, 77.5);
            $pdf->Cell($pageWidth, 10, $data['nom_client'], 0, 0, 'C');
        }

        if (isset($data['nom_eleve'])) {
            $pdf->SetFont('helvetica', '', 14);
            $pdf->SetXY(0, 90);
            $pdf->Cell($pageWidth, 10, $data['nom_eleve'], 0, 0, 'C');
        }

        if (isset($data['adresse'])) {
            $pdf->SetFont('helvetica', '', 14);
            $pdf->SetXY(0, 108);
            $pdf->MultiCell($pageWidth, 5, $data['adresse'], 0, 'C');
        }
    }

    private function addSignatureFields($pdf, $data) {
        $pdf->SetFont('helvetica', '', 14);
        $pdf->SetTextColor(0, 0, 0); // Reset to black
        $currentDate = dateFr('d M Y');
        
        $pdf->SetXY(140, 114);
        $pdf->Write(0, $currentDate);
        
        $pdf->SetXY(140, 137);
        $pdf->Write(0, $currentDate);
        
        // Add test signature text if provided (for preview mode)
        if (isset($data['signature']) && !empty($data['signature'])) {
            $pdf->SetFont('helvetica', 'I', 16); // Italic font, slightly larger for signature
            $pdf->SetTextColor(0, 0, 255); // Blue color to clearly indicate it's test data
            $pdf->SetXY(35, 114); // Position where signature image would normally appear
            $pdf->Write(0, $data['signature']);
            
            // Reset text color back to black
            $pdf->SetTextColor(0, 0, 0);
        }
    }
    
    /**
     * Add signature image to PDF with dynamic width calculation
     * @param string $pdfContent - The PDF content to add signature to
     * @param string $signatureImageData - Base64 encoded signature image
     * @param int $xPosition - X coordinate for signature (default: 8)
     * @param int $yPosition - Y coordinate for signature (default: 108)
     */
    public function addSignatureToPDF($pdfContent, $signatureImageData, $xPosition = 8, $yPosition = 108) {
        require_once(__DIR__ . '/vendor/autoload.php');
        
        // Decode signature
        $signatureImage = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $signatureImageData));
        
        if ($signatureImage === false || empty($signatureImage)) {
            throw new Exception("Failed to decode signature image");
        }
        
        // Save signature to temp file
        $tempSigPath = sys_get_temp_dir() . '/signature_' . uniqid() . '.png';
        file_put_contents($tempSigPath, $signatureImage);
        
        // Verify it's a valid image and get dimensions
        $imageInfo = @getimagesize($tempSigPath);
        if ($imageInfo === false) {
            unlink($tempSigPath);
            throw new Exception("Invalid signature image format");
        }
        
        // Get actual image dimensions
        $imageWidth = $imageInfo[0];
        $imageHeight = $imageInfo[1];
        
        // Calculate aspect ratio
        $aspectRatio = $imageWidth / $imageHeight;
        
        // Set desired height in PDF (mm) - keep this consistent
        $pdfHeight = 20;
        
        // Calculate width to maintain aspect ratio
        $pdfWidth = $pdfHeight * $aspectRatio;
        
        // Optional: Set maximum width to prevent oversized signatures
        $maxWidth = 100; // Maximum width in mm
        if ($pdfWidth > $maxWidth) {
            $pdfWidth = $maxWidth;
            $pdfHeight = $pdfWidth / $aspectRatio;
        }
        
        // Create new PDF instance
        $pdf = new \setasign\Fpdi\Tcpdf\Fpdi();
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetAutoPageBreak(false, 0);
        
        // Write PDF content to temp file
        $tempPdfPath = sys_get_temp_dir() . '/temp_pdf_' . uniqid() . '.pdf';
        file_put_contents($tempPdfPath, $pdfContent);
        
        // Load the existing PDF
        $pageCount = $pdf->setSourceFile($tempPdfPath);
        
        // Import all pages
        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($templateId);
            
            $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
            $pdf->AddPage($orientation, [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);
            
            // Add signature on page 5
            if ($pageNo == 5) {
                // Add signature with calculated dimensions at specified position
                $pdf->Image($tempSigPath, $xPosition, $yPosition, $pdfWidth, $pdfHeight, 'PNG');
            }
        }
        
        // Generate output via temporary file
        $tempOutputPath = sys_get_temp_dir() . '/signed_' . uniqid() . '.pdf';
        $pdf->Output($tempOutputPath, 'F');
        
        if (!file_exists($tempOutputPath) || filesize($tempOutputPath) == 0) {
            throw new Exception("Failed to generate signed PDF output");
        }
        
        $output = file_get_contents($tempOutputPath);
        unlink($tempOutputPath);
        
        // Clean up temp files
        unlink($tempSigPath);
        unlink($tempPdfPath);
        
        return $output;
    }

    public function savePDF($pdfContent, $outputPath) {
        $dir = dirname($outputPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        return file_put_contents($outputPath, $pdfContent);
    }
}

function dateFr($format, $timestamp = null) {
    // Use current time if no timestamp provided
    if ($timestamp === null) {
        $timestamp = time();
    }
    
    // French month names
    $months = [
        1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril',
        5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'août',
        9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre'
    ];
    
    // French abbreviated month names
    $monthsShort = [
        1 => 'janv', 2 => 'févr', 3 => 'mars', 4 => 'avr',
        5 => 'mai', 6 => 'juin', 7 => 'juil', 8 => 'août',
        9 => 'sept', 10 => 'oct', 11 => 'nov', 12 => 'déc'
    ];
    
    // French day names
    $days = [
        0 => 'dimanche', 1 => 'lundi', 2 => 'mardi', 3 => 'mercredi',
        4 => 'jeudi', 5 => 'vendredi', 6 => 'samedi'
    ];
    
    // French abbreviated day names
    $daysShort = [
        0 => 'dim', 1 => 'lun', 2 => 'mar', 3 => 'mer',
        4 => 'jeu', 5 => 'ven', 6 => 'sam'
    ];
    
    // Get the formatted date using regular date() function
    $result = date($format, $timestamp);
    
    // Replace English names with French ones
    $result = str_replace([
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ], [
        'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
        'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'
    ], $result);
    
    $result = str_replace([
        'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
        'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
    ], [
        'janv', 'févr', 'mars', 'avr', 'mai', 'juin',
        'juil', 'août', 'sept', 'oct', 'nov', 'déc'
    ], $result);
    
    $result = str_replace([
        'Sunday', 'Monday', 'Tuesday', 'Wednesday',
        'Thursday', 'Friday', 'Saturday'
    ], [
        'dimanche', 'lundi', 'mardi', 'mercredi',
        'jeudi', 'vendredi', 'samedi'
    ], $result);
    
    $result = str_replace([
        'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'
    ], [
        'dim', 'lun', 'mar', 'mer', 'jeu', 'ven', 'sam'
    ], $result);
    
    return $result;
}