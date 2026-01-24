<?php
/**
 * ContractManager.php
 * Production version - clean code without debugging
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
        $currentDate = date('Y-m-d');
        
        $pdf->SetXY(140, 113);
        $pdf->Write(0, $currentDate);
        
        $pdf->SetXY(140, 136);
        $pdf->Write(0, $currentDate);
    }
    
    /**
     * Add signature image to PDF
     */
    public function addSignatureToPDF($pdfContent, $signatureImageData) {
        require_once(__DIR__ . '/vendor/autoload.php');
        
        // Decode signature
        $signatureImage = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $signatureImageData));
        
        if ($signatureImage === false || empty($signatureImage)) {
            throw new Exception("Failed to decode signature image");
        }
        
        // Save signature to temp file
        $tempSigPath = sys_get_temp_dir() . '/signature_' . uniqid() . '.png';
        file_put_contents($tempSigPath, $signatureImage);
        
        // Verify it's a valid image
        $imageInfo = @getimagesize($tempSigPath);
        if ($imageInfo === false) {
            unlink($tempSigPath);
            throw new Exception("Invalid signature image format");
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
                $pdf->Image($tempSigPath, 30, 105, 50, 20, 'PNG');
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