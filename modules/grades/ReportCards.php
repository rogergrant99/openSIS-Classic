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


        $extra['SELECT'] .= ',rc_cp.COURSE_WEIGHT,rc_cp.TITLE as SHORT ,rpg.TITLE as GRADE_TITLE,sg1.GRADE_PERCENT,sg1.WEIGHTED_GP,sg1.UNWEIGHTED_GP ,sg1.CREDIT_ATTEMPTED , sg1.COMMENT as COMMENT_TITLE,sg1.STUDENT_ID,sg1.COURSE_PERIOD_ID,sg1.MARKING_PERIOD_ID,c.TITLE as COURSE_TITLE,rc_cp.TEACHER_ID AS TEACHER,rc_cp.TEACHER_ID AS TEACHER_ID2,sg1.COURSE_PERIOD_ID AS COURSE_ID,sp.SORT_ORDER';

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
        $extra['functions']['TEACHER'] = '_makeTeacher';
        $extra['group'] = array('STUDENT_ID', 'COURSE_PERIOD_ID', 'MARKING_PERIOD_ID');
        $RET = GetStuList($extra);
        if (($_REQUEST['elements']['comments'] == 'Y') || ($_REQUEST['elements']['comments'] == 'Y' && $_REQUEST['elements']['percents'])) {
            // GET THE COMMENTS
            unset($extra);
            $extra['WHERE'] = ' AND s.STUDENT_ID IN (' . $st_list . ')';
            $extra['SELECT_ONLY'] = 's.STUDENT_ID,sc.COURSE_PERIOD_ID,sc.MARKING_PERIOD_ID,sc.REPORT_CARD_COMMENT_ID,sc.COMMENT,(SELECT SORT_ORDER FROM report_card_comments WHERE ID=sc.REPORT_CARD_COMMENT_ID) AS SORT_ORDER';
            $extra['FROM'] = ',student_report_card_comments sc';
            $extra['WHERE'] .= ' AND sc.STUDENT_ID=s.STUDENT_ID AND sc.MARKING_PERIOD_ID=\'' . $last_mp . '\'';
            $extra['ORDER_BY'] = 'SORT_ORDER';
            $extra['group'] = array('STUDENT_ID', 'COURSE_PERIOD_ID', 'MARKING_PERIOD_ID');
            $comments_RET = GetStuList($extra);


            $all_commentsA_RET = DBGet(DBQuery('SELECT ID,TITLE,SORT_ORDER FROM report_card_comments WHERE SCHOOL_ID=\'' . UserSchool() . '\' AND SYEAR=\'' . UserSyear() . '\' AND COURSE_ID IS NOT NULL AND COURSE_ID=\'0\' ORDER BY SORT_ORDER,ID'), array(), array('ID'));
            $commentsA_RET = DBGet(DBQuery('SELECT ID,TITLE,SORT_ORDER FROM report_card_comments WHERE SCHOOL_ID=\'' . UserSchool() . '\' AND SYEAR=\'' . UserSyear() . '\' AND COURSE_ID IS NOT NULL AND COURSE_ID!=\'0\''), array(), array('ID'));
            $commentsB_RET = DBGet(DBQuery('SELECT ID,TITLE,SORT_ORDER FROM report_card_comments WHERE SCHOOL_ID=\'' . UserSchool() . '\' AND SYEAR=\'' . UserSyear() . '\' AND COURSE_ID IS NULL'), array(), array('ID'));
        }
        if ((($_REQUEST['elements']['mp_tardies'] == 'Y' || $_REQUEST['elements']['ytd_tardies'] == 'Y') && !$_REQUEST['elements']['grade_type']) || (($_REQUEST['elements']['mp_tardies'] == 'Y' || $_REQUEST['elements']['ytd_tardies'] == 'Y') && $_REQUEST['elements']['grade_type'] && $_REQUEST['elements']['percents'])) {
            // GET THE ATTENDANCE
            unset($extra);
            $extra['WHERE'] = ' AND s.STUDENT_ID IN (' . $st_list . ')';
            $extra['SELECT_ONLY'] = 'ap.SCHOOL_DATE,ap.COURSE_PERIOD_ID,ac.ID AS ATTENDANCE_CODE,ap.MARKING_PERIOD_ID,ssm.STUDENT_ID';
            $extra['FROM'] = ',attendance_codes ac,attendance_period ap';
            $extra['WHERE'] .= ' AND ac.ID=ap.ATTENDANCE_CODE AND (ac.DEFAULT_CODE!=\'Y\' OR ac.DEFAULT_CODE IS NULL) AND ac.SYEAR=ssm.SYEAR AND ap.STUDENT_ID=ssm.STUDENT_ID';
            $extra['group'] = array('STUDENT_ID', 'ATTENDANCE_CODE', 'MARKING_PERIOD_ID');
            $attendance_RET = GetStuList($extra);
        }
        if ((($_REQUEST['elements']['mp_absences'] == 'Y' || $_REQUEST['elements']['ytd_absences'] == 'Y') && !$_REQUEST['elements']['grade_type']) || (($_REQUEST['elements']['mp_absences'] == 'Y' || $_REQUEST['elements']['ytd_absences'] == 'Y') && $_REQUEST['elements']['grade_type'] && $_REQUEST['elements']['percents'])) {
            // GET THE DAILY ATTENDANCE
            unset($extra);
            $extra['WHERE'] = ' AND s.STUDENT_ID IN (' . $st_list . ')';
            $extra['SELECT_ONLY'] = 'ad.SCHOOL_DATE,ad.MARKING_PERIOD_ID,ad.STATE_VALUE,ssm.STUDENT_ID';
            $extra['FROM'] = ',attendance_day ad';
            $extra['WHERE'] .= ' AND ad.STUDENT_ID=ssm.STUDENT_ID AND ad.SYEAR=ssm.SYEAR AND (ad.STATE_VALUE=\'0.0\' OR ad.STATE_VALUE=\'.5\') AND ad.SCHOOL_DATE<=\'' . GetMP($last_mp, 'END_DATE') . '\'';
            $extra['group'] = array('STUDENT_ID', 'MARKING_PERIOD_ID');
            $attendance_day_RET = GetStuList($extra);
        }


        if (count($RET)) {
            $columns = array('COURSE_TITLE' => _course);
            if ($_REQUEST['elements']['teacher'] == 'Y')
                $columns += array('TEACHER' => _teacher);
            if ($_REQUEST['elements']['period_absences'] == 'Y')
                $columns += array('ABSENCES' => 'Abs<BR>YTD / MP');
            if (count($_REQUEST['mp_arr']) > 4)
                $mp_TITLE = 'SHORT_NAME';
            else
                $mp_TITLE = 'TITLE';
            foreach ($_REQUEST['mp_arr'] as $mp)
                $columns[$mp] = GetMP($mp, $mp_TITLE);
            if ($_REQUEST['elements']['comments'] == 'Y') {  //for standard grade
                foreach ($all_commentsA_RET as $comment)
                    $columns['C' . $comment[1]['ID']] = $comment[1]['TITLE'];
                $columns['COMMENT'] = 'Comment';
            }
            if ($_REQUEST['elements']['gpa'] == 'Y')
                $columns['GPA'] = 'GPA';
            //start of report card print

            $total_stu = 1;
            if (!isset($_REQUEST['elements']['percents']) || (isset($_REQUEST['elements']['percents']) && $_REQUEST['elements']['percents'] == 'Y')) {
                foreach ($RET as $student_id => $course_periods) {
                    $handle = PDFStart();
                    if($publish_parents) $publish_parents=$student_id;
                    if (!isset($_REQUEST['elements']['percents']) || (isset($_REQUEST['elements']['percents']) && $_REQUEST['elements']['percents'] == 'Y')) {   //when Standard Grade is not selected
                        $comments_arr = array();
                        $comments_arr_key = (is_countable($all_commentsA_RET) ? count($all_commentsA_RET) : 0) > 0;
                        unset($grades_RET);
                        $i = 0;
                        $total_grade_point = 0;
                        $Total_Credit_Hr_Attempted = 0;
                        $commentc = '';
                        foreach ($course_periods as $course_period_id => $mps) {
                            $i++;
                            //$commentc=$mps[key($mps)][1]['COMMENT_TITLE'];
                            $commentc = $mps[$last_mp][1]['COMMENT_TITLE'];
                            $grades_RET[$i]['COURSE_TITLE'] = $mps[key($mps)][1]['COURSE_TITLE'];
                            $grades_RET[$i]['SHORT'] = $mps[key($mps)][1]['SHORT'];
                            $grades_RET[$i]['TEACHER'] = $mps[key($mps)][1]['TEACHER'];
                            $grades_RET[$i]['TEACHER_ID'] = $mps[key($mps)][1]['TEACHER_ID2'];
                            $grades_RET[$i]['COURSE_ID'] = $mps[key($mps)][1]['COURSE_ID'];
                            $grades_RET[$i]['CGPA'] = round($mps[key($mps)][1]['UNWEIGHTED_GPA'], 3);
                            if ($mps[key($mps)][1]['WEIGHTED_GP'] && $mps[key($mps)][1]['COURSE_WEIGHT']) {
                                if (substr(key($mps), 0, 1) == 'E')
                                    $mpkey = substr(key($mps), 1);
                                else
                                    $mpkey = key($mps);
                                $total_grade_point += ($mps[$mpkey][1]['WEIGHTED_GP'] * $mps[$mpkey][1]['CREDIT_ATTEMPTED']);
                                $Total_Credit_Hr_Attempted += $mps[$mpkey][1]['CREDIT_ATTEMPTED'];
                            } elseif ($mps[key($mps)][1]['UNWEIGHTED_GP']) {
                                if (substr(key($mps), 0, 1) == 'E')
                                    $mpkey = substr(key($mps), 1);
                                else
                                    $mpkey = key($mps);
                                $total_grade_point += ($mps[$mpkey][1]['UNWEIGHTED_GP'] * $mps[$mpkey][1]['CREDIT_ATTEMPTED']);
                                $Total_Credit_Hr_Attempted += $mps[$mpkey][1]['CREDIT_ATTEMPTED'];
                            }

                            if ($_REQUEST['elements']['gpa'] == 'Y')
                                $grades_RET[$i]['GPA'] = ($Total_Credit_Hr_Attempted != 0 && $total_grade_point != 0 ? sprintf("%01.3f", ($total_grade_point / $Total_Credit_Hr_Attempted)) : 0);
                            $total_grade_point = 0;
                            $To_Credit_Hr_Attempted += $Total_Credit_Hr_Attempted;
                            $to_Credit_hr_attempt[$student_id] = $To_Credit_Hr_Attempted;
                            $Total_Credit_Hr_Attempted = 0;

                            foreach ($_REQUEST['mp_arr'] as $mp) {
                                $total_p1 = 0;

                                if ($mps[$mp]) {


                                    $dbf = DBGet(DBQuery('SELECT DOES_BREAKOFF,GRADE_SCALE_ID,TEACHER_ID FROM course_periods WHERE COURSE_PERIOD_ID=\'' . $course_period_id . '\''));
                                    $rounding = DBGet(DBQuery('SELECT VALUE FROM program_user_config WHERE USER_ID=\'' . $dbf[1]['TEACHER_ID'] . '\' AND TITLE=\'ROUNDING\' AND PROGRAM=\'Gradebook\' AND VALUE LIKE \'%_' . $course_period_id . '\''));
                                    //                                               if(count($config_RET))
                                    //			foreach($config_RET as $title=>$value)
                                    //                        {
                                    //                                $unused_var=explode('_',$value[1]['VALUE']);
                                    //                                $programconfig[$staff_id][$title] =$unused_var[0];
                                    ////				$programconfig[$staff_id][$title] = rtrim($value[1]['VALUE'],'_'.$course_period_id);
                                    //                        }
                                    //		else
                                    //			$programconfig[$staff_id] = true;


                                    if (count($rounding)) {
                                        $unused_var = explode('_', $rounding[1]['VALUE']);


                                        $_SESSION['ROUNDING'] = $unused_var[0];
                                    }
                                    //$_SESSION['ROUNDING']=rtrim($rounding[1]['VALUE'],'_'.UserCoursePeriod());
                                    else
                                        $_SESSION['ROUNDING'] = '';
                                    if ($_SESSION['ROUNDING'] == 'UP')
                                        $mps[$mp][1]['GRADE_PERCENT'] = ceil($mps[$mp][1]['GRADE_PERCENT']);
                                    elseif ($_SESSION['ROUNDING'] == 'DOWN')
                                        $mps[$mp][1]['GRADE_PERCENT'] = floor($mps[$mp][1]['GRADE_PERCENT']);
                                    elseif ($_SESSION['ROUNDING'] == 'NORMAL')
                                        $mps[$mp][1]['GRADE_PERCENT'] = round($mps[$mp][1]['GRADE_PERCENT']);
                                    if ($dbf[1]['DOES_BREAKOFF'] == 'Y' && $mps[$mp][1]['GRADE_PERCENT'] !== '' && $mps[$mp][1]['GRADE_PERCENT'] !== NULL) {
                                        $tc_grade = 'n';
                                        $get_details = DBGet(DBQuery('SELECT TITLE,VALUE FROM program_user_config WHERE TITLE LIKE \'' . $course_period_id . '-%' . '\' AND USER_ID=\'' . $grades_RET[$i]['TEACHER_ID'] . '\' AND PROGRAM=\'Gradebook\' AND VALUE LIKE \'%_' . UserCoursePeriod() . '\' ORDER BY VALUE DESC '));
                                        if (count($get_details)) {
                                            unset($id_mod);
                                            foreach ($get_details as $i_mod => $d_mod) {
                                                $unused_var = explode('_', $d_mod['VALUE']);
                                                if ($mps[$mp][1]['GRADE_PERCENT'] >= $unused_var[0] && !isset($id_mod)) {
                                                    $id_mod = $i_mod;
                                                }
                                            }
                                            $grade_id_mod = explode('-', $get_details[$id_mod]['TITLE']);

                                            $grades_RET[$i][$mp] = _makeLetterGrade($mps[$mp][1]['GRADE_PERCENT'] / 100, $course_period_id, $dbf[1]['TEACHER_ID'], "") . '&nbsp;';
                                            $tc_grade = 'y';
                                        }
                                        if ($tc_grade == 'n')
                                            $grades_RET[$i][$mp] = _makeLetterGrade($mps[$mp][1]['GRADE_PERCENT'] / 100, $course_period_id, $dbf[1]['TEACHER_ID'], "") . '&nbsp;';
                                    } else {
                                        if ($mps[$mp][1]['GRADE_PERCENT'] != NULl)
                                            $grades_RET[$i][$mp] = _makeLetterGrade($mps[$mp][1]['GRADE_PERCENT'] / 100, $course_period_id, $dbf[1]['TEACHER_ID'], "") . '&nbsp;';
                                    }

                                    if ($_REQUEST['elements']['percents'] == 'Y' && $mps[$mp][1]['GRADE_PERCENT'] > 0) {

                                        if ($mps[$mp][1]['GRADE_PERCENT'] != NULl) {


                                            //                                                        if($_SESSION['ROUNDING']=='UP')
                                            //                                                            $mps[$mp][1]['GRADE_PERCENT'] = ceil($mps[$mp][1]['GRADE_PERCENT']);
                                            //                                                    elseif($_SESSION['ROUNDING']=='DOWN')
                                            //                                                            $mps[$mp][1]['GRADE_PERCENT'] = floor($mps[$mp][1]['GRADE_PERCENT']);
                                            //                                                    elseif($_SESSION['ROUNDING']=='NORMAL')
                                            //                                                            $mps[$mp][1]['GRADE_PERCENT'] = round($mps[$mp][1]['GRADE_PERCENT']);
                                            $grades_RET[$i][$mp] .= '<br>' . $mps[$mp][1]['GRADE_PERCENT'] . '%';
                                        }

                                        //                                                
                                    }
                                    $last_mp = $mp;
                                }
                            }
                            if ($_REQUEST['elements']['period_absences'] == 'Y')
                                if ($mps[$last_mp][1]['DOES_ATTENDANCE'])
                                    $grades_RET[$i]['ABSENCES'] = $mps[$last_mp][1]['YTD_ABSENCES'] . ' / ' . $mps[$last_mp][1]['MP_ABSENCES'];
                                else
                                    $grades_RET[$i]['ABSENCES'] = 'n/a';
                            if ($_REQUEST['elements']['comments'] == 'Y') {
                                $sep = '';
                                foreach ($comments_RET[$student_id][$course_period_id][$last_mp] as $comment) {
                                    if ($all_commentsA_RET[$comment['REPORT_CARD_COMMENT_ID']])
                                        $grades_RET[$i]['C' . $comment['REPORT_CARD_COMMENT_ID']] = $comment['COMMENT'] != ' ' ? $comment['COMMENT'] : '&middot;';
                                    else {
                                        if ($commentsA_RET[$comment['REPORT_CARD_COMMENT_ID']]) {
                                            $grades_RET[$i]['COMMENT'] .= $sep . $commentsA_RET[$comment['REPORT_CARD_COMMENT_ID']][1]['SORT_ORDER'];
                                            $grades_RET[$i]['COMMENT'] .= '(' . ($comment['COMMENT'] != ' ' ? $comment['COMMENT'] : '&middot;') . ')';
                                            $comments_arr_key = true;
                                        } else
                                            $grades_RET[$i]['COMMENT'] .= $sep . $commentsB_RET[$comment['REPORT_CARD_COMMENT_ID']][1]['SORT_ORDER'];
                                        $sep = ', ';
                                        $comments_arr[$comment['REPORT_CARD_COMMENT_ID']] = $comment['SORT_ORDER'];
                                    }
                                }
                                if ($commentc != '')
                                    $grades_RET[$i]['COMMENT'] .= $sep . $commentc;
                                //if ($mps[$last_mp][1]['COMMENT_TITLE'])
                                //   $grades_RET[$i]['COMMENT'] .= $sep . $mps[$last_mp][1]['COMMENT_TITLE'];
                            }
                        }
                        asort($comments_arr, SORT_NUMERIC);

                        $addresses = array(0 => array());

                        foreach ($addresses as $address) {
                            unset($_openSIS['DrawHeader']);
                            CadoPageSetup(_reportcard_title . html_entity_decode($mps[key($mps)][1]['GRADE_ID']));
                            CadoHeader($mps[key($mps)][1]['STUDENT_ID'],$mps[key($mps)][1]['GRADE_ID'],$attendance_day_RET,$last_mp,$columns[$mp]);                            
                            //ListOutputPrint($grades_RET, $columns, '', '', array(), array(), array('print' =>false));
                            CadoStudentGrades($grades_RET,$mps[key($mps)][1]['STUDENT_ID'],$columns,$mps[key($mps)][1]['GRADE_ID'],GetMP($mp, $mp_TITLE),$last_mp);
                            CadoStudentComments($mps[key($mps)][1]['STUDENT_ID'],$mps[key($mps)][1]['GRADE_ID'],$last_mp);
                            if ($_REQUEST['elements']['comments'] == 'Y' && ($comments_arr_key || count($comments_arr))) {
                                $gender = substr($mps[key($mps)][1]['GENDER'], 0, 1);
                                $personalizations = array(
                                    '^n' => ($mps[key($mps)][1]['NICKNAME'] ? $mps[key($mps)][1]['NICKNAME'] : $mps[key($mps)][1]['FIRST_NAME']),
                                    '^s' => ($gender == 'M' ? 'his' : ($gender == 'F' ? 'her' : 'his/her'))
                                );

                                echo '<TABLE width=100%><TR><TD colspan=2><b>' . _explanationOfCommentCodes . '</b></TD>';
                                $i = 0;
                                if ($comments_arr_key)
                                    foreach ($commentsA_select as $key => $comment) {
                                        if ($i++ % 3 == 0)
                                            echo '</TR><TR valign=top>';
                                        echo '<TD>(' . ($key != ' ' ? $key : '&middot;') . '): ' . $comment[2] . '</TD>';
                                    }
                                foreach ($comments_arr as $comment => $so) {
                                    if ($i++ % 3 == 0)
                                        echo '</TR><TR valign=top>';
                                    if ($commentsA_RET[$comment])
                                        echo '<TD width=33%><small>' . $commentsA_RET[$comment][1]['SORT_ORDER'] . ': ' . str_replace(array_keys($personalizations), $personalizations, $commentsA_RET[$comment][1]['TITLE']) . '</small></TD>';
                                    else
                                        echo '<TD width=33%><small>' . $commentsB_RET[$comment][1]['SORT_ORDER'] . ': ' . str_replace(array_keys($personalizations), $personalizations, $commentsB_RET[$comment][1]['TITLE']) . '</small></TD>';
                                }
                                echo '</TR></TABLE>';
                            }
                            if ($_REQUEST['elements']['signature'] == 'Y') {
                                $date = date("d-m-Y ");
                                echo '<br/>';
                                echo '<br/>';
                                echo '<table class="signature">';
//                                echo '<tr class="signature-tr"><td  class="signature-td">Signature de l&rsquo;enseignant:</td><td class="signature-td">____________________________________________________</td><td class="signature-td">Date : ______________</td></tr>';
                                echo '<tr class="signature-tr"><td  class="signature-td">Signature de la directrice:</td><td class="signature-ts">Danielle Grant</td><td class="signature-td"> Date : ' . $date . '</td></tr>';
//                                echo '<tr class="signature-tr"><td  class="signature-td">Signature du parent/tuteur:</td><td class="signature-td">____________________________________________________</td><td class="signature-td">Date : ______________</td></tr>';
                                echo '</table>';
                            } echo '<br/><br/>';
                            //if (!$_REQUEST['elements']['grade_type']) {
                            //    if ($total_stu < count($RET)) {
                            //        echo '<span style="font-size:13px; font-weight:bold;"></span>';
                            //        echo '<!-- NEW PAGE -->';
                            //        echo "<div style=\"page-break-before: always;\"></div>";
                            //    }
                            //}
                        }
                    }
                    $QUART_RET=DBGet(DBQuery('SELECT * from school_quarters WHERE SCHOOL_ID=\'' . UserSchool() . '\' AND SYEAR=\'' . UserSyear() . '\' AND MARKING_PERIOD_ID=\'' . $last_mp . '\''));  
                    $filename=$mps[key($mps)][1]['FIRST_NAME'] . ' ' . $mps[key($mps)][1]['LAST_NAME']  . ' - ' . $QUART_RET[1]['TITLE'] . ' - ' .  UserSyear() . '-' . (UserSyear()+1);
                    $filename=html_entity_decode($filename); 
                    PDFStop($handle);
                }
            }

            #################end####################################### 
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

        $extra['extra_header_left'] = '<h5 class="text-primary no-margin-top">'._includeOnReportCard.':</h5>';
        $extra['extra_header_left'] .= '<div class="row">';
        $extra['extra_header_left'] .= '<div class="col-md-6 col-lg-4"><div class="form-group"><div class="checkbox checkbox-switch switch-success switch-xs"><label><INPUT type=checkbox name=elements[teacher] value=Y CHECKED><span></span>'._teacher.'</label></div></div></div>';
        $extra['extra_header_left'] .= '<div class="col-md-6 col-lg-4"><div class="form-group"><div class="checkbox checkbox-switch switch-success switch-xs"><label><INPUT type=checkbox name=elements[signature] value=Y CHECKED><span></span>'._includeSignatureLine.'</label></div></div></div>';
        $extra['extra_header_left'] .= '<div class="col-md-6 col-lg-4"><div class="form-group"><div class="checkbox checkbox-switch switch-success switch-xs"><label><INPUT type=checkbox name=elements[comments] value=Y CHECKED><span></span>'._comments.'</label></div></div></div>';
        $extra['extra_header_left'] .= '<div class="col-md-6 col-lg-4"><div class="form-group"><div class="checkbox checkbox-switch switch-success switch-xs"><label><INPUT type=checkbox name=elements[percents] value=Y CHECKED><span></span>'._percents.'</label></div></div></div>';
        $extra['extra_header_left'] .= '<div class="col-md-6 col-lg-4"><div class="form-group"><div class="checkbox checkbox-switch switch-success switch-xs"><label><INPUT type=checkbox name=elements[ytd_absences] value=Y CHECKED><span></span>'._yearToDateDailyAbsences.'</label></div></div></div>';
        $extra['extra_header_left'] .= '<div class="col-md-6 col-lg-4"><div class="form-group"><div class="checkbox checkbox-switch switch-success switch-xs"><label><INPUT type=checkbox name=elements[mp_absences] value=Y CHECKED' . (GetMP(UserMP(), 'SORT_ORDER') != 1 ? ' CHECKED' : '') . '><span></span>'._dailyAbsencesThisMarkingPeriod.'</label></div></div></div>';
        //$extra['extra_header_left'] .= '<div class="col-md-6 col-lg-4 form-inline"><div class="form-group"><div class="checkbox checkbox-switch switch-success switch-xs"><label><INPUT type=checkbox name=elements[ytd_tardies] value=Y><span></span>'._otherAttendanceYearToDate.' :</label></div> <SELECT name="ytd_tardies_code" class="form-control input-xs">';
        //foreach ($attendance_codes as $code)
        //    $extra['extra_header_left'] .= '<OPTION value=' . $code['ID'] . '>' . $code['SHORT_NAME'] . '</OPTION>';
        //$extra['extra_header_left'] .= '</SELECT></div></div>';
        //$extra['extra_header_left'] .= '<div class="col-md-6 col-lg-4 form-inline"><div class="form-group"><div class="checkbox checkbox-switch switch-success switch-success switch-xs"><label><INPUT type=checkbox name=elements[mp_tardies] value=Y><span></span>'._otherAttendanceThisMarkingPeriod.':</label></div> <SELECT class="form-control input-xs" name="mp_tardies_code">';
        //foreach ($attendance_codes as $code)
        //    $extra['extra_header_left'] .= '<OPTION value=' . $code['ID'] . '>' . $code['SHORT_NAME'] . '</OPTION>';
        //$extra['extra_header_left'] .= '</SELECT></div></div>';
        //$extra['extra_header_left'] .= '<div class="col-md-6 col-lg-4"><div class="form-group"><div class="checkbox checkbox-switch switch-success switch-xs"><label><INPUT type=checkbox name=elements[period_absences] value=Y><span></span>'._periodByPeriodAbsences.'</label></div></div></div>';
        $extra['extra_header_left'] .= '<div class="col-md-6 col-lg-4"><div class="form-group"><div class="checkbox checkbox-switch switch-success switch-xs"><label><INPUT type=checkbox name=elements[publish_report] value=Y><span></span>'._publish_report.'</label></div></div></div>';
        //$extra['extra_header_left'] .= '</div>';

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
        //if ($sem) {
        //    $fy = GetParentMP('FY', $sem);
        //    if (GetMP($fy, 'DOES_EXAM') == 'Y')
        //        $extra['extra_header_left'] .= '<label class="checkbox-inline"><INPUT class="styled" type=checkbox name=mp_arr[] value=E' . $fy . ' onclick="reportCardGpaChk();">' . GetMP($fy, 'SHORT_NAME') . ' Exam</label>';
        //    if (GetMP($fy, 'DOES_GRADES') == 'Y')
        //        $extra['extra_header_left'] .= '<label class="checkbox-inline"><INPUT class="styled" type=checkbox name=mp_arr[] value=' . $fy . ' onclick="reportCardGpaChk();">' . GetMP($fy, 'SHORT_NAME') . '</label>';
        //}
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
    //    $extra['columns_before'] = array('CHECKBOX' => '</A><INPUT type=checkbox value=Y name=controller checked onclick="checkAll(this.form,this.form.controller.checked,\'st_arr\');"><A>');
    // $extra['columns_before'] = array('CHECKBOX' => '</A><INPUT type=checkbox value=Y name=controller onclick="checkAll(this.form,this.form.controller.checked,\'st_arr\');"><A>');
    $extra['columns_before'] = array('CHECKBOX' => '</A><INPUT type=checkbox value=Y name=controller onclick="checkAllDtMod(this,\'st_arr\');"><A>');
    $extra['options']['search'] = false;
    $extra['new'] = true;


    // echo "<pre><xmp>";
    // print_r($extra);
    // echo "</xmp></pre>";

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

function _makeChooseCheckbox($value, $title)
{
    global $THIS_RET;


    // return '<INPUT type=checkbox name=st_arr[] value=' . $value . '>';

    return "<input name=unused_var[$THIS_RET[STUDENT_ID]] value=" . $THIS_RET['STUDENT_ID'] . "  type='checkbox' id=$THIS_RET[STUDENT_ID] onClick='setHiddenCheckboxStudents(\"st_arr[$THIS_RET[STUDENT_ID]]\",this,$THIS_RET[STUDENT_ID]);' />";
}

function _makeTeacher($teacher, $column)
{

    $TEACHER_NAME = DBGet(DBQuery("SELECT concat(first_name,' ',last_name) as name from staff where staff_id=$teacher"));

    return $TEACHER_NAME[1]['NAME'];
}

//#####################################################//
//### CADO CUSTOM REPORT CARD
//#####################################################//

function CadoStudentGrades($mgrades_RET, $student_id, $columns,$grade_id,$mp,$last_mp) {
    $markingPeriod = DBGet(DBQuery('SELECT * FROM school_quarters WHERE SYEAR=\'' . UserSyear() . '\' AND SCHOOL_ID=\'' . UserSchool() . '\' AND SORT_ORDER=255 '));
    $QUART_RET=DBGet(DBQuery('SELECT * from school_quarters WHERE SCHOOL_ID=\'' . UserSchool() . '\' AND SYEAR=\'' . UserSyear() . '\' AND MARKING_PERIOD_ID=\'' . $last_mp . '\''));  
    if($QUART_RET[1]['MARKING_PERIOD_ID'] == $markingPeriod[1]['MARKING_PERIOD_ID']){
        CadoStudentCommunication($mgrades_RET, $student_id, $columns,$grade_id,$mp,$last_mp);
        return;
    }
    $year=UserSyear();
    if(strpos($grade_id,"Primaire")){
         $year--;
         $cycle_count=2;
    } else $cycle_count=1;
    while($cycle_count--){
    $quarters_RET=DBGet(DBQuery("SELECT MARKING_PERIOD_ID,TITLE FROM school_quarters  WHERE SYEAR='". $year . '" AND TITLE="'. $col . "' AND SCHOOL_ID='". UserSchool() . "' ORDER BY SORT_ORDER"), array(), array());
    //echo "<pre>";print_r($mgrades_RET);echo "</pre>";
    // Build array with results , [quarter][result] the results are one per quarter and last one is total
    $type_names=array();
    $tot_grade=array();
    $type_ids=array();
    $type_weight=array();
    $tot_missing_weight=array();
    $total_weight=array();
    $tot_grade_type=array();
    $course=0;
    if (count($mgrades_RET)) {
        foreach ($mgrades_RET as $key=> $sgrades_RET) {
            //echo '-------START-------------'7
            if($year!=UserSyear()){
                $search = substr(html_entity_decode($sgrades_RET['SHORT']), 0, 6);
                $search .= '%';
                $search .= substr($grade_id, 12, 2);
                $search .= '%';
                $search .= substr($grade_id, 19, 1) -1;
                $search .= '%';
                $course_temp=DBGet(DBQuery('SELECT * FROM course_periods WHERE short_name like "' . $search . '" and SYEAR='. $year .''));
                $sgrades_RET['COURSE_ID']=$course_temp[1]['COURSE_PERIOD_ID'];
            }
            unset($student_points);
            unset($total_points);
            unset($percent_weights);
            $course_period_id=$sgrades_RET['COURSE_ID'];
            $course_id_RET=DBGet(DBQuery('SELECT cp.COURSE_ID,  c.SHORT_NAME, c.TITLE FROM course_periods cp,courses c WHERE c.COURSE_ID=cp.COURSE_ID AND cp.COURSE_PERIOD_ID=\''. $course_period_id . '\''));
            $course_title=$course_id_RET[1]['SHORT_NAME'];
            $course_id=$course_id_RET[1]['COURSE_ID'];
            $current_quart=0;

            foreach ($quarters_RET as $key=> $quart) {
                //echo '-------QUARTERS--------';
                if($quart['TITLE'] > $mp && $year==UserSyear() || ! $course_period_id)
                    break;
                $course_periods=DBGet(DBQuery('select marking_period_id from course_periods where course_period_id='. $course_period_id));
                if ($course_periods[1]['MARKING_PERIOD_ID']==NULL) {
                    $assignment_type_ids=DBGet(DBQuery('SELECT group_concat(distinct(assignment_type_id)) AS assignment_type_ids FROM gradebook_assignments a JOIN gradebook_grades g ON (a.ASSIGNMENT_ID = g.ASSIGNMENT_ID AND g.STUDENT_ID=\''. $student_id . '\' AND g.COURSE_PERIOD_ID=\''. $course_period_id . '\') WHERE (a.COURSE_PERIOD_ID=\''. $course_period_id . '\' OR a.COURSE_ID=\''. $course_id . '\' AND a.STAFF_ID=\''. User('STAFF_ID') . '\') AND (a.MARKING_PERIOD_ID=\''. UserMP() . '\' OR a.MARKING_PERIOD_ID=\''. $fy_mp_id . '\')'));
                    $assignment_type_weight=$assignment_type_weight[1]['FINAL_GRADE_PERCENT'];
                    $assignment_weight=DBGet(DBQuery('SELECT ASSIGNMENT_WEIGHT AS ASSIGNMENT_WEIGHT FROM gradebook_assignments WHERE assignment_type_id IN ('.$assignment_type_ids[1]['ASSIGNMENT_TYPE_IDS'].')'));
                    $assignment_weight=$assignment_weight[1]['ASSIGNMENT_WEIGHT'];
                    $school_years=DBGet(DBQuery('select marking_period_id from  school_years where  syear='. $year . ' and school_id='. UserSchool()));
                    $fy_mp_id=$school_years[1]['MARKING_PERIOD_ID'];
                    $sql='SELECT a.TITLE,t.TITLE AS ASSIGN_TYP,a.ASSIGNED_DATE,a.DUE_DATE, t.ASSIGNMENT_TYPE_ID, t.FINAL_GRADE_PERCENT AS WEIGHT_GRADE  , a.ASSIGNMENT_WEIGHT as ASSIGN_WEIGHT,   (t.FINAL_GRADE_PERCENT / "'.$assignment_type_weight.'") as FINAL_GRADE_PERCENT, t.FINAL_GRADE_PERCENT as ASSIGN_TYP_WG,g.POINTS,a.POINTS AS TOTAL_POINTS,g.COMMENT,g.POINTS AS LETTER_GRADE,g.POINTS AS LETTERWTD_GRADE,CASE WHEN (a.ASSIGNED_DATE IS NULL OR CURRENT_DATE>=a.ASSIGNED_DATE) AND (a.DUE_DATE IS NULL OR CURRENT_DATE>=a.DUE_DATE) THEN \'Y\' ELSE NULL END AS DUE FROM gradebook_assignment_types t,gradebook_assignments a LEFT OUTER JOIN gradebook_grades g ON (a.ASSIGNMENT_ID=g.ASSIGNMENT_ID AND g.STUDENT_ID=\''. $student_id . '\' AND g.COURSE_PERIOD_ID=\''. $course_period_id . '\') WHERE   a.ASSIGNMENT_TYPE_ID=t.ASSIGNMENT_TYPE_ID AND (a.COURSE_PERIOD_ID=\''. $course_period_id . '\' OR a.COURSE_ID=\''. $course_id . '\' AND a.STAFF_ID=\''. User('STAFF_ID') . '\') AND t.COURSE_ID=\''. $course_id . '\' AND (a.MARKING_PERIOD_ID=\''. $quart['MARKING_PERIOD_ID'] . '\' OR a.MARKING_PERIOD_ID=\''. $fy_mp_id . '\')';
                }

                else {
                    $assignment_type_ids=DBGet(DBQuery('SELECT group_concat(distinct(assignment_type_id)) AS assignment_type_ids FROM gradebook_assignments a JOIN gradebook_grades g ON (a.ASSIGNMENT_ID = g.ASSIGNMENT_ID AND g.STUDENT_ID=\''.$student_id . '\' AND g.COURSE_PERIOD_ID=\''. $course_period_id . '\') WHERE (a.COURSE_PERIOD_ID=\''. $course_period_id . '\' OR a.COURSE_ID=\''. $course_id . '\' AND a.STAFF_ID=\''. User('STAFF_ID') . '\') AND (a.MARKING_PERIOD_ID=\''. UserMP() . '\')'));

                    if ( !$assignment_type_ids[1]['ASSIGNMENT_TYPE_IDS']) {
                        //echo "<p style='color:red'><b>". _noGradesWereFound . "</b></p>";
                        //return;
                    }
                    else{
                    $assignment_type_weight=DBGet(DBQuery('SELECT SUM(FINAL_GRADE_PERCENT) AS FINAL_GRADE_PERCENT FROM gradebook_assignment_types WHERE assignment_type_id IN ('.$assignment_type_ids[1]['ASSIGNMENT_TYPE_IDS'].')'));
                    $assignment_type_weight=$assignment_type_weight[1]['FINAL_GRADE_PERCENT'];
                    $assignment_weight=DBGet(DBQuery('SELECT ASSIGNMENT_WEIGHT AS ASSIGNMENT_WEIGHT FROM gradebook_assignments WHERE assignment_type_id IN ('.$assignment_type_ids[1]['ASSIGNMENT_TYPE_IDS'].')'));
                    $assignment_weight=$assignment_weight[1]['ASSIGNMENT_WEIGHT'];
                    }
                    $sql='SELECT a.TITLE,t.TITLE AS ASSIGN_TYP,a.ASSIGNED_DATE,a.DUE_DATE,  t.ASSIGNMENT_TYPE_ID,   t.FINAL_GRADE_PERCENT AS WEIGHT_GRADE  , a.ASSIGNMENT_WEIGHT as ASSIGN_WEIGHT, g.COMMENT as COMMENT, (t.FINAL_GRADE_PERCENT / "'.$assignment_type_weight.'") as FINAL_GRADE_PERCENT, t.FINAL_GRADE_PERCENT as ASSIGN_TYP_WG,g.POINTS,a.POINTS AS TOTAL_POINTS,g.COMMENT,g.POINTS AS LETTER_GRADE,g.POINTS AS LETTERWTD_GRADE,CASE WHEN (a.ASSIGNED_DATE IS NULL OR CURRENT_DATE>=a.ASSIGNED_DATE) AND (a.DUE_DATE IS NULL OR CURRENT_DATE>=a.DUE_DATE) THEN \'Y\' ELSE NULL END AS DUE FROM gradebook_assignment_types t,gradebook_assignments a LEFT OUTER JOIN gradebook_grades g ON (a.ASSIGNMENT_ID=g.ASSIGNMENT_ID AND g.STUDENT_ID=\''. $student_id . '\' AND g.COURSE_PERIOD_ID=\''. $course_period_id . '\') WHERE   a.ASSIGNMENT_TYPE_ID=t.ASSIGNMENT_TYPE_ID AND (a.COURSE_PERIOD_ID=\''. $course_period_id . '\' OR a.COURSE_ID=\''. $course_id . '\' AND a.STAFF_ID=\''. User('STAFF_ID') . '\') AND t.COURSE_ID=\''. $course_id . '\' AND a.MARKING_PERIOD_ID=\''. $quart['MARKING_PERIOD_ID'] . '\'';
                }

                if ($_REQUEST['exclude_notdue']=='Y') $sql .=' AND ((a.ASSIGNED_DATE IS NULL OR CURRENT_DATE>=a.ASSIGNED_DATE) AND (a.DUE_DATE IS NULL OR CURRENT_DATE>=DUE_DATE) OR g.POINTS IS NOT NULL)';
                if ($_REQUEST['exclude_ec']=='Y') $sql .=' AND (a.POINTS!=\'0\' OR g.POINTS IS NOT NULL AND g.POINTS!=\'-1\')';
                $sql .='  ORDER BY ASSIGN_TYP';
                $grades_RET=DBGet(DBQuery($sql), array('ASSIGNED_DATE'=> '_removeSpaces', 'ASSIGN_TYP_WG'=> '_makeAssnWG', 'ASSIGN_WEIGHT'=> '_makeAssgnmtWtg', 'DUE_DATE'=> '_removeSpaces', 'TITLE'=> '_removeSpaces', 'POINTS'=> '_makeExtra', 'LETTER_GRADE'=> '_makeExtra', 'WEIGHT_GRADE'=> '_makeWtg'));
                $sum_points=$sum_percent=0;
                $flag=false;
                $type_index=0;
                $last_index=0;
                foreach ($grades_RET as $key=> $val) {
                    if($val['ASSIGN_WEIGHT'] == ' %')
                    $val['ASSIGN_WEIGHT']=0;
                    //echo '--------------------grade-------------------';
                    //print_r($grades_RET);
                    $tot_grade_type[$course][$current_quart][$val['ASSIGNMENT_TYPE_ID']]+=$val['POINTS'] / $val['TOTAL_POINTS'] * ((($val['ASSIGN_WEIGHT'] * $val['ASSIGN_TYP_WG'])) / 100);
                    $name=DBGet(DBQuery('SELECT TITLE FROM gradebook_assignment_types WHERE assignment_type_id = "'. $val['ASSIGNMENT_TYPE_ID'] . '"'));
                    $type_names[$course][$val['ASSIGNMENT_TYPE_ID']]=$name[1]['TITLE'];
                    $assignment_type_id_count=DBGet(DBQuery('SELECT distinct(assignment_type_id) AS assignment_type_ids FROM gradebook_assignments a JOIN gradebook_grades g ON (a.ASSIGNMENT_ID = g.ASSIGNMENT_ID AND g.STUDENT_ID=\''.$student_id . '\' AND g.COURSE_PERIOD_ID=\''. $course_period_id . '\') WHERE (a.COURSE_PERIOD_ID=\''. $course_period_id . '\' OR a.COURSE_ID=\''. $course_id . '\' AND a.STAFF_ID=\''. User('STAFF_ID') . '\') AND (a.MARKING_PERIOD_ID=\''. UserMP() . '\')'));
                    if ($val['WEIGHT_GRADE'] != 'N/A')
                        $total_weight[$course][$current_quart][$val['ASSIGNMENT_TYPE_ID']] += ($val['ASSIGN_WEIGHT'] * $val['ASSIGN_TYP_WG']) / 100; 
                    if($last_index != $val['ASSIGNMENT_TYPE_ID']){
                        $type_ids[$course][$type_index]=$val['ASSIGNMENT_TYPE_ID']; 
                        $type_weight[$course][$type_index]=$val['ASSIGN_TYP_WG']; 
                        //ROGER
                        $type_index++;
                    }
                    $last_index=$val['ASSIGNMENT_TYPE_ID'];
                }
                $current_quart++;
            }
            $course++;
        }

    }

    // Build array with results
    $course_arr=array();
    $column_arr=array();
    $results_arr=array();
    $results_raw_arr=array();
    $result_diff=array();
    $comment_arr=array();
    $course=0;
    $grand_total=array();
    $group_grand_total=array();
    $group_current_quart=array();
    $percent_on='';
    if (isset($_REQUEST['elements']['percents']))
        $percent_on='%';
    if (count($mgrades_RET)) {
        foreach ($mgrades_RET as $key=> $sgrades_RET) {
            //echo'---- COURSE -----';
            if($year!=UserSyear()){
                $search = substr(html_entity_decode($sgrades_RET['SHORT']), 0, 6);
                $search .= '%';
                $search .= substr($grade_id, 12, 2);
                $search .= '%';
                $search .= substr($grade_id, 19, 1) -1;
                $search .= '%';
                $course_temp=DBGet(DBQuery('SELECT * FROM course_periods WHERE short_name like "' . $search . '" and SYEAR='. $year .''));
                $sgrades_RET['COURSE_ID']=$course_temp[1]['COURSE_PERIOD_ID'];
            }
            $course_arr[$course]['TITLE']= $sgrades_RET['COURSE_TITLE'];
            $course_arr[$course]['TEACHER']= _teacher . ' :' . $sgrades_RET['TEACHER'];
            $course_arr[$course]['STUDENT_GRADE']= $grade_id;
            $course_period_id=$sgrades_RET['COURSE_ID'];
            $course_id=DBGet(DBQuery('SELECT cp.COURSE_ID,  c.SHORT_NAME, c.TITLE FROM course_periods cp,courses c WHERE c.COURSE_ID=cp.COURSE_ID AND cp.COURSE_PERIOD_ID=\''. $course_period_id . '\''));
            $course_title=$course_id[1]['SHORT_NAME'];
            $course_id=$course_id[1]['COURSE_ID'];
            $course_arr[$course]['COURSE_#']= _course . ' :' . $course_title;
            $teacher_id=$sgrades_RET['TEACHER_ID'];
            $config_RET2 = DBGet(DBQuery('SELECT VALUE FROM program_user_config WHERE USER_ID=2 AND PROGRAM=\'Gradebook\' AND TITLE=\'FY-E1\' ORDER BY last_updated DESC LIMIT 1'));
            $exam_percent[$course] = explode('_', $config_RET2[1]['VALUE']);
            $exam_percent[$course] = $exam_percent[$course][0];
            if($exam_percent[$course]){
                $sql='SELECT GRADE_PERCENT FROM student_report_card_grades WHERE COURSE_PERIOD_ID=\'' . $course_period_id . '\' AND MARKING_PERIOD_ID=\'E1\' AND STUDENT_ID=\''.$student_id . '\'';
                $grade=DBGet(DBQuery($sql));
                $exam_value[$course]=$grade[1]['GRADE_PERCENT'];
            }
            $current_quart=0;
            $loop=0;
            $type_array_index=0;
            $column_arr[$course][$loop++]=_assgnmentType;
            foreach ($quarters_RET as $key=> $quart) {
                $column_arr[$course][$loop++] =  $quart['TITLE'] ;
            }
            // Debut resulats final types de devoir
            $column_arr[$course]['FINAL'] = _finalResult ;
            $comment_arr[$course]['COMMENT_TITLE'] = _comments;
            $comment_arr[$course]['COMMENT'] = $sgrades_RET['COMMENT'];
            $total_current_quart=array();
            $type_index=0;
            foreach ($type_ids[$course] as $key=> $val) {
                $current_quart=0;
                $active_quarts=0;
                $results_arr[$course][$type_index]['TYPE'] = $type_names[$course][$val];
                 foreach ($quarters_RET as $key=> $quart) {
                    $assign_total_id_weigth[$course][$current_quart]=0;
                  if($quart['TITLE'] > $mp && $year==UserSyear())
                    break;
                    $config_RET = DBGet(DBQuery('SELECT VALUE FROM program_user_config WHERE USER_ID=2 AND PROGRAM=\'Gradebook\' AND TITLE=\'FY-'.$quart['MARKING_PERIOD_ID'].'\' ORDER BY last_updated DESC LIMIT 1'));
                    //echo '-------------RESULT-------------------';
                    if($tot_grade_type[$course][$current_quart][$val]){
                    //if(!is_nan($tot_grade_type[$course][$current_quart][$val])){
                        $tot_grade_type[$course][$current_quart][$val]  = ($tot_grade_type[$course][$current_quart][$val] / 100) / $total_weight[$course][$current_quart][$val] * 100;
                        $total_current_quart[$current_quart]+=$tot_grade_type[$course][$current_quart][$val] * $type_weight[$course][$type_index] ;
                        $results_arr[$course][$type_array_index]['RESULT'][$active_quarts]=_makeLetterGrade($tot_grade_type[$course][$current_quart][$val] ,  $course_period_id, $teacher_id, $percent_on);
                        $results_raw_arr[$course][$type_array_index]['RESULT'][$active_quarts]=$tot_grade_type[$course][$current_quart][$val]*100;
                        $assign_total_id_weigth[$course][$current_quart]+=$type_weight[$course][$type_index];
                    }
                    else $results_arr[$course][$type_array_index]['RESULT'][$active_quarts]=''; //Resultas TI types de devoir
                    $active_quarts++;
                    $current_quart++;
                }
                $type_index++;
                $type_array_index++;
            }

            $current_quart=0;
            foreach ($quarters_RET as $key=> $quart) {
                if($quart['TITLE'] > $mp && $year==UserSyear())
                    break;
                    //echo $assign_total_id_weigth[$course][$current_quart];
                    if($assign_total_id_weigth[$course][$current_quart])
                        $total_current_quart[$current_quart]=$total_current_quart[$current_quart]*100/$assign_total_id_weigth[$course][$current_quart];
                    $current_quart++;
            }
            // Fin resulats final types de devoir

            // Debut resultat disciplinaire
            if($type_ids[$course])
                if(count($type_ids[$course])==1) $type_array_index--;
            $current_quart=0;    
            $grand_total=array();
            $grand_total_weight[$course]=0;
            $results_arr[$course][$type_array_index]['TYPE'] = _studentAverage;    
                foreach ($quarters_RET as $key=> $quart) {
                    if($quart['TITLE'] > $mp && $year==UserSyear())
                    break;
                    $config_RET = DBGet(DBQuery('SELECT VALUE FROM program_user_config WHERE USER_ID=2 AND PROGRAM=\'Gradebook\' AND TITLE=\'FY-'.$quart['MARKING_PERIOD_ID'].'\' ORDER BY last_updated DESC LIMIT 1'));
                    if(!is_nan($total_current_quart[$current_quart])){
                        if($total_current_quart[$current_quart]){
                        $sql='SELECT GRADE_PERCENT FROM student_report_card_grades WHERE COURSE_PERIOD_ID=\'' . $course_period_id . '\' AND MARKING_PERIOD_ID=\''.  $quart['MARKING_PERIOD_ID'] . '\' AND STUDENT_ID=\''.$student_id . '\'';
                        $grade=DBGet(DBQuery($sql));
                        $final_admim_grade=round($grade[1]['GRADE_PERCENT']);
                        $diff = $final_admim_grade - round($total_current_quart[$current_quart]*$assign_total_id_weigth[$course][$current_quart]/100);
                        $type_index=0;
                        if (! $final_admim_grade)
                        $final_admim_grade=round($total_current_quart[$current_quart]*$assign_total_id_weigth[$course][$current_quart]/100);
                                foreach ($type_ids[$course] as $key=> $val) {
                                $result_diff[$course][$current_quart]['RESULTDIFF'] = round($diff);                                
                                $weigth = $type_weight[$course][$type_index] / 100 ;
                                $markup = round($diff * $weigth);
                                if($results_arr[$course][$type_index]['RESULT'][$current_quart]){
                                    if(round((($results_arr[$course][$type_index]['RESULT'][$current_quart] * $weigth + $markup) * 100  / $weigth ) /100) <= 100)
                                        $results_arr[$course][$type_index]['RESULT'][$current_quart] = round((($results_arr[$course][$type_index]['RESULT'][$current_quart] * $weigth + $markup) * 100  / $weigth ) /100);
                                }
                                else $type_index++;
                                $total_current_quart[$current_quart] = $final_admim_grade ;
                                if($weigth)
                                    $results_raw_arr[$course][$type_index]['RESULT'][$current_quart] = round((($results_raw_arr[$course][$type_index]['RESULT'][$current_quart] * $weigth + $markup) * 100  / $weigth ) /100);
                                $type_index++;
                                }
                        $type_index=0;
                        $results_arr[$course][$type_array_index]['RESULT'][$current_quart] = _makeLetterGrade($total_current_quart[$current_quart] / 100 ,  $course_period_id, $teacher_id, $percent_on);
                        $grand_total[$course]+=$results_arr[$course][$type_array_index]['RESULT'][$current_quart] / 100 * substr($config_RET[1]['VALUE'], 0, strpos($config_RET[1]['VALUE'], "_"));
                        $grand_total_weight[$course] +=substr($config_RET[1]['VALUE'], 0, strpos($config_RET[1]['VALUE'], "_"));
                    }
                }else $results_arr[$course][$type_array_index]['RESULT'][$current_quart] = '';
                if($results_arr[$course][$type_array_index]['RESULT'][$current_quart] == '')
                        $results_arr[$course][$type_array_index]['RESULT'][$current_quart] = ''; // Resultat disciplinaire 
                $current_quart++;
                }   

                if($exam_value[$course]){
                    $results_arr[$course][$type_array_index]['RESULT'][$current_quart] = round($exam_value[$course]);
                    $grand_total[$course]+=$exam_value[$course] * $exam_percent[$course] / 100;;
                    $grand_total_weight[$course]+=$exam_percent[$course];
                }
                if($grand_total_weight[$course])
                    $grand_total[$course]=$grand_total[$course] * 100 / $grand_total_weight[$course];
            // fin resultat disciplinaire

            // Debut resultats de type de devoir
            $type_index=0;
                foreach ($type_ids[$course] as $key=> $val) {
                $total_all_quarts=0;
                $total_weight_all_quarts=0;
                $current_quart=0;
                foreach ($quarters_RET as $key=> $quart) {
                    if($quart['TITLE'] > $mp && $year==UserSyear())
                    break;
                        $config_RET = DBGet(DBQuery('SELECT VALUE FROM program_user_config WHERE USER_ID=2 AND PROGRAM=\'Gradebook\' AND TITLE=\'FY-'.$quart['MARKING_PERIOD_ID'].'\' ORDER BY last_updated DESC LIMIT 1'));
                        if($tot_grade_type[$course][$current_quart][$val]){
                            $total_weight_all_quarts+=substr($config_RET[1]['VALUE'], 0, strpos($config_RET[1]['VALUE'], "_"));
                            $total_all_quarts+=$results_raw_arr[$course][$type_index]['RESULT'][$current_quart] /100 * substr($config_RET[1]['VALUE'], 0, strpos($config_RET[1]['VALUE'], "_"));
                        }
                    $current_quart++;
                }
                if($total_weight_all_quarts)
                    $results_arr[$course][$type_index]['RESULT']['FINAL'] =  _makeLetterGrade($total_all_quarts / $total_weight_all_quarts   ,  $course_period_id, $teacher_id, $percent_on);
                if ($results_arr[$course][$type_index]['RESULT']['FINAL'] > 100)
                    $results_arr[$course][$type_index]['RESULT']['FINAL'] =100;
                if ($results_arr[$course][$type_index]['RESULT']['FINAL'] == '' )
                    $results_arr[$course][$type_index]['RESULT']['FINAL'] = 'TI'; // Resultat final types de devoirs
                $type_index++;
            }
            if($grand_total[$course] && ! $results_arr[$course][$type_array_index]['RESULT']['FINAL'])
              $results_arr[$course][$type_array_index]['RESULT']['FINAL'] = _makeLetterGrade($grand_total[$course] / 100 ,  $course_period_id, $teacher_id, $percent_on );
            if($results_arr[$course][$type_array_index]['RESULT']['FINAL'] == '')
                $results_arr[$course][$type_array_index]['RESULT']['FINAL'] = 'TI'; // Resultats disciplinaire final
            //Fin resultats de type de devoir

            // Debut resulats moyenne groupe
            $group_current_quart[$course]=0;
            $group_grand_total[$course]=0;
            $type_array_index++;
            $results_arr[$course][$type_array_index]['TYPE'] = _groupAverage;
            $total_weight_all_quarts=0;
            foreach ($quarters_RET as $key=> $quart) {
                if($quart['TITLE'] > $mp && $year==UserSyear())
                break;
                $config_RET = DBGet(DBQuery('SELECT VALUE FROM program_user_config WHERE USER_ID=2 AND PROGRAM=\'Gradebook\' AND TITLE=\'FY-'.$quart['MARKING_PERIOD_ID'].'\' ORDER BY last_updated DESC LIMIT 1'));
                $assignment_type_id_count=DBGet(DBQuery('SELECT distinct(assignment_type_id) AS assignment_type_ids FROM gradebook_assignments a JOIN gradebook_grades g ON (a.ASSIGNMENT_ID = g.ASSIGNMENT_ID AND g.STUDENT_ID=\''.$student_id . '\' AND g.COURSE_PERIOD_ID=\''. $course_period_id . '\') WHERE (a.COURSE_PERIOD_ID=\''. $course_period_id . '\' OR a.COURSE_ID=\''. $course_id . '\' AND a.STAFF_ID=\''. User('STAFF_ID') . '\') AND (a.MARKING_PERIOD_ID=\''. UserMP() . '\')'));
                $quart_total=GetGroupAverage($course_id,$course_period_id,$quart['MARKING_PERIOD_ID'],$year);
                if($quart_total > 0) {
                    $total_weight_all_quarts+=substr($config_RET[1]['VALUE'], 0, strpos($config_RET[1]['VALUE'], "_"));
                    // Group average
                    $results_arr[$course][$type_array_index]['RESULT'][$group_current_quart[$course]] = _makeLetterGrade($quart_total / 100,  $course_period_id, $teacher_id, $percent_on);
                    $group_grand_total[$course]+=$quart_total * substr($config_RET[1]['VALUE'], 0, strpos($config_RET[1]['VALUE'], "_"));
                }
                $group_current_quart[$course]++;
            }
            if($total_weight_all_quarts)
                $results_arr[$course][$type_array_index]['RESULT']['FINAL'] =  _makeLetterGrade($group_grand_total[$course] / $total_weight_all_quarts /100 ,  $course_period_id, $teacher_id, $percent_on) ;  
            //Fin resulats moyenne groupe

            $course++;
        }        
    }
    $primaire_cycle_course[$year]=$course_arr;
    $primaire_cycle_results[$year]=$results_arr;
    /////// FOR TESTING PURPOSE SINCE WE DONT HAVE 2021 RESULTS
    //$primaire_cycle_course[$year-1]=0;
    //$primaire_cycle_results[$year-1]=0;
    ///////
    $year++;
    }
    if(strpos($grade_id,"Primaire")) // Primaire
        CadoHTMLresultatsPrimaire(_reportcard_cat2,$primaire_cycle_course,$column_arr,$primaire_cycle_results,$comment_arr,$result_diff,UserSyear(),$grade_id,$exam_value);
        else if(strpos($grade_id,"scolaire")) /// Préscolaire 
        CadoHTMLresultatsPrescolaire(_reportcard_cat2,$course_arr,$column_arr,$results_arr,$comment_arr,$result_diff,$exam_value);
        else CadoHTMLresultatsSecondaire(_reportcard_cat2,$course_arr,$column_arr,$results_arr,$comment_arr,$result_diff,$exam_value);
}

function CadoHeader($student_id, $grade_id,$attendance_day_RET,$last_mp,$mp) {

    $columns=array();
    $data=array();
    $SCHOOL_RET=DBGet(DBQuery('SELECT * from schools where ID = \''. UserSchool() . '\''));
    $USER_RET=DBGet(DBQuery('SELECT * from students where STUDENT_ID = \''. $student_id . '\''));
    $ADDRESS_RET=DBGet(DBQuery('SELECT * from student_address where STUDENT_ID = \''. $student_id . '\'  AND TYPE = \'PRIMARY\' '));
    $PRIMARY_RET=DBGet(DBQuery('SELECT * from students_join_people where STUDENT_ID = \''. $student_id . '\'  AND EMERGENCY_TYPE = \'PRIMARY\' '));
    $PRIMARYNAME_RET=DBGet(DBQuery('SELECT * from people where STAFF_ID = \''. $PRIMARY_RET[1]['PERSON_ID'] . '\''));
    $QUART_RET=DBGet(DBQuery('SELECT * from school_quarters WHERE SCHOOL_ID=\'' . UserSchool() . '\' AND SYEAR=\'' . UserSyear() . '\' AND MARKING_PERIOD_ID=\'' . $last_mp . '\''));  
    $column['SCHOOL_NAME']=_schoolName;
    $column['SCHOOL_CODE']=_schoolCode;
    $column['SCHOOL_PRINCIPAL']=_principal;
    $column['SCHOOL_ADDRESS']=_addresses;
    $column['SCHOOL_TEL']=_telephone;
    $column['STUDENT_NAME']=_studentName;
    $column['STUDENT_PERM_ID']=_alternateId;
    $column['STUDENT_ID']=_studentId;
    $column['STUDENT_GRADE']=_studentGrade;
    $column['STUDENT_BIRTHDATE']=_birthdate;
    $column['STUDENT_ABSCENCES_QUARTER']=_dailyAbsencesThis . $QUART_RET[1]['TITLE'];
    $column['STUDENT_ABSCENCES_YEARLY']=_yearToDateDailyAbsences;
    $column['REPORT_OWNER']=_reportOwner;
    $column['REPORT_RELATION']=_relation;
    $column['REPORT_ADDRESS']=_addresses;
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
    $data['STUDENT_NAME']=$USER_RET[1]['FIRST_NAME'] .' '. $USER_RET[1]['LAST_NAME'];
    $data['STUDENT_PERM_ID']=$USER_RET[1]['ALT_ID'];
    $data['STUDENT_ID']=$student_id;
    $data['STUDENT_GRADE']=$grade_id;
    $data['STUDENT_BIRTHDATE']=$USER_RET[1]['BIRTHDATE'];
    if ($_REQUEST['elements']['mp_absences']=='Y') {
        $count=0;
        foreach ($attendance_day_RET[$student_id][$last_mp] as $abs) $count+=1 - $abs['STATE_VALUE'];
        $data['STUDENT_ABSCENCES_QUARTER']=$count;
    }else $data['STUDENT_ABSCENCES_QUARTER']=0;
    if ($_REQUEST['elements']['ytd_absences']=='Y') {
        $count=0;
        foreach ($attendance_day_RET[$student_id] as $mp_abs) foreach ($mp_abs as $abs) $count+=1 - $abs['STATE_VALUE'];
        $data['STUDENT_ABSCENCES_YEARLY']=$count;
    }else $data['STUDENT_ABSCENCES_YEARLY']=0;
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
    );
    $data['REPORT_RELATION']=$translate[$PRIMARY_RET[1]['RELATIONSHIP']];
    $data['REPORT_ADDRESS']=html_entity_decode($ADDRESS_RET[1]['STREET_ADDRESS_1']);
    $data['REPORT_CITY']=$ADDRESS_RET[1]['CITY'];
    $data['REPORT_STATE']=$ADDRESS_RET[1]['STATE'];
    $data['REPORT_ZIPCODE']=$ADDRESS_RET[1]['ZIPCODE'];
    $data['COMMUNICATION_QUARTER']=$QUART_RET[1]['TITLE'];
    $data['COMMUNICATION_STAR_DATE']=$QUART_RET[1]['START_DATE'];
    $data['COMMUNICATION_END_DATE']=$QUART_RET[1]['END_DATE'];
    CadoHTMLHeader(_reportcard_cat1,$column,$data);
}


function CadoStudentCommunication($mgrades_RET, $student_id, $columns,$grade_id,$mp,$last_mp) {

    $course_index=0;
    //print_r($mgrades_RET);
    foreach ($mgrades_RET as $key=> $course){
        $course_id=DBGet(DBQuery('select course_id from course_periods where course_period_id=\''. $course['COURSE_ID'] . '\''));
        $course_name=DBGet(DBQuery('select title,short_name from courses where course_id=\''. $course_id[1]['COURSE_ID'] . '\''));
        $grades_RET=DBGet(DBQuery('SELECT * FROM gradebook_assignments a JOIN gradebook_grades g ON (a.ASSIGNMENT_ID = g.ASSIGNMENT_ID AND g.STUDENT_ID=\''.$student_id . '\' AND g.COURSE_PERIOD_ID=\''. $course['COURSE_ID'] . '\') WHERE (a.COURSE_PERIOD_ID=\''. $course['COURSE_ID'] . '\' OR a.COURSE_ID=\''. $course['COURSE_ID'] . '\' AND a.STAFF_ID=\''. User('STAFF_ID') . '\') AND (a.MARKING_PERIOD_ID=\''. UserMP() . '\') ORDER BY TITLE'));
        $course_arr[$course_index]['TITLE'] = $course['COURSE_TITLE'];
        $course_arr[$course_index]['TEACHER'] = _teacher . ' :' . $course['TEACHER'];
        $course_arr[$course_index]['COURSE_#'] = _course. ' :' . $course_name[1]['SHORT_NAME'];
        $comment_arr[$course_index]['COMMENT_TITLE'] = _comment;
        $comment_arr[$course_index]['COMMENT'] = $course['COMMENT'];
        $grade_index=0;
        foreach ($grades_RET as $key=> $grades){
            $results_arr[$course_index][$grade_index]['TITLE'] = $grades['TITLE'];
            $results_arr[$course_index][$grade_index]['POINTS'] = $grades['POINTS'];
            $grade_index++;
        }
        $grade_index=0;
        $course_index++;
    }
    CadoHTMLcommunication(_reportcard_cat2,$course_arr,$results_arr,$grade_id,$comment_arr);
}

function CadoStudentComments($student_id, $grade_id,$marking_period) {

    $column=array();
    $data=array();
    //$USER_RET=DBGet(DBQuery('SELECT COMMENT1,COMMENT2 from student_report_card_grades where STUDENT_ID = \''. $student_id . '\' AND MARKING_PERIOD_ID=\''.  $marking_period . '\'  '));
    /* convert comments */
    //DBQuery('INSERT CADO_report_card_comments SET MARKING_PERIOD = \''. $marking_period . '\' , STUDENT_ID = \''. $student_id . '\' , com_competences= "'. html_entity_decode($USER_RET[1]['COMMENT1']) . '" , com_general= "'. html_entity_decode($USER_RET[1]['COMMENT2']) . '" '); 
    $USER_RET2=DBGet(DBQuery('SELECT com_competences,com_general from CADO_report_card_comments where STUDENT_ID = \''. $student_id . '\' AND MARKING_PERIOD=\''.  $marking_period . '\'  '));
    $markingPeriod = DBGet(DBQuery('SELECT * FROM school_quarters WHERE SYEAR=\'' . UserSyear() . '\' AND SCHOOL_ID=\'' . UserSchool() . '\' AND SORT_ORDER=255 '));
    ##### ROGER ###
    if($markingPeriod[1]['MARKING_PERIOD_ID'] == $marking_period)
    {
        $column['COMMENTAIRE']=_commentsOther;
        //$data['COMMENTAIRE']=$USER_RET[1]['COMMENT2'];
        $data['COMMENTAIRE']=$USER_RET2[1]['COM_GENERAL'];
        CadoHTMLcommentairesGeneral(_reportcard_cat5,$column,$data);
        return;
    }      
    $column['COMMENTAIRE']=_commentsCompetences;
    //$data['COMMENTAIRE']=$USER_RET[1]['COMMENT1'];
    $data['COMMENTAIRE']=$USER_RET2[1]['COM_COMPETENCES'];
    CadoHTMLcommentairesCompetence(_reportcard_cat3,$column,$data);

    $column['COMMENTAIRE']=_commentsOther;
    //$data['COMMENTAIRE']=$USER_RET[1]['COMMENT2'];
    $data['COMMENTAIRE']=$USER_RET2[1]['COM_GENERAL'];
    CadoHTMLcommentairesGeneral(_reportcard_cat4,$column,$data);
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



function CadoHTMLHeader($title,$items,$data){

    echo '<pre class="section-title">'; echo $title; echo'</pre>';    
    echo '<table class="section-1">
    <tr>
        <td class="section-1-block">
        <div class="section-1-item">' . $items['SCHOOL_NAME'] . ' : <b>' . $data['SCHOOL_NAME'] . '</b></div>
        <div class="section-1-item">' . $items['SCHOOL_CODE'] . ' : <b>' . $data['SCHOOL_CODE'] . '</b></div>
        <div class="section-1-item">' . $items['SCHOOL_TEL'] . ' : <b>' . $data['SCHOOL_TEL'] . '</b></div>
        <div class="section-1-item">' . $items['SCHOOL_PRINCIPAL'] . ' : <b>' . $data['SCHOOL_PRINCIPAL'] . '</b></div>
        <div class="section-1-item">' . $items['SCHOOL_ADDRESS'] . ' : <b>' . $data['SCHOOL_ADDRESS'] . '</div>
        <div class="section-1-item">&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp<b>' . $data['SCHOOL_CITY'] . ', ' . $data['SCHOOL_STATE'] . '</b></div>
        <div class="section-1-item">&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp<b>'. $data['SCHOOL_ZIPCODE'] . '</b></div>
        </td>

        <td class="section-1-block"> 
        <div class="section-1-item">' . $items['STUDENT_NAME'] . ' : <b>' . $data['STUDENT_NAME'] . '</b></div>
        <div class="section-1-item">' . $items['STUDENT_PERM_ID'] . ' : <b>' . $data['STUDENT_PERM_ID'] . '</b></div>
        <div class="section-1-item">' . $items['STUDENT_ID'] . ' : <b>' . $data['STUDENT_ID'] . '</b></div>
        <div class="section-1-item">' . $items['STUDENT_GRADE'] . ' : <b>' . $data['STUDENT_GRADE'] . '</b></div>
        <div class="section-1-item">' . $items['STUDENT_BIRTHDATE'] . ' : <b>' . $data['STUDENT_BIRTHDATE'] . '</b></div>';
        $temp='';
        if ($_REQUEST['elements']['mp_absences'] == 'Y') {
            echo '
            <div class="section-1-item">' . $items['STUDENT_ABSCENCES_QUARTER'] . ' : <b>' . $data['STUDENT_ABSCENCES_QUARTER'] . '</b></div> ' ;
        }
        else $temp='<div class="section-1-item">&nbsp</div>';
        if ($_REQUEST['elements']['ytd_absences'] == 'Y') {
            echo '
            <div class="section-1-item">' . $items['STUDENT_ABSCENCES_YEARLY'] . ' : <b>' . $data['STUDENT_ABSCENCES_YEARLY'] . '</b></div>';
        }
        else $temp = $temp . '<div class="section-1-item">&nbsp</div>';
        if ($temp) echo $temp;
        echo '</td></tr>
    <tr>
        <td class="section-1-block">
        <div class="section-1-item">' . $items['REPORT_OWNER'] . ' : <b>' . $data['REPORT_OWNER'] . '</b></div>
        <div class="section-1-item">' . $items['REPORT_RELATION'] . ' : <b>' . $data['REPORT_RELATION'] . '</b></div>
        <div class="section-1-item">' . $items['REPORT_ADDRESS'] . ' : <b>' . $data['REPORT_ADDRESS'] . '</b></div>
        <div class="section-1-item">&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp<b>' . $data['REPORT_CITY'] . ', ' . $data['REPORT_STATE'] . '</b></div>
        <div class="section-1-item">&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp<b>' . $data['REPORT_ZIPCODE'] . '</b></div>
        </td>

        <td class="section-1-block">
        <div class="section-1-item">' . $items['COMMUNICATION_QUARTER'] . ' : <b>' . $data['COMMUNICATION_QUARTER'] . '</b></div>
        <div class="section-1-item">' . $items['COMMUNICATION_STAR_DATE'] . ' <b>: ' . $data['COMMUNICATION_STAR_DATE'] . '</b></div>
        <div class="section-1-item">' . $items['COMMUNICATION_END_DATE'] . ' <b>: ' . $data['COMMUNICATION_END_DATE'] . '</b></div>
        <div class="section-1-item">&nbsp</div>
        <div class="section-1-item">&nbsp</div>
        </td>
    </tr>
    </table>';
    //echo "<pre>";print_r($items);echo "</pre>";
    //echo "<pre>";print_r($data);echo "</pre>";
}


function CadoHTMLcommunication($title,$course,$results,$grade_id,$comments){
    global $publish_parents;
    //print_r($course);
    //echo '+++++++++++++++++++++++++++++++++++';
    //print_r($results);
    $percent=1;
    if (isset($_REQUEST['elements']['percents']))$percent=0;
    $numquart=3;
    $colspan=$numquart+1;
    $commentspan=$colspan+1;
    echo '<pre class="section-title">'; echo $title; echo'</pre>';    
    $courseloop=0;
    foreach ($course as $key=> $col) {
        $catloop=0;
        $category=$course[$courseloop];
        foreach ($category as $key=> $cat){
            if($results[$courseloop][$catloop]['POINTS'] > 90){ 
                if($percent)
                    $A[$courseloop][$catloop]=$results[$courseloop][$catloop]['POINTS'];
                else
                    $A[$courseloop][$catloop]='X';
            }elseif($results[$courseloop][$catloop]['POINTS'] > 80){ 
                if($percent)
                    $B[$courseloop][$catloop]=$results[$courseloop][$catloop]['POINTS'];
                else
                    $B[$courseloop][$catloop]='X';
            }elseif ($results[$courseloop][$catloop]['POINTS'] > 70 ){ 
                if($percent)
                    $C[$courseloop][$catloop]=$results[$courseloop][$catloop]['POINTS'];
                else
                    $C[$courseloop][$catloop]='X';
            }elseif($results[$courseloop][$catloop]['POINTS'] > 60 ){
                if($percent)
                    $D[$courseloop][$catloop]=$results[$courseloop][$catloop]['POINTS'];
                else
                    $D[$courseloop][$catloop]='X';
            }
            $catloop++;
        }
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
        <th colspan="' . $colspan  . '" class="class-results__3col-right">' . $grade_id  . '</th>
      </tr>
      <tr>';
      echo'
      <th class="class-results__3col__th">A</th>
      <th class="class-results__3col__th">B</th>
      <th class="class-results__3col__th">C</th>
      <th class="class-results__3col__th">D</th>
      </tr>
    ';
    
 
    $resloop=0;
    foreach ($results[$courseloop] as $key=> $result){
      echo '<tr>
      <td class="class-results--align-right">' . $results[$courseloop][$resloop][TITLE] . ' </td> 
      ';
      echo'
        <td class="class-results--align-center">' . $A[$courseloop][$resloop] .'</td>
        <td class="class-results--align-center">' . $B[$courseloop][$resloop] .'</td>
        <td class="class-results--align-center">' . $C[$courseloop][$resloop] .'</td>
        <td class="class-results--align-center">' . $D[$courseloop][$resloop] .'</td>
        </tr>';
        $resloop++;
    }
    echo '  
      <tr>
      <tr>
      </tr>';
      if ($_REQUEST['elements']['comments'] == 'Y') {
      echo '<tr>
        <td colspan="' . $commentspan . '">' . $comments[$courseloop]['COMMENT_TITLE'] . ': <b><i>' . $comments[$courseloop]['COMMENT'] . '</i></b></td>
      </tr>';
      }
    echo '</table>';
    $courseloop++;
    }
    echo '<tr> <i> 
    <p style="text-align:right;">A : Très satisfaisant<br>B : Satisfaisant<br>C : Insatisfaisant<br>D : Très insatisfaisant</p>
  </i></tr>';    //echo "<pre>";print_r($column);echo "</pre>";
    //echo "<pre>";print_r($quarts);echo "</pre>";
    //echo "<pre>";print_r($results);echo "</pre>";
    //echo "<pre>";print_r($comments);echo "</pre>";
}



function CadoHTMLresultatsPrescolaire($title,$course,$quarts,$results,$comments,$result_diff,$exam_value){
    global $publish_parents;

    $numquart=count($quarts[0])-2;
    $colspan=$numquart+1;
    $commentspan=$colspan+1;
    $markingPeriod = DBGet(DBQuery('SELECT * FROM school_quarters WHERE SYEAR=\'' . UserSyear() . '\' AND SCHOOL_ID=\'' . UserSchool() . '\' AND SORT_ORDER=255 '));
    ##### ROGER ###
    if($markingPeriod[1]['SORT_ORDER'] == 255) $numquart--;
    echo '<pre class="section-title">'; echo $title; echo'</pre>';    
    $courseloop=0;
    foreach ($course as $key=> $col) {
        
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

      </tr>
      <tr>';

      echo'
 
      </tr>
    ';
    $resloop=0;

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
      if ($_REQUEST['elements']['comments'] == 'Y') {
      echo '<tr>
        <td colspan="' . $commentspan . '">' . $comments[$courseloop]['COMMENT_TITLE'] . ': <b><i>' . $comments[$courseloop]['COMMENT'] . '</i></b></td>
      </tr>';
      }
    echo '</table>';
    $courseloop++;
    }
    //echo "<pre>";print_r($column);echo "</pre>";
    //echo "<pre>";print_r($quarts);echo "</pre>";
    //echo "<pre>";print_r($results);echo "</pre>";
    //echo "<pre>";print_r($comments);echo "</pre>";
}

function CadoHTMLresultatsPrimaire($title,$course,$quarts,$results,$comments,$result_diff,$year,$grade_id,$exam_value){
    global $publish_parents;
    $numquart=count($quarts[0])-2;
    $markingPeriod = DBGet(DBQuery('SELECT * FROM school_quarters WHERE SYEAR=\'' . UserSyear() . '\' AND SCHOOL_ID=\'' . UserSchool() . '\' AND SORT_ORDER=255 '));
    ##### ROGER ###
    if($markingPeriod[1]['SORT_ORDER'] == 255) $numquart--;
    $colspan=$numquart+1;
    $commentspan=$colspan*2+1;
    $right=0;
    $SCHOOL_GRADELEVELS=DBGet(DBQuery('SELECT * from schools where ID = \''. UserSchool() . '\''));
    $courseloop=0;
    $course[$year][$courseloop]['STUDENT_GRADE']='Primaire 2';
    if(strpos($grade_id,"1") || strpos($grade_id,"2")){
            $cycle='Cycle 1';
            if (strpos($grade_id , "1")) {
                $row1=$results[$year];
            }
            else{
                $row1=$results[$year-1];
                $row2=$results[$year];
                $right=1;
            }
    }
    else    
        if(strpos($grade_id,"3") || strpos($grade_id,"4")){
            $cycle='Cycle 2';
            if (strpos($grade_id , "3")) {
                $row1=$results[$year];
            }
            else{
                $row1=$results[$year-1];
                $row2=$results[$year];
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
                    $right=1;
                }
    }
    echo '<pre class="section-title">'; echo $title; echo'</pre>';    
    foreach ($course[$year] as $key=> $col) {

    echo'
    <table class="class-results__table">
      <tr>
        <th
          rowspan="3"
          class="class-results--align-left class-results__th--left-header"
        >
          <h1>' . $course[$year][$courseloop]['TITLE']  . '</h1>
          <br />
          '; 
          if ($_REQUEST['elements']['teacher'] == 'Y') {
            echo $course[$year][$courseloop]['TEACHER'];
          } 
          echo ' </th>
          <th colspan="' . $colspan *2  . '" class="class-results__3col-right">' . $cycle  . '</th>
          </tr>
          <tr>
              <th colspan="' . $colspan  . '" class="class-results__3col-right">Année 1</th>
              <th colspan="' . $colspan  . '" class="class-results__3col-right">Année 2</th>
          </tr>
      <tr>';
      for($quartloop=0; $quartloop < $numquart ; $quartloop++){
        echo'
        <th class="class-results__3col__th">' . $quarts[$courseloop][$quartloop+1]  .'</th>
    ';
      }
    echo'
        <th class="class-results__3col__th">' . $quarts[$courseloop]['FINAL']  .'</th>
    ';
    for($quartloop=0; $quartloop < $numquart ; $quartloop++){
        echo'
        <th class="class-results__3col__th">' . $quarts[$courseloop][$quartloop+1]  .'</th>
    ';
      }
    echo'
        <th class="class-results__3col__th">' . $quarts[$courseloop]['FINAL']  .'</th>
      </tr>
    ';
    $resloop=0;
    foreach ($results[$year][$courseloop] as $key=> $result){
      echo '<tr>
        <td class="class-results--align-right">' . $results[$year][$courseloop][$resloop]['TYPE']  .'</td>';
        for($quartloop=0; $quartloop < $numquart ; $quartloop++){
            echo'
            <td class="class-results--align-center">' . $row1[$courseloop][$resloop]['RESULT'][$quartloop]  .'</td>
            ';
          }
    echo'
        <td class="class-results--align-center">' . $row1[$courseloop][$resloop]['RESULT']['FINAL']  .'</td>';
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
    //if($right)
    //    echo '<td></td><td></td><td></td><td>';
   
      if(! $publish_parents){
        if($right){
            if($result_diff[$courseloop][0]['RESULTDIFF'] || $result_diff[$courseloop][1]['RESULTDIFF'] || $result_diff[$courseloop][2]['RESULTDIFF']){
                echo '<td></td><td></td><td></td><td></td><td></td>';
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
        else {
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
            echo '<td></td><td></td><td></td><td></td><td></td>';
            }              
            if($exam_value[$courseloop]){
                echo '<tr><td class="class-results--align-center highligth">Examen final = '. $exam_value[$courseloop] . '</td></tr>';
            }
        }
    }

  }

    echo '  
      <tr>
      <tr>
      </tr>';
      if ($_REQUEST['elements']['comments'] == 'Y') {
      echo '<tr>
        <td colspan="' . $commentspan . '">' . $comments[$courseloop]['COMMENT_TITLE'] . ': <b><i>' . $comments[$courseloop]['COMMENT'] . '</i></b></td>
      </tr>';
      }
    echo '</table>';
    //echo "<pre>";echo $course[$year-1][$courseloop]['STUDENT_GRADE']  ;echo "</pre>";
    //echo "<pre>";echo $year-1 ;echo "</pre>";
    $courseloop++;
}
    //echo "<pre>";echo $course[$year][$courseloop]['STUDENT_GRADE']  ;echo "</pre>";
    //echo "<pre>";print_r($column);echo "</pre>";
    //echo "<pre>";print_r($type);echo "</pre>";
    //echo "<pre>";print_r($results);echo "</pre>";
    //echo "<pre>";print_r($comments);echo "</pre>";
}


function CadoHTMLresultatsSecondaire($title,$course,$quarts,$results,$comments,$result_diff,$exam_value){
    global $publish_parents;

    $numquart=count($quarts[0])-2;
    $colspan=$numquart+1;
    $commentspan=$colspan+1;
    $markingPeriod = DBGet(DBQuery('SELECT * FROM school_quarters WHERE SYEAR=\'' . UserSyear() . '\' AND SCHOOL_ID=\'' . UserSchool() . '\' AND SORT_ORDER=255 '));
    ##### ROGER ###
    if($markingPeriod[1]['SORT_ORDER'] == 255) $numquart--;
    echo '<pre class="section-title">'; echo $title; echo'</pre>';    
    $courseloop=0;
    foreach ($course as $key=> $col) {
        if(html_entity_decode($comments[$courseloop]['COMMENT']) != html_entity_decode('Cours abandonné.'))
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
        ##### ROGER ###
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
    }
    echo '  
      <tr>
      <tr>
      </tr>';
      if ($_REQUEST['elements']['comments'] == 'Y') {
      echo '<tr>
        <td colspan="' . $commentspan . '">' . $comments[$courseloop]['COMMENT_TITLE'] . ': <b><i>' . $comments[$courseloop]['COMMENT'] . '</i></b></td>
      </tr>';
      }
    echo '</table>';
    $courseloop++;
    }
    //echo "<pre>";print_r($column);echo "</pre>";
    //echo "<pre>";print_r($quarts);echo "</pre>";
    //echo "<pre>";print_r($results);echo "</pre>";
    //echo "<pre>";print_r($comments);echo "</pre>";
}


function CadoHTMLcommentairesCompetence($title,$items,$data){

    if (! $_REQUEST['elements']['comments'] == 'Y') return;
    echo '<pre class="section-title">'; echo $title; echo'</pre>';    
    echo '<table class="section-1">
    <tr>
        <td class="section-2-header"> ' . $items['COMMENTAIRE'] . '</td>
    </tr>
    <td>
    <div class="section-2-item">' . $data['COMMENTAIRE'] . '&nbsp</div>
    </td>
    </table>';

    //echo "<pre>";print_r($column);echo "</pre>";
    //echo "<pre>";print_r($data);echo "</pre>";
}

function CadoHTMLcommentairesGeneral($title,$items,$data){

   if (! $_REQUEST['elements']['comments'] == 'Y') return;
   echo '<pre class="section-title">'; echo $title; echo'</pre>';    
    echo '<table class="section-1">
    <tr>
        <td class="section-2-header"> ' . $items['COMMENTAIRE'] . '</td>
    </tr>
    <td>
    <div class="section-2-item">' . $data['COMMENTAIRE'] . '&nbsp</div>
    </td>
    </table>';

    //echo "<pre>";print_r($column);echo "</pre>";
    //echo "<pre>";print_r($data);echo "</pre>";
}

function CadoPageSetup($title){
    echo '<table class="logo">';
    echo '<tr><td width=105>' . DrawLogo() . '<div class="logo-title">' . $title  . '</div>';
    echo '<div class="logo-title">' . _schoolYear . ' ' . UserSyear() . '-' . (UserSyear()+1) .  '</div></table>';
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
      }
      th,
      td {
        border: 1px solid #000000;
        padding: 5px;
      }
      .logo {
        border: none;
        width: 100%;
      }
      .logo td, .logo tr{
       text-align:center;
        border: none;
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
        border: 2px solid black;  
      }
      .class-results__table {
        width: 100%;
        margin-bottom: 20px;
        border: 2px solid black;
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
        font-family: Arial, Helvetica, sans-serif;
      }
      .section-2-item{
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
        font-size:25px; 
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
    </style>
    
    ';
}

function _removeSpaces($value, $column) {
    if ($column == 'ASSIGNED_DATE' || $column == 'DUE_DATE')
        $value = ProperDate($value);
    if ($column == 'TITLE')
        $value = html_entity_decode($value);
    return str_replace(' ', '&nbsp;', str_replace('&', '&amp;', $value));
}

function _makeAssnWG($value, $column) {
    global $THIS_RET, $student_points, $total_points, $percent_weights;
    return ($THIS_RET['ASSIGN_TYP_WG'] != 'N/A' ? ($value * 100) . ' %' : $THIS_RET['ASSIGN_TYP_WG']);
}

function _makeWtg($value, $column) {
    global $THIS_RET, $student_points, $total_points, $percent_weights;
    $wtdper = ($THIS_RET['POINTS'] / $THIS_RET['TOTAL_POINTS']) * $THIS_RET['ASSIGN_WEIGHT'] / 100;
    return (($THIS_RET['LETTERWTD_GRADE'] != -1.00 && $THIS_RET['LETTERWTD_GRADE'] != '' && $THIS_RET['ASSIGN_TYP_WG'] != 'N/A') ? _makeLetterGrade($wtdper, "", User('STAFF_ID'), '%') . '%' : 'N/A');
}
function _makeAssgnmtWtg($value, $column) {
    global $THIS_RET, $student_points, $total_points, $percent_weights;
    return ($THIS_RET['ASSIGN_WEIGHT'] != 'N/A' ? $value . ' %' : $THIS_RET['ASSIGN_WEIGHT']);
}
