<?php
/**
 * Contract.php
 * Contract signing interface
 */
include("../../Data.php");
include("../../Warehouse.php");
session_start();

$student_id = UserStudentID();
$syear = UserSyear();

// Store in session for use by other files
$_SESSION['contract_student_id'] = $student_id;
$_SESSION['contract_syear'] = $syear;


// Get client data from database
$stuRET = DBGet(DBQuery('SELECT CONCAT(FIRST_NAME, \' \', LAST_NAME) AS FULL_NAME FROM students WHERE STUDENT_ID = ' . $student_id));
$addrRET = DBGet(DBQuery('SELECT STREET_ADDRESS_1, CITY, STATE, ZIPCODE FROM student_address WHERE STUDENT_ID = ' . $student_id));
$parentRET = DBGet(DBQuery('SELECT CONCAT(FIRST_NAME, \' \', LAST_NAME) AS FULL_NAME FROM people WHERE STAFF_ID = ' . UserID()));

$clientData = [
    'nom_client' => $parentRET[1]['FULL_NAME'],
    'nom_eleve' => $stuRET[1]['FULL_NAME'],
    'adresse' => ' '. $addrRET[1]['STREET_ADDRESS_1'] . "\n" . $addrRET[1]['CITY']. ", " . $addrRET[1]['STATE'] . " " . $addrRET[1]['ZIPCODE'] . ''
];

// Generate contract ID from student ID + year
$contractId = $student_id . '_' . $stuRET[1]['FULL_NAME'] . '-'  . ($syear+1) . '-' . ($syear+2) ;

// Check if contract ID has changed (student switched)
if (isset($_SESSION['contract_id']) && $_SESSION['contract_id'] !== $contractId) {
    // Different student - clear all contract session data
    unset($_SESSION['contract_id']);
    unset($_SESSION['contract_signed']);
    unset($_SESSION['signed_contract_path']);
    unset($_SESSION['preview_contract_path']);
    unset($_SESSION['signature_date']);
}

// Set the current contract ID
$_SESSION['contract_id'] = $contractId;

// Store client data in session for generate_pdf.php
$_SESSION['contract_client_data'] = $clientData;

// Get next grade level
$grade_levelRET = DBGet(DBQuery("SELECT s.student_id,s.first_name,s.last_name,g.title AS current_grade,g.id AS current_id,ng.title AS next_grade,g.next_grade_id as next_grade_id FROM student_enrollment se JOIN students s ON se.student_id=s.student_id JOIN school_gradelevels g ON se.grade_id=g.id LEFT JOIN school_gradelevels ng ON g.next_grade_id=ng.id WHERE se.student_id= '" . $student_id . "' AND se.end_date IS NULL ORDER BY se.start_date DESC LIMIT 1;"));

// Determine contract template based on next grade level
$next_grade_id = $grade_levelRET[1]['NEXT_GRADE_ID'] ?? null;

// Set template path based on next grade level
// Primary: grades 1-7 (pré through Pr 6)
// Secondary: grades 8-12 (Sec1 through Sec5)
// No contract if no next grade level
if ($next_grade_id === null) {
    // No next grade - student is graduating (e.g., Sec5)
    // Don't show contract interface
    $_SESSION['contract_template_path'] = null;
} elseif ($next_grade_id >= 8) {
    // Secondary - find any PDF in the secondaire directory
    $templateDir = __DIR__ . '/../../assets/contracts/template/secondaire/';
    $pdfFiles = glob($templateDir . '*.pdf');
    $_SESSION['contract_template_path'] = !empty($pdfFiles) ? $pdfFiles[0] : null;
} else {
    // Primary (grades 1-7: pré through Pr 6) - find any PDF in the primaire directory
    $templateDir = __DIR__ . '/../../assets/contracts/template/primaire/';
    $pdfFiles = glob($templateDir . '*.pdf');
    $_SESSION['contract_template_path'] = !empty($pdfFiles) ? $pdfFiles[0] : null;
}


// Check if contract is signed - ALWAYS check filesystem first, then update session
$isContractSigned = false;
$signedDir = __DIR__ . '/../../assets/contracts/signed/';

// Look for any signed contract file that matches this contract ID pattern
if ($signedDir !== null) {
    // Create directory if it doesn't exist
    if (!is_dir($signedDir)) {
        mkdir($signedDir, 0755, true);
    }
    
    $signedPattern = $signedDir . $contractId . '*.pdf';
    $matchingFiles = glob($signedPattern);

    if (!empty($matchingFiles)) {
        // Found a signed contract file - use the most recent one
        $signedPath = end($matchingFiles); // Get last (most recent) file
        
        if (file_exists($signedPath) && filesize($signedPath) > 100) {
            $isContractSigned = true;
            
            // Update session with correct information
            $_SESSION['contract_signed'] = true;
            $_SESSION['signed_contract_path'] = $signedPath;
            $_SESSION['signed_contract_dir'] = $signedDir; // Store directory for use by other files
            
            // Get file modification time as signature date if not set
            if (!isset($_SESSION['signature_date'])) {
                $_SESSION['signature_date'] = date('Y-m-d H:i:s', filemtime($signedPath));
            }
        }
    } else {
        // No file found - ensure session reflects this
        $_SESSION['contract_signed'] = false;
        $_SESSION['signed_contract_dir'] = $signedDir; // Store directory even if not signed yet
        unset($_SESSION['signed_contract_path']);
    }
} else {
    // No signed directory needed (graduating student)
    $_SESSION['contract_signed'] = false;
    unset($_SESSION['signed_contract_path']);
    unset($_SESSION['signed_contract_dir']);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signature du contrat - École CADO</title>
    <!-- Google Fonts for Signature -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@400;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        .header {
            color: black;
            padding: 0px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 0px;
        }
        
        .content {
            padding: 10px;
        }
        
        .pdf-preview {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 30px;
            background: #f9f9f9;
            text-align: center;
        }
        
        .pdf-preview iframe {
            width: 100%;
            height: 800px;
            border: none;
            border-radius: 5px;
            background: white;
        }
        
        .signature-section {
            margin-top: 30px;
        }
        
        .signature-section h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 22px;
        }
        
        .signature-mode-toggle {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            background: #f0f0f0;
            padding: 5px;
            border-radius: 8px;
        }
        
        .mode-btn {
            flex: 1;
            padding: 10px 20px;
            background: white;
            border: 2px solid #ddd;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
        }
        
        .mode-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: #667eea;
        }
        
        .signature-pad-container {
            border: 3px solid #667eea;
            border-radius: 10px;
            background: white;
            padding: 10px;
            margin-bottom: 15px;
        }
        
        #signature-pad {
            width: 100%;
            height: 200px;
            border: 1px dashed #ccc;
            border-radius: 5px;
            cursor: crosshair;
            touch-action: none;
            display: block;
        }
        
        #signature-pad.hidden {
            display: none;
        }
        
        .typed-signature-container {
            display: none;
            width: 100%;
            min-height: 200px;
            border: 1px dashed #ccc;
            border-radius: 5px;
            padding: 20px;
            text-align: center;
            background: white;
        }
        
        .typed-signature-container.active {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        
        #typed-signature-input {
            font-family: 'Caveat', cursive;
            font-size: 52px;
            border: none;
            border-bottom: 2px solid #667eea;
            text-align: center;
            padding: 10px;
            width: 80%;
            max-width: 500px;
            outline: none;
            background: transparent;
        }
        
        .button-group {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        button {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-clear {
            background: #f44336;
            color: white;
        }
        
        .btn-clear:hover {
            background: #d32f2f;
            transform: translateY(-2px);
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            flex: 1;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-submit:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }
        
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 5px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .info-box p {
            color: #1976d2;
            line-height: 1.6;
            margin: 0;
        }
        
        .status-message {
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            display: none;
        }
        
        .status-message.success {
            background: #4caf50;
            color: white;
            display: block;
        }
        
        .status-message.error {
            background: #f44336;
            color: white;
            display: block;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        @media (max-width: 768px) {
            .content {
                padding: 20px;
            }
            
            .header h1 {
                font-size: 22px;
            }
            
            .pdf-preview iframe {
                height: 400px;
            }
            
            #typed-signature-input {
                font-size: 36px;
            }
            
            #typed-signature-preview {
                font-size: 36px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1> Signature du contrat de réinscription</h1>
        </div>
        
        <div class="content">
            <?php if ($isContractSigned): ?>
            <div class="info-box" style="background: #e8f5e9; border-left-color: #4caf50;">
                <p><strong>✓ Contrat signé:</strong> Ce contrat a déjà été signé avec succès.</p>
                <p style="margin-top: 5px;"><small>Contrat ID: <?php echo htmlspecialchars($contractId); ?></small></p>
                <?php if (isset($_SESSION['signature_date'])): ?>
                    <p style="margin-top: 5px;"><small>Signé le: <?php echo htmlspecialchars($_SESSION['signature_date']); ?></small></p>
                <?php endif; ?>
                <?php if (isset($_SESSION['signed_contract_path'])): ?>
                    <p style="margin-top: 10px;">
                        <a href="../../modules/contract/generate_pdf.php" 
                        download
                        style="color: #2e7d32; text-decoration: underline;">
                            📥 Télécharger le contrat signé
                        </a>
                    </p>
                <?php endif; ?>
            </div>
            <?php elseif ($next_grade_id === null || !isset($_SESSION['contract_template_path']) || $_SESSION['contract_template_path'] === null): ?>
            <div class="info-box" style="background: #fff3e0; border-left-color: #ff9800;">
                <p><strong>ℹ️ Aucun contrat disponible:</strong> 
                <?php if ($next_grade_id === null): ?>
                    Cet élève termine son parcours scolaire et n'a pas de niveau suivant.
                <?php else: ?>
                    Aucun modèle de contrat n'est disponible pour le moment.
                <?php endif; ?>
                </p>
                <?php if (isset($grade_levelRET[1]['CURRENT_GRADE'])): ?>
                <p style="margin-top: 5px;"><small>Niveau actuel: <?php echo htmlspecialchars($grade_levelRET[1]['CURRENT_GRADE']); ?></small></p>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="info-box">
                <p><strong>ℹ️ Information:</strong> Veuillez examiner le contrat ci-dessous, puis signer dans la zone prévue à cet effet.</p>
            </div>
            
            <div class="pdf-preview">
                <iframe src="modules/contract/generate_pdf.php" id="pdf-viewer">
                    <div class="loading">Chargement du PDF...</div>
                </iframe>
            </div>
            
            <form id="signature-form" method="POST" action="modules/contract/process_signature.php">
                <div class="signature-section">
                    <h2>✍️ Signature du parent / tuteur</h2>
                    
                    <div class="signature-mode-toggle">
                        <button type="button" class="mode-btn active" id="draw-mode-btn">
                            ✏️ Dessiner la signature
                        </button>
                        <button type="button" class="mode-btn" id="type-mode-btn">
                            ⌨️ Taper la signature
                        </button>
                    </div>
                    
                    <div class="info-box">
                        <p><strong>Instructions:</strong> <span id="mode-instructions">Utilisez votre souris ou votre doigt pour signer dans la zone ci-dessous.</span></p>
                    </div>
                    
                    <div class="signature-pad-container">
                        <canvas id="signature-pad"></canvas>
                        <div class="typed-signature-container" id="typed-signature-container">
                            <input type="text" 
                                   id="typed-signature-input" 
                                   placeholder="Tapez votre nom ici..."
                                   maxlength="50">
                        </div>
                    </div>
                    
                    <div class="button-group">
                        <button type="button" class="btn-clear" id="clear-btn">
                            🗑️ Effacer
                        </button>
                        <button type="submit" class="btn-submit" id="submit-btn" disabled>
                            ✓ Signer et soumettre le contrat
                        </button>
                    </div>
                </div>
                
                <input type="hidden" name="signature" id="signature-data">
                <input type="hidden" name="signature_mode" id="signature-mode" value="draw">
                <input type="hidden" name="contract_id" value="<?php echo htmlspecialchars($contractId); ?>">
            </form>
            <?php endif; ?>
            
            <div class="status-message" id="status-message"></div>
        </div>
    </div>

    <script>
        <?php if (!$isContractSigned && isset($_SESSION['contract_template_path']) && $_SESSION['contract_template_path'] !== null): ?>
        // Signature Pad Implementation
        class SignaturePad {
            constructor(canvas) {
                this.canvas = canvas;
                this.ctx = canvas.getContext('2d');
                this.isDrawing = false;
                this.hasSignature = false;
                
                this.resizeCanvas();
                this.setupEventListeners();
            }
            
            resizeCanvas() {
                const ratio = Math.max(window.devicePixelRatio || 1, 1);
                this.canvas.width = this.canvas.offsetWidth * ratio;
                this.canvas.height = this.canvas.offsetHeight * ratio;
                this.canvas.getContext('2d').scale(ratio, ratio);
                this.ctx.strokeStyle = '#000';
                this.ctx.lineWidth = 4;
                this.ctx.lineCap = 'round';
            }
            
            setupEventListeners() {
                this.canvas.addEventListener('mousedown', (e) => this.startDrawing(e));
                this.canvas.addEventListener('mousemove', (e) => this.draw(e));
                this.canvas.addEventListener('mouseup', () => this.stopDrawing());
                this.canvas.addEventListener('mouseout', () => this.stopDrawing());
                
                this.canvas.addEventListener('touchstart', (e) => {
                    e.preventDefault();
                    this.startDrawing(e.touches[0]);
                });
                this.canvas.addEventListener('touchmove', (e) => {
                    e.preventDefault();
                    this.draw(e.touches[0]);
                });
                this.canvas.addEventListener('touchend', () => this.stopDrawing());
            }
            
            getCoordinates(e) {
                const rect = this.canvas.getBoundingClientRect();
                return {
                    x: e.clientX - rect.left,
                    y: e.clientY - rect.top
                };
            }
            
            startDrawing(e) {
                this.isDrawing = true;
                const coords = this.getCoordinates(e);
                this.ctx.beginPath();
                this.ctx.moveTo(coords.x, coords.y);
            }
            
            draw(e) {
                if (!this.isDrawing) return;
                
                const coords = this.getCoordinates(e);
                this.ctx.lineTo(coords.x, coords.y);
                this.ctx.stroke();
                this.hasSignature = true;
                
                document.getElementById('submit-btn').disabled = false;
            }
            
            stopDrawing() {
                this.isDrawing = false;
            }
            
            clear() {
                this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
                this.hasSignature = false;
                document.getElementById('submit-btn').disabled = true;
            }
            
            toDataURL() {
                return this.canvas.toDataURL('image/png');
            }
        }
        
        // Initialize signature pad
        const canvas = document.getElementById('signature-pad');
        const signaturePad = new SignaturePad(canvas);
        
        // Typed signature functionality
        const typedInput = document.getElementById('typed-signature-input');
        const typedContainer = document.getElementById('typed-signature-container');
        const drawModeBtn = document.getElementById('draw-mode-btn');
        const typeModeBtn = document.getElementById('type-mode-btn');
        const modeInstructions = document.getElementById('mode-instructions');
        const signatureModeInput = document.getElementById('signature-mode');
        
        let currentMode = 'draw';
        
        // Mode switching
        drawModeBtn.addEventListener('click', () => {
            currentMode = 'draw';
            drawModeBtn.classList.add('active');
            typeModeBtn.classList.remove('active');
            canvas.classList.remove('hidden');
            typedContainer.classList.remove('active');
            modeInstructions.textContent = 'Utilisez votre souris ou votre doigt pour signer dans la zone ci-dessous.';
            signatureModeInput.value = 'draw';
            updateSubmitButton();
        });
        
        typeModeBtn.addEventListener('click', () => {
            currentMode = 'type';
            typeModeBtn.classList.add('active');
            drawModeBtn.classList.remove('active');
            canvas.classList.add('hidden');
            typedContainer.classList.add('active');
            modeInstructions.textContent = 'Tapez votre nom complet dans le champ ci-dessous.';
            signatureModeInput.value = 'type';
            typedInput.focus();
            updateSubmitButton();
        });
        
        // Typed signature input
        typedInput.addEventListener('input', () => {
            updateSubmitButton();
        });
        
        function updateSubmitButton() {
            const submitBtn = document.getElementById('submit-btn');
            if (currentMode === 'draw') {
                submitBtn.disabled = !signaturePad.hasSignature;
            } else {
                submitBtn.disabled = typedInput.value.trim().length === 0;
            }
        }
        
        // Convert typed signature to canvas for consistent handling
        function typedSignatureToCanvas() {
            const tempCanvas = document.createElement('canvas');
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            
            // Set canvas size to match signature pad (use actual pixel dimensions)
            tempCanvas.width = canvas.width;
            tempCanvas.height = canvas.height;
            
            const tempCtx = tempCanvas.getContext('2d');
            
            // Transparent background (no white fill)
            tempCtx.clearRect(0, 0, tempCanvas.width, tempCanvas.height);
            
            // Draw typed signature with Caveat font
            tempCtx.fillStyle = '#000000';
            tempCtx.font = (120 * ratio) + 'px "Caveat", cursive';
            tempCtx.textAlign = 'center';
            tempCtx.textBaseline = 'middle';
            tempCtx.fillText(typedInput.value, tempCanvas.width / 2, tempCanvas.height / 2);
            
            return tempCanvas.toDataURL('image/png');
        }
        
        // Clear button
        document.getElementById('clear-btn').addEventListener('click', () => {
            if (currentMode === 'draw') {
                signaturePad.clear();
            } else {
                typedInput.value = '';
                updateSubmitButton();
            }
        });
        
        // Form submission
        document.getElementById('signature-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            let signatureData;
            
            if (currentMode === 'draw') {
                if (!signaturePad.hasSignature) {
                    alert('Veuillez signer avant de soumettre.');
                    return;
                }
                signatureData = signaturePad.toDataURL();
            } else {
                if (typedInput.value.trim().length === 0) {
                    alert('Veuillez taper votre nom avant de soumettre.');
                    return;
                }
                signatureData = typedSignatureToCanvas();
            }
            
            document.getElementById('signature-data').value = signatureData;
            
            const formData = new FormData(e.target);
            const submitBtn = document.getElementById('submit-btn');
            submitBtn.disabled = true;
            submitBtn.textContent = '⏳ Traitement en cours...';
            
            try {
                const response = await fetch('../../modules/contract/process_signature.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                const statusMsg = document.getElementById('status-message');
                
                if (result.success) {
                    statusMsg.className = 'status-message success';
                    statusMsg.textContent = '✓ Contrat signé avec succès! Rechargement...';
                    setTimeout(() => {
                        check_content("Ajax.php?modname=contract/Contract.php");
                    }, 1500);
                } else {
                    statusMsg.className = 'status-message error';
                    statusMsg.textContent = '✗ Erreur: ' + result.message;
                    submitBtn.disabled = false;
                    submitBtn.textContent = '✓ Signer et soumettre le contrat';
                }
            } catch (error) {
                console.error('Error:', error);
                const statusMsg = document.getElementById('status-message');
                statusMsg.className = 'status-message error';
                statusMsg.textContent = '✗ Erreur de connexion. Veuillez réessayer.';
                submitBtn.disabled = false;
                submitBtn.textContent = '✓ Signer et soumettre le contrat';
            }
        });
        
        window.addEventListener('resize', () => {
            signaturePad.resizeCanvas();
        });
        <?php endif; ?>
    </script>
</body>
</html>