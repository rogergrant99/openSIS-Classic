<?php

#**************************************************************************
#  openSIS is a free student information system for public and non-public 
#  schools from Open Solutions for Education, Inc. web: www.os4ed.com
#
#  openSIS is  web-based, open source, and comes packed with features that 
#  include student demographic info, scheduling, grade book, attendance, 
#  report cards, eligibility, transcripts, parent portal, 
#  student portal and more.   
#
#  Visit the openSIS web site at http://www.opensis.com to learn more.
#  If you have question regarding this system or the license, please send 
#  an email to info@os4ed.com.
#
#  This program is released under the terms of the GNU General Public License as  
#  published by the Free Software Foundation, version 2 of the License. 
#  See license.txt.
#
#  This program is distributed in the hope that it will be useful,
#  but WITHOUT ANY WARRANTY; without even the implied warranty of
#  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#  GNU General Public License for more details.
#
#  You should have received a copy of the GNU General Public License
#  along with this program.  If not, see <http://www.gnu.org/licenses/>.
#
#***************************************************************************************
ini_set('memory_limit', '12000000M');
ini_set('max_execution_time', '50000');
include('../../RedirectModulesInc.php');
DrawBC(""._gradebook." > " . ProgramTitle());

echo '<div class="panel panel-default">';
$sem = GetParentMP('SEM', UserMP());
$fy = GetParentMP('FY', $sem);
$pros = GetChildrenMP('PRO', UserMP());
// if the UserMP has been changed, the REQUESTed MP may not work
if (!$_REQUEST['mp'] || strpos($str = "'" . UserMP() . "','" . $sem . "','" . $fy . "'," . $pros, "'" . ltrim($_REQUEST['mp'], 'E') . "'") === false)
    $_REQUEST['mp'] = UserMP();
$QI = DBQuery('SELECT PERIOD_ID,TITLE FROM school_periods WHERE SCHOOL_ID=\'' . UserSchool() . '\' AND SYEAR=\'' . UserSyear() . '\' ORDER BY SORT_ORDER ');
$period_RET = DBGet($QI);
$TI = DBQuery('SELECT DISTINCT STAFF_ID,CONCAT(LAST_NAME,\', \',FIRST_NAME) AS FULL_NAME,LAST_NAME,FIRST_NAME FROM staff  WHERE PROFILE_ID="2" ORDER BY LOWER(FULL_NAME) ');
$teacher_RET= DBGet($TI);
$mp_select = "<SELECT class=\"form-control\" name=mp onChange='this.form.submit();'>";
if ($pros != '')
    foreach (explode(',', str_replace("'", '', $pros)) as $pro)
        if (GetMP($pro, 'DOES_GRADES') == 'Y')
            $mp_select .= "<OPTION value=" . $pro . (($pro == $_REQUEST['mp']) ? ' SELECTED' : '') . ">" . GetMP($pro) . "</OPTION>";

$mp_select .= "<OPTION value=" . UserMP() . ((UserMP() == $_REQUEST['mp']) ? ' SELECTED' : '') . ">" . GetMP(UserMP()) . "</OPTION>";
if (GetMP($sem, 'DOES_GRADES') == 'Y')
    $mp_select .= "<OPTION value=$sem" . (($sem == $_REQUEST['mp']) ? ' SELECTED' : '') . ">" . GetMP($sem) . "</OPTION>";
if (GetMP($sem, 'DOES_EXAM') == 'Y')
    $mp_select .= "<OPTION value=E$sem" . (('E' . $sem == $_REQUEST['mp']) ? ' SELECTED' : '') . ">" . GetMP($sem) . " Exam</OPTION>";

if (GetMP($fy, 'DOES_GRADES') == 'Y')
    $mp_select .= "<OPTION value=" . $fy . (($fy == $_REQUEST['mp']) ? ' SELECTED' : '') . ">" . GetMP($fy) . "</OPTION>";
if (GetMP($fy, 'DOES_EXAM') == 'Y')
    $mp_select .= "<OPTION value=E" . $fy . (('E' . $fy == $_REQUEST['mp']) ? ' SELECTED' : '') . ">" . GetMP($fy) . " Exam</OPTION>";
$mp_select .= '</SELECT>';
if ($_REQUEST['mp'])
    $cur_mp = $_REQUEST['mp'];
else
    $cur_mp = UserMP();
echo "<FORM class=\"no-margin\" action=Modules.php?modname=" . strip_tags(trim($_REQUEST[modname])) . " method=POST>";
DrawHeader(_teacherCompletion, '<div class="form-inline"><div class="form-group"><label class="control-label ml-20 mr-20">-</label>' . $teacher_select.'</div></div>');
echo '</FORM>';

echo '<hr class="no-margin"/>';

$mp_type = DBGet(DBQuery('SELECT MP_TYPE FROM marking_periods WHERE marking_period_id=\'' . $cur_mp . '\' '));
if ($mp_type[1]['MP_TYPE'] == 'year')
    $mp_type = 'FY';
elseif ($mp_type[1]['MP_TYPE'] == 'semester')
    $mp_type = 'SEM';
elseif ($mp_type[1]['MP_TYPE'] == 'quarter')
    $mp_type = 'QTR';
else
    $mp_type = 'PRO';



$sql = 'SELECT DISTINCT s.STAFF_ID,CONCAT(s.LAST_NAME,\', \',s.FIRST_NAME) AS FULL_NAME,cp.TITLE,cp.COURSE_PERIOD_ID,cp.SHORT_NAME,cp.COURSE_ID AS COURSE_ID FROM staff s,school_periods sp,course_periods cp
			
WHERE cp.GRADE_SCALE_ID IS NOT NULL AND cp.TEACHER_ID=s.STAFF_ID 

AND cp.MARKING_PERIOD_ID IN (' . GetAllMP($mp_type, $cur_mp) . ') AND cp.SYEAR=\'' . UserSyear() . '\' AND cp.SCHOOL_ID=\'' . UserSchool() . '\' AND s.PROFILE=\'teacher\'
			' . (($_REQUEST['period']) ? ' AND cp.COURSE_PERIOD_ID=\'' . $_REQUEST[period] . '\'' : 'ORDER BY  LOWER(cp.SHORT_NAME)') . '
			
		';
$courses_RET = DBGet(DBQuery($sql));
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
                    $list_RET[$j][$i] = '<font size="4"><u><center><b>';
                    $list_RET[$j][$i] .= $course['SHORT_NAME'];
                    $list_RET[$j][$i] .= '</b></font><font size="2">';
                    $bad_weght=check_weight($course['COURSE_PERIOD_ID'],$staff_id['STAFF_ID'],$cur_mp,$course['COURSE_ID']);
                    $bad_config=check_config($course['COURSE_PERIOD_ID'],$staff_id['STAFF_ID'],$cur_mp,$course['COURSE_ID']);
                    $one_day = 60 * 60 * 24;
                    $one_week = 60 * 60 * 24 * 7;
                    $start_time_cur = strtotime(date('Y-m-d'));
                    while (date('N', $start_time_cur) != 1) {
                        $start_time_cur = $start_time_cur - $one_day;
                    }
                    $bad_planif_this= check_planif($course['COURSE_ID'],$start_time_cur);
                    $bad_planif_next= check_planif($course['COURSE_ID'],$start_time_cur+$one_week);
                    // $list_RET[$j][$i] .= '<br>';
                    if($bad_planif_this)
                        $list_RET[$j][$i] .= '<br><b style="color:red;"></b><i class="fa fa-times fa-lg text-danger"></i>Planif cette semaine';
                    else 
                       $list_RET[$j][$i] .= '<br><i class="fa fa-check fa-lg text-success"></i>Planif cette semaine';
                    if($bad_planif_next)
                        $list_RET[$j][$i] .= '<br><b style="color:red;"></b><i class="fa fa-times fa-lg text-danger"></i>Planif la semaine prochaine';
                    else 
                        $list_RET[$j][$i] .= '<br><i class="fa fa-check fa-lg text-success"></i>Planif la semaine prochaine';
                    if(round(GetGroupAverage($course['COURSE_PERIOD_ID'],$cur_mp,UserSyear(),$course['SHORT_NAME'])) > 0 && round(GetGroupAverage($course['COURSE_PERIOD_ID'],$cur_mp,UserSyear(),$course['SHORT_NAME'])) != 'NAN')
                        $bad_final = 0;
                    else 
                        $bad_final = 1;
                    if($bad_config)
                        $list_RET[$j][$i] .= '<br><b style="color:red;"></b><i class="fa fa-times fa-lg text-danger"></i><i>Config</i>';
                    else 
                       $list_RET[$j][$i] .= '<br><i class="fa fa-check fa-lg text-success"></i>Config';
                    if($bad_weght) 
                        $list_RET[$j][$i] .= '<br><b style="color:red;"></b><i class="fa fa-times fa-lg text-danger"></i><i>Pondération</i>';
                    else 
                       $list_RET[$j][$i] .= '<br><i class="fa fa-check fa-lg text-success"></i>Pondération';
                    if($bad_final)
                        $list_RET[$j][$i] .= '<br><b style="color:red;"></b><i class="fa fa-times fa-lg text-danger"></i><i>Final</i>';
                    else 
                       $list_RET[$j][$i] .= '<br><i class="fa fa-check fa-lg text-success"></i>Final';
                    // if(! $bad_final && ! $bad_config && ! $bad_weght)
                    //     $list_RET[$j][$i] .= '<i class="fa fa-check fa-lg text-success"></i>';
                    $list_RET[$j][$i] .=  '</font>';
                }
            }
        }
    }
}
$options['search']=false;
ListOutput($list_RET, $staff_RET, _teacherWhoHasnTEnteredGrades, "","","",$options);
echo '</div>';

function check_planif($course_id,$start_time){
    $RET = DBGet(DBQuery('select * from planification where start_date=\'' . date('Y-m-d',$start_time) . '\'  and course_id=\'' . $course_id . '\''));
    if(count($RET))
        return false;
    return true;
}


function GetGroupAverage($course_period_id,$mp,$year,$title){

    $markingPeriod = DBGet(DBQuery('SELECT * FROM school_quarters WHERE SYEAR=\'' . $year . '\' AND SCHOOL_ID=\'' . UserSchool() . '\' AND SORT_ORDER=255 '));   
    if($markingPeriod[1][MARKING_PERIOD_ID] != $mp) 
    { 
        //if(substr( $title, 0, 3 ) === "PRE") return 100;
        $total_group=0;
        $students=0;
        $sql='SELECT GRADE_PERCENT FROM student_report_card_grades WHERE COURSE_PERIOD_ID=\'' . $course_period_id . '\' AND MARKING_PERIOD_ID=\''.  $mp . '\' ';
        $grades_RET=DBGet(DBQuery($sql));
        if($grades_RET){ 
            foreach ($grades_RET as $key=> $val) {
                if($year==2022 || substr( $title, 0, 3 ) === "PRE"){
                    if($val['GRADE_PERCENT'] > 0 ){
                        $total_group+=$val['GRADE_PERCENT'];
                        $student++;
                    }
                }else
                    if($val['GRADE_PERCENT'] > 49 ){
                        $total_group+=$val['GRADE_PERCENT'];
                        $student++;
                    }
            }
        }
    }
    else{
        //if(substr( $title, 0, 3 ) === "PRE") return 100;
        $sql='SELECT GRADE_PERCENT FROM student_report_card_grades WHERE COURSE_PERIOD_ID=\'' . $course_period_id . '\' AND MARKING_PERIOD_ID=\''.  $mp . '\' ';
        $grades_RET=DBGet(DBQuery($sql));
        if(count($grades_RET))
            return 100;
        else
            return 0;
    }
    if($student)
        return $total_group/$student;
    else 
        return 0;
}


function check_weight($course_period_id,$staff_id,$mp,$course_id)
{
    $markingPeriod = DBGet(DBQuery('SELECT * FROM school_quarters WHERE SYEAR=\'' . UserSyear() . '\' AND SCHOOL_ID=\'' . UserSchool() . '\' AND SORT_ORDER=255 '));   
    if($markingPeriod[1][MARKING_PERIOD_ID] != $mp) 
    { 
        $assignment_type_list_sql = 'SELECT ASSIGNMENT_TYPE_ID, TITLE, FINAL_GRADE_PERCENT 
                FROM (
                ( SELECT gat.ASSIGNMENT_TYPE_ID, gat.TITLE, gat.FINAL_GRADE_PERCENT FROM gradebook_assignment_types gat WHERE gat.COURSE_PERIOD_ID=\'' . $course_period_id . '\' )
                UNION  
                (SELECT gat.ASSIGNMENT_TYPE_ID as ASSIGNMENT_TYPE_ID,concat(gat.TITLE,\' (\',TRIM(cp.title),\')\') as TITLE, gat.FINAL_GRADE_PERCENT FROM gradebook_assignment_types gat, gradebook_assignments ga, course_periods cp
                WHERE cp.course_period_id = gat.course_period_id AND gat.ASSIGNMENT_TYPE_ID = ga.ASSIGNMENT_TYPE_ID AND ga.COURSE_ID IS NOT NULL AND ga.COURSE_ID = \'' . $course_id . '\' AND ga.STAFF_ID = \'' . $staff_id . '\' ) 
                ) AS T
                GROUP BY ASSIGNMENT_TYPE_ID';
        $list_assignment_types = DBGet(DBQuery($assignment_type_list_sql));
        if (count($list_assignment_types) ==1 ) return 0;
        foreach ($list_assignment_types as $key => $type)
        {
            if($markingPeriod[1][MARKING_PERIOD_ID] == $mp) 
                break;
            if($type[TITLE] != $markingPeriod[1][TITLE])
            {
            $assignment_weight=DBGet(DBQuery('SELECT    ASSIGNMENT_WEIGHT AS ASSIGNMENT_WEIGHT FROM gradebook_assignments WHERE MARKING_PERIOD_ID=\''.  $mp . '\' AND assignment_type_id= ('.$type['ASSIGNMENT_TYPE_ID'].')'));
            foreach ($assignment_weight as $key => $weight) 
            {
                $total+=$weight['ASSIGNMENT_WEIGHT'];
            }
            if ($total != 100)
                return 1;
                //echo '<div class="alert alert-warning alert-styled-left">' . _coursePeriodIsConfiguredAsWeightedButNoWeightsAreAssignedToTheAssignmentTypes . ' '.$type['TITLE'] . '</div>';
            }
            $total=0;

        $total_assignment_type_weightage = 0;
        $total_assignment_type_weightage_arr = array();

        if (!empty($list_assignment_types)) {
            foreach ($list_assignment_types as $at_key => $at_val) {
                if ($at_val['FINAL_GRADE_PERCENT'] != '' && number_format($at_val['FINAL_GRADE_PERCENT'],2) != 0)
                    array_push($total_assignment_type_weightage_arr, $at_val['FINAL_GRADE_PERCENT']);
            }

            $total_assignment_type_weightage = array_sum($total_assignment_type_weightage_arr);

            if ($total_assignment_type_weightage != 1)
            {
                return 1;
                //echo '<div class="alert alert-warning alert-styled-left">' . _coursePeriodIsConfiguredAsWeightedButNoWeightsAreAssignedToTheAssignmentTypes . '</div>';
            }
        }else echo 'empty';
        }
    }
    return 0;
}

function check_config($course_period_id,$staff_id,$mp,$course_id)
{
    $config_RET = DBGet(DBQuery('SELECT TITLE,VALUE FROM program_user_config WHERE USER_ID=\'' . $staff_id . '\' AND PROGRAM="Gradebook" AND VALUE LIKE "%_' . $course_period_id . '" AND TITLE = "ROUNDING"'));   
    if($config_RET[1]['VALUE'] != "NORMAL_$course_period_id")
        return 1;
    $config_RET = DBGet(DBQuery('SELECT TITLE,VALUE FROM program_user_config WHERE USER_ID=\'' . $staff_id . '\' AND PROGRAM="Gradebook" AND VALUE LIKE "%_' . $course_period_id . '" AND TITLE = "WEIGHT"'));   
    if($config_RET[1]['VALUE'] != "Y_$course_period_id")
        return 1;
    $config_RET = DBGet(DBQuery('SELECT TITLE,VALUE FROM program_user_config WHERE USER_ID=\'' . $staff_id . '\' AND PROGRAM="Gradebook" AND VALUE LIKE "%_' . $course_period_id . '" AND TITLE LIKE "' . $course_period_id . '%"'));   
    if(count($config_RET) < 6 )
        return 1;
    $config_RET = DBGet(DBQuery('SELECT TITLE,VALUE FROM program_user_config WHERE USER_ID=\'' . $staff_id . '\' AND PROGRAM="Gradebook" AND VALUE LIKE "%_' . $course_period_id . '" AND TITLE LIKE "FY-%"'));   
    if(count($config_RET) != 5 )
        return 1;
    $config_RET = DBGet(DBQuery('SELECT TITLE,VALUE FROM program_user_config WHERE USER_ID=\'' . $staff_id . '\' AND PROGRAM="Gradebook" AND VALUE LIKE "%_' . $course_period_id . '" AND TITLE LIKE "Q-%"'));   
    if(count($config_RET) != 4 )
        return 1;
        
    return 0;
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
