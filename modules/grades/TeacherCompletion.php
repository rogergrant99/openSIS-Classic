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
                    $staff_RET[$i]  = '<font size="4"><b><center>';
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
                    $list_RET[$j][$i] = '<font size="2"><i> <u><center>';
                    $list_RET[$j][$i] .= $course['SHORT_NAME'];
                    $list_RET[$j][$i] .= '</i></u>';
                    $bad_weght=check_weight($course['COURSE_PERIOD_ID'],$staff_id['STAFF_ID'],$cur_mp,$course['COURSE_ID']);
                    $bad_config=check_config($course['COURSE_PERIOD_ID'],$staff_id['STAFF_ID'],$cur_mp,$course['COURSE_ID']);
                    if(round(GetGroupAverage($course['COURSE_PERIOD_ID'],$cur_mp,UserSyear(),$course['SHORT_NAME'])) > 0 && round(GetGroupAverage($course['COURSE_PERIOD_ID'],$cur_mp,UserSyear(),$course['SHORT_NAME'])) != 'NAN')
                        $bad_final = 0;
                    else 
                        $bad_final = 1;
                    if($bad_config)
                        $list_RET[$j][$i] .= '<b style="color:red;"></b><i class="fa fa-times fa-lg text-danger"></i><i><b style="color:red;">Config</i>';
                    if($bad_weght) 
                        $list_RET[$j][$i] .= '<b style="color:red;"></b><i class="fa fa-times fa-lg text-danger"></i><i><b style="color:red;">Pondé</i>';
                    if($bad_final)
                        $list_RET[$j][$i] .= '<b style="color:red;"></b><i class="fa fa-times fa-lg text-danger"></i><i><b style="color:red;">Final</i>';
                    if(! $bad_final && ! $bad_config && ! $bad_weght)
                        $list_RET[$j][$i] .= '<i class="fa fa-check fa-lg text-success"></i>';
                }
            }
        }
    }
}
ListOutput($list_RET, $staff_RET, _teacherWhoHasnTEnteredGrades, "");
echo '</div>';

function GetGroupAverage($course_period_id,$mp,$year,$title){

    $markingPeriod = DBGet(DBQuery('SELECT * FROM school_quarters WHERE SYEAR=\'' . $year . '\' AND SCHOOL_ID=\'' . UserSchool() . '\' AND SORT_ORDER=255 '));   
    if($markingPeriod[1][MARKING_PERIOD_ID] != $mp) 
    { 
        if(substr( $title, 0, 3 ) === "PRE") return 100;
        $total_group=0;
        $students=0;
        $sql='SELECT GRADE_PERCENT FROM student_report_card_grades WHERE COURSE_PERIOD_ID=\'' . $course_period_id . '\' AND MARKING_PERIOD_ID=\''.  $mp . '\' ';
        $grades_RET=DBGet(DBQuery($sql));
        if($grades_RET){ 
            foreach ($grades_RET as $key=> $val) {
                if($year==2022){
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
        if(substr( $title, 0, 3 ) === "PRE") return 100;
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

?>
