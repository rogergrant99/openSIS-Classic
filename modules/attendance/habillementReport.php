<?php

#**************************************************************************
#  openSIS is a free student information system for public and non-public 
#  schools from Open Solutions for Education, Inc. web: www.os4ed.com
#
#  Report: Students Compliant with Dress Code (Habillement)
#  Lists students by grade level who are compliant with dress code
#  for the selected week (students WITHOUT a non-compliant record)
#
#***************************************************************************************

include('../../RedirectModulesInc.php');

DrawBC("" . _attendance . " > Rapport Habillement Libre");

// Get current week or selected week
if (isset($_REQUEST['week_start'])) {
    $week_start = $_REQUEST['week_start'];
} else {
    $week_start = date('Y-m-d', strtotime('monday this week'));
}
$week_end = date('Y-m-d', strtotime('sunday this week', strtotime($week_start)));

// Handle week navigation
if (isset($_REQUEST['navigate'])) {
    if ($_REQUEST['navigate'] == 'prev') {
        $week_start = date('Y-m-d', strtotime($week_start . ' -7 days'));
    } elseif ($_REQUEST['navigate'] == 'next') {
        $week_start = date('Y-m-d', strtotime($week_start . ' +7 days'));
    } elseif ($_REQUEST['navigate'] == 'current') {
        $week_start = date('Y-m-d', strtotime('monday this week'));
    }
    $week_end = date('Y-m-d', strtotime('sunday this week', strtotime($week_start)));
}

// Format dates for display
$week_start_display = date('d/m/Y', strtotime($week_start));
$week_end_display = date('d/m/Y', strtotime($week_end));

echo '<div class="panel panel-default">';

// Header with week navigation
if(! $_REQUEST['_openSIS_PDF']){
    echo '<div class="panel-body">';
    echo '<div class="row no-print">';
    echo '<div class="col-md-12">';
    echo '<div class="text-center">';
    echo '<div class="btn-group m-b-20" role="group">';
    echo '<a href="Modules.php?modname=' . $_REQUEST['modname'] . '&navigate=prev&week_start=' . $week_start . '" class="btn btn-default">';
    echo '<i class="icon-arrow-left13"></i> Semaine précédente</a>';
    echo '<a href="Modules.php?modname=' . $_REQUEST['modname'] . '&navigate=current" class="btn btn-primary">';
    echo '<i class="icon-calendar"></i> Semaine actuelle</a>';
    echo '<a href="Modules.php?modname=' . $_REQUEST['modname'] . '&navigate=next&week_start=' . $week_start . '" class="btn btn-default">';
    echo 'Semaine suivante <i class="icon-arrow-right14"></i></a>';
    echo '</div>';
    echo '</div>';
    echo '<h4 class="text-center m-b-20"><strong>Rapport Habillement Libre - Semaine du ' . $week_start_display . ' au ' . $week_end_display . '</strong></h4>';
    echo '</div>';
    echo '</div>';
}

// Query to get compliant students (students WITHOUT a non-compliant record)
// This gets all enrolled students who don't have a non-compliant entry for this week
$sql = "SELECT 
    s.STUDENT_ID,
    s.FIRST_NAME,
    s.LAST_NAME,
    CONCAT(s.LAST_NAME, ' ', s.FIRST_NAME) AS FULL_NAME,
    se.GRADE_ID,
    sg.TITLE AS GRADE_TITLE,
    sg.SHORT_NAME AS GRADE_SHORT,
    sg.SORT_ORDER,
    se.START_DATE,
    se.END_DATE
FROM students s
INNER JOIN student_enrollment se ON s.STUDENT_ID = se.STUDENT_ID 
    AND se.SYEAR = '" . UserSyear() . "'
    AND se.SCHOOL_ID = '" . UserSchool() . "'
    AND '" . $week_start . "' BETWEEN se.START_DATE AND COALESCE(se.END_DATE, '" . $week_start . "')
LEFT JOIN school_gradelevels sg ON se.GRADE_ID = sg.ID
LEFT JOIN habillement h ON s.STUDENT_ID = h.STUDENT_ID
    AND h.SCHOOL_ID = '" . UserSchool() . "'
    AND h.SYEAR = '" . UserSyear() . "'
    AND h.WEEK_START = '" . $week_start . "'
    AND h.COMPLIANT = 'N'
WHERE (s.is_disable IS NULL OR s.is_disable = '' OR s.is_disable = '0' OR s.is_disable = 'N' OR s.is_disable = 'No')
    AND h.STUDENT_ID IS NULL
    AND sg.SORT_ORDER >= 8
ORDER BY sg.SORT_ORDER, s.LAST_NAME, s.FIRST_NAME";

$compliant_RET = DBGet(DBQuery($sql));

// Count total compliant students
$total_count = is_countable($compliant_RET) ? count($compliant_RET) : 0;

// Display summary
if(! $_REQUEST['_openSIS_PDF']){
    echo '<div class="row m-b-10 no-print">';
    echo '<div class="col-md-12">';
    echo '<div class="alert alert-success">';
    echo '<i class=""></i> <strong>' . $total_count . '</strong> étudiants pour cette semaine';
    echo '</div>';
    echo '</div>';
    echo '</div>';
}

if ($total_count > 0) {
    // Group students by grade level
    $students_by_grade = array();
    foreach ($compliant_RET as $student) {
        $grade_id = $student['GRADE_ID'];
        if (!isset($students_by_grade[$grade_id])) {
            $students_by_grade[$grade_id] = array(
                'GRADE_TITLE' => $student['GRADE_TITLE'] ? $student['GRADE_TITLE'] : 'Non assigné',
                'GRADE_SHORT' => $student['GRADE_SHORT'] ? $student['GRADE_SHORT'] : 'N/A',
                'SORT_ORDER' => $student['SORT_ORDER'] ? $student['SORT_ORDER'] : 9999,
                'STUDENTS' => array()
            );
        }
        $students_by_grade[$grade_id]['STUDENTS'][] = $student;
    }
    
    // Sort by grade sort order
    uasort($students_by_grade, function($a, $b) {
        return $a['SORT_ORDER'] - $b['SORT_ORDER'];
    });
    
    // Display students grouped by grade
    foreach ($students_by_grade as $grade_id => $grade_data) {
        $grade_count = count($grade_data['STUDENTS']);
        
        echo '<div class="panel panel-flat grade-section" id="grade-' . $grade_id . '">';
        echo '<div class="panel-heading" style="background-color:rgb(214, 216, 224);">';
        echo '<div class="clearfix">';
        echo '<h5 class="panel-title pull-left">';
        echo '<strong>' . $grade_data['GRADE_TITLE'] . '</strong>';
        echo ' <span class="badge" style="background-color:rgb(214, 216, 224);">' . $grade_count . ' étudiant(s)</span>';
        echo '</h5>';
        if(! $_REQUEST['_openSIS_PDF']){
            echo '<div class="pull-right  no-print">';
            echo '<button onclick="printGradeLevel(\'' . $grade_id . '\', \'' . addslashes($grade_data['GRADE_TITLE']) . '\')" class="btn btn-xs btn-success no-print">';
            echo '<i class=""></i> Imprimer ce niveau';
            echo '</button>';
            echo '</div>';
        }
        echo '</div>';
        echo '</div>';
        
        echo '<div class="table-responsive">';
        echo '<table class="table table-striped table-hover">';
        echo '<thead>';
        echo '<tr>';
        echo '<th style="width: 50px;"></th>';
        echo '<th>Nom</th>';
        echo '<th>Prénom</th>';
        echo '<th>ID Étudiant</th>';
        // echo '<th>Date</th>';
        echo '<th class="text-center"></th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        $counter = 1;
        foreach ($grade_data['STUDENTS'] as $student) {
            echo '<tr>';
            echo '<td><i>' . $counter . '</i></td>';
            echo '<td><strong>' . $student['LAST_NAME'] . '</strong></td>';
            echo '<td><strong>' . $student['FIRST_NAME'] . '</strong></td>';
            echo '<td>' . $student['STUDENT_ID'] . '</td>';
            // echo '<td>' . date('d/m/y', $student['CREATED_AT']) . '</td>';
            echo '<td>';
            echo '</td>';
            echo '</tr>';
            $counter++;
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '</div>'; // .table-responsive
        echo '</div>'; // .panel
    }
    
} else {
    echo '<div class="alert alert-info text-center">';
    echo '<i class="icon-info"></i> Aucun étudiant trouvé pour cette semaine.';
    echo '</div>';
}

echo '</div>'; // .panel-body
echo '</div>'; // .panel

// Handle CSV export
if (isset($_REQUEST['export']) && $_REQUEST['export'] == 'csv') {
    $week_start_export = $_REQUEST['week_start'];
    $week_end_export = date('Y-m-d', strtotime('sunday this week', strtotime($week_start_export)));
    
    $sql = "SELECT 
        s.STUDENT_ID,
        s.FIRST_NAME,
        s.LAST_NAME,
        se.GRADE_ID,
        sg.TITLE AS GRADE_TITLE,
        sg.SHORT_NAME AS GRADE_SHORT,
        sg.SORT_ORDER
    FROM students s
    INNER JOIN student_enrollment se ON s.STUDENT_ID = se.STUDENT_ID 
        AND se.SYEAR = '" . UserSyear() . "'
        AND se.SCHOOL_ID = '" . UserSchool() . "'
        AND '" . $week_start_export . "' BETWEEN se.START_DATE AND COALESCE(se.END_DATE, '" . $week_start_export . "')
    LEFT JOIN school_gradelevels sg ON se.GRADE_ID = sg.ID
    LEFT JOIN habillement h ON s.STUDENT_ID = h.STUDENT_ID
        AND h.SCHOOL_ID = '" . UserSchool() . "'
        AND h.SYEAR = '" . UserSyear() . "'
        AND h.WEEK_START = '" . $week_start_export . "'
        AND h.COMPLIANT = 'N'
    WHERE (s.is_disable IS NULL OR s.is_disable = '' OR s.is_disable = '0' OR s.is_disable = 'N' OR s.is_disable = 'No')
        AND h.STUDENT_ID IS NULL
        AND sg.SORT_ORDER >= 8
    ORDER BY sg.SORT_ORDER, s.LAST_NAME, s.FIRST_NAME";
    
    $export_data = DBGet(DBQuery($sql));
    
    if (is_countable($export_data) && count($export_data) > 0) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=habillement_conforme_' . $week_start_export . '.csv');
        
        $output = fopen('php://output', 'w');
        
        // Add BOM for UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Headers
        fputcsv($output, array('ID Étudiant', 'Nom', 'Prénom', 'Niveau', 'Semaine du', 'Semaine au'), ';');
        
        // Data
        foreach ($export_data as $row) {
            fputcsv($output, array(
                $row['STUDENT_ID'],
                $row['LAST_NAME'],
                $row['FIRST_NAME'],
                $row['GRADE_TITLE'] ? $row['GRADE_TITLE'] : 'Non assigné',
                date('d/m/Y', strtotime($week_start_export)),
                date('d/m/Y', strtotime($week_end_export))
            ), ';');
        }
        
        fclose($output);
        exit;
    }
}

?>

<script>
function printGradeLevel(gradeId, gradeTitle) {
    // Ouvrir une fenêtre maximisée
    var printWindow = window.open('', '_blank', 'fullscreen=yes,scrollbars=yes');

    // Get the specific grade section
    var gradeSection = document.getElementById('grade-' + gradeId);
    if (!gradeSection) return;
    
    // Clone the section to avoid modifying the original
    var clonedSection = gradeSection.cloneNode(true);
    
    // Remove all elements with 'no-print' class from the clone
    var noPrintElements = clonedSection.querySelectorAll('.no-print');
    noPrintElements.forEach(function(element) {
        element.remove();
    });
    
    // // Create a new window for printing
    // var printWindow = window.open('', '_blank');
    
    // Get dates from PHP
    var weekStartDisplay = '<?php echo addslashes($week_start_display); ?>';
    var weekEndDisplay = '<?php echo addslashes($week_end_display); ?>';
    
    // Build the HTML content
    var htmlContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Rapport Habillement Libre - ${gradeTitle}</title>
            <style>
                /* ========================================
                   BASIC LAYOUT
                   ======================================== */
                body {
                    font-family: Arial, sans-serif;
                    margin: 20px;
                    padding: 0;
                }
                
                /* ========================================
                   PRINT BUTTONS
                   ======================================== */
                           
                        .print-toolbar {
                            background: #f8f9fa;
                            border-bottom: 2px solid #dee2e6;
                            padding: 15px 20px;
                            position: sticky;
                            top: 0;
                            z-index: 1000;
                            display: flex;
                            justify-content: center;  /* CENTERED */
                            align-items: center;
                            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                        }
                        
                        .print-toolbar h2 {
                            margin: 0;
                            font-size: 1.5em;
                            color: #333;
                        }
                        
                        .print-toolbar button {
                            background: #5c96d4ff;
                            color: white;
                            border: none;
                            padding: 10px 20px;
                            border-radius: 4px;
                            cursor: pointer;
                            font-size: 14px;
                            margin-left: 10px;
                            font-weight: 500;
                        }
                        
                        .print-toolbar button:hover {
                            background: #558ac2ff;
                        }
                        
                        .print-toolbar button.close-btn {
                            background: #dc3545;
                        }
                        
                        .print-toolbar button.close-btn:hover {
                            background: #c82333;
                        }
                        
                        
                .print-buttons {
                    text-align: center;
                    margin-bottom: 20px;
                    padding: 15px;
                    background-color: #f8f9fa;
                    border-radius: 4px;
                }
                
                .print-buttons button {
                    padding: 10px 20px;
                    margin: 0 10px;
                    font-size: 14px;
                    font-weight: 500;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                    transition: all 0.2s ease;
                }
                
                .btn-print {
                    background-color: #5090C1;
                    color: white;
                }
                
                .btn-print:hover {
                    background-color: #1677c6;
                }
                
                .btn-close {
                    background-color: #d90e0eff;
                    color: white;
                }
                
                .btn-close:hover {
                    background-color: #b21e1eff;
                }
                
                h3 {
                    color: #333;
                    margin-bottom: 20px;
                }
                
                /* ========================================
                   PANEL & HEADER
                   ======================================== */
                .panel-heading {
                    background-color: rgb(214, 216, 224);
                    padding: 15px;
                    margin-bottom: 0;
                    border: 1px solid #000;
                }
                
                .panel-heading h5 {
                    margin: 0;
                    font-size: 18px;
                    color: #000;
                }
                
                /* ========================================
                   TABLE STYLING
                   ======================================== */
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 0;
                }
                
                table, th, td {
                    border: 1px solid #000;
                }
                
                th {
                    background-color: #f5f5f5;
                    padding: 10px;
                    text-align: left;
                    font-weight: bold;
                }
                
                td {
                    padding: 8px;
                }
                
                tr:nth-child(even) {
                    background-color: #f9f9f9;
                }
                
                /* ========================================
                   UTILITIES
                   ======================================== */
                .badge {
                    background-color: rgb(214, 216, 224);
                    color: #000;
                    padding: 4px 8px;
                    border-radius: 3px;
                    font-size: 12px;
                }
                
                .pull-left {
                    float: left;
                }
                
                .clearfix::after {
                    content: "";
                    display: table;
                    clear: both;
                }
                
                /* ========================================
                   PRINT STYLES
                   ======================================== */
                @media print {
                    /* Masquer TOUS les éléments non nécessaires */
                    .btn,
                    button,
                    .print-toolbar,
                    nav,
                    header,
                    footer,
                    .sidebar,
                    .breadcrumb,
                    #menu,
                    #header,
                    #footer,
                    h3 {
                        display: none !important;
                    }
                    
                    /* Configuration de la page */
                    @page {
                        size: A4 landscape;
                        margin: 8mm 5mm;
                    }
                        </style>
        </head>
        <body>
            <div class="print-toolbar">
                <button  onclick="window.print()">
                    🖨️ Imprimer
                </button>
                <button class="close-btn" onclick="window.close()">
                    ✕ Fermer
                </button>
            </div>
            <h3>Rapport Habillement Libre - Semaine du ${weekStartDisplay} au ${weekEndDisplay}</h3>
            ${clonedSection.outerHTML}
        </body>
        </html>
    `;
    
    printWindow.document.write(htmlContent);
    printWindow.document.close();
}
</script>

<style>
/* ========================================
   MAIN LAYOUT & PANELS
   ======================================== */

.panel.panel-default {
    border: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    border-radius: 8px;
}

.panel-body {
    padding: 5px;
}

.panel-flat {
    border: none;
    margin-bottom: 25px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    border-radius: 6px;
    overflow: hidden;
}

.panel-heading {
    background-color: rgb(214, 216, 224);
    border-bottom: none;
    padding: 5px 10px;
}

.panel-heading h5 {
    margin: 0;
    font-size: 20px;
    color: #000;
}

/* ========================================
   TABLE STYLING
   ======================================== */

.table-responsive {
    border: none;
}

.table {
    margin-bottom: 0;
    border-collapse: separate;
    border-spacing: 0;
}

.table > thead > tr > th {
    font-weight: 600;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #495057;
    border-top: none;
    padding: 12px 15px;
}

.table > tbody > tr > td {
    padding: 12px 15px;
    vertical-align: middle;
    border-top: 1px solid #f0f0f0;
    font-size: 14px;
    color: #333;
    width: 150px;
}

/* First column (number) styling */
.table > tbody > tr > td:first-child,
.table > thead > tr > th:first-child {
    color: #666869;
    font-weight: 100;
    font-size: 12px;
    text-align: center;
}

/* Student name styling */
.table > tbody > tr > td:nth-child(2) strong {
    font-weight: 600;
}

/* Student ID styling */
.table > tbody > tr > td:nth-child(4) {
    font-family: 'Courier New', monospace;
    color: #6c757d;
    font-size: 13px;
}

.table-hover > tbody > tr:hover {
    transition: background-color 0.2s ease;
}

/* ========================================
   BUTTONS & NAVIGATION
   ======================================== */

.btn-group .btn {
    padding: 8px 16px;
    font-weight: 500;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.btn-group .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.btn-xs {
    padding: 4px 10px;
    font-size: 12px;
    border-radius: 3px;
    font-weight: 500;
}

.btn-success {
    padding: 4px 8px;
    font-size: 12px;
    font-weight: 500;
    color: #fcf8f8 !important;
    text-align: center;
    white-space: nowrap;
    vertical-align: baseline;
    border-radius: 3px; 
    background-color: rgb(80, 144, 193)!important;
    border: none !important;
}

.btn-success:hover {
    color: #f2eeee !important;
    background-color: #1677c6 !important;  
}

/* ========================================
   BADGES & ALERTS
   ======================================== */

.badge {
    display: inline-block;
    padding: 4px 8px;
    font-size: 12px;
    font-weight: 500;
    line-height: 1;
    color: #000;
    text-align: center;
    white-space: nowrap;
    vertical-align: baseline;
    border-radius: 3px;
    margin-left: 8px;
}

.alert-info {
    background-color: #5090C1;
    border: 1px solid #90CAF9;
    border-left: 4px solid #2196F3;
    color: #1565C0;
    border-radius: 4px;
}

.alert-success {
    background-color: #5090C1 !important;
    border: 1px solid #5090C1;
    border-left: 1px solid #5090C1;
    color: #fff;
    border-radius: 4px;
}

/* ========================================
   TYPOGRAPHY & LAYOUT
   ======================================== */

h4.text-center {
    color: #212529;
    font-size: 20px;
    margin-bottom: 25px;
}

.pull-left {
    float: left;
}

.pull-right {
    float: right;
}

.clearfix::after {
    content: "";
    display: table;
    clear: both;
}

/* ========================================
   RESPONSIVE DESIGN
   ======================================== */

@media (max-width: 768px) {
    .table > thead > tr > th,
    .table > tbody > tr > td {
        padding: 8px 10px;
        font-size: 13px;
    }
    
    .panel-heading {
        padding: 12px 15px;
    }
    
    .panel-body {
        padding: 15px;
    }
}

/* ========================================
   PRINT STYLES
   ======================================== */

@media print {
    .no-print,
    .btn-group,
    .panel-body > .row.no-print,
    .text-center.m-t-20.m-b-20,
    body > *:not(.panel),
    nav,
    .sidebar,
    .navbar,
    .breadcrumb,
    footer,
    button,
    input[type="submit"] {
        display: none !important;
    }
    
    table th:last-child,
    table td:last-child {
        display: none !important;
    }
    
    body {
        margin: 0;
        padding: 20px;
        background: white !important;
    }
    
    .panel {
        box-shadow: none !important;
        border: none !important;
        page-break-inside: avoid;
    }
    
    .panel-flat {
        page-break-inside: avoid;
        border: 1px solid #000 !important;
        margin-bottom: 15px;
    }
    
    .panel-heading {
        background-color: rgb(214, 216, 224) !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    .table {
        page-break-inside: auto;
    }
    
    .table tr {
        page-break-inside: avoid;
        page-break-after: auto;
    }
    
    .table thead {
        display: table-header-group;
    }
    
    .table tbody {
        display: table-row-group;
    }
    
    .table,
    .table th,
    .table td {
        border: 1px solid #000 !important;
    }
    
    .table-striped > tbody > tr:nth-of-type(odd) {
        background-color: #f5f5f5 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    h4, h5 {
        color: #000 !important;
    }
}
</style>