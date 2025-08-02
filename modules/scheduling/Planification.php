

<?php  
// Include HTML Purifier
// require_once dirname(__FILE__) . '/../library/HTMLPurifier.auto.php';
require_once 'libraries/htmlpurifier/library/HTMLPurifier.auto.php';

// Configure HTML Purifier
function createHtmlPurifier() {
    $config = HTMLPurifier_Config::createDefault();
    
    // Allow common HTML elements and attributes for rich text editing
    $config->set('HTML.Allowed', 
        'b,p[class|style],br,strong,b,em,i,u,strike,del,h1,h2,h3,h4,h5,h6,' .
        'ul,ol,li,blockquote,pre,code,' .
        'table[style|class|width|cellspacing|cellpadding|border|width|margin|align],thead,tfoot,tr[style|class],td[style|class|valign|colspan],th[style|class|valign],' .
        'a[href|title|target],' .
        'img[src|alt|width|height|style],' .
        'div[style|class],span[style|class],font[color|style|size]'
    );
    
    // Allow safe CSS properties for styling
    $config->set('CSS.AllowedProperties', 
        'background-color,color,font-weight,font-style,text-decoration,font-variant-numeric,font-variant-east-asian,font-variant-alternates,font-size-adjust,font-kerning,font-optical-sizing,font-feature-settings,font-variation-settings,font-variant-position,font-variant-emoji,font-stretch,font-size,line-height,font-family,' .
        'border-style,border-width,border-color,margin-bottom,' .
        'text-align,padding,margin,border,width,height'
    );


    // Allow target="_blank" for links
    $config->set('Attr.AllowedFrameTargets', array('_blank'));
    
    // Set cache directory (make sure this directory exists and is writable)
    $config->set('Cache.SerializerPath', '/tmp/htmlpurifier');
    
    return new HTMLPurifier($config);
}

$purifier = createHtmlPurifier();

if ($_POST && isset($_POST['content'])) {
    $content = $_POST['content'];
    
    // Purify the HTML content before storing
    $clean_content = $purifier->purify($content);

    $RET = DBGet(DBQuery('select * from planification where course_id=\'' . UserCourse() . '\''));
    if(count($RET))
        $result = DBQuery('UPDATE  planification SET text ="' . base64_encode($clean_content) . '" WHERE course_id= '. UserCourse() . ''); 

        // If this is an auto-save request, return JSON response
    if (isset($_POST['auto_save'])) {
        header('Content-Type: application/json');
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Auto-saved successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
        exit; // Important: stop execution after JSON response
    }
    // For manual saves, continue with normal page rendering
}

// Get course data
$RET = DBGet(DBQuery('select short_name from course_details where course_id=\'' . UserCourse() . '\''));
$course = 'Planification ';
$course .= $RET[1]['SHORT_NAME'];
$RET = DBGet(DBQuery('select * from planification where course_id=\'' . UserCourse() . '\''));
if(!count($RET)) 
        DBQuery('INSERT INTO planification (course_id) VALUES ('. UserCourse() . ')'); 

// Purify content when displaying it as well (defense in depth)
$raw_content = base64_decode($RET[1]['TEXT']);
$content = $purifier->purify($raw_content);

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Éditeur WYSIWYG</title>
    <style>
        .editor-container {
            max-width: 1500px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .editor-header {
            background:  #5c8bb0ff;
            color: white;
            padding: 2px;
            text-align: center;
        }

        .editor-header h1 {
            font-size: 2rem;
            font-weight: 300;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .toolbar {
            background: #f8f9fa;
            padding: 1px;
            border-bottom: 2px solid #e9ecef;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }

        .toolbar-group {
            display: flex;
            gap: 5px;
            align-items: center;
            padding: 5px;
            border-radius: 8px;
            background: white;
        }

 
        .toolbar-font {
        }

        .toolbar button {
            background: white;
            border: 1px solid #c3c6c8ff;
            border-radius: 6px;
            padding: 8px 12px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s ease;
            color: #495057;
        }

        .toolbar button:hover {
            background: #007bff;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,123,255,0.3);
        }

        .toolbar button.active {
            background: #007bff;
            color: white;
            box-shadow: 0 2px 4px rgba(0,123,255,0.3);
        }

        .toolbar select {
            border: 1px solid #bcbebfff;
            border-radius: 6px;
            padding: 10px;
            background: white;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .toolbar select:hover {
            border-color: #007bff;
        }

        .toolbar input[type="color"] {
            width: 40px;
            height: 35px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            background: white;
        }

        .editor-content {
            min-height: 500px;
            padding: 20px;
            border: none;
            outline: none;
            font-size: 16px;
            line-height: 1.6;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: white;
        }

        .editor-footer {
            padding: 15px 20px;
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .save-btn {
            background: #5090c1;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .save-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6);
        }

        .word-count {
            color: #6c757d;
            font-size: 14px;
        }

        .auto-save-status {
            color: #6c757d;
            font-size: 12px;
            margin-left: 10px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .auto-save-status.saving {
            color: #ffc107;
        }

        .auto-save-status.saved {
            color: #28a745;
        }

        .auto-save-status.error {
            color: #dc3545;
        }

        .auto-save-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: currentColor;
            display: inline-block;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 20px;
            border-radius: 15px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            animation: slideUp 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .modal-buttons button {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-primary:hover, .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        /* Table row coloring styles */
        .table-color-options {
            display: flex;
            flex-direction: column;
            gap: 0px;
        }


        .color-section {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            background: #f8f9fa;
        }

        .color-section h4 {
            margin: 0 0 10px 0;
            color: #495057;
            font-size: 14px;
            font-weight: 600;
        }

        .zebra-options {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .zebra-preset {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.2s ease;
            background: white;
        }

        .zebra-preset:hover {
            border-color: #007bff;
            background: #f0f8ff;
        }

        .zebra-preset input[type="radio"] {
            margin: 0;
        }

        .color-preview {
            width: 20px;
            height: 15px;
            border: 1px solid #ccc;
            border-radius: 3px;
            display: inline-block;
        }

        .custom-colors {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .custom-colors label {
            font-size: 12px;
            color: #666;
        }

        .row-selector {
            margin-bottom: 15px;
        }

        .row-selector label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #495057;
        }

        /* Header row styling options */
        .header-options {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        .header-preset {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.2s ease;
            background: white;
        }

        .header-preset:hover {
            border-color: #007bff;
            background: #f0f8ff;
        }

        .header-preset input[type="checkbox"] {
            margin: 0;
        }

        @media (max-width: 768px) {
            .toolbar {
                justify-content: center;
            }
            
            .toolbar-group {
                flex-wrap: wrap;
            }
            
            .editor-footer {
                flex-direction: column;
                text-align: center;
            }

            .zebra-options {
                flex-direction: column;
            }
        }


                .color-section {
            background: white;
            border: 2px solid #f1f5f9;
            border-radius: 16px;
            padding: 24px;
            transition: all 0.3s ease;
        }

        .color-section:hover {
            border-color: #e2e8f0;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .header-options {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: center;
        }

        .header-options label {
            font-weight: 600;
            color: #a4a4a4ff;
            margin-right: 8px;
        }

        input[type="color"] {
            width: 50px;
            height: 40px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            cursor: pointer;
            background: none;
            transition: all 0.3s ease;
        }

        input[type="color"]:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .color-section h4 {
            font-size: 20px;
            font-weight: 700;
            color: #8d8f93ff;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .color-picker-container {
            position: relative;
            display: inline-block;
        }

        .color-trigger {
            display: flex;
            align-items: center;
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 6px 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 13px;
            font-weight: 500;
            gap: 8px;
            min-width: 80px;
        }

        .color-trigger:hover {
            border-color: #007bff;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.2);
        }

        .color-preview {
            width: 20px;
            height: 16px;
            border-radius: 4px;
            border: 1px solid #ddd;
            display: inline-block;
        }

        .color-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            display: none;
            padding: 20px;
            min-width: 280px;
            animation: fadeInUp 0.3s ease;
        }

        .color-dropdown.show {
            display: block;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .color-section {
            margin-bottom: 20px;
        }

        .color-section h4 {
            margin: 0 0 12px 0;
            font-size: 14px;
            font-weight: 600;
            color: #4a5568;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .color-grid {
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            gap: 6px;
            margin-bottom: 15px;
        }

        .color-swatch {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            border: 2px solid transparent;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        }

        .color-swatch:hover {
            transform: scale(1.1);
            border-color: #007bff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .color-swatch.selected {
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.3);
        }

        .custom-color-section {
            border-top: 1px solid #e2e8f0;
            padding-top: 15px;
        }

        .custom-color-input {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }

        .custom-color-input input[type="color"] {
            width: 40px;
            height: 32px;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            cursor: pointer;
            background: none;
        }

        .custom-color-input input[type="text"] {
            flex: 1;
            padding: 6px 10px;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            font-size: 13px;
            font-family: monospace;
        }

        .custom-color-input input[type="text"]:focus {
            outline: none;
            border-color: #007bff;
        }

        .recent-colors {
            display: flex;
            gap: 4px;
            flex-wrap: wrap;
        }

        .recent-color {
            width: 24px;
            height: 24px;
            border-radius: 4px;
            border: 1px solid #ddd;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .recent-color:hover {
            transform: scale(1.1);
            border-color: #007bff;
        }

        .clear-recent {
            background: #f8f9fa;
            border: 1px dashed #6c757d;
            color: #6c757d;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .clear-recent:hover {
            background: #e9ecef;
            border-color: #495057;
            color: #495057;
        }

    </style>
</head>
<body>
    <div class="editor-container">
        <div class="editor-header">
        <?php
            echo '<h1><b>'.$course.'</b></h1>';
        ?>
        </div>
        
        <div class="toolbar">
            <div class="toolbar-group">
                <button onclick="execCmd('undo')" title="Annuler">↶</button>
                <button onclick="execCmd('redo')" title="Rétablir">↷</button>
            </div>
            
            <div class="toolbar-font">
                <select onchange="execCmd('fontSize', this.value)">
                    <option value="">Taille</option>
                    <option value="1">Très petit</option>
                    <option value="2">Petit</option>
                    <option value="3">Normal</option>
                    <option value="4">Moyen</option>
                    <option value="5">Grand</option>
                    <option value="6">Très grand</option>
                    <option value="7">Énorme</option>
                </select>
            </div>
            
            <div class="toolbar-group">
                <button onclick="execCmd('bold')" title="Gras"><strong>G</strong></button>
                <button onclick="execCmd('italic')" title="Italique"><em>I</em></button>
                <button onclick="execCmd('underline')" title="Souligné"><u>S</u></button>
                <button onclick="execCmd('strikeThrough')" title="Barré"><strike>B</strike></button>
            </div>
            
            <div class="toolbar-group hidden">texte
                <input type="color" onchange="execCmd('foreColor', this.value)" title="Couleur du texte" value="#000000" >
                arrière-plan
                <input type="color" onchange="execCmd('backColor', this.value)" title="Couleur de fond" value="#ffffff" >
            </div>
<div class="toolbar-group">
    <div class="color-picker-container">
        <div class="color-trigger" onclick="toggleColorDropdown('textColorPicker')">
            <div class="color-preview" id="textColorPreview" style="background-color: #101010ff;"></div>
            <span>Texte</span>
            <span style="font-size: 10px;">▼</span>
        </div>
        <div class="color-dropdown" id="textColorPicker">
            <div class="color-section">
                <h4>🎨 Couleurs courantes</h4>
                <div class="color-grid" id="commonColors"></div>
            </div>
            
            <div class="color-section">
                <h4>🌈 Palette étendue</h4>
                <div class="color-grid" id="extendedColors"></div>
            </div>
            
            <div class="color-section">
                <h4>🕒 Récemment utilisées</h4>
                <div class="recent-colors" id="recentTextColors">
                    <div class="recent-color clear-recent" onclick="clearRecentColors('text')" title="Effacer l'historique">×</div>
                </div>
            </div>
            
            <div class="custom-color-section">
                <h4>⚙️ Couleur personnalisée</h4>
                <div class="custom-color-input">
                    <input type="color" id="customTextColor" onchange="applyCustomColor('text', this.value)">
                    <input type="text" id="customTextHex" placeholder="#000000" onchange="applyCustomHex('text', this.value)">
                </div>
            </div>
        </div>
    </div>
    
    <div class="color-picker-container">
        <div class="color-trigger" onclick="toggleColorDropdown('bgColorPicker')">
            <div class="color-preview" id="bgColorPreview" style="background-color: #ffffff; border: 1px solid #ddd;"></div>
            <span>Fond</span>
            <span style="font-size: 10px;">▼</span>
        </div>
        <div class="color-dropdown" id="bgColorPicker">
            <div class="color-section">
                <h4>🎨 Couleurs courantes</h4>
                <div class="color-grid" id="commonBgColors"></div>
            </div>
            
            <div class="color-section">
                <h4>🌈 Palette étendue</h4>
                <div class="color-grid" id="extendedBgColors"></div>
            </div>
            
            <div class="color-section">
                <h4>🕒 Récemment utilisées</h4>
                <div class="recent-colors" id="recentBgColors">
                    <div class="recent-color clear-recent" onclick="clearRecentColors('bg')" title="Effacer l'historique">×</div>
                </div>
            </div>
            
            <div class="custom-color-section">
                <h4>⚙️ Couleur personnalisée</h4>
                <div class="custom-color-input">
                    <input type="color" id="customBgColor" onchange="applyCustomColor('bg', this.value)">
                    <input type="text" id="customBgHex" placeholder="#ffffff" onchange="applyCustomHex('bg', this.value)">
                </div>
            </div>
        </div>
    </div>
</div>              
            <div class="toolbar-group">
                <button onclick="execCmd('justifyLeft')" title="Aligner à gauche">≡</button>
                <button onclick="execCmd('justifyCenter')" title="Centrer">≣</button>
                <button onclick="execCmd('justifyRight')" title="Aligner à droite">≡</button>
                <button onclick="execCmd('justifyFull')" title="Justifier">≣</button>
            </div>
            
            <div class="toolbar-group">
                <button onclick="execCmd('insertUnorderedList')" title="Liste à puces">• Liste</button>
                <button onclick="execCmd('insertOrderedList')" title="Liste numérotée">1. Liste</button>
                <button onclick="execCmd('indent')" title="Indenter">→|</button>
                <button onclick="execCmd('outdent')" title="Désindenter">|←</button>
            </div>
            
            <div class="toolbar-group">
                <button onclick="insertTable()" title="Insérer un tableau">📊 Tableau</button>
                <button onclick="colorTableRows()" title="Colorer les lignes du tableau">🎨 Colorer lignes</button>
            </div>
            
        </div>
        
        <div <div id="editor" class="editor-content" contenteditable="true"><?php echo ''.$content.''; ?></div>
        
        <div class="editor-footer">
            <div style="display: flex; align-items: center;">
                <div class="word-count" id="wordCount">Nombre de mots: 0</div>
                <div>&nbsp&nbsp&nbsp&nbsp</div>
                <div class="word-count" id="charCount">Nombre de char: 0</div>
                <div class="auto-save-status" id="autoSaveStatus">
                    <span class="auto-save-indicator"></span>
                    <span id="autoSaveText">Auto-sauvegarde activée</span>
                </div>
            </div>
            <button class="save-btn" onclick="saveContent2()">💾 Enregistrer le contenu</button>
        </div>
    </div>

    <!-- Modal pour les liens -->
    <div id="linkModal" class="modal">
        <div class="modal-content">
            <h3>Insérer un lien</h3>
            <input type="text" id="linkText" placeholder="Texte à afficher">
            <input type="url" id="linkUrl" placeholder="URL (https://exemple.com)">
            <div class="modal-buttons">
                <button class="btn-secondary" onclick="closeModal('linkModal')">Annuler</button>
                <button class="btn-primary" onclick="insertLinkAction()">Insérer</button>
            </div>
        </div>
    </div>

    <!-- Modal pour les tableaux -->
    <div id="tableModal" class="modal">
        <div class="modal-content">
            <h3>Insérer un tableau</h3>
            <input type="number" id="tableRows" placeholder="Nombre de lignes" min="1" max="10" value="4">
            <input type="number" id="tableCols" placeholder="Nombre de colonnes" min="1" max="10" value="4">
            <div class="modal-buttons">
                <button class="btn-secondary" onclick="closeModal('tableModal')">Annuler</button>
                <button class="btn-primary" onclick="insertTableAction()">Insérer</button>
            </div>
        </div>
    </div>

    <!-- Modal pour colorer les lignes du tableau -->
    <div id="tableColorModal" class="modal">
        <div class="modal-content">
            <h3>Colorer les lignes du tableau</h3>
            
            <div class="row-selector">
                <label>Sélectionner le tableau :</label>
                <select id="tableSelector">
                    <option value="">Choisir un tableau...</option>
                </select>
            </div>

            <div class="table-color-options">
                <div class="color-section">
                    <div class="header-options">
                            <input hidden type="checkbox" checked="true" id="headerEnabled">
                            <label hidden for="headerEnabled">Activer l'en-tête</label>
                        <label>Couleur en-tête:</label>
                        <input type="color" id="headerColor" value="#636363">
                        <label>Couleur texte:</label>
                        <input type="color" id="headerTextColor" value="#ffffff">
                    </div>
                </div>

                <div class="color-section">
                    <h4>🦓 Rayures alternées (Zebra)</h4>
                    <div class="zebra-options">
                        <div class="zebra-preset">
                            <input type="radio" name="colorOption" value="zebra-light" id="zebraLight">
                            <label for="zebraLight">
                                <div class="color-preview" style="background: linear-gradient(to right, #f8f9fa 50%, white 50%);"></div>
                                Clair
                            </label>
                        </div>
                        <div class="zebra-preset">
                            <input type="radio" name="colorOption" value="zebra-blue" id="zebraBlue">
                            <label for="zebraBlue">
                                <div class="color-preview" style="background: linear-gradient(to right, #e3f2fd 50%, white 50%);"></div>
                                Bleu
                            </label>
                        </div>
                        <div class="zebra-preset">
                            <input type="radio" name="colorOption" value="zebra-green" id="zebraGreen">
                            <label for="zebraGreen">
                                <div class="color-preview" style="background: linear-gradient(to right, #e8f5e8 50%, white 50%);"></div>
                                Vert
                            </label>
                        </div>
                        <div class="zebra-preset">
                            <input type="radio" name="colorOption" value="zebra-yellow" id="zebraYellow">
                            <label for="zebraYellow">
                                <div class="color-preview" style="background: linear-gradient(to right, #fff8e1 50%, white 50%);"></div>
                                Jaune
                            </label>
                        </div>
                    </div>
                </div>
            <div class="modal-buttons">
                <button class="btn-secondary" onclick="closeModal('tableColorModal')">Annuler</button>
                <button class="btn-primary" onclick="applyTableColoring()">Appliquer</button>
            </div>
        </div>
    </div>

    <script>
        const editor = document.getElementById('editor');
        const autoSaveStatus = document.getElementById('autoSaveStatus');
        const autoSaveText = document.getElementById('autoSaveText');
        let savedSelection = null;
        let savedRange = null;

        // Auto-save configuration
        let autoSaveTimeout;
        let autoSaveInterval;
        let lastSavedContent = editor.innerHTML;
        let hasUnsavedChanges = false;
        let dont_save = false;
        
        const AUTO_SAVE_DELAY = 3000; // 3 seconds after user stops typing
        const AUTO_SAVE_INTERVAL = 30000; // 30 seconds periodic save

function saveCursorPosition() {
    const selection = window.getSelection();
    if (selection.rangeCount > 0) {
        savedRange = selection.getRangeAt(0).cloneRange();
        savedSelection = {
            anchorNode: selection.anchorNode,
            anchorOffset: selection.anchorOffset,
            focusNode: selection.focusNode,
            focusOffset: selection.focusOffset
        };
    }
}

// Function to restore cursor position/selection
function restoreCursorPosition() {
    if (savedRange) {
        const selection = window.getSelection();
        selection.removeAllRanges();
        try {
            selection.addRange(savedRange);
        } catch (e) {
            // Fallback if range is invalid
            const editor = document.getElementById('editor');
            editor.focus();
            // Move cursor to end as fallback
            const range = document.createRange();
            range.selectNodeContents(editor);
            range.collapse(false);
            selection.addRange(range);
        }
    }
}

        // Exécuter les commandes d'édition
function execCmd(command, value = null) {
    const editor = document.getElementById('editor');
    
    // Ensure editor has focus but don't disturb selection
    if (!editor.contains(document.activeElement)) {
        editor.focus();
    }
    
    document.execCommand(command, false, value);
    updateWordCount();
    updateToolbarState();
}

        // Auto-save functions
        function updateAutoSaveStatus(status, message) {
            autoSaveStatus.className = `auto-save-status ${status}`;
            autoSaveText.textContent = message;
        }

        function triggerAutoSave() {
            hasUnsavedChanges = true;
            
            // Clear existing timeout
            if (autoSaveTimeout) {
                clearTimeout(autoSaveTimeout);
            }
            
            // Set new timeout
            autoSaveTimeout = setTimeout(() => {
                if (hasUnsavedChanges) {
                    saveContent();
                }
            }, AUTO_SAVE_DELAY);
        }

        function startPeriodicAutoSave() {
            autoSaveInterval = setInterval(() => {
                if (hasUnsavedChanges) {
                    saveContent();
                }
            }, AUTO_SAVE_INTERVAL);
        }

        function stopPeriodicAutoSave() {
            if (autoSaveInterval) {
                clearInterval(autoSaveInterval);
            }
        }
        
        // Compter les mots
        function updateWordCount() {
            const text = editor.innerText || editor.textContent || '';
            const words = text.trim().split(/\s+/).filter(word => word.length > 0);
            const char = text.length;
            document.getElementById('wordCount').textContent = `Nombre de mots : ${words.length}`;
            document.getElementById('charCount').textContent = `Nombre de char : ${text.length }`;
            if(text.length > 40000){
                dont_save = true;
                alert('Texte trop long.... La sauvegarde n\'aura pas lieu. Enlevez du texte.'); 
            }
            else
                dont_save = false;
        }
        
        // Insérer un lien
        function insertLink() {
            document.getElementById('linkModal').style.display = 'block';
        }
        
        function insertLinkAction() {
            const text = document.getElementById('linkText').value;
            const url = document.getElementById('linkUrl').value;
            
            if (url) {
                const linkHtml = text ? `<a href="${url}" target="_blank">${text}</a>` : `<a href="${url}" target="_blank">${url}</a>`;
                execCmd('insertHTML', linkHtml);
            }
            
            closeModal('linkModal');
            document.getElementById('linkText').value = '';
            document.getElementById('linkUrl').value = '';
        }
        
        
        // Insérer un tableau
        function insertTable() {
            document.getElementById('tableModal').style.display = 'block';
        }
        
        function insertTableAction() {
            const rows = parseInt(document.getElementById('tableRows').value) || 3;
            const cols = parseInt(document.getElementById('tableCols').value) || 3;
            let tableHtml = '<table border="1" style="border-collapse: collapse; width: 100%; margin: 10px 0;">';
            for (let i = 0; i < rows; i++) {
                tableHtml += '<tr>';
                for (let j = 0; j < cols; j++) {
                    tableHtml += '<td style="padding: 8px; border: 1px solid #ddd;">&nbsp</td>';
                }
                tableHtml += '</tr>';
            }
            tableHtml += '</table>';
            execCmd('insertHTML', tableHtml);            
            closeModal('tableModal');
        }

        // Table row coloring functions
        function colorTableRows() {
            populateTableSelector();
            document.getElementById('tableColorModal').style.display = 'block';
        }

        function populateTableSelector() {
            const tables = editor.querySelectorAll('table');
            const selector = document.getElementById('tableSelector');
            
            // Clear existing options
            selector.innerHTML = '<option value="">Choisir un tableau...</option>';
            
            tables.forEach((table, index) => {
                const option = document.createElement('option');
                option.value = index;
                option.textContent = `Tableau ${index + 1} (${table.rows.length} lignes)`;
                selector.appendChild(option);
            });
        }

        function applyTableColoring() {
            const tableIndex = document.getElementById('tableSelector').value;
            const selectedOption = document.querySelector('input[name="colorOption"]:checked');
            
            if (!tableIndex) {
                alert('Veuillez sélectionner un tableau.');
                return;
            }
            
            const tables = editor.querySelectorAll('table');
            const selectedTable = tables[parseInt(tableIndex)];
            
            if (!selectedTable) {
                alert('Tableau non trouvé.');
                return;
            }
            
            // Apply header styling first if enabled
            const headerEnabled = document.getElementById('headerEnabled').checked;
            if (headerEnabled) {
                applyHeaderStyling(selectedTable);
            }
            
            // Apply row coloring if selected
            if (selectedOption) {
                const colorOption = selectedOption.value;
                applyColoringToTable(selectedTable, colorOption, headerEnabled);
            }
            
            closeModal('tableColorModal');
            triggerAutoSave();
        }

        function applyHeaderStyling(table) {
            const headerColor = document.getElementById('headerColor').value;
            const headerTextColor = document.getElementById('headerTextColor').value;
            const firstRow = table.querySelector('tr');
            
            if (firstRow) {
                firstRow.style.backgroundColor = headerColor;
                firstRow.style.color = headerTextColor;
                firstRow.style.fontWeight = 'bold';
                
                // Apply to all cells in the first row
                const cells = firstRow.querySelectorAll('td, th');
                cells.forEach(cell => {
                    cell.style.backgroundColor = headerColor;
                    cell.style.color = headerTextColor;
                    cell.style.fontWeight = 'bold';
                });
            }
        }

        function applyColoringToTable(table, colorOption, skipFirstRow = false) {
            const rows = table.querySelectorAll('tr');
            const startIndex = skipFirstRow ? 1 : 0;
            
            for (let i = startIndex; i < rows.length; i++) {
                const row = rows[i];
                
                // Skip header row styling if it was already applied
                if (i === 0 && skipFirstRow) {
                    continue;
                }
                
                // Remove existing background color styles for non-header rows
                if (!(i === 0 && skipFirstRow)) {
                    row.style.backgroundColor = '';
                }
                
                const adjustedIndex = skipFirstRow ? i - 1 : i;
                
                switch (colorOption) {
                    case 'zebra-light':
                        if (!(i === 0 && skipFirstRow)) {
                            row.style.backgroundColor = adjustedIndex % 2 === 0 ? '#f8f9fa' : 'white';
                        }
                        break;
                    case 'zebra-blue':
                        if (!(i === 0 && skipFirstRow)) {
                            row.style.backgroundColor = adjustedIndex % 2 === 0 ? '#e3f2fd' : 'white';
                        }
                        break;
                    case 'zebra-green':
                        if (!(i === 0 && skipFirstRow)) {
                            row.style.backgroundColor = adjustedIndex % 2 === 0 ? '#e8f5e8' : 'white';
                        }
                        break;
                    case 'zebra-yellow':
                        if (!(i === 0 && skipFirstRow)) {
                            row.style.backgroundColor = adjustedIndex % 2 === 0 ? '#fff8e1' : 'white';
                        }
                        break;
                    case 'custom':
                        if (!(i === 0 && skipFirstRow)) {
                            const color1 = document.getElementById('customColor1').value;
                            const color2 = document.getElementById('customColor2').value;
                            row.style.backgroundColor = adjustedIndex % 2 === 0 ? color1 : color2;
                        }
                        break;
                    case 'remove':
                        if (!(i === 0 && skipFirstRow)) {
                            row.style.backgroundColor = '';
                            row.removeAttribute('style');
                        }
                        break;
                }
            }
        }
        
        // Fermer les modales
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Fermer les modales en cliquant à l'extérieur
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }
        
        // Sauvegarder le contenu
        function saveContent() {
            const content = editor.innerHTML;
            if(dont_save) return;
            updateAutoSaveStatus('saving', 'Sauvegarde manuelle...');
            
            const formData = new FormData();
            formData.append('content', content);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.ok) {
                    lastSavedContent = content;
                    hasUnsavedChanges = false;
                    const now = new Date().toLocaleTimeString('fr-FR');
                    updateAutoSaveStatus('saved', `Sauvegardé à ${now}`);
                } else {
                    throw new Error('Network response was not ok');
                }
            })
            .catch(error => {
                console.error('Manual save error:', error);
                updateAutoSaveStatus('error', 'Erreur de sauvegarde manuelle');
            });
        }

        // Sauvegarder le contenu
        function saveContent2() {
            const content = editor.innerHTML;
            if(dont_save) return;
            
            // Créer un formulaire pour envoyer les données
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'content';
            input.value = content;
            
            form.appendChild(input);
            document.body.appendChild(form);
            post('Modules.php?modname=scheduling/Planification.php',{content});
            document.body.removeChild(form);
        }
           
        function autoSaveContent() {
            const currentContent = editor.innerHTML;
            
            // Only save if content has actually changed
            if (currentContent === lastSavedContent) {
                hasUnsavedChanges = false;
                return;
            }
            
            updateAutoSaveStatus('saving', 'Sauvegarde automatique...');
            
            // Create form data
            const formData = new FormData();
            formData.append('content', currentContent);
            formData.append('auto_save', '1'); // This tells PHP to return JSON
            
            // Send AJAX request
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error('Response is not JSON');
                }
                
                return response.json();
            })
            .then(data => {
                if (data && data.success) {
                    lastSavedContent = currentContent;
                    hasUnsavedChanges = false;
                    const now = new Date().toLocaleTimeString('fr-FR');
                    updateAutoSaveStatus('saved', `Auto-sauvegardé à ${now}`);
                } else {
                    console.error('Auto-save failed:', data);
                    updateAutoSaveStatus('error', 'Erreur: ' + (data?.message || 'Réponse invalide'));
                }
            })
            .catch(error => {
                console.error('Auto-save error:', error);
                updateAutoSaveStatus('error', 'Erreur de sauvegarde automatique');
            });
        }

        function post(path, params, method='post') {
            // The rest of this code assumes you are not using a library.
            // It can be made less verbose if you use one.
            const form = document.createElement('form');
            form.method = method;
            form.action = path;

            for (const key in params) {
                if (params.hasOwnProperty(key)) {
                const hiddenField = document.createElement('input');
                hiddenField.type = 'hidden';
                hiddenField.name = key;
                hiddenField.value = params[key];
                form.appendChild(hiddenField);
                }
            }
            document.body.appendChild(form);
            form.submit();
        }

        // Event listeners for auto-save
        editor.addEventListener('input', function() {
            updateWordCount();
            triggerAutoSave();
        });
        
        editor.addEventListener('paste', function() {
            setTimeout(() => {
                triggerAutoSave();
            }, 100);
        });

        // Mettre à jour le compteur de mots en temps réel
        editor.addEventListener('input', updateWordCount);
        editor.addEventListener('mouseup', updateToolbarState);
        editor.addEventListener('keyup', updateToolbarState);
        editor.addEventListener('focus', updateToolbarState);

        // Initialiser le compteur de mots
        updateWordCount();
        startPeriodicAutoSave(); // Start the periodic auto-save

        if(document.readyState === 'complete') {
            post('Modules.php?modname=scheduling/Planification.php','');
            execCmd('fontSize', '3');
        }

        // Gestion des raccourcis clavier
        editor.addEventListener('keydown', function(e) {
            if (e.ctrlKey || e.metaKey) {
                switch(e.key) {
                    case 'b':
                        e.preventDefault();
                        execCmd('bold');
                        break;
                    case 'i':
                        e.preventDefault();
                        execCmd('italic');
                        break;
                    case 'u':
                        e.preventDefault();
                        execCmd('underline');
                        break;
                    case 's':
                        e.preventDefault();
                        saveContent();
                        break;
                }
            }
        });
        
        // Animation au survol des boutons de la barre d'outils
        document.querySelectorAll('.toolbar button').forEach(button => {
            button.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
            });
            
            button.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });

        function updateToolbarState() {
            // Get all formatting buttons
            const buttons = {
                bold: document.querySelector('button[onclick="execCmd(\'bold\')"]'),
                italic: document.querySelector('button[onclick="execCmd(\'italic\')"]'),
                underline: document.querySelector('button[onclick="execCmd(\'underline\')"]'),  
                strikeThrough: document.querySelector('button[onclick="execCmd(\'strikeThrough\')"]')
            };
            
            // Check each formatting state and update button appearance
            for (let command in buttons) {
                const button = buttons[command];
                if (button) {
                    if (document.queryCommandState(command)) {
                        button.classList.add('active');
                    } else {
                        button.classList.remove('active');
                    }
                }
            }
            
            // Update color inputs to reflect current selection colors
            updateColorInputs();
        }

        // Context menu for table rows
        let contextMenuTable = null;
        let contextMenuRow = null;

        // Add context menu for right-clicking on table rows
        editor.addEventListener('contextmenu', function(e) {
            const row = e.target.closest('tr');
            if (row && row.closest('table')) {
                e.preventDefault();
                contextMenuTable = row.closest('table');
                contextMenuRow = row;
                showRowContextMenu(e.pageX, e.pageY, row);
            }
        });

        function showRowContextMenu(x, y, row) {
            // Remove existing context menu
            const existingMenu = document.getElementById('rowContextMenu');
            if (existingMenu) {
                existingMenu.remove();
            }

            // Create context menu
            const menu = document.createElement('div');
            menu.id = 'rowContextMenu';
            menu.style.cssText = `
                position: absolute;
                left: ${x}px;
                top: ${y}px;
                background: white;
                border: 1px solid #ccc;
                border-radius: 5px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                z-index: 1001;
                min-width: 150px;
                font-size: 14px;
            `;

            const table = row.closest('table');
            const isFirstRow = row === table.querySelector('tr');
            
            const menuItems = [
                { text: '🎨 Colorer cette ligne', action: () => colorSingleRow(row) },
                { text: '🗑️ Supprimer couleur', action: () => removeSingleRowColor(row) }
            ];

            // Add header-specific options if this is the first row
            if (isFirstRow) {
                menuItems.unshift({ text: '📋 Définir comme en-tête', action: () => makeRowHeader(row) });
            }

            menuItems.push({ text: '📊 Colorer tout le tableau', action: () => colorWholeTable(contextMenuTable) });

            menuItems.forEach(item => {
                const menuItem = document.createElement('div');
                menuItem.textContent = item.text;
                menuItem.style.cssText = `
                    padding: 8px 12px;
                    cursor: pointer;
                    border-bottom: 1px solid #eee;
                `;
                menuItem.addEventListener('mouseenter', () => {
                    menuItem.style.backgroundColor = '#f0f0f0';
                });
                menuItem.addEventListener('mouseleave', () => {
                    menuItem.style.backgroundColor = '';
                });
                menuItem.addEventListener('click', () => {
                    item.action();
                    menu.remove();
                });
                menu.appendChild(menuItem);
            });

            document.body.appendChild(menu);

            // Remove menu when clicking elsewhere
            setTimeout(() => {
                document.addEventListener('click', function removeMenu() {
                    menu.remove();
                    document.removeEventListener('click', removeMenu);
                }, 0);
            }, 0);
        }

        function makeRowHeader(row) {
            const headerColor = prompt('Couleur de fond de l\'en-tête (hex, nom, rgb):', '#7476789c');
            const textColor = prompt('Couleur du texte de l\'en-tête (hex, nom, rgb):', '#ffffff');
            
            if (headerColor && textColor) {
                row.style.backgroundColor = headerColor;
                row.style.color = textColor;
                row.style.fontWeight = 'bold';
                
                // Apply to all cells in the row
                const cells = row.querySelectorAll('td, th');
                cells.forEach(cell => {
                    cell.style.backgroundColor = headerColor;
                    cell.style.color = textColor;
                    cell.style.fontWeight = 'bold';
                });
                
                triggerAutoSave();
            }
        }

        function colorSingleRow(row) {
            const color = prompt('Entrez une couleur (nom, hex, rgb):', '#e3f2fd');
            if (color) {
                row.style.backgroundColor = color;
                triggerAutoSave();
            }
        }

        function removeSingleRowColor(row) {
            row.style.backgroundColor = '';
            row.style.color = '';
            row.style.fontWeight = '';
            
            // Remove styling from all cells in the row
            const cells = row.querySelectorAll('td, th');
            cells.forEach(cell => {
                cell.style.backgroundColor = '';
                cell.style.color = '';
                cell.style.fontWeight = '';
            });
            
            triggerAutoSave();
        }

        function colorWholeTable(table) {
            // Find the table index and open the color modal
            const tables = editor.querySelectorAll('table');
            const tableIndex = Array.from(tables).indexOf(table);
            
            populateTableSelector();
            document.getElementById('tableSelector').value = tableIndex;
            document.getElementById('tableColorModal').style.display = 'block';
        }
        // ROGER
    function updateColorInputs() {
        const foreColorInput = document.querySelector('input[onchange="execCmd(\'foreColor\', this.value)"]');
        const backColorInput = document.querySelector('input[onchange="execCmd(\'backColor\', this.value)"]');
        const fore2ColorInput =  document.getElementById('textColorPreview');
        const selection = window.getSelection();
        


        // Get current fore color
        const currentForeColor = document.queryCommandValue('foreColor');
        if (currentForeColor && foreColorInput) {
            // Convert RGB to hex if needed
            const hexForeColor = rgbToHex(currentForeColor);
            if (hexForeColor) {
                foreColorInput.value = hexForeColor;
                document.getElementById('textColorPreview').style.backgroundColor= currentForeColor;
            }
        }
        
        // Get current background color
        const currentBackColor = document.queryCommandValue('backColor');
        if (currentBackColor && backColorInput) {
            // Convert RGB to hex if needed
            const hexBackColor = rgbToHex(currentBackColor);
            if (hexBackColor) {
                backColorInput.value = hexBackColor;
                document.getElementById('bgColorPreview').style.backgroundColor= currentBackColor;
            }
        }
    }

        function rgbToHex(color) {
            if (!color) return null;
            
            // If already hex, return as is
            if (color.startsWith('#')) {
                return color;
            }
            
            // Handle rgb() and rgba() formats
            const rgbMatch = color.match(/rgb\((\d+),\s*(\d+),\s*(\d+)\)/);
            const rgbaMatch = color.match(/rgba\((\d+),\s*(\d+),\s*(\d+),\s*[\d.]+\)/);
            
            let r, g, b;
            
            if (rgbMatch) {
                [, r, g, b] = rgbMatch;
            } else if (rgbaMatch) {
                [, r, g, b] = rgbaMatch;
            } else {
                // Try to handle named colors or other formats
                return null;
            }
            
            // Convert to hex
            const toHex = (n) => {
                const hex = parseInt(n).toString(16);
                return hex.length === 1 ? '0' + hex : hex;
            };
            
            return `#${toHex(r)}${toHex(g)}${toHex(b)}`;
        }
         // Color palettes
        const commonColors = [
            '#000000', '#ffffff', '#ff0000', '#00ff00', '#0000ff', '#ffff00', '#ff00ff', '#00ffff',
            '#800000', '#008000', '#000080', '#808000', '#800080', '#008080', '#c0c0c0', '#808080'
        ];

        const extendedColors = [
            '#ff4444', '#44ff44', '#4444ff', '#ffaa44', '#ff44aa', '#44aaff', '#aa44ff', '#44ffaa',
            '#ff8888', '#88ff88', '#8888ff', '#ffcc88', '#ff88cc', '#88ccff', '#cc88ff', '#88ffcc',
            '#ffcccc', '#ccffcc', '#ccccff', '#ffeecc', '#ffccee', '#cceeff', '#eeccff', '#ccffee',
            '#333333', '#666666', '#999999', '#bbbbbb', '#dddddd', '#f0f0f0', '#f8f8f8', '#fcfcfc'
        ];

        // Recent colors storage
        let recentTextColors = JSON.parse(localStorage.getItem('recentTextColors') || '[]');
        let recentBgColors = JSON.parse(localStorage.getItem('recentBgColors') || '[]');

        // Initialize color pickers
        function initializeColorPickers() {
            // Initialize common colors for both pickers
            createColorGrid('commonColors', commonColors, 'text');
            createColorGrid('commonBgColors', commonColors, 'bg');
            
            // Initialize extended colors for both pickers
            createColorGrid('extendedColors', extendedColors, 'text');
            createColorGrid('extendedBgColors', extendedColors, 'bg');
            
            // Load recent colors
            loadRecentColors();
        }

        function createColorGrid(containerId, colors, type) {
            const container = document.getElementById(containerId);
            container.innerHTML = '';
            
            colors.forEach(color => {
                const swatch = document.createElement('div');
                swatch.className = 'color-swatch';
                swatch.style.backgroundColor = color;
                swatch.title = color;
                swatch.onclick = () => applyColor(type, color);
                container.appendChild(swatch);
            });
        }

function toggleColorDropdown(pickerId) {
    // Close other dropdowns first
    document.querySelectorAll('.color-dropdown').forEach(dropdown => {
        if (dropdown.id !== pickerId) {
            dropdown.classList.remove('show');
        }
    });
    
    const dropdown = document.getElementById(pickerId);
    dropdown.classList.toggle('show');
}

function applyColor(type, color) {
    // Restore cursor position first
    restoreCursorPosition();
    
    // Ensure editor has focus
    const editor = document.getElementById('editor');
    editor.focus();
    
    if (type === 'text') {
        document.execCommand('foreColor', false, color);
        document.getElementById('textColorPreview').style.backgroundColor = color;
        addToRecentColors('text', color);
    } else {
        document.execCommand('backColor', false, color);
        document.getElementById('bgColorPreview').style.backgroundColor = color;
        addToRecentColors('bg', color);
    }
    
    // Close dropdown
    document.querySelectorAll('.color-dropdown').forEach(dropdown => {
        dropdown.classList.remove('show');
    });
    
    // Trigger auto-save
    triggerAutoSave();
}

function applyCustomColor(type, color) {
    if (type === 'text') {
        document.getElementById('customTextHex').value = color;
    } else {
        document.getElementById('customBgHex').value = color;
    }
    applyColor(type, color);
}
function applyCustomHex(type, hex) {
    // Validate hex color
    if (!/^#[0-9A-F]{6}$/i.test(hex)) {
        alert('Format de couleur invalide. Utilisez le format #RRGGBB');
        return;
    }
    
    if (type === 'text') {
        document.getElementById('customTextColor').value = hex;
    } else {
        document.getElementById('customBgColor').value = hex;
    }
    applyColor(type, hex);
}
        function addToRecentColors(type, color) {
            const recentColors = type === 'text' ? recentTextColors : recentBgColors;
            
            // Remove if already exists
            const index = recentColors.indexOf(color);
            if (index > -1) {
                recentColors.splice(index, 1);
            }
            
            // Add to beginning
            recentColors.unshift(color);
            
            // Keep only last 8 colors
            if (recentColors.length > 8) {
                recentColors.splice(8);
            }
            
            // Update storage and display
            if (type === 'text') {
                recentTextColors = recentColors;
                localStorage.setItem('recentTextColors', JSON.stringify(recentColors));
            } else {
                recentBgColors = recentColors;
                localStorage.setItem('recentBgColors', JSON.stringify(recentColors));
            }
            
            loadRecentColors();
        }

        function loadRecentColors() {
            loadRecentColorsForType('text', recentTextColors, 'recentTextColors');
            loadRecentColorsForType('bg', recentBgColors, 'recentBgColors');
        }

        function loadRecentColorsForType(type, colors, containerId) {
            const container = document.getElementById(containerId);
            // Keep the clear button
            const clearButton = container.querySelector('.clear-recent');
            container.innerHTML = '';
            container.appendChild(clearButton);
            
            colors.forEach(color => {
                const swatch = document.createElement('div');
                swatch.className = 'recent-color';
                swatch.style.backgroundColor = color;
                swatch.title = color;
                swatch.onclick = () => applyColor(type, color);
                container.appendChild(swatch);
            });
        }

        function clearRecentColors(type) {
            if (type === 'text') {
                recentTextColors = [];
                localStorage.removeItem('recentTextColors');
            } else {
                recentBgColors = [];
                localStorage.removeItem('recentBgColors');
            }
            loadRecentColors();
        }

        // Close dropdowns when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('.color-picker-container')) {
        const wasOpen = document.querySelector('.color-dropdown.show');
        document.querySelectorAll('.color-dropdown').forEach(dropdown => {
            dropdown.classList.remove('show');
        });
        
        // If a dropdown was open and we clicked outside, restore focus to editor
        if (wasOpen && savedRange) {
            setTimeout(() => {
                restoreCursorPosition();
            }, 50);
        }
    }
});
document.addEventListener('DOMContentLoaded', function() {
    const editor = document.getElementById('editor');
    
    // Save cursor position on various editor interactions
    editor.addEventListener('mouseup', saveCursorPosition);
    editor.addEventListener('keyup', function(e) {
            saveCursorPosition();
    });
    editor.addEventListener('focus', saveCursorPosition);
    
    // Initialize other functions
    initializeColorPickers();
});
        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', initializeColorPickers);        
    </script>
</body>
</html>