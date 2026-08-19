<?php
// Enable error reporting for debugging
$DEBUG = false; // Set to true to enable debug output

if ($DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

include('lang/language.php');
include('../../RedirectModulesInc.php');

session_start();

DrawBC("" . _scheduling . " > " . ProgramTitle());

global $course_period_id, $course_id;

$user_course = UserCourse();
if ($_REQUEST['print_admin']) {
    $user_course = $_REQUEST['marking_period_id'];
}

// Admin only sees completion status
if (User('PROFILE') == 'admin' && !$_REQUEST['print_admin']) {
    check_all_planif();
    exit;
}

// Week navigation
$one_day = 60 * 60 * 24;
$one_week = 60 * 60 * 24 * 7;
if ($_REQUEST && isset($_REQUEST['week_range'])) {
    $start = $_REQUEST['week_range'];
    $week1_date_start = dateFr('d-M', strtotime($_REQUEST['week_range']));
    $week1_sec = strtotime($_REQUEST['week_range']);
    $temp_course_id = $course_id = $_REQUEST['marking_period_id'];
    $primaire = 0;
    if ($course_id)
        update_days($course_id);
} else {
    if (!$_REQUEST['week_range']) {
        $start_time_cur = strtotime(dateFr('Y-m-d'));
        while (dateFr('N', $start_time_cur) != 1) {
            $start_time_cur = $start_time_cur - $one_day;
        }
        $start = $_REQUEST['week_range'] = date('Y-m-d', $start_time_cur);
        $week1_date_start = dateFr('d-M', strtotime($_REQUEST['week_range']));
        $week1_sec = strtotime($_REQUEST['week_range']);
    }
    $week1_date_start = dateFr('d-M', $start_time_cur);
    $week1_sec = $start_time_cur;
}

// Change course for secondary students
if ($_REQUEST['id']) {
    $course_id = $_REQUEST['id'];
    $course_RET = DBGet(DBQuery('SELECT grade_level,teacher_id FROM course_details WHERE SYEAR=\'' . UserSyear() . '\' AND course_id=' . $_REQUEST['id'] . ''));
    $course_id = $_REQUEST['id'];
    $grade_level = $course_RET[1]['GRADE_LEVEL'];
    $primaire = 0;
    $temp_course_id = $course_id;
    if ($course_id)
        update_days($course_id);
}

// Set default course id on initial load
if (!$course_id && User('PROFILE') != 'teacher') {
    $courses_RET = DBGet(DBQuery('SELECT DISTINCT c.TITLE , cp.DOES_NO_PLANNING, cp.COURSE_PERIOD_ID ,cp.COURSE_ID as ID,cp.TEACHER_ID AS STAFF_ID FROM schedule s,course_periods cp,course_period_var cpv,courses c,attendance_calendar acc WHERE s.SYEAR=\'' . UserSyear() . '\' AND cp.COURSE_PERIOD_ID=s.COURSE_PERIOD_ID  AND cp.COURSE_PERIOD_ID=cpv.COURSE_PERIOD_ID  AND (s.MARKING_PERIOD_ID IN (SELECT MARKING_PERIOD_ID FROM school_years WHERE SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE  UNION SELECT MARKING_PERIOD_ID FROM school_semesters WHERE SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE  UNION SELECT MARKING_PERIOD_ID FROM school_quarters WHERE SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE )or s.MARKING_PERIOD_ID  is NULL) AND (\'' . DBDate() . '\' BETWEEN s.START_DATE AND s.END_DATE OR \'' . DBDate() . '\'>=s.START_DATE AND s.END_DATE IS NULL) AND s.STUDENT_ID=\'' . UserStudentID() . '\' AND cp.GRADE_SCALE_ID IS NOT NULL' . (User('PROFILE') == 'teacher' ? ' AND cp.TEACHER_ID=\'' . User('STAFF_ID') . '\'' : '') . ' AND c.COURSE_ID=cp.COURSE_ID ORDER BY TITLE'));
    $course_RET = $courses_RET[1]['ID'] ? DBGet(DBQuery('SELECT course_id,grade_level,teacher_id FROM course_details WHERE SYEAR=\'' . UserSyear() . '\' AND course_id=' . $courses_RET[1]['ID'] . '')) : array();
    $course_id = $course_RET[1]['COURSE_ID'];
    if ($course_RET[1]['GRADE_LEVEL'] >= '1' && $course_RET[1]['GRADE_LEVEL'] <= '7') {
        $primaire = $course_RET[1]['GRADE_LEVEL'];
        $temp_course_id = 0;
        $course_id = 0;
    } else {
        $primaire = 0;
        $temp_course_id = $course_id;
        if ($course_id)
            update_days($course_id);
    }
}

// Set teacher course
if (User('PROFILE') == 'teacher' || $_REQUEST['print_admin']) {
    if (!$user_course) return;
    $course_RET = DBGet(DBQuery('SELECT does_no_planning , course_id,grade_level,teacher_id FROM course_details WHERE SYEAR=\'' . UserSyear() . '\' AND course_id=' . $user_course . ''));
    if ($course_RET[1]['DOES_NO_PLANNING'] == 'Y')
        return;
    if ($course_RET[1]['GRADE_LEVEL'] >= '1' && $course_RET[1]['GRADE_LEVEL'] <= '7') {
        $primaire = $course_RET[1]['GRADE_LEVEL'];
        $temp_course_id = 0;
        $course_id = 0;
        update_days($course_id);
    } else {
        $primaire = 0;
        $course_id = $temp_course_id = $user_course;
        if ($course_id)
            update_days($course_id);
    }
}

// Add course selector on multiple courses
if (!$_REQUEST['_openSIS_PDF'] && !$primaire) {
    $courses_RET = DBGet(DBQuery('SELECT DISTINCT c.TITLE , cp.COURSE_PERIOD_ID ,cp.COURSE_ID as ID,cp.TEACHER_ID AS STAFF_ID FROM schedule s,course_periods cp,course_period_var cpv,courses c,attendance_calendar acc WHERE s.SYEAR=\'' . UserSyear() . '\' AND cp.COURSE_PERIOD_ID=s.COURSE_PERIOD_ID  AND cp.COURSE_PERIOD_ID=cpv.COURSE_PERIOD_ID  AND (s.MARKING_PERIOD_ID IN (SELECT MARKING_PERIOD_ID FROM school_years WHERE SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE  UNION SELECT MARKING_PERIOD_ID FROM school_semesters WHERE SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE  UNION SELECT MARKING_PERIOD_ID FROM school_quarters WHERE SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE )or s.MARKING_PERIOD_ID  is NULL) AND (\'' . DBDate() . '\' BETWEEN s.START_DATE AND s.END_DATE OR \'' . DBDate() . '\'>=s.START_DATE AND s.END_DATE IS NULL) AND s.STUDENT_ID=\'' . UserStudentID() . '\' AND cp.GRADE_SCALE_ID IS NOT NULL' . (User('PROFILE') == 'teacher' ? ' AND cp.TEACHER_ID=\'' . User('STAFF_ID') . '\'' : '') . ' AND c.COURSE_ID=cp.COURSE_ID AND cp.DOES_NO_PLANNING is NULL  ORDER BY TITLE'));
    if (count($courses_RET)) {
        echo '<div class="form-inline"><div style="width: 300px;" class="col-md-12">' . CreateSelectAjax($courses_RET, 'id', $course_id, _selectCourse . ' : ') . '</div><br><br>';
        echo '<br>';
    }
}

// Teacher or student
if (User('PROFILE') == 'teacher') {
    $course_id = $user_course;
    $editable = 'true';
} else {
    $editable = 'false';
}

// Add files (keeping original functionality)
if (isset($_FILES['files'])) {
    $fileCount = count($_FILES['files']['name']);
    $dir = 'assets/stafffiles';
    for ($i = 0; $i < $fileCount; $i++) {
        if ($_FILES['files']['error'][$i] == UPLOAD_ERR_OK) {
            $fileName = $_FILES['files']['name'][$i];
            $fileTmpName = $_FILES['files']['tmp_name'][$i];
            $fileSize = $_FILES['files']['size'][$i];
            $fileType = $_FILES['files']['type'][$i];
            if ($primaire)
                $target_path = $dir . '/0-[PRI-' . $primaire . ']' . $fileName . '';
            else
                $target_path = $dir . '/' . $_POST['teacher_id'] . '-[' . $_POST['course_period_id'] . ']' . $fileName . '';

            $content = 'IN_DIR';
            $concat_filename = str_replace($dir . '/', '', $target_path);
            $concat_filename = str_replace("'", "\'", $concat_filename);
            if (file_exists($target_path)) {
                DBQuery('DELETE FROM user_file_upload WHERE USER_ID=\'' . $_POST['teacher_id'] . '\'AND NAME=\'' . $concat_filename . '\'');
                unlink($target_path);
            }
            move_uploaded_file($fileTmpName, "assets/stafffiles/" . $concat_filename);
            DBQuery('INSERT INTO user_file_upload (USER_ID,PROFILE_ID,SCHOOL_ID,SYEAR,NAME, SIZE, TYPE, CONTENT,FILE_INFO) VALUES (' . $_POST['teacher_id'] . ',\'2\',' . UserSchool() . ',' . UserSyear() . ',\'' . $concat_filename . '\', \'' . $fileSize . '\', \'' . $fileType . '\', \'' . $content . '\',\'stafffile\')');
        }
    }
}

// Get course name
if ($primaire) {
    $course = 'Planification primaire ';
    $course .= $primaire - 1;
    if ($primaire == 1)
        $course = 'Planification préscolaire ';
} else {
    $RET = DBGet(DBQuery('select short_name from course_details where course_id=\'' . $course_id . '\''));
    $course = 'Planification ';
    $course .= $RET[1]['SHORT_NAME'];
}

// Week 1
if ($week1_sec) {
    $RET = DBGet(DBQuery('select * from planification where start_date=\'' . dateFr('Y-m-d', $week1_sec) . '\'  and is_primary=' . $primaire . ' and course_id=\'' . $temp_course_id . '\''));
    $raw_content = base64_decode($RET[1]['TEXT']);
    if ($RET[1]['UPDATED_BY']) {
        $get_teacher = DBGet(DBQuery('SELECT CONCAT(FIRST_NAME," ",LAST_NAME) AS FULLNAME FROM staff  WHERE  STAFF_ID=' . $RET[1]['UPDATED_BY'] . ' '));
        $updated_by = $get_teacher[1]['FULLNAME'];
    }
    $_SESSION['schedule_data']['week1'] = unserialize($raw_content);
    foreach ($_SESSION['schedule_data']['week1'] as $key => $line) {
        $line = ltrim($line);
        $_SESSION['schedule_data']['week1'][$key] = str_replace(["\r\n", "\r", "\n"], '<br>', $line);
    }
}

// Initialize default data if not set
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
$one_week = 60 * 60 * 24 * 7;
$today = strtotime($_REQUEST['week_range']);
$week_start = dateFr('Y-m-d', $today);
$week_end = dateFr('Y-m-d', $today + $one_day * 6);
$next_week = strtotime($_REQUEST['next_week_range'] + $one_week);
$week_range = _makeWeeksAjax('', '', $start);

// Add print button
if (!$_REQUEST['_openSIS_PDF']) {
    DrawHeader($week_range, '<div class="form-inline"><div class="input-group"></div><FORM name="exp" class="no-margin-bottom" id="exp" action="ForExport.php?modname=' . urlencode(strip_tags(trim($_REQUEST["modname"]))) . '&modfunc=print&marking_period_id=' . urlencode($course_id) . '&week_range=' . urlencode($start) . '&_openSIS_PDF=true&report=true" method="POST" target="_blank"><div class="text-right"><INPUT type="submit" class="btn btn-primary" value="' . htmlspecialchars(_print, ENT_QUOTES) . '"></div></form><div class="input-group"><span class="input-group-addon" id="view_mode"></span></div></div>');
}

function CreateSelectAjax($val, $name, $opt, $cap) {
    $html = '<label class="control-label text-uppercase"><b>' . $cap . '</b></label>';
    $html .= "<select name='" . $name . "' id='" . $name . "' class=\"form-control\" onchange=\"changeCourse(this.value);\">";
    
    foreach ($val as $key => $value) {
        $selected = ($value[strtoupper($name)] == $opt) ? 'selected' : '';
        $html .= "<option " . $selected . " value='" . $value[strtoupper($name)] . "'>" . htmlspecialchars($value['TITLE']) . "</option>";
    }
    $html .= "</select>";
    return $html;
}

function update_days($course_id) {
    global $mondayClass, $tuesdayClass, $wednesdayClass, $thursdayClass, $fridayClass;

    if (!$course_id) {
        $mondayClass = $tuesdayClass = $wednesdayClass = $thursdayClass = $fridayClass = '';
        return;
    }

    $days_RET = DBGet(DBQuery('SELECT cpv.days FROM course_details cd JOIN course_period_var cpv WHERE SYEAR=\'' . UserSyear() . '\' AND course_id=' . $course_id . ' and cpv.course_period_id = cd.course_period_id'));
    foreach ($days_RET as $key => $days) {
        $result .= $days['DAYS'];
    }
    $array = str_split($result);
    $mondayClass = in_array('M', $array) ? '' : 'hidden-day';
    $tuesdayClass = in_array('T', $array) ? '' : 'hidden-day';
    $wednesdayClass = in_array('W', $array) ? '' : 'hidden-day';
    $thursdayClass = in_array('H', $array) ? '' : 'hidden-day';
    $fridayClass = in_array('F', $array) ? '' : 'hidden-day';
}

function _makeWeeksAjax($start, $end, $current_week) {
    $html = '';
    $one_day = 60 * 60 * 24;
    
    if (!$current_week) {
        $start_time_cur = strtotime(dateFr('Y-m-d'));
        while (dateFr('N', $start_time_cur) != 1) {
            $start_time_cur = $start_time_cur - $one_day;
        }
        $current_week = dateFr('Y-m-d', $start_time_cur);
    }

    $prev = dateFr('Y-m-d', strtotime($current_week) - $one_day * 7);
    $next = dateFr('Y-m-d', strtotime($current_week) + $one_day * 7);
    $upper = dateFr('Y-m-d', strtotime($current_week) + $one_day * 6);
    
    $html .= "<a href='javascript:void(0);' class=\"text-primary\" title='Previous' onclick=\"changeWeek('" . $prev . "');\"><i class=\"fa fa-angle-left\"></i> " . _prev . "</a> &nbsp; &nbsp; <span id=\"week-display\">" . properDateFr($current_week) . "&nbsp; - &nbsp;" . properDateFr($upper) . "</span> &nbsp; &nbsp; <a href='javascript:void(0);' title='Next' onclick=\"changeWeek('" . $next . "');\" class=\"text-primary\">" . _next . " <i class=\"fa fa-angle-right\"></i></a>";

    return $html;
}

function dateFr($format, $timestamp = null) {
    if ($timestamp === null) {
        $timestamp = time();
    }
    
    $months = [
        1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril',
        5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'août',
        9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre'
    ];
    
    $monthsShort = [
        1 => 'janv', 2 => 'févr', 3 => 'mars', 4 => 'avr',
        5 => 'mai', 6 => 'juin', 7 => 'juil', 8 => 'août',
        9 => 'sept', 10 => 'oct', 11 => 'nov', 12 => 'déc'
    ];
    
    $days = [
        0 => 'dimanche', 1 => 'lundi', 2 => 'mardi', 3 => 'mercredi',
        4 => 'jeudi', 5 => 'vendredi', 6 => 'samedi'
    ];
    
    $daysShort = [
        0 => 'dim', 1 => 'lun', 2 => 'mar', 3 => 'mer',
        4 => 'jeu', 5 => 'ven', 6 => 'sam'
    ];
    
    $result = date($format, $timestamp);
    
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

function properDateFr($date) {
    // Convert date to timestamp
    $timestamp = is_numeric($date) ? $date : strtotime($date);
    
    // Format as "dd-Mmm" (e.g., "23-févr")
    $day = date('d', $timestamp);
    $month_num = date('n', $timestamp);
    
    $months_short = [
        1 => 'janv', 2 => 'févr', 3 => 'mars', 4 => 'avr',
        5 => 'mai', 6 => 'juin', 7 => 'juil', 8 => 'août',
        9 => 'sept', 10 => 'oct', 11 => 'nov', 12 => 'déc'
    ];
    
    return $day . '-' . ucfirst($months_short[$month_num]);
}

if (!function_exists('properDate')) {
    function properDate($date) {
        return properDateFr($date);
    }
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
    
    // Separate primary/preschool courses from regular courses
    $separatedCourses = separatePrimaryAndRegularCourses($courses);
    
    // Build display data with separated columns
    $displayData = buildTeacherPlanningDisplayWithPrimaryColumn(
        $teachers, 
        $separatedCourses['regular'], 
        $separatedCourses['primary']
    );
    
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
 * Separate courses into primary/preschool and regular courses
 * @param array $courses List of all courses
 * @return array Array with 'primary' and 'regular' keys
 */
function separatePrimaryAndRegularCourses($courses) {
    $primary = [];
    $regular = [];
    $primaryGroups = [];
    
    foreach ($courses as $course) {
        $shortName = $course['SHORT_NAME'];
        $groupKey = determineGroupKey($shortName);
        
        if ($groupKey === 'regular') {
            // Regular course
            $regular[] = $course;
        } else {
            // Primary/Preschool course - group them
            if (!isset($primaryGroups[$groupKey])) {
                $groupedCourse = $course;
                $groupedCourse['SHORT_NAME'] = getGroupDisplayName($groupKey);
                $groupedCourse['IS_GROUPED'] = true;
                $groupedCourse['GROUP_KEY'] = $groupKey;
                $groupedCourse['ORIGINAL_COURSES'] = [$course];
                $primaryGroups[$groupKey] = $groupedCourse;
            } else {
                $primaryGroups[$groupKey]['ORIGINAL_COURSES'][] = $course;
            }
        }
    }
    
    // Sort primary groups (PRE, then Prim 1-6)
    uksort($primaryGroups, function($a, $b) {
        if ($a === 'pre') return -1;
        if ($b === 'pre') return 1;
        return strcmp($a, $b);
    });
    
    return [
        'primary' => array_values($primaryGroups),
        'regular' => $regular
    ];
}

function buildTeacherPlanningDisplayWithPrimaryColumn($teachers, $regularCourses, $primaryCourses) {
    $staffDisplay = [];
    $courseDisplay = [];
    
    $columnIndex = 0;
    
    // First column: Primary/Preschool
    if (!empty($primaryCourses)) {
        $columnIndex++;
        $staffDisplay[$columnIndex] = formatTeacherHeader('Primaire/Préscolaire');
        
        $courseIndex = 0;
        foreach ($primaryCourses as $course) {
            $courseIndex++;
            $courseDisplay[$courseIndex][$columnIndex] = formatCourseDisplay($course);
        }
    }
    
    // Remaining columns: Individual teachers with their regular courses
    foreach ($teachers as $teacher) {
        $teacherCourses = getTeacherCourses($teacher, $regularCourses);
        
        if (!empty($teacherCourses)) {
            $columnIndex++;
            $staffDisplay[$columnIndex] = formatTeacherHeader($teacher['FULL_NAME']);
            
            $courseIndex = 0;
            foreach ($teacherCourses as $course) {
                $courseIndex++;
                $courseDisplay[$courseIndex][$columnIndex] = formatCourseDisplay($course);
            }
        }
    }
    
    return [
        'teachers' => $staffDisplay,
        'courses' => $courseDisplay
    ];
}


function groupCoursesByType($courses) {
    $grouped = [];
    $processedGroups = [];
    
    foreach ($courses as $course) {
        $shortName = $course['SHORT_NAME'];
        $groupKey = determineGroupKey($shortName);
        
        if ($groupKey === 'regular') {
            // Regular course - add as is
            $grouped[] = $course;
        } else {
            // Group course (PRE or Prim X)
            if (!isset($processedGroups[$groupKey])) {
                // First course in this group - create a representative entry
                $groupedCourse = $course;
                $groupedCourse['SHORT_NAME'] = getGroupDisplayName($groupKey);
                $groupedCourse['IS_GROUPED'] = true;
                $groupedCourse['GROUP_KEY'] = $groupKey;
                $groupedCourse['ORIGINAL_COURSES'] = [$course];
                
                $processedGroups[$groupKey] = count($grouped);
                $grouped[] = $groupedCourse;
            } else {
                // Add to existing group
                $index = $processedGroups[$groupKey];
                $grouped[$index]['ORIGINAL_COURSES'][] = $course;
            }
        }
    }
    
    return $grouped;
}

function determineGroupKey($shortName) {
    $upperName = strtoupper($shortName);
    
    // Check for PRE courses
    if (strpos($upperName, 'PRE') === 0) {
        return 'pre';
    }
    
    // Check for Prim courses
    if (preg_match('/PRIM\s*(\d)/', $upperName, $matches)) {
        $level = $matches[1];
        if ($level >= 1 && $level <= 6) {
            return 'prim' . $level;
        }
    }
    
    return 'regular';
}

/**
 * Get display name for a course group
 * @param string $groupKey Group key
 * @return string Display name
 */
function getGroupDisplayName($groupKey) {
    if ($groupKey === 'pre') {
        return 'Préscolaire (PRE)';
    }
    
    if (preg_match('/prim(\d)/', $groupKey, $matches)) {
        return 'Primaire ' . $matches[1];
    }
    
    return $groupKey;
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
    global $mp_type, $cur_mp, $DEBUG;
    
    $query = "
        SELECT DISTINCT 
            s.STAFF_ID,
            CONCAT(s.LAST_NAME, ', ', s.FIRST_NAME) AS FULL_NAME,
            cp.TITLE,
            cp.COURSE_PERIOD_ID,
            cp.SHORT_NAME,
            cp.COURSE_ID,
            cp.COURSE_WEIGHT as WEIGHT,
            cd.GRADE_LEVEL
        FROM staff s
        JOIN course_periods cp ON cp.TEACHER_ID = s.STAFF_ID
        JOIN course_details cd ON cd.COURSE_ID = cp.COURSE_ID AND cd.SYEAR = cp.SYEAR
        JOIN school_periods sp ON 1=1
        WHERE cp.GRADE_SCALE_ID IS NOT NULL 
            AND cp.DOES_NO_PLANNING IS NULL
            AND cp.MARKING_PERIOD_ID IN (" . GetAllMP($mp_type, $cur_mp) . ")
            AND cp.SYEAR = '" . UserSyear() . "'
            AND cp.SCHOOL_ID = '" . UserSchool() . "'
            AND s.PROFILE = 'teacher'
    ";
    
    // Add period filter if specified
    if (!empty($_REQUEST['period'])) {
        $query .= " AND cp.COURSE_PERIOD_ID = '" . $_REQUEST['period'] . "'";
    } else {
        $query .= " ORDER BY cd.GRADE_LEVEL, LOWER(cp.SHORT_NAME)";
    }
    
    return DBGet(DBQuery($query));
}

/**
 * Build the display data structure for teachers and their courses
 * @param array $teachers List of teachers
 * @param array $courses List of courses
 * @return array Display data with teachers and courses
 */
/**
 * Build the display data structure for teachers and their courses
 * Modified to handle grouped courses
 * @param array $teachers List of teachers
 * @param array $courses List of courses (may include grouped courses)
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
    global $DEBUG;

    $one_day = 24 * 60 * 60;
    $start_time_cur = strtotime(dateFr('Y-m-d'));
    while (dateFr('N', $start_time_cur) != 1) {
        $start_time_cur = $start_time_cur - $one_day;
    }
    $start = $_REQUEST['week_range'] = date('Y-m-d', $start_time_cur);
    
    $html = '<script>
    function handleCourseClick(courseId, coursePeriodId, teacherId, weekDate) {
        window.open(\'ForExport.php?modname=scheduling/Planification.php&modfunc=print&marking_period_id=\' + courseId + \'&c_period_id=\' + coursePeriodId + \'&week_range=\' + weekDate + \'&print_admin=true&_openSIS_PDF=true&report=true\', \'_blank\');
    }</script><div class="course-item text-center">';
    
    // Display course title (non-clickable)
    $html .= '<span style="color: #333;">'
        . htmlspecialchars($course['SHORT_NAME'], ENT_QUOTES, 'UTF-8')
        . '</span>';
    
    $html .= '<div style="font-size: 12px;">';
    
    // Get current week and next week timestamps
    $timeData = getCurrentAndNextWeekTimestamps();
    
    // For grouped courses, check all courses in the group
    if (!empty($course['IS_GROUPED'])) {
        $currentWeekMissing = true;
        $nextWeekMissing = true;
        
        // Check if ANY course in the group has planning
        foreach ($course['ORIGINAL_COURSES'] as $originalCourse) {
            if (!check_planif($originalCourse['COURSE_ID'], $timeData['current_week'])) {
                $currentWeekMissing = false;
                break;
            }
        }
        
        foreach ($course['ORIGINAL_COURSES'] as $originalCourse) {
            if (!check_planif($originalCourse['COURSE_ID'], $timeData['next_week'])) {
                $nextWeekMissing = false;
                break;
            }
        }
        
        // Use the first course's ID for the link
        $displayCourseId = $course['ORIGINAL_COURSES'][0]['COURSE_ID'];
        $displayCoursePeriodId = $course['ORIGINAL_COURSES'][0]['COURSE_PERIOD_ID'];
        $displayStaffId = $course['ORIGINAL_COURSES'][0]['STAFF_ID'];
    } else {
        // Regular course - check normally
        $currentWeekMissing = check_planif($course['COURSE_ID'], $timeData['current_week']);
        $nextWeekMissing = check_planif($course['COURSE_ID'], $timeData['next_week']);
        
        $displayCourseId = $course['COURSE_ID'];
        $displayCoursePeriodId = $course['COURSE_PERIOD_ID'];
        $displayStaffId = $course['STAFF_ID'];
    }
    
    // Format dates for display and use
    $currentWeekDate = date('Y-m-d', $timeData['current_week']);
    $nextWeekDate = date('Y-m-d', $timeData['next_week']);
    $currentWeekDisplay = dateFr('d-M', $timeData['current_week']);
    $nextWeekDisplay = dateFr('d-M', $timeData['next_week']);
    
    // Display current week status with clickable date
    $html .= '<br>' . getPlanningStatusIcon($currentWeekMissing);
    $html .= '<a href="javascript:void(0);" onclick="handleCourseClick('
        . intval($displayCourseId) . ', '
        . intval($displayCoursePeriodId) . ', '
        . intval($displayStaffId) . ', '
        . '\'' . $currentWeekDate . '\'' . ')" '
        . 'style="text-decoration: underline; color: #007bff; cursor: pointer;">'
        . htmlspecialchars($currentWeekDisplay, ENT_QUOTES, 'UTF-8')
        . '</a>';
    
    // Display next week status with clickable date
    $html .= '<br>' . getPlanningStatusIcon($nextWeekMissing);
    $html .= '<a href="javascript:void(0);" onclick="handleCourseClick('
        . intval($displayCourseId) . ', '
        . intval($displayCoursePeriodId) . ', '
        . intval($displayStaffId) . ', '
        . '\'' . $nextWeekDate . '\'' . ')" '
        . 'style="text-decoration: underline; color: #007bff; cursor: pointer;">'
        . htmlspecialchars($nextWeekDisplay, ENT_QUOTES, 'UTF-8')
        . '</a>';
    
    if ($DEBUG) {
        $html .= '<br><small style="color: #999;">ID: ' . intval($displayCourseId) . ' | Period: ' . intval($displayCoursePeriodId) . '</small>';
    }

    $html .= '</div></div>';
    
    return $html;
}
/**
 * Get timestamps for current week (Monday) and next week (Monday)
 * @return array Timestamps for current and next week
 */
function getCurrentAndNextWeekTimestamps() {
    $oneDay = 24 * 60 * 60;
    $oneWeek = 7 * $oneDay;
    
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
    global $DEBUG;

    // Get course details
    $courseDetails = getCourseDetails($courseId);
    
    if (empty($courseDetails)) {
        if ($DEBUG) echo "<!-- check_planif: No course details found for courseId={$courseId} -->\n";
        return true; // No course found, consider planning missing
    }
    
    $gradeLevel = $courseDetails['GRADE_LEVEL'];
    $isPrimaryGrade = ($gradeLevel >= 1 && $gradeLevel <= 7);
    
    // Determine search parameters
    $searchCourseId = $isPrimaryGrade ? 0 : $courseId;
    $searchGradeLevel = $isPrimaryGrade ? $gradeLevel : 0;
    
    if ($DEBUG) {
        echo "<!-- check_planif: courseId={$courseId}, gradeLevel={$gradeLevel}, isPrimary=" . ($isPrimaryGrade ? 'true' : 'false') . ", searchCourseId={$searchCourseId}, searchGradeLevel={$searchGradeLevel} -->\n";
    }

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
    global $DEBUG;

    $query = "
        SELECT GRADE_LEVEL, TEACHER_ID 
        FROM course_details 
        WHERE course_id = " . intval($courseId) . "
            AND syear = " . UserSyear() . "
        ORDER BY SHORT_NAME
    ";
    
    $result = DBGet(DBQuery($query));
    if ($DEBUG) {
        echo '<pre>getCourseDetails result: ';
        print_r($result);
        echo '</pre>';
    }
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
    global $DEBUG;

    $query = '
        SELECT COUNT(*) as count 
        FROM planification 
        WHERE start_date = "' . $date . '"
            AND is_primary = ' . intval($gradeLevel) . '
            AND course_id = ' . intval($courseId) .'
    ';
    
    $result = DBGet(DBQuery($query));
    if ($DEBUG) {
        echo '<pre>checkPlanningExists(' . $date . ', gradeLevel=' . $gradeLevel . ', courseId=' . $courseId . '): ';
        print_r($result);
        echo '</pre>';
    }
    return !empty($result) && $result[1]['COUNT'] > 0;
}

function do_cado_courses_files() {
    global $course_id, $default_course_id, $primaire;
    if (!$course_id && $_REQUEST['c_period_id']) $course_id = $_REQUEST['c_period_id'];
    $course_period_id = $course_id ? DBGet(DBQuery('SELECT COURSE_PERIOD_ID,TEACHER_ID FROM course_details WHERE course_id = ' . $course_id . ' AND syear=' . UserSyear() . '  ORDER BY SHORT_NAME')) : array();
    $search = '%[';
    $search .= $course_period_id[1]['COURSE_PERIOD_ID'];
    $search .= ']%';
    if ($primaire) {
        $search = '%0-[PRI-';
        $search .= $primaire;
        $search .= ']%';
        $course_period_id[1]['TEACHER_ID'] = 0;
    }

    if (!isset($course_period_id[1]['TEACHER_ID'])) {
        $fileid = array();
    } elseif (User('PROFILE') == 'teacher')
        $fileid = DBGet(DBQuery('SELECT * FROM user_file_upload WHERE name like "' . $search . '" AND PROFILE_ID=2 AND syear=' . UserSyear() . ' AND user_id=' . $course_period_id[1]['TEACHER_ID'] . ' AND FILE_INFO="stafffile" '));
    else
        $fileid = DBGet(DBQuery('SELECT * FROM user_file_upload WHERE name like "' . $search . '" AND PROFILE_ID=2 AND syear=' . UserSyear() . ' AND user_id=' . $course_period_id[1]['TEACHER_ID'] . ' AND FILE_INFO="stafffile" ORDER BY NAME'));
    if (count($fileid) || User('PROFILE') == 'teacher') {
        echo "<div id='upload-status' class='upload-box'>
        <span class='upload-text'>⏳ Téléchargement en cours... Veuillez patienter</span>
        </div>";

        if (User('PROFILE') == 'admin')
            echo '    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">';
        echo '<div  class="dl-panel">';
        foreach ($fileid as $file) {
            $ext = substr($file['NAME'], strpos($file['NAME'], '.') + 1);
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
            if (($file['DOWNLOAD_ID'] && !$_REQUEST['_openSIS_PDF']) || $_REQUEST['c_period_id']) {
                $show_filename = strstr($file['NAME'], ']');
                $show_filename = trim($show_filename, "]");
                echo "<div>";
                if (User('PROFILE') == 'teacher')
                    echo '<button  class="minus-sign" onclick="deleteFile(\'' . $file['NAME'] . '\', ' . $course_period_id[1]['TEACHER_ID'] . ');">X</button>';
                echo '<a class="custom-file-download" href="DownloadWindow.php?down_id=' . $file['DOWNLOAD_ID'] . '&stafffile=Y"> ' . $fileIcon . '' . $show_filename . '</a>';
                echo "</div>";
                echo '&nbsp&nbsp&nbsp';
            }
        }
        if (User('PROFILE') == 'teacher') {
            echo "<button  class='plus-sign' onclick=\"document.getElementById('actual-btn').click()\">+</button>";
            if (!count($fileid))
                echo "<button class='inserez-text' onclick=\"document.getElementById('actual-btn').click()\">&nbsp Inserez vos fichiers ici.</a>";
        }
    }
    echo "</div>";
    echo "<div>&nbsp</div>";
    echo "<form hidden action='Modules.php?modname=scheduling/Planification.php' method='POST' enctype='multipart/form-data'>
    <input type='hidden' name='course_period_id' value='";
    echo $course_period_id[1]['COURSE_PERIOD_ID'];
    echo "'>
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
        /* Keep all original CSS here */
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
            text-align: center;
            font-family: Arial, sans-serif;
            border: 1px solid #000000ff;
        }
        
        .header-row {
            background-color: #fff;
            color: white;
        }
        
        .day-header {
            background-color: #5090c1;
            text-align: center;
            width: 6%;
            color: white;
        }
        
        .editable-student {
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
            outline: 1px solid #007cba;
        }
        .editable {
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
        }

        .auto-save-status.saving {
            display: flex;
            align-items: center;
            color: black;
            margin-left: auto;
            color: #ffc107;
        }

        .auto-save-status.saved {
            display: flex;
            align-items: center;
            color: black;
            margin-left: auto;
            color: #28a745;
        }

        .auto-save-status.error {
            color: #dc3545;
        }

        .auto-save-indicator {
            display: flex;
            align-items: center;
            color: black;
            margin-left: auto;
           font-size: 12px;
            text-align: right;
            color: #28a745;
        }

        .plus-sign {
            background: #24b245ff;
            color: white;
            border-radius: 4px;
            flex-shrink: 0;
            border: 1px solid #000000ff;
        }

        .plus-sign:hover {
            background: #18de43ff;
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
        .format-btn select,
        #fontSizeBtn {
            min-width: 80px;
            padding: 4px 6px;
            cursor: pointer;
            font-size: 11px;
        }

        .format-btn select option {
            padding: 2px;
        }

        .editable font[size="1"] { font-size: 10px; }
        .editable font[size="2"] { font-size: 13px; }
        .editable font[size="3"] { font-size: 16px; }
        .editable font[size="4"] { font-size: 18px; }
        .editable font[size="5"] { font-size: 24px; }
        .editable font[size="6"] { font-size: 32px; }
    .formatting-toolbar {
        gap: 4px;
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
        margin-left: auto;
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
        .format-btn.list-btn {
            min-width: 60px;
            font-size: 11px;
        }

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
            cursor: pointer;
            margin-right: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
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
            white-space: nowrap;
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

        .dl-panel > div {
            display: inline-flex;
            align-items: center;
            white-space: nowrap;
            background: #fdfeffff;
            border: 1px solid #000000ff;
            border-radius: 4px;
            padding: 1px 4px;
        }
        .upload-box{
            display:none; 
            background: #ff0000ff; 
            border:1px solid #e60b0bff; 
            border-radius:4px; 
            text-align: center;
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
        
        /* Loading overlay */
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.3);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        
        .loading-overlay.active {
            display: flex;
        }
        
        .loading-spinner {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>

<body>
    <!-- Loading overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner">
            <i class="fa fa-spinner fa-spin fa-2x"></i>
            <p>Chargement...</p>
        </div>
    </div>

    <?php
    if (!$_REQUEST['_openSIS_PDF'] && User('PROFILE') == "teacher") {
        echo '<div class="formatting-toolbar" id="formattingToolbar">';
        echo '<button class="format-btn" id="italicBtn" onclick="formatText(\'italic\')">I</button>';
        echo '<button class="format-btn" id="boldBtn" onclick="formatText(\'bold\')">B</button>';
        echo '<button class="format-btn" id="underlineBtn" onclick="formatText(\'underline\')">U</button>';
        echo '<button class="format-btn" id="highlightBtn" onclick="toggleHighlight()" title="Surligner">🖍</button>';
        echo '<select class="format-btn" id="fontSizeBtn" onchange="changeFontSize(this.value)" title="Taille de police">';
        echo '<option value="">Taille</option>';
        echo '<option value="1">Très petit</option>';
        echo '<option value="2">Petit</option>';
        echo '<option value="3">Normal</option>';
        echo '<option value="4">Grand</option>';
        echo '<option value="5">Très grand</option>';
        echo '<option value="6">Énorme</option>';
        echo '</select>';
        echo '<button class="format-btn list-btn" id="ulBtn" onclick="insertList(\'ul\')">• Liste</button>';
        echo '<button class="format-btn list-btn" id="olBtn" onclick="insertList(\'ol\')">1. Liste</button>';
        
        echo '<p class="format_item auto-save-status" id="autoSaveStatus">';
        echo '<span class="auto-save-indicator"><span id="autoSaveText"></span></span></p>';
        echo '</div>';
        
        // Pass updated_by to JavaScript for initial status
        if (!empty($updated_by)) {
            echo '<script>var initialUpdatedBy = ' . json_encode($updated_by) . ';</script>';
        } else {
            echo '<script>var initialUpdatedBy = null;</script>';
        }
    } else {
        echo '<div class="auto-save-status hidden" id="autoSaveStatus">';
        echo '<span class="auto-save-indicator hidden"></span>';
        echo '<span class="auto-save-indicator hidden" id="autoSaveText"></span>';
        echo '</div>';
        echo '<script>var initialUpdatedBy = null;</script>';
    }
    ?>
        
    
    <!-- Week 1 -->
    <div class="week-section">
        <table>
            <tr class="header-row">
                <td colspan="4" class="semaine" id="courseTitle">
                    <strong><?php echo $course ?> semaine du <i id="weekDisplay"> <?php echo $week1_date_start ?></i> </strong>
            </tr>
            <tr class="header-row">
                <th>Jour</th>
                <th>Notions et travail en classe</th>
                <th>Devoirs/Étude</th>
                <th>Matériel</th>
            </tr>
    
        <!-- Lundi -->
        <tr class="<?php echo $mondayClass; ?>" id="mondayRow">
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
        <tr class="<?php echo $tuesdayClass; ?>" id="tuesdayRow">
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
        <tr class="<?php echo $wednesdayClass; ?>" id="wednesdayRow">
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
        <tr class="<?php echo $thursdayClass; ?>" id="thursdayRow">
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
        <tr class="<?php echo $fridayClass; ?>" id="fridayRow">
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
    // Global variables
    const editableCells = document.querySelectorAll('.editable');
    const autoSaveStatus = document.getElementById('autoSaveStatus');
    const autoSaveText = document.getElementById('autoSaveText');
    const loadingOverlay = document.getElementById('loadingOverlay');
    
    let isEditingCell = false;
    let currentEditableElement = null;
    let currentWeek = '<?php echo $start; ?>';
    let currentCourseId = <?php echo $temp_course_id; ?>;
    let currentPrimaire = <?php echo $primaire; ?>;

    // Debug flag mirrored from PHP
    const DEBUG = <?php echo $DEBUG ? 'true' : 'false'; ?>;
    
    // Auto-save configuration
    let autoSaveTimeout;
    let hasUnsavedChanges = false;
    
    const AUTO_SAVE_DELAY = 500; // 500ms - feels instant but prevents too many requests
    
    // Initialize page data
    const pageData = {
        course_id: <?php echo $temp_course_id; ?>,
        primaire: <?php echo $primaire; ?>,
        week_start: '<?php echo $start; ?>'
    };
    
    // Use standalone Ajax endpoint (ajax_planification.php in root directory)
    const ajaxHandlerUrl = 'ajax_planification.php';
    
    if (DEBUG) console.log('Ajax Handler URL:', ajaxHandlerUrl);
    
    // Change week via Ajax
    function changeWeek(newWeek) {
        // CRITICAL: Save any pending changes BEFORE changing weeks
        if (hasUnsavedChanges && currentEditableElement) {
            clearTimeout(autoSaveTimeout);
            if (DEBUG) console.log('Saving pending changes before week change...');
            
            // Immediately save the current cell
            const capturedWeek = currentEditableElement.getAttribute('data-week');
            const capturedField = currentEditableElement.getAttribute('data-field');
            const capturedCourseId = pageData.course_id;
            const capturedPrimaire = pageData.primaire;
            const capturedWeekStart = pageData.week_start;
            const capturedContent = currentEditableElement.innerHTML;
            
            saveContentWithCapturedData(capturedWeek, capturedField, capturedContent, capturedCourseId, capturedPrimaire, capturedWeekStart);
        }
        
        hasUnsavedChanges = false;
        
        const formData = new FormData();
        formData.append('ajax_action', 'change_week');
        formData.append('week_range', newWeek);
        formData.append('course_id', pageData.course_id);
        formData.append('primaire', pageData.primaire);
        
        if (DEBUG) console.log('Changing week to:', newWeek);
        
        fetch(ajaxHandlerUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }
            return response.text();
        })
        .then(text => {
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                if (DEBUG) console.error('Response was not JSON:', text.substring(0, 200));
                throw new Error('Server returned invalid JSON. Check console for details.');
            }
            
            if (data.success) {
                // Update week display
                document.getElementById('weekDisplay').textContent = data.week_start;
                document.getElementById('courseTitle').innerHTML = 
                    '<strong>' + data.course_name + ' semaine du <i id="weekDisplay">' + data.week_start + '</i></strong>';
                
                // Update page data
                pageData.week_start = newWeek;
                currentWeek = newWeek;
                
                // Update all editable cells
                updateEditableCells(data.data);
                
                // Update navigation
                const weekDisplaySpan = document.getElementById('week-display');
                if (weekDisplaySpan) {
                    weekDisplaySpan.textContent = data.week_start + ' - ' + data.week_end;
                }
                
                // Update the prev/next links with new dates
                const prevLinks = document.querySelectorAll('a[onclick*="changeWeek"]');
                if (prevLinks.length >= 2) {
                    prevLinks[0].setAttribute('onclick', "changeWeek('" + data.prev_week + "');");
                    prevLinks[1].setAttribute('onclick', "changeWeek('" + data.next_week + "');");
                }
                
                // Update save status
                if (data.updated_by) {
                    updateAutoSaveStatus('saved', 'Dernière sauvegarde par - ' + data.updated_by);
                } else {
                    updateAutoSaveStatus('saved', 'Sauvegarde automatique');
                }
            } else {
                throw new Error(data.error || 'Unknown error occurred');
            }
        })
        .catch(error => {
            if (DEBUG) console.error('Error changing week:', error);
            updateAutoSaveStatus('error', 'Erreur: ' + error.message);
            alert('Erreur lors du changement de semaine: ' + error.message);
        });
    }
    
    // Change course via Ajax
    function changeCourse(courseId) {
        if (DEBUG) console.log('changeCourse called with courseId:', courseId);
        
        const isTeacher = <?php echo (User('PROFILE') == 'teacher' ? 'true' : 'false'); ?>;
        
        if (!isTeacher) {
            if (DEBUG) console.log('Student/parent detected - reloading page with new course');
            const baseUrl = window.location.origin + window.location.pathname;
            const reloadUrl = baseUrl + '?modname=scheduling/Planification.php&id=' + courseId;
            window.location.href = reloadUrl;
            return;
        }
        
        if (hasUnsavedChanges && currentEditableElement) {
            clearTimeout(autoSaveTimeout);
            if (DEBUG) console.log('Saving pending changes before course change...');
            
            const capturedWeek = currentEditableElement.getAttribute('data-week');
            const capturedField = currentEditableElement.getAttribute('data-field');
            const capturedCourseId = pageData.course_id;
            const capturedPrimaire = pageData.primaire;
            const capturedWeekStart = pageData.week_start;
            const capturedContent = currentEditableElement.innerHTML;
            
            saveContentWithCapturedData(capturedWeek, capturedField, capturedContent, capturedCourseId, capturedPrimaire, capturedWeekStart);
        }
        
        hasUnsavedChanges = false;
        
        const formData = new FormData();
        formData.append('ajax_action', 'change_course');
        formData.append('course_id', courseId);
        formData.append('week_range', pageData.week_start);
        
        if (DEBUG) console.log('Sending change_course request...');
        
        fetch(ajaxHandlerUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(text => {
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                if (DEBUG) console.error('Response was not JSON:', text.substring(0, 200));
                throw new Error('Server returned invalid JSON');
            }
            
            if (data.success) {
                pageData.course_id = data.course_id;
                pageData.primaire = data.primaire;
                currentCourseId = data.course_id;
                currentPrimaire = data.primaire;
                
                updateEditableCells(data.data);
                updateDayVisibility(data.days);
                
                const courseTitle = document.getElementById('courseTitle');
                if (courseTitle && data.course_name) {
                    const weekDisplay = document.getElementById('weekDisplay');
                    const weekText = weekDisplay ? weekDisplay.textContent : '';
                    courseTitle.innerHTML = '<strong>' + data.course_name + ' semaine du <i id="weekDisplay">' + weekText + '</i></strong>';
                }
                
                if (data.updated_by) {
                    updateAutoSaveStatus('saved', 'Dernière sauvegarde par - ' + data.updated_by);
                } else {
                    updateAutoSaveStatus('saved', 'Sauvegarde automatique');
                }
                
                if (DEBUG) console.log('Course changed successfully to:', data.course_id);
            } else {
                throw new Error(data.error || 'Unknown error');
            }
        })
        .catch(error => {
            if (DEBUG) console.error('Error changing course:', error);
            updateAutoSaveStatus('error', 'Erreur lors du changement de cours');
        });
    }
    
    // Update editable cells with new data
    function updateEditableCells(data) {
        if (!data) {
            if (DEBUG) console.warn('No data provided to updateEditableCells');
            return;
        }
        
        const weekData = data.week1 || data;
        
        if (!weekData) {
            if (DEBUG) console.warn('No week data found');
            return;
        }
        
        if (DEBUG) console.log('Updating cells with data:', JSON.stringify(weekData).substring(0, 200) + '...');
        
        const cells = document.querySelectorAll('.editable');
        
        cells.forEach(cell => {
            const field = cell.getAttribute('data-field');
            if (field) {
                cell.innerHTML = weekData[field] || '';
                setTimeout(() => autoLinkURLs(cell), 100);
            }
        });
        
        if (DEBUG) console.log('Updated', cells.length, 'cells');
    }
    
    // Update day visibility
    function updateDayVisibility(days) {
        if (!days) return;
        
        const dayMap = {
            'mondayRow': days.mondayClass,
            'tuesdayRow': days.tuesdayClass,
            'wednesdayRow': days.wednesdayClass,
            'thursdayRow': days.thursdayClass,
            'fridayRow': days.fridayClass
        };
        
        Object.keys(dayMap).forEach(rowId => {
            const row = document.getElementById(rowId);
            if (row) {
                row.className = dayMap[rowId];
            }
        });
    }
    
    // Auto-save with Ajax
    function saveContent(week, field, content) {
        const formData = new FormData();
        
        if (DEBUG) console.log('saveContent called:', { week, field, course_id: pageData.course_id, primaire: pageData.primaire, week_start: pageData.week_start });
        
        updateAutoSaveStatus('saving', 'Sauvegarde...');
        
        formData.append('ajax_action', 'auto_save');
        formData.append('week', week);
        formData.append('field', field);
        formData.append('content', content);
        formData.append('course_id', pageData.course_id);
        formData.append('primaire', pageData.primaire);
        formData.append('week_start', pageData.week_start);
        
        fetch(ajaxHandlerUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(text => {
            let data;
            try {
                data = JSON.parse(text);
                if (DEBUG) console.log('Save response:', data);
            } catch (e) {
                if (DEBUG) console.error('Response was not JSON:', text.substring(0, 200));
                throw new Error('Server returned invalid JSON');
            }
            
            if (data.success) {
                hasUnsavedChanges = false;
                updateAutoSaveStatus('saved', 'Dernière sauvegarde par - ' + data.updated_by + ' à ' + data.timestamp);
            } else {
                throw new Error(data.message || 'Save failed');
            }
        })
        .catch(error => {
            if (DEBUG) console.error('Auto-save error:', error);
            updateAutoSaveStatus('error', 'Erreur de sauvegarde');
        });
    }
    
    // Auto-save with captured context (used when context might have changed)
    function saveContentWithCapturedData(week, field, content, capturedCourseId, capturedPrimaire, capturedWeekStart) {
        const formData = new FormData();
        
        if (DEBUG) console.log('saveContentWithCapturedData called:', { week, field, course_id: capturedCourseId, primaire: capturedPrimaire, week_start: capturedWeekStart });
        
        updateAutoSaveStatus('saving', 'Sauvegarde...');
        
        formData.append('ajax_action', 'auto_save');
        formData.append('week', week);
        formData.append('field', field);
        formData.append('content', content);
        formData.append('course_id', capturedCourseId);
        formData.append('primaire', capturedPrimaire);
        formData.append('week_start', capturedWeekStart);
        
        fetch(ajaxHandlerUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(text => {
            let data;
            try {
                data = JSON.parse(text);
                if (DEBUG) console.log('Save response (captured):', data);
            } catch (e) {
                if (DEBUG) console.error('Response was not JSON:', text.substring(0, 200));
                throw new Error('Server returned invalid JSON');
            }
            
            if (data.success) {
                hasUnsavedChanges = false;
                updateAutoSaveStatus('saved', 'Dernière sauvegarde par - ' + data.updated_by + ' à ' + data.timestamp);
            } else {
                throw new Error(data.message || 'Save failed');
            }
        })
        .catch(error => {
            if (DEBUG) console.error('Auto-save error (captured):', error);
            updateAutoSaveStatus('error', 'Erreur de sauvegarde');
        });
    }
    
    // Delete file via Ajax
    function deleteFile(fileName, teacherId) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer ce fichier?')) {
            return;
        }
        
        if (DEBUG) console.log('Deleting file:', fileName, 'teacherId:', teacherId);
        
        const formData = new FormData();
        formData.append('ajax_action', 'delete_file');
        formData.append('file_name', fileName);
        formData.append('teacher_id', teacherId);
        
        fetch(ajaxHandlerUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(text => {
            if (DEBUG) console.log('Delete response:', text);
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                if (DEBUG) console.error('Response was not JSON:', text.substring(0, 200));
                throw new Error('Server returned invalid JSON');
            }
            
            if (data.success) {
                if (DEBUG) {
                    console.log('File deleted successfully');
                    console.log('DB deleted:', data.db_deleted);
                    console.log('Physical file deleted:', data.file_deleted);
                    console.log('File path:', data.file_path);
                    if (data.file_error) {
                        console.warn('File deletion warning:', data.file_error);
                    }
                }
                
                const baseUrl = window.location.origin + window.location.pathname;
                const reloadUrl = baseUrl + '?modname=scheduling/Planification.php';
                if (DEBUG) console.log('Reloading to:', reloadUrl);
                window.location.href = reloadUrl;
            } else {
                throw new Error(data.error || 'Failed to delete file');
            }
        })
        .catch(error => {
            if (DEBUG) console.error('Error deleting file:', error);
            alert('Erreur lors de la suppression du fichier: ' + error.message);
        });
    }
    
    function updateAutoSaveStatus(status, message) {
        autoSaveStatus.className = `auto-save-status ${status}`;
        autoSaveText.textContent = message;
    }
    
    // Schedule auto-save
    function scheduleAutoSave(element) {
        hasUnsavedChanges = true;
        clearTimeout(autoSaveTimeout);
        
        updateAutoSaveStatus('saving', 'Modification en cours...');
        
        if (DEBUG) console.log('Scheduling auto-save for:', element.getAttribute('data-field'));
        
        const capturedWeek = element.getAttribute('data-week');
        const capturedField = element.getAttribute('data-field');
        const capturedCourseId = pageData.course_id;
        const capturedPrimaire = pageData.primaire;
        const capturedWeekStart = pageData.week_start;
        
        autoSaveTimeout = setTimeout(() => {
            saveCell(element, capturedWeek, capturedField, capturedCourseId, capturedPrimaire, capturedWeekStart);
        }, AUTO_SAVE_DELAY);
    }
    
    function saveCell(element, capturedWeek, capturedField, capturedCourseId, capturedPrimaire, capturedWeekStart) {
        const week = capturedWeek || element.getAttribute('data-week');
        const field = capturedField || element.getAttribute('data-field');
        const value = element.innerHTML;
        
        if (DEBUG) console.log('Saving cell:', week, field, 'to week:', capturedWeekStart, 'length:', value.length);
        
        saveContentWithCapturedData(week, field, value, capturedCourseId, capturedPrimaire, capturedWeekStart);
    }
    
    // Auto-link URLs in text
    function autoLinkURLs(element) {
        const urlPattern = /(\b(https?|ftp):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/gim;
        
        const walker = document.createTreeWalker(
            element,
            NodeFilter.SHOW_TEXT,
            null,
            false
        );
        
        const textNodes = [];
        let node;
        
        while (node = walker.nextNode()) {
            if (node.parentElement.tagName !== 'A') {
                textNodes.push(node);
            }
        }
        
        textNodes.forEach(textNode => {
            const text = textNode.textContent;
            const matches = text.match(urlPattern);
            
            if (matches) {
                const fragment = document.createDocumentFragment();
                let lastIndex = 0;
                
                matches.forEach(url => {
                    const index = text.indexOf(url, lastIndex);
                    
                    if (index > lastIndex) {
                        fragment.appendChild(
                            document.createTextNode(text.substring(lastIndex, index))
                        );
                    }
                    
                    const link = document.createElement('a');
                    link.href = url;
                    link.textContent = url;
                    link.target = '_blank';
                    link.rel = 'noopener noreferrer';
                    link.style.color = '#007bff';
                    link.style.textDecoration = 'underline';
                    link.contentEditable = 'false';
                    
                    fragment.appendChild(link);
                    lastIndex = index + url.length;
                });
                
                if (lastIndex < text.length) {
                    fragment.appendChild(
                        document.createTextNode(text.substring(lastIndex))
                    );
                }
                
                textNode.parentNode.replaceChild(fragment, textNode);
            }
        });
    }
    
    // Formatting functions
    function formatText(command) {
        if (currentEditableElement) {
            currentEditableElement.focus();
            document.execCommand(command, false, null);
            scheduleAutoSave(currentEditableElement);
            setTimeout(() => updateToolbarButtons(), 10);
            setTimeout(() => updateToolbarButtons(), 50);
            setTimeout(() => updateToolbarButtons(), 100);
        }
    }
    
    function showFormattingToolbar() {
        if (!isEditingCell) return;
        const boldBtn = document.getElementById('boldBtn');
        const italicBtn = document.getElementById('italicBtn');
        const underlineBtn = document.getElementById('underlineBtn');
        const highlightBtn = document.getElementById('highlightBtn');
        const fontSizeBtn = document.getElementById('fontSizeBtn');
        const ulBtn = document.getElementById('ulBtn');
        const olBtn = document.getElementById('olBtn');
        
        if (boldBtn) boldBtn.style.display = 'block';
        if (italicBtn) italicBtn.style.display = 'block';
        if (underlineBtn) underlineBtn.style.display = 'block';
        if (highlightBtn) highlightBtn.style.display = 'block';
        if (fontSizeBtn) fontSizeBtn.style.display = 'block';
        if (ulBtn) ulBtn.style.display = 'block';
        if (olBtn) olBtn.style.display = 'block';
        updateToolbarButtons();
    }
    
    function hideFormattingToolbar() {
        if (isEditingCell) return;
        const boldBtn = document.getElementById('boldBtn');
        const italicBtn = document.getElementById('italicBtn');
        const underlineBtn = document.getElementById('underlineBtn');
        const highlightBtn = document.getElementById('highlightBtn');
        const fontSizeBtn = document.getElementById('fontSizeBtn');
        const ulBtn = document.getElementById('ulBtn');
        const olBtn = document.getElementById('olBtn');
        
        if (boldBtn) boldBtn.style.display = 'none';
        if (italicBtn) italicBtn.style.display = 'none';
        if (underlineBtn) underlineBtn.style.display = 'none';
        if (highlightBtn) highlightBtn.style.display = 'none';
        if (fontSizeBtn) fontSizeBtn.style.display = 'none';
        if (ulBtn) ulBtn.style.display = 'none';
        if (olBtn) olBtn.style.display = 'none';
    }
    
    function updateToolbarButtons() {
        if (!currentEditableElement || !isEditingCell) return;
        
        setTimeout(() => {
            try {
                let isBold = false, isItalic = false, isUnderline = false, isUL = false, isOL = false;
                
                try {
                    isBold = document.queryCommandState('bold');
                    isItalic = document.queryCommandState('italic');
                    isUnderline = document.queryCommandState('underline');
                    isUL = document.queryCommandState('insertUnorderedList');
                    isOL = document.queryCommandState('insertOrderedList');
                } catch (e) {
                    const result = getFormattingFromDOM();
                    isBold = result.isBold;
                    isItalic = result.isItalic;
                    isUnderline = result.isUnderline;
                    isUL = result.isUL;
                    isOL = result.isOL;
                }
                
                const boldBtn = document.getElementById('boldBtn');
                const italicBtn = document.getElementById('italicBtn');
                const underlineBtn = document.getElementById('underlineBtn');
                const ulBtn = document.getElementById('ulBtn');
                const olBtn = document.getElementById('olBtn');
                
                if (boldBtn) boldBtn.classList.toggle('active', isBold);
                if (italicBtn) italicBtn.classList.toggle('active', isItalic);
                if (underlineBtn) underlineBtn.classList.toggle('active', isUnderline);
                if (ulBtn) ulBtn.classList.toggle('active', isUL);
                if (olBtn) olBtn.classList.toggle('active', isOL);
                
            } catch (error) {
                if (DEBUG) console.log('Error updating toolbar buttons:', error);
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
            return { isBold: false, isItalic: false, isUnderline: false, isUL: false, isOL: false };
        }
        
        let isBold = false, isItalic = false, isUnderline = false, isUL = false, isOL = false;
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
    
    function toggleHighlight() {
        if (currentEditableElement && isEditingCell) {
            currentEditableElement.focus();
            const selection = window.getSelection();
            if (!selection.rangeCount) return;
            
            if (selection.isCollapsed) {
                const range = selection.getRangeAt(0);
                const highlightedElement = findHighlightedParent(range.startContainer);
                
                if (highlightedElement) {
                    const newRange = document.createRange();
                    newRange.selectNodeContents(highlightedElement);
                    selection.removeAllRanges();
                    selection.addRange(newRange);
                } else {
                    updateToolbarButtons();
                    return;
                }
            }
            
            const range = selection.getRangeAt(0);
            
            if (isTextHighlighted(range)) {
                document.execCommand('hiliteColor', false, 'transparent');
            } else {
                document.execCommand('hiliteColor', false, '#ffff00');
            }
            
            scheduleAutoSave(currentEditableElement);
            setTimeout(() => updateToolbarButtons(), 10);
        }
    }
    
    function findHighlightedParent(node) {
        let current = node;
        if (current.nodeType === Node.TEXT_NODE) {
            current = current.parentElement;
        }
        
        while (current && current !== currentEditableElement) {
            const bgColor = window.getComputedStyle(current).backgroundColor;
            const tagName = current.tagName ? current.tagName.toUpperCase() : '';
            
            if (tagName === 'MARK' || 
                bgColor === 'rgb(255, 255, 0)' || 
                bgColor === 'yellow' ||
                current.classList.contains('highlight')) {
                return current;
            }
            current = current.parentElement;
        }
        return null;
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
    
    function changeFontSize(size) {
        if (!size || !currentEditableElement || !isEditingCell) {
            const fontSizeBtn = document.getElementById('fontSizeBtn');
            if (fontSizeBtn) fontSizeBtn.value = '';
            return;
        }
        
        currentEditableElement.focus();
        
        const selection = window.getSelection();
        if (!selection.rangeCount || selection.isCollapsed) {
            const fontSizeBtn = document.getElementById('fontSizeBtn');
            if (fontSizeBtn) fontSizeBtn.value = '';
            return;
        }
        
        document.execCommand('fontSize', false, size);
        
        const fontSizeBtn = document.getElementById('fontSizeBtn');
        if (fontSizeBtn) fontSizeBtn.value = '';
        
        scheduleAutoSave(currentEditableElement);
        setTimeout(() => updateToolbarButtons(), 10);
    }
    
    // Initialize event listeners
    function initializeEventListeners() {
        const cells = document.querySelectorAll('.editable');
        if (DEBUG) {
            console.log('Initializing event listeners for', cells.length, 'editable cells');
            console.log('Page data:', JSON.stringify(pageData));
        }
        
        const isPrintMode = <?php echo (!empty($_REQUEST['_openSIS_PDF']) ? 'true' : 'false'); ?>;
        if (!isPrintMode) {
            if (typeof initialUpdatedBy !== 'undefined' && initialUpdatedBy) {
                updateAutoSaveStatus('saved', 'Dernière sauvegarde par - ' + initialUpdatedBy);
            } else {
                updateAutoSaveStatus('saved', 'Sauvegarde automatique');
            }
        }
        
        cells.forEach(cell => {
            autoLinkURLs(cell);
            
            cell.addEventListener('input', function() {
                if (DEBUG) console.log('Input event fired on cell:', this.getAttribute('data-field'));
                scheduleAutoSave(this);
            });
            
            cell.addEventListener('paste', function(e) {
                setTimeout(() => {
                    autoLinkURLs(this);
                    scheduleAutoSave(this);
                }, 10);
            });
            
            cell.addEventListener('focus', function() {
                currentEditableElement = this;
                isEditingCell = true;
                showFormattingToolbar();
                updateToolbarButtons();
            });
            
            cell.addEventListener('blur', function() {
                // Don't clear currentEditableElement here - we need it for saving on navigation
            });
        });
        
        if (DEBUG) console.log('Event listeners attached to', cells.length, 'cells');
    }
    
    if (document.readyState === 'loading') {
        if (DEBUG) console.log('DOM still loading, waiting for DOMContentLoaded...');
        document.addEventListener('DOMContentLoaded', initializeEventListeners);
    } else {
        if (DEBUG) console.log('DOM already loaded, initializing now...');
        initializeEventListeners();
    }
        
    // Handle keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (!currentEditableElement || !currentEditableElement.contains(document.activeElement) && 
            document.activeElement !== currentEditableElement) {
            return;
        }
        
        setTimeout(() => updateToolbarButtons(), 10);
        
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
                e.preventDefault();
                document.execCommand(command, false, null);
                scheduleAutoSave(currentEditableElement);
                
                setTimeout(() => updateToolbarButtons(), 10);
                setTimeout(() => updateToolbarButtons(), 50);
                setTimeout(() => updateToolbarButtons(), 100);
            }
        }
        
        setTimeout(() => updateToolbarButtons(), 10);
    });
    
    // Click outside handler
    document.addEventListener('click', function(e) {
        const formattingToolbar = document.getElementById('formattingToolbar');
        const isOutsideEditable = !Array.from(editableCells).some(cell => cell.contains(e.target));
        const isOutsideToolbar = !formattingToolbar || !formattingToolbar.contains(e.target);
        
        if (isOutsideEditable && isOutsideToolbar) {
            isEditingCell = false;
            hideFormattingToolbar();
        }
        updateToolbarButtons();
    });
    
    // Handle link clicks
    document.addEventListener('click', function(e) {
        if (e.target.tagName === 'A' && e.target.closest('.editable')) {
            if (e.ctrlKey || e.metaKey) {
                return;
            } else {
                window.open(e.target.href, '_blank', 'noopener,noreferrer');
            }
            e.preventDefault();
        }
    });
    
    // Warn user about unsaved changes
    window.addEventListener('beforeunload', function(e) {
        if (hasUnsavedChanges) {
            e.preventDefault();
            e.returnValue = '';
            return '';
        }
    });
    
    function showUploading() {
        document.getElementById('upload-status').style.display = 'block';
    }
</script>

<?php
if (!$_REQUEST['_openSIS_PDF'])
    do_cado_courses_files();
if (User('PROFILE') == 'admin' && $_REQUEST['print_admin'])
    do_cado_courses_files();
if (!$_REQUEST['_openSIS_PDF']) {
    echo '</div>';
}
?>

</body>
</html>