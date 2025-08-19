<?php
include('lang/language.php');
include('../../RedirectModulesInc.php');
require_once 'libraries/htmlpurifier/library/HTMLPurifier.auto.php';

session_start();
DrawBC("" . _scheduling . " > " . ProgramTitle());

global $course_period_id,$dynamic;
$dynamic=false;

// Configure HTML Purifier
function createHtmlPurifier() {
    $config = HTMLPurifier_Config::createDefault();
    $config->set('HTML.Allowed', 
        'b,p[class|style],br,strong,b,em,i,u,strike,del,h1,h2,h3,h4,h5,h6,' .
        'ul,ol,li,blockquote,pre,code,' .
        'table[style|class|width|cellspacing|cellpadding|border|width|margin|align],thead,tfoot,tr[style|class],td[style|class|valign|colspan],th[style|class|valign],' .
        'a[href|title|target],' .
        'img[src|alt|width|height|style],' .
        'div[style|class],span[style|class],font[color|style|size]'
    );
    $config->set('CSS.AllowedProperties', 
        'background-color,color,font-weight,font-style,text-decoration,font-variant-numeric,font-variant-east-asian,font-variant-alternates,font-size-adjust,font-kerning,font-optical-sizing,font-feature-settings,font-variation-settings,font-variant-position,font-variant-emoji,font-stretch,font-size,line-height,font-family,' .
        'border-style,border-width,border-color,margin-bottom,' .
        'text-align,padding,margin,border,width,height'
    );
    $config->set('Attr.AllowedFrameTargets', array('_blank'));
    $config->set('Cache.SerializerPath', '/tmp/htmlpurifier');    
    return new HTMLPurifier($config);
}

$purifier = createHtmlPurifier();

// Delete file
if ($_POST && isset($_POST['delete_file'])){
    $dir = 'assets/stafffiles';
    $target_path = $dir . '/' . $_POST['teacher_id'] . ''. $_POST['delete_file'] .'' . $fileName . '';
    DBQuery('DELETE FROM user_file_upload WHERE name="' .$_POST['delete_file'] . '"');
    unlink($target_path);
}

// Add file
if (isset($_FILES['files'])) {
    $fileCount = count($_FILES['files']['name']);
    $dir = 'assets/stafffiles';
    for ($i = 0; $i < $fileCount; $i++) {
        if ($_FILES['files']['error'][$i] == UPLOAD_ERR_OK) {
            $fileName = $_FILES['files']['name'][$i];
            $fileTmpName = $_FILES['files']['tmp_name'][$i];
            $fileSize = $_FILES['files']['size'][$i];
            $fileType = $_FILES['files']['type'][$i];
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

// Admin no planif
if (User('PROFILE') == 'admin'){
    check_all_planif();
    exit;
}
// Teacher  or student
if (User('PROFILE') == 'teacher'){
    $course_id = UserCourse();
    $editable=' class="editable" ';
}
else
    $editable=' readonly class="editable-student" ';

// Change course requests
if($_REQUEST['id']){
    $course_id  = $_REQUEST['id'];
}

// Week navigation
$one_day = 60 * 60 * 24;
$one_week = 60 * 60 * 24 * 7;
if ($_REQUEST && isset($_REQUEST['week_range'])){
    $start = $_REQUEST['week_range'];
    $week1_date_start = dateFr('d-M',strtotime($_REQUEST['week_range']));
    $week1_sec = strtotime($_REQUEST['week_range']);
    // $week2_date_start = dateFr('d-M',strtotime($_REQUEST['week_range']) + $one_week);
    // $week2_sec = strtotime($_REQUEST['week_range']) + $one_week;
    $course_id  = $_REQUEST['marking_period_id'];
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
        // $week2_date_start = dateFr('d-M',strtotime($_REQUEST['week_range']) + $one_week);
        // $week2_sec = strtotime($_REQUEST['week_range']) + $one_week;
    }
    $week1_date_start =  dateFr('d-M', $start_time_cur);
    $week1_sec = $start_time_cur;
    // $week2_date_start =  dateFr('d-M', $start_time_cur + $one_week);
    // $week2_sec = $start_time_cur + $one_week;
}

// Content save
if ($_POST && isset($_POST['content'])) {
    $week =  $_POST['week'];
    $field =  $_POST['field'];
    $content = $_POST['content'];
    $_SESSION['schedule_data'][$week][$field]=$content;
    if( $week  === 'week1' && $field){
        $RET = DBGet(DBQuery('select * from planification where start_date=\'' . dateFr('Y-m-d',$week1_sec) . '\'  and course_id=\'' . $course_id . '\''));
        if(!count($RET) && $content)
            DBQuery('INSERT INTO planification (start_date,course_id) VALUES  ("' .dateFr('Y-m-d',$week1_sec) .'", '. $course_id . ')'); 
        if(!$dynamic)
                $seralizedArray = serialize($_SESSION['schedule_data']['week1']);
        else 
                $seralizedArray =  $purifier->purify($content);
        $result = DBQuery('UPDATE  planification SET text =  "' . base64_encode($seralizedArray) . '"  WHERE course_id= '. $course_id . ' and start_date = "' . dateFr('Y-m-d',$week1_sec) . '" ');

    }
    // if( $week  === 'week2' && $field){
    //     $RET = DBGet(DBQuery('select * from planification where start_date=\'' . dateFr('Y-m-d',$week2_sec) . '\'  and course_id=\'' . $course_id . '\''));
    //     if(!count($RET)) 
    //         DBQuery('INSERT INTO planification (start_date,course_id) VALUES  ("' .dateFr('Y-m-d',$week2_sec) .'", '. $course_id . ')'); 
    //     $seralizedArray = serialize($_SESSION['schedule_data']['week2']);
    //     $result = DBQuery('UPDATE  planification SET text =  "' . base64_encode($seralizedArray) . '"  WHERE course_id= '. $course_id . ' and start_date = "' . dateFr('Y-m-d',$week2_sec) . '" ');
    // }
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
    if(!$dynamic)
        $_SESSION['schedule_data']['week1'] = unserialize($raw_content);
    else
        $content=$purifier->purify($raw_content);
}
// Week 2
// if($week2_sec){
//     $RET = DBGet(DBQuery('select * from planification where start_date=\'' . dateFr('Y-m-d',$week2_sec) . '\'  and course_id=\'' . $course_id . '\''));
//     $raw_content = base64_decode($RET[1]['TEXT']);
//     $_SESSION['schedule_data']['week2'] = unserialize($raw_content);
// }

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
        // ,
        // 'week2' => [
        //     'semaine' => '',
        //     'lundi_date' => '',
        //     'lundi_notions' => '',
        //     'lundi_devoirs' => '',
        //     'lundi_materiel' => '',
        //     'mardi_date' => '',
        //     'mardi_notions' => '',
        //     'mardi_devoirs' => '',
        //     'mardi_materiel' => '',
        //     'mercredi_date' => '',
        //     'mercredi_notions' => '',
        //     'mercredi_devoirs' => '',
        //     'mercredi_materiel' => '',
        //     'jeudi_date' => '',
        //     'jeudi_notions' => '',
        //     'jeudi_devoirs' => '',
        //     'jeudi_materiel' => '',
        //     'vendredi_date' => '',
        //     'vendredi_notions' => '',
        //     'vendredi_devoirs' => '',
        //     'vendredi_materiel' => ''
        // ]
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
        echo '<br>';
        $default_course_id=$courses_RET[1]['ID'];
    }
}

if(! $_REQUEST['_openSIS_PDF']){
    DrawHeader($week_range, '<div class="form-inline"><div class="input-group"></div><FORM name="exp" class="no-margin-bottom" id="exp" action="ForExport.php?modname=' . urlencode(strip_tags(trim($_REQUEST["modname"]))) . '&modfunc=print&marking_period_id=' . urlencode($course_id) . '&week_range=' . urlencode($start) . '&_openSIS_PDF=true&report=true" method="POST" target="_blank"><div class="text-right"><INPUT type="submit" class="btn btn-primary" value="' . htmlspecialchars(_print, ENT_QUOTES) . '"></div></form><div class="input-group"><span class="input-group-addon" id="view_mode"></span></div></div>');
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
    $RET = DBGet(DBQuery('select * from planification where start_date=\'' . date('Y-m-d',$start_time) . '\'  and course_id=\'' . $course_id . '\''));
    if(count($RET))
        return false;
    return true;
}

function do_cado_courses_files(){
    global $course_id,$default_course_id;
    if(!$course_id) $course_id=$default_course_id;
    if(!$course_id) return;
    $course_period_id = DBGet(DBQuery('SELECT COURSE_PERIOD_ID,TEACHER_ID FROM course_details WHERE course_id = ' . $course_id .' AND syear=' . UserSyear() . '  ORDER BY SHORT_NAME'));
    $search='%[';
    $search.=$course_period_id[1]['COURSE_PERIOD_ID'];
    $search.=']%';
    // echo $search;
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

if($dynamic)
    do_dynamic_editor();
else 
    do_static_editor();

function do_dynamic_editor(){
    global $content,$dynamic;

    $dynamic=true;
    echo '
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
            font-family: \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif;
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
            background: #f50303ff; 
            border:1px solid #e60b0bff; 
            border-radius:4px; 
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
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <div class="editor-container">
        <div class="editor-header">
        ' . (isset($course) ? '<h1><b>' . htmlspecialchars($course) . '</b></h1>' : '<h1><b>Éditeur</b></h1>') . '
        </div>
        
        <div class="toolbar">
            <div class="toolbar-group">
                <button onclick="execCmd(\'undo\')" title="Annuler">↶</button>
                <button onclick="execCmd(\'redo\')" title="Rétablir">↷</button>
            </div>
            
            <div class="toolbar-font">
                <select onchange="execCmd(\'fontSize\', this.value)">
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
                <button onclick="execCmd(\'bold\')" title="Gras"><strong>G</strong></button>
                <button onclick="execCmd(\'italic\')" title="Italique"><em>I</em></button>
                <button onclick="execCmd(\'underline\')" title="Souligné"><u>S</u></button>
                <button onclick="execCmd(\'strikeThrough\')" title="Barré"><strike>B</strike></button>
            </div>
            
            <div class="toolbar-group hidden">texte
                <input type="color" onchange="execCmd(\'foreColor\', this.value)" title="Couleur du texte" value="#000000" >
                arrière-plan
                <input type="color" onchange="execCmd(\'backColor\', this.value)" title="Couleur de fond" value="#ffffff" >
            </div>
            
            <div class="toolbar-group">
                <div class="color-picker-container">
                    <div class="color-trigger" onclick="toggleColorDropdown(\'textColorPicker\')">
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
                                <div class="recent-color clear-recent" onclick="clearRecentColors(\'text\')" title="Effacer l\'historique">×</div>
                            </div>
                        </div>
                        
                        <div class="custom-color-section">
                            <h4>⚙️ Couleur personnalisée</h4>
                            <div class="custom-color-input">
                                <input type="color" id="customTextColor" onchange="applyCustomColor(\'text\', this.value)">
                                <input type="text" id="customTextHex" placeholder="#000000" onchange="applyCustomHex(\'text\', this.value)">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="color-picker-container">
                    <div class="color-trigger" onclick="toggleColorDropdown(\'bgColorPicker\')">
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
                                <div class="recent-color clear-recent" onclick="clearRecentColors(\'bg\')" title="Effacer l\'historique">×</div>
                            </div>
                        </div>
                        
                        <div class="custom-color-section">
                            <h4>⚙️ Couleur personnalisée</h4>
                            <div class="custom-color-input">
                                <input type="color" id="customBgColor" onchange="applyCustomColor(\'bg\', this.value)">
                                <input type="text" id="customBgHex" placeholder="#ffffff" onchange="applyCustomHex(\'bg\', this.value)">
                            </div>
                        </div>
                    </div>
                </div>
            </div>              
            
            <div class="toolbar-group">
                <button onclick="execCmd(\'justifyLeft\')" title="Aligner à gauche">≡</button>
                <button onclick="execCmd(\'justifyCenter\')" title="Centrer">≣</button>
                <button onclick="execCmd(\'justifyRight\')" title="Aligner à droite">≡</button>
                <button onclick="execCmd(\'justifyFull\')" title="Justifier">≣</button>
            </div>
            
            <div class="toolbar-group">
                <button onclick="execCmd(\'insertUnorderedList\')" title="Liste à puces">• Liste</button>
                <button onclick="execCmd(\'insertOrderedList\')" title="Liste numérotée">1. Liste</button>
                <button onclick="execCmd(\'indent\')" title="Indenter">→|</button>
                <button onclick="execCmd(\'outdent\')" title="Désindenter">|←</button>
            </div>
            
            <div class="toolbar-group">
                <button onclick="insertTable()" title="Insérer un tableau">📊 Tableau</button>
                <button onclick="colorTableRows()" title="Colorer les lignes du tableau">🎨 Colorer lignes</button>
            </div>
        </div>
        
        <div id="editor" class="editor-content" contenteditable="true">' . (isset($content) ? $content : '') . '</div>
        
        <div class="editor-footer">
            <div style="display: flex; align-items: center;">
                <div class="word-count" id="wordCount">Nombre de mots: 0</div>
                <div>&nbsp;&nbsp;&nbsp;&nbsp;</div>
                <div class="word-count" id="charCount">Nombre de char: 0</div>
                <div class="auto-save-status" id="autoSaveStatus">
                    <span class="auto-save-indicator"></span>
                    <span id="autoSaveText">Auto-sauvegarde activée</span>
                </div>
            </div>
            <button class="save-btn" onclick="saveContent(content)">💾 Enregistrer le contenu</button>
        </div>
    </div>

    <!-- Modal pour les liens -->
    <div id="linkModal" class="modal">
        <div class="modal-content">
            <h3>Insérer un lien</h3>
            <input type="text" id="linkText" placeholder="Texte à afficher">
            <input type="url" id="linkUrl" placeholder="URL (https://exemple.com)">
            <div class="modal-buttons">
                <button class="btn-secondary" onclick="closeModal(\'linkModal\')">Annuler</button>
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
                <button class="btn-secondary" onclick="closeModal(\'tableModal\')">Annuler</button>
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
                        <label hidden for="headerEnabled">Activer l\'en-tête</label>
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
                    <button class="btn-secondary" onclick="closeModal(\'tableColorModal\')">Annuler</button>
                    <button class="btn-primary" onclick="applyTableColoring()">Appliquer</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const editor = document.getElementById(\'editor\');
        const autoSaveStatus = document.getElementById(\'autoSaveStatus\');
        const autoSaveText = document.getElementById(\'autoSaveText\');
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
                    const editor = document.getElementById(\'editor\');
                    editor.focus();
                    // Move cursor to end as fallback
                    const range = document.createRange();
                    range.selectNodeContents(editor);
                    range.collapse(false);
                    selection.addRange(range);
                }
            }
        }

        // Exécuter les commandes d\'édition
        function execCmd(command, value = null) {
            const editor = document.getElementById(\'editor\');
            
            // Ensure editor has focus but don\'t disturb selection
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
            const text = editor.innerText || editor.textContent || \'\';
            const words = text.trim().split(/\\s+/).filter(word => word.length > 0);
            const char = text.length;
            document.getElementById(\'wordCount\').textContent = `Nombre de mots : ${words.length}`;
            document.getElementById(\'charCount\').textContent = `Nombre de char : ${text.length}`;
            if(text.length > 40000){
                dont_save = true;
                alert(\'Texte trop long.... La sauvegarde n\\\'aura pas lieu. Enlevez du texte.\'); 
            }
            else
                dont_save = false;
        }
        
        // Insérer un lien
        function insertLink() {
            document.getElementById(\'linkModal\').style.display = \'block\';
        }
        
        function insertLinkAction() {
            const text = document.getElementById(\'linkText\').value;
            const url = document.getElementById(\'linkUrl\').value;
            
            if (url) {
                const linkHtml = text ? `<a href="${url}" target="_blank">${text}</a>` : `<a href="${url}" target="_blank">${url}</a>`;
                execCmd(\'insertHTML\', linkHtml);
            }
            
            closeModal(\'linkModal\');
            document.getElementById(\'linkText\').value = \'\';
            document.getElementById(\'linkUrl\').value = \'\';
        }
        
        // Insérer un tableau
        function insertTable() {
            document.getElementById(\'tableModal\').style.display = \'block\';
        }
        
        function insertTableAction() {
            const rows = parseInt(document.getElementById(\'tableRows\').value) || 3;
            const cols = parseInt(document.getElementById(\'tableCols\').value) || 3;
            let tableHtml = \'<table border="1" style="border-collapse: collapse; width: 100%; margin: 10px 0;">\';
            for (let i = 0; i < rows; i++) {
                tableHtml += \'<tr>\';
                for (let j = 0; j < cols; j++) {
                    tableHtml += \'<td style="padding: 8px; border: 1px solid #ddd;">&nbsp;</td>\';
                }
                tableHtml += \'</tr>\';
            }
            tableHtml += \'</table>\';
            execCmd(\'insertHTML\', tableHtml);            
            closeModal(\'tableModal\');
        }

        // Table row coloring functions
        function colorTableRows() {
            populateTableSelector();
            document.getElementById(\'tableColorModal\').style.display = \'block\';
        }

        function populateTableSelector() {
            const tables = editor.querySelectorAll(\'table\');
            const selector = document.getElementById(\'tableSelector\');
            
            // Clear existing options
            selector.innerHTML = \'<option value="">Choisir un tableau...</option>\';
            
            tables.forEach((table, index) => {
                const option = document.createElement(\'option\');
                option.value = index;
                option.textContent = `Tableau ${index + 1} (${table.rows.length} lignes)`;
                selector.appendChild(option);
            });
        }

        function applyTableColoring() {
            const tableIndex = document.getElementById(\'tableSelector\').value;
            const selectedOption = document.querySelector(\'input[name="colorOption"]:checked\');
            
            if (!tableIndex) {
                alert(\'Veuillez sélectionner un tableau.\');
                return;
            }
            
            const tables = editor.querySelectorAll(\'table\');
            const selectedTable = tables[parseInt(tableIndex)];
            
            if (!selectedTable) {
                alert(\'Tableau non trouvé.\');
                return;
            }
            
            // Apply header styling first if enabled
            const headerEnabled = document.getElementById(\'headerEnabled\').checked;
            if (headerEnabled) {
                applyHeaderStyling(selectedTable);
            }
            
            // Apply row coloring if selected
            if (selectedOption) {
                const colorOption = selectedOption.value;
                applyColoringToTable(selectedTable, colorOption, headerEnabled);
            }
            
            closeModal(\'tableColorModal\');
            triggerAutoSave();
        }

        function applyHeaderStyling(table) {
            const headerColor = document.getElementById(\'headerColor\').value;
            const headerTextColor = document.getElementById(\'headerTextColor\').value;
            const firstRow = table.querySelector(\'tr\');
            
            if (firstRow) {
                firstRow.style.backgroundColor = headerColor;
                firstRow.style.color = headerTextColor;
                firstRow.style.fontWeight = \'bold\';
                
                // Apply to all cells in the first row
                const cells = firstRow.querySelectorAll(\'td, th\');
                cells.forEach(cell => {
                    cell.style.backgroundColor = headerColor;
                    cell.style.color = headerTextColor;
                    cell.style.fontWeight = \'bold\';
                });
            }
        }

        function applyColoringToTable(table, colorOption, skipFirstRow = false) {
            const rows = table.querySelectorAll(\'tr\');
            const startIndex = skipFirstRow ? 1 : 0;
            
            for (let i = startIndex; i < rows.length; i++) {
                const row = rows[i];
                
                // Skip header row styling if it was already applied
                if (i === 0 && skipFirstRow) {
                    continue;
                }
                
                // Remove existing background color styles for non-header rows
                if (!(i === 0 && skipFirstRow)) {
                    row.style.backgroundColor = \'\';
                }
                
                const adjustedIndex = skipFirstRow ? i - 1 : i;
                
                switch (colorOption) {
                    case \'zebra-light\':
                        if (!(i === 0 && skipFirstRow)) {
                            row.style.backgroundColor = adjustedIndex % 2 === 0 ? \'#f8f9fa\' : \'white\';
                        }
                        break;
                    case \'zebra-blue\':
                        if (!(i === 0 && skipFirstRow)) {
                            row.style.backgroundColor = adjustedIndex % 2 === 0 ? \'#e3f2fd\' : \'white\';
                        }
                        break;
                    case \'zebra-green\':
                        if (!(i === 0 && skipFirstRow)) {
                            row.style.backgroundColor = adjustedIndex % 2 === 0 ? \'#e8f5e8\' : \'white\';
                        }
                        break;
                    case \'zebra-yellow\':
                        if (!(i === 0 && skipFirstRow)) {
                            row.style.backgroundColor = adjustedIndex % 2 === 0 ? \'#fff8e1\' : \'white\';
                        }
                        break;
                    case \'custom\':
                        if (!(i === 0 && skipFirstRow)) {
                            const color1 = document.getElementById(\'customColor1\').value;
                            const color2 = document.getElementById(\'customColor2\').value;
                            row.style.backgroundColor = adjustedIndex % 2 === 0 ? color1 : color2;
                        }
                        break;
                    case \'remove\':
                        if (!(i === 0 && skipFirstRow)) {
                            row.style.backgroundColor = \'\';
                            row.removeAttribute(\'style\');
                        }
                        break;
                }
            }
        }
        
        // Fermer les modales
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = \'none\';
        }
        
        // Fermer les modales en cliquant à l\'extérieur
        window.onclick = function(event) {
            const modals = document.querySelectorAll(\'.modal\');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = \'none\';
                }
            });
        }
        
        // Sauvegarder le contenu
        function saveContent() {
            const content = editor.innerHTML;
            const formData = new FormData();

            updateAutoSaveStatus(\'saving\', \'Sauvegarde manuelle...\');

            formData.append(\'week\', \'week1\');
            formData.append(\'field\', \'f1\');
            formData.append(\'content\', content);
            // console.log(\'Full href:\', window.location.href);
            // console.log(\'Pathname only:\', window.location.pathname);
            // console.log(\'Search params:\', window.location.search);
            // console.log(\'Hash:\', window.location.hash);
            //post(\'Modules.php?modname=scheduling/Planification.php\',{content});
            fetch(window.location.href, {
                method: \'POST\',
                body: formData
            })
            .then(response => {
                if (response.ok) {
                    lastSavedContent = content;
                    hasUnsavedChanges = false;
                    const now = new Date().toLocaleTimeString(\'fr-FR\');
                    updateAutoSaveStatus(\'saved\', `Sauvegardé à ${now}`);
                } else {
                    throw new Error(\'Network response was not ok\');
                }
            })
            .catch(error => {
                console.error(\'Manual save error:\', error);
                updateAutoSaveStatus(\'error\', \'Erreur de sauvegarde manuelle\');
            });
        }

        // Sauvegarder le contenu
        function saveContent2() {
            const content = editor.innerHTML;
            const week = \'week1\';
            const field = \'f1\';
            if(dont_save) return;
            const form = document.createElement(\'form\');
            form.method = \'POST\';
            form.style.display = \'none\';
            
            const input = document.createElement(\'input\');
            input.type = \'hidden\';
            input.name = \'content\';
            input.value = content;
            input.name = \'week\';
            input.value = week;
            input.name = \'field\';
            input.value = field;
            
            form.appendChild(input);
            document.body.appendChild(form);
            post(\'Modules.php?modname=scheduling/Planification.php\',{week,field,content});
            document.body.removeChild(form);
        }
           
        function autoSaveContent() {
            const currentContent = editor.innerHTML;
            
            // Only save if content has actually changed
            if (currentContent === lastSavedContent) {
                hasUnsavedChanges = false;
                return;
            }
            
            updateAutoSaveStatus(\'saving\', \'Sauvegarde automatique...\');
            
            // Create form data
            const formData = new FormData();
            formData.append(\'content\', currentContent);
            formData.append(\'auto_save\', \'1\'); // This tells PHP to return JSON
            
            // Send AJAX request
            fetch(window.location.href, {
                method: \'POST\',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const contentType = response.headers.get(\'content-type\');
                if (!contentType || !contentType.includes(\'application/json\')) {
                    throw new Error(\'Response is not JSON\');
                }
                
                return response.json();
            })
            .then(data => {
                if (data && data.success) {
                    lastSavedContent = currentContent;
                    hasUnsavedChanges = false;
                    const now = new Date().toLocaleTimeString(\'fr-FR\');
                    updateAutoSaveStatus(\'saved\', `Auto-sauvegardé à ${now}`);
                } else {
                    console.error(\'Auto-save failed:\', data);
                    updateAutoSaveStatus(\'error\', \'Erreur: \' + (data?.message || \'Réponse invalide\'));
                }
            })
            .catch(error => {
                console.error(\'Auto-save error:\', error);
                updateAutoSaveStatus(\'error\', \'Erreur de sauvegarde automatique\');
            });
        }

        function post(path, params, method=\'post\') {
            // The rest of this code assumes you are not using a library.
            // It can be made less verbose if you use one.
            const form = document.createElement(\'form\');
            form.method = method;
            form.action = path;
            for (const key in params) {
                if (params.hasOwnProperty(key)) {
                    const hiddenField = document.createElement(\'input\');
                    hiddenField.type = \'hidden\';
                    hiddenField.name = key;
                    hiddenField.value = params[key];
                    form.appendChild(hiddenField);
                }
            }
            document.body.appendChild(form);
            form.submit();
        }

        // Event listeners for auto-save
        editor.addEventListener(\'input\', function() {
            updateWordCount();
            triggerAutoSave();
        });
        
        editor.addEventListener(\'paste\', function() {
            setTimeout(() => {
                triggerAutoSave();
            }, 100);
        });

        // Mettre à jour le compteur de mots en temps réel
        editor.addEventListener(\'input\', updateWordCount);
        editor.addEventListener(\'mouseup\', updateToolbarState);
        editor.addEventListener(\'keyup\', updateToolbarState);
        editor.addEventListener(\'focus\', updateToolbarState);

        // Initialiser le compteur de mots
        updateWordCount();
        startPeriodicAutoSave(); // Start the periodic auto-save

        if(document.readyState === \'complete\') {
            post(\'Modules.php?modname=scheduling/Planification.php\',\'\');
            execCmd(\'fontSize\', \'3\');
        }

        // Gestion des raccourcis clavier
        editor.addEventListener(\'keydown\', function(e) {
            if (e.ctrlKey || e.metaKey) {
                switch(e.key) {
                    case \'b\':
                        e.preventDefault();
                        execCmd(\'bold\');
                        break;
                    case \'i\':
                        e.preventDefault();
                        execCmd(\'italic\');
                        break;
                    case \'u\':
                        e.preventDefault();
                        execCmd(\'underline\');
                        break;
                    case \'s\':
                        e.preventDefault();
                        saveContent();
                        break;
                }
            }
        });
        
        // Animation au survol des boutons de la barre d\'outils
        document.querySelectorAll(\'.toolbar button\').forEach(button => {
            button.addEventListener(\'mouseenter\', function() {
                this.style.transform = \'translateY(-2px)\';
            });
            
            button.addEventListener(\'mouseleave\', function() {
                this.style.transform = \'translateY(0)\';
            });
        });

        function updateToolbarState() {
            // Get all formatting buttons
            const buttons = {
                bold: document.querySelector(\'button[onclick="execCmd(\\\'bold\\\')"]\'),
                italic: document.querySelector(\'button[onclick="execCmd(\\\'italic\\\')"]\'),
                underline: document.querySelector(\'button[onclick="execCmd(\\\'underline\\\')"]\'),  
                strikeThrough: document.querySelector(\'button[onclick="execCmd(\\\'strikeThrough\\\')"]\')
            };
            
            // Check each formatting state and update button appearance
            for (let command in buttons) {
                const button = buttons[command];
                if (button) {
                    if (document.queryCommandState(command)) {
                        button.classList.add(\'active\');
                    } else {
                        button.classList.remove(\'active\');
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
        editor.addEventListener(\'contextmenu\', function(e) {
            const row = e.target.closest(\'tr\');
            if (row && row.closest(\'table\')) {
                e.preventDefault();
                contextMenuTable = row.closest(\'table\');
                contextMenuRow = row;
                showRowContextMenu(e.pageX, e.pageY, row);
            }
        });

        function showRowContextMenu(x, y, row) {
            // Remove existing context menu
            const existingMenu = document.getElementById(\'rowContextMenu\');
            if (existingMenu) {
                existingMenu.remove();
            }

            // Create context menu
            const menu = document.createElement(\'div\');
            menu.id = \'rowContextMenu\';
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

            const table = row.closest(\'table\');
            const isFirstRow = row === table.querySelector(\'tr\');
            
            const menuItems = [
                { text: \'🎨 Colorer cette ligne\', action: () => colorSingleRow(row) },
                { text: \'🗑️ Supprimer couleur\', action: () => removeSingleRowColor(row) }
            ];

            // Add header-specific options if this is the first row
            if (isFirstRow) {
                menuItems.unshift({ text: \'📋 Définir comme en-tête\', action: () => makeRowHeader(row) });
            }

            menuItems.push({ text: \'📊 Colorer tout le tableau\', action: () => colorWholeTable(contextMenuTable) });

            menuItems.forEach(item => {
                const menuItem = document.createElement(\'div\');
                menuItem.textContent = item.text;
                menuItem.style.cssText = `
                    padding: 8px 12px;
                    cursor: pointer;
                    border-bottom: 1px solid #eee;
                `;
                menuItem.addEventListener(\'mouseenter\', () => {
                    menuItem.style.backgroundColor = \'#f0f0f0\';
                });
                menuItem.addEventListener(\'mouseleave\', () => {
                    menuItem.style.backgroundColor = \'\';
                });
                menuItem.addEventListener(\'click\', () => {
                    item.action();
                    menu.remove();
                });
                menu.appendChild(menuItem);
            });

            document.body.appendChild(menu);

            // Remove menu when clicking elsewhere
            setTimeout(() => {
                document.addEventListener(\'click\', function removeMenu() {
                    menu.remove();
                    document.removeEventListener(\'click\', removeMenu);
                }, 0);
            }, 0);
        }

        function makeRowHeader(row) {
            const headerColor = prompt(\'Couleur de fond de l\\\'en-tête (hex, nom, rgb):\', \'#7476789c\');
            const textColor = prompt(\'Couleur du texte de l\\\'en-tête (hex, nom, rgb):\', \'#ffffff\');
            
            if (headerColor && textColor) {
                row.style.backgroundColor = headerColor;
                row.style.color = textColor;
                row.style.fontWeight = \'bold\';
                
                // Apply to all cells in the row
                const cells = row.querySelectorAll(\'td, th\');
                cells.forEach(cell => {
                    cell.style.backgroundColor = headerColor;
                    cell.style.color = textColor;
                    cell.style.fontWeight = \'bold\';
                });
                
                triggerAutoSave();
            }
        }

        function colorSingleRow(row) {
            const color = prompt(\'Entrez une couleur (nom, hex, rgb):\', \'#e3f2fd\');
            if (color) {
                row.style.backgroundColor = color;
                triggerAutoSave();
            }
        }

        function removeSingleRowColor(row) {
            row.style.backgroundColor = \'\';
            row.style.color = \'\';
            row.style.fontWeight = \'\';
            
            // Remove styling from all cells in the row
            const cells = row.querySelectorAll(\'td, th\');
            cells.forEach(cell => {
                cell.style.backgroundColor = \'\';
                cell.style.color = \'\';
                cell.style.fontWeight = \'\';
            });
            
            triggerAutoSave();
        }

        function colorWholeTable(table) {
            // Find the table index and open the color modal
            const tables = editor.querySelectorAll(\'table\');
            const tableIndex = Array.from(tables).indexOf(table);
            
            populateTableSelector();
            document.getElementById(\'tableSelector\').value = tableIndex;
            document.getElementById(\'tableColorModal\').style.display = \'block\';
        }

        function updateColorInputs() {
            const foreColorInput = document.querySelector(\'input[onchange="execCmd(\\\'foreColor\\\', this.value)"]\');
            const backColorInput = document.querySelector(\'input[onchange="execCmd(\\\'backColor\\\', this.value)"]\');
            const fore2ColorInput = document.getElementById(\'textColorPreview\');
            const selection = window.getSelection();

            // Get current fore color
            const currentForeColor = document.queryCommandValue(\'foreColor\');
            if (currentForeColor && foreColorInput) {
                // Convert RGB to hex if needed
                const hexForeColor = rgbToHex(currentForeColor);
                if (hexForeColor) {
                    foreColorInput.value = hexForeColor;
                    document.getElementById(\'textColorPreview\').style.backgroundColor = currentForeColor;
                }
            }
            
            // Get current background color
            const currentBackColor = document.queryCommandValue(\'backColor\');
            if (currentBackColor && backColorInput) {
                // Convert RGB to hex if needed
                const hexBackColor = rgbToHex(currentBackColor);
                if (hexBackColor) {
                    backColorInput.value = hexBackColor;
                    document.getElementById(\'bgColorPreview\').style.backgroundColor = currentBackColor;
                }
            }
        }

        function rgbToHex(color) {
            if (!color) return null;
            
            // If already hex, return as is
            if (color.startsWith(\'#\')) {
                return color;
            }
            
            // Handle rgb() and rgba() formats
            const rgbMatch = color.match(/rgb\\((\\d+),\\s*(\\d+),\\s*(\\d+)\\)/);
            const rgbaMatch = color.match(/rgba\\((\\d+),\\s*(\\d+),\\s*(\\d+),\\s*[\\d.]+\\)/);
            
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
                return hex.length === 1 ? \'0\' + hex : hex;
            };
            
            return `#${toHex(r)}${toHex(g)}${toHex(b)}`;
        }

        // Color palettes
        const commonColors = [
            \'#000000\', \'#ffffff\', \'#ff0000\', \'#00ff00\', \'#0000ff\', \'#ffff00\', \'#ff00ff\', \'#00ffff\',
            \'#800000\', \'#008000\', \'#000080\', \'#808000\', \'#800080\', \'#008080\', \'#c0c0c0\', \'#808080\'
        ];

        const extendedColors = [
            \'#ff4444\', \'#44ff44\', \'#4444ff\', \'#ffaa44\', \'#ff44aa\', \'#44aaff\', \'#aa44ff\', \'#44ffaa\',
            \'#ff8888\', \'#88ff88\', \'#8888ff\', \'#ffcc88\', \'#ff88cc\', \'#88ccff\', \'#cc88ff\', \'#88ffcc\',
            \'#ffcccc\', \'#ccffcc\', \'#ccccff\', \'#ffeecc\', \'#ffccee\', \'#cceeff\', \'#eeccff\', \'#ccffee\',
            \'#333333\', \'#666666\', \'#999999\', \'#bbbbbb\', \'#dddddd\', \'#f0f0f0\', \'#f8f8f8\', \'#fcfcfc\'
        ];

        // Recent colors storage
        let recentTextColors = JSON.parse(localStorage.getItem(\'recentTextColors\') || \'[]\');
        let recentBgColors = JSON.parse(localStorage.getItem(\'recentBgColors\') || \'[]\');

        // Initialize color pickers
        function initializeColorPickers() {
            // Initialize common colors for both pickers
            createColorGrid(\'commonColors\', commonColors, \'text\');
            createColorGrid(\'commonBgColors\', commonColors, \'bg\');
            
            // Initialize extended colors for both pickers
            createColorGrid(\'extendedColors\', extendedColors, \'text\');
            createColorGrid(\'extendedBgColors\', extendedColors, \'bg\');
            
            // Load recent colors
            loadRecentColors();
        }

        function createColorGrid(containerId, colors, type) {
            const container = document.getElementById(containerId);
            container.innerHTML = \'\';
            
            colors.forEach(color => {
                const swatch = document.createElement(\'div\');
                swatch.className = \'color-swatch\';
                swatch.style.backgroundColor = color;
                swatch.title = color;
                swatch.onclick = () => applyColor(type, color);
                container.appendChild(swatch);
            });
        }

        function toggleColorDropdown(pickerId) {
            // Close other dropdowns first
            document.querySelectorAll(\'.color-dropdown\').forEach(dropdown => {
                if (dropdown.id !== pickerId) {
                    dropdown.classList.remove(\'show\');
                }
            });
            
            const dropdown = document.getElementById(pickerId);
            dropdown.classList.toggle(\'show\');
        }

        function applyColor(type, color) {
            // Restore cursor position first
            restoreCursorPosition();
            
            // Ensure editor has focus
            const editor = document.getElementById(\'editor\');
            editor.focus();
            
            if (type === \'text\') {
                document.execCommand(\'foreColor\', false, color);
                document.getElementById(\'textColorPreview\').style.backgroundColor = color;
                addToRecentColors(\'text\', color);
            } else {
                document.execCommand(\'backColor\', false, color);
                document.getElementById(\'bgColorPreview\').style.backgroundColor = color;
                addToRecentColors(\'bg\', color);
            }
            
            // Close dropdown
            document.querySelectorAll(\'.color-dropdown\').forEach(dropdown => {
                dropdown.classList.remove(\'show\');
            });
            
            // Trigger auto-save
            triggerAutoSave();
        }

        function applyCustomColor(type, color) {
            if (type === \'text\') {
                document.getElementById(\'customTextHex\').value = color;
            } else {
                document.getElementById(\'customBgHex\').value = color;
            }
            applyColor(type, color);
        }

        function applyCustomHex(type, hex) {
            // Validate hex color
            if (!/^#[0-9A-F]{6}$/i.test(hex)) {
                alert(\'Format de couleur invalide. Utilisez le format #RRGGBB\');
                return;
            }
            
            if (type === \'text\') {
                document.getElementById(\'customTextColor\').value = hex;
            } else {
                document.getElementById(\'customBgColor\').value = hex;
            }
            applyColor(type, hex);
        }

        function addToRecentColors(type, color) {
            const recentColors = type === \'text\' ? recentTextColors : recentBgColors;
            
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
            if (type === \'text\') {
                recentTextColors = recentColors;
                localStorage.setItem(\'recentTextColors\', JSON.stringify(recentColors));
            } else {
                recentBgColors = recentColors;
                localStorage.setItem(\'recentBgColors\', JSON.stringify(recentColors));
            }
            
            loadRecentColors();
        }

        function loadRecentColors() {
            loadRecentColorsForType(\'text\', recentTextColors, \'recentTextColors\');
            loadRecentColorsForType(\'bg\', recentBgColors, \'recentBgColors\');
        }

        function loadRecentColorsForType(type, colors, containerId) {
            const container = document.getElementById(containerId);
            // Keep the clear button
            const clearButton = container.querySelector(\'.clear-recent\');
            container.innerHTML = \'\';
            container.appendChild(clearButton);
            
            colors.forEach(color => {
                const swatch = document.createElement(\'div\');
                swatch.className = \'recent-color\';
                swatch.style.backgroundColor = color;
                swatch.title = color;
                swatch.onclick = () => applyColor(type, color);
                container.appendChild(swatch);
            });
        }

        function clearRecentColors(type) {
            if (type === \'text\') {
                recentTextColors = [];
                localStorage.removeItem(\'recentTextColors\');
            } else {
                recentBgColors = [];
                localStorage.removeItem(\'recentBgColors\');
            }
            loadRecentColors();
        }

        // Delete file
        function deleteFile(delete_file) {
            post(\'Modules.php?modname=scheduling/Planification.php\', {delete_file: delete_file});
        }

        // Close dropdowns when clicking outside
        document.addEventListener(\'click\', function(event) {
            if (!event.target.closest(\'.color-picker-container\')) {
                const wasOpen = document.querySelector(\'.color-dropdown.show\');
                document.querySelectorAll(\'.color-dropdown\').forEach(dropdown => {
                    dropdown.classList.remove(\'show\');
                });
                
                // If a dropdown was open and we clicked outside, restore focus to editor
                if (wasOpen && savedRange) {
                    setTimeout(() => {
                        restoreCursorPosition();
                    }, 50);
                }
            }
        });
        function showUploading() {
            const uploadStatus = document.getElementById(\'upload-status\');
            if (uploadStatus) {
                uploadStatus.style.display = \'block\';
            }
        }

        document.addEventListener(\'DOMContentLoaded\', function() {
            const editor = document.getElementById(\'editor\');
            
            // Save cursor position on various editor interactions
            editor.addEventListener(\'mouseup\', saveCursorPosition);
            editor.addEventListener(\'keyup\', function(e) {
                saveCursorPosition();
            });
            editor.addEventListener(\'focus\', saveCursorPosition);
            
            // Initialize other functions
            initializeColorPickers();
        });

        // Initialize when page loads
        document.addEventListener(\'DOMContentLoaded\', initializeColorPickers);        
    </script>
</body>
</html>';
if(! $_REQUEST['_openSIS_PDF'])
    do_cado_courses_files();

}
function do_static_editor(){
global $data,$dynamic;

$dynamic=false;
    echo <<<HTML
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
            margin-bottom: 10px;
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
            background: #f50303ff; 
            border:1px solid #e60b0bff; 
            border-radius:4px; 
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
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
HTML;

    if (!isset($_REQUEST['_openSIS_PDF']) && User('PROFILE') == "teacher") {
        echo <<<HTML
        <div class="auto-save-status" id="autoSaveStatus">
            <span class="auto-save-indicator"></span>
            <span id="autoSaveText">Auto-sauvegarde activée</span>
        </div>
HTML;
    } else {
        echo <<<HTML
        <div class="auto-save-status hidden" id="autoSaveStatus">
            <span class="auto-save-indicator hidden"></span>
            <span class="auto-save-indicator hidden" id="autoSaveText"></span>
        </div>
HTML;
    }

    echo <<<HTML

    <!-- Week 1 -->
    <div class="week-section">
        <table>
            <tr class="header-row">
                <td colspan="4" class="semaine">
HTML;
    echo '<strong>' . htmlspecialchars($course ?? '') . ' semaine du <i>' . htmlspecialchars($week1_date_start ?? '') . '</i></strong>';
    echo <<<HTML
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
                <td><textarea class="editable" data-week="week1" data-field="lundi_notions">
HTML;
    echo htmlspecialchars($data['week1']['lundi_notions'] ?? '');
    echo <<<HTML
</textarea></td>
                <td><textarea class="editable" data-week="week1" data-field="lundi_devoirs">
HTML;
    echo htmlspecialchars($data['week1']['lundi_devoirs'] ?? '');
    echo <<<HTML
</textarea></td>
                <td><textarea class="editable" data-week="week1" data-field="lundi_materiel">
HTML;
    echo htmlspecialchars($data['week1']['lundi_materiel'] ?? '');
    echo <<<HTML
</textarea></td>
            </tr>
            
            <tr>
                <td class="day-header">Mardi</td>
                <td><textarea class="editable" data-week="week1" data-field="mardi_notions">
HTML;
    echo htmlspecialchars($data['week1']['mardi_notions'] ?? '');
    echo <<<HTML
</textarea></td>
                <td><textarea class="editable" data-week="week1" data-field="mardi_devoirs">
HTML;
    echo htmlspecialchars($data['week1']['mardi_devoirs'] ?? '');
    echo <<<HTML
</textarea></td>
                <td><textarea class="editable" data-week="week1" data-field="mardi_materiel">
HTML;
    echo htmlspecialchars($data['week1']['mardi_materiel'] ?? '');
    echo <<<HTML
</textarea></td>
            </tr>
            
            <tr>
                <td class="day-header">Mercredi</td>
                <td><textarea class="editable" data-week="week1" data-field="mercredi_notions">
HTML;
    echo htmlspecialchars($data['week1']['mercredi_notions'] ?? '');
    echo <<<HTML
</textarea></td>
                <td><textarea class="editable" data-week="week1" data-field="mercredi_devoirs">
HTML;
    echo htmlspecialchars($data['week1']['mercredi_devoirs'] ?? '');
    echo <<<HTML
</textarea></td>
                <td><textarea class="editable" data-week="week1" data-field="mercredi_materiel">
HTML;
    echo htmlspecialchars($data['week1']['mercredi_materiel'] ?? '');
    echo <<<HTML
</textarea></td>
            </tr>
            
            <tr>
                <td class="day-header">Jeudi</td>
                <td><textarea class="editable" data-week="week1" data-field="jeudi_notions">
HTML;
    echo htmlspecialchars($data['week1']['jeudi_notions'] ?? '');
    echo <<<HTML
</textarea></td>
                <td><textarea class="editable" data-week="week1" data-field="jeudi_devoirs">
HTML;
    echo htmlspecialchars($data['week1']['jeudi_devoirs'] ?? '');
    echo <<<HTML
</textarea></td>
                <td><textarea class="editable" data-week="week1" data-field="jeudi_materiel">
HTML;
    echo htmlspecialchars($data['week1']['jeudi_materiel'] ?? '');
    echo <<<HTML
</textarea></td>
            </tr>
            
            <tr>
                <td class="day-header">Vendredi</td>
                <td><textarea class="editable" data-week="week1" data-field="vendredi_notions">
HTML;
    echo htmlspecialchars($data['week1']['vendredi_notions'] ?? '');
    echo <<<HTML
</textarea></td>
                <td><textarea class="editable" data-week="week1" data-field="vendredi_devoirs">
HTML;
    echo htmlspecialchars($data['week1']['vendredi_devoirs'] ?? '');
    echo <<<HTML
</textarea></td>
                <td><textarea class="editable" data-week="week1" data-field="vendredi_materiel">
HTML;
    echo htmlspecialchars($data['week1']['vendredi_materiel'] ?? '');
    echo <<<HTML
</textarea></td>
            </tr>
        </table>
    </div>

    <!-- Week 2 (commented out in original) -->
    <!-- <div class="week-section">
        <table>
            <tr class="header-row">
                <td colspan="4" class="semaine">
                    <strong>
HTML;
    // Commented out week 2 content would go here
    echo <<<HTML
                    </strong>
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
                <td><textarea class="editable" data-week="week2" data-field="lundi_notions"></textarea></td>
                <td><textarea class="editable" data-week="week2" data-field="lundi_devoirs"></textarea></td>
                <td><textarea class="editable" data-week="week2" data-field="lundi_materiel"></textarea></td>
            </tr>
            
            <tr>
                <td class="day-header">Mardi</td>
                <td><textarea class="editable" data-week="week2" data-field="mardi_notions"></textarea></td>
                <td><textarea class="editable" data-week="week2" data-field="mardi_devoirs"></textarea></td>
                <td><textarea class="editable" data-week="week2" data-field="mardi_materiel"></textarea></td>
            </tr>
            
            <tr>
                <td class="day-header">Mercredi</td>
                <td><textarea class="editable" data-week="week2" data-field="mercredi_notions"></textarea></td>
                <td><textarea class="editable" data-week="week2" data-field="mercredi_devoirs"></textarea></td>
                <td><textarea class="editable" data-week="week2" data-field="mercredi_materiel"></textarea></td>
            </tr>
            
            <tr>
                <td class="day-header">Jeudi</td>
                <td><textarea class="editable" data-week="week2" data-field="jeudi_notions"></textarea></td>
                <td><textarea class="editable" data-week="week2" data-field="jeudi_devoirs"></textarea></td>
                <td><textarea class="editable" data-week="week2" data-field="jeudi_materiel"></textarea></td>
            </tr>
            
            <tr>
                <td class="day-header">Vendredi</td>
                <td><textarea class="editable" data-week="week2" data-field="vendredi_notions"></textarea></td>
                <td><textarea class="editable" data-week="week2" data-field="vendredi_devoirs"></textarea></td>
                <td><textarea class="editable" data-week="week2" data-field="vendredi_materiel"></textarea></td>
            </tr>
        </table>
    </div> -->

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
            saveContent(week, field, value);
        }
        
        function showSaveStatus() {
            const status = document.getElementById('saveStatus');
            if (status) {
                status.style.display = 'block';
                setTimeout(() => {
                    status.style.display = 'none';
                }, 2000);
            }
        }

        function showErrorStatus(message) {
            const status = document.getElementById('errorStatus');
            if (status) {
                status.textContent = 'Erreur: ' + message;
                status.style.display = 'block';
                setTimeout(() => {
                    status.style.display = 'none';
                }, 3000);
            }
        }

        // Sauvegarder le contenu
        function saveContent(week, field, content) {
            const formData = new FormData();

            updateAutoSaveStatus('saving', 'Sauvegarde manuelle...');

            formData.append('week', week);
            formData.append('field', field);
            formData.append('content', content);

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.ok) {
                    hasUnsavedChanges = false;
                    const now = new Date().toLocaleTimeString('fr-FR');
                    updateAutoSaveStatus('saved', 'Sauvegardé à ' + now);
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
            post('Modules.php?modname=scheduling/Planification.php', {delete_file: delete_file});
        }

        function updateAutoSaveStatus(status, message) {
            if (autoSaveStatus && autoSaveText) {
                autoSaveStatus.className = 'auto-save-status ' + status;
                autoSaveText.textContent = message;
            }
        }
        
        function post(path, params, method) {
            method = method || 'post';
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
        
        if (document.readyState === 'complete') {
            post('Modules.php?modname=scheduling/Planification.php', {auto_save: 'auto_save'});
        }
        
        function showUploading() {
            const uploadStatus = document.getElementById('upload-status');
            if (uploadStatus) {
                uploadStatus.style.display = 'block';
            }
        }
    </script>
</body>
</html>
HTML;
if(! $_REQUEST['_openSIS_PDF'])
    do_cado_courses_files();
if(! $_REQUEST['_openSIS_PDF']){
    echo '</div>';
}
}
?>
