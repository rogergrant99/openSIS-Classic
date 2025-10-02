<?php
include('lang/language.php');
include('../../RedirectModulesInc.php');
session_start();
DrawBC("" . _scheduling . " > " . ProgramTitle());

global $course_period_id,$course_id;

$user_course = UserCourse();
if( $_REQUEST['print_admin']){
    $user_course = $_REQUEST['marking_period_id'];
}
// Admin only sees completion statue
if (User('PROFILE') == 'admin' && !$_REQUEST['print_admin']){
    check_all_planif();
    exit;
}

// Week navigation
$one_day = 60 * 60 * 24;
$one_week = 60 * 60 * 24 * 7;
if ($_REQUEST && isset($_REQUEST['week_range'])){
    $start = $_REQUEST['week_range'];
    $week1_date_start = dateFr('d-M',strtotime($_REQUEST['week_range']));
    $week1_sec = strtotime($_REQUEST['week_range']);
    $temp_course_id =  $course_id  = $_REQUEST['marking_period_id'];
    $primaire=0;
    if($course_id)
        update_days($course_id);

}
else{
    if (!$_REQUEST['week_range']) {
        $start_time_cur = strtotime(dateFr('Y-m-d'));
        while (dateFr('N', $start_time_cur) != 1) {
            $start_time_cur = $start_time_cur - $one_day;
        }
        $start = $_REQUEST['week_range'] =  date('Y-m-d', $start_time_cur ); 
        $week1_date_start = dateFr('d-M',strtotime($_REQUEST['week_range']));
        $week1_sec = strtotime($_REQUEST['week_range']);
    }
    $week1_date_start =  dateFr('d-M', $start_time_cur);
    $week1_sec = $start_time_cur;
}

// Change course for secondary students
if($_REQUEST['id']){
    $course_id  = $_REQUEST['id'];
    $course_RET = DBGet(DBQuery('SELECT grade_level,teacher_id FROM course_details WHERE SYEAR=\'' . UserSyear() . '\' AND course_id=' . $_REQUEST['id'] . ''));
    $course_id=$_REQUEST['id'];
    // $teacher_id=$course_RET[1]['TEACHER_ID'];
    $grade_level=$course_RET[1]['GRADE_LEVEL'];
    $primaire=0;
    $temp_course_id=$course_id;
    if($course_id)
        update_days($course_id);
}

// Set default course id on initial load
if(!$course_id && User('PROFILE') != 'teacher'){
    $courses_RET = DBGet(DBQuery('SELECT DISTINCT c.TITLE , cp.COURSE_PERIOD_ID ,cp.COURSE_ID as ID,cp.TEACHER_ID AS STAFF_ID FROM schedule s,course_periods cp,course_period_var cpv,courses c,attendance_calendar acc WHERE s.SYEAR=\'' . UserSyear() . '\' AND cp.COURSE_PERIOD_ID=s.COURSE_PERIOD_ID  AND cp.COURSE_PERIOD_ID=cpv.COURSE_PERIOD_ID  AND (s.MARKING_PERIOD_ID IN (SELECT MARKING_PERIOD_ID FROM school_years WHERE SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE  UNION SELECT MARKING_PERIOD_ID FROM school_semesters WHERE SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE  UNION SELECT MARKING_PERIOD_ID FROM school_quarters WHERE SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE )or s.MARKING_PERIOD_ID  is NULL) AND (\'' . DBDate() . '\' BETWEEN s.START_DATE AND s.END_DATE OR \'' . DBDate() . '\'>=s.START_DATE AND s.END_DATE IS NULL) AND s.STUDENT_ID=\'' . UserStudentID() . '\' AND cp.GRADE_SCALE_ID IS NOT NULL' . (User('PROFILE') == 'teacher' ? ' AND cp.TEACHER_ID=\'' . User('STAFF_ID') . '\'' : '') . ' AND c.COURSE_ID=cp.COURSE_ID ORDER BY TITLE'));
    // print_r($courses_RET);
    $course_RET = DBGet(DBQuery('SELECT course_id,grade_level,teacher_id FROM course_details WHERE SYEAR=\'' . UserSyear() . '\' AND course_id=' . $courses_RET[1]['ID'] . ''));
    // print_r($course_RET);
    $course_id= $course_RET[1]['COURSE_ID'];
    if($course_RET[1]['GRADE_LEVEL'] >= '1' && $course_RET[1]['GRADE_LEVEL'] <= '7'){
        $primaire=$course_RET[1]['GRADE_LEVEL'];
        $temp_course_id=0;
        $course_id=0;
    }else{
        $primaire=0;
        $temp_course_id=$course_id;
        if($course_id)
            update_days($course_id);
    }
}

// Set teacher course
if (User('PROFILE') == 'teacher' ||  $_REQUEST['print_admin'] ){
    if(!$user_course) return;
    $course_RET = DBGet(DBQuery('SELECT course_id,grade_level,teacher_id FROM course_details WHERE SYEAR=\'' . UserSyear() . '\' AND course_id=' . $user_course . ''));
    if($course_RET[1]['GRADE_LEVEL'] >= '1' && $course_RET[1]['GRADE_LEVEL'] <= '7'){
        $primaire=$course_RET[1]['GRADE_LEVEL'];
        // $teacher_id=$course_RET[1]['TEACHER_ID'];
        $temp_course_id=0;
        $course_id=0;
         update_days($course_id);
    }else{
        $primaire=0;
        $course_id=$temp_course_id=$user_course;
        if($course_id)
            update_days($course_id);
    }
}

// echo ' Primaire = ';
// echo $primaire;
// echo ' Course id = ';
// echo $course_id;
// echo ' temp = ';
// echo $temp_course_id;

// Add course selector on multiple courses
if(! $_REQUEST['_openSIS_PDF'] && ! $primaire){
    $courses_RET = DBGet(DBQuery('SELECT DISTINCT c.TITLE , cp.COURSE_PERIOD_ID ,cp.COURSE_ID as ID,cp.TEACHER_ID AS STAFF_ID FROM schedule s,course_periods cp,course_period_var cpv,courses c,attendance_calendar acc WHERE s.SYEAR=\'' . UserSyear() . '\' AND cp.COURSE_PERIOD_ID=s.COURSE_PERIOD_ID  AND cp.COURSE_PERIOD_ID=cpv.COURSE_PERIOD_ID  AND (s.MARKING_PERIOD_ID IN (SELECT MARKING_PERIOD_ID FROM school_years WHERE SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE  UNION SELECT MARKING_PERIOD_ID FROM school_semesters WHERE SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE  UNION SELECT MARKING_PERIOD_ID FROM school_quarters WHERE SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE )or s.MARKING_PERIOD_ID  is NULL) AND (\'' . DBDate() . '\' BETWEEN s.START_DATE AND s.END_DATE OR \'' . DBDate() . '\'>=s.START_DATE AND s.END_DATE IS NULL) AND s.STUDENT_ID=\'' . UserStudentID() . '\' AND cp.GRADE_SCALE_ID IS NOT NULL' . (User('PROFILE') == 'teacher' ? ' AND cp.TEACHER_ID=\'' . User('STAFF_ID') . '\'' : '') . ' AND c.COURSE_ID=cp.COURSE_ID ORDER BY TITLE'));
    if (count($courses_RET)) {
        echo '<div class="form-inline"><div style="width: 300px;" class="col-md-12">' . CreateSelect($courses_RET, 'id', $course_id, _selectCourse . ' : ', 'Modules.php?modname=' . strip_tags(trim($_REQUEST['modname'])) . '&id=') . '</div><br><br>';
        echo '<br>';
    }
}

// Teacher  or student
if (User('PROFILE') == 'teacher'){
    $course_id = $user_course;
    $editable='true';
}
else{
    $editable='false';    
}

// Add files
if (isset($_FILES['files'])) {
    $fileCount = count($_FILES['files']['name']);
    $dir = 'assets/stafffiles';
    for ($i = 0; $i < $fileCount; $i++) {
        if ($_FILES['files']['error'][$i] == UPLOAD_ERR_OK) {
            $fileName = $_FILES['files']['name'][$i];
            $fileTmpName = $_FILES['files']['tmp_name'][$i];
            $fileSize = $_FILES['files']['size'][$i];
            $fileType = $_FILES['files']['type'][$i];
            if($primaire)
               $target_path = $dir . '/0-[PRI-'. $primaire .']' . $fileName . '';
            else
               $target_path = $dir . '/' . $_POST['teacher_id'] . '-['. $_POST['course_period_id'] .']' . $fileName . '';

            $content = 'IN_DIR';
            $concat_filename = str_replace($dir.'/', '', $target_path);
            $concat_filename = str_replace("'", "\'", $concat_filename);
            // echo $concat_filename;
            if(file_exists($target_path)){
                DBQuery('DELETE FROM user_file_upload WHERE USER_ID=\''  . $_POST['teacher_id'] . '\'AND NAME=\'' . $concat_filename . '\'');
                unlink($target_path);
            }
            move_uploaded_file($fileTmpName, "assets/stafffiles/" . $concat_filename);
        	// rename($fileTmpName, $target_path);
            DBQuery('INSERT INTO user_file_upload (USER_ID,PROFILE_ID,SCHOOL_ID,SYEAR,NAME, SIZE, TYPE, CONTENT,FILE_INFO) VALUES (' . $_POST['teacher_id'] . ',\'2\',' . UserSchool() . ',' . UserSyear() . ',\'' . $concat_filename . '\', \'' . $fileSize . '\', \'' . $fileType . '\', \'' . $content . '\',\'stafffile\')');
        }
    }
}

// Delete file
if ($_POST && isset($_POST['delete_file'])){
    $dir = 'assets/stafffiles';
    $target_path = $dir . '/' . $_POST['teacher_id'] . ''. $_POST['delete_file'] .'' . $fileName . '';
    DBQuery('DELETE FROM user_file_upload WHERE name="' .$_POST['delete_file'] . '"');
    unlink($target_path);
}

// Save content
if ($_POST && isset($_POST['auto_save'])) {
    $week = $_POST['week'];
    $field = $_POST['field'];
    $content = $_POST['content']; 
    $content = strip_tags($content, '<b><strong><i><em><u><br><p><ul><ol><li><span><div>');    
    $_SESSION['schedule_data'][$week][$field] = $content;
    if( $week  === 'week1' && $field){
        $RET = DBGet(DBQuery('select * from planification where start_date=\'' . dateFr('Y-m-d',$week1_sec) . '\'   and course_id=\'' . $temp_course_id . '\'  and is_primary=\'' . $primaire . '\''));
        if(!count($RET) && $content)
            DBQuery('INSERT INTO planification (start_date,updated_by,is_primary,course_id) VALUES  ("' .dateFr('Y-m-d',$week1_sec) .'", ' . UserID() . '  ,'. $primaire .','. $temp_course_id  . ')'); 
        $seralizedArray = serialize($_SESSION['schedule_data']['week1']);
        $result = DBQuery('UPDATE  planification SET updated_by = ' . UserID() . ' , text =  "' . base64_encode($seralizedArray) . '"  WHERE course_id= '. $temp_course_id . '  and is_primary= ' . $primaire . ' and start_date = "' . dateFr('Y-m-d',$week1_sec) . '" ');
    }
    if (isset($_POST['auto_save'])) {
        header('Content-Type: application/json');
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Auto-saved successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
        exit; // Important: stop execution after JSON response
    }
}
// Get course name
if($primaire){
    $course = 'Planification primaire ';
    $course .=$primaire-1;
    if($primaire==1)
        $course = 'Planification préscolaire ';
}
else{
    $RET = DBGet(DBQuery('select short_name from course_details where course_id=\'' . $course_id . '\''));
    $course = 'Planification ';
    $course .= $RET[1]['SHORT_NAME'];
}

// Week 1
if($week1_sec){
    $RET = DBGet(DBQuery('select * from planification where start_date=\'' . dateFr('Y-m-d',$week1_sec) . '\'  and is_primary=' . $primaire . ' and course_id=\'' . $temp_course_id . '\''));
    $raw_content = base64_decode($RET[1]['TEXT']);
    if($RET[1]['UPDATED_BY']){
        $get_teacher = DBGet(DBQuery('SELECT CONCAT(FIRST_NAME," ",LAST_NAME) AS FULLNAME FROM staff  WHERE  STAFF_ID=' . $RET[1]['UPDATED_BY'] . ' '));
        $updated_by=$get_teacher[1]['FULLNAME'];
    }
    $_SESSION['schedule_data']['week1'] = unserialize($raw_content);
     // echo '<pre>'; print_r($_SESSION['schedule_data']['week1']);echo '</pre>'; 
    foreach($_SESSION['schedule_data']['week1'] as $key =>  $line){
       $line = ltrim($line);
       $_SESSION['schedule_data']['week1'][$key] = str_replace(["\r\n", "\r", "\n"], '<br>', $line);
    }
     //echo '<pre>'; print_r($_SESSION['schedule_data']['week1']);echo '</pre>'; 
    // echo nl2br("foo isn't\n bar");
    //  print_r($_SESSION['schedule_data']['week1']);
}

// Initialize default data if not set (only for non-AJAX requests)
if (!isset($_SESSION['schedule_data'])) {
    $_SESSION['schedule_data'] = [
        'week1' => [
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

// Add print button
if(! $_REQUEST['_openSIS_PDF']){
    DrawHeader($week_range, '<div class="form-inline"><div class="input-group"></div><FORM name="exp" class="no-margin-bottom" id="exp" action="ForExport.php?modname=' . urlencode(strip_tags(trim($_REQUEST["modname"]))) . '&modfunc=print&marking_period_id=' . urlencode($course_id) . '&week_range=' . urlencode($start) . '&_openSIS_PDF=true&report=true" method="POST" target="_blank"><div class="text-right"><INPUT type="submit" class="btn btn-primary" value="' . htmlspecialchars(_print, ENT_QUOTES) . '"></div></form><div class="input-group"><span class="input-group-addon" id="view_mode"></span></div></div>');
}


function CreateSelect($val, $name, $opt, $cap, $link){
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
function update_days($course_id){
        global $mondayClass,$tuesdayClass,$wednesdayClass,$thursdayClass,$fridayClass;

        if(!$course_id){
            $mondayClass=$tuesdayClass=$wednesdayClass=$thursdayClass=$fridayClass='';
            return;
        }

        $days_RET = DBGet(DBQuery('SELECT cpv.days FROM course_details cd JOIN course_period_var cpv WHERE SYEAR=\'' . UserSyear() . '\' AND course_id=' . $course_id . ' and cpv.course_period_id = cd.course_period_id'));
        // echo '<pre>'; print_r($days_RET); echo '</pre>';
        foreach($days_RET  as $key => $days){
            $result .= $days['DAYS'];
        }
        $array = str_split($result);
        $mondayClass = in_array('M', $array) ? '' : 'hidden-day';
        $tuesdayClass = in_array('T', $array) ? '' : 'hidden-day';
        $wednesdayClass = in_array('W', $array) ? '' : 'hidden-day';
        $thursdayClass = in_array('H', $array) ? '' : 'hidden-day';
        $fridayClass = in_array('F', $array) ? '' : 'hidden-day';

}
function _makeWeeks($start, $end, $link){
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
function check_all_planif() {
    echo '<div class="panel panel-default">';
    
    // Get all active teachers
    $teachers = getActiveTeachers();
    
    // Render teacher selection form
    renderTeacherSelectionForm();
    
    echo '<hr class="no-margin"/>';
    
    // Get courses for current marking period
    $courses = getCoursesForCurrentPeriod();
    
    if (empty($teachers)) {
        echo '<p>No teachers found.</p>';
        echo '</div>';
        return;
    }
    
    // Build display data
    $displayData = buildTeacherPlanningDisplay($teachers, $courses);
    
    // Output the results
    $options = ['search' => false];
    ListOutput(
        $displayData['courses'], 
        $displayData['teachers'], 
        _teacherWhoHasnTEnteredGrades, 
        "", "", "", 
        $options
    );
    
    echo '</div>';
}

/**
 * Get all active teachers from the database
 * @return array List of active teachers
 */
function getActiveTeachers() {
    $query = "
        SELECT DISTINCT 
            STAFF_ID,
            CONCAT(LAST_NAME, ', ', FIRST_NAME) AS FULL_NAME,
            LAST_NAME,
            FIRST_NAME 
        FROM staff  
        WHERE PROFILE_ID = '2' 
            AND (is_disable != 'Y' OR is_disable IS NULL)
        ORDER BY LOWER(FULL_NAME)
    ";
    
    $result = DBQuery($query);
    return DBGet($result);
}

/**
 * Render the teacher selection form
 */
function renderTeacherSelectionForm() {
    $modname = strip_tags(trim($_REQUEST['modname'] ?? ''));
    
    echo "<form class=\"no-margin\" action=\"Modules.php?modname={$modname}\" method=\"POST\">";
    
    $teacherSelectHtml = '<div class="form-inline">' .
                        '<div class="form-group">' .
                        '<label class="control-label ml-20 mr-20">-</label>' . 
                        ($teacher_select ?? '') . 
                        '</div></div>';
    
    DrawHeader(_teacherCompletion, $teacherSelectHtml);
    echo '</form>';
}

/**
 * Get courses for the current marking period
 * @return array List of courses
 */
function getCoursesForCurrentPeriod() {
    global $mp_type, $cur_mp;
    
    $query = "
        SELECT DISTINCT 
            s.STAFF_ID,
            CONCAT(s.LAST_NAME, ', ', s.FIRST_NAME) AS FULL_NAME,
            cp.TITLE,
            cp.COURSE_PERIOD_ID,
            cp.SHORT_NAME,
            cp.COURSE_ID,
            cp.COURSE_WEIGHT as WEIGHT 
        FROM staff s
        JOIN course_periods cp ON cp.TEACHER_ID = s.STAFF_ID
        JOIN school_periods sp ON 1=1
        WHERE cp.GRADE_SCALE_ID IS NOT NULL 
            AND cp.MARKING_PERIOD_ID IN (" . GetAllMP($mp_type, $cur_mp) . ")
            AND cp.SYEAR = '" . UserSyear() . "'
            AND cp.SCHOOL_ID = '" . UserSchool() . "'
            AND s.PROFILE = 'teacher'
    ";
    
    // Add period filter if specified
    if (!empty($_REQUEST['period'])) {
        $query .= " AND cp.COURSE_PERIOD_ID = '" . $_REQUEST['period'] . "'";
    } else {
        $query .= " ORDER BY LOWER(cp.SHORT_NAME)";
    }
    
    return DBGet(DBQuery($query));
}

/**
 * Build the display data structure for teachers and their courses
 * @param array $teachers List of teachers
 * @param array $courses List of courses
 * @return array Display data with teachers and courses
 */
function buildTeacherPlanningDisplay($teachers, $courses) {
    $staffDisplay = [];
    $courseDisplay = [];
    
    $teacherIndex = 0;
    
    foreach ($teachers as $teacher) {
        $teacherCourses = getTeacherCourses($teacher, $courses);
        
        if (!empty($teacherCourses)) {
            $teacherIndex++;
            
            // Add teacher header
            $staffDisplay[$teacherIndex] = formatTeacherHeader($teacher['FULL_NAME']);
            
            // Add courses for this teacher
            $courseIndex = 0;
            foreach ($teacherCourses as $course) {
                $courseIndex++;
                $courseDisplay[$courseIndex][$teacherIndex] = formatCourseDisplay($course);
            }
        }
    }
    
    return [
        'teachers' => $staffDisplay,
        'courses' => $courseDisplay
    ];
}

/**
 * Get courses for a specific teacher
 * @param array $teacher Teacher data
 * @param array $allCourses All available courses
 * @return array Courses for the teacher
 */
function getTeacherCourses($teacher, $allCourses) {
    $teacherCourses = [];
    
    foreach ($allCourses as $course) {
        if ($teacher['FULL_NAME'] === $course['FULL_NAME']) {
            $teacherCourses[] = $course;
        }
    }
    
    return $teacherCourses;
}

/**
 * Format teacher header for display
 * @param string $fullName Teacher's full name
 * @return string Formatted HTML
 */
function formatTeacherHeader($fullName) {
    return '<div class="teacher-header text-center">' .
           '<strong style="font-size: 14px; color: black;">&nbsp&nbsp&nbsp&nbsp' .
           htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') .
           '</strong>&nbsp&nbsp&nbsp&nbsp</div>';
}

/**
 * Format course display with planning status
 * @param array $course Course data
 * @return string Formatted HTML
 */
function formatCourseDisplay($course) {
    $one_day = 24 * 60 * 60;
    $start_time_cur = strtotime(dateFr('Y-m-d'));
    while (dateFr('N', $start_time_cur) != 1) {
        $start_time_cur = $start_time_cur - $one_day;
    }
    $start = $_REQUEST['week_range'] = date('Y-m-d', $start_time_cur);
    
    $html = '<script>
    function handleCourseClick(courseId, coursePeriodId, teacherId, weekDate) {
        console.log(\'Course clicked:\', {
            courseId: courseId,
            coursePeriodId: coursePeriodId,
            teacherId: teacherId,
            weekDate: weekDate
        });
        window.open(\'ForExport.php?modname=scheduling/Planification.php&modfunc=print&marking_period_id=\' + courseId + \'&week_range=\' + weekDate + \'&print_admin=true&_openSIS_PDF=true&report=true\', \'_blank\');
    }</script><div class="course-item text-center">';
    
    // Display course title (non-clickable)
    $html .= '<span style="color: #333;">'
        . htmlspecialchars($course['SHORT_NAME'], ENT_QUOTES, 'UTF-8')
        . '</span>';
    
    $html .= '<div style="font-size: 12px;">';
    
    // Get current week and next week timestamps
    $timeData = getCurrentAndNextWeekTimestamps();
    
    // Check planning status for both weeks
    $currentWeekMissing = check_planif($course['COURSE_ID'], $timeData['current_week']);
    $nextWeekMissing = check_planif($course['COURSE_ID'], $timeData['next_week']);
    
    // Format dates for display and use
    $currentWeekDate = date('Y-m-d', $timeData['current_week']);
    $nextWeekDate = date('Y-m-d', $timeData['next_week']);
    $currentWeekDisplay = dateFr('d-M', $timeData['current_week']);
    $nextWeekDisplay = dateFr('d-M', $timeData['next_week']);
    
    // Display current week status with clickable date
    $html .= '<br>' . getPlanningStatusIcon($currentWeekMissing);
    $html .= '<a href="javascript:void(0);" onclick="handleCourseClick('
        . intval($course['COURSE_ID']) . ', '
        . intval($course['COURSE_PERIOD_ID']) . ', '
        . intval($course['STAFF_ID']) . ', '
        . '\'' . $currentWeekDate . '\'' . ')" '
        . 'style="text-decoration: underline; color: #007bff; cursor: pointer;">'
        . htmlspecialchars($currentWeekDisplay, ENT_QUOTES, 'UTF-8')
        . '</a>';
    
    // Display next week status with clickable date
    $html .= '<br>' . getPlanningStatusIcon($nextWeekMissing);
    $html .= '<a href="javascript:void(0);" onclick="handleCourseClick('
        . intval($course['COURSE_ID']) . ', '
        . intval($course['COURSE_PERIOD_ID']) . ', '
        . intval($course['STAFF_ID']) . ', '
        . '\'' . $nextWeekDate . '\'' . ')" '
        . 'style="text-decoration: underline; color: #007bff; cursor: pointer;">'
        . htmlspecialchars($nextWeekDisplay, ENT_QUOTES, 'UTF-8')
        . '</a>';
    
    $html .= '</div></div>';
    
    return $html;
}
/**
 * Get timestamps for current week (Monday) and next week (Monday)
 * @return array Timestamps for current and next week
 */
function getCurrentAndNextWeekTimestamps() {
    $oneDay = 24 * 60 * 60; // seconds in a day
    $oneWeek = 7 * $oneDay; // seconds in a week
    
    // Get current week's Monday
    $currentWeekStart = strtotime(date('Y-m-d'));
    while (date('N', $currentWeekStart) != 1) {
        $currentWeekStart -= $oneDay;
    }
    
    return [
        'current_week' => $currentWeekStart,
        'next_week' => $currentWeekStart + $oneWeek
    ];
}

/**
 * Get the appropriate icon for planning status
 * @param bool $isMissing Whether planning is missing
 * @return string HTML icon
 */
function getPlanningStatusIcon($isMissing) {
    if ($isMissing) {
        return '<i class="fa fa-times fa-lg text-danger" title="Planning missing"></i> ';
    } else {
        return '<i class="fa fa-check fa-lg text-success" title="Planning complete"></i> ';
    }
}

/**
 * Check if planning is missing for a specific course and date
 * @param int $courseId Course ID
 * @param int $startTime Unix timestamp for the start date
 * @return bool True if planning is missing, false if present
 */
function check_planif($courseId, $startTime) {
    // Get course details
    $courseDetails = getCourseDetails($courseId);
    
    if (empty($courseDetails)) {
        return true; // No course found, consider planning missing
    }
    
    $gradeLevel = $courseDetails['GRADE_LEVEL'];
    $isPrimaryGrade = ($gradeLevel >= 1 && $gradeLevel <= 7);
    
    // Determine search parameters
    $searchCourseId = $isPrimaryGrade ? 0 : $courseId;
    $searchGradeLevel = $isPrimaryGrade ? $gradeLevel : 0;
    
    // Check for existing planning
    $planningExists = checkPlanningExists(
        date('Y-m-d', $startTime),
        $searchGradeLevel,
        $searchCourseId
    );
    
    return !$planningExists; // Return true if planning is missing
}

/**
 * Get course details from database
 * @param int $courseId Course ID
 * @return array|null Course details or null if not found
 */
function getCourseDetails($courseId) {
    $query = "
        SELECT GRADE_LEVEL, TEACHER_ID 
        FROM course_details 
        WHERE course_id = " . intval($courseId) . "
            AND syear = " . UserSyear() . "
        ORDER BY SHORT_NAME
    ";
    
    $result = DBGet(DBQuery($query));
    // echo '<pre>'; print_r($result); echo '</pre>';
    return !empty($result) ? $result[1] : null;
}

/**
 * Check if planning exists for given parameters
 * @param string $date Date in Y-m-d format
 * @param int $gradeLevel Grade level (0 for non-primary)
 * @param int $courseId Course ID (0 for primary grades)
 * @return bool True if planning exists
 */
function checkPlanningExists($date, $gradeLevel, $courseId) {
    $query = '
        SELECT COUNT(*) as count 
        FROM planification 
        WHERE start_date = "' . $date . '"
            AND is_primary = ' . intval($gradeLevel) . '
            AND course_id = ' . intval($courseId) .'
    ';
    
    $result = DBGet(DBQuery($query));
    // echo '<pre>'; print_r($result); echo '</pre>';
    return !empty($result) && $result[1]['COUNT'] > 0;
}

function do_cado_courses_files(){
    global $course_id,$default_course_id,$primaire,$primaire;
    // if(!$course_id) $course_id=$default_course_id;
    // if(!$course_id) return;
    $course_period_id = DBGet(DBQuery('SELECT COURSE_PERIOD_ID,TEACHER_ID FROM course_details WHERE course_id = ' . $course_id .' AND syear=' . UserSyear() . '  ORDER BY SHORT_NAME'));
    $search='%[';
    $search.=$course_period_id[1]['COURSE_PERIOD_ID'];
    $search.=']%';
    if($primaire){
        $search='%0-[PRI-';
        $search.=$primaire;
        $search.=']%';
        $course_period_id[1]['TEACHER_ID']=0;
    }
    //  echo $search;

    if(User('PROFILE') == 'teacher')
        $fileid = DBGet(DBQuery('SELECT * FROM user_file_upload WHERE name like "' . $search . '" AND PROFILE_ID=2 AND syear=' . UserSyear() . ' AND user_id=' . $course_period_id[1]['TEACHER_ID'] . ' AND FILE_INFO="stafffile" '));
    else
        $fileid = DBGet(DBQuery('SELECT * FROM user_file_upload WHERE name like "' . $search . '" AND PROFILE_ID=2 AND syear=' . UserSyear() . ' AND user_id=' . $course_period_id[1]['TEACHER_ID'] . ' AND FILE_INFO="stafffile" ORDER BY NAME'));
    if(count($fileid) || User('PROFILE') == 'teacher'){
        echo "<div id='upload-status' class='upload-box'>
        <span class='upload-text'>⏳ Téléchargement en cours... Veuillez patienter</span>
        </div>";
        echo '<div  class="dl-panel">';
        foreach ($fileid as $file){
            $ext=substr($file['NAME'], strpos($file['NAME'], '.') + 1);
            if ($ext == 'jpg' || $ext == 'jpeg' || $ext == 'png' || $ext == 'gif') {
                $fileIcon = '<i class="fa fa-file-image-o"></i>';
            } elseif ($ext == 'doc' || $ext == 'docx') {
                $fileIcon = '<i class="fa fa-file-word-o"></i>';
            } elseif ($ext == 'xls' || $ext == 'xlsx') {
                $fileIcon = '<i class="fa fa-file-excel-o"></i>';
            } elseif ($ext == 'ppt' || $ext == 'pptx') {
                $fileIcon = '<i class="fa fa-file-powerpoint-o"></i>';
            } elseif ($ext == 'pdf') {
                $fileIcon = '<i class="fa fa-file-pdf-o"></i>';
            } else {
                $fileIcon = '<i class="fa fa-file-o"></i>';
            }
            if($file['DOWNLOAD_ID'] && ! $_REQUEST['_openSIS_PDF']){
                $show_filename=strstr($file['NAME'], ']');
                $show_filename=trim($show_filename, "]");
                echo "<div>";
                if(User('PROFILE') == 'teacher')
                    echo '<button  class="minus-sign" onclick="deleteFile(`'.$file['NAME']  .'`);">X</button>';
                echo '<a class="custom-file-download" href="DownloadWindow.php?down_id=' . $file['DOWNLOAD_ID'] . '&stafffile=Y"> ' . $fileIcon . ''. $show_filename . '</a>';
                echo "</div>";
                echo '&nbsp&nbsp&nbsp';
            }
        }
        if(User('PROFILE') == 'teacher'){
            echo "<button  class='plus-sign' onclick=\"document.getElementById('actual-btn').click()\">+</button>";
            if(!count($fileid))
                echo "<button class='inserez-text' onclick=\"document.getElementById('actual-btn').click()\">&nbsp Inserez vos fichiers ici.</a>";
        }
    }
    echo "</div>";
    echo "<div>&nbsp</div>";
    echo "<form hidden action='Modules.php?modname=scheduling/Planification.php' method='POST' enctype='multipart/form-data'>
    <input type='hidden' name='course_period_id' value='";
    echo $course_period_id[1]['COURSE_PERIOD_ID'];
    echo"'>
    <input type='hidden' name='teacher_id' value='";
    echo $course_period_id[1]['TEACHER_ID'];
    echo "'>
    <label hidden for='actual-btn'>Choose a file to upload:</label><br><br>
    <input hidden type='file' name='files[]' id='actual-btn' onchange='showUploading(); this.form.submit()' multiple><br><br>
    <button hidden type='submit'>Upload</button>
    </form>";

}


?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planificateur Hebdomadaire</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0px;
        }
        
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        
        .week-section {
            margin-bottom: 0px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        
        th, td {
            border: 1px solid #333;
            padding: 4px;
            vertical-align: center;
            min-height: 40px;
            font-family: Arial, sans-serif;
            border: 1px solid #000000ff;
        }
        
        th {
            background-color: #a09b9bff;
            /* font-weight: bold; */
            text-align: center;
            font-family: Arial, sans-serif;
            border: 1px solid #000000ff;
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
            width: 6%;
            color: white;
        }
        
        .editable-student {
            /* background-color: #fff; */
            cursor: text;
            min-height: 95px;
            padding: 0px;
            border: none;
            width: 100%;
            resize: vertical;
            font-family: inherit;
            font-size: inherit;
        }
        .editable-student:focus {
            /* background-color: #c3ccd5ff; */
            outline: 1px solid #007cba;
        }
        .editable {
            /* background-color: #fff; */
            cursor: text;
            min-height: 101px;
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
            font-family: Arial, sans-serif;
            background: #d4d6d7ff;
            border: 1px solid #000000ff;
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
            text-align: right;
            /* margin-left: 10px; */
            /* display: flex;
            align-items: center; */
            /* gap: 5px; */
        }

        .auto-save-status.saving {
            display: flex;
            align-items: center;
            color: black;
            margin-left: auto; /* This pushes the element to the right */
            color: #ffc107;
        }

        .auto-save-status.saved {
            display: flex;
            align-items: center;
            color: black;
            margin-left: auto; /* This pushes the element to the right */
            color: #28a745;
        }

        .auto-save-status.error {
            color: #dc3545;
        }

        .auto-save-indicator {
            display: flex;
            align-items: center;
            color: black;
            margin-left: auto; /* This pushes the element to the right */
           font-size: 12px;
            text-align: right;
            color: #28a745;
        }

        .plus-sign {
            background: #24b245ff;
            /* 
            border: none;
            width: 25px;
            height: 25px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin-left: 4px; */
            color: white;
            border-radius: 4px;
            flex-shrink: 0;
            border: 1px solid #000000ff;
        }

        .plus-sign:hover {
            background: #18de43ff;
            /* border: 1px solid #000000ff; */
        }
        .content-cell {
            width: 28.33%;
            padding: 2px;
        }
    .format-btn.active {
        background: #007bff;
        color: white;
        border-color: #0056b3;
    }
    .formatting-toolbar {
        gap: 4px;
        /* border: 1px solid #dee2e6; */
        border-radius: 4px;
        padding: 1px;
        margin-bottom: 0px;
        display: flex;
        align-items: center;
        color: black;
    }

    .format_item {
        display: flex;
        align-items: center;
        color: black;
        margin-left: auto; /* This pushes the element to the right */
        align-self: center;
    }
        .formatting-toolbar.active {
            display: flex;
            align-items: left;
            color: black;
        }

        .format-btn {
            flex-shrink: 0;
            background: #fff;
            border: 1px solid #b0b4b7ff;
            border-radius: 3px;
            /* padding: 4px 8px; */
            margin: 0 2px;
            cursor: pointer;
            font-size: 10px;
            min-width: 30px;
            display: none;
        }

        .format-btn:hover {
            background: #e9ecef;
        }

        .format-btn.active {
            background: #007bff;
            color: white;
        }
        /* Specific styling for text-heavy buttons */
        .format-btn.list-btn {
            min-width: 60px; /* More space for list buttons */
            font-size: 11px; /* Slightly smaller font for longer text */
        }

        /* Support for formatted content */
        .editable b, .editable strong {
            font-weight: bold;
        }

        .editable i, .editable em {
            font-style: italic;
        }

        .editable u {
            text-decoration: underline;
        }

        .editable ul, .editable ol {
            margin: 0.5em 0;
            padding-left: 1.5em;
        }

        .editable li {
            margin: 0.2em 0;
        }

        /* Highlight styling */
        .editable mark,
        .editable .highlight {
            background-color: #ffff00;
            padding: 0 2px;
        }
        .minus-sign {
            background: #d3192bff;
            color: white;
            border: none;
            border-radius: 3px;
            width: 16px;
            height: 16px;
            font-size: 15px;
            /* font-weight: bold; */
            cursor: pointer;
            margin-right: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0; /* Prevents the button from shrinking */
        }

        .minus-sign:hover {
            background: #ff0019ff;
        }
        .custom-file-download {
            text-decoration: none;
            color: #2879caff;
            display: inline-flex;
            align-items: center;
            font-size: 14px;
            white-space: nowrap; /* Keeps icon and filename together */
        }
        .custom-file-download i {
            background: #a1baf2ff;
            color: black;
            margin-right: 6px;
            flex-shrink: 0;
        }

        .custom-file-download:hover {
            color: #007bff;
            text-decoration: underline;
        }  
        .dl-panel {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 1px;
            padding: 4px;
            font-family: Arial, sans-serif;
            background: #fdfeffff;
            border: 1px solid #000000ff;
        }

        /* Container for each file item (minus button + download link) */
        .dl-panel > div {
            display: inline-flex;
            align-items: center;
            white-space: nowrap; /* Prevents breaking between minus sign and filename */
            background: #fdfeffff;
            border: 1px solid #000000ff;
            border-radius: 4px;
            padding: 1px 4px;
            /* margin: 2px; */
        }
        .upload-box{
            display:none; 
            /* padding:10px;  */
            background: #ff0000ff; 
            border:1px solid #e60b0bff; 
            border-radius:4px; 
            text-align: center;
            /* margin-top:10px;            */
        }
        .upload-text{
            font-family: Arial, sans-serif;
            color: white; 
            font-size: 12px;
            font-weight:bold;
        }
        .inserez-text{
            font-family: Arial, sans-serif;
            color: black; 
            font-size: 12px;
            border:0px; 
            background: #fdfeffff;
            /* font-weight:bold;             */
        }

        .hidden-day td:not(.day-header) {
            background-image: 
                repeating-linear-gradient(
                    45deg,
                    transparent,
                    transparent 10px,
                    rgba(150, 150, 150, 0.3) 10px,
                    rgba(150, 150, 150, 0.3) 12px
                ),
                repeating-linear-gradient(
                    -45deg,
                    transparent,
                    transparent 10px,
                    rgba(150, 150, 150, 0.3) 10px,
                    rgba(150, 150, 150, 0.3) 12px
                );
            background-color: rgba(201, 202, 201, 0.4);
            opacity: 0.6;
        }
        
        .hidden-day .editable,
        .hidden-day .editable-student {
            pointer-events: none;
        }

        .hidden-day .editable,
        .hidden-day .editable-student {
            opacity: 0.5;
            pointer-events: none;
        }    
        @media (max-width: 768px) {
            .dl-panel {
                gap: 6px;
            }
            
            .dl-panel > div {
                margin: 1px;
                padding: 3px 5px;
            }
            
            .custom-file-download {
                font-size: 13px;
            }
        }
    </style>
</head>
            <?php
            if(! $_REQUEST['_openSIS_PDF'] && User('PROFILE') == "teacher"){
            echo '

            ';
            }
            ?>

<body>
            <?php
            if(! $_REQUEST['_openSIS_PDF'] && User('PROFILE') == "teacher"){
                echo'<div class="formatting-toolbar" id="formattingToolbar">
                    <button class="format-btn" id="italicBtn" onclick="formatText(\'italic\')">I</button>
                    <button class="format-btn" id="boldBtn" onclick="formatText(\'bold\')">B</button>
                    <button class="format-btn" id="underlineBtn" onclick="formatText(\'underline\')">U</button>
                    <button class="format-btn" id="highlightBtn" onclick="toggleHighlight()" title="Surligner">🖍</button>
                    <button class="format-btn list-btn" id="ulBtn" onclick="insertList(\'ul\')">• Liste</button>
                    <button class="format-btn list-btn" id="olBtn" onclick="insertList(\'ol\')">1. Liste</button>
                ';
                echo '<p class="format_item auto-save-status" id="autoSaveStatus">'; 
                if($updated_by){echo 'Dernière sauvegarde par -'; 
                    echo $updated_by;} 
                else echo'Sauvegarde automatique&nbsp'; 
                echo '</i>  <span class="auto-save-indicator"><span id="autoSaveText"></span></span>';
            }else{
            echo '
                        <div  class="auto-save-status hidden" id="autoSaveStatus">
                        <span class="auto-save-indicator hidden" ></span>
                        <span class="auto-save-indicator hidden id="autoSaveText"></span></div>

            ';
            }
        ?>
        
    </div>
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
        
            <!-- Lundi -->
            <tr class="<?php echo $mondayClass; ?>">
                <td class="day-header">Lundi</td>
                <td class="content-cell">
                    <div class="editable" 
                         contenteditable="<?php echo $editable ?>"
                         data-week="week1"
                         data-field="lundi_notions"><?php echo $data['week1']['lundi_notions']; ?></div>
                </td>
                <td class="content-cell">
                    <div class="editable" 
                         contenteditable="<?php echo $editable ?>"
                         data-week="week1" 
                         data-field="lundi_devoirs"><?php echo $data['week1']['lundi_devoirs']; ?></div>
                </td>
                <td class="content-cell">
                    <div class="editable" 
                         contenteditable="<?php echo $editable ?>"
                         data-week="week1" 
                         data-field="lundi_materiel"><?php echo $data['week1']['lundi_materiel']; ?></div>
                </td>
            </tr>
            
            <!-- Mardi -->
            <tr class="<?php echo $tuesdayClass; ?>">
                <td class="day-header">Mardi</td>
                <td class="content-cell">
                    <div class="editable" 
                         contenteditable="<?php echo $editable ?>"
                         data-week="week1" 
                         data-field="mardi_notions"><?php echo $data['week1']['mardi_notions']; ?></div>
                </td>
                <td class="content-cell">
                    <div class="editable" 
                         contenteditable="<?php echo $editable ?>"
                         data-week="week1" 
                         data-field="mardi_devoirs"><?php echo $data['week1']['mardi_devoirs']; ?></div>
                </td>
                <td class="content-cell">
                    <div class="editable" 
                         contenteditable="<?php echo $editable ?>"
                         data-week="week1" 
                         data-field="mardi_materiel"><?php echo $data['week1']['mardi_materiel']; ?></div>
                </td>
            </tr>
            
            <!-- Mercredi -->
            <tr class="<?php echo $wednesdayClass; ?>">
                <td class="day-header">Mercredi</td>
                <td class="content-cell">
                    <div class="editable" 
                         contenteditable="<?php echo $editable ?>"
                         data-week="week1" 
                         data-field="mercredi_notions"><?php echo $data['week1']['mercredi_notions']; ?></div>
                </td>
                <td class="content-cell">
                    <div class="editable" 
                         contenteditable="<?php echo $editable ?>"
                         data-week="week1" 
                         data-field="mercredi_devoirs"><?php echo $data['week1']['mercredi_devoirs']; ?></div>
                </td>
                <td class="content-cell">
                    <div class="editable" 
                         contenteditable="<?php echo $editable ?>"
                         data-week="week1" 
                         data-field="mercredi_materiel"><?php echo $data['week1']['mercredi_materiel']; ?></div>
                </td>
            </tr>
            
            <!-- Jeudi -->
            <tr class="<?php echo $thursdayClass; ?>">
                <td class="day-header">Jeudi</td>
                <td class="content-cell">
                    <div class="editable" 
                         contenteditable="<?php echo $editable ?>"
                         data-week="week1" 
                         data-field="jeudi_notions"><?php echo $data['week1']['jeudi_notions']; ?></div>
                </td>
                <td class="content-cell">
                    <div class="editable" 
                         contenteditable="<?php echo $editable ?>"
                         data-week="week1" 
                         data-field="jeudi_devoirs"><?php echo $data['week1']['jeudi_devoirs']; ?></div>
                </td>
                <td class="content-cell">
                    <div class="editable" 
                         contenteditable="<?php echo $editable ?>"
                         data-week="week1" 
                         data-field="jeudi_materiel"><?php echo $data['week1']['jeudi_materiel']; ?></div>
                </td>
            </tr>
            
            <!-- Vendredi -->
            <tr class="<?php echo $fridayClass; ?>">
                <td class="day-header">Vendredi</td>
                <td class="content-cell">
                    <div class="editable" 
                         contenteditable="<?php echo $editable ?>"
                         data-week="week1" 
                         data-field="vendredi_notions"><?php echo $data['week1']['vendredi_notions']; ?></div>
                </td>
                <td class="content-cell">
                    <div class="editable" 
                         contenteditable="<?php echo $editable ?>"
                         data-week="week1" 
                         data-field="vendredi_devoirs"><?php echo $data['week1']['vendredi_devoirs']; ?></div>
                </td>
                <td class="content-cell">
                    <div class="editable" 
                         contenteditable="<?php echo $editable ?>"
                         data-week="week1" 
                         data-field="vendredi_materiel"><?php echo $data['week1']['vendredi_materiel']; ?></div>
                </td>
            </tr>
        </table>
    </div>

    <script>
        const editableCells = document.querySelectorAll('.editable');
        const autoSaveStatus = document.getElementById('autoSaveStatus');
        const autoSaveText = document.getElementById('autoSaveText');
        const boldBtn = document.getElementById('boldBtn');
        const italicBtn = document.getElementById('italicBtn');
        const underlineBtn = document.getElementById('underlineBtn');
        const highlightBtn = document.getElementById('highlightBtn');
        const ulBtn = document.getElementById('ulBtn');
        const olBtn = document.getElementById('olBtn');

        let isEditingCell = false; // Track editing state
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
                cell.addEventListener('focus', function() {
        currentEditableElement = this;
        isEditingCell = true;
        showFormattingToolbar();
        updateToolbarButtons();
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

        function formatText(command) {
            if (currentEditableElement) {
                currentEditableElement.focus();
                document.execCommand(command, false, null);
                scheduleAutoSave(currentEditableElement);
                
                // Multiple updates with different delays to catch all scenarios
                setTimeout(() => updateToolbarButtons(), 10);
                setTimeout(() => updateToolbarButtons(), 50);
                setTimeout(() => updateToolbarButtons(), 100);
            }
        }        
        function showFormattingToolbar() {
            if (!isEditingCell) return;
            boldBtn.style.display = 'block';  
            italicBtn.style.display = 'block';  
            underlineBtn.style.display = 'block';  
            highlightBtn.style.display = 'block';  
            ulBtn.style.display = 'block';  
            olBtn.style.display = 'block';  
            updateToolbarButtons();
        }

        function hideFormattingToolbar() {
            if (isEditingCell) return;
            boldBtn.style.display = 'none';  
            italicBtn.style.display = 'none';  
            underlineBtn.style.display = 'none';  
            highlightBtn.style.display = 'none';  
            ulBtn.style.display = 'none';  
            olBtn.style.display = 'none';  
        }
        function insertList(listType) {
            if (currentEditableElement && isEditingCell) {
                currentEditableElement.focus();
                if (listType === 'ul') {
                    document.execCommand('insertUnorderedList', false, null);
                } else if (listType === 'ol') {
                    document.execCommand('insertOrderedList', false, null);
                }
                scheduleAutoSave(currentEditableElement);
                
                setTimeout(() => updateToolbarButtons(), 10);
                setTimeout(() => updateToolbarButtons(), 50);
                setTimeout(() => updateToolbarButtons(), 100);
            }
        }
        function updateToolbarButtons() {
            if (!currentEditableElement || !isEditingCell) return;
            
            // Get buttons by ID
            
            setTimeout(() => {
                try {
                    let isBold = false, isItalic = false, isUnderline = false, isHighlight = false, isUL = false, isOL = false;
                    
                    try {
                        isBold = document.queryCommandState('bold');
                        isItalic = document.queryCommandState('italic');
                        isUnderline = document.queryCommandState('underline');
                        isUL = document.queryCommandState('insertUnorderedList');
                        isOL = document.queryCommandState('insertOrderedList');
                        const selection = window.getSelection();
                        if (selection.rangeCount > 0) {
                            isHighlight = isTextHighlighted(selection.getRangeAt(0));
                        }
                    } catch (e) {
                        const result = getFormattingFromDOM();
                        isBold = result.isBold;
                        isItalic = result.isItalic;
                        isUnderline = result.isUnderline;
                        isUL = result.isUL;
                        isOL = result.isOL;
                    }
                    
                    // Update button states
                    if (boldBtn) boldBtn.classList.toggle('active', isBold);
                    if (italicBtn) italicBtn.classList.toggle('active', isItalic);
                    if (underlineBtn) underlineBtn.classList.toggle('active', isUnderline);
                    if (highlightBtn) highlightBtn.classList.toggle('active', isHighlight);
                    if (ulBtn) ulBtn.classList.toggle('active', isUL);
                    if (olBtn) olBtn.classList.toggle('active', isOL);
                    
                } catch (error) {
                    console.log('Error updating toolbar buttons:', error);
                }
            }, 10);
        }

        function getFormattingFromDOM() {
            const selection = window.getSelection();
            let element = null;
            
            if (selection.rangeCount > 0) {
                const range = selection.getRangeAt(0);
                element = range.commonAncestorContainer;
                
                if (element.nodeType === Node.TEXT_NODE) {
                    element = element.parentElement;
                }
            } else {
                element = currentEditableElement;
            }
            
            if (!element) {
                return { isBold: false, isItalic: false, isUnderline: false, isHighlight: false, isUL: false, isOL: false };
            }
            
            let isBold = false, isItalic = false, isUnderline = false, isHighlight = false, isUL = false, isOL = false;
            let current = element;
            
            while (current && current !== currentEditableElement && current !== document.body) {
                const tagName = current.tagName ? current.tagName.toUpperCase() : '';
                const style = window.getComputedStyle ? window.getComputedStyle(current) : current.style;
                
                if (!isBold && (tagName === 'B' || tagName === 'STRONG' || 
                    (style && (style.fontWeight === 'bold' || parseInt(style.fontWeight) >= 700)))) {
                    isBold = true;
                }
                
                if (!isItalic && (tagName === 'I' || tagName === 'EM' || 
                    (style && style.fontStyle === 'italic'))) {
                    isItalic = true;
                }
                
                if (!isUnderline && (tagName === 'U' || 
                    (style && style.textDecoration && style.textDecoration.includes('underline')))) {
                    isUnderline = true;
                }
                
                if (!isUL && tagName === 'UL') isUL = true;
                if (!isOL && tagName === 'OL') isOL = true;
                
                current = current.parentElement;
            }
            
            return { isBold, isItalic, isUnderline, isUL, isOL };
        }


        function saveCell(element) {
            const week = element.getAttribute('data-week');
            const field = element.getAttribute('data-field');
            const value = element.innerHTML;
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
            formData.append('auto_save', 1);
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
                    updateAutoSaveStatus('saved', `à ${now}`);
                } else {
                    throw new Error('Network response was not ok');
                }
            })
            .catch(error => {
                console.error('Manual save error:', error);
                updateAutoSaveStatus('error', 'Erreur de sauvegarde manuelle');
            });
        }

        // Delete file
        function deleteFile(delete_file) {
            const formData = new FormData();
            // console.log('File :', delete_file);
            post('Modules.php?modname=scheduling/Planification.php',{delete_file});
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
        function toggleHighlight() {
            if (currentEditableElement && isEditingCell) {
                currentEditableElement.focus();
                
                const selection = window.getSelection();
                if (!selection.rangeCount) return;
                
                const range = selection.getRangeAt(0);
                
                // Check if selection is already highlighted
                const isHighlighted = isTextHighlighted(range);
                
                if (isHighlighted) {
                    // Remove highlight
                    removeHighlight(range);
                } else {
                    // Add highlight
                    document.execCommand('hiliteColor', false, '#ffff00');
                }
                
                scheduleAutoSave(currentEditableElement);
                setTimeout(() => updateToolbarButtons(), 10);
                setTimeout(() => updateToolbarButtons(), 50);
                setTimeout(() => updateToolbarButtons(), 100);
            }
        }

        function isTextHighlighted(range) {
            let container = range.commonAncestorContainer;
            if (container.nodeType === Node.TEXT_NODE) {
                container = container.parentElement;
            }
            
            let current = container;
            while (current && current !== currentEditableElement) {
                const bgColor = window.getComputedStyle(current).backgroundColor;
                const tagName = current.tagName ? current.tagName.toUpperCase() : '';
                
                if (tagName === 'MARK' || 
                    bgColor === 'rgb(255, 255, 0)' || 
                    bgColor === 'yellow' ||
                    current.classList.contains('highlight')) {
                    return true;
                }
                current = current.parentElement;
            }
            return false;
        }

        function removeHighlight(range) {
            const selectedContent = range.extractContents();
            const span = document.createElement('span');
            span.appendChild(selectedContent);
            
            // Remove background color from all child elements
            const highlightedElements = span.querySelectorAll('[style*="background"]');
            highlightedElements.forEach(el => {
                el.style.backgroundColor = '';
                if (!el.getAttribute('style')) {
                    const parent = el.parentNode;
                    while (el.firstChild) {
                        parent.insertBefore(el.firstChild, el);
                    }
                    parent.removeChild(el);
                }
            });
            
            // Remove mark tags
            const markElements = span.querySelectorAll('mark');
            markElements.forEach(mark => {
                const parent = mark.parentNode;
                while (mark.firstChild) {
                    parent.insertBefore(mark.firstChild, mark);
                }
                parent.removeChild(mark);
            });
            
            range.insertNode(span);
            
            // Unwrap the span
            const parent = span.parentNode;
            while (span.firstChild) {
                parent.insertBefore(span.firstChild, span);
            }
            parent.removeChild(span);
        }
        if(document.readyState === 'complete') {
            post('Modules.php?modname=scheduling/Planification.php','auto_save');
        }
        function showUploading() {
            document.getElementById('upload-status').style.display = 'block';
        }
        // Click outside handler to hide toolbar
        document.addEventListener('click', function(e) {
            // Check if click is outside all editable cells and the toolbar
            const isOutsideEditable = !Array.from(editableCells).some(cell => cell.contains(e.target));
            const isOutsideToolbar = !formattingToolbar || !formattingToolbar.contains(e.target);
            
            if (isOutsideEditable && isOutsideToolbar) {
                isEditingCell = false;
                hideFormattingToolbar();
            }
             updateToolbarButtons();
        });
        document.addEventListener('keydown', function(e) {
                    
                    // Only process if we're in an editable cell
                    if (!currentEditableElement || !currentEditableElement.contains(document.activeElement) && 
                        document.activeElement !== currentEditableElement) {
                        return;
                    }
                    
                    // Handle Ctrl/Cmd + formatting shortcuts
                    if (e.ctrlKey || e.metaKey) {
                        let command = null;
                        switch(e.key.toLowerCase()) {
                            case 'b':
                                command = 'bold';
                                break;
                            case 'i':
                                command = 'italic';
                                break;
                            case 'u':
                                command = 'underline';
                                break;
                        }
                        
                        if (command) {
                            e.preventDefault(); // Prevent default browser behavior
                            document.execCommand(command, false, null);
                            scheduleAutoSave(currentEditableElement);
                            
                            // Update toolbar buttons after command execution
                            setTimeout(() => updateToolbarButtons(), 10);
                            setTimeout(() => updateToolbarButtons(), 50);
                            setTimeout(() => updateToolbarButtons(), 100);
                        }
                    }
                    
                    // Always update toolbar on any key press (for arrow keys, etc.)
                    setTimeout(() => updateToolbarButtons(), 10);
                });

</script>
<?php
if(! $_REQUEST['_openSIS_PDF'])
    do_cado_courses_files();
if(! $_REQUEST['_openSIS_PDF']){
    echo '</div>';
}

?>
</div>
</body>
</html>
