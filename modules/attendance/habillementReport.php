<?php

#**************************************************************************
#  openSIS is a free student information system for public and non-public 
#  schools from Open Solutions for Education, Inc. web: www.os4ed.com
#
#  Report: Students Non-Compliant with Dress Code (Habillement)
#  Lists students by grade level who have a non-compliant entry
#  in the habillement table for the selected week (COMPLIANT = 'N')
#
#  ENHANCED: Teachers can now add/edit habillement compliance entries
#  ENHANCED: Non-teachers can now see C, M, T status for non-compliant students
#
#***************************************************************************************

include('../../RedirectModulesInc.php');

DrawBC("" . _attendance . " > Rapport Habillement Non-Conforme");

// Handle form submission for teachers BEFORE displaying the page
if(User('PROFILE') == 'teacher' && isset($_POST['save_habillement']) && $_POST['save_habillement'] == '1'){
    $week_start = isset($_POST['week_start']) ? $_POST['week_start'] : '';
    
    if(!empty($week_start)){
        $week_end = date('Y-m-d', strtotime('sunday this week', strtotime($week_start)));
        
        $success_count = 0;
        $error_count = 0;
        $deleted_count = 0;
        
        // Get list of students that were modified (sent via hidden input)
        $modified_students = isset($_POST['modified_students']) ? explode(',', $_POST['modified_students']) : array();
        $modified_students = array_filter(array_map('intval', $modified_students));
        
        if(!empty($modified_students)){
            foreach($modified_students as $student_id){
                if($student_id <= 0) continue;
                
                $compliant = isset($_POST['compliant'][$student_id]) ? $_POST['compliant'][$student_id] : 'Y';
                
                // If student is marked as Conforme (Y), delete any existing non-compliant entry
                if($compliant === 'Y'){
                    $delete_sql = "DELETE FROM habillement 
                                  WHERE STUDENT_ID = " . $student_id . " 
                                  AND SCHOOL_ID = '" . UserSchool() . "'
                                  AND SYEAR = '" . UserSyear() . "'
                                  AND WEEK_START = '" . $week_start . "'";
                    
                    if(DBQuery($delete_sql)){
                        $deleted_count++;
                        $success_count++;
                    } else {
                        $error_count++;
                    }
                    continue;
                }
                
                // If Non-conforme (N), save/update the entry
                $compliant = 'N';
                $c = (isset($_POST['c'][$student_id]) && $_POST['c'][$student_id] == 'Y') ? 'Y' : 'N';
                $m = (isset($_POST['m'][$student_id]) && $_POST['m'][$student_id] == 'Y') ? 'Y' : 'N';
                $t = (isset($_POST['t'][$student_id]) && $_POST['t'][$student_id] == 'Y') ? 'Y' : 'N';
                
                // Check if entry exists
                $check_sql = "SELECT ID FROM habillement 
                             WHERE STUDENT_ID = " . $student_id . " 
                             AND SCHOOL_ID = '" . UserSchool() . "'
                             AND SYEAR = '" . UserSyear() . "'
                             AND WEEK_START = '" . $week_start . "'";
                
                $existing = DBGet(DBQuery($check_sql));
                
                if($existing && count($existing) > 0){
                    // Update existing entry - UPDATED_AT set to NOW()
                    $update_sql = "UPDATE habillement SET 
                                  COMPLIANT = '" . $compliant . "',
                                  C = '" . $c . "',
                                  M = '" . $m . "',
                                  T = '" . $t . "',
                                  WEEK_END = '" . $week_end . "',
                                  UPDATED_AT = NOW(),
                                  UPDATED_BY = '" . User('STAFF_ID') . "'
                                  WHERE ID = " . intval($existing[1]['ID']);
                    
                    if(DBQuery($update_sql)){
                        $success_count++;
                    } else {
                        $error_count++;
                    }
                } else {
                    // Insert new entry - CREATED_AT and UPDATED_AT both set to NOW()
                    $insert_sql = "INSERT INTO habillement 
                                  (STUDENT_ID, SCHOOL_ID, SYEAR, WEEK_START, WEEK_END, COMPLIANT, C, M, T, CREATED_AT, CREATED_BY, UPDATED_AT, UPDATED_BY)
                                  VALUES (
                                      " . $student_id . ",
                                      '" . UserSchool() . "',
                                      '" . UserSyear() . "',
                                      '" . $week_start . "',
                                      '" . $week_end . "',
                                      '" . $compliant . "',
                                      '" . $c . "',
                                      '" . $m . "',
                                      '" . $t . "',
                                      NOW(),
                                      '" . User('STAFF_ID') . "',
                                      NOW(),
                                      '" . User('STAFF_ID') . "'
                                  )";
                    
                    if(DBQuery($insert_sql)){
                        $success_count++;
                    } else {
                        $error_count++;
                    }
                }
            }
        }
        
        // Show success message
        if($error_count == 0 && $success_count > 0){
            $message = $success_count . ' enregistrement(s) sauvegardé(s) avec succès!';
            if($deleted_count > 0){
                $message .= ' (' . $deleted_count . ' marqué(s) comme conforme)';
            }
            echo '<div class="alert alert-success alert-styled-left alert-dismissible">';
            echo '<button type="button" class="close" data-dismiss="alert"><span>×&nbsp&nbsp&nbsp</span></button>';
            echo '<i class="icon-checkmark-circle"></i> ' . $message;
            echo '</div>';
        } elseif($error_count > 0){
            echo '<div class="alert alert-warning alert-styled-left alert-dismissible">';
            echo '<button type="button" class="close" data-dismiss="alert"><span>×&nbsp&nbsp&nbsp</span></button>';
            echo '<i class="icon-warning"></i> ' . $success_count . ' succès, ' . $error_count . ' erreur(s)';
            echo '</div>';
        } elseif($success_count == 0 && $error_count == 0){
            echo '<div class="alert alert-info alert-styled-left alert-dismissible">';
            echo '<button type="button" class="close" data-dismiss="alert"><span>×&nbsp&nbsp&nbsp</span></button>';
            echo '<i class="icon-info"></i> Aucune modification détectée.';
            echo '</div>';
        }
    }
}

if(User('PROFILE') == 'teacher'){
    edit_habillement();
    return;
}

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
    echo '<h4 class="text-center m-b-20"><strong>Rapport Habillement Non-Conforme - Semaine du ' . $week_start_display . ' au ' . $week_end_display . '</strong></h4>';
    echo '</div>';
    echo '</div>';
}

// Query to get non-compliant students WITH their C, M, T status
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
    se.END_DATE,
    h.CREATED_AT,
    h.C,
    h.M,
    h.T
FROM students s
INNER JOIN student_enrollment se ON s.STUDENT_ID = se.STUDENT_ID 
    AND se.SYEAR = '" . UserSyear() . "'
    AND se.SCHOOL_ID = '" . UserSchool() . "'
    AND '" . $week_start . "' BETWEEN se.START_DATE AND COALESCE(se.END_DATE, '" . $week_start . "')
LEFT JOIN school_gradelevels sg ON se.GRADE_ID = sg.ID
INNER JOIN habillement h ON s.STUDENT_ID = h.STUDENT_ID
    AND h.SCHOOL_ID = '" . UserSchool() . "'
    AND h.SYEAR = '" . UserSyear() . "'
    AND h.WEEK_START = '" . $week_start . "'
    AND h.COMPLIANT = 'N'
WHERE (s.is_disable IS NULL OR s.is_disable = '' OR s.is_disable = '0' OR s.is_disable = 'N' OR s.is_disable = 'No')
    AND sg.SORT_ORDER >= 8
ORDER BY sg.SORT_ORDER, s.LAST_NAME, s.FIRST_NAME";

$no_entry_RET = DBGet(DBQuery($sql));

// Count total non-compliant students
$total_count = is_countable($no_entry_RET) ? count($no_entry_RET) : 0;

// Display summary
if(! $_REQUEST['_openSIS_PDF'] && $total_count){
    echo '<div class="row m-b-10 no-print">';
    echo '<div class="col-md-12">';
    echo '<div class="alert alert-danger">';
    echo '<i class=""></i> <strong>' . $total_count . '</strong> étudiants non-conformes pour cette semaine';
    echo '</div>';
    echo '</div>';
    echo '</div>';
}

if ($total_count > 0) {
    // Group students by grade level
    $students_by_grade = array();
    foreach ($no_entry_RET as $student) {
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
        echo '<th>Nom Complet</th>';
        echo '<th>ID Étudiant</th>';
        echo '<th class="text-center" style="width: 80px;">C<br><small>Comportement</small></th>';
        echo '<th class="text-center" style="width: 80px;">M<br><small>Matériel</small></th>';
        echo '<th class="text-center" style="width: 80px;">T<br><small>Travaux</small></th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        $counter = 1;
        foreach ($grade_data['STUDENTS'] as $student) {
            $full_name = $student['FIRST_NAME'] . ' ' . $student['LAST_NAME'];
            echo '<tr>';
            echo '<td><i>' . $counter . '</i></td>';
            echo '<td><strong>' . htmlspecialchars($full_name) . '</strong></td>';
            echo '<td>' . $student['STUDENT_ID'] . '</td>';
            
            // Display C status
            echo '<td class="text-center">';
            if($student['C'] == 'Y'){
                echo '<span class="status-indicator status-yes">✓</span>';
            } else {
                echo '<span class="status-indicator status-no">—</span>';
            }
            echo '</td>';
            
            // Display M status
            echo '<td class="text-center">';
            if($student['M'] == 'Y'){
                echo '<span class="status-indicator status-yes">✓</span>';
            } else {
                echo '<span class="status-indicator status-no">—</span>';
            }
            echo '</td>';
            
            // Display T status
            echo '<td class="text-center">';
            if($student['T'] == 'Y'){
                echo '<span class="status-indicator status-yes">✓</span>';
            } else {
                echo '<span class="status-indicator status-no">—</span>';
            }
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
    echo '<div class="alert alert-success text-center">';
    echo '<i class="icon-checkmark"></i> Aucun étudiant non-conforme pour cette semaine.';
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
        sg.SORT_ORDER,
        h.CREATED_AT,
        h.C,
        h.M,
        h.T
    FROM students s
    INNER JOIN student_enrollment se ON s.STUDENT_ID = se.STUDENT_ID 
        AND se.SYEAR = '" . UserSyear() . "'
        AND se.SCHOOL_ID = '" . UserSchool() . "'
        AND '" . $week_start_export . "' BETWEEN se.START_DATE AND COALESCE(se.END_DATE, '" . $week_start_export . "')
    LEFT JOIN school_gradelevels sg ON se.GRADE_ID = sg.ID
    INNER JOIN habillement h ON s.STUDENT_ID = h.STUDENT_ID
        AND h.SCHOOL_ID = '" . UserSchool() . "'
        AND h.SYEAR = '" . UserSyear() . "'
        AND h.WEEK_START = '" . $week_start_export . "'
        AND h.COMPLIANT = 'N'
    WHERE (s.is_disable IS NULL OR s.is_disable = '' OR s.is_disable = '0' OR s.is_disable = 'N' OR s.is_disable = 'No')
        AND sg.SORT_ORDER >= 8
    ORDER BY sg.SORT_ORDER, s.LAST_NAME, s.FIRST_NAME";
    
    $export_data = DBGet(DBQuery($sql));
    
    if (is_countable($export_data) && count($export_data) > 0) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=habillement_non_conforme_' . $week_start_export . '.csv');
        
        $output = fopen('php://output', 'w');
        
        // Add BOM for UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Headers
        fputcsv($output, array('ID Étudiant', 'Nom Complet', 'Niveau', 'Semaine du', 'Semaine au', 'C', 'M', 'T'), ';');
        
        // Data
        foreach ($export_data as $row) {
            $full_name = $row['FIRST_NAME'] . ' ' . $row['LAST_NAME'];
            fputcsv($output, array(
                $row['STUDENT_ID'],
                $full_name,
                $row['GRADE_TITLE'] ? $row['GRADE_TITLE'] : 'Non assigné',
                date('d/m/Y', strtotime($week_start_export)),
                date('d/m/Y', strtotime($week_end_export)),
                $row['C'] == 'Y' ? 'Oui' : 'Non',
                $row['M'] == 'Y' ? 'Oui' : 'Non',
                $row['T'] == 'Y' ? 'Oui' : 'Non'
            ), ';');
        }
        
        fclose($output);
        exit;
    }
}

// ========================================
// TEACHER FUNCTIONS
// ========================================

function edit_habillement(){
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
    echo '<div class="panel-body">';
    
    // Header with week navigation
    echo '<div class="row no-print m-b-20">';
    echo '<div class="col-md-12">';
    echo '<div class="text-center">';
    echo '<div class="btn-group" role="group">';
    echo '<a href="Modules.php?modname=' . $_REQUEST['modname'] . '&navigate=prev&week_start=' . $week_start . '" class="btn btn-default">';
    echo '<i class="icon-arrow-left13"></i> Semaine précédente</a>';
    echo '<a href="Modules.php?modname=' . $_REQUEST['modname'] . '&navigate=current" class="btn btn-primary">';
    echo '<i class="icon-calendar"></i> Semaine actuelle</a>';
    echo '<a href="Modules.php?modname=' . $_REQUEST['modname'] . '&navigate=next&week_start=' . $week_start . '" class="btn btn-default">';
    echo 'Semaine suivante <i class="icon-arrow-right14"></i></a>';
    echo '</div>';
    echo '</div>';
    echo '<h4 class="text-center m-t-20"><strong>Gestion Habillement - Semaine du ' . $week_start_display . ' au ' . $week_end_display . '</strong></h4>';
    echo '</div>';
    echo '</div>';

    // Get students for this teacher
    $extra = array();
    $extra['SELECT'] = ',s.STUDENT_ID AS STUDENT_ID';
    $extra['ID'] = CpvId(); // Get current period value ID (class)
    // Filter out disabled students
    $extra['WHERE'] = "AND (s.is_disable IS NULL OR s.is_disable = '' OR s.is_disable = '0' OR s.is_disable = 'N' OR s.is_disable = 'No')";
    
    $stu_RET = GetStuListAttn($extra);

    if (!$stu_RET || count($stu_RET) == 0) {
        echo '<div class="alert alert-info">';
        echo '<i class="icon-info"></i> Aucun étudiant trouvé pour votre classe.';
        echo '</div>';
        echo '</div></div>';
        return;
    }

    // Get existing habillement entries for this week
    $student_ids = array_column($stu_RET, 'STUDENT_ID');
    $student_ids_str = implode(',', $student_ids);

    $sql = "SELECT 
        h.STUDENT_ID,
        h.COMPLIANT,
        h.C,
        h.M,
        h.T,
        h.CREATED_AT,
        h.UPDATED_AT
    FROM habillement h
    WHERE h.STUDENT_ID IN (" . $student_ids_str . ")
        AND h.SCHOOL_ID = '" . UserSchool() . "'
        AND h.SYEAR = '" . UserSyear() . "'
        AND h.WEEK_START = '" . $week_start . "'";

    $habillement_data = DBGet(DBQuery($sql));
    
    // Index by student ID for easy lookup
    $habillement_by_student = array();
    if ($habillement_data) {
        foreach ($habillement_data as $row) {
            $habillement_by_student[$row['STUDENT_ID']] = $row;
        }
    }

    // Display form
    echo '<form id="habillementForm" method="post" action="Modules.php?modname=' . $_REQUEST['modname'] . '&week_start=' . $week_start . '">';
    echo '<input type="hidden" name="save_habillement" value="1">';
    echo '<input type="hidden" name="week_start" value="' . $week_start . '">';
    
    echo '<div class="table-responsive">';
    echo '<table class="table table-striped table-hover">';
    echo '<thead>';
    echo '<tr>';
    echo '<th style="width: 50px;">#</th>';
    echo '<th>Nom complet</th>';
    echo '<th>ID Étudiant</th>';
    echo '<th style="width: 150px;">Statut</th>';
    echo '<th style="width: 100px; text-align: center;">C<br><small>Comportement</small></th>';
    echo '<th style="width: 100px; text-align: center;">M<br><small>Matériel oublié</small></th>';
    echo '<th style="width: 100px; text-align: center;">T<br><small>Travaux non-faits</small></th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    $counter = 1;
    foreach ($stu_RET as $student) {
        $student_id = $student['STUDENT_ID'];
        $full_name = $student['FIRST_NAME'] . ' ' . $student['LAST_NAME'];
        
        // Get existing data - default to 'Y' (Conforme)
        $existing = isset($habillement_by_student[$student_id]) ? $habillement_by_student[$student_id] : null;
        $compliant = $existing ? $existing['COMPLIANT'] : 'Y';
        $c_checked = $existing && $existing['C'] == 'Y' ? true : false;
        $m_checked = $existing && $existing['M'] == 'Y' ? true : false;
        $t_checked = $existing && $existing['T'] == 'Y' ? true : false;

        echo '<tr class="student-row" data-student-id="' . $student_id . '">';
        echo '<td>' . $counter . '</td>';
        echo '<td><strong>' . htmlspecialchars($full_name) . '</strong></td>';
        echo '<td>' . $student_id . '</td>';
        echo '<td>';
        echo '<select class="form-control compliant-select" name="compliant[' . $student_id . ']" data-student-id="' . $student_id . '">';
        echo '<option value="Y"' . ($compliant == 'Y' ? ' selected' : '') . '>✓ Conforme</option>';
        echo '<option value="N"' . ($compliant == 'N' ? ' selected' : '') . '>✗ Non-conforme</option>';
        echo '</select>';
        echo '</td>';
        echo '<td style="text-align: center;">';
        echo '<input type="checkbox" class="reason-checkbox" name="c[' . $student_id . ']" value="Y" ';
        echo 'data-student-id="' . $student_id . '" data-type="C" ';
        echo ($c_checked ? 'checked ' : '');
        echo 'style="' . ($compliant != 'N' ? 'opacity: 0.3; pointer-events: none;' : '') . '">';
        echo '</td>';
        echo '<td style="text-align: center;">';
        echo '<input type="checkbox" class="reason-checkbox" name="m[' . $student_id . ']" value="Y" ';
        echo 'data-student-id="' . $student_id . '" data-type="M" ';
        echo ($m_checked ? 'checked ' : '');
        echo 'style="' . ($compliant != 'N' ? 'opacity: 0.3; pointer-events: none;' : '') . '">';
        echo '</td>';
        echo '<td style="text-align: center;">';
        echo '<input type="checkbox" class="reason-checkbox" name="t[' . $student_id . ']" value="Y" ';
        echo 'data-student-id="' . $student_id . '" data-type="T" ';
        echo ($t_checked ? 'checked ' : '');
        echo 'style="' . ($compliant != 'N' ? 'opacity: 0.3; pointer-events: none;' : '') . '">';
        echo '</td>';
        echo '</tr>';

        $counter++;
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';

    echo '<div class="text-center m-t-20">';
    echo '<button type="button" id="saveButton" class="btn btn-success btn-lg">';
    echo '<i class="icon-checkmark"></i> Enregistrer toutes les modifications';
    echo '</button>';
    echo '</div>';

    echo '</form>';
    echo '</div></div>';

    // Add JavaScript for form handling
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Track modified students
        var modifiedStudents = new Set();
        
        // Store initial state for each student
        var initialState = {};
        $('.student-row').each(function() {
            var studentId = $(this).data('student-id');
            var $select = $('.compliant-select[data-student-id="' + studentId + '"]');
            var compliant = $select.val();
            var c = $('.reason-checkbox[data-student-id="' + studentId + '"][data-type="C"]').is(':checked');
            var m = $('.reason-checkbox[data-student-id="' + studentId + '"][data-type="M"]').is(':checked');
            var t = $('.reason-checkbox[data-student-id="' + studentId + '"][data-type="T"]').is(':checked');
            
            initialState[studentId] = {
                compliant: compliant,
                c: c,
                m: m,
                t: t
            };
        });
        
        // Function to check if student data has changed
        function hasChanged(studentId) {
            var current = {
                compliant: $('.compliant-select[data-student-id="' + studentId + '"]').val(),
                c: $('.reason-checkbox[data-student-id="' + studentId + '"][data-type="C"]').is(':checked'),
                m: $('.reason-checkbox[data-student-id="' + studentId + '"][data-type="M"]').is(':checked'),
                t: $('.reason-checkbox[data-student-id="' + studentId + '"][data-type="T"]').is(':checked')
            };
            
            var initial = initialState[studentId];
            
            return current.compliant !== initial.compliant ||
                current.c !== initial.c ||
                current.m !== initial.m ||
                current.t !== initial.t;
        }
        
        // Function to update modified students list
        function updateModifiedList(studentId) {
            if (hasChanged(studentId)) {
                modifiedStudents.add(studentId);
                // Add visual indicator
                $('.student-row[data-student-id="' + studentId + '"]').addClass('modified-row');
            } else {
                modifiedStudents.delete(studentId);
                // Remove visual indicator
                $('.student-row[data-student-id="' + studentId + '"]').removeClass('modified-row');
            }
            
            // Update hidden input with modified students
            updateHiddenInput();
            
            // Update save button text
            updateSaveButtonText();
        }
        
        // Function to update hidden input
        function updateHiddenInput() {
            var $hidden = $('#modified_students');
            if ($hidden.length === 0) {
                $hidden = $('<input type="hidden" id="modified_students" name="modified_students">');
                $('#habillementForm').append($hidden);
            }
            $hidden.val(Array.from(modifiedStudents).join(','));
        }
        
        // Function to update save button text
        function updateSaveButtonText() {
            var count = modifiedStudents.size;
            var $button = $('#saveButton');
            
            if (count === 0) {
                $button.html('<i class="icon-checkmark"></i> Aucune modification à enregistrer');
                $button.prop('disabled', true);
            } else {
                $button.html('<i class="icon-checkmark"></i> Enregistrer ' + count + ' modification(s)');
                $button.prop('disabled', false);
            }
        }
        
        // Function to update select styling based on value
        function updateSelectStyling(selectElement) {
            var $select = $(selectElement);
            var value = $select.val();
            
            // Remove existing classes
            $select.removeClass('status-conforme status-non-conforme');
            
            // Add appropriate class
            if (value === 'Y') {
                $select.addClass('status-conforme');
            } else if (value === 'N') {
                $select.addClass('status-non-conforme');
            }
        }
        
        // Enable/disable checkboxes based on compliance status
        $('.compliant-select').on('change', function() {
            var studentId = $(this).data('student-id');
            var compliant = $(this).val();
            var checkboxes = $('.reason-checkbox[data-student-id="' + studentId + '"]');
            
            // Update select styling
            updateSelectStyling(this);
            
            if (compliant === 'N') {
                checkboxes.css({
                    'opacity': '1',
                    'pointer-events': 'auto'
                });
            } else {
                // Uncheck and visually disable (but don't use disabled attribute)
                checkboxes.prop('checked', false);
                checkboxes.css({
                    'opacity': '0.3',
                    'pointer-events': 'none'
                });
                // Remove red background from table cells
                checkboxes.closest('td').css('background-color', '');
            }
            
            // Track modification
            updateModifiedList(studentId);
        });

        // Initialize disabled state and styling on page load
        $('.student-row').each(function() {
            var studentId = $(this).data('student-id');
            var $select = $('.compliant-select[data-student-id="' + studentId + '"]');
            var compliant = $select.val();
            
            // Update select styling
            updateSelectStyling($select[0]);
            
            if (compliant !== 'N') {
                $('.reason-checkbox[data-student-id="' + studentId + '"]').css({
                    'opacity': '0.3',
                    'pointer-events': 'none'
                });
            }
        });
        
        // Add visual feedback for checkbox changes
        $('.reason-checkbox').on('change', function() {
            var $checkbox = $(this);
            var $td = $checkbox.closest('td');
            var studentId = $checkbox.data('student-id');
            
            if ($checkbox.is(':checked')) {
                $td.css('background-color', '#fee');
            } else {
                $td.css('background-color', '');
            }
            
            // Track modification
            updateModifiedList(studentId);
        });

        // Initialize save button state
        updateSaveButtonText();

        // Save button handler - just submit the form
        $('#saveButton').on('click', function(e) {
            e.preventDefault();
            
            if (modifiedStudents.size === 0) {
                return; // Don't submit if no changes
            }
            
            var button = $(this);
            button.prop('disabled', true);
            button.html('<i class="icon-spinner2 spinner"></i> Enregistrement...');
            
            // Submit the form normally
            $('#habillementForm').submit();
        });
    });
    </script>

    <style>
    /* Status dropdown styling - green for Conforme, red for Non-conforme */
    .compliant-select {
        font-weight: 500;
        transition: all 0.3s ease;
        padding: 3px 5px;
        height: 28px;
        width: auto;
        border-radius: 6px;
        font-size: 13px;
        border: none;
        border-color: #000000ff;
    }

    /* Default state - Conforme (Green) */
    .compliant-select.status-conforme {
        background-color: #d4edda;
        color: #155724;
        border-color: #28a745;
    }

    /* Non-conforme state (Red) */
    .compliant-select.status-non-conforme {
        background-color: #f8d7da;
        color: #721c24;
        border-color: #dc3545;
    }

    /* Checkbox styling - red when checked */
    .reason-checkbox {
        width: 15px;
        height: 15px;
        cursor: pointer;
        transition: opacity 0.2s ease;
        accent-color: #dc3545;
    }

    .reason-checkbox:disabled {
        cursor: not-allowed;
    }

    /* Additional styling for better visual feedback */
    .reason-checkbox:checked {
        filter: brightness(1.1);
    }

    /* Table cell for checkboxes - add subtle background when checked */
    td:has(.reason-checkbox:checked) {
        background-color: #fee;
    }

    /* Visual indicator for modified rows */
    .modified-row {
        border-left: 3px solid #ffc107;
        background-color: #fffbf0;
    }

    .modified-row:hover {
        background-color: #fff8e1 !important;
    }
    
    .student-row {
        transition: background-color 0.2s ease;
    }
    
    .student-row:hover {
        background-color: #f5f5f5;
    }

    table th small {
        font-weight: normal;
        font-size: 11px;
        color: #666;
        display: block;
        margin-top: 2px;
    }

    .spinner {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .btn-success {
        padding: 12px 30px;
        font-size: 16px;
    }
    
    /* Style for table headers with reasons */
    .table > thead > tr > th small {
        display: block;
        font-size: 10px;
        font-weight: normal;
        text-transform: none;
        margin-top: 2px;
        color: #6c757d;
    }
    
    /* Status indicators for non-teacher view */
    .status-indicator {
        display: inline-block;
        font-size: 18px;
        font-weight: bold;
    }
    
    .status-yes {
        color: #dc3545;
    }
    
    .status-no {
        color: #999;
    }
    </style>
    <?php
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
    
    // Get dates from PHP
    var weekStartDisplay = '<?php echo addslashes($week_start_display); ?>';
    var weekEndDisplay = '<?php echo addslashes($week_end_display); ?>';
    
    // Build the HTML content
    var htmlContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Rapport Habillement Non-Conforme - ${gradeTitle}</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 20px;
                    padding: 0;
                }
                
                .print-toolbar {
                    background: #f8f9fa;
                    border-bottom: 2px solid #dee2e6;
                    padding: 15px 20px;
                    position: sticky;
                    top: 0;
                    z-index: 1000;
                    display: flex;
                    justify-content: center;
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
                
                h3 {
                    color: #333;
                    margin-bottom: 20px;
                }
                
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
                
                .text-center {
                    text-align: center;
                }
                
                .status-indicator {
                    display: inline-block;
                    font-size: 18px;
                    font-weight: bold;
                }
                
                .status-yes {
                    color: #dc3545;
                }
                
                .status-no {
                    color: #999;
                }
                
                th small {
                    display: block;
                    font-size: 10px;
                    font-weight: normal;
                    margin-top: 2px;
                    color: #666;
                }
                
                .text-center {
                    text-align: center;
                }
                
                @media print {
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
                    
                    @page {
                        size: A4 landscape;
                        margin: 8mm 5mm;
                    }
                }
            </style>
        </head>
        <body>
            <div class="print-toolbar">
                <button onclick="window.print()">
                    🖨️ Imprimer
                </button>
                <button class="close-btn" onclick="window.close()">
                    ✕ Fermer
                </button>
            </div>
            <h3>Rapport Habillement Non-Conforme - Semaine du ${weekStartDisplay} au ${weekEndDisplay}</h3>
            ${clonedSection.outerHTML}
        </body>
        </html>
    `;
    
    printWindow.document.write(htmlContent);
    printWindow.document.close();
}
</script>

<style>
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

.table > tbody > tr > td:first-child,
.table > thead > tr > th:first-child {
    color: #666869;
    font-weight: 100;
    font-size: 12px;
    text-align: center;
}

.table > tbody > tr > td:nth-child(2) strong {
    font-weight: 600;
}

.table > tbody > tr > td:nth-child(4) {
    font-family: 'Courier New', monospace;
    color: #6c757d;
    font-size: 13px;
}

.table-hover > tbody > tr:hover {
    transition: background-color 0.2s ease;
}

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

.alert-danger {
    background-color: #F8D7DA;
    border: 1px solid #F5C2C7;
    border-left: 4px solid #DC3545;
    color: #842029;
    border-radius: 4px;
}

.alert-success {
    background-color: #5090C1 !important;
    border: 1px solid #5090C1;
    border-left: 1px solid #5090C1;
    color: #fff;
    border-radius: 4px;
}

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

.status-indicator {
    display: inline-block;
    font-size: 18px;
    font-weight: bold;
}

.status-yes {
    color: #dc3545;
}

.status-no {
    color: #999;
}

.table > thead > tr > th small {
    display: block;
    font-size: 10px;
    font-weight: normal;
    text-transform: none;
    margin-top: 2px;
    color: #6c757d;
}

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
        display: table-cell !important;
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
    
    .status-yes {
        color: #dc3545 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    .status-no {
        color: #999 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}
</style>