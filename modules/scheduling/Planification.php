<?php
include('lang/language.php');
include('../../RedirectModulesInc.php');
//session_start();
DrawBC("" . _scheduling . " > " . ProgramTitle());
// print_r($_REQUEST);

if (User('PROFILE') == 'teacher'){
    $course_id = UserCourse();
    $editable='';
}
else
    $editable='readonly';
if($_REQUEST['id']){
    $course_id  = $_REQUEST['id'];
}

$one_day = 60 * 60 * 24;
$one_week = 60 * 60 * 24 * 7;
// print_r($_REQUEST);
if ($_REQUEST && isset($_REQUEST['week_range'])){
    $start = $_REQUEST['week_range'];
    $week1_date_start = dateFr('d-M',strtotime($_REQUEST['week_range']));
    $week1_sec = strtotime($_REQUEST['week_range']);
    $week2_date_start = dateFr('d-M',strtotime($_REQUEST['week_range']) + $one_week);
    $week2_sec = strtotime($_REQUEST['week_range']) + $one_week;
    $course_id  = $_REQUEST['marking_period_id'];
}
else{
    if (!$_REQUEST['week_range']) {
        $start_time_cur = strtotime(dateFr('Y-m-d'));
        while (dateFr('N', $start_time_cur) != 1) {
            $start_time_cur = $start_time_cur - $one_day;
        }
    }
    $week1_date_start =  dateFr('d-M', $start_time_cur);
    $week1_sec = $start_time_cur;
    $week2_date_start =  dateFr('d-M', $start_time_cur + $one_week);
    $week2_sec = $start_time_cur + $one_week;
}
if ($_POST && isset($_POST['content'])) {
    $week =  $_POST['week'];
    $field =  $_POST['field'];
    $content = $_POST['content'];
    $_SESSION['schedule_data'][$week][$field]=$content;
    if( $week  === 'week1' && $field){
        $RET = DBGet(DBQuery('select * from planification where start_date=\'' . dateFr('Y-m-d',$week1_sec) . '\'  and course_id=\'' . $course_id . '\''));
        if(!count($RET)) 
            DBQuery('INSERT INTO planification (start_date,course_id) VALUES  ("' .dateFr('Y-m-d',$week1_sec) .'", '. $course_id . ')'); 
        $seralizedArray = serialize($_SESSION['schedule_data']['week1']);
        $result = DBQuery('UPDATE  planification SET text =  "' . base64_encode($seralizedArray) . '"  WHERE course_id= '. $course_id . ' and start_date = "' . dateFr('Y-m-d',$week1_sec) . '" ');

    }
    if( $week  === 'week2' && $field){
        $RET = DBGet(DBQuery('select * from planification where start_date=\'' . dateFr('Y-m-d',$week2_sec) . '\'  and course_id=\'' . $course_id . '\''));
        if(!count($RET)) 
            DBQuery('INSERT INTO planification (start_date,course_id) VALUES  ("' .dateFr('Y-m-d',$week2_sec) .'", '. $course_id . ')'); 
        $seralizedArray = serialize($_SESSION['schedule_data']['week2']);
        $result = DBQuery('UPDATE  planification SET text =  "' . base64_encode($seralizedArray) . '"  WHERE course_id= '. $course_id . ' and start_date = "' . dateFr('Y-m-d',$week2_sec) . '" ');
    }
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
$RET = DBGet(DBQuery('select short_name from course_details where course_id=\'' . $course_id . '\''));
$course = 'Planification ';
$course .= $RET[1]['SHORT_NAME'];

// Week 1
if($week1_sec){
    $RET = DBGet(DBQuery('select * from planification where start_date=\'' . dateFr('Y-m-d',$week1_sec) . '\'  and course_id=\'' . $course_id . '\''));
    $raw_content = base64_decode($RET[1]['TEXT']);
    $_SESSION['schedule_data']['week1'] = unserialize($raw_content);
}
// Week 2
if($week2_sec){
    $RET = DBGet(DBQuery('select * from planification where start_date=\'' . dateFr('Y-m-d',$week2_sec) . '\'  and course_id=\'' . $course_id . '\''));
    $raw_content = base64_decode($RET[1]['TEXT']);
    $_SESSION['schedule_data']['week2'] = unserialize($raw_content);
}



// Initialize default data if not set (only for non-AJAX requests)
if (!isset($_SESSION['schedule_data'])) {
    $_SESSION['schedule_data'] = [
        'week1' => [
            'semaine' => '',
            'lundi_date' => '',
            'lundi_notions' => '',
            'lundi_devoirs' => 'kjsdnlksndsalknd',
            'lundi_materiel' => '',
            'mardi_date' => '',
            'mardi_notions' => '',
            'mardi_devoirs' => '',
            'mardi_materiel' => '',
            'mercredi_date' => '',
            'mercredi_notions' => '',
            'mercredi_devoirs' => '',
            'mercredi_materiel' => '',
            'jeudi_date' => '',
            'jeudi_notions' => '',
            'jeudi_devoirs' => '',
            'jeudi_materiel' => '',
            'vendredi_date' => '',
            'vendredi_notions' => '',
            'vendredi_devoirs' => '',
            'vendredi_materiel' => ''
        ],
        'week2' => [
            'semaine' => '',
            'lundi_date' => '',
            'lundi_notions' => '',
            'lundi_devoirs' => '',
            'lundi_materiel' => '',
            'mardi_date' => '',
            'mardi_notions' => '',
            'mardi_devoirs' => '',
            'mardi_materiel' => '',
            'mercredi_date' => '',
            'mercredi_notions' => '',
            'mercredi_devoirs' => '',
            'mercredi_materiel' => '',
            'jeudi_date' => '',
            'jeudi_notions' => '',
            'jeudi_devoirs' => '',
            'jeudi_materiel' => '',
            'vendredi_date' => '',
            'vendredi_notions' => '',
            'vendredi_devoirs' => '',
            'vendredi_materiel' => ''
        ]
    ];
}

$data = $_SESSION['schedule_data'];
$one_day = 60 * 60 * 24;
$one_week = 60 * 60 * 24 *7;
$today = strtotime($_REQUEST['week_range']);
$week_start = dateFr('Y-m-d', $today);
$week_end = dateFr('Y-m-d', $today + $one_day * 6);
$next_week = strtotime($_REQUEST['next_week_range'] + $one_week);
$week_range = _makeWeeks('', '', 'Modules.php?modname=' . $_REQUEST['modname'] . '&marking_period_id=' . $course_id . '&view_mode=' . $_REQUEST['view_mode'] . '&week_range=');

if(! $_REQUEST['_openSIS_PDF']){
    $courses_RET = DBGet(DBQuery('SELECT DISTINCT c.TITLE , cp.COURSE_PERIOD_ID ,cp.COURSE_ID as ID,cp.TEACHER_ID AS STAFF_ID FROM schedule s,course_periods cp,course_period_var cpv,courses c,attendance_calendar acc WHERE s.SYEAR=\'' . UserSyear() . '\' AND cp.COURSE_PERIOD_ID=s.COURSE_PERIOD_ID  AND cp.COURSE_PERIOD_ID=cpv.COURSE_PERIOD_ID  AND (s.MARKING_PERIOD_ID IN (SELECT MARKING_PERIOD_ID FROM school_years WHERE SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE  UNION SELECT MARKING_PERIOD_ID FROM school_semesters WHERE SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE  UNION SELECT MARKING_PERIOD_ID FROM school_quarters WHERE SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE )or s.MARKING_PERIOD_ID  is NULL) AND (\'' . DBDate() . '\' BETWEEN s.START_DATE AND s.END_DATE OR \'' . DBDate() . '\'>=s.START_DATE AND s.END_DATE IS NULL) AND s.STUDENT_ID=\'' . UserStudentID() . '\' AND cp.GRADE_SCALE_ID IS NOT NULL' . (User('PROFILE') == 'teacher' ? ' AND cp.TEACHER_ID=\'' . User('STAFF_ID') . '\'' : '') . ' AND c.COURSE_ID=cp.COURSE_ID ORDER BY TITLE'));
    if (count($courses_RET)) {
         echo '<div class="form-inline"><div style="width: 300px;" class="col-md-12">' . CreateSelect($courses_RET, 'id', $course_id, _selectCourse . ' : ', 'Modules.php?modname=' . strip_tags(trim($_REQUEST['modname'])) . '&id=') . '</div><br><br>';
    }
}

if(! $_REQUEST['_openSIS_PDF']){
    echo '<br>';
    DrawHeader($week_range, '<div class="form-inline"><div class="input-group"></div><div class="input-group"><span class="input-group-addon" id="view_mode"></span></div></div>');
}

function CreateSelect($val, $name, $opt, $cap, $link)
{
    $html = '<label class="control-label text-uppercase"><b>' . $cap . '</b></label>';
    $html .= "<select name=" . $name . " id=" . $name . " class=\"form-control\" onChange=\"window.location='" . $link . "' + this.options[this.selectedIndex].value;\">";
    // $html .= "<option value=''>" . $opt . "</option>";

    foreach ($val as $key => $value) {
        if ($value[strtoupper($name)] == $opt)
            $html .= "<option selected value=" . $value[strtoupper($name)] . ">" . $value['TITLE'] . "</option>";
        else
            $html .= "<option value=" . $value[strtoupper($name)] . ">" . $value['TITLE'] . "</option>";
    }
    $html .= "</select>";
    return $html;
}

function _makeWeeks($start, $end, $link)
{
    $html = '';
    $one_day = 60 * 60 * 24;
    $start_time = strtotime($start);
    $end_time = strtotime($end);
    if (!$_REQUEST['week_range']) {
        $start_time_cur = strtotime(dateFr('Y-m-d'));
        while (dateFr('N', $start_time_cur) != 1) {
            $start_time_cur = $start_time_cur - $one_day;
        }
        $_REQUEST['week_range'] = dateFr('Y-m-d', $start_time_cur);
    }



    $prev = dateFr('Y-m-d', strtotime($_REQUEST['week_range']) - $one_day * 7);
    $next = dateFr('Y-m-d', strtotime($_REQUEST['week_range']) + $one_day * 7);
    $upper = dateFr('Y-m-d', strtotime($_REQUEST['week_range']) + $one_day * 6);
    if ($link != '') {
        $html .= "<a href='javascript:void(0);' class=\"text-primary\" title=Previous onClick=\"window.location='" . $link . $prev . "';\"><i class=\"fa fa-angle-left\"></i> " . _prev . "</a> &nbsp; &nbsp; <span>" . properDate($_REQUEST['week_range']) . "&nbsp; - &nbsp;" . properDate($upper) . "</span> &nbsp; &nbsp; <a href='javascript:void(0);' title=Next onClick=\"window.location='" . $link . $next . "';\" class=\"text-primary\">" . _next . " <i class=\"fa fa-angle-right\"></i></a>";
    }

    return $html;
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


?>

<!DOCTYPE html>
<html lang="fr">
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planificateur Hebdomadaire</title>
    <style>
        /* body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        } */
        
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        
        .week-section {
            margin-bottom: 40px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        
        th, td {
            border: 1px solid #333;
            padding: 4px;
            vertical-align: top;
            min-height: 40px;
        }
        
        th {
            background-color: #a09b9bff;
            /* font-weight: bold; */
            text-align: center;
        }
        
        .header-row {
            background-color: #fff;
            /* background-color: #a4a0a0ff; */
            color: white;
            /* font-weight: bold; */
        }
        
        .day-header {
            background-color: #5090c1;
            /* font-weight: bold; */
            text-align: center;
            width: 7%;
            color: white;
        }
        
        .editable {
            /* background-color: #fff; */
            cursor: text;
            min-height: 30px;
            padding: 0px;
            border: none;
            width: 100%;
            resize: vertical;
            font-family: inherit;
            font-size: inherit;
        }
        
        .editable:focus {
            /* background-color: #c3ccd5ff; */
            outline: 1px solid #007cba;
        }
        
        .semaine {
            background-color:  #dbdddeff;
            text-align: center; 
            font-size: 16px;
            color: black;
        }

        .semaine-input {
            width: 100%;
            border: none;
            background: transparent;
            /* font-weight: bold; */
            text-align: center;
            font-size: inherit;
            color: white;
        }
        
        .semaine-input:focus {
            background-color: #ffffcc;
            outline: 2px solid #007cba;
        }
        
        .save-status {
            position: fixed;
            top: 10px;
            right: 10px;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border-radius: 4px;
            display: none;
        }

        .error-status {
            position: fixed;
            top: 10px;
            right: 10px;
            padding: 10px 20px;
            background-color: #f44336;
            color: white;
            border-radius: 4px;
            display: none;
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
    </style>
</head>
<body>
        <!-- Week 1 -->
        <div class="week-section">
            <table>
                <tr class="header-row">
                    <td colspan="4" class="semaine">
                        <strong><?php echo $course ?> semaine du <i> <?php echo $week1_date_start ?></i> </strong>
                </tr>
                <tr class="header-row">
                    <th>Jour</th>
                    <th>Notions et travail en classe</th>
                    <th>Devoirs/Étude</th>
                    <th>Matériel</th>
                </tr>
                
                <tr>
                    <td class="day-header">Lundi</td>
                    <td><textarea <?php echo $editable ?> class="editable"  data-week="week1" data-field="lundi_notions"><?php echo htmlspecialchars($data['week1']['lundi_notions']); ?></textarea></td>
                    <td><textarea <?php echo $editable ?> class="editable" data-week="week1" data-field="lundi_devoirs"><?php echo htmlspecialchars($data['week1']['lundi_devoirs']); ?></textarea></td>
                    <td><textarea <?php echo $editable ?> class="editable" data-week="week1" data-field="lundi_materiel"><?php echo htmlspecialchars($data['week1']['lundi_materiel']); ?></textarea></td>
                </tr>
                
                <tr>
                    <td class="day-header">Mardi</td>
                    <td><textarea <?php echo $editable ?> class="editable" data-week="week1" data-field="mardi_notions"><?php echo htmlspecialchars($data['week1']['mardi_notions']); ?></textarea></td>
                    <td><textarea <?php echo $editable ?> class="editable" data-week="week1" data-field="mardi_devoirs"><?php echo htmlspecialchars($data['week1']['mardi_devoirs']); ?></textarea></td>
                    <td><textarea <?php echo $editable ?> class="editable" data-week="week1" data-field="mardi_materiel"><?php echo htmlspecialchars($data['week1']['mardi_materiel']); ?></textarea></td>
                </tr>
                
                <tr>
                    <td class="day-header">Mercredi</td>
                    <td><textarea <?php echo $editable ?> class="editable" data-week="week1" data-field="mercredi_notions"><?php echo htmlspecialchars($data['week1']['mercredi_notions']); ?></textarea></td>
                    <td><textarea <?php echo $editable ?> class="editable" data-week="week1" data-field="mercredi_devoirs"><?php echo htmlspecialchars($data['week1']['mercredi_devoirs']); ?></textarea></td>
                    <td><textarea <?php echo $editable ?> class="editable" data-week="week1" data-field="mercredi_materiel"><?php echo htmlspecialchars($data['week1']['mercredi_materiel']); ?></textarea></td>
                </tr>
                
                <tr>
                    <td class="day-header">Jeudi</td>
                    <td><textarea <?php echo $editable ?> class="editable" data-week="week1" data-field="jeudi_notions"><?php echo htmlspecialchars($data['week1']['jeudi_notions']); ?></textarea></td>
                    <td><textarea <?php echo $editable ?> class="editable" data-week="week1" data-field="jeudi_devoirs"><?php echo htmlspecialchars($data['week1']['jeudi_devoirs']); ?></textarea></td>
                    <td><textarea <?php echo $editable ?> class="editable" data-week="week1" data-field="jeudi_materiel"><?php echo htmlspecialchars($data['week1']['jeudi_materiel']); ?></textarea></td>
                </tr>
                
                <tr>
                    <td class="day-header">Vendredi</td>
                    <td><textarea <?php echo $editable ?> class="editable" data-week="week1" data-field="vendredi_notions"><?php echo htmlspecialchars($data['week1']['vendredi_notions']); ?></textarea></td>
                    <td><textarea <?php echo $editable ?> class="editable" data-week="week1" data-field="vendredi_devoirs"><?php echo htmlspecialchars($data['week1']['vendredi_devoirs']); ?></textarea></td>
                    <td><textarea <?php echo $editable ?> class="editable" data-week="week1" data-field="vendredi_materiel"><?php echo htmlspecialchars($data['week1']['vendredi_materiel']); ?></textarea></td>
                </tr>
            </table>
        </div>
        
        <!-- Week 2 -->
        <div class="week-section">
            <table>
                <tr class="header-row">
                    <td colspan="4" class="semaine">
                        <strong><?php echo $course ?> semaine du <i> <?php echo $week2_date_start ?></i> </strong>
                    </td>
                </tr>
                <tr class="header-row">
                    <th>Jour</th>
                    <th>Notions et travail en classe</th>
                    <th>Devoirs/Étude</th>
                    <th>Matériel</th>
                </tr>
                
                <tr>
                    <td class="day-header">Lundi</td>
                    <td><textarea <?php echo $editable ?> class="editable" data-week="week2" data-field="lundi_notions"><?php echo htmlspecialchars($data['week2']['lundi_notions']); ?></textarea></td>
                    <td><textarea <?php echo $editable ?> class="editable" data-week="week2" data-field="lundi_devoirs"><?php echo htmlspecialchars($data['week2']['lundi_devoirs']); ?></textarea></td>
                    <td><textarea <?php echo $editable ?> class="editable" data-week="week2" data-field="lundi_materiel"><?php echo htmlspecialchars($data['week2']['lundi_materiel']); ?></textarea></td>
                </tr>
                
                <tr>
                    <td class="day-header">Mardi</td>
                    <td><textarea <?php echo $editable ?> class="editable" data-week="week2" data-field="mardi_notions"><?php echo htmlspecialchars($data['week2']['mardi_notions']); ?></textarea></td>
                    <td><textarea <?php echo $editable ?> class="editable" data-week="week2" data-field="mardi_devoirs"><?php echo htmlspecialchars($data['week2']['mardi_devoirs']); ?></textarea></td>
                    <td><textarea <?php echo $editable ?> class="editable" data-week="week2" data-field="mardi_materiel"><?php echo htmlspecialchars($data['week2']['mardi_materiel']); ?></textarea></td>
                </tr>
                
                <tr>
                    <td class="day-header">Mercredi</td>
                    <td><textarea <?php echo $editable ?> class="editable" data-week="week2" data-field="mercredi_notions"><?php echo htmlspecialchars($data['week2']['mercredi_notions']); ?></textarea></td>
                    <td><textarea <?php echo $editable ?> class="editable" data-week="week2" data-field="mercredi_devoirs"><?php echo htmlspecialchars($data['week2']['mercredi_devoirs']); ?></textarea></td>
                    <td><textarea <?php echo $editable ?> class="editable" data-week="week2" data-field="mercredi_materiel"><?php echo htmlspecialchars($data['week2']['mercredi_materiel']); ?></textarea></td>
                </tr>
                
                <tr>
                    <td class="day-header">Jeudi</td>
                    <td><textarea <?php echo $editable ?> class="editable" data-week="week2" data-field="jeudi_notions"><?php echo htmlspecialchars($data['week2']['jeudi_notions']); ?></textarea></td>
                    <td><textarea <?php echo $editable ?> class="editable" data-week="week2" data-field="jeudi_devoirs"><?php echo htmlspecialchars($data['week2']['jeudi_devoirs']); ?></textarea></td>
                    <td><textarea <?php echo $editable ?> class="editable" data-week="week2" data-field="jeudi_materiel"><?php echo htmlspecialchars($data['week2']['jeudi_materiel']); ?></textarea></td>
                </tr>
                
                <tr>
                    <td class="day-header">Vendredi</td>
                    <td><textarea <?php echo $editable ?> class="editable" data-week="week2" data-field="vendredi_notions"><?php echo htmlspecialchars($data['week2']['vendredi_notions']); ?></textarea></td>
                    <td><textarea <?php echo $editable ?> class="editable" data-week="week2" data-field="vendredi_devoirs"><?php echo htmlspecialchars($data['week2']['vendredi_devoirs']); ?></textarea></td>
                    <td><textarea <?php echo $editable ?> class="editable" data-week="week2" data-field="vendredi_materiel"><?php echo htmlspecialchars($data['week2']['vendredi_materiel']); ?></textarea></td>
                </tr>
            </table>

            <?php
            if(! $_REQUEST['_openSIS_PDF'] && User('PROFILE') == "teacher"){
            echo '
                        <div class="auto-save-status" id="autoSaveStatus">
                        <span class="auto-save-indicator"></span>
                        <span id="autoSaveText">Auto-sauvegarde activée</span>

            ';
            }else{
            echo '
                        <div  class="auto-save-status hidden" id="autoSaveStatus">
                        <span class="auto-save-indicator hidden" ></span>
                        <span class="auto-save-indicator hidden id="autoSaveText"></span>

            ';
            }
            ?>
    <script>
        const editableCells = document.querySelectorAll('.editable');
        const autoSaveStatus = document.getElementById('autoSaveStatus');
        const autoSaveText = document.getElementById('autoSaveText');

        let savedSelection = null;
        let savedRange = null;

        // Auto-save configuration
        let autoSaveTimeout;
        let autoSaveInterval;
        let hasUnsavedChanges = false;
        let dont_save = false;
        
        const AUTO_SAVE_DELAY = 3000; // 3 seconds after user stops typing
        const AUTO_SAVE_INTERVAL = 30000; // 30 seconds periodic save

        
        editableCells.forEach(cell => {
            cell.addEventListener('blur', function() {
                saveCell(this);
            });
            
            // Auto-resize textareas
            if (cell.tagName === 'TEXTAREA') {
                cell.addEventListener('input', function() {
                    this.style.height = 'auto';
                    this.style.height = this.scrollHeight + 'px';
                });
                
                // Initial resize
                cell.style.height = 'auto';
                cell.style.height = cell.scrollHeight + 'px';
            }
        });
        
        function saveCell(element) {
            const week = element.getAttribute('data-week');
            const field = element.getAttribute('data-field');
            const value = element.value;
            // Send AJAX request to save data  
            saveContent(week,field,value);
        }
        
        function showSaveStatus() {
            const status = document.getElementById('saveStatus');
            status.style.display = 'block';
            setTimeout(() => {
                status.style.display = 'none';
            }, 2000);
        }

        function showErrorStatus(message) {
            const status = document.getElementById('errorStatus');
            status.textContent = `Erreur: ${message}`;
            status.style.display = 'block';
            setTimeout(() => {
                status.style.display = 'none';
            }, 3000);
        }

        // Sauvegarder le contenu
        function saveContent(week,field,content) {
            //const content = 'doit';
            const formData = new FormData();

            updateAutoSaveStatus('saving', 'Sauvegarde manuelle...');

            formData.append('week', week);
            formData.append('field', field);
            formData.append('content', content);
            // console.log('Full href:', window.location.href);
            // console.log('Pathname only:', window.location.pathname);
            // console.log('Search params:', window.location.search);
            // console.log('Hash:', window.location.hash);
            //post('Modules.php?modname=scheduling/Planification.php',{content});
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


        function updateAutoSaveStatus(status, message) {
            autoSaveStatus.className = `auto-save-status ${status}`;
            autoSaveText.textContent = message;
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
        if(document.readyState === 'complete') {
            post('Modules.php?modname=scheduling/Planification.php','auto_save');
        }
    </script>
</body>
</html>
<?php
if(! $_REQUEST['_openSIS_PDF']){
    echo '</div>';
    echo "<FORM name=exp class=no-margin-bottom id=exp action=ForExport.php?modname=" . strip_tags(trim($_REQUEST['modname'])) . "&modfunc=print&marking_period_id=" . $course_id . "&week_range=" . $start . "&_openSIS_PDF=true&report=true method=POST target=_blank>";
    echo '<div class="text-right"><INPUT type=submit class="btn btn-primary" value=\'' . _print . '\'></div>';
}
?>