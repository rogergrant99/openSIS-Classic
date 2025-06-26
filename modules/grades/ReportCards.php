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
include('../../RedirectModulesInc.php');
include 'modules/grades/ConfigInc.php';
include '_makeLetterGrade.fnc.php';
ini_set('max_execution_time', 5000);
ini_set('memory_limit', '12000M');

if (isset($_SESSION['student_id']) && $_SESSION['student_id'] != '') {
    $_REQUEST['search_modfunc'] = 'list';
}

if ($_REQUEST['modfunc'] == 'save') {
    $cur_session_RET = DBGet(DBQuery('SELECT YEAR(start_date) AS PRE,YEAR(end_date) AS POST FROM school_years WHERE SCHOOL_ID=\'' . UserSchool() . '\' AND SYEAR=\'' . UserSyear() . '\''));
    if ($cur_session_RET[1]['PRE'] == $cur_session_RET[1]['POST']) {
        $cur_session = $cur_session_RET[1]['PRE'];
    } else {
        $cur_session = $cur_session_RET[1]['PRE'] . '-' . $cur_session_RET[1]['POST'];
    }
    if (isset($_REQUEST['elements']['publish_report']) )
        $publish_parents=1;
    else 
        $publish_parents=='';
    if ((is_countable($_REQUEST['mp_arr']) && count($_REQUEST['mp_arr'])) && (is_countable($_REQUEST['st_arr']) && count($_REQUEST['st_arr']))) {
        //    if (count($_REQUEST['mp_arr']) && count($_REQUEST['unused'])) {
        $mp_list = '\'' . implode('\',\'', $_REQUEST['mp_arr']) . '\'';
        $last_mp = end($_REQUEST['mp_arr']);
        $st_list = '\'' . implode('\',\'', $_REQUEST['st_arr']) . '\'';
        //        $st_list = '\'' . implode('\',\'', $_REQUEST['unused']) . '\'';
        $extra['WHERE'] = ' AND s.STUDENT_ID IN (' . $st_list . ')';


        $extra['SELECT'] .= ',c.GRADE_LEVEL,rc_cp.COURSE_ID,rc_cp.SHORT_NAME,rc_cp.COURSE_PERIOD_ID,rc_cp.COURSE_WEIGHT,rpg.TITLE as GRADE_TITLE,sg1.GRADE_PERCENT,sg1.WEIGHTED_GP,sg1.UNWEIGHTED_GP ,sg1.CREDIT_ATTEMPTED , sg1.COMMENT as COMMENT_TITLE,sg1.STUDENT_ID,sg1.COURSE_PERIOD_ID,sg1.MARKING_PERIOD_ID,c.TITLE as COURSE_TITLE,c.SHORT_NAME as COURSE_NUMBER,rc_cp.TEACHER_ID AS TEACHER_NAME,rc_cp.TEACHER_ID AS TEACHER_ID,sp.SORT_ORDER';

        if (($_REQUEST['elements']['period_absences'] == 'Y' && !$_REQUEST['elements']['grade_type']) || ($_REQUEST['elements']['period_absences'] == 'Y' && $_REQUEST['elements']['grade_type'] && $_REQUEST['elements']['percents']))
            $extra['SELECT'] .= ',cpv.DOES_ATTENDANCE,
				(SELECT count(*) FROM attendance_period ap,attendance_codes ac
					WHERE ac.ID=ap.ATTENDANCE_CODE AND ac.STATE_CODE=\'A\' AND ap.COURSE_PERIOD_ID=sg1.COURSE_PERIOD_ID AND ap.STUDENT_ID=ssm.STUDENT_ID) AS YTD_ABSENCES,
				(SELECT count(*) FROM attendance_period ap,attendance_codes ac
					WHERE ac.ID=ap.ATTENDANCE_CODE AND ac.STATE_CODE=\'A\' AND ap.COURSE_PERIOD_ID=sg1.COURSE_PERIOD_ID AND sg1.MARKING_PERIOD_ID=ap.MARKING_PERIOD_ID AND ap.STUDENT_ID=ssm.STUDENT_ID) AS MP_ABSENCES';
        if (($_REQUEST['elements']['gpa'] == 'Y' && !$_REQUEST['elements']['grade_type']) || ($_REQUEST['elements']['gpa'] == 'Y' && $_REQUEST['elements']['grade_type'] && $_REQUEST['elements']['percents']))
            $extra['SELECT'] .= ",sg1.weighted_gp as GPA";
        if (($_REQUEST['elements']['comments'] == 'Y' && !$_REQUEST['elements']['grade_type']) || ($_REQUEST['elements']['comments'] == 'Y' && $_REQUEST['elements']['grade_type'] && $_REQUEST['elements']['percents']))
            $extra['SELECT'] .= ',s.gender AS GENDER,s.common_name AS NICKNAME';

        $extra['FROM'] .= ',student_report_card_grades sg1 LEFT OUTER JOIN report_card_grades rpg ON (rpg.ID=sg1.REPORT_CARD_GRADE_ID),
					course_periods rc_cp,course_period_var cpv,courses c,school_periods sp,schools sc ';


        $extra['WHERE'] .= ' AND sg1.MARKING_PERIOD_ID IN (' . $mp_list . ')
					AND rc_cp.COURSE_PERIOD_ID=sg1.COURSE_PERIOD_ID AND c.COURSE_ID = rc_cp.COURSE_ID AND sg1.STUDENT_ID=ssm.STUDENT_ID AND cpv.COURSE_PERIOD_ID=rc_cp.COURSE_PERIOD_ID AND sp.PERIOD_ID=cpv.PERIOD_ID
                                                                                           AND sc.ID=sg1.SCHOOL_ID';

        $extra['ORDER'] .= ',c.TITLE';
        $extra['functions']['TEACHER_NAME'] = '_makeTeacher';
        $extra['functions']['TEACHER_ID'] = '_makeTeacherID';
        $extra['group'] = array('STUDENT_ID', 'COURSE_PERIOD_ID', 'MARKING_PERIOD_ID');
        $RET = GetStuList($extra);
        //echo '<pre>'; print_r($RET); echo '</pre>';  
        if (count($RET)) {
            //start of report card print
            $QUART_RET=DBGet(DBQuery('SELECT * from school_quarters WHERE SCHOOL_ID=\'' . UserSchool() . '\' AND SYEAR=\'' . UserSyear() . '\' AND MARKING_PERIOD_ID=\'' . UserMP() . '\''));  
                //echo '<pre>'; print_r($RET); echo '</pre>'; 
                foreach ($RET as $student_id => $course_periods) {
                    $individual=array();
                    if($publish_parents) $publish_parents=$student_id;
                    $i=1;
                    foreach ($RET[$student_id] as $course_id => $courses) {
                        foreach ($courses as $marking_period_id => $temp) {
                            $SCHED_RET=DBGet(DBQuery('SELECT * from schedule WHERE SCHOOL_ID=\'' . UserSchool() . '\' AND student_id=\'' . $student_id . '\'  AND SYEAR=\'' . UserSyear(). '\' AND COURSE_PERIOD_ID=\'' . $temp[1]['COURSE_PERIOD_ID'] . '\'')); 
                            if(count($SCHED_RET) && $SCHED_RET[1]['DROPPED'] == 'Y')
                                continue;
                            $grade_id=$temp[1]['GRADE_ID'];
                            $individual[$i++]=$temp[1];
                        }
                }
                // echo '<pre>'; print_r($individual); echo '</pre>';  
                $handle = PDFStart();
                CadoHTMLpageSetup(_reportcard_title , $grade_id);
                CadoHTMLHeader($student_id, $grade_id,UserMP());
                foreach ($individual as $one_course) {
                    // Test to see if all teachers have cpmmpleted all requirements before issuing report card
                    if(CadoTeacherComlpetion($one_course['TEACHER_ID'],$one_course['COURSE_ID'],$one_course['COURSE_PERIOD_ID'],$one_course['SHORT_NAME'])){
                        echo '<script type="text/javascript"> document.body.innerHTML = \'\'; </script>';
                        echo '<h1><br><b>';
                        echo $one_course['TEACHER_NAME'];
                        echo '</h1></br></b>';
                        BackPrompt("Le professeur suivant n'as pas complèté les notes final ou pondération:");
                        exit;
                    }
                }
                CadoStudentGrades($individual,$student_id, $grade_id, UserMP());
                CadoStudentComments($student_id, $grade_id,$last_mp);
                $filename=$individual[1]['FIRST_NAME'] . ' ' . $individual[1]['LAST_NAME']  . ' - ' . $QUART_RET[1]['TITLE'] . ' - ' .  UserSyear() . '-' . (UserSyear()+1);
                $filename=html_entity_decode($filename);
                PDFStop($handle);

            }
        } else
            BackPrompt(_missingGradesOrNoStudentsWereFound);
    } else
        BackPrompt(_youMustChooseAtLeastOneStudentAndMarkingPeriod);
}

if (!$_REQUEST['modfunc']) {
    DrawBC("" . _gradebook . " > " . ProgramTitle());

    if ($_REQUEST['search_modfunc'] == 'list') {
        echo "<FORM action=ForExport.php?modname=" . strip_tags(trim($_REQUEST[modname])) . "&modfunc=save&include_inactive=" . strip_tags(trim($_REQUEST['include_inactive'])) . "&_openSIS_PDF=true&head_html=Student+Report+Card method=POST target=_blank>";
        $attendance_codes = DBGet(DBQuery("SELECT SHORT_NAME,ID FROM attendance_codes WHERE SYEAR='" . UserSyear() . "' AND SCHOOL_ID='" . UserSchool() . "' AND (DEFAULT_CODE!='Y' OR DEFAULT_CODE IS NULL) AND TABLE_NAME='0'"));
        $extra['extra_header_left'] .= '<div class="col-md-6 col-lg-4"><div class="form-group"><div class="checkbox checkbox-switch switch-success switch-xs"><label><INPUT type=checkbox name=elements[publish_report] value=Y><span></span>'._publish_report.'</label></div></div></div>';
        $mps_RET = DBGet(DBQuery("SELECT SEMESTER_ID,MARKING_PERIOD_ID,SHORT_NAME,TITLE FROM school_quarters WHERE SYEAR='" . UserSyear() . "' AND SCHOOL_ID='" . UserSchool() . "' ORDER BY SORT_ORDER"), array(), array('SEMESTER_ID'));
        if (!$mps_RET) {
            $mps_RET = DBGet(DBQuery("SELECT YEAR_ID,MARKING_PERIOD_ID,SHORT_NAME FROM school_semesters WHERE SYEAR='" . UserSyear() . "' AND SCHOOL_ID='" . UserSchool() . "' ORDER BY SORT_ORDER"), array(), array('MARKING_PERIOD_ID'));
        }
        if (!$mps_RET) {
            $mps_RET = DBGet(DBQuery("SELECT MARKING_PERIOD_ID,SHORT_NAME FROM school_years WHERE SYEAR='" . UserSyear() . "' AND SCHOOL_ID='" . UserSchool() . "' ORDER BY SORT_ORDER"), array(), array('MARKING_PERIOD_ID'));
        }
        $extra['extra_header_left'] .= '<h5 class="text-primary">' . _markingPeriods . '</h5>';
        $extra['extra_header_left'] .= '<div class="form-group">';
        foreach ($mps_RET as $sem => $quarters) {
            foreach ($quarters as $qtr) {
                $qtr1=$qtr['MARKING_PERIOD_ID'];
                $pro = GetChildrenMP('PRO', $qtr['MARKING_PERIOD_ID']);
                if ($pro) {
                    $pros = explode(',', str_replace("'", '', $pro));
                    foreach ($pros as $pro)
                        if (GetMP($pro, 'DOES_GRADES') == 'Y')
                            $extra['extra_header_left'] .= '<label class="checkbox-inline"><INPUT class="styled" type=checkbox name=mp_arr[] value=' . $pro . ' onclick="reportCardGpaChk();">' . GetMP($pro, 'SHORT_NAME') . '</label>';
                }
                if( GetMP(UserMP()) == $qtr[TITLE] ) 
                    $extra['extra_header_left'] .= '<label class="checkbox-inline"><INPUT class="styled" type=checkbox name=mp_arr[] value=' . $qtr['MARKING_PERIOD_ID'] . ' CHECKED onclick="reportCardGpaChk();">' . $qtr['TITLE'] . '</label>';
              
                if (GetMP($qtr1, 'DOES_EXAM') == 'Y')
                $extra['extra_header_left'] .= '<label class="checkbox-inline"><INPUT class="styled" type=checkbox name=mp_arr[] value=E' . $qtr1 . ' onclick="reportCardGpaChk();">' . GetMP($qtr1, 'SHORT_NAME') . ' Exam</label>';
                }
            if (GetMP($sem, 'DOES_EXAM') == 'Y')
                $extra['extra_header_left'] .= '<label class="checkbox-inline"><INPUT class="styled" type=checkbox name=mp_arr[] value=E' . $sem . ' onclick="reportCardGpaChk();">' . GetMP($sem, 'SHORT_NAME') . ' Exam</label>';
            if (GetMP($sem, 'DOES_GRADES') == 'Y' && $sem != $quarters[1]['MARKING_PERIOD_ID'])
                $extra['extra_header_left'] .= '<label class="checkbox-inline"><INPUT class="styled" type=checkbox name=mp_arr[] value=' . $sem . ' onclick="reportCardGpaChk();">' . GetMP($sem, 'SHORT_NAME') . '</label>';
        }
        $extra['extra_header_left'] .= '</div>';
        $extra['extra_header_left'] .= $extra['search'];
        $extra['search'] = '';
    }

    $extra['link'] = array('FULL_NAME' => false);
    $extra['SELECT'] = ",s.STUDENT_ID AS CHECKBOX";
    if (isset($_SESSION['student_id']) && $_SESSION['student_id'] != '') {
        $extra['WHERE'] .= ' AND s.STUDENT_ID=' . $_SESSION['student_id'];
    }
    $extra['functions'] = array('CHECKBOX' => '_makeChooseCheckbox');
    $extra['columns_before'] = array('CHECKBOX' => '</A><INPUT type=checkbox value=Y name=controller onclick="checkAllDtMod(this,\'st_arr\');"><A>');
    $extra['options']['search'] = false;
    $extra['new'] = true;
    Search('student_id', $extra, 'true');
    if ($_REQUEST['search_modfunc'] == 'list') {
        if ($_SESSION['count_stu'] != 0)
            echo '<div class="text-right p-b-20 p-r-20"><INPUT type=submit class="btn btn-primary" value=\'' . _createReportCardsForSelectedStudents . '\'></div>';
        echo "</FORM>";
    }
    }
    $modal_flag = 1;
    if ($_REQUEST['modname'] == 'grades/ReportCards.php' && $_REQUEST['modfunc'] == 'save')
    $modal_flag = 0;
    if ($modal_flag == 1) {
    echo '<div id="modal_default" class="modal fade">';
    echo '<div class="modal-dialog modal-lg">';
    echo '<div class="modal-content">';
    echo '<div class="modal-header">';
    echo '<button type="button" class="close" data-dismiss="modal">×</button>';
    echo '<h4 class="modal-title">' . _chooseCourse . '</h4>';
    echo '</div>';

    echo '<div class="modal-body">';
    echo '<div id="conf_div" class="text-center"></div>';
    echo '<div class="row" id="resp_table">';
    echo '<div class="col-md-4">';
    $sql = "SELECT SUBJECT_ID,TITLE FROM course_subjects WHERE SCHOOL_ID='" . UserSchool() . "' AND SYEAR='" . UserSyear() . "' ORDER BY TITLE";
    $QI = DBQuery($sql);
    $subjects_RET = DBGet($QI);

    echo '<h6>' . count($subjects_RET) . ((count($subjects_RET) == 1) ? ' ' . _subjectWas : ' ' . _subjectsWere) . ' ' . _found . '.</h6>';
    if (count($subjects_RET) > 0) {
        echo '<table class="table table-bordered"><thead><tr class="alpha-grey"><th>' . _subject . '</th></tr></thead><tbody>';
        foreach ($subjects_RET as $val) {
            echo '<tr><td><a href=javascript:void(0); onclick="chooseCpModalSearch(' . $val['SUBJECT_ID'] . ',\'courses\')">' . $val['TITLE'] . '</a></td></tr>';
        }
        echo '</tbody></table>';
    }
    echo '</div>';
    echo '<div class="col-md-4" id="course_modal"></div>';
    echo '<div class="col-md-4" id="cp_modal"></div>';
    echo '</div>'; //.row
    echo '</div>'; //.modal-body
    echo '</div>'; //.modal-content
    echo '</div>'; //.modal-dialog
    echo '</div>'; //.modal
}

//#####################################################//
//### CADO CUSTOM REPORT CARD
//#####################################################//

function CadoStudentGrades($courses,$student_id,$grade_id,$mp) {
    global $THIS_RET,$student_points,$total_points,$percent_weights;

    $report_year=$year=UserSyear();
    $cycle_count=1;
    if(strpos($grade_id,"Primaire") || strpos($grade_id,"Secondaire 1") || strpos($grade_id,"Secondaire 2")){
         $year--;
         $cycle_count=2;
    }
    $premiere_comm = DBGet(DBQuery('SELECT TITLE,MARKING_PERIOD_ID,SORT_ORDER  FROM school_quarters WHERE SYEAR=\'' . UserSyear() . '\' AND SCHOOL_ID=\'' . UserSchool() . '\' AND SORT_ORDER=255 '));
    if($premiere_comm[1]['MARKING_PERIOD_ID'] == $mp){
        $report_year=$year=UserSyear();
    }
    $year_loop=$year;
    while($cycle_count--){
        // echo 'YEAR CYCLE ----------------';
        // echo $year_loop;
        // echo '<br>';
        // echo $year;
        // echo '<br>';
        if($premiere_comm[1]['MARKING_PERIOD_ID'] == $mp)
            $quarters_RET = DBGet(DBQuery('SELECT TITLE,MARKING_PERIOD_ID,SORT_ORDER from school_quarters WHERE SCHOOL_ID=\'' . UserSchool() . '\' AND SYEAR=\'' . $year . '\' AND TITLE LIKE \'1ère commu%\' ORDER BY sort_order')); 
        else
            $quarters_RET = DBGet(DBQuery('SELECT TITLE,MARKING_PERIOD_ID,SORT_ORDER from school_quarters WHERE SCHOOL_ID=\'' . UserSchool() . '\' AND SYEAR=\'' . $year . '\' AND TITLE LIKE \'Étape%\' ORDER BY sort_order')); 
        $school_RET=DBGet(DBQuery('SELECT MARKING_PERIOD_ID from school_years WHERE SCHOOL_ID=\'' . UserSchool() . '\' AND SYEAR=\'' . $year . '\'')); 
        $year_mp=$school_RET[1]['MARKING_PERIOD_ID'];
        $quart_loop=1;
        foreach ($quarters_RET as $quart_count => $quart) {
            // echo 'QUART CYCLE ----------------';
            // echo $quart_count;
            // echo '<br>';
            // echo '<br>';
            // echo $quart['TITLE'];
            // echo '<br>';
            $marking_period_id= $quart['MARKING_PERIOD_ID'];
            // echo '  Marking Period=';
            // echo $marking_period_id;
            if($marking_period_id > UserMP()) break;
            foreach ($courses as $course_count => $course) {
                // echo ' COURSE ----------------';
                // echo $course_count;
                // echo '<br>';
                // echo $course['COURSE_TITLE'];
                // echo '<br>';
                // ------------------------------------------------------------------------------------------
                $course_id=$course['COURSE_ID'];
                $teacher_id=$course['TEACHER_ID'];
                $course_period_id=$course['COURSE_PERIOD_ID'];
                if($year!=$report_year){
                    $grade_level=$course['GRADE_LEVEL']-1;
                    $course_temp = DBGet(DBQuery('SELECT * FROM course_details WHERE course_title= "'.$course['COURSE_TITLE'] .'" and grade_level='. $grade_level .'  and SYEAR='. $year .''));
                    // echo '<pre>'; print_r($course_temp); echo '</pre>';
                    $data['STUDENT_ABSCENCES_QUARTER']['COURSE'][$course_count]['QUART'][$quart_loop]['YEAR'][$year_loop]['ABSCENCES']=0;
                    if(!count($course_temp)) continue;
                    $course_period_id=$course_temp[1]['COURSE_PERIOD_ID'];
                    $course_id=$course_temp[1]['COURSE_ID'];
                    $teacher_id=$course_temp[1]['TEACHER_ID'];
                    $code=DBGet(DBQuery('SELECT ID from attendance_codes WHERE SYEAR=\'' . $year . '\' AND STATE_CODE=\'A\''));
                    $ALL_QUART=DBGet(DBQuery('SELECT MARKING_PERIOD_ID,SORT_ORDER from school_quarters WHERE SCHOOL_ID=\'' . UserSchool() . '\' AND SYEAR=\'' . $year . '\' AND TITLE LIKE \'Étape%\' ORDER BY sort_order'));
                    $count=0;
                    $ATT_RET=DBGet(DBQuery('SELECT SCHOOL_DATE,PERIOD_ID,COURSE_PERIOD_ID,MARKING_PERIOD_ID,ATTENDANCE_CODE,student_id from attendance_period WHERE STUDENT_ID=\'' .  $student_id . '\'  AND ATTENDANCE_CODE=\'' .  $code[1]['ID'] . '\'  AND COURSE_PERIOD_ID =\'' . $course_period_id . '\' AND MARKING_PERIOD_ID =\'' . $marking_period_id . '\''));
                    // echo '<pre>'; print_r($ATT_RET); echo '</pre>';
                    foreach ($ATT_RET as $abs) $count+=1 - $abs['STATE_VALUE'];
                    if($count)
                        $data['STUDENT_ABSCENCES_QUARTER']['COURSE'][$course_count]['QUART'][$quart_loop]['YEAR'][$year_loop]['ABSCENCES']=($count !=0 ? $count . '' : '0');
                    else
                        $data['STUDENT_ABSCENCES_QUARTER']['COURSE'][$course_count]['QUART'][$quart_loop]['YEAR'][$year_loop]['ABSCENCES']=0;
                }else{
                    $code=DBGet(DBQuery('SELECT ID from attendance_codes WHERE SYEAR=\'' . $year . '\' AND STATE_CODE=\'A\''));
                    $ALL_QUART=DBGet(DBQuery('SELECT MARKING_PERIOD_ID,SORT_ORDER from school_quarters WHERE SCHOOL_ID=\'' . UserSchool() . '\' AND SYEAR=\'' . $year . '\' AND TITLE LIKE \'Étape%\' ORDER BY sort_order'));
                    $count=0;
                    $ATT_RET=DBGet(DBQuery('SELECT SCHOOL_DATE,PERIOD_ID,COURSE_PERIOD_ID,MARKING_PERIOD_ID,ATTENDANCE_CODE,student_id from attendance_period WHERE STUDENT_ID=\'' .  $student_id . '\'  AND ATTENDANCE_CODE=\'' .  $code[1]['ID'] . '\'  AND COURSE_PERIOD_ID =\'' . $course_period_id . '\' AND MARKING_PERIOD_ID =\'' . $marking_period_id . '\''));
                    // echo '<pre>'; print_r($ATT_RET); echo '</pre>';
                    foreach ($ATT_RET as $abs) $count+=1 - $abs['STATE_VALUE'];
                    if($count)
                        $data['STUDENT_ABSCENCES_QUARTER']['COURSE'][$course_count]['QUART'][$quart_loop]['YEAR'][$year_loop]['ABSCENCES']=($count !=0 ? $count . '' : '0');
                    else
                        $data['STUDENT_ABSCENCES_QUARTER']['COURSE'][$course_count]['QUART'][$quart_loop]['YEAR'][$year_loop]['ABSCENCES']=0;
    
                }
                $quart_total=GetGroupAverage($course_id, $course_period_id, $marking_period_id,$year);
                $data['RESULTS']['COURSE'][$course_count]['QUART'][$quart_loop]['YEAR'][$year_loop]['GROUP_AVG']=$quart_total;
                $assignment_type_ids = DBGet(DBQuery('SELECT group_concat(distinct(assignment_type_id)) AS assignment_type_ids FROM gradebook_assignments WHERE (COURSE_PERIOD_ID=\'' . $course_period_id . '\' OR COURSE_ID=\'' . $course_id . '\') AND (MARKING_PERIOD_ID=\'' . $marking_period_id . '\') ORDER by TITLE'));
                // echo '<pre>'; print_r($assignment_type_ids); echo '</pre>';
                if(!$assignment_type_ids[1]['ASSIGNMENT_TYPE_IDS']) continue;
                $assignment_type_weight = DBGet(DBQuery('SELECT SUM(FINAL_GRADE_PERCENT) AS FINAL_GRADE_PERCENT FROM gradebook_assignment_types WHERE assignment_type_id IN ('.$assignment_type_ids[1]['ASSIGNMENT_TYPE_IDS'].')'));
                $assignment_type_weight = $assignment_type_weight[1]['FINAL_GRADE_PERCENT'];
                if(!$assignment_type_weight) $assignment_type_weight=100;
                if($assignment_type_ids[1]['ASSIGNMENT_TYPE_IDS'])
                        $assignment_weight = DBGet(DBQuery('SELECT DUE_DATE  , ASSIGNMENT_WEIGHT AS ASSIGNMENT_WEIGHT FROM gradebook_assignments WHERE assignment_type_id IN ('.$assignment_type_ids[1]['ASSIGNMENT_TYPE_IDS'].')'));
                // echo '<pre>'; print_r($assignment_weight); echo '</pre>';
                // echo '<pre>'; print_r($assignment_type_ids); echo '</pre>';
                $assignment_weight = $assignment_weight[1]['ASSIGNMENT_WEIGHT'];
                $sql = 'SELECT '.$course_period_id.' as COURSE_PERIOD_ID,a.TITLE,t.TITLE AS ASSIGN_TYP,a.ASSIGNED_DATE,a.DUE_DATE, t.ASSIGNMENT_TYPE_ID, (t.FINAL_GRADE_PERCENT / '.$assignment_type_weight.') as FINAL_GRADE_PERCENT,t.FINAL_GRADE_PERCENT as ASSIGN_TYP_WG,t.FINAL_GRADE_PERCENT AS WEIGHT_GRADE , a.ASSIGNMENT_WEIGHT as ASSIGN_WEIGHT ,g.POINTS,a.POINTS AS TOTAL_POINTS,g.COMMENT, g.POINTS AS POINTS2, g.POINTS AS LETTER_GRADE,g.POINTS AS LETTERWTD_GRADE,'.$course['TEACHER_ID'].' AS CP_TEACHER_ID,CASE WHEN (a.ASSIGNED_DATE IS NULL OR CURRENT_DATE>=a.ASSIGNED_DATE) AND (a.DUE_DATE IS NULL OR CURRENT_DATE>=a.DUE_DATE) THEN \'Y\' ELSE NULL END AS DUE FROM gradebook_assignment_types t,gradebook_assignments a 
                LEFT OUTER JOIN gradebook_grades g ON (a.ASSIGNMENT_ID=g.ASSIGNMENT_ID AND g.STUDENT_ID=\''.$student_id.'\' AND g.COURSE_PERIOD_ID=\''.$course_period_id.'\') 
                    WHERE   a.ASSIGNMENT_TYPE_ID=t.ASSIGNMENT_TYPE_ID AND (a.COURSE_PERIOD_ID=\''.$course_period_id.'\' OR a.COURSE_ID=\''.$course_id.'\' ) AND t.COURSE_ID=\''.$course_id.'\' AND a.MARKING_PERIOD_ID=\''.$marking_period_id.'\'';
                $sql.= ' AND (a.POINTS!=\'0\' OR g.POINTS IS NOT NULL AND g.POINTS!=\'-1\')';
                if($premiere_comm[1]['MARKING_PERIOD_ID'] == $mp)
                    $sql .=' ORDER BY a.TITLE';
                else
                    $sql .=' ORDER BY t.TITLE';
                $grades_RET = DBGet(DBQuery($sql),array('ASSIGNED_DATE'=>'_removeSpaces','ASSIGN_TYP_WG'=>'_makeAssnWG','ASSIGN_WEIGHT'=>'_makeAssgnmtWtg','DUE_DATE'=>'_removeSpaces','TITLE'=>'_removeSpaces','POINTS'=>'_makeExtra','LETTER_GRADE'=>'_makeExtra','WEIGHT_GRADE'=>'_makeWtg'));
                // echo '<pre>'; print_r($grades_RET); echo '</pre>';
                $sum_points = $sum_percent = 0;
                if (is_countable($percent_weights) && count($percent_weights)) {
                    foreach ($percent_weights as $assignment_type_id => $percent) {
                        $total_stpoints += $student_points[$assignment_type_id];
                        $total_asgnpoints += $total_points[$assignment_type_id];
                    }
                }
                $final_RET=DBGet(DBQuery('SELECT VALUE FROM program_user_config WHERE USER_ID=\'' . $teacher_id . '\' AND PROGRAM=\'Gradebook\' AND TITLE LIKE \'FY-E' . $year_mp . '\' AND VALUE LIKE \'%_' . $course_period_id . '\''));
                if(trim(substr($final_RET[1]['VALUE'], 0, 2),"_")>0)
                    $exam_weight=trim(substr($final_RET[1]['VALUE'], 0, 2),"_");
                $tot_weight_grade = 0;
                $tot_id_grade=array();
                $assign_typ_wg = array();
                $tot_weight_grade = 0;
                $tot_weight=0;
                $tot_id_grade=array();
                $tot_id_weight=array();
                $total_weight=array();
                $assign_id_weigth=array();
                $assign_ids=array();                
                if (count($grades_RET)) {
                    foreach ($grades_RET as $key => $val) {
                        // echo '<pre>'; print_r($val); echo '</pre>';
                        if ($val['LETTERWTD_GRADE'] != -1.00 && $val['LETTERWTD_GRADE'] != '') {
                            $wper = explode('%', $val['LETTER_GRADE']);
                            if ($tot_weighted_percent[$val['ASSIGNMENT_TYPE_ID']] != '')
                                $tot_weighted_percent[$val['ASSIGNMENT_TYPE_ID']] = $tot_weighted_percent[$val['ASSIGNMENT_TYPE_ID']] + $wper[0];
                            else
                                $tot_weighted_percent[$val['ASSIGNMENT_TYPE_ID']] = $wper[0];
                            if ($assignment_type_count[$val['ASSIGNMENT_TYPE_ID']] != '')
                                $assignment_type_count[$val['ASSIGNMENT_TYPE_ID']] = $assignment_type_count[$val['ASSIGNMENT_TYPE_ID']] + 1;
                            else
                                $assignment_type_count[$val['ASSIGNMENT_TYPE_ID']] = 1;
                            if ($val['ASSIGN_TYP_WG'] != '')
                                $assign_typ_wg[$val['ASSIGNMENT_TYPE_ID']] = substr($val['ASSIGN_TYP_WG'], 0, -2);
                        }
                        if($val['ASSIGN_TYP_WG'] > 0 && $val['ASSIGN_WEIGHT'] > 0){
                            if($val['WEIGHT_GRADE'] != 'N/A')
                                $total_id_weight[$val['ASSIGNMENT_TYPE_ID']]+= $val['ASSIGN_WEIGHT'] * $val['ASSIGN_TYP_WG'] / 100;
                            $tot_id_grade[$val['ASSIGNMENT_TYPE_ID']]+= $val['POINTS2'] / $val['TOTAL_POINTS'] * ((($val['ASSIGN_WEIGHT'] * $val['ASSIGN_TYP_WG'])) / 100);
                            $assign_ids[$val['ASSIGNMENT_TYPE_ID']] = $val['ASSIGNMENT_TYPE_ID'];
                            $assign_id_weigth[$val['ASSIGNMENT_TYPE_ID']]= $val['ASSIGN_TYP_WG'];
                        }
                    }
                }
                // echo '<pre>'; print_r($tot_id_grade); echo '</pre>';
                foreach ($assign_ids as $key => $val) {
                    $tot_id_grade[$key] = $tot_id_grade[$key]  * $assign_id_weigth[$key] ;
                    if($total_id_weight[$key]){
                            $tot_weight_grade+= ($tot_id_grade[$key]/ 100) / $total_id_weight[$key] ;
                    }
                }
                $assignment=1;
                $last_id=0;
                foreach ($grades_RET as $key => $val) {
                    // echo '<pre>'; print_r($val); echo '<\pre>';
                    if($premiere_comm[1]['MARKING_PERIOD_ID'] == $mp)
                        $data['RESULTS']['COURSE'][$course_count]['QUART'][$quart_loop]['ASSIGNMENT'][$assignment]['YEAR'][$year_loop]['RAW']=$val['POINTS2'];
                    if($val['ASSIGNMENT_TYPE_ID'] == $assign_ids[$val['ASSIGNMENT_TYPE_ID']]) {
                        if($total_id_weight[$val['ASSIGNMENT_TYPE_ID']])
                            $grades_RET[$key]['WEIGHT_GRADE']= ($tot_id_grade[$val['ASSIGNMENT_TYPE_ID']]*100) / $total_id_weight[$val['ASSIGNMENT_TYPE_ID']] / $grades_RET[$key]['ASSIGN_TYP_WG'];
                    }
                    if($val['ASSIGNMENT_TYPE_ID'] != $last_id || $premiere_comm[1]['MARKING_PERIOD_ID'] == $mp){
                        $last_id=$val['ASSIGNMENT_TYPE_ID'];
                        if($premiere_comm[1]['MARKING_PERIOD_ID'] == $mp)
                            $data['RESULTS']['COURSE'][$course_count]['ASSIGNMENT'][$assignment]['YEAR'][$year_loop]['COMPETENCE'] = $grades_RET[$key]['TITLE'];
                        else    
                            $data['RESULTS']['COURSE'][$course_count]['ASSIGNMENT'][$assignment]['YEAR'][$year_loop]['COMPETENCE'] = $grades_RET[$key]['ASSIGN_TYP'];
                        $data['RESULTS']['COURSE'][$course_count]['QUART'][$quart_loop]['ASSIGNMENT'][$assignment]['YEAR'][$year_loop]['GRADE']= $grades_RET[$key]['WEIGHT_GRADE'];
                        $data['RESULTS']['COURSE'][$course_count]['QUART'][$quart_loop]['ASSIGNMENT'][$assignment]['YEAR'][$year_loop]['WEIGTH']=$grades_RET[$key]['ASSIGN_TYP_WG']/100;
                        $assignment++;
                    } 
                    if(str_contains($grades_RET[$key]['TITLE'], 'Examen') && str_contains($grades_RET[$key]['TITLE'], 'Final')){
                        $data['RESULTS']['COURSE'][$course_count]['ASSIGNMENT'][$assignment-1]['YEAR'][$year_loop]['FINALEXAM']=$grades_RET[$key]['POINTS2'];
                        $data['RESULTS']['COURSE'][$course_count]['ASSIGNMENT'][$assignment-1]['YEAR'][$year_loop]['FINALEXAMWEIGTH']=$exam_weight;
                    }
                }
                $data['RESULTS']['COURSE'][$course_count]['QUART'][$quart_loop]['YEAR'][$year_loop]['FINAL']=_makeLetterGrade($tot_weight_grade,$course_period_id,$course['TEACHER_ID'],"%");
                $sql='SELECT GRADE_PERCENT , COMMENT FROM student_report_card_grades WHERE COURSE_PERIOD_ID=\'' . $course_period_id . '\' AND MARKING_PERIOD_ID=\''.  $quart['MARKING_PERIOD_ID'] . '\' AND STUDENT_ID=\''.$student_id . '\'';
                $final_grade=DBGet(DBQuery($sql));
                if($data['RESULTS']['COURSE'][$course_count]['QUART'][$quart_loop]['YEAR'][$year_loop]['FINAL']){
                    $final_admim_grade=$final_grade[1]['GRADE_PERCENT'];
                    $diff = $final_admim_grade - $data['RESULTS']['COURSE'][$course_count]['QUART'][$quart_loop]['YEAR'][$year_loop]['FINAL'];
                    if($diff && $diff < 15 && $final_admim_grade){
                        $assignment=1;
                        $percent=$final_admim_grade/round($data['RESULTS']['COURSE'][$course_count]['QUART'][$quart_loop]['YEAR'][$year_loop]['FINAL']);
                        foreach ($assign_ids as $key => $val) {
                            $data['RESULTS']['COURSE'][$course_count]['QUART'][$quart_loop]['ASSIGNMENT'][$assignment]['YEAR'][$year_loop]['GRADE']=$data['RESULTS']['COURSE'][$course_count]['QUART'][$quart_loop]['ASSIGNMENT'][$assignment]['YEAR'][$year_loop]['GRADE']*$percent;
                            if($data['RESULTS']['COURSE'][$course_count]['QUART'][$quart_loop]['ASSIGNMENT'][$assignment]['YEAR'][$year_loop]['GRADE'] > 100)
                                $data['RESULTS']['COURSE'][$course_count]['QUART'][$quart_loop]['ASSIGNMENT'][$assignment]['YEAR'][$year_loop]['GRADE']=100;
                            $assignment++;
                        }
                        $data['RESULTS']['COURSE'][$course_count]['QUART'][$quart_loop]['YEAR'][$year_loop]['DIFF']=$diff;

                    }
                    $data['RESULTS']['COURSE'][$course_count]['QUART'][$quart_loop]['YEAR'][$year_loop]['FINAL']=$final_admim_grade;
                }
                $student_points = $total_points = $percent_weights = array();
                $total_asgnpoints=0;
                $total_stpoints=0;
                $tot_weighted_percent=array();
                $assignment_type_count=array();
                $assign_typ_wg = array();
                $tot_weight=0;
                $tot_id_weight=array();
                $total_weight=array();
                $total_id_weight=array();
                $assign_id_weigth=array();
                $assign_ids=array();
                $grades_RET=array();
                $last_id=0;
                $SCHED_RET=DBGet(DBQuery('SELECT * from schedule WHERE SCHOOL_ID=\'' . UserSchool() . '\' AND student_id=\'' . $student_id . '\'  AND SYEAR=\'' . UserSyear(). '\' AND COURSE_PERIOD_ID=\'' . $course_period_id . '\'')); 
                if(count($SCHED_RET) && $SCHED_RET[1]['DROPPED'] == 'Y')
                    $data['COURSES'][$course_count]['DROPPED']=true;
                $GRADE_LEVEL_RET=DBGet(DBQuery('SELECT title from school_gradelevels WHERE SCHOOL_ID=\'' . UserSchool() . '\' AND id=\'' . $course['GRADE_LEVEL'] . '\'')); 
                if(count($GRADE_LEVEL_RET))
                    $data['COURSES'][$course_count]['GRADE_TITLE']=$GRADE_LEVEL_RET[1]["TITLE"];
                $data['COURSES'][$course_count]['GRADE_LEVEL']=$course['GRADE_LEVEL'];
                $data['COURSES'][$course_count]['COURSE_TITLE']=$course['COURSE_TITLE'];
                $data['COURSES'][$course_count]['COURSE_NUMBER']=$course['COURSE_NUMBER'];
                $data['COURSES'][$course_count]['TEACHER_NAME']=$course['TEACHER_NAME'];
                $data['COURSES'][$course_count]['COMMENT']=$final_grade[1]['COMMENT'];
                $data['COURSES'][$course_count]['ASIGN_COUNT']=$assignment-1;
            // End course
            }
            $MAXDAYS_RET=DBGet(DBQuery('SELECT DAYS FROM school_quarters WHERE MARKING_PERIOD_ID =\'' . $marking_period_id . '\''));
            $data['ABSCENCES_QUARTER']['QUART'][$quart_loop]['YEAR'][$year_loop]['MAXDAYS_QUARTER']=$MAXDAYS_RET[1]['DAYS'];
            if($teacher_id){
                $config_RET = DBGet(DBQuery('SELECT VALUE FROM program_user_config WHERE USER_ID=' . $teacher_id . ' AND PROGRAM=\'Gradebook\' AND TITLE=\'FY-'.$marking_period_id.'\' ORDER BY last_updated DESC LIMIT 1'));
                $data['RESULTS']['QUART'][$quart_loop]['YEAR'][$year_loop]['FINAL_WEIGHT']= substr($config_RET[1]['VALUE'], 0, strpos($config_RET[1]['VALUE'], "_"));
            }
            $quart_loop++;
        // End cycle
    }
        $year_loop++;
        $year++;
    // End year
    }
    // echo '<pre>'; print_r($data['RESULTS']); echo '</pre>';

    if($premiere_comm[1]['MARKING_PERIOD_ID'] == $mp){ /// Première communication 
        CadoHTMLcommunication(_reportcard_cat2,$courses,$data,$grade_id,$student_id);
        return;
    }
    if(strpos($grade_id,"scolaire")) /// Préscolaire 
        CadoHTMLresultatsPrescolaire(_reportcard_cat2,$quarters_RET,$courses,$data,$grade_id,$student_id,$mp);
    else    /// Primaire et Secondaire 
        CadoHTMLresultatsCycles(_reportcard_cat2,$quarters_RET,$courses,$data,$grade_id,$student_id,$mp);
}

//#####################################################//
//### RESULTATS

function CadoHTMLresultatsCycles($title,$quarts,$courses,$data,$grade_id,$student_id,$mp){
    global $publish_parents;
    global $one_page_pdf;
    
    $one_page_pdf=false;
    if(strpos($grade_id,"Primaire")){
        $year1='Année 1';
        $year2='Année 2';
        $abscences=false;
        $colspan=4;
    }else
    if(strpos($grade_id,"Secondaire 1") || strpos($grade_id,"Secondaire 2")){
        $year1='1re secondaire';
        $year2='2e secondaire';       
        $abscences=true;
        $colspan=4;
        $cycle='Cycle 1';
    }
    if(strpos($grade_id,"Secondaire 3") || strpos($grade_id,"Secondaire 4") || strpos($grade_id,"Secondaire 5")){
        $year1=$grade_id;
        $abscences=true;
        $colspan=2;
        $cycle=$grade_id;
    }
    if(strpos($grade_id,"Primaire 1") || strpos($grade_id,"Primaire 2"))
        $cycle='Cycle 1';
    if(strpos($grade_id,"Primaire 3") || strpos($grade_id,"Primaire 4"))
        $cycle='Cycle 2';
    if(strpos($grade_id,"Primaire 5") || strpos($grade_id,"Primaire 6"))
        $cycle='Cycle 3';
    if(strpos($grade_id,"Primaire 1") || strpos($grade_id,"Primaire 3")|| strpos($grade_id,"Primaire 5") || strpos($grade_id,"Secondaire 1")){
        $YY2=UserSyear();
        $toggle=true;
    }
    else{
        $YY1=UserSyear();
        $YY2=UserSyear()-1;
        $toggle=false;
    }
    $commentspan=$colspan*2+1;
    if(is_countable($data['RESULTS']))
        $numrow=count($data['RESULTS']);
    $numquart=count($quarts);
    if(is_countable($data['COURSES'])) 
        $numcourses=count($data['COURSES']);
    echo '<pre class="section-title">'; echo $title; echo'</pre>';    
    for($courseloop=1; $courseloop <= $numcourses ; $courseloop++){
        if(html_entity_decode($data['COURSES'][$courseloop]['COMMENT'] ) == html_entity_decode('Cours abandonné.') || $data['COURSES'][$courseloop]['DROPPED'] || ! $data['COURSES'][$courseloop]    )
            continue;
        if(strpos($grade_id,"Secondaire")){
            echo'<table class="class-results__table"><tr><th rowspan="3" class="class-results--align-left class-results__th--left-header"><h1>' . $data['COURSES'][$courseloop]['COURSE_TITLE']  . '</h1>Cours :' . $data['COURSES'][$courseloop]['COURSE_NUMBER']  . '<br>Enseignant(e) :' . $data['COURSES'][$courseloop]['TEACHER_NAME']  . ''; 
            $cycle=$data['COURSES'][$courseloop]['GRADE_TITLE'];
        }
        else 
            echo'<table class="class-results__table"><tr><th rowspan="3" class="class-results--align-left class-results__th--left-header"><h1>' . $data['COURSES'][$courseloop]['COURSE_TITLE']  . '</h1><br>Enseignant(e) :' . $data['COURSES'][$courseloop]['TEACHER_NAME']  . ''; 
        echo '</th><th colspan="' . $colspan *2  . '" class="class-results__3col-right">' . $cycle  . '</th></tr>';
        if($colspan==4){
            echo '<tr><th colspan="' . $colspan  . '" class="class-results__3col-right">' . $year1 .'</th><th colspan="' . $colspan  . '" class="class-results__3col-right">' . $year2 . '</th></tr>';
        }else echo '<tr></tr>';
        for($quartloop=0; $quartloop < $numquart ; $quartloop++){
            echo' <th class="class-results__3col__th">' . $quarts[$quartloop+1]['TITLE']  .'</th>';
        }
        echo' <th class="class-results__3col__th">Résultat final</th> ';
        if($colspan==4){
            for($quartloop=0; $quartloop < $numquart ; $quartloop++){
                echo'<th class="class-results__3col__th">' . $quarts[$quartloop+1]['TITLE'] .'</th>';
            }
            echo'<th class="class-results__3col__th">Résultat final</th></tr>';
        }else 
            echo '</tr>';
        $numcompetences=$data['COURSES'][$courseloop]['ASIGN_COUNT'];
        $resultat_final[$YY1]=0;
        $resultat_final[$YY2]=0;
        $has_final[$YY1]=false;
        $has_final[$YY2]=false;
        for($comploop=1; $comploop <= $numcompetences ; $comploop++){
            $comp_total=0;
            $weight_total=0;
            if($numcompetences>1){
                if($toggle)
                    echo '<tr> <td class="class-results--align-right">' . $data['RESULTS']['COURSE'][$courseloop]['ASSIGNMENT'][$comploop]['YEAR'][$YY2]['COMPETENCE']  .'</td>';
                else
                    echo '<tr> <td class="class-results--align-right">' . $data['RESULTS']['COURSE'][$courseloop]['ASSIGNMENT'][$comploop]['YEAR'][$YY1]['COMPETENCE']  .'</td>';
            }else
                echo '<td class="class-results--align-right">' . _studentAverage .'</td>';
            if($colspan==4){
                for($quartloop=1; $quartloop <= $numquart ; $quartloop++){
                    // Year 1 , all competences and quarts
                    echo'<td class="class-results--align-center">' . _myround($data['RESULTS']['COURSE'][$courseloop]['QUART'][$quartloop]['ASSIGNMENT'][$comploop]['YEAR'][$YY2]['GRADE'])   .'</td>';
                    if(_myround($data['RESULTS']['COURSE'][$courseloop]['QUART'][$quartloop]['ASSIGNMENT'][$comploop]['YEAR'][$YY2]['GRADE'])){
                        $comp_total += _myround($data['RESULTS']['COURSE'][$courseloop]['QUART'][$quartloop]['ASSIGNMENT'][$comploop]['YEAR'][$YY2]['GRADE']) / 100 * $data['RESULTS']['QUART'][$quartloop]['YEAR'][$YY2]['FINAL_WEIGHT'];
                        $weight_total += $data['RESULTS']['QUART'][$quartloop]['YEAR'][$YY2]['FINAL_WEIGHT'];
                    }
                } 
                // Year 1 , competences totals
                if($data['RESULTS']['COURSE'][$courseloop]['ASSIGNMENT'][$comploop]['YEAR'][$YY2]['FINALEXAM'] && $data['RESULTS']['COURSE'][$courseloop]['ASSIGNMENT'][$comploop]['YEAR'][$YY2]['FINALEXAMWEIGTH']){
                    $has_final[$YY2]=true;
                    $comp_total += _myround($data['RESULTS']['COURSE'][$courseloop]['ASSIGNMENT'][$comploop]['YEAR'][$YY2]['FINALEXAM']) / 100 * $data['RESULTS']['COURSE'][$courseloop]['ASSIGNMENT'][$comploop]['YEAR'][$YY2]['FINALEXAMWEIGTH'];
                    $weight_total += $data['RESULTS']['COURSE'][$courseloop]['ASSIGNMENT'][$comploop]['YEAR'][$YY2]['FINALEXAMWEIGTH'];
                    if(! $publish_parents)
                        echo'<td class="class-results--align-center "><span style="color:red;"><i>(' . _myround($data['RESULTS']['COURSE'][$courseloop]['ASSIGNMENT'][$comploop]['YEAR'][$YY2]['FINALEXAM']) .  ')</i></span><spanstyle="color:black;">  ' . ($comp_total !=0 ? _myround($comp_total * 100 / $weight_total) . '' : 'TI')  .'</span></td>';
                    else
                        echo'<td class="class-results--align-center">' . ($comp_total !=0 ? _myround($comp_total * 100 / $weight_total) . '' : 'TI')  .'</td>'; 
                }
                else
                    echo'<td class="class-results--align-center">' . ($comp_total !=0 ? _myround($comp_total * 100 / $weight_total) . '' : 'TI')  .'</td>';
                if($weight_total)
                    $resultat_final[$YY2]+= _myround($comp_total * 100 / $weight_total) * $data['RESULTS']['COURSE'][$courseloop]['QUART'][$quartloop-1]['ASSIGNMENT'][$comploop]['YEAR'][$YY2]['WEIGTH'];
            }   
            $comp_total=0;
            $weight_total=0;
            for($quartloop=1; $quartloop <= $numquart ; $quartloop++){
                // Year 2 , all competences and quarts
                echo'<td class="class-results--align-center">' . _myround($data['RESULTS']['COURSE'][$courseloop]['QUART'][$quartloop]['ASSIGNMENT'][$comploop]['YEAR'][$YY1]['GRADE'])  .'</td>';
                if(_myround($data['RESULTS']['COURSE'][$courseloop]['QUART'][$quartloop]['ASSIGNMENT'][$comploop]['YEAR'][$YY1]['GRADE'])){
                    $comp_total += _myround($data['RESULTS']['COURSE'][$courseloop]['QUART'][$quartloop]['ASSIGNMENT'][$comploop]['YEAR'][$YY1]['GRADE']) / 100 * $data['RESULTS']['QUART'][$quartloop]['YEAR'][$YY1]['FINAL_WEIGHT'];
                    $weight_total += $data['RESULTS']['QUART'][$quartloop]['YEAR'][$YY1]['FINAL_WEIGHT'];
                }
            }
            // Year 2 , competences totals 
            if($data['RESULTS']['COURSE'][$courseloop]['ASSIGNMENT'][$comploop]['YEAR'][$YY1]['FINALEXAM'] && $data['RESULTS']['COURSE'][$courseloop]['ASSIGNMENT'][$comploop]['YEAR'][$YY1]['FINALEXAMWEIGTH']){
                $has_final[$YY1]=true;
                $comp_total += _myround($data['RESULTS']['COURSE'][$courseloop]['ASSIGNMENT'][$comploop]['YEAR'][$YY1]['FINALEXAM']) / 100 * $data['RESULTS']['COURSE'][$courseloop]['ASSIGNMENT'][$comploop]['YEAR'][$YY1]['FINALEXAMWEIGTH'];
                $weight_total += $data['RESULTS']['COURSE'][$courseloop]['ASSIGNMENT'][$comploop]['YEAR'][$YY1]['FINALEXAMWEIGTH'];
                if(! $publish_parents)
                    echo'<td class="class-results--align-center "><span style="color:red;"><i>(' . _myround($data['RESULTS']['COURSE'][$courseloop]['ASSIGNMENT'][$comploop]['YEAR'][$YY1]['FINALEXAM']) .  ')</i></span><spanstyle="color:black;">  ' . ($comp_total !=0 ? _myround($comp_total * 100 / $weight_total) . '' : 'TI')  .'</span></td></tr>';
                else
                    echo'<td class="class-results--align-center">' . ($comp_total !=0 ? _myround($comp_total * 100 / $weight_total) . '' : 'TI')  .'</td></tr>';
            }
            else
                echo'<td class="class-results--align-center">' . ($comp_total !=0 ? _myround($comp_total * 100 / $weight_total) . '' : '  ') .'</td></tr>';
            if($weight_total)
                $resultat_final[$YY1]+= _myround($comp_total * 100 / $weight_total) * $data['RESULTS']['COURSE'][$courseloop]['QUART'][$quartloop-1]['ASSIGNMENT'][$comploop]['YEAR'][$YY1]['WEIGTH'];
        }
        if($numcompetences>1){
            echo '<td class="class-results--align-right">' . _studentAverage .'</td>';
            $comp_total=0;
            $weight_total=0;
            if($colspan==4){
                for($quartloop=1; $quartloop <= $numquart ; $quartloop++){
                    // Year 1 quarts totals
                    echo'<td class="class-results--align-center">' . _myround($data['RESULTS']['COURSE'][$courseloop]['QUART'][$quartloop]['YEAR'][$YY2]['FINAL'])   .'</td>';
                    if(_myround($data['RESULTS']['COURSE'][$courseloop]['QUART'][$quartloop]['YEAR'][$YY2]['FINAL'])){
                        $comp_total += _myround($data['RESULTS']['COURSE'][$courseloop]['QUART'][$quartloop]['YEAR'][$YY2]['FINAL']) / 100 * $data['RESULTS']['QUART'][$quartloop]['YEAR'][$YY2]['FINAL_WEIGHT'];
                        $weight_total += $data['RESULTS']['QUART'][$quartloop]['YEAR'][$YY2]['FINAL_WEIGHT'];
                    }
                }
                // Year 1 , all quarts total
                if($data['RESULTS']['COURSE'][$courseloop]['ASSIGNMENT'][$comploop]['YEAR'][$YY2]['FINALEXAM'] && $data['RESULTS']['COURSE'][$courseloop]['ASSIGNMENT'][$comploop]['YEAR'][$YY2]['FINALEXAMWEIGTH']){
                    $has_final[$YY2]=true;
                    $comp_total += _myround($data['RESULTS']['COURSE'][$courseloop]['ASSIGNMENT'][$comploop]['YEAR'][$YY2]['FINALEXAM']) / 100 * $data['RESULTS']['COURSE'][$courseloop]['ASSIGNMENT'][$comploop]['YEAR'][$YY2]['FINALEXAMWEIGTH'];
                    $weight_total += $data['RESULTS']['COURSE'][$courseloop]['ASSIGNMENT'][$comploop]['YEAR'][$YY2]['FINALEXAMWEIGTH'];
                    if(! $publish_parents)
                        echo'<td class="class-results--align-center "><span style="color:red;"><i>(' . _myround($data['RESULTS']['COURSE'][$courseloop]['ASSIGNMENT'][$comploop]['YEAR'][$YY2]['FINALEXAM']) .  ')</i></span><spanstyle="color:black;">  ' . ($comp_total !=0 ? _myround($comp_total * 100 / $weight_total) . '' : 'TI')  .'</span></td>';
                    else
                        echo'<td class="class-results--align-center">' . ($comp_total !=0 ? _myround($comp_total * 100 / $weight_total) . '' : 'TI')  .'</td>';
                }
                if($has_final[$YY2]){
                    if(! $publish_parents)
                        echo'<td class="class-results--align-center"> <span style="color:red;">' . ($comp_total !=0 ? _myround($resultat_final[$YY2]) . '' : '')   .'</td>';
                    else
                        echo'<td class="class-results--align-center"> <span>' . ($comp_total !=0 ? _myround($resultat_final[$YY2]) . '' : '')   .'</td>';
                }
                else
                    echo'<td class="class-results--align-center">' . ($comp_total !=0 ? _myround($comp_total * 100 / $weight_total) . '' : 'TI')   .'</td>';
            }
        $comp_total=0;
        $weight_total=0;
            for($quartloop=1; $quartloop <= $numquart ; $quartloop++){
                // Year 2 quarts totals
                echo'<td class="class-results--align-center">' . _myround($data['RESULTS']['COURSE'][$courseloop]['QUART'][$quartloop]['YEAR'][$YY1]['FINAL'])  .'</td>';
                if(_myround($data['RESULTS']['COURSE'][$courseloop]['QUART'][$quartloop]['YEAR'][$YY1]['FINAL'])){
                    $comp_total += _myround($data['RESULTS']['COURSE'][$courseloop]['QUART'][$quartloop]['YEAR'][$YY1]['FINAL']) / 100 * $data['RESULTS']['QUART'][$quartloop]['YEAR'][$YY1]['FINAL_WEIGHT'];
                    $weight_total += $data['RESULTS']['QUART'][$quartloop]['YEAR'][$YY1]['FINAL_WEIGHT'];
                }
            }
            // Year 2 , all quarts total
            if($data['RESULTS']['COURSE'][$courseloop]['ASSIGNMENT'][$comploop]['YEAR'][$YY1]['FINALEXAM'] && $data['RESULTS']['COURSE'][$courseloop]['ASSIGNMENT'][$comploop]['YEAR'][$YY1]['FINALEXAMWEIGTH']){
                $has_final[$YY1]=true;
                $comp_total += _myround($data['RESULTS']['COURSE'][$courseloop]['ASSIGNMENT'][$comploop]['YEAR'][$YY1]['FINALEXAM']) / 100 * $data['RESULTS']['COURSE'][$courseloop]['ASSIGNMENT'][$comploop]['YEAR'][$YY1]['FINALEXAMWEIGTH'];
                $weight_total += $data['RESULTS']['COURSE'][$courseloop]['ASSIGNMENT'][$comploop]['YEAR'][$YY1]['FINALEXAMWEIGTH'];
                if(! $publish_parents)
                    echo'<td class="class-results--align-center "><span style="color:red;"><i>(' . _myround($data['RESULTS']['COURSE'][$courseloop]['ASSIGNMENT'][$comploop]['YEAR'][$YY1]['FINALEXAM']) .  ')</i></span><spanstyle="color:black;">  ' . ($comp_total !=0 ? _myround($comp_total * 100 / $weight_total) . '' : 'TI')  .'</span></td>';
                else
                    echo'<td class="class-results--align-center ">' . ($comp_total !=0 ? _myround($comp_total * 100 / $weight_total) . '' : 'TI')  .'</td>';
            }
            if($has_final[$YY1]){
                    if(! $publish_parents)
                        echo'<td class="class-results--align-center"><span style="color:red;">' . ($comp_total !=0 ? _myround($resultat_final[$YY1]) . '' : '')   .'</td></tr>';
                    else
                        echo'<td class="class-results--align-center"><span>' . ($comp_total !=0 ? _myround($resultat_final[$YY1]) . '' : '')   .'</td></tr>';
            }
            else
                echo'<td class="class-results--align-center ">' .($comp_total !=0 ? _myround($comp_total * 100 / $weight_total) . '' : '  ').'</td></tr>';
        }
        echo '<td class="class-results--align-right">' . _groupAverage .'</td>';
        $comp_total=0;
        $weight_total=0;
        if($colspan==4){
            for($quartloop=1; $quartloop <= $numquart ; $quartloop++){
                // Year 1 group average
                echo'<td class="class-results--align-center">' . _myround($data['RESULTS']['COURSE'][$courseloop]['QUART'][$quartloop]['YEAR'][$YY2]['GROUP_AVG'])   .'</td>';
                if(_myround($data['RESULTS']['COURSE'][$courseloop]['QUART'][$quartloop]['YEAR'][$YY2]['GROUP_AVG'])){
                    $comp_total += $data['RESULTS']['COURSE'][$courseloop]['QUART'][$quartloop]['YEAR'][$YY2]['GROUP_AVG'] / 100 * $data['RESULTS']['QUART'][$quartloop]['YEAR'][$YY2]['FINAL_WEIGHT'];
                    $weight_total += $data['RESULTS']['QUART'][$quartloop]['YEAR'][$YY2]['FINAL_WEIGHT'];
                }
            }
            //echo  $comp_total * 100 / $weight_total;
            // Year 1 , all quarts group average total
            echo'<td class="class-results--align-center">' . ($comp_total !=0 ? _myround($comp_total * 100 / $weight_total) . '' : '  ')   .'</td>';
        }
        $comp_total=0;
        $weight_total=0;
            for($quartloop=1; $quartloop <= $numquart ; $quartloop++){
                // Year 2 group average
                echo'<td class="class-results--align-center">' . _myround($data['RESULTS']['COURSE'][$courseloop]['QUART'][$quartloop]['YEAR'][$YY1]['GROUP_AVG'])  .'</td>';
                if(_myround($data['RESULTS']['COURSE'][$courseloop]['QUART'][$quartloop]['YEAR'][$YY1]['GROUP_AVG'])){
                    $comp_total += $data['RESULTS']['COURSE'][$courseloop]['QUART'][$quartloop]['YEAR'][$YY1]['GROUP_AVG'] / 100 * $data['RESULTS']['QUART'][$quartloop]['YEAR'][$YY1]['FINAL_WEIGHT'];
                    $weight_total += $data['RESULTS']['QUART'][$quartloop]['YEAR'][$YY1]['FINAL_WEIGHT'];
                }
            }
        // Year 2 , all quarts group average total
        echo'<td class="class-results--align-center">' .($comp_total !=0 ? _myround($comp_total * 100 / $weight_total) . '' : '  ').'</td></tr>';

        if(! $publish_parents){
            if($colspan==4){
                if($data['RESULTS']['COURSE'][$courseloop]['QUART'][1]['YEAR'][$YY2]['DIFF'] || $data['RESULTS']['COURSE'][$courseloop]['QUART'][2]['YEAR'][$YY2]['DIFF'] || $data['RESULTS']['COURSE'][$courseloop]['QUART'][3]['YEAR'][$YY2]['DIFF'] || $data['RESULTS']['COURSE'][$courseloop]['QUART'][1]['YEAR'][$YY1]['DIFF'] || $data['RESULTS']['COURSE'][$courseloop]['QUART'][2]['YEAR'][$YY1]['DIFF'] || $data['RESULTS']['COURSE'][$courseloop]['QUART'][3]['YEAR'][$YY1]['DIFF'])
                {
                    echo '<td></td>';
                    for($quartloop=1; $quartloop <= $numquart ; $quartloop++){
                        if($data['RESULTS']['COURSE'][$courseloop]['QUART'][1]['YEAR'][$YY2]['DIFF'] || $data['RESULTS']['COURSE'][$courseloop]['QUART'][2]['YEAR'][$YY2]['DIFF'] || $data['RESULTS']['COURSE'][$courseloop]['QUART'][3]['YEAR'][$YY2]['DIFF']){
                            if($data['RESULTS']['COURSE'][$courseloop]['QUART'][$quartloop]['YEAR'][$YY2]['DIFF'])
                                echo '<td class="class-results--align-center  highligth">' . $data['RESULTS']['COURSE'][$courseloop]['QUART'][$quartloop]['YEAR'][$YY2]['DIFF'] .'</td>';
                            else echo '<td></td>';
                        }else echo '<td></td>';
                    }
                    echo '<td></td>';
                    for($quartloop=1; $quartloop <= $numquart ; $quartloop++){
                        if($data['RESULTS']['COURSE'][$courseloop]['QUART'][1]['YEAR'][$YY1]['DIFF'] || $data['RESULTS']['COURSE'][$courseloop]['QUART'][2]['YEAR'][$YY1]['DIFF'] || $data['RESULTS']['COURSE'][$courseloop]['QUART'][3]['YEAR'][$YY1]['DIFF']){
                            if($data['RESULTS']['COURSE'][$courseloop]['QUART'][$quartloop]['YEAR'][$YY1]['DIFF'])
                                echo '<td class="class-results--align-center  highligth">' . $data['RESULTS']['COURSE'][$courseloop]['QUART'][$quartloop]['YEAR'][$YY1]['DIFF'] .'</td>';
                            else echo '<td></td>';
                        }else echo '<td></td>';
                    }
                    echo '<td></td>';
                }
            }else
            for($quartloop=1; $quartloop <= $numquart ; $quartloop++){
                if($data['RESULTS']['COURSE'][$courseloop]['QUART'][1]['YEAR'][$YY1]['DIFF'] || $data['RESULTS']['COURSE'][$courseloop]['QUART'][2]['YEAR'][$YY1]['DIFF'] || $data['RESULTS']['COURSE'][$courseloop]['QUART'][3]['YEAR'][$YY1]['DIFF'] )
                {
                    echo '<td></td>';
                    if($data['RESULTS']['COURSE'][$courseloop]['QUART'][$quartloop]['YEAR'][$YY1]['DIFF'])
                        echo '<td class="class-results--align-center  highligth">' . $data['RESULTS']['COURSE'][$courseloop]['QUART'][$quartloop]['YEAR'][$YY1]['DIFF'] .'</td><td></td>';
                }
            }
        }
        if($abscences){
            if($colspan==4){
                echo '<tr><tr></tr>';
                echo '</tr><td class="class-results--align-right"">Unités</td>
                <td colspan=1 style="background-color:grey"></td><td colspan=1 style="background-color:grey"></td>
                <td colspan=1 style="background-color:grey"></td><td colspan=1 class="class-results--align-center">  </td>
                <td colspan=1 style="background-color:grey"></td><td colspan=1 style="background-color:grey"></td>
                <td colspan=1 style="background-color:grey"></td><td colspan=1 class="class-results--align-center">  </td>
                ';
                echo '</tr><td class="class-results--align-right"">Absences / Jours de classe</td>';
                $total_absc=0;
                $total_quart_abs=0;
                for($quartloop=1; $quartloop <= $numquart ; $quartloop++){
                    echo '<td colspan=1 class="center">' .$data['STUDENT_ABSCENCES_QUARTER']['COURSE'][$courseloop]['QUART'][$quartloop]['YEAR'][$YY2]['ABSCENCES'] .' / ' . $data['ABSCENCES_QUARTER']['QUART'][$quartloop]['YEAR'][$YY2]['MAXDAYS_QUARTER'] .'</td>';
                    $total_absc+=$data['STUDENT_ABSCENCES_QUARTER']['COURSE'][$courseloop]['QUART'][$quartloop]['YEAR'][$YY2]['ABSCENCES'];
                    $total_quart_abs+=$data['ABSCENCES_QUARTER']['QUART'][$quartloop]['YEAR'][$YY2]['MAXDAYS_QUARTER'];
                }
                echo '<td colspan=1 class="class-results--align-center">' . $total_absc .' / ' . $total_quart_abs .'</td>';
                $total_absc=0;
                $total_quart_abs=0;
                for($quartloop=1; $quartloop <= $numquart ; $quartloop++){
                    echo '<td colspan=1 class="center">' .$data['STUDENT_ABSCENCES_QUARTER']['COURSE'][$courseloop]['QUART'][$quartloop]['YEAR'][$YY1]['ABSCENCES'] .' / ' . $data['ABSCENCES_QUARTER']['QUART'][$quartloop]['YEAR'][$YY1]['MAXDAYS_QUARTER'] .'</td>';
                    $total_absc+=$data['STUDENT_ABSCENCES_QUARTER']['COURSE'][$courseloop]['QUART'][$quartloop]['YEAR'][$YY1]['ABSCENCES'];
                    $total_quart_abs+=$data['ABSCENCES_QUARTER']['QUART'][$quartloop]['YEAR'][$YY1]['MAXDAYS_QUARTER'];
                }
                echo '<td colspan=1 class="class-results--align-center">' . $total_absc .' / ' . $total_quart_abs .'</td>';
            }else{
                echo '<tr><tr></tr>';
                echo '</tr><td class="class-results--align-right"">Unités</td>
                <td colspan=1 style="background-color:grey"></td><td colspan=1 style="background-color:grey"></td>
                <td colspan=1 style="background-color:grey"></td><td colspan=1 class="class-results--align-center">  </td>
                ';
                echo '</tr><td class="class-results--align-right"">Absences / Jours de classe</td>';
                $total_absc=0;
                $total_quart_abs=0;
                for($quartloop=1; $quartloop <= $numquart ; $quartloop++){
                    echo '<td colspan=1 class="center">' .$data['STUDENT_ABSCENCES_QUARTER']['COURSE'][$courseloop]['QUART'][$quartloop]['YEAR'][$YY1]['ABSCENCES'] .' / ' . $data['ABSCENCES_QUARTER']['QUART'][$quartloop]['YEAR'][$YY1]['MAXDAYS_QUARTER'] .'</td>';
                    $total_absc+=$data['STUDENT_ABSCENCES_QUARTER']['COURSE'][$courseloop]['QUART'][$quartloop]['YEAR'][$YY1]['ABSCENCES'];
                    $total_quart_abs+=$data['ABSCENCES_QUARTER']['QUART'][$quartloop]['YEAR'][$YY1]['MAXDAYS_QUARTER'];
                }
                echo '<td colspan=1 class="class-results--align-center">' . $total_absc .' / ' . $total_quart_abs .'</td>';
            }     
    
        }
        echo '<tr> <td colspan="' . $commentspan . '">' . _comments . ': <b><i>' . $data['COURSES'][$courseloop]['COMMENT'] . '</i></b></td></tr>';
        echo '</table>';
    // End courloop
    }
}

//### FIN RESULTATS
//#####################################################//

//#####################################################//
//### START HTML FUNCTIONS

function CadoHTMLresultatsPrescolaire($title,$quarts,$courses,$data,$grade_id,$student_id,$mp){
    global $publish_parents;
    global $one_page_pdf;

    $one_page_pdf=true;
    usort($courses, function ($a, $b) {return $a['COURSE_NUMBER'] > $b['COURSE_NUMBER'];});
    // echo '<pre>' ;print_r($courses); echo '</pre>';
    $commentspan=$colspan*2+1;
    $numquart=count($quarts);
    if(is_countable($data['COURSES'])) 
        $numcourses=count($data['COURSES']);
    echo '<table class="page-prescolaire-table"><p ">&nbsp;</p><p ">&nbsp;</p><h2 class="section-prescolaire-title"><span>2</span> Constats</h2>';
    echo'<table class="class-results__table noborder class-border-right"><thead><tr><td>Domaines et compétences</td><td>Étape</td><td colspan="2">État de développement des compétences</td></tr></thead>';
    $mp_E1=DBGet(DBQuery('SELECT MARKING_PERIOD_ID FROM  school_quarters WHERE SYEAR=\'' . UserSyear() . '\' AND SCHOOL_ID=\'' . UserSchool() . '\' AND title="Étape 1"'));
    $mp_E2=DBGet(DBQuery('SELECT MARKING_PERIOD_ID FROM  school_quarters WHERE SYEAR=\'' . UserSyear() . '\' AND SCHOOL_ID=\'' . UserSchool() . '\' AND title="Étape 2"'));
    $mp_E3=DBGet(DBQuery('SELECT MARKING_PERIOD_ID FROM  school_quarters WHERE SYEAR=\'' . UserSyear() . '\' AND SCHOOL_ID=\'' . UserSchool() . '\' AND title="Étape 3"'));
    foreach ($courses as $courseloop=> $col) {
        $USER_RET_E1=DBGet(DBQuery('SELECT comment from student_report_card_grades where SYEAR=\'' . UserSyear() . '\'  AND STUDENT_ID = \''. $student_id . '\' AND COURSE_PERIOD_ID=\''.  $col['COURSE_PERIOD_ID']. '\' AND marking_period_id=\''.  $mp_E1[1]['MARKING_PERIOD_ID'] . '\' '));
        $USER_RET_E2=DBGet(DBQuery('SELECT comment from student_report_card_grades where SYEAR=\'' . UserSyear() . '\'  AND STUDENT_ID = \''. $student_id . '\' AND COURSE_PERIOD_ID=\''.  $col['COURSE_PERIOD_ID'] . '\' AND marking_period_id=\''.  $mp_E2[1]['MARKING_PERIOD_ID'] . '\' '));
        $USER_RET_E3=DBGet(DBQuery('SELECT comment from student_report_card_grades where SYEAR=\'' . UserSyear() . '\'  AND STUDENT_ID = \''. $student_id . '\' AND COURSE_PERIOD_ID=\''.  $col['COURSE_PERIOD_ID'] . '\' AND marking_period_id=\''.  $mp_E3[1]['MARKING_PERIOD_ID'] . '\' '));
        $progress_ret_E1 = DBGet(DBQuery('SELECT POINTS,COMMENT FROM gradebook_grades WHERE STUDENT_ID=\'' . $student_id . '\' AND COURSE_PERIOD_ID=\'' . $col['COURSE_PERIOD_ID'] . '\' and ASSIGNMENT_ID=(select assignment_id from gradebook_assignments where COURSE_PERIOD_ID=\'' . $col['COURSE_PERIOD_ID'] . '\' and marking_period_id=\'' .$mp_E1[1]['MARKING_PERIOD_ID'] . '\' and TITLE="Progrès" )'));
        $progress_ret_E2 = DBGet(DBQuery('SELECT POINTS,COMMENT FROM gradebook_grades WHERE STUDENT_ID=\'' . $student_id . '\' AND COURSE_PERIOD_ID=\'' . $col['COURSE_PERIOD_ID'] . '\' and ASSIGNMENT_ID=(select assignment_id from gradebook_assignments where COURSE_PERIOD_ID=\'' . $col['COURSE_PERIOD_ID'] . '\' and marking_period_id=\'' . $mp_E2[1]['MARKING_PERIOD_ID'] . '\' and TITLE="Progrès" )'));
        $progress_ret_E3 = DBGet(DBQuery('SELECT POINTS,COMMENT FROM gradebook_grades WHERE STUDENT_ID=\'' . $student_id . '\' AND COURSE_PERIOD_ID=\'' . $col['COURSE_PERIOD_ID'] . '\' and ASSIGNMENT_ID=(select assignment_id from gradebook_assignments where COURSE_PERIOD_ID=\'' . $col['COURSE_PERIOD_ID'] . '\' and marking_period_id=\'' . $mp_E3[1]['MARKING_PERIOD_ID'] . '\' and TITLE="Progrès" )'));
        $defis_ret_E1 = DBGet(DBQuery('SELECT POINTS,COMMENT FROM gradebook_grades WHERE STUDENT_ID=\'' . $student_id . '\' AND COURSE_PERIOD_ID=\'' . $col['COURSE_PERIOD_ID']. '\' and ASSIGNMENT_ID=(select assignment_id from gradebook_assignments where COURSE_PERIOD_ID=\'' . $col['COURSE_PERIOD_ID'] . '\' and marking_period_id=\'' . $mp_E1[1]['MARKING_PERIOD_ID'] . '\' and TITLE="Défi(s)" )'));
        $defis_ret_E2 = DBGet(DBQuery('SELECT POINTS,COMMENT FROM gradebook_grades WHERE STUDENT_ID=\'' . $student_id . '\' AND COURSE_PERIOD_ID=\'' . $col['COURSE_PERIOD_ID'] . '\' and ASSIGNMENT_ID=(select assignment_id from gradebook_assignments where COURSE_PERIOD_ID=\'' . $col['COURSE_PERIOD_ID'] . '\' and marking_period_id=\'' . $mp_E2[1]['MARKING_PERIOD_ID'] . '\' and TITLE="Défi(s)" )'));
        $defis_ret_E3 = DBGet(DBQuery('SELECT POINTS,COMMENT FROM gradebook_grades WHERE STUDENT_ID=\'' . $student_id . '\' AND COURSE_PERIOD_ID=\'' . $col['COURSE_PERIOD_ID'] . '\' and ASSIGNMENT_ID=(select assignment_id from gradebook_assignments where COURSE_PERIOD_ID=\'' . $col['COURSE_PERIOD_ID'] . '\' and marking_period_id=\'' . $mp_E3[1]['MARKING_PERIOD_ID'] . '\' and TITLE="Défi(s)" )'));
        echo '<tr><td colspan="6"">&nbsp</td></tr><tr><td rowspan="10" class="border-left">' . $col['COURSE_TITLE']  . '</td></tr><tr><td rowspan="3" class="center border-left bkgnd1"><b>1</b></td>';
        if($progress_ret_E1[1]['POINTS']==1)
            echo '<td colspan=2" class="class-prescolaire-checkbox bkgnd1"><b>L’élève se développe très bien au regard de la compétence visée.</b>';
        elseif($progress_ret_E1[1]['POINTS']==2)
            echo '<td colspan=2" class="class-prescolaire-checkbox bkgnd1"><b>L’élève se développe adéquatement au regard de la compétence visée.</b>';
        elseif($progress_ret_E1[1]['POINTS']==3)
            echo '<td colspan=2" class="class-prescolaire-checkbox bkgnd1"><b>L’élève se développe avec certaines difficultés au regard de la compétence visée.</b>';
        elseif($progress_ret_E1[1]['POINTS']==4)
            echo '<td colspan=2" class="class-prescolaire-checkbox bkgnd1"><b>L’élève se développe avec des difficultés importantes au regard de la compétence visée.</b>';    
        else 
            echo '<td colspan=2 class="class-prescolaire-checkbox bkgnd1">&nbsp';
        echo '</td></tr><tr><td colspan="2" class="bkgnd1"><b>Commentaires: </b>' . $USER_RET_E1[1]['COMMENT'] . '</td></tr><tr><td class="bkgnd1"><b>PROGRÈS: </b>' . $progress_ret_E1[1]['COMMENT'] . '</td><td class="bkgnd1"><b>DÉFI(S): </b>' . $defis_ret_E1[1]['COMMENT'] . '</td></tr><tr><td rowspan="3" class="center border-left bkgnd2"><b>2</b></td>';
        if($progress_ret_E2[1]['POINTS']==1)
            echo '<td colspan=2" class="class-prescolaire-checkbox bkgnd2"><b>L’élève se développe très bien au regard de la compétence visée.</b>';
        elseif($progress_ret_E2[1]['POINTS']==2)
            echo '<td colspan=2" class="class-prescolaire-checkbox bkgnd2"><b>L’élève se développe adéquatement au regard de la compétence visée.</b>';
        elseif($progress_ret_E2[1]['POINTS']==3)
            echo '<td colspan=2" class="class-prescolaire-checkbox bkgnd2"><b>L’élève se développe avec certaines difficultés au regard de la compétence visée.</b>';
        elseif($progress_ret_E2[1]['POINTS']==4)
            echo '<td colspan=2" class="class-prescolaire-checkbox bkgnd2"><b>L’élève se développe avec des difficultés importantes au regard de la compétence visée.</b>';    
        else 
            echo '<td colspan=2 class="class-prescolaire-checkbox bkgnd2">&nbsp';
        echo '</td></tr><tr><td colspan="2" class="bkgnd2"><b>Commentaires: </b>' . $USER_RET_E2[1]['COMMENT'] . '</td></tr><tr><td  class="bkgnd2"><b>PROGRÈS: </b>' . $progress_ret_E2[1]['COMMENT'] . '</td><td class="bkgnd2"><b>DÉFI(S): </b>' . $defis_ret_E2[1]['COMMENT'] . '</td></tr><tr><td rowspan="3" class="bilan  bkgnd3 border-left"><b>&nbsp&nbsp&nbsp3&nbsp&nbsp&nbsp Bilan</td>';
        if($progress_ret_E3[1]['POINTS']==1)
            echo '<td colspan=2" class="class-prescolaire-checkbox  bkgnd3"><b>L’élève se développe très bien au regard de la compétence visée.</b>';
        elseif($progress_ret_E3[1]['POINTS']==2)
            echo '<td colspan=2" class="class-prescolaire-checkbox  bkgnd3"><b>L’élève se développe adéquatement au regard de la compétence visée.</b>';
        elseif($progress_ret_E3[1]['POINTS']==3)
            echo '<td colspan=2" class="class-prescolaire-checkbox  bkgnd3"><b>L’élève se développe avec certaines difficultés au regard de la compétence visée.</b>';
        elseif($progress_ret_E3[1]['POINTS']==4)
            echo '<td colspan=2" class="class-prescolaire-checkbox  bkgnd3"><b>L’élève se développe avec des difficultés importantes au regard de la compétence visée.</b>';    
        else 
            echo '<td colspan=2 class="class-prescolaire-checkbox bkgnd3">&nbsp';echo' </tr><tr><td colspan="2" class="bkgnd3"><b>Commentaires: </b>' . $USER_RET_E3[1]['COMMENT'] . '</td></tr><tr><td class="bkgnd3"><b>PROGRÈS: </b>' . $progress_ret_E3[1]['COMMENT'] . '</td><td class="bkgnd3"><b>DÉFI(S): </b>' . $defis_ret_E3[1]['COMMENT'] . '</td></tr>';
    }
    echo '</table>';
}

function CadoHTMLcommunication($title,$courses,$results,$grade_id,$student_id){
    global $publish_parents;

    // echo '<pre>' ;print_r($results); echo '</pre>';
    $percent=0;
    if (isset($_REQUEST['elements']['percents']))$percent=0;
    $numquart=3;
    $colspan=$numquart+1;
    $commentspan=$colspan+1;
    $courseloop=1;
    echo '<pre class="section-title">'; echo $title; echo'</pre>';
    foreach ($courses as $course_key=> $course) {
        foreach ($results['RESULTS']['COURSE'][$course_key]['QUART'][1]['ASSIGNMENT'] as $asignment_key=> $assignment){
            if($assignment['YEAR'][UserSyear()]['RAW'] > 90){ 
                if($percent)
                    $A[$course_key][$asignment_key]=$assignment['YEAR'][UserSyear()]['RAW'];
                else
                    $A[$course_key][$asignment_key]='X';
            }elseif($assignment['YEAR'][UserSyear()]['RAW'] > 80){ 
                if($percent)
                    $B[$course_key][$asignment_key]=$assignment['YEAR'][UserSyear()]['RAW'];
                else
                    $B[$course_key][$asignment_key]='X';
            }elseif ($assignment['YEAR'][UserSyear()]['RAW'] > 70 ){ 
                if($percent)
                    $C[$course_key][$asignment_key]=$assignment['YEAR'][UserSyear()]['RAW'];
                else
                    $C[$course_key][$asignment_key]='X';
            }elseif($assignment['YEAR'][UserSyear()]['RAW'] > 60 ){
                if($percent)
                    $D[$course_key][$asignment_key]=$assignment['YEAR'][UserSyear()]['RAW'];
                else
                    $D[$course_key][$asignment_key]='X';
            }
        }
        echo'<table class="class-results__table"><tr><th rowspan="2" class="class-results--align-left class-results__th--left-header"><h1>' . $results['COURSES'][$courseloop]['COURSE_TITLE']  . '</h1>Cours :' . $results['COURSES'][$courseloop]['COURSE_NUMBER']  . ' <br />';
        echo 'Enseignant(e) :';  
        echo $results['COURSES'][$courseloop]['TEACHER_NAME'];
        echo ' </th><th colspan="' . $colspan  . '" class="class-results__3col-right">' . $grade_id  . '</th></tr><tr>';
        echo'<th class="class-results__3col__th">A</th><th class="class-results__3col__th">B</th><th class="class-results__3col__th">C</th><th class="class-results__3col__th">D</th></tr>';
        foreach ($results['RESULTS']['COURSE'][$course_key]['ASSIGNMENT'] as $key=> $result){
            echo '<tr><td class="class-results--align-right">' . $results['RESULTS']['COURSE'][$course_key]['ASSIGNMENT'][$key]['YEAR'][UserSyear()]['COMPETENCE'] . ' </td> ';
            echo'
            <td class="class-results--align-center">' . $A[$courseloop][$key] .'</td>
            <td class="class-results--align-center">' . $B[$courseloop][$key] .'</td>
            <td class="class-results--align-center">' . $C[$courseloop][$key] .'</td>
            <td class="class-results--align-center">' . $D[$courseloop][$key] .'</td></tr>';
        }
        echo '<tr><tr></tr>';
        echo '<tr><td colspan="' . $commentspan . '">Commentaire: <b><i>' . $results['COURSES'][$courseloop]['COMMENT']. '</i></b></td></tr>';
        $courseloop++;
    // End courses
    }
    echo '</table>';
    echo '<tr> <i> <p style="text-align:right;">A : Très satisfaisant<br>B : Satisfaisant<br>C : Insatisfaisant<br>D : Très insatisfaisant</p></i></tr>';    
}

function CadoHTMLHeader($student_id, $grade_id,$last_mp) {

    $columns=array();
    $data=array();
    $SCHOOL_RET=DBGet(DBQuery('SELECT * from schools where ID = \''. UserSchool() . '\''));
    $USER_RET=DBGet(DBQuery('SELECT * from students where STUDENT_ID = \''. $student_id . '\''));
    $ADDRESS_RET=DBGet(DBQuery('SELECT * from student_address where STUDENT_ID = \''. $student_id . '\'  AND TYPE = \'PRIMARY\' '));
    $PRIMARY_RET=DBGet(DBQuery('SELECT * from students_join_people where STUDENT_ID = \''. $student_id . '\'  AND EMERGENCY_TYPE = \'PRIMARY\' '));
    $PRIMARYNAME_RET=DBGet(DBQuery('SELECT * from people where STAFF_ID = \''. $PRIMARY_RET[1]['PERSON_ID'] . '\''));
    $QUART_RET=DBGet(DBQuery('SELECT * from school_quarters WHERE SCHOOL_ID=\'' . UserSchool() . '\' AND SYEAR=\'' . UserSyear() . '\' AND MARKING_PERIOD_ID=\'' . $last_mp . '\''));  
    $data['STUDENT_ABSCENCES_QUARTER']= CadoAssiduiteQuarters($student_id, $grade_id);
    $column['SCHOOL_NAME']=_schoolName;
    $column['SCHOOL_CODE']=_schoolCode;
    $column['SCHOOL_PRINCIPAL']=_principal;
    $column['SCHOOL_ADDRESS']=_addresses;
    $column['SCHOOL_TEL']=_telephone;
    $column['SCHOOL_FAX']=_fax;
    $column['SCHOOL_EMAIL']=_email;
    $column['STUDENT_NAME']=_studentName;
    $column['STUDENT_PERM_ID']=_alternateId;
    $column['STUDENT_ID']=_studentId;
    $column['STUDENT_GRADE']=_studentGrade;
    $column['STUDENT_AGE']=_ageAu30Sept;
    $column['STUDENT_BIRTHDATE']=_birthdate;
    $column['STUDENT_ABSCENCES_QUARTER']=_dailyAbsencesThis . $QUART_RET[1]['TITLE'];
    $column['STUDENT_ABSCENCES_YEARLY']=_yearToDateDailyAbsences;
    $column['REPORT_OWNER']=_reportOwner;
    $column['REPORT_NAME']=_name;
    $column['REPORT_RELATION']=_relation;
    $column['REPORT_ADDRESS']=_addresses;
    $column['REPORT_HOME_PHONE']=_homePhoneNumber;
    $column['REPORT_WORK_PHONE']=_workPhone;
    $column['REPORT_CELL_PHONE']=_cellMobilePhone;
    $column['COMMUNICATION_QUARTER']=_report_quart;
    $column['COMMUNICATION_STAR_DATE']=_quart_start;
    $column['COMMUNICATION_END_DATE']=_quart_end;
    $data['SCHOOL_NAME']='Le Centre académique de l\'Outaouais';
    $data['SCHOOL_CODE']='602501';
    $data['SCHOOL_PRINCIPAL']=$SCHOOL_RET[1]['PRINCIPAL'];
    $data['SCHOOL_ADDRESS']=$SCHOOL_RET[1]['ADDRESS'];
    $data['SCHOOL_CITY']=$SCHOOL_RET[1]['CITY'];
    $data['SCHOOL_STATE']=$SCHOOL_RET[1]['STATE'];
    $data['SCHOOL_ZIPCODE']=$SCHOOL_RET[1]['ZIPCODE'];
    $data['SCHOOL_TEL']= $SCHOOL_RET[1]['AREA_CODE'] . '-'. $SCHOOL_RET[1]['PHONE'];
    $data['SCHOOL_FAX']= '819-893-2237';
    $data['SCHOOL_EMAIL']= $SCHOOL_RET[1]['E_MAIL'];
    $data['STUDENT_NAME']=$USER_RET[1]['FIRST_NAME'] .' '. $USER_RET[1]['LAST_NAME'];
    $data['STUDENT_PERM_ID']=$USER_RET[1]['ALT_ID'];
    $data['STUDENT_ID']=$student_id;
    $data['STUDENT_GRADE']=$grade_id;
    $data['STUDENT_BIRTHDATE']=$USER_RET[1]['BIRTHDATE'];
    $data['STUDENT_AGE']=_getage30sept($data['STUDENT_BIRTHDATE']);
    $data['REPORT_OWNER']=$PRIMARYNAME_RET[1]['FIRST_NAME'] .' '. $PRIMARYNAME_RET[1]['LAST_NAME'];
    $translate=array('Father' => _father,
    'Mother' => _mother,
    'Step Mother' => _mother,
    'Step Father' => _stepFather,
    'Step Mother' => _stepMother,
    'Grandmother' => _grandmother,
    'Grandfather' => _grandfather,
    'Legal Guardian' => _legalGuardian,
    'Other Family Member' => _otherFamilyMember,
    'Soeur' => 'Soeur',
    'Père' => 'Père',
    'Mère' => 'Mère'
    );
    $data['REPORT_RELATION']=$translate[$PRIMARY_RET[1]['RELATIONSHIP']];
    $data['REPORT_ADDRESS']=html_entity_decode($ADDRESS_RET[1]['STREET_ADDRESS_1']);
    $data['REPORT_CITY']=$ADDRESS_RET[1]['CITY'];
    $data['REPORT_STATE']=$ADDRESS_RET[1]['STATE'];
    $data['REPORT_ZIPCODE']=$ADDRESS_RET[1]['ZIPCODE'];
    $data['REPORT_HOME_PHONE']=$PRIMARYNAME_RET[1]['HOME_PHONE'];
    $data['REPORT_WORK_PHONE']=$PRIMARYNAME_RET[1]['WORK_PHONE'];
    $data['REPORT_CELL_PHONE']=$$PRIMARYNAME_RET[1]['CELL_PHONE'];
    $data['COMMUNICATION_QUARTER']=$QUART_RET[1]['TITLE'];
    $data['COMMUNICATION_STAR_DATE']=$QUART_RET[1]['START_DATE'];
    $data['COMMUNICATION_END_DATE']=$QUART_RET[1]['END_DATE'];
    if(strpos($grade_id,'Secondaire'))
        CadoHTMLHeaderSecondaire(_reportcard_cat1,$column,$data);
    else 
        if(strpos($grade_id,'Primaire'))
            CadoHTMLHeaderPrimaire(_reportcard_cat1,$column,$data);
    else
        if(strpos($grade_id,'Préscolaire'))
            CadoHTMLHeaderPrescolaire(_reportcard_cat1,$column,$data);

}

function CadoHTMLHeaderPrescolaire($title,$items,$data){
    $teacher_name=DBGet(DBQuery('select first_name,last_name from staff where staff_id=(select teacher_id from course_periods where title like "PRE 1%" and syear=\'' . UserSyear() . '\')'));
    $sch_img_info= DBGet(DBQuery('SELECT * FROM user_file_upload WHERE SCHOOL_ID='. UserSchool().' AND FILE_INFO=\'schlogo\''));
    echo '<h2 class="section-prescolaire-title"><span>1</span> RENSEIGNEMENTS GÉNÉRAUX</h2></pre>
    <table class="class-prescolaire_table">
    <tr>
        <td rowspan="2" colspan="4" class="bggrey">' . $data['STUDENT_NAME'] . '</td> 
        <td rowspan="3" colspan="4"> 
    ';
    echo "<img src='data:image/jpeg;base64,".base64_encode($sch_img_info[1]['CONTENT'])."' width='100' class='m-r-15 img-responsive' alt='Logo'/>";
    echo '
    </td> 
    </tr>
    <tr>
    </tr>
    <tr>
        <td colspan="4">' . $items['STUDENT_ID'] . ' : <b>' . $data['STUDENT_ID'] . '</b> &nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp' . $items['STUDENT_PERM_ID'] . ' : <b>' . $data['STUDENT_PERM_ID'] . '</td> 
    </tr>
    <tr>
        <td colspan="3">' . $items['STUDENT_BIRTHDATE'] . ' : <b>' . $data['STUDENT_BIRTHDATE'] . '</td> 
        <td>' . $items['STUDENT_AGE'] . ' : <b>' . $data['STUDENT_AGE'] . '</td> 
        <td rowspan="2" colspan="4" class="bggrey">' . $data['SCHOOL_NAME'] . '</td> 
    </tr>
    <tr>
        <td colspan="4" class="bggrey">Destinataire du bulletin </td> 
    </tr><tr>
        <td  colspan="4"">Relation du destinataire: <b>' . $data['REPORT_RELATION'] . '</b></td> 
        <td colspan="4">' . $items['SCHOOL_ADDRESS'] . ' : <b> ' . $data['SCHOOL_ADDRESS'] . ' , ' . $data['SCHOOL_CITY'] . ', ' . $data['SCHOOL_STATE'] . ' , '. $data['SCHOOL_ZIPCODE'] . '</td> 
    </tr>
    <tr>
        <td  colspan="4">' . $items['REPORT_NAME'] . ' : <b>' . $data['REPORT_OWNER'] . '</td> 
        <td colspan="3">' . $items['SCHOOL_TEL'] . ' : &nbsp&nbsp<b>' . $data['SCHOOL_TEL'] . '</b>  ' . $items['SCHOOL_FAX'] . ' : <b>' . $data['SCHOOL_FAX'] . '</td> 
        <td>' . $items['SCHOOL_CODE'] . ' : <b>' . $data['SCHOOL_CODE'] . '</td> 
    </tr>
    <tr>
        <td colspan="4">' . $items['REPORT_ADDRESS'] . ' : <b>' . $data['REPORT_ADDRESS'] . ' , ' . $data['REPORT_CITY'] . ', ' . $data['REPORT_STATE'] . ' , ' . $data['REPORT_ZIPCODE'] . '</td> 
        <td colspan="4">' . $items['SCHOOL_EMAIL'] . ' : <b>' . $data['SCHOOL_EMAIL'] . '</td> 
    </tr>
    <tr>
        <td colspan="3">' . $items['REPORT_HOME_PHONE'] . ' : <b>' . $data['REPORT_HOME_PHONE'] . '</td> 
        <td>' . $items['REPORT_WORK_PHONE'] . ' : <b>' . $data['REPORT_WORK_PHONE'] . '</td> 
        <td rowspan="2" colspan="4" class="bggrey">' . $items['SCHOOL_PRINCIPAL'] . ' : <b>' . $data['SCHOOL_PRINCIPAL'] . '</td> 
    </tr>
    <tr>
        <td colspan="4">' . $items['REPORT_CELL_PHONE'] . ' : <b>' . $data['REPORT_CELL_PHONE'] . '</td> 
    </tr>
    <tr>
        <td  rowspan="2" colspan="4"></td> 
        <td colspan="4">' . _signature . ': <b class="signature-ts">Danielle Grant</b></td> 
    </tr>
    <tr>
        <td colspan="4">Enseignant(e) : <b>' . $teacher_name[1]['FIRST_NAME'] . ' ' . $teacher_name[1]['LAST_NAME'] . '</b></td> 
    </tr>
    <tr>
        <td rowspan="2" class="bggrey">Étape de communication : ' . $data['COMMUNICATION_QUARTER'] . '</td>
        <td  colspan="3">' . $items['COMMUNICATION_STAR_DATE'] . ' <b>: ' . $data['COMMUNICATION_STAR_DATE'] . '</td>
        <td colspan="4" class=bggrey>ASSIDUITÉ</td>
    </tr>
    <tr>
        <td colspan="3">' . $items['COMMUNICATION_END_DATE'] . ' <b>: ' . $data['COMMUNICATION_END_DATE'] . '</td>
        <td class="bgetapes">Étape</td>
        <td class="bgetapes">1</td>
        <td class="bgetapes">2</td>
        <td class="bgetapes">3</td>
    </tr>
    <tr>
        <td colspan="4"></td>
        <td>Jours d’absence</td>
        <td>' . $data['STUDENT_ABSCENCES_QUARTER'][1][1][1] .'</td>
        <td>' . $data['STUDENT_ABSCENCES_QUARTER'][1][2][2] .'</td>
        <td>' . $data['STUDENT_ABSCENCES_QUARTER'][1][3][3] .'</td>
    </tr>
        <tr>
        <td colspan="4"></td>
        <td>Jours de classe</td>
        <td>' . $data['STUDENT_ABSCENCES_QUARTER'][1][1]['MAXDAYS_QUARTER'] .'</td>
        <td>' . $data['STUDENT_ABSCENCES_QUARTER'][1][2]['MAXDAYS_QUARTER'] .'</td>
        <td>' . $data['STUDENT_ABSCENCES_QUARTER'][1][3]['MAXDAYS_QUARTER'] .'</td>
    </tr>
    </table>
    '; 
    echo'<div class="bggrey border"><b>Réservé à l’administration</b><div  class="bggrey">&nbsp<div  class="bggrey">&nbsp</div></div></div>';
}

function CadoHTMLHeaderPrimaire($title,$items,$data){

    echo '<pre class="section-title">'; echo $title; echo'</pre>';    
    echo '<table class="section-1">
    <tr>
       <td class="section-1-block">
        <div class="section-1-item">' . $items['SCHOOL_NAME'] . ' : <b>' . $data['SCHOOL_NAME'] . '</b></div>
        <div class="section-1-item">' . $items['SCHOOL_CODE'] . ' : <b>' . $data['SCHOOL_CODE'] . '</b></div>
        <div class="section-1-item">' . $items['SCHOOL_PRINCIPAL'] . ' : <b>' . $data['SCHOOL_PRINCIPAL'] . '</b></div>
        <div class="section-1-item">' . _signature . ': <b class="signature-ts">Danielle Grant</b></div>
        </div></td> 

       <td class="section-1-block">
        <div class="section-1-item">' . $items['SCHOOL_ADDRESS'] . ' : <b>' . $data['SCHOOL_ADDRESS'] . '</b></div>
        <div class="section-1-item">&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp<b>' . $data['SCHOOL_CITY'] . ', ' . $data['SCHOOL_STATE'] . '</b></div>
        <div class="section-1-item">&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp<b>'. $data['SCHOOL_ZIPCODE'] . '</b></div>
        <div class="section-1-item">' . $items['SCHOOL_TEL'] . ' : &nbsp&nbsp<b>' . $data['SCHOOL_TEL'] . '</b></div>                
        <div class="section-1-item">' . $items['SCHOOL_FAX'] . ' : <b>' . $data['SCHOOL_FAX'] . '</b></div>                
        </div>
       </td>
     </tr> 

     <tr>
       <td class="section-1-block"> 
        <div class="section-1-item">' . $items['STUDENT_NAME'] . ' : <b>' . $data['STUDENT_NAME'] . '</b></div>
        <div class="section-1-item">' . $items['STUDENT_PERM_ID'] . ' : <b>' . $data['STUDENT_PERM_ID'] . '</b></div>
        <div class="section-1-item">' . $items['STUDENT_BIRTHDATE'] . ' : <b>' . $data['STUDENT_BIRTHDATE'] . '</b></div>
        <div class="section-1-item">' . $items['STUDENT_AGE'] . ' : <b>' . $data['STUDENT_AGE'] . '</b></div>
        <div class="section-1-item">' . $items['STUDENT_ID'] . ' : <b>' . $data['STUDENT_ID'] . '</b></div>
        <div class="section-1-item">' . $items['STUDENT_GRADE'] . ' : <b>' . $data['STUDENT_GRADE'] . '</b></div>
       </td>
       <td class="section-1-block">
        <div class="section-1-item">' . $items['REPORT_OWNER'] . ' : <b>' . $data['REPORT_RELATION'] . '</b></div>
        <div class="section-1-item">' . $items['REPORT_NAME'] . ' : <b>' . $data['REPORT_OWNER'] . '</b></div>
        <div class="section-1-item">' . $items['REPORT_ADDRESS'] . ' : <b>' . $data['REPORT_ADDRESS'] . '</b></div>
        <div class="section-1-item">&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp<b>' . $data['REPORT_CITY'] . ', ' . $data['REPORT_STATE'] . '</b></div>
        <div class="section-1-item">&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp<b>' . $data['REPORT_ZIPCODE'] . '</b></div>
        <div class="section-1-item">' . $items['REPORT_HOME_PHONE'] . ' : <b>' . $data['REPORT_HOME_PHONE'] . '</b></div>
        <div class="section-1-item">' . $items['REPORT_WORK_PHONE'] . ' : <b>' . $data['REPORT_WORK_PHONE'] . '</b></div>
        <div class="section-1-item">' . $items['REPORT_CELL_PHONE'] . ' : <b>' . $data['REPORT_CELL_PHONE'] . '</b></div>
       </td>
    </tr>
    <tr>   
        <td class="section-1-block">
        <div class="section-1-item">' . $items['COMMUNICATION_QUARTER'] . ' : <b>' . $data['COMMUNICATION_QUARTER'] . '</b></div>
        <div class="section-1-item">' . $items['COMMUNICATION_STAR_DATE'] . ' <b>: ' . $data['COMMUNICATION_STAR_DATE'] . '</b></div>
        <div class="section-1-item">' . $items['COMMUNICATION_END_DATE'] . ' <b>: ' . $data['COMMUNICATION_END_DATE'] . '</b></div>

        </td>
        <td class="section-1-block">';

        echo'
        <table class="class-assiduite_table">
          <tr>
            <th colspan="9" class="class-assiduite__top_col">Assiduité</th>
          </tr>
          <tr>
            <th class="class-assiduite__first_col">&#32</th>
            <th colspan="3" class="class-assiduite__first_col">Année 1</th>
            <th colspan="3" class="class-assiduite__last_col">Année 2</th>
          </tr
           <tr>
            <th class="class-assiduite__col">Étape</th>
            <th class="class-assiduite__item1">1</th>
            <th class="class-assiduite__item1">2</th>
            <th class="class-assiduite__item1">3</th>
            <th class="class-assiduite__item1">1</th>
            <th class="class-assiduite__item1">2</th>
            <th class="class-assiduite__last_item1">3</th>
          </tr>
         <tr>
            <th class="class-assiduite__col" >Jours d’absence</th>
            <th class="class-assiduite__item2">' . $data['STUDENT_ABSCENCES_QUARTER'][0][1][1] .'</th>
            <th class="class-assiduite__item2">' . $data['STUDENT_ABSCENCES_QUARTER'][0][2][2] .'</th>
            <th class="class-assiduite__item2">' . $data['STUDENT_ABSCENCES_QUARTER'][0][3][3] .'</th>
            <th class="class-assiduite__item2">' . $data['STUDENT_ABSCENCES_QUARTER'][1][1][1] .'</th>
            <th class="class-assiduite__item2">' . $data['STUDENT_ABSCENCES_QUARTER'][1][2][2] .'</th>
            <th class="class-assiduite__last_item2">' . $data['STUDENT_ABSCENCES_QUARTER'][1][3][3] .'</th>
          </tr>
          <tr>
            <th class="class-assiduite__last_row_item">Jours de classe</th>
            <th class="class-assiduite__last_row">' . $data['STUDENT_ABSCENCES_QUARTER'][0][1]['MAXDAYS_QUARTER'] .'</th>
            <th class="class-assiduite__last_row">' . $data['STUDENT_ABSCENCES_QUARTER'][0][2]['MAXDAYS_QUARTER'] .'</th>
            <th class="class-assiduite__last_row">' . $data['STUDENT_ABSCENCES_QUARTER'][0][3]['MAXDAYS_QUARTER'] .'</th>
            <th class="class-assiduite__last_row">' . $data['STUDENT_ABSCENCES_QUARTER'][1][1]['MAXDAYS_QUARTER'] .'</th>
            <th class="class-assiduite__last_row">' . $data['STUDENT_ABSCENCES_QUARTER'][1][2]['MAXDAYS_QUARTER'] .'</th>
            <th class="class-assiduite__last_row_item_last">' . $data['STUDENT_ABSCENCES_QUARTER'][1][3]['MAXDAYS_QUARTER'] .'</th>
          </tr>
        </table>
                ';
    
    
        echo '</td>
    </tr>
    </table>';
}

function CadoHTMLHeaderSecondaire($title,$items,$data){

    echo '<pre class="section-title">'; echo $title; echo'</pre>';    
    echo '<table class="section-1">
    <tr>
        <td class="section-1-block">
        <div class="section-1-item">' . $items['SCHOOL_NAME'] . ' : <b>' . $data['SCHOOL_NAME'] . '</b></div>
        <div class="section-1-item">' . $items['SCHOOL_CODE'] . ' : <b>' . $data['SCHOOL_CODE'] . '</b></div>
        <div class="section-1-item">' . $items['SCHOOL_ADDRESS'] . ' : <b>' . $data['SCHOOL_ADDRESS'] . '</b></div>
        <div class="section-1-item">&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp<b>' . $data['SCHOOL_CITY'] . ', ' . $data['SCHOOL_STATE'] . '</b></div>
        <div class="section-1-item">&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp<b>'. $data['SCHOOL_ZIPCODE'] . '</b></div>
        <div class="section-1-item">' . $items['SCHOOL_TEL'] . ' : &nbsp&nbsp<b>' . $data['SCHOOL_TEL'] . '</b></div>                
        <div class="section-1-item">' . $items['SCHOOL_FAX'] . ' : <b>' . $data['SCHOOL_FAX'] . '</b></div>                
        <div class="section-1-item">' . $items['SCHOOL_PRINCIPAL'] . ' : <b>' . $data['SCHOOL_PRINCIPAL'] . '</b></div>
        <div class="section-1-item">' . _signature . ': <b class="signature-ts">Danielle Grant</b></div>

        </div></td>

        <td class="section-1-block">
        <div class="section-1-item">' . $items['COMMUNICATION_QUARTER'] . ' : <b>' . $data['COMMUNICATION_QUARTER'] . '</b></div>
        <div class="section-1-item">' . $items['COMMUNICATION_STAR_DATE'] . ' <b>: ' . $data['COMMUNICATION_STAR_DATE'] . '</b></div>
        <div class="section-1-item">' . $items['COMMUNICATION_END_DATE'] . ' <b>: ' . $data['COMMUNICATION_END_DATE'] . '</b></div>
        <div class="section-1-item">&nbsp</div>
        <div class="section-1-item">&nbsp</div>
        </td>
    </tr>
    <tr>
       <td class="section-1-block"> 
        <div class="section-1-item">' . $items['STUDENT_NAME'] . ' : <b>' . $data['STUDENT_NAME'] . '</b></div>
        <div class="section-1-item">' . $items['STUDENT_PERM_ID'] . ' : <b>' . $data['STUDENT_PERM_ID'] . '</b></div>
        <div class="section-1-item">' . $items['STUDENT_BIRTHDATE'] . ' : <b>' . $data['STUDENT_BIRTHDATE'] . '</b></div>
        <div class="section-1-item">' . $items['STUDENT_AGE'] . ' : <b>' . $data['STUDENT_AGE'] . '</b></div>
        <div class="section-1-item">' . $items['STUDENT_ID'] . ' : <b>' . $data['STUDENT_ID'] . '</b></div>
        <div class="section-1-item">' . $items['STUDENT_GRADE'] . ' : <b>' . $data['STUDENT_GRADE'] . '</b></div>
        </td>
        <td class="section-1-block">
        <div class="section-1-item">' . $items['REPORT_OWNER'] . ' : <b>' . $data['REPORT_RELATION'] . '</b></div>
        <div class="section-1-item">' . $items['REPORT_NAME'] . ' : <b>' . $data['REPORT_OWNER'] . '</b></div>
            <div class="section-1-item">' . $items['REPORT_ADDRESS'] . ' : <b>' . $data['REPORT_ADDRESS'] . '</b></div>
        <div class="section-1-item">&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp<b>' . $data['REPORT_CITY'] . ', ' . $data['REPORT_STATE'] . '</b></div>
        <div class="section-1-item">&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp<b>' . $data['REPORT_ZIPCODE'] . '</b></div>
        <div class="section-1-item">' . $items['REPORT_HOME_PHONE'] . ' : <b>' . $data['REPORT_HOME_PHONE'] . '</b></div>
        <div class="section-1-item">' . $items['REPORT_WORK_PHONE'] . ' : <b>' . $data['REPORT_WORK_PHONE'] . '</b></div>
        <div class="section-1-item">' . $items['REPORT_CELL_PHONE'] . ' : <b>' . $data['REPORT_CELL_PHONE'] . '</b></div>
        </td>
        </tr>
    </table>';
}

function CadoHTMLresultatsPrimaire($title,$course,$quarts,$results,$comments,$result_diff,$year,$grade_id,$exam_value,$student_id){
    global $publish_parents;
    global $publish_parents_grade;

    $publish_parents_grade='Primaire';
    $numquart=count($quarts[0])-2;
    $markingPeriod = DBGet(DBQuery('SELECT * FROM school_quarters WHERE SYEAR=\'' . UserSyear() . '\' AND SCHOOL_ID=\'' . UserSchool() . '\' AND SORT_ORDER=255 '));
    if(strpos($grade_id,"Primaire")){
        $year1='Année 1';
        $year2='Année 2';
        $abscences=false;
    }
    else{
        $year1='1re secondaire';
        $year2='2e secondaire';       
        $abscences=true;
    }
    if($markingPeriod[1]['SORT_ORDER'] == 255) $numquart--;
    $colspan=$numquart+1;
    $commentspan=$colspan*2+1;
    $SCHOOL_GRADELEVELS=DBGet(DBQuery('SELECT * from schools where ID = \''. UserSchool() . '\''));
    $courseloop=0;
    $course[$year][$courseloop]['STUDENT_GRADE']='Primaire 2';
    if(strpos($grade_id,"1") || strpos($grade_id,"2")){
            $cycle='Cycle 1';
            if (strpos($grade_id , "1")) {
                $row1=$results[$year];
                $diffyear1=$result_diff[$year];
            }
            else{
                $row1=$results[$year-1];
                $row2=$results[$year];
                $diffyear1=$result_diff[$year-1];
                $diffyear2=$result_diff[$year];
            }
    }
    else
    if(strpos($grade_id,"3") || strpos($grade_id,"4")){
        $cycle='Cycle 2';
        if (strpos($grade_id , "3")) {
            $row1=$results[$year];
            $diffyear1=$result_diff[$year];
        }
        else{
            $row1=$results[$year-1];
            $row2=$results[$year];
            $diffyear1=$result_diff[$year-1];
            $diffyear2=$result_diff[$year];
            $right=1;
        }
    }
    else 
    if(strpos($grade_id,"5") || strpos($grade_id,"6")){
            $cycle='Cycle 3';
            if (strpos($grade_id , "5")) {
                $row1=$results[$year];
            }
            else{
                $row1=$results[$year-1];
                $row2=$results[$year];
                $diffyear1=$result_diff[$year-1];
                $diffyear2=$result_diff[$year];
                $right=1;
            }
    }
    echo '<pre class="section-title">'; echo $title; echo'</pre>';    
    foreach ($course[$year] as $key=> $col) {
        if(strpos($grade_id,"Primaire"))
            $course[$year][$courseloop]['COURSE_#']='';
        echo'<table class="class-results__table"><tr><th rowspan="3" class="class-results--align-left class-results__th--left-header">
            <h1>' . $course[$year][$courseloop]['TITLE']  . '</h1>
            ' . $course[$year][$courseloop]['COURSE_#']  . ' <br />'; 
        echo $course[$year][$courseloop]['TEACHER'];
        echo ' </th>
        <th colspan="' . $colspan *2  . '" class="class-results__3col-right">' . $cycle  . '</th></tr><tr>
        <th colspan="' . $colspan  . '" class="class-results__3col-right">' . $year1 .'</th>
        <th colspan="' . $colspan  . '" class="class-results__3col-right">' . $year2 . '</th></tr><tr>';
        for($quartloop=0; $quartloop < $numquart ; $quartloop++){
            echo'<th class="class-results__3col__th">' . $quarts[$courseloop][$quartloop+1]  .'</th>';
        }
        echo'<th class="class-results__3col__th">' . $quarts[$courseloop]['FINAL']  .'</th>';
        for($quartloop=0; $quartloop < $numquart ; $quartloop++){
            echo'<th class="class-results__3col__th">' . $quarts[$courseloop][$quartloop+1]  .'</th>';
        }
        echo'<th class="class-results__3col__th">' . $quarts[$courseloop]['FINAL']  .'</th></tr>';
        $resloop=0;
        foreach ($results[$year][$courseloop] as $key=> $result){
            echo '<tr><td class="class-results--align-right">' . $results[$year][$courseloop][$resloop]['TYPE']  .'</td>';
            for($quartloop=0; $quartloop < $numquart ; $quartloop++){
                echo'
                <td class="class-results--align-center">' . $row1[$courseloop][$resloop]['RESULT'][$quartloop]  .'</td>
                ';
            }
            echo'<td class="class-results--align-center">' . $row1[$courseloop][$resloop]['RESULT']['FINAL']  .'</td>';
            for($quartloop=0; $quartloop < $numquart ; $quartloop++){
                echo'
                <td class="class-results--align-center">' . $row2[$courseloop][$resloop]['RESULT'][$quartloop]  .'</td>
                ';
            }
            echo'
                <td class="class-results--align-center">' . $row2[$courseloop][$resloop]['RESULT']['FINAL']  .'</td>
                </tr>';
                $resloop++;
        }
        if(! $publish_parents){
                if($diffyear1[$courseloop][0]['RESULTDIFF'] || $diffyear1[$courseloop][1]['RESULTDIFF'] || $diffyear1[$courseloop][2]['RESULTDIFF'] || $diffyear2[$courseloop][0]['RESULTDIFF'] || $diffyear2[$courseloop][1]['RESULTDIFF'] || $diffyear2[$courseloop][2]['RESULTDIFF']){
                    echo '<td></td>';
                    if($diffyear1[$courseloop][0]['RESULTDIFF'])
                        echo '<td class="class-results--align-center  highligth">' . $diffyear1[$courseloop][0]['RESULTDIFF'] .'</td>';
                    else 
                        echo '<td></td>';
                    if($diffyear1[$courseloop][1]['RESULTDIFF'])
                        echo '<td class="class-results--align-center highligth">' . $diffyear1[$courseloop][1]['RESULTDIFF'] .'</td>';
                    else 
                        echo '<td></td>';
                    if($diffyear1[$courseloop][2]['RESULTDIFF'])
                        echo '<td class="class-results--align-center highligth">' . $diffyear1[$courseloop][2]['RESULTDIFF'] .'</td>';
                    else 
                        echo '<td></td>'; 
                    echo '<td></td>';  
                    if($diffyear2[$courseloop][0]['RESULTDIFF'])
                        echo '<td class="class-results--align-center  highligth">' . $diffyear2[$courseloop][0]['RESULTDIFF'] .'</td>';
                    else 
                        echo '<td></td>';
                    if($diffyear2[$courseloop][1]['RESULTDIFF'])
                        echo '<td class="class-results--align-center highligth">' . $diffyear2[$courseloop][1]['RESULTDIFF'] .'</td>';
                    else 
                        echo '<td></td>';
                    if($diffyear2[$courseloop][2]['RESULTDIFF'])
                        echo '<td class="class-results--align-center highligth">' . $diffyear2[$courseloop][2]['RESULTDIFF'] .'</td>';
                    else 
                        echo '<td></td>';   
                    echo '<td></td>';             
                    if($exam_value[$courseloop]){
                        echo '<tr><td class="class-results--align-center highligth">Examen final = '. $exam_value[$courseloop] . '</td></tr>';
                    }
                }

        }
        echo '<tr><tr></tr>';
        if($abscences){
            if(strpos($grade_id,"Primaire"))
                $data['STUDENT_ABSCENCES_QUARTER']=CadoAssiduiteQuarters($student_id,$grade_id);
            else
                $data['STUDENT_ABSCENCES_QUARTER']=CadoAssiduitePeriodsCycle($student_id,$grade_id,$course,$courseloop);
            echo '
            </tr><td class="class-results--align-right"">Unités</td>
            <td colspan=1 style="background-color:grey"></td><td colspan=1 style="background-color:grey"></td>
            <td colspan=1 style="background-color:grey"></td><td colspan=1 class="class-results--align-center">  </td>
            <td colspan=1 style="background-color:grey"></td><td colspan=1 style="background-color:grey"></td>
            <td colspan=1 style="background-color:grey"></td><td colspan=1 class="class-results--align-center">  </td>
            
            ';     
            echo '
            </tr><td class="class-results--align-right"">Absences / Jours de classe</td>
            <td colspan=1 class="center">' . $data['STUDENT_ABSCENCES_QUARTER'][0][1][1] .' / ' . $data['STUDENT_ABSCENCES_QUARTER'][0][1]['MAXDAYS_QUARTER'] .'</td><td colspan=1 class="center">' . $data['STUDENT_ABSCENCES_QUARTER'][0][2][2] .' / ' . $data['STUDENT_ABSCENCES_QUARTER'][0][2]['MAXDAYS_QUARTER'] .'</td>
            <td colspan=1 class="center">' . $data['STUDENT_ABSCENCES_QUARTER'][0][3][3] .' / ' . $data['STUDENT_ABSCENCES_QUARTER'][0][3]['MAXDAYS_QUARTER'] .'</td><td colspan=1 class="class-results--align-center">
            ' . $data['STUDENT_ABSCENCES_QUARTER'][0][1][1]+$data['STUDENT_ABSCENCES_QUARTER'][0][2][2]+$data['STUDENT_ABSCENCES_QUARTER'][0][3][3] .' / ' . $data['STUDENT_ABSCENCES_QUARTER'][0][1]['MAXDAYS_QUARTER']+$data['STUDENT_ABSCENCES_QUARTER'][0][2]['MAXDAYS_QUARTER']+$data['STUDENT_ABSCENCES_QUARTER'][0][3]['MAXDAYS_QUARTER'] .'
            </td>
            <td colspan=1 class="center">' . $data['STUDENT_ABSCENCES_QUARTER'][1][1][1] .' / ' . $data['STUDENT_ABSCENCES_QUARTER'][1][1]['MAXDAYS_QUARTER'] .'</td><td colspan=1 class="center">' . $data['STUDENT_ABSCENCES_QUARTER'][1][2][2] .' / ' . $data['STUDENT_ABSCENCES_QUARTER'][1][2]['MAXDAYS_QUARTER'] .'</td>
            <td colspan=1 class="center">' . $data['STUDENT_ABSCENCES_QUARTER'][1][3][3] .' / ' . $data['STUDENT_ABSCENCES_QUARTER'][1][3]['MAXDAYS_QUARTER'] .'</td><td colspan=1 class="class-results--align-center">
            ' . $data['STUDENT_ABSCENCES_QUARTER'][1][1][1]+$data['STUDENT_ABSCENCES_QUARTER'][1][2][2]+$data['STUDENT_ABSCENCES_QUARTER'][1][3][3] .' / ' . $data['STUDENT_ABSCENCES_QUARTER'][1][1]['MAXDAYS_QUARTER']+$data['STUDENT_ABSCENCES_QUARTER'][1][2]['MAXDAYS_QUARTER']+$data['STUDENT_ABSCENCES_QUARTER'][1][3]['MAXDAYS_QUARTER'] .'
            </td>
            ';     

        }
        if ($_REQUEST['elements']['comments'] == 'Y') {
            echo '<tr><td colspan="' . $commentspan . '">' . $comments[$courseloop]['COMMENT_TITLE'] . ': <b><i>' . $comments[$courseloop]['COMMENT'] . '</i></b></td></tr>';
    }
    echo '</table>';
    $courseloop++;
    }
}

function CadoHTMLresultatsSecondaire($title,$course,$quarts,$results,$comments,$result_diff,$exam_value,$student_id){
    global $publish_parents;
    global $publish_parents_grade;

    $publish_parents_grade='Secondaire';

    $numquart=count($quarts[0])-2;
    $colspan=$numquart+1;
    $commentspan=$colspan+1;
    $markingPeriod = DBGet(DBQuery('SELECT * FROM school_quarters WHERE SYEAR=\'' . UserSyear() . '\' AND SCHOOL_ID=\'' . UserSchool() . '\' AND SORT_ORDER=255 '));
    if($markingPeriod[1]['SORT_ORDER'] == 255) $numquart--;
    echo '<pre class="section-title">'; echo $title; echo'</pre>';    
    $courseloop=0;

    foreach ($course as $key=> $col) {
        $SCHED_RET=DBGet(DBQuery('SELECT * from schedule WHERE SCHOOL_ID=\'' . UserSchool() . '\' AND student_id=\'' . $student_id . '\'  AND SYEAR=\'' . UserSyear() . '\' AND COURSE_PERIOD_ID=\'' . $course[$courseloop]['COURSE_PERIOD_ID'] . '\''));  
        if(html_entity_decode($comments[$courseloop]['COMMENT']) != html_entity_decode('Cours abandonné.') && count($SCHED_RET) && $SCHED_RET[1]['DROPPED'] == 'N')
        {
            echo'
            <table class="class-results__table">
            <tr>
                <th
                rowspan="2"
                class="class-results--align-left class-results__th--left-header"
                >
                <h1>' . $course[$courseloop]['TITLE']  . '</h1>
                ' . $course[$courseloop]['COURSE_#']  . ' <br />
                '; 
                if ($_REQUEST['elements']['teacher'] == 'Y') {
                    echo $course[$courseloop]['TEACHER'];
                } 
                echo ' </th>
                <th colspan="' . $colspan  . '" class="class-results__3col-right">' . $course[$courseloop]['STUDENT_GRADE']  . '</th>
            </tr>
            <tr>';
            for($quartloop=0; $quartloop < $numquart ; $quartloop++){
                if($quarts[$courseloop][$quartloop+1] == $markingPeriod[1][TITLE]) $quartloop++;
                echo'<th class="class-results__3col__th">' . $quarts[$courseloop][$quartloop+1]  .'</th>';
            }
            echo'
                <th class="class-results__3col__th">' . $quarts[$courseloop]['FINAL']  .'</th>
            </tr>
            ';
            $resloop=0;
            foreach ($results[$courseloop] as $key=> $result){
                echo '<tr>
                <td class="class-results--align-right">' . $results[$courseloop][$resloop]['TYPE'] . ' ' . $results[$courseloop][$resloop]['WEIGHT'] . '</td> 
                ';
                for($quartloop=0; $quartloop < $numquart ; $quartloop++){
                    echo'<td class="class-results--align-center">' . $results[$courseloop][$resloop]['RESULT'][$quartloop]  .'</td>';
                    }
                echo'
                    <td class="class-results--align-center">' . $results[$courseloop][$resloop]['RESULT']['FINAL'] .'</td>
                    </tr>';
                    $resloop++;
            }
            if(! $publish_parents){
                if($result_diff[$courseloop][0]['RESULTDIFF'] || $result_diff[$courseloop][1]['RESULTDIFF'] || $result_diff[$courseloop][2]['RESULTDIFF']){
                echo '<td></td>';
                if($result_diff[$courseloop][0]['RESULTDIFF']){
                echo '<td class="class-results--align-center  highligth">' . $result_diff[$courseloop][0]['RESULTDIFF'] .'</td>';
                }
                else echo '<td></td>';
                if($result_diff[$courseloop][1]['RESULTDIFF']){
                echo '<td class="class-results--align-center highligth">' . $result_diff[$courseloop][1]['RESULTDIFF'] .'</td>';
                }
                else echo '<td></td>';
                if($result_diff[$courseloop][2]['RESULTDIFF']){
                echo '<td class="class-results--align-center highligth">' . $result_diff[$courseloop][2]['RESULTDIFF'] .'</td>';
                }  
                else echo '<td></td>';   
                echo '<td></td>';
            }   
            if($exam_value[$courseloop]){
                echo '<tr><td class="class-results--align-center highligth">Examen final = '. $exam_value[$courseloop] . '</td></tr>';
            }
        }
        echo '  
        <tr>
        <tr>
        </tr>';
        echo '
        </tr><td class="class-results--align-right"">Unités</td>
        <td colspan=1 style="background-color:grey"></td><td colspan=1 style="background-color:grey"></td>
        <td colspan=1 style="background-color:grey"></td><td colspan=1 class="class-results--align-center"> </td>
        '; 
        $data['STUDENT_ABSCENCES_QUARTER']=CadoAssiduitePeriods($student_id,$grade_id,$col['COURSE_PERIOD_ID']);
        echo '
        </tr><td class="class-results--align-right"">Absences / Jours de classe</td>
        <td colspan=1 class="center">' . $data['STUDENT_ABSCENCES_QUARTER'][1][1][1] .' / ' . $data['STUDENT_ABSCENCES_QUARTER'][1][1]['MAXDAYS_QUARTER'] .'</td><td colspan=1 class="center">' . $data['STUDENT_ABSCENCES_QUARTER'][1][2][2] .' / ' . $data['STUDENT_ABSCENCES_QUARTER'][1][2]['MAXDAYS_QUARTER'] .'</td>
        <td colspan=1 class="center">' . $data['STUDENT_ABSCENCES_QUARTER'][1][3][3] .' / ' . $data['STUDENT_ABSCENCES_QUARTER'][1][3]['MAXDAYS_QUARTER'] .'</td><td colspan=1 class="class-results--align-center">
        ' . $data['STUDENT_ABSCENCES_QUARTER'][1][1][1]+$data['STUDENT_ABSCENCES_QUARTER'][1][2][2]+$data['STUDENT_ABSCENCES_QUARTER'][1][3][3] .' / ' . $data['STUDENT_ABSCENCES_QUARTER'][1][1]['MAXDAYS_QUARTER']+$data['STUDENT_ABSCENCES_QUARTER'][1][2]['MAXDAYS_QUARTER']+$data['STUDENT_ABSCENCES_QUARTER'][1][3]['MAXDAYS_QUARTER'] .'
        </td>
        ';     
        if ($_REQUEST['elements']['comments'] == 'Y') {
        echo '<tr>
            <td colspan="' . $commentspan . '">' . $comments[$courseloop]['COMMENT_TITLE'] . ': <b><i>' . $comments[$courseloop]['COMMENT'] . '</i></b></td>
        </tr>';
        }
    }
    echo '</table>';
    $courseloop++;
    }
}

function CadoHTMLcommentairesCompetence($title,$data,$grade_id){

    //print_r($data);
    $cycle=false;
    if(strpos($grade_id,"Secondaire 1") || strpos($grade_id,"Secondaire 2")){
        $etape1='1re secondaire';
        $etape2='2e secondaire';
        $cycle=true;
    }
    if(strpos($grade_id,"Primaire")){
        $etape1='Année 1';
        $etape2='Année 2';
        $cycle=true;
    }
    echo '<pre class="section-title">'; echo $title; echo'</pre>';    
    if($cycle){
        echo '
        <table class="class-results__table print-friendly" class-results--align-center">
        <tr>
        <td colspan="7" class=class-results--align-center>Commentaires sur deux des quatre compétences suivantes : exercer son jugement critique, organiser son travail, savoir communiquer et travailler en équipe</td>
        </tr><tr>
        <td colspan="3"> </td> 
        <th colspan="3">Étape 1</th> 
        <th colspan="3">Étape 3</th> 
        </tr><tr>
        </tr><tr>
        <th colspan="3"> ' . $etape1 . ' </th> 
        <td colspan="3"><b> ' . $data["C1_E1"] .  '</b> </td> 
        <td colspan="3"><b>  ' . $data["C1_E3"] .  '</b> </td> 
        </tr><tr>
        <th colspan="3"> ' . $etape2 . ' </th> 
        <td colspan="3"><b>  ' . $data["C2_E1"] .  '</b> </td> 
        <td colspan="3"><b>  ' . $data["C2_E3"] .  '</b> </td> 
        </tr>
        </table>
        ';
    }
    else{
        echo '
        <table class="class-results__table" class-results--align-center">
        <tr>
        <td colspan="7" class=class-results--align-center>Commentaires sur deux des quatre compétences suivantes : exercer son jugement critique, organiser son travail, savoir communiquer et travailler en équipe</td>
        </tr><tr>
        <th colspan="2">Étape 1</th> 
        <th colspan="2">Étape 3</th> 
        </tr><tr>
        </tr><tr>
        <td colspan="2"><b> ' . $data["C2_E1"] .  '</b></td> 
        <td colspan="2"><b> ' . $data["C2_E3"] .  '</b></td> 
        </tr>
        </table>
        ';
    
    }
}

function CadoHTMLcommentairesPrescolaire($title,$data,$grade_id){

    echo'<h2 class="section-prescolaire-title"><span>3</span> Autres commentaires</h2>';
    echo '
    <table class="class-results__table" class-results--align-center">
    <tr>
    <td colspan="7" class=class-results--align-center>Commentaires divers, notamment sur d’autres apprentissages prévus dans les projets de l’école ou de la classe</td>
    </tr><tr>
    <td><b>&nbsp' . $data["C2_E1"] .  '</b></td> 
    </tr>
    </table>
    ';
    
}

function CadoHTMLcommentairesGeneral($title,$items,$data){

    echo '<pre class="section-title">'; echo $title; echo'</pre>';    
    echo '<table class="section-1  print-friendly">
    <tr>
        <td class="section-2-header"> ' . $items['COMMENTAIRE'] . '</td>
    </tr>
    <td>
    <div class="section-2-item"><b>' . $data['COMMENTAIRE'] . '&nbsp</b></div>
    </td>
    </table>';
}

function CadoHTMLcommentairesCheminement($title,$items,$data,$mp,$grade_id){

    if(GetMP(UserMP()) != 'Étape 3')
        return;
    $date = date("d-m-Y ");
    if(strpos($grade_id,"Préscolaire")){
        echo'<h2 class="section-prescolaire-title"><span>4</span> Cheminement scolaire</h2>';
        echo '<table class="section-1  print-friendly">
        <tr>
        <td class="section-2-header"> ' . $items['COMMENTAIRE'] . '</td>
        </tr><td>
        <div><input type="checkbox" onclick="return false"' . $data["1"] . '>L’élève poursuivra ses apprentissages à l’éducation préscolaire, car il n’aura pas atteint l’âge de 6 ans avant le 1er octobre prochain.</div>
        <div><input type="checkbox" onclick="return false"' . $data["2"] . '>L’élève poursuivra ses apprentissages à l’éducation préscolaire, selon les modalités prévues dans son plan d’intervention.</div>
        <div><input type="checkbox" onclick="return false"' . $data["3"] . '>L’élève poursuivra ses apprentissages à l’enseignement primaire.</div>
        <div><input type="checkbox" onclick="return false"' . $data["4"] . '>Autre : <u><b> ' . $data["5"] . ' </b></u></div>
        <div>&nbsp&nbsp&nbsp&nbsp&nbsp</div>
        <div>&nbsp&nbsp&nbsp&nbsp&nbsp</div>
        <div class="section-1-item class-results--align-center"><b class="signature-ts">&nbsp&nbsp&nbsp&nbsp&nbspDanielle Grant&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp</b>' . $date . '</div>
        <div class=class-results--align-center>Signature de la directrice &nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp Date</div>
        </td>     
        </table>';
    }else{
         echo '<pre class="section-title">'; 
         echo $title; echo'</pre>';
         echo '<table class="section-1  print-friendly">
         <tr>
         <td class="section-2-header"> ' . $items['COMMENTAIRE'] . '</td>
         </tr><td>
         <div><input type="checkbox" onclick="return false"' . $data["1"] . '>L’élève poursuivra ses apprentissages dans la classe supérieure.</div>
         <div><input type="checkbox" onclick="return false"' . $data["2"] . '>L’élève poursuivra ses apprentissages dans la même classe, selon les modalités prévues dans son plan d’intervention.</div>
         <div>&nbsp&nbsp&nbsp&nbsp&nbsp</div>
         <div>&nbsp&nbsp&nbsp&nbsp&nbsp</div>
         <div class="section-1-item class-results--align-center"><b class="signature-ts">&nbsp&nbsp&nbsp&nbsp&nbspDanielle Grant&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp</b>' . $date . '</div>
         <div class=class-results--align-center>Signature de la directrice &nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp Date</div>
         </td>     
         </table>';
         }
}
 
function CadoHTMLpageSetup($title,$grade){

    if(strpos($grade,'Secondaire 1') || strpos($grade,'Secondaire 2'))
        $extra = 'Premier cycle';
    if(strpos($grade,"Préscolaire")){
        echo '<table class="page-prescolaire-table">';
        echo '<tr><td>';
        echo '<div>&nbsp</div>';
        echo '<div class="page-prescolaire-title textwhite">BULLETIN DE L’ÉDUCATION PRÉSCOLAIRE</div>';
        echo '<div class="page-prescolaire-title textwhite">' . _schoolYear . ' ' . UserSyear() . '-' . (UserSyear()+1) .  '</div></td></tr></table>';
    }else{
        echo '<table class="logo">';
        echo '<tr><td width=105 style=justify-right>' . DrawLogo() . '';
        echo '<div class="logo-td">' . $title  . ' ' .$grade . '</div>';
        echo '<div class="logo-td">' . $extra .  '</div>';
        echo '<div class="logo-td">' . _schoolYear . ' ' . UserSyear() . '-' . (UserSyear()+1) .  '</div></td></tr></table>';
    }
    
    echo '<!-- MEDIA SIZE 8.5x11in -->';
    echo'
    <style>
    @import url("https://fonts.cdnfonts.com/css/lucida-handwriting-std");
    body {
        font-family: Sans-Serif;
      }
      @media print {
        table {page-break-inside: avoid;}
      }
      table,
      thead,
      tbody,
      tfoot,
      tr,
      th,
      td {
        width: auto;
        height: auto;
        margin: 0;
        padding: 0;
        border: none;
        border-spacing: 0;
        page-break-inside: avoid;
      }
      th,
      td {
        border-right: 2px solid black;
        border-bottom: 2px solid black;
        padding: 5px;
        page-break-inside: avoid;
      }
      .logo {
        border: none;
        width: 100%;
      }
      .logo td, .logo tr{
       text-align:center;
        border: none;
        font-size:20px; 
        font-weight:bold; 
      }
      .logo-title{
        font-size:20px; 
        text-align: center; 
        font-weight:bold; 
        padding-right:90px; 
        padding-top:10px;
      }
      .section-title{
        font-size:20px; 
        font-weight:bold; 
        font-family: Arial, Helvetica, sans-serif;
      }
      .section-1{
        width: 100%;
        margin-bottom: 20px;
        border-top: 2px solid black;
        border-left: 2px solid black;
        border-bottom: 0px solid black;
        border-right: 0px solid black;
      }
      .bilan{
        line-height: 1.5em;
        height: 3em;       /* height is 2x line-height, so two lines will display */
        overflow: hidden;  /* prevents extra lines from being visible */
        text-align: center;
        background-color: lightgrey;
       }
      .class-prescolaire_table {
        width: 100%;
        margin-bottom: 40px;
        page-break-inside: avoid;
      }
      .class-prescolaire_table{
        border: 1px solid grey;
        page-break-inside: avoid;
      }
      .class-prescolaire_table tr td{
        padding-top : 10px;
        padding-bottom : 5px;
        border-top: 1px solid grey;
        border-left:  1px solid grey;
        border-right:  1px solid grey;
        border-bottom: 1px solid grey;
        page-break-inside: avoid;
      }
      .section-prescolaire-title{
        font-size:20px; 
        font-weight:bold; 
        font-family: Arial, Helvetica, sans-serif;
      }
      .section-prescolaire-title span {
        display: inline-block;
        background-color: #585858;
        padding: 5px 10px;
        border-radius: 20px;
      }
      .page-prescolaire-title{
        font-size:25px; 
        font-weight:bold; 
        font-family: Arial, Helvetica, sans-serif;
        text-color: white;
        margin-left: 20px;
       }
       .page-prescolaire-table{
        font-size: 40px; 
        font-weight:bold; 
        font-family: Arial, Helvetica, sans-serif;
        width: 100%;
        height: 100px;
        padding-right: 0px; 
        padding-top:0px;
        padding-bottom:0px;
        margin-left: 0px;
        margin-bottom: 0px;
        margin-right: 0px;
        page-break-inside: avoid;
        background-color: #585858;
        page-break-inside: avoid;
      }
     .class-assiduite_table {
        width: 100%;
        padding-right: 0px; 
        padding-top:0px;
        padding-bottom:0px;
        margin-left: 0px;
        margin-bottom: 0px;
        margin-right: 0px;
        border: 0px solid black;
        page-break-inside: avoid;
        page-break-inside: avoid;
      }
      .class-assiduite__first_col {
        border-top: 1px solid black;
        border-left: 1px solid black;
        border-right: none;
        border-bottom: 1px solid black;
        font-weight: bold; 
      }
      .class-assiduite__first_col1 {
        border-top: 1px solid black;
        border-left: 1px solid black;
        border-right: 1px solid black;
        border-bottom: 1px solid black;
        font-weight: bold; 
      }
       .class-assiduite__last_col {
        border-top: 1px solid black;
        border-left: 1px solid black;
        border-right: 1px solid black;;
        border-bottom: 1px solid black;
        font-weight: bold; 
      }
      .class-assiduite__top_col {
        border-top: 1px solid black;
        border-left: 1px solid black;
        border-right: 1px solid black;
        border-bottom: 0px solid black;
        font-weight: bold; 
      }
      .class-assiduite__col {
        border-top: none;
        border-left: 1px solid black;
        border-right: none;
        border-bottom: 1px solid black;
        font-weight: bold; 
      }
      .class-assiduite__item1 {
        border-top: none;
        border-left: 1px solid black;
        border-right: 0px solid black;
        border-bottom: 1px solid black;
        font-weight: bold; 
      }
      .class-assiduite__last_item1 {
        border-top: none;
        border-left: 1px solid black;
        border-right: 1px solid black;
        border-bottom: 1px solid black;
        font-weight: bold; 

      }
      .class-assiduite__item2 {
        border-top: none;
        font-size:13px; 
        font-weight:bold;
        border-left: 1px solid black;
        border-right: 0px solid black;
        border-bottom: 1px solid black;
        font-weight: normal; 
      }
      .class-assiduite__last_item2 {
        border-top: none;
        font-size:13px; 
        border-left: 1px solid black;
        border-right: 1px solid black;
        border-bottom: 1px solid black;
        font-weight: normal; 
      }
      .class-assiduite__last_row_item {
        border-top: none;
        border-left: 1px solid black;
        border-right: 0px solid black;
        border-bottom: 1px solid black;
        font-weight: bold; 
      }
      .class-assiduite__last_row_item_last {
        border-top: none;
        border-left: 1px solid black;
        border-right: 1px solid black;
        border-bottom: 1px solid black;
        font-weight: normal; 
        font-size:13px; 
      }
      .class-assiduite__last_row {
        border-top: none;
        font-size:13px; 
        border-left: 1px solid black;
        border-right: 0px solid black;
        border-bottom: 1px solid black;
        font-weight: normal; 
      }
      .border{
        border 1px solid black;
      }
      .border-left{
        border-left: 1px solid black;
      }
      .class-border-right{
        border-right: 1px solid black;
      }
      .class-prescolaire-checkbox{
        font-size:16px; 
        text-align: center;
      }
      .bkgnd1{
            background-color: #d0d0d0;
       }
      .bkgnd2{
            background-color: #f2f2f2;
       }
      .bkgnd3{
            background-color: #bfbfbf;
       }
      .class-results__table {
        width: 100%;
        margin-bottom: 20px;
        border-top: 2px solid black;
        border-left: 2px solid black;
        border-bottom: 0px solid black;
        border-right: 0px solid black;
        page-break-inside: avoid;
      }
      .class-results__th--left-header {
        font-weight: normal;
      }
      .class-results__th--left-header h1 {
        font-size: 1.2rem;
        margin-top: 0;
      }
      .class-results__3col__th {
        width: 60px;
      }
      .class-results--align-left {
        text-align: left;
      }
      .class-results--align-center {
        text-align: center;
      }
      .class-results--align-right {
        text-align: right;
      }
      .class-results__td--grey {
        background: lightgrey;
      }
      .section-1-block{
        padding-top:10px;
        padding-left:5px;
        padding-bottom:10px;
      }
      .section-1-item{
        padding:3px;
      }
      .section-2-header{
        text-align: center;
        font-size:1 5px; 
        page-break-inside: avoid;
        font-family: Arial, Helvetica, sans-serif;
      }
      .section-2-item{
        page-break-inside: avoid;
        font-weight:bold; 
        font-style: italic;
      }
      .signature{
        padding 10px;
        border: none;
        padding-left:10px;
        page-break-inside: avoid;
        align:right;
      }
      .signature-td{
        text-align: left;
        font-family: Arial, Helvetica, sans-serif;
        font-weight:bold; 
        font-size:20px; 
        padding 10px;
        border: none;
        alignv: bottom;
        padding-top:20px;
      }
      .signature-tr{
        padding 10px;
        border: none;
        alignv: bottom;
        padding-top:20px;
      }
      .signature-ts{
        text-align: left;
        font-family: "Lucida Handwriting Std",  sans-serif;
        font-size:12px; 
        padding 10px;
        border: none;
        alignv: bottom;
        padding-top:20px;
        width: 50%;
      }
      .highligth{
        font-size:15px; 
        font-weight:bold; 
        font-style: italic;
        color:white;
        background-color:red;
    }
    .noborder{
        border-top: 0px solid black;
        border-left: 0px solid black;
        border-bottom: 0px solid black;
        border-right: 1px solid black;
    }
    .border{
        border: 1px solid black;
    }
    .textwhite{
        color:white;
    }
    .center{
        align: center;
        text-align: center;
    }
    .bggrey{
        font-size:20px; 
        font-weight:bold; 
        color:black;
        text-align:center;
        background-color:lightgrey;
    }
    .bgetapes{
        background-color:lightgrey;
        boder: none;
    }
    table.noborder tr td:last-child {
      border-right: none;
        page-break-inside: avoid;
    }

    table td:has(table),
    table td.section-1-block:has(table) {
        padding-top: 0;
        padding-left: 0;
        padding-bottom: 0;
        padding-right: 0;
        vertical-align: top;
        page-break-inside: avoid;
    }
    .class-results__table thead tr td {
        color: white;
        font-size: 20px; 
        font-weight: bold; 
        font-family: Arial, Helvetica, sans-serif;
        background-color: #585858;
        page-break-inside: avoid;
    }
    </style>';
}

//### END HTML FUNCTIONS
//#####################################################//

//### START ACCESORY FUNCTIONS
//#####################################################//

function _removeSpaces($value,$column){
	if($column=='ASSIGNED_DATE' || $column=='DUE_DATE')
		$value = ProperDate($value);
                if($column=='TITLE')
                    $value = html_entity_decode($value);
	return str_replace(' ','&nbsp;',str_replace('&','&amp;',$value));
}

function _makeAssnWG($value,$column){
    global $THIS_RET,$student_points,$total_points,$percent_weights;
    return ($THIS_RET['ASSIGN_TYP_WG']!='N/A'?($value*100).' %':$THIS_RET['ASSIGN_TYP_WG']);    
}

function _makeWtg($value,$column){	
    global $THIS_RET,$student_points,$total_points,$percent_weights;
    $wtdper=($THIS_RET['POINTS']/$THIS_RET['TOTAL_POINTS'])* $THIS_RET['ASSIGN_WEIGHT']/100 ;
    return (($THIS_RET['LETTERWTD_GRADE']!=-1.00 && $THIS_RET['LETTERWTD_GRADE']!='' && $THIS_RET['ASSIGN_TYP_WG']!='N/A') ?_makeLetterGrade($wtdper,"",$THIS_RET['CP_TEACHER_ID'],'%').'%':'N/A');
}

function _makeAssgnmtWtg($value, $column) {
    global $THIS_RET, $student_points, $total_points, $percent_weights;
    return ($THIS_RET['ASSIGN_WEIGHT'] != 'N/A' ? $value . ' %' : $THIS_RET['ASSIGN_WEIGHT']);
}

function _getage30sept($dateOfBirth){
    $today = date("Y");
    $today .= '-09-30'; 
    $diff = date_diff(date_create($dateOfBirth), date_create($today));
    return ($diff->format('%y'));
}

function _makeExtra($value,$column){
	global $THIS_RET,$student_points,$total_points,$percent_weights;

	if($column=='POINTS')
	{
		if($THIS_RET['TOTAL_POINTS']!='0')
			if($value!='-1')
			{
				if(($THIS_RET['DUE'] || $value!='')&& $value!='')
				{
					$student_points[$THIS_RET['ASSIGNMENT_TYPE_ID']] += $value;
					$total_points[$THIS_RET['ASSIGNMENT_TYPE_ID']] += $THIS_RET['TOTAL_POINTS'];
					$percent_weights[$THIS_RET['ASSIGNMENT_TYPE_ID']] = $THIS_RET['FINAL_GRADE_PERCENT'];
				}
				//return '<TABLE border=0 cellspacing=0 cellpadding=0 class=LO_field><TR><TD><font size=-1>'.(rtrim(rtrim($value,'0'),'.')+0).'</font></TD><TD><font size=-1>&nbsp;/&nbsp;</font></TD><TD><font size=-1>'.$THIS_RET['TOTAL_POINTS'].'</font></TD></TR></TABLE>';
                $outputval = rtrim(rtrim($value,'0'),'.');
                return '<TABLE border=0 cellspacing=0 cellpadding=0 class=LO_field><TR><TD><font size=-1>'.($outputval == '' ? 0 : $outputval).'</font></TD><TD><font size=-1>&nbsp;/&nbsp;</font></TD><TD><font size=-1>'.$THIS_RET['TOTAL_POINTS'].'</font></TD></TR></TABLE>';
			}
			else
				return '<TABLE border=0 cellspacing=0 cellpadding=0 class=LO_field><TR><TD><font size=-1>Excluded</font></TD><TD></TD><TD></TD></TR></TABLE>';
		else
		{
			$student_points[$THIS_RET['ASSIGNMENT_TYPE_ID']] += $value;
			//return '<TABLE border=0 cellspacing=0 cellpadding=0 class=LO_field><TR><TD><font size=-1>'.(rtrim(rtrim($value,'0'),'.')+0).'</font></TD><TD><font size=-1>&nbsp;/&nbsp;</font></TD><TD><font size=-1>'.$THIS_RET['TOTAL_POINTS'].'</font></TD></TR></TABLE>';
            return '<TABLE border=0 cellspacing=0 cellpadding=0 class=LO_field><TR><TD><font size=-1>'.rtrim(rtrim($value,'0'),'.').'</font></TD><TD><font size=-1>&nbsp;/&nbsp;</font></TD><TD><font size=-1>'.$THIS_RET['TOTAL_POINTS'].'</font></TD></TR></TABLE>';
		}
	}
	elseif($column=='LETTER_GRADE')
	{
		if($THIS_RET['TOTAL_POINTS']!='0')
			if($value!='-1')
				if($THIS_RET['DUE'] && $value=='')
                                    return "Non coté";
                                else if($THIS_RET['DUE'] || $value!='')
                                {
                                    $per = $value/$THIS_RET['TOTAL_POINTS'];
                                  
                                    return _makeLetterGrade($per,"",$THIS_RET['CP_TEACHER_ID'],"%").'%&nbsp;'. _makeLetterGrade($value/$THIS_RET['TOTAL_POINTS'],"",$THIS_RET['CP_TEACHER_ID']);
					// return Percent($value/$THIS_RET['TOTAL_POINTS'],0).'&nbsp;'. _makeLetterGrade($value/$THIS_RET['TOTAL_POINTS'],$THIS_RET['COURSE_PERIOD_ID'],  UserStaffID());
                                }
				else
					return 'Due';
			else
				return 'N/A';
		else
			return 'E/C';
	}
}

function _makeChooseCheckbox($value, $title){
    global $THIS_RET;
    return "<input name=unused_var[$THIS_RET[STUDENT_ID]] value=" . $THIS_RET['STUDENT_ID'] . "  type='checkbox' id=$THIS_RET[STUDENT_ID] onClick='setHiddenCheckboxStudents(\"st_arr[$THIS_RET[STUDENT_ID]]\",this,$THIS_RET[STUDENT_ID]);' />";
}

function _makeTeacher($teacher, $column){

    $TEACHER_NAME = DBGet(DBQuery("SELECT concat(first_name,' ',last_name) as name from staff where staff_id=$teacher"));
    return $TEACHER_NAME[1]['NAME'];
}

function _makeTeacherID($teacher, $column){

    $TEACHER_ID = DBGet(DBQuery("SELECT staff_id as name from staff where staff_id=$teacher"));
    return $TEACHER_ID[1]['NAME'];
}

function _myround($value){
    if($value== 'N/A') return ''; 
//    return round(round($value,2),0);
    return($value !=0 ? round(round($value,2),0) . '' : '');
}

function CadoTeacherComlpetion($teacher_id,$course_id,$course_period_id,$short_name){
    $cur_mp= UserMP();
    $bad_weght=CadoCheckWeight($course_period_id,$teacher_id,$cur_mp,$course_id);
    $bad_config=CadoCheckConfig($course_period_id,$teacher_id,$cur_mp,$course_id);
    if(round(CadoGetFinalAverage($course_period_id,$cur_mp,UserSyear(),$short_name)) > 0 && round(CadoGetFinalAverage($course_id,$cur_mp,UserSyear(),$short_name)) != 'NAN')
        $bad_final = 0;
    else 
        $bad_final = 1;
    if($bad_config || $bad_weght || $bad_final) 
        return 1;
    return 0;
}

function CadoCheckWeight($course_period_id,$staff_id,$mp,$course_id){
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

function CadoCheckConfig($course_period_id,$staff_id,$mp,$course_id){
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

function CadoGetFinalAverage($course_period_id,$mp,$year,$title){

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
                if($year=='2022' || substr( $title, 0, 3 ) === "PRE") {
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

function CadoStudentComments($student_id, $grade_id,$marking_period) {
    $column=array();
    $data=array();
    $USER_RET2=DBGet(DBQuery('SELECT com_competences,com_general from CADO_report_card_comments where STUDENT_ID = \''. $student_id . '\' AND MARKING_PERIOD=\''.  $marking_period . '\'  '));
    if(strpos($grade_id,"Primaire 1") || strpos($grade_id,"Primaire 3")  || strpos($grade_id,"Primaire 5")  || strpos($grade_id,"Secondaire 1") ){
        $cycle1year=UserSyear();
        $cycle2year='';
    }else{
        $cycle1year=UserSyear()-1;
        $cycle2year=UserSyear();
    }
    $mpC1_E1=DBGet(DBQuery('SELECT marking_period_id FROM  marking_periods WHERE SYEAR=\'' . $cycle1year . '\' AND SCHOOL_ID=\'' . UserSchool() . '\' AND title="Étape 1"'));
    $mpC1_E3=DBGet(DBQuery('SELECT marking_period_id FROM  marking_periods WHERE SYEAR=\'' . $cycle1year . '\' AND SCHOOL_ID=\'' . UserSchool() . '\' AND title="Étape 3"'));
    $mpC2_E1=DBGet(DBQuery('SELECT marking_period_id FROM  marking_periods WHERE SYEAR=\'' . $cycle2year . '\' AND SCHOOL_ID=\'' . UserSchool() . '\' AND title="Étape 1"'));
    $mpC2_E3=DBGet(DBQuery('SELECT marking_period_id FROM  marking_periods WHERE SYEAR=\'' . $cycle2year . '\' AND SCHOOL_ID=\'' . UserSchool() . '\' AND title="Étape 3"'));
    $USER_RETC1_E1=DBGet(DBQuery('SELECT com_competences,com_general from CADO_report_card_comments where STUDENT_ID = \''. $student_id . '\' AND MARKING_PERIOD=\''.  $mpC1_E1[1]['MARKING_PERIOD_ID'] . '\'  '));
    $USER_RETC1_E3=DBGet(DBQuery('SELECT com_competences,com_general from CADO_report_card_comments where STUDENT_ID = \''. $student_id . '\' AND MARKING_PERIOD=\''.  $mpC1_E3[1]['MARKING_PERIOD_ID'] . '\'  '));
    $USER_RETC2_E1=DBGet(DBQuery('SELECT com_competences,com_general from CADO_report_card_comments where STUDENT_ID = \''. $student_id . '\' AND MARKING_PERIOD=\''.  $mpC2_E1[1]['MARKING_PERIOD_ID'] . '\'  '));
    $USER_RETC2_E3=DBGet(DBQuery('SELECT com_competences,com_general from CADO_report_card_comments where STUDENT_ID = \''. $student_id . '\' AND MARKING_PERIOD=\''.  $mpC2_E3[1]['MARKING_PERIOD_ID'] . '\'  '));
    $markingPeriod = DBGet(DBQuery('SELECT * FROM school_quarters WHERE SYEAR=\'' . UserSyear() . '\' AND SCHOOL_ID=\'' . UserSchool() . '\' AND SORT_ORDER=255 '));
    if($markingPeriod[1]['MARKING_PERIOD_ID'] == $marking_period)
    {
        $column['COMMENTAIRE']=_commentsOther;
        $data['COMMENTAIRE']=$USER_RET2[1]['COM_GENERAL'];
        CadoHTMLcommentairesGeneral(_reportcard_cat5,$column,$data);
        return;
    } 
    $data['C1_E1']=$USER_RETC1_E1[1]['COM_COMPETENCES'];
    $data['C1_E3']=$USER_RETC1_E3[1]['COM_COMPETENCES'];
    $data['C2_E1']=$USER_RETC2_E1[1]['COM_COMPETENCES'];
    $data['C2_E3']=$USER_RETC2_E3[1]['COM_COMPETENCES'];
    $cheminenment = DBGet(DBQuery('SELECT CUSTOM_23,CUSTOM_24,CUSTOM_25 FROM students WHERE  STUDENT_ID = ' . $student_id . ' '));
    if(strpos($grade_id,"Préscolaire")){
        if(substr( $cheminenment[1]['CUSTOM_23'], 0, 1 ) === "1") 
            $data['1']='checked';
        else
            $data['1']='unchecked';
        if(substr( $cheminenment[1]['CUSTOM_23'], 0, 1 ) === "2") 
            $data['2']='checked';
        else
            $data['2']='unchecked';
        if(substr( $cheminenment[1]['CUSTOM_23'], 0, 1 ) === "3") 
            $data['3']='checked';
        else
            $data['3']='unchecked';
        if(substr( $cheminenment[1]['CUSTOM_23'], 0, 1 ) === "4"){
            $data['4']='checked';
            $data['5']=$cheminenment[1]['CUSTOM_25'];
        }
        else
            $data['4']='unchecked';
        CadoHTMLcommentairesPrescolaire(_reportcard_cat3,$data,$grade_id);
    }
    else{
        if(substr( $cheminenment[1]['CUSTOM_24'], 0, 1 ) === "1") 
            $data['1']='checked';
        else
            $data['1']='unchecked';
        if(substr( $cheminenment[1]['CUSTOM_24'], 0, 1 ) === "2") 
            $data['2']='checked';
        else
            $data['2']='unchecked';
        CadoHTMLcommentairesCompetence(_reportcard_cat3,$data,$grade_id);
    }
    $column['COMMENTAIRE']=_commentsOther;
    $data['COMMENTAIRE']=$USER_RET2[1]['COM_GENERAL'];
    if(! strpos($grade_id,"Préscolaire"))
        CadoHTMLcommentairesGeneral(_reportcard_cat4,$column,$data);
    $column['COMMENTAIRE']=_reportcard_higher;
    if(! strpos($grade_id,"Préscolaire"))
        CadoHTMLcommentairesCheminement(_reportcard_cat5a,$column,$data,$mp,$grade_id);
    else
        CadoHTMLcommentairesCheminement("4. Cheminement scolaire",$column,$data,$mp,$grade_id);
    $date = date("d-m-Y ");
    echo '<br/>';
    echo '<br/>';
    echo '<table class="signature">';
    echo '<tr class="signature-tr"><td  class="signature-td">Signature de la directrice:</td><td class="signature-ts">Danielle Grant</td><td class="signature-td"> Date : ' . $date . '</td></tr>';
    echo '</table>';
    echo '<br/><br/>';
    
}

function GetGroupAverage($course_id,$course_period_id,$marking_period,$year){
    $total_group=0;
    $students=0;
    $sql='SELECT GRADE_PERCENT FROM student_report_card_grades WHERE COURSE_PERIOD_ID=\'' . $course_period_id . '\' AND MARKING_PERIOD_ID=\''.  $marking_period . '\' ';
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
    if($student)
        return $total_group/$student;
    else return 0;
    }
}

function CadoAssiduiteQuarters($student_id, $grade_id){

    if(strpos($grade_id,"Primaire 1") || strpos($grade_id,"Primaire 3")  || strpos($grade_id,"Primaire 5")  || strpos($grade_id,"Secondaire 1") ){
        $cycle1year=UserSyear();
        $cycle2year='';
    }else{
        $cycle1year=UserSyear()-1;
        $cycle2year=UserSyear();
    }
    $year=$cycle1year;
    for($i = 0; $i <2; $i++){
        $ALL_QUART=DBGet(DBQuery('SELECT MARKING_PERIOD_ID,SORT_ORDER from school_quarters WHERE SCHOOL_ID=\'' . UserSchool() . '\' AND SYEAR=\'' . $year . '\' AND TITLE LIKE \'Étape%\' ORDER BY sort_order')); 
        $mpcount=1;
        foreach ($ALL_QUART as $key=> $quart) {
            if ( $quart['MARKING_PERIOD_ID'] > UserMP())
                break;
            $count=0;
            $ATT_RET=DBGet(DBQuery('SELECT SCHOOL_DATE,MARKING_PERIOD_ID,STATE_VALUE,student_id from attendance_day WHERE STATE_VALUE=0 AND STUDENT_ID=\'' .  $student_id . '\'  AND MARKING_PERIOD_ID =\'' . $quart['MARKING_PERIOD_ID'] . '\'')); 
            $MAXDAYS_RET=DBGet(DBQuery('SELECT DAYS FROM school_quarters WHERE MARKING_PERIOD_ID =\'' . $quart['MARKING_PERIOD_ID'] . '\'')); 
            foreach ($ATT_RET as $abs) $count+=1 - $abs['STATE_VALUE'];
            $data['STUDENT_ABSCENCES_QUARTER'][$i][$mpcount][$mpcount]=$count;
            $data['STUDENT_ABSCENCES_QUARTER'][$i][$mpcount]['MAXDAYS_QUARTER']=$MAXDAYS_RET[1]['DAYS'];
            $mpcount+=1;
        }
        $year=$cycle2year;
    }
    // echo '<pre>';  print_r($data); echo '</pre>';
    return $data['STUDENT_ABSCENCES_QUARTER'];
}

//### END ACCESORY FUNCTIONS
//#####################################################//
