<?php
/**
 * ContractAdmin.php
 * Admin interface for contract management with preview functionality
 */
include("../../Data.php");
include("../../Warehouse.php");
session_start();

// Check if user has admin privileges
// Uncomment and modify based on your permission system
// if (!User('PROFILE') == 'admin') {
//     die('Access denied');
// }

// Get templates from subdirectories
$templatesDir = __DIR__ . '/../../assets/contracts/template/';
$primaireTemplate = null;
$secondaireTemplate = null;

// Check primaire folder
$primaireDir = $templatesDir . 'primaire/';
if (is_dir($primaireDir)) {
    $files = scandir($primaireDir);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'pdf') {
            $primaireTemplate = [
                'name' => $file,
                'path' => $primaireDir . $file,
                'size' => filesize($primaireDir . $file),
                'modified' => filemtime($primaireDir . $file)
            ];
            break; // Only one template per type
        }
    }
}

// Check secondaire folder
$secondaireDir = $templatesDir . 'secondaire/';
if (is_dir($secondaireDir)) {
    $files = scandir($secondaireDir);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'pdf') {
            $secondaireTemplate = [
                'name' => $file,
                'path' => $secondaireDir . $file,
                'size' => filesize($secondaireDir . $file),
                'modified' => filemtime($secondaireDir . $file)
            ];
            break; // Only one template per type
        }
    }
}

// Get all signed contracts
$signedDir = __DIR__ . '/../../assets/contracts/signed/';
$signedContracts = [];
if (is_dir($signedDir)) {
    $files = scandir($signedDir);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'pdf') {
            $signedContracts[] = [
                'name' => $file,
                'path' => $signedDir . $file,
                'size' => filesize($signedDir . $file),
                'modified' => filemtime($signedDir . $file)
            ];
        }
    }
    // Sort by most recent first
    usort($signedContracts, function($a, $b) {
        return $b['modified'] - $a['modified'];
    });
}

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration des contrats - École CADO</title>
    <style>
        .container {
            width: 100%;
            margin: 0 auto;
        }
        
        .header {
            background: linear-gradient(135deg, #2d6692 0%, #2d6692 100%);
            text-align: center;
            color: white;
            padding: 1px 1px;
            border-radius: 8px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            font-size: 20px;
            margin-bottom: 2px;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 13px;
        }
        
        .section {
            background: white;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .section h2 {
            color: #333;
            margin-bottom: 5px;
            padding-bottom: 4px;
            border-bottom: 2px solid #667eea;
            font-size: 16px;
        }
        
        .templates-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 5px;
        }
        
        .template-card {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            background: #fafafa;
        }
        
        .template-card h3 {
            font-size: 14px;
            color: #667eea;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .template-info {
            background: white;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 10px;
            border: 1px solid #e0e0e0;
        }
        
        .template-info.empty {
            text-align: center;
            color: #999;
            font-size: 13px;
            padding: 20px;
        }
        
        .template-detail {
            font-size: 14px;
            color: #666;
            margin-bottom: 4px;
        }
        
        .template-detail strong {
            color: #333;
        }
        
        .template-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            flex: 1;
            justify-content: center;
            min-width: 80px;
        }
        
        .btn-upload {
            background: #667eea;
            color: white;
        }
        
        .btn-upload:hover {
            background: #5568d3;
        }
        
        .btn-view {
            background: #2196f3;
            color: white;
        }
        
        .btn-view:hover {
            background: #1976d2;
        }
        
        .btn-preview {
            background: #9c27b0;
            color: white;
        }
        
        .btn-preview:hover {
            background: #7b1fa2;
        }
        
        .btn-delete {
            background: #f44336;
            color: white;
        }
        
        .btn-delete:hover {
            background: #d32f2f;
        }
        
        .btn-download {
            background: #4caf50;
            color: white;
        }
        
        .btn-download:hover {
            background: #388e3c;
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .file-input {
            display: none;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 13px;
        }
        
        thead {
            background: #f8f9fa;
        }
        
        th {
            padding: 10px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #dee2e6;
            font-size: 12px;
        }
        
        td {
            padding: 10px;
            border-bottom: 1px solid #dee2e6;
        }
        td:last-child {
            width: auto;
        }

        td .template-actions,
        td > .btn-container {
            display: flex;
            gap: 6px;
            flex-wrap: nowrap;
        }

        tbody td .btn {
            flex: none;
            white-space: nowrap;
            min-width: 70px;
            padding: 5px 8px;
        }

        tbody tr:hover {
            background: #f8f9ff;
        }
        
        .empty-state {
            text-align: center;
            padding: 30px;
            color: #999;
            font-size: 13px;
        }
        
        .empty-state-icon {
            font-size: 40px;
            margin-bottom: 10px;
            opacity: 0.5;
        }
        
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            border: 1px solid #129cd3;
        }
        
        .badge-primary {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .badge-secondary {
            background: #e3f2fd;
            color: #df1111;
        }
        
        .status-message {
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 15px;
            display: none;
            font-size: 13px;
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
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            padding: 20px;
            border-radius: 8px;
            width: 85%;
            max-width: 1400px;
            height: 95vh;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            display: flex;
            flex-direction: column;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #dee2e6;
            flex-shrink: 0;
        }
        
        .modal-header h3 {
            font-size: 16px;
            color: #333;
        }
        
        .close-modal {
            font-size: 24px;
            cursor: pointer;
            color: #999;
            line-height: 1;
        }
        
        .close-modal:hover {
            color: #333;
        }
        
        .pdf-viewer {
            width: 100%;
            flex: 1;
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }
        
        .preview-badge {
            background: #9c27b0;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            margin-left: 10px;
        }
        
        @media (max-width: 768px) {
            .templates-grid {
                grid-template-columns: 1fr;
            }
            
            table {
                font-size: 12px;
            }
            
            th, td {
                padding: 8px;
            }
            
            .btn {
                font-size: 11px;
                padding: 5px 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Administration des contrats</h1>
            <p>Gestion des modèles de contrats et des contrats signés</p>
        </div>
        
        <!-- Templates Section -->
        <div class="section">
            <h2>📄 Modèles de contrats</h2>
            
            <div class="templates-grid">
                <!-- Primaire Template -->
                <div class="template-card">
                    <h3>
                        <span class="badge badge-primary">Primaire</span>
                    </h3>
                    
                    <?php if ($primaireTemplate): ?>
                    <div class="template-info">
                        <div class="template-detail"><strong>Fichier:</strong> <?php echo htmlspecialchars($primaireTemplate['name']); ?></div>
                        <div class="template-detail"><strong>Taille:</strong> <?php echo formatBytes($primaireTemplate['size']); ?></div>
                        <div class="template-detail"><strong>Modifié:</strong> <?php echo date('Y-m-d H:i', $primaireTemplate['modified']); ?></div>
                    </div>
                    <div class="template-actions">
                        <button class="btn btn-view" onclick="viewTemplate('<?php echo htmlspecialchars($primaireTemplate['name']); ?>', 'primaire', false)">
                            👁️ Original
                        </button>
                        <button class="btn btn-preview" onclick="viewTemplate('<?php echo htmlspecialchars($primaireTemplate['name']); ?>', 'primaire', true)">
                            🔍 Aperçu
                        </button>
                        <button class="btn btn-delete" onclick="deleteTemplate('<?php echo htmlspecialchars($primaireTemplate['name']); ?>', 'primaire')">
                            🗑️ Supprimer
                        </button>
                    </div>
                    <?php else: ?>
                    <div class="template-info empty">
                        <div>📭</div>
                        <div>Aucun modèle</div>
                    </div>
                    <div class="template-actions">
                        <label for="primaire-file" class="btn btn-upload" style="cursor: pointer;">
                            📤 Téléverser
                        </label>
                        <input type="file" id="primaire-file" class="file-input" accept=".pdf" onchange="handleTemplateUpload('primaire', this)">
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Secondaire Template -->
                <div class="template-card">
                    <h3>
                        <span class="badge badge-secondary">Secondaire</span>
                    </h3>
                    
                    <?php if ($secondaireTemplate): ?>
                    <div class="template-info">
                        <div class="template-detail"><strong>Fichier:</strong> <?php echo htmlspecialchars($secondaireTemplate['name']); ?></div>
                        <div class="template-detail"><strong>Taille:</strong> <?php echo formatBytes($secondaireTemplate['size']); ?></div>
                        <div class="template-detail"><strong>Modifié:</strong> <?php echo date('Y-m-d H:i', $secondaireTemplate['modified']); ?></div>
                    </div>
                    <div class="template-actions">
                        <button class="btn btn-view" onclick="viewTemplate('<?php echo htmlspecialchars($secondaireTemplate['name']); ?>', 'secondaire', false)">
                            👁️ Original
                        </button>
                        <button class="btn btn-preview" onclick="viewTemplate('<?php echo htmlspecialchars($secondaireTemplate['name']); ?>', 'secondaire', true)">
                            🔍 Aperçu
                        </button>
                        <button class="btn btn-delete" onclick="deleteTemplate('<?php echo htmlspecialchars($secondaireTemplate['name']); ?>', 'secondaire')">
                            🗑️ Supprimer
                        </button>
                    </div>
                    <?php else: ?>
                    <div class="template-info empty">
                        <div>📭</div>
                        <div>Aucun modèle</div>
                    </div>
                    <div class="template-actions">
                        <label for="secondaire-file" class="btn btn-upload" style="cursor: pointer;">
                            📤 Téléverser
                        </label>
                        <input type="file" id="secondaire-file" class="file-input" accept=".pdf" onchange="handleTemplateUpload('secondaire', this)">
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Status message for templates -->
            <div id="template-status-message" class="status-message"></div>
        </div>
        
        <!-- Signed Contracts Section -->
        <div class="section">
            <h2>✅ Contrats signés (<?php echo count($signedContracts); ?>)</h2>
            
            <!-- Status message for signed contracts -->
            <div id="contract-status-message" class="status-message"></div>
            
            <?php if (empty($signedContracts)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📭</div>
                <p>Aucun contrat signé</p>
            </div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Nom du fichier</th>
                        <th>Taille</th>
                        <th>Date de signature</th>
                        <th style="width: 220px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($signedContracts as $contract): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($contract['name']); ?></strong></td>
                        <td><?php echo formatBytes($contract['size']); ?></td>
                        <td><?php echo date('Y-m-d H:i:s', $contract['modified']); ?></td>
                        <td>
                            <button class="btn btn-view" onclick="viewContract('<?php echo htmlspecialchars($contract['name']); ?>')">
                                👁️ Voir
                            </button>
                            <a href="modules/contract/admin_handler.php?action=download&file=<?php echo urlencode($contract['name']); ?>" 
                               class="btn btn-download" download>
                                📥 Télécharger
                            </a>
                            <button class="btn btn-delete" onclick="deleteContract('<?php echo htmlspecialchars($contract['name']); ?>')">
                                🗑️ Supprimer
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- PDF Viewer Modal -->
    <div id="pdf-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modal-title">Aperçu du document</h3>
                <span class="close-modal" onclick="closeModal()">&times;</span>
            </div>
            <iframe id="pdf-iframe" class="pdf-viewer"></iframe>
        </div>
    </div>

    <script>
        async function handleTemplateUpload(type, input) {
            const file = input.files[0];
            if (!file) return;
            
            if (file.type !== 'application/pdf') {
                showTemplateMessage('Erreur: Seuls les fichiers PDF sont acceptés', 'error');
                input.value = '';
                return;
            }
            
            if (file.size > 10 * 1024 * 1024) {
                showTemplateMessage('Erreur: Le fichier ne doit pas dépasser 10 MB', 'error');
                input.value = '';
                return;
            }
            
            const formData = new FormData();
            formData.append('template', file);
            formData.append('type', type);
            
            try {
                const response = await fetch('../../modules/contract/admin_upload_template.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showTemplateMessage('✓ Modèle ' + type + ' téléversé avec succès!', 'success');
                    setTimeout(() => {
                        check_content("Ajax.php?modname=contract/Contractadmin.php");
                    }, 1500);
                } else {
                    showTemplateMessage('✗ Erreur: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showTemplateMessage('✗ Erreur de connexion', 'error');
            }
            
            input.value = '';
        }
        
        function viewTemplate(filename, subtype, preview) {
            const previewParam = preview ? '&preview=true' : '';
            const titlePrefix = preview ? '🔍 Aperçu avec données de test: ' : 'Modèle: ';
            
            document.getElementById('modal-title').innerHTML = titlePrefix + filename + 
                (preview ? ' <span class="preview-badge">Données de test</span>' : '');
            document.getElementById('pdf-iframe').src = '../../modules/contract/admin_handler.php?action=view&type=template&subtype=' + 
                subtype + '&file=' + encodeURIComponent(filename) + previewParam;
            document.getElementById('pdf-modal').classList.add('active');
        }
        
        function viewContract(filename) {
            document.getElementById('modal-title').textContent = 'Contrat signé: ' + filename;
            document.getElementById('pdf-iframe').src = '../../modules/contract/admin_handler.php?action=view&type=signed&file=' + encodeURIComponent(filename);
            document.getElementById('pdf-modal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('pdf-modal').classList.remove('active');
            document.getElementById('pdf-iframe').src = '';
        }
        
        async function deleteTemplate(filename, subtype) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer ce modèle?\n\n' + filename)) {
                return;
            }
            
            try {
                const response = await fetch('../../modules/contract/admin_handler.php?action=delete', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        type: 'template',
                        filename: filename,
                        subtype: subtype
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showTemplateMessage('✓ Modèle supprimé avec succès!', 'success');
                    setTimeout(() => {
                        check_content("Ajax.php?modname=contract/Contractadmin.php");
                    }, 1500);
                } else {
                    showTemplateMessage('✗ Erreur: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showTemplateMessage('✗ Erreur de connexion', 'error');
            }
        }
        
        async function deleteContract(filename) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer ce contrat signé?\n\nCette action est irréversible!\n\n' + filename)) {
                return;
            }
            
            try {
                const response = await fetch('../../modules/contract/admin_handler.php?action=delete', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        type: 'signed',
                        filename: filename
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showContractMessage('✓ Contrat signé supprimé avec succès!', 'success');
                    setTimeout(() => {
                        check_content("Ajax.php?modname=contract/Contractadmin.php");
                    }, 1500);
                } else {
                    showContractMessage('✗ Erreur: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showContractMessage('✗ Erreur de connexion', 'error');
            }
        }
        
        function showTemplateMessage(message, type) {
            const statusDiv = document.getElementById('template-status-message');
            statusDiv.className = 'status-message ' + type;
            statusDiv.textContent = message;
            
            setTimeout(() => {
                statusDiv.className = 'status-message';
            }, 5000);
        }
        
        function showContractMessage(message, type) {
            const statusDiv = document.getElementById('contract-status-message');
            statusDiv.className = 'status-message ' + type;
            statusDiv.textContent = message;
            
            setTimeout(() => {
                statusDiv.className = 'status-message';
            }, 5000);
        }
        
        // Close modal when clicking outside
        document.getElementById('pdf-modal').addEventListener('click', (e) => {
            if (e.target === document.getElementById('pdf-modal')) {
                closeModal();
            }
        });
        
        // Close modal with Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>