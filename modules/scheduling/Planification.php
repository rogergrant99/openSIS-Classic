<?php
include('lang/language.php');
include('../../RedirectModulesInc.php');
session_start();
DrawBC("" . _scheduling . " > " . ProgramTitle());

global $course_period_id,$course_id;

// Admin only sees completion statue
if (User('PROFILE') == 'admin'){
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
}

// Set default course id on initial load
if(!$course_id && User('PROFILE') != 'teacher'){
    $courses_RET = DBGet(DBQuery('SELECT DISTINCT c.TITLE , cp.COURSE_PERIOD_ID ,cp.COURSE_ID as ID,cp.TEACHER_ID AS STAFF_ID FROM schedule s,course_periods cp,course_period_var cpv,courses c,attendance_calendar acc WHERE s.SYEAR=\'' . UserSyear() . '\' AND cp.COURSE_PERIOD_ID=s.COURSE_PERIOD_ID  AND cp.COURSE_PERIOD_ID=cpv.COURSE_PERIOD_ID  AND (s.MARKING_PERIOD_ID IN (SELECT MARKING_PERIOD_ID FROM school_years WHERE SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE  UNION SELECT MARKING_PERIOD_ID FROM school_semesters WHERE SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE  UNION SELECT MARKING_PERIOD_ID FROM school_quarters WHERE SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE )or s.MARKING_PERIOD_ID  is NULL) AND (\'' . DBDate() . '\' BETWEEN s.START_DATE AND s.END_DATE OR \'' . DBDate() . '\'>=s.START_DATE AND s.END_DATE IS NULL) AND s.STUDENT_ID=\'' . UserStudentID() . '\' AND cp.GRADE_SCALE_ID IS NOT NULL' . (User('PROFILE') == 'teacher' ? ' AND cp.TEACHER_ID=\'' . User('STAFF_ID') . '\'' : '') . ' AND c.COURSE_ID=cp.COURSE_ID ORDER BY TITLE'));
    // print_r($courses_RET);
    $course_RET = DBGet(DBQuery('SELECT course_id,grade_level,teacher_id FROM course_details WHERE SYEAR=\'' . UserSyear() . '\' AND course_id=' . $courses_RET[1]['ID'] . ''));
    // print_r($course_RET);
    $course_id= $course_RET[1]['COURSE_ID'];
    if($course_RET[1]['GRADE_LEVEL'] >= '2' && $course_RET[1]['GRADE_LEVEL'] <= '7'){
        $primaire=$course_RET[1]['GRADE_LEVEL'];
        $temp_course_id=0;
        $course_id=0;
    }else{
        $primaire=0;
        $temp_course_id=$course_id;
    }
}

// Set teacher course
if (User('PROFILE') == 'teacher'){
    if(!UserCourse()) return;
    $course_RET = DBGet(DBQuery('SELECT course_id,grade_level,teacher_id FROM course_details WHERE SYEAR=\'' . UserSyear() . '\' AND course_id=' . UserCourse() . ''));
    if($course_RET[1]['GRADE_LEVEL'] >= '2' && $course_RET[1]['GRADE_LEVEL'] <= '7'){
        $primaire=$course_RET[1]['GRADE_LEVEL'];
        // $teacher_id=$course_RET[1]['TEACHER_ID'];
        $temp_course_id=0;
        $course_id=0;
    }else{
        $primaire=0;
        $course_id=$temp_course_id=UserCourse();
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
    $course_id = UserCourse();
    $editable=' class="editable" ';
}
else{
    $editable=' readonly class="editable-student" ';    
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
if ($_POST && isset($_POST['content'])) {
    $week =  $_POST['week'];
    $field =  $_POST['field'];
    $content = $_POST['content'];
    $_SESSION['schedule_data'][$week][$field]=$content;
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
function check_all_planif(){
    echo '<div class="panel panel-default">';
    $TI = DBQuery('SELECT DISTINCT STAFF_ID,CONCAT(LAST_NAME,\', \',FIRST_NAME) AS FULL_NAME,LAST_NAME,FIRST_NAME FROM staff  WHERE PROFILE_ID="2" ORDER BY LOWER(FULL_NAME) ');
    $teacher_RET= DBGet($TI);
    echo "<FORM class=\"no-margin\" action=Modules.php?modname=" . strip_tags(trim($_REQUEST[modname])) . " method=POST>";
    DrawHeader(_teacherCompletion, '<div class="form-inline"><div class="form-group"><label class="control-label ml-20 mr-20">-</label>' . $teacher_select.'</div></div>');
    echo '</FORM>';
    echo '<hr class="no-margin"/>';
    $sql = 'SELECT DISTINCT s.STAFF_ID,CONCAT(s.LAST_NAME,\', \',s.FIRST_NAME) AS FULL_NAME,cp.TITLE,cp.COURSE_PERIOD_ID,cp.SHORT_NAME,cp.COURSE_ID AS COURSE_ID FROM staff s,school_periods sp,course_periods cp
            WHERE cp.GRADE_SCALE_ID IS NOT NULL AND cp.TEACHER_ID=s.STAFF_ID AND cp.MARKING_PERIOD_ID IN (' . GetAllMP($mp_type, $cur_mp) . ') AND cp.SYEAR=\'' . UserSyear() . '\' AND cp.SCHOOL_ID=\'' . UserSchool() . '\' AND s.PROFILE=\'teacher\'
            ' . (($_REQUEST['period']) ? ' AND cp.COURSE_PERIOD_ID=\'' . $_REQUEST[period] . '\'' : 'ORDER BY  LOWER(cp.SHORT_NAME)') . '	
            ';
    $courses_RET = DBGet(DBQuery($sql));
    // print_r($courses_RET);
    if (count($teacher_RET)) {
        unset($i);
        foreach ($teacher_RET as $staff_id ) {
            if (count($courses_RET)) {
                unset($j);
                foreach ($courses_RET as $course ) {
                    if($staff_id['FULL_NAME'] == $course['FULL_NAME'] )
                    {
                        $i++;
                        $staff_RET[$i]  = '<font size="4" color=green><b><center>';
                        $staff_RET[$i] .= '&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp';
                        $staff_RET[$i] .= $staff_id['FULL_NAME'];
                        $staff_RET[$i] .= '&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp';
                        $staff_RET[$i] .= '</b></center>';
                        break;
                    }
                    $j++;
                }
            }
            if (count($courses_RET)) {
                unset($j);
                foreach ($courses_RET as $course ) {
                    if($staff_id['FULL_NAME'] == $course['FULL_NAME'] )
                    {
                        $j++;
                        $list_RET[$j][$i] = '<font size="4"><i> <u><center><b>';
                        $list_RET[$j][$i] .= $course['SHORT_NAME'];
                        $list_RET[$j][$i] .= '</b></font><font size="2">';
                        $one_day = 60 * 60 * 24;
                        $one_week = 60 * 60 * 24 * 7;
                        $start_time_cur = strtotime(date('Y-m-d'));
                        while (date('N', $start_time_cur) != 1) {
                            $start_time_cur = $start_time_cur - $one_day;
                        }
                        $bad_planif_this= check_planif($course['COURSE_ID'],$start_time_cur);
                        $bad_planif_next= check_planif($course['COURSE_ID'],$start_time_cur+$one_week);
                        if($bad_planif_this)
                            $list_RET[$j][$i] .= '<br><b style="color:red;"></b><i class="fa fa-times fa-lg text-danger"></i>' . htmlspecialchars(dateFr('d-M',$start_time_cur), ENT_QUOTES, 'UTF-8');
                        else 
                        $list_RET[$j][$i]  .= '<br><i class="fa fa-check fa-lg text-success"></i>' . htmlspecialchars(dateFr('d-M',$start_time_cur), ENT_QUOTES, 'UTF-8');
                        if($bad_planif_next)
                            $list_RET[$j][$i] .= '<br><b style="color:red;"></b><i class="fa fa-times fa-lg text-danger"></i>' . htmlspecialchars(dateFr('d-M',$start_time_cur + $one_week), ENT_QUOTES, 'UTF-8');
                        else 
                        $list_RET[$j][$i]  .= '<br><i class="fa fa-check fa-lg text-success"></i>' . htmlspecialchars(dateFr('d-M',$start_time_cur + $one_week), ENT_QUOTES, 'UTF-8');
            
                    }
                }
            }
        }
    }
    $options['search']=false;
    ListOutput($list_RET, $staff_RET, _teacherWhoHasnTEnteredGrades, "","","",$options);
    echo '</div>';
}

function check_planif($course_id,$start_time){
    $course_RET = DBGet(DBQuery('SELECT GRADE_LEVEL,TEACHER_ID FROM course_details WHERE course_id = ' . $course_id .' AND syear=' . UserSyear() . '  ORDER BY SHORT_NAME'));
    if($course_RET[1]['GRADE_LEVEL'] >= '2' && $course_RET[1]['GRADE_LEVEL'] <= '7'){
        $grade_level=$course_RET[1]['GRADE_LEVEL'];
        $course_id=0;
    }
    else
        $grade_level=0;
    $RET = DBGet(DBQuery('select * from planification where start_date=\'' . date('Y-m-d',$start_time) . '\' and is_primary=' . $grade_level  . '  and course_id=\'' . $course_id . '\''));
    if(count($RET))
        return false;
    return true;
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
            width: 7%;
            color: white;
        }
        
        .editable-student {
            /* background-color: #fff; */
            cursor: text;
            min-height: 75px;
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
            color: #ffc107;
        }

        .auto-save-status.saved {
            color: #28a745;
        }

        .auto-save-status.error {
            color: #dc3545;
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
                        <div class="auto-save-status" id="autoSaveStatus">
                        <div> <i>'; if($updated_by){echo 'Dernière sauvegarde par :'; echo $updated_by;} else echo'Sauvegarde automatique&nbsp'; echo '</i> 
                        <span class="auto-save-indicator"></span>
                        <span id="autoSaveText"></span></div>

            ';
            }else{
            echo '
                        <div  class="auto-save-status hidden" id="autoSaveStatus">
                        <span class="auto-save-indicator hidden" ></span>
                        <span class="auto-save-indicator hidden id="autoSaveText"></span></div>

            ';
            }
            ?>

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
                    <td><textarea <?php echo $editable ?>  data-week="week1" data-field="lundi_notions"><?php echo htmlspecialchars($data['week1']['lundi_notions']); ?></textarea></td>
                    <td><textarea <?php echo $editable ?>  data-week="week1" data-field="lundi_devoirs"><?php echo htmlspecialchars($data['week1']['lundi_devoirs']); ?></textarea></td>
                    <td><textarea <?php echo $editable ?>  data-week="week1" data-field="lundi_materiel"><?php echo htmlspecialchars($data['week1']['lundi_materiel']); ?></textarea></td>
                </tr>
                
                <tr>
                    <td class="day-header">Mardi</td>
                    <td><textarea <?php echo $editable ?>  data-week="week1" data-field="mardi_notions"><?php echo htmlspecialchars($data['week1']['mardi_notions']); ?></textarea></td>
                    <td><textarea <?php echo $editable ?>  data-week="week1" data-field="mardi_devoirs"><?php echo htmlspecialchars($data['week1']['mardi_devoirs']); ?></textarea></td>
                    <td><textarea <?php echo $editable ?>  data-week="week1" data-field="mardi_materiel"><?php echo htmlspecialchars($data['week1']['mardi_materiel']); ?></textarea></td>
                </tr>
                
                <tr>
                    <td class="day-header">Mercredi</td>
                    <td><textarea <?php echo $editable ?>  data-week="week1" data-field="mercredi_notions"><?php echo htmlspecialchars($data['week1']['mercredi_notions']); ?></textarea></td>
                    <td><textarea <?php echo $editable ?>  data-week="week1" data-field="mercredi_devoirs"><?php echo htmlspecialchars($data['week1']['mercredi_devoirs']); ?></textarea></td>
                    <td><textarea <?php echo $editable ?>  data-week="week1" data-field="mercredi_materiel"><?php echo htmlspecialchars($data['week1']['mercredi_materiel']); ?></textarea></td>
                </tr>
                
                <tr>
                    <td class="day-header">Jeudi</td>
                    <td><textarea <?php echo $editable ?>  data-week="week1" data-field="jeudi_notions"><?php echo htmlspecialchars($data['week1']['jeudi_notions']); ?></textarea></td>
                    <td><textarea <?php echo $editable ?>  data-week="week1" data-field="jeudi_devoirs"><?php echo htmlspecialchars($data['week1']['jeudi_devoirs']); ?></textarea></td>
                    <td><textarea <?php echo $editable ?>  data-week="week1" data-field="jeudi_materiel"><?php echo htmlspecialchars($data['week1']['jeudi_materiel']); ?></textarea></td>
                </tr>
                
                <tr>
                    <td class="day-header">Vendredi</td>
                    <td><textarea <?php echo $editable ?>  data-week="week1" data-field="vendredi_notions"><?php echo htmlspecialchars($data['week1']['vendredi_notions']); ?></textarea></td>
                    <td><textarea <?php echo $editable ?>  data-week="week1" data-field="vendredi_devoirs"><?php echo htmlspecialchars($data['week1']['vendredi_devoirs']); ?></textarea></td>
                    <td><textarea <?php echo $editable ?>  data-week="week1" data-field="vendredi_materiel"><?php echo htmlspecialchars($data['week1']['vendredi_materiel']); ?></textarea></td>
                </tr>
            </table>
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
        if(document.readyState === 'complete') {
            post('Modules.php?modname=scheduling/Planification.php','auto_save');
        }
        function showUploading() {
            document.getElementById('upload-status').style.display = 'block';
        }
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
