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
include '../../RedirectModulesInc.php';
ini_set('memory_limit', '120000000000M');
ini_set('max_execution_time', '50000000');


$courses_count=0;
$students_count=0;

if (!$_REQUEST['modfunc']) {

    $start_date = date('Y-m') . '-01';
    $end_date = DBDate('mysql');
    $YearMP = DBGet(DBQuery('SELECT MARKING_PERIOD_ID from school_quarters where SYEAR = '  .UserSyear(). ' and title = \'1ère communication\' '));
    if($YearMP[1]['MARKING_PERIOD_ID']){
        $courses=DBGet(DBQuery('SELECT * from gradebook_assignments where MARKING_PERIOD_ID = '  .$YearMP[1]['MARKING_PERIOD_ID']. ' '));
     }
     if(count($courses)){
        if ((!isset($conv_st_date) || !isset($conv_end_date))) {
            echo '<center><font color="red"><b><h5>Les types de devoir ont déja été attribués</h5></b></font></center>';
        }
    }
     else{
        echo "<FORM action=Modules.php?modname=" . strip_tags(trim($_REQUEST[modname])) . "&modfunc=teachers method=POST >";
        echo '<div class="row">';
        echo '<div class="col-md-8 col-md-offset-2">';
        echo "<FORM class=\"form-horizontal\" name=log id=log action=Modules.php?modname=$_REQUEST[modname]&modfunc=teachers method=POST>";
        PopTable('header',  'Attribuer les types de devoirs a tous les professeurs');
        echo '<h5 class="text-center">Cette fonction est irréversible, procédez avec prudence</h5>';
        $btn = '<input type="submit" class="btn btn-primary" value="Atribuer les types de devoirs de l\'an passé aux professeurs" name="generate2" onclick="self_disable(this);">';
        PopTable('footer', $btn);
        echo '</FORM>';
        echo '</div>';
        echo '</div>'; //.row
     }

     $schedule=DBGet(DBQuery('SELECT * from schedule where SYEAR = ' .UserSyear().' '));
     if(count($schedule)){
        if ((!isset($conv_st_date) || !isset($conv_end_date))) {
            echo '<center><font color="red"><b><h5>Les cours ont déja été assignés </h5></b></font></center>';
        }
     }
     else{
        echo "<FORM action=Modules.php?modname=" . strip_tags(trim($_REQUEST[modname])) . "&modfunc=students method=POST >";
        echo '<div class="row">';
        echo '<div class="col-md-8 col-md-offset-2">';
        echo "<FORM class=\"form-horizontal\" name=log id=log action=Modules.php?modname=$_REQUEST[modname]&modfunc=students method=POST>";
        PopTable('header',  'Assigner cours a tous les étudiants');
        echo '<h5 class="text-center">Cette fonction est irréversible, procédez avec prudence</h5>';
        $btn = '<input type="submit" class="btn btn-primary" value="Assigner tous les cours disponible à tous les étudiants éligibles" name="generate" onclick="self_disable(this);">';
        PopTable('footer', $btn);
        echo '</FORM>';
        echo '</div>';
        echo '</div>'; //.row
     }
}
if ($_REQUEST['modfunc'] == 'students') {
    CadoStudentFix();
}

if ($_REQUEST['modfunc'] == 'teachers') {
    CadoTeacherFix(UserSyear());
}

function CadoTeacherFix($next_syear)
{
    echo 'CADO - Assigner valeurs par défault aux enseignants ainsi que les compétances des cours';
    $YearMP = DBGet(DBQuery('SELECT   MARKING_PERIOD_ID FROM SCHOOL_YEARS WHERE SYEAR=\'' . UserSyear() . '\' AND SCHOOL_ID=\'' . UserSchool() . '\''));
    $E1MP = DBGet(DBQuery('SELECT   MARKING_PERIOD_ID FROM SCHOOL_QUARTERS WHERE SYEAR=\'' . UserSyear() . '\' AND TITLE = \'Étape 1\' AND SCHOOL_ID=\'' . UserSchool() . '\''));
    $E2MP = DBGet(DBQuery('SELECT   MARKING_PERIOD_ID FROM SCHOOL_QUARTERS WHERE SYEAR=\'' . UserSyear() . '\' AND TITLE = \'Étape 2\' AND SCHOOL_ID=\'' . UserSchool() . '\''));
    $E3MP = DBGet(DBQuery('SELECT   MARKING_PERIOD_ID FROM SCHOOL_QUARTERS WHERE SYEAR=\'' . UserSyear() . '\' AND TITLE = \'Étape 3\' AND SCHOOL_ID=\'' . UserSchool() . '\''));
    $ECMP = DBGet(DBQuery('SELECT   MARKING_PERIOD_ID FROM SCHOOL_QUARTERS WHERE SYEAR=\'' . UserSyear() . '\' AND TITLE = \'1ère communication\' AND SCHOOL_ID=\'' . UserSchool() . '\''));
    $FY = $FY1 = $FY2 = $FY3 = $FYC = 'FY-';
    $E1 = $E2 = $E3 = $EC = 'Q-';
    $FY .='E';
    $FY .= $YearMP[1]['MARKING_PERIOD_ID'];
    $E1 .= $E1MP[1]['MARKING_PERIOD_ID'];
    $FY1 .= $E1MP[1]['MARKING_PERIOD_ID'];
    $E2 .= $E2MP[1]['MARKING_PERIOD_ID'];
    $FY2 .= $E2MP[1]['MARKING_PERIOD_ID'];
    $E3 .= $E3MP[1]['MARKING_PERIOD_ID'];
    $FY3 .= $E3MP[1]['MARKING_PERIOD_ID'];
    $EC .= $ECMP[1]['MARKING_PERIOD_ID'];
    $FYC .= $ECMP[1]['MARKING_PERIOD_ID'];
    // General options
    DBQuery('INSERT INTO program_user_config (user_id,school_id,program,title,value,last_updated,updated_by) SELECT teacher_id as user_id, school_id, \'Gradebook\' as program, \'ROUNDING\' as title, CONCAT("NORMAL_",course_period_id) as value, now() as last_updated, teacher_id as updated_by FROM course_periods WHERE syear = \''.$next_syear.'\'');
    DBQuery('INSERT INTO program_user_config (user_id,school_id,program,title,value,last_updated,updated_by) SELECT teacher_id as user_id, school_id, \'Gradebook\' as program, \'ASSIGNMENT_SORTING\' as title, CONCAT("ASSIGNMENT_ID_",course_period_id) as value, now() as last_updated, teacher_id as updated_by FROM course_periods WHERE  syear = \''.$next_syear.'\'');
    DBQuery('INSERT INTO program_user_config (user_id,school_id,program,title,value,last_updated,updated_by) SELECT teacher_id as user_id, school_id, \'Gradebook\' as program, \'WEIGHT\' as title, CONCAT("Y_",course_period_id) as value, now() as last_updated, teacher_id as updated_by FROM course_periods WHERE  syear = \''.$next_syear.'\'');
    DBQuery('INSERT INTO program_user_config (user_id,school_id,program,title,value,last_updated,updated_by) SELECT teacher_id as user_id, school_id, \'Gradebook\' as program, \'ANOMALOUS_MAX\' as title, CONCAT("100_",course_period_id) as value, now() as last_updated, teacher_id as updated_by FROM course_periods WHERE  syear = \''.$next_syear.'\'');
    DBQuery('INSERT INTO program_user_config (user_id,school_id,program,title,value,last_updated,updated_by) SELECT teacher_id as user_id, school_id, \'Gradebook\' as program, \'LATENCY\' as title, CONCAT("0_",course_period_id) as value, now() as last_updated, teacher_id as updated_by FROM course_periods WHERE  syear = \''.$next_syear.'\'');
    DBQuery('INSERT INTO program_user_config (user_id,school_id,program,title,value,last_updated,updated_by) SELECT teacher_id as user_id, school_id, \'Gradebook\' as program, \'COMMENT_A\' as title, NULL as value, now() as last_updated, teacher_id as updated_by FROM course_periods WHERE  syear = \''.$next_syear.'\'');
    //DBQuery('INSERT INTO program_user_config (user_id,program,title,value,last_updated,updated_by) SELECT staff_id as user_id,\'Preferences\' as program,\'HIDE_ALERTS\' as title,\'N\' as value,last_updated as last_updated,staff_id as updated_by FROM staff WHERE  profile_id =\'2\'');
    // Scale
    DBQuery('INSERT INTO program_user_config (user_id,school_id,program,title,value,last_updated,updated_by) SELECT teacher_id as user_id, school_id, \'Gradebook\' as program, CONCAT(course_period_id,"-65") as title, CONCAT("90_",course_period_id) as value, now() as last_updated, teacher_id as updated_by FROM course_periods WHERE  syear = \''.$next_syear.'\'');
    DBQuery('INSERT INTO program_user_config (user_id,school_id,program,title,value,last_updated,updated_by) SELECT teacher_id as user_id, school_id, \'Gradebook\' as program, CONCAT(course_period_id,"-66") as title, CONCAT("80_",course_period_id) as value, now() as last_updated, teacher_id as updated_by FROM course_periods WHERE  syear = \''.$next_syear.'\'');
    DBQuery('INSERT INTO program_user_config (user_id,school_id,program,title,value,last_updated,updated_by) SELECT teacher_id as user_id, school_id, \'Gradebook\' as program, CONCAT(course_period_id,"-67") as title, CONCAT("70_",course_period_id) as value, now() as last_updated, teacher_id as updated_by FROM course_periods WHERE  syear = \''.$next_syear.'\'');
    DBQuery('INSERT INTO program_user_config (user_id,school_id,program,title,value,last_updated,updated_by) SELECT teacher_id as user_id, school_id, \'Gradebook\' as program, CONCAT(course_period_id,"-68") as title, CONCAT("60_",course_period_id) as value, now() as last_updated, teacher_id as updated_by FROM course_periods WHERE  syear = \''.$next_syear.'\'');
    DBQuery('INSERT INTO program_user_config (user_id,school_id,program,title,value,last_updated,updated_by) SELECT teacher_id as user_id, school_id, \'Gradebook\' as program, CONCAT(course_period_id,"-69") as title, CONCAT("50_",course_period_id) as value, now() as last_updated, teacher_id as updated_by FROM course_periods WHERE  syear = \''.$next_syear.'\'');
    DBQuery('INSERT INTO program_user_config (user_id,school_id,program,title,value,last_updated,updated_by) SELECT teacher_id as user_id, school_id, \'Gradebook\' as program, CONCAT(course_period_id,"-70") as title, CONCAT("40_",course_period_id) as value, now() as last_updated, teacher_id as updated_by FROM course_periods WHERE  syear = \''.$next_syear.'\'');
    // Quarter weigth
    DBQuery('INSERT INTO program_user_config (user_id,school_id,program,title,value,last_updated,updated_by) SELECT teacher_id as user_id, school_id, \'Gradebook\' as program, \''.$E1.'\' as title, CONCAT("100_",course_period_id) as value, now() as last_updated, teacher_id as updated_by FROM course_periods WHERE  syear = \''.$next_syear.'\'');
    DBQuery('INSERT INTO program_user_config (user_id,school_id,program,title,value,last_updated,updated_by) SELECT teacher_id as user_id, school_id, \'Gradebook\' as program, \''.$E2.'\' as title, CONCAT("100_",course_period_id) as value, now() as last_updated, teacher_id as updated_by FROM course_periods WHERE  syear = \''.$next_syear.'\'');
    DBQuery('INSERT INTO program_user_config (user_id,school_id,program,title,value,last_updated,updated_by) SELECT teacher_id as user_id, school_id, \'Gradebook\' as program, \''.$EC.'\' as title, CONCAT("100_",course_period_id) as value, now() as last_updated, teacher_id as updated_by FROM course_periods WHERE  syear = \''.$next_syear.'\'');
    DBQuery('INSERT INTO program_user_config (user_id,school_id,program,title,value,last_updated,updated_by) SELECT teacher_id as user_id, school_id, \'Gradebook\' as program, \''.$E3.'\' as title, CONCAT("100_",course_period_id) as value, now() as last_updated, teacher_id as updated_by FROM course_periods WHERE  syear = \''.$next_syear.'\'');
    // Full year quarter weigth
    DBQuery('INSERT INTO program_user_config (user_id,school_id,program,title,value,last_updated,updated_by) SELECT teacher_id as user_id, school_id, \'Gradebook\' as program, \''.$FY1.'\' as title, CONCAT("20_",course_period_id) as value, now() as last_updated, teacher_id as updated_by FROM course_periods WHERE  syear = \''.$next_syear.'\'');
    DBQuery('INSERT INTO program_user_config (user_id,school_id,program,title,value,last_updated,updated_by) SELECT teacher_id as user_id, school_id, \'Gradebook\' as program, \''.$FY2.'\'  as title, CONCAT("20_",course_period_id) as value, now() as last_updated, teacher_id as updated_by FROM course_periods WHERE  syear = \''.$next_syear.'\'');
    DBQuery('INSERT INTO program_user_config (user_id,school_id,program,title,value,last_updated,updated_by) SELECT teacher_id as user_id, school_id, \'Gradebook\' as program, \''.$FYC.'\' as title, CONCAT("0_",course_period_id) as value, now() as last_updated, teacher_id as updated_by FROM course_periods WHERE  syear = \''.$next_syear.'\'');
    DBQuery('INSERT INTO program_user_config (user_id,school_id,program,title,value,last_updated,updated_by) SELECT teacher_id as user_id, school_id, \'Gradebook\' as program, \''.$FY3.'\' as title, CONCAT("60_",course_period_id) as value, now() as last_updated, teacher_id as updated_by FROM course_periods WHERE  syear = \''.$next_syear.'\'');
    DBQuery('INSERT INTO program_user_config (user_id,school_id,program,title,value,last_updated,updated_by) SELECT teacher_id as user_id, school_id, \'Gradebook\' as program, \''.$FY.'\' as title, CONCAT("0_",course_period_id) as value, now() as last_updated, teacher_id as updated_by FROM course_periods WHERE  syear = \''.$next_syear.'\'');
    // Assignment type 1ere communication
    $syear=$this_year=$next_syear;
    $last_year=$this_year-1;
    $get_dates=DBGet(DBQuery('SELECT *  FROM school_quarters WHERE  TITLE= \'1ère communication\' AND SYEAR = \''.$next_syear.'\''));
    $start=$get_dates[1]['POST_START_DATE'];
    $end=$get_dates[1]['POST_END_DATE'];
    $now=date('Y-m-d');
    //$oldcourses=DBGet(DBQuery('SELECT TEACHER_ID,COURSE_ID,COURSE_PERIOD_ID,COURSE_TITLE as TITLE,CP_TITLE as SHORT,(select COURSE_PERIOD_ID from course_details  where SYEAR=' .$this_year. ' and COURSE_TITLE=TITLE and CP_TITLE=SHORT)as NEW_COURSE_PERIOD_ID from course_details where SYEAR=' .$last_year. ''));
    $oldcourses=DBGet(DBQuery('SELECT cdnew.course_period_id as NEW_COURSE_PERIOD_ID, cdnew.TEACHER_ID,cdold.COURSE_ID as course_id,cdold.COURSE_PERIOD_ID,cdnew.COURSE_TITLE as TITLE,cdnew.CP_TITLE as SHORT from course_details cdold inner join course_details cdnew on (cdnew.rollover_id=cdold.COURSE_ID) where cdnew.syear=' .$this_year. ''));
    //  echo '<pre>'; print_r($oldcourses); echo '</pre>';

    foreach($oldcourses as $individual) {
            $types=DBGet(DBQuery('SELECT TITLE,COURSE_ID,COURSE_PERIOD_ID,FINAL_GRADE_PERCENT from gradebook_assignment_types where COURSE_PERIOD_ID= ' .$individual['COURSE_PERIOD_ID'].' '));
//   echo '<pre>'; print_r($individual); echo '</pre>';
        foreach($types as $type){
            if (!$type['FINAL_GRADE_PERCENT'])  
                $type['FINAL_GRADE_PERCENT']='null';
            DBQuery('INSERT INTO gradebook_assignment_types (STAFF_ID,COURSE_PERIOD_ID,COURSE_ID,TITLE,FINAL_GRADE_PERCENT) values('.$individual['TEACHER_ID'].','.$individual['NEW_COURSE_PERIOD_ID'].','.$type['COURSE_ID'].',"'. html_entity_decode($type['TITLE']).'",'.$type['FINAL_GRADE_PERCENT'].')');
            $return=DBGet(DBQuery('SELECT * FROM gradebook_assignment_types where STAFF_ID= '.$individual['TEACHER_ID'].' AND COURSE_PERIOD_ID= '.$individual['NEW_COURSE_PERIOD_ID'].' AND COURSE_ID= '.$type['COURSE_ID'].' AND TITLE= "'.html_entity_decode($type['TITLE']).'" '));
            if($type['TITLE'] == '1ère communication'){
             DBQuery('INSERT INTO gradebook_assignments (staff_id,marking_period_id,assignment_type_id,course_period_id,title,due_date,assigned_date,points,ASSIGNMENT_WEIGHT,ungraded,last_updated) values(' .$individual['TEACHER_ID'] . ',' .$get_dates[1]['MARKING_PERIOD_ID']. ',' .$return[1]['ASSIGNMENT_TYPE_ID']. ',' . $individual['NEW_COURSE_PERIOD_ID'] . ' , \'En voie de réussite\' , \''.$end.'\' , \''.$start.'\' , \'100\' , \'33\' , \'1\' ,  \''.$now.'\' )');
             DBQuery('INSERT INTO gradebook_assignments (staff_id,marking_period_id,assignment_type_id,course_period_id,title,due_date,assigned_date,points,ASSIGNMENT_WEIGHT,ungraded,last_updated) values(' .$individual['TEACHER_ID'] . ',' .$get_dates[1]['MARKING_PERIOD_ID']. ',' .$return[1]['ASSIGNMENT_TYPE_ID']. ',' . $individual['NEW_COURSE_PERIOD_ID'] . ' , \'Complète et remet ses travaux\' , \''.$end.'\' , \''.$start.'\' , \'100\' , \'33\' , \'1\' ,  \''.$now.'\' )');
             DBQuery('INSERT INTO gradebook_assignments (staff_id,marking_period_id,assignment_type_id,course_period_id,title,due_date,assigned_date,points,ASSIGNMENT_WEIGHT,ungraded,last_updated) values(' .$individual['TEACHER_ID'] . ',' .$get_dates[1]['MARKING_PERIOD_ID']. ',' .$return[1]['ASSIGNMENT_TYPE_ID']. ',' . $individual['NEW_COURSE_PERIOD_ID'] . ' , \'Attitude et comportement\' , \''.$end.'\' , \''.$start.'\' , \'100\' , \'34\' , \'1\' ,  \''.$now.'\' )');
             
            //SELECT staff_id as staff_id,  \''.$YearMP[1]['MARKING_PERIOD_ID'].'\' as marking_period_id, assignment_type_id as assignment_type_id, \'En voie de réussite\' as title, \''.$end.'\' as due_date, \''.$start.'\' as assigned_date, 100 as points, 33 as ASSIGNMENT_WEIGHT, 1 as ungraded, \''.$now.'\' as last_updated FROM gradebook_assignment_types WHERE  title = \'1ère communication\'');
            // DBQuery('INSERT INTO gradebook_assignments (staff_id,marking_period_id,assignment_type_id,title,due_date,assigned_date,points,ASSIGNMENT_WEIGHT,ungraded,last_updated) SELECT staff_id as staff_id,  \''.$YearMP[1]['MARKING_PERIOD_ID'].'\'  as marking_period_id, assignment_type_id as assignment_type_id, \'Complète et remet ses travaux\' as title, \''.$end.'\' as due_date, \''.$start.'\' as assigned_date, 100 as points, 33 as ASSIGNMENT_WEIGHT, 1 as ungraded, \''.$now.'\' as last_updated FROM gradebook_assignment_types WHERE  title = \'1ère communication\'');
            // DBQuery('INSERT INTO gradebook_assignments (staff_id,marking_period_id,assignment_type_id,title,due_date,assigned_date,points,ASSIGNMENT_WEIGHT,ungraded,last_updated) SELECT staff_id as staff_id,  \''.$YearMP[1]['MARKING_PERIOD_ID'].'\'  as marking_period_id, assignment_type_id as assignment_type_id, \'Attitude et comportement\' as title, \''.$end.'\' as due_date, \''.$start.'\' as assigned_date, 100 as points, 34 as ASSIGNMENT_WEIGHT, 1 as ungraded, \''.$now.'\' as last_update FROM gradebook_assignment_types WHERE  title = \'1ère communication\'');
            }
        }
    }
    // DBQuery('INSERT INTO gradebook_assignment_types (title,final_grade_percent,staff_id,course_period_id,course_id) SELECT \'1ère communication\' as title, NULL as final_grade_percent, teacher_id as staff_id, course_period_id as course_period_id, course_id as course_id FROM course_periods WHERE  syear =  \''.$next_syear.'\'');
    // Assigments 1ere communicatio
}


function CadoStudentFix()
{
    $courses_count=0;
    $students_count=0;
    $syear=UserSyear();
    // Auto enroll students in courses
    $grade_levels=DBGet(DBQuery('SELECT ID from school_gradelevels'));
    $dates=DBGet(DBQuery('SELECT SCHOOL_ID,START_DATE,END_DATE,MARKING_PERIOD_ID,SHORT_NAME from marking_periods where SYEAR = '.$syear.' and SHORT_NAME = \'FY\' '));
    foreach($grade_levels as $individual) {
        $students=DBGet(DBQuery('SELECT STUDENT_ID,GRADE_ID from student_enrollment where syear= ' .$syear . ' and grade_id= ' .$individual['ID']. ''));
        foreach($students as $student) {
            $courses=DBGet(DBQuery('SELECT COURSE_PERIOD_ID as NEW_COURSE_PERIOD_ID ,COURSE_ID as C_ID,COURSE_TITLE as TITLE,CP_TITLE as SHORT,(select COURSE_PERIOD_ID from course_details  where SYEAR=' .($syear-1) . ' and COURSE_TITLE=TITLE and CP_TITLE=SHORT)as OLD_COURSE_PERIOD_ID, (SELECT GRADE_LEVEL from courses where syear= ' .$syear . ' and  COURSE_ID=C_ID ) as GRADE_LEVEL  from course_details where SYEAR=' .$syear . ''));
            foreach($courses as $course) {
                if($course['GRADE_LEVEL']==$student['GRADE_ID']){
                    DBQuery('INSERT INTO schedule (STUDENT_ID,SYEAR,SCHOOL_ID,START_DATE,END_DATE,MODIFIED_DATE,MODIFIED_BY,UPDATED_BY,COURSE_ID,COURSE_PERIOD_ID,MP,MARKING_PERIOD_ID) values('.$student['STUDENT_ID'].','.$syear.', '.$dates[1]['SCHOOL_ID'].' ,\''.$dates[1]['START_DATE'].'\',\''. $dates[1]['END_DATE'] .'\',\'' . $now .'\',1,1,'.$course['C_ID'].','.$course['NEW_COURSE_PERIOD_ID'].',\''.$dates[1]['SHORT_NAME'].'\', '.$dates[1]['MARKING_PERIOD_ID'].')');
                    $courses_count++;
                }
            }
            $students_count++;
        }
    }
    echo '<center><font color="black"><b><h5>'. $courses_count .' cours ont été attribué avec succès a ' . $students_count . ' étudiants pour une moyenne de ' . round($courses_count/$students_count)  . ' cours par étudiant.</h5></b></font></center>';
}
exit;

if ($_REQUEST['func'] == 'Basic') {
    // $num_students = DBGet(DBQuery('SELECT COUNT(STUDENT_ID) as TOTAL_STUDENTS FROM students WHERE STUDENT_ID IN (SELECT DISTINCT STUDENT_ID FROM student_enrollment WHERE SYEAR=' . UserSyear() . ' AND SCHOOL_ID=' . UserSchool() . ')'));
    $num_schools = DBGet(DBQuery('SELECT COUNT(ID) as TOTAL_SCHOOLS FROM schools'));
    $num_schools = $num_schools[1]['TOTAL_SCHOOLS'];

    $num_students = DBGet(DBQuery('SELECT COUNT(STUDENT_ID) as TOTAL_STUDENTS FROM students'));
    $num_students = $num_students[1]['TOTAL_STUDENTS'];

    // $male = DBGet(DBQuery('SELECT COUNT(STUDENT_ID) as MALE FROM students WHERE GENDER=\'Male\' AND STUDENT_ID IN (SELECT DISTINCT STUDENT_ID FROM student_enrollment WHERE SYEAR=' . UserSyear() . ' AND SCHOOL_ID=' . UserSchool() . ')'));
    $male = DBGet(DBQuery('SELECT COUNT(STUDENT_ID) as MALE FROM students WHERE GENDER=\'Male\''));
    $male = $male[1]['MALE'];

    // $female = DBGet(DBQuery('SELECT COUNT(STUDENT_ID) as FEMALE FROM students WHERE GENDER=\'Female\' AND STUDENT_ID IN (SELECT DISTINCT STUDENT_ID FROM student_enrollment WHERE SYEAR=' . UserSyear() . ' AND SCHOOL_ID=' . UserSchool() . ')'));
    $female = DBGet(DBQuery('SELECT COUNT(STUDENT_ID) as FEMALE FROM students WHERE GENDER=\'Female\''));
    $female = $female[1]['FEMALE'];
    $num_staff = 0;
    $num_teacher = 0;
    // $num_users = DBGet(DBQuery('SELECT COUNT(DISTINCT s.STAFF_ID) as TOTAL_USER,IF(PROFILE_ID=2,\'Teacher\',\'Staff\') as PROFILEID FROM staff s,staff_school_relationship ssr WHERE s.STAFF_ID=ssr.STAFF_ID AND SYEAR = ' . UserSyear() . ' AND SCHOOL_ID=' . UserSchool() . ' AND SCHOOL_ID IN (SELECT ID FROM schools ) GROUP BY PROFILEID'));
    $num_users = DBGet(DBQuery('SELECT COUNT(DISTINCT s.STAFF_ID) as TOTAL_USER, IF(PROFILE IN(SELECT PROFILE FROM user_profiles WHERE PROFILE =\'teacher\'),\'Teacher\',\'Staff\')as PROFILEID FROM staff s group by PROFILEID'));
    foreach ($num_users as $gt_dt) {
        if ($gt_dt['PROFILEID'] == 'Staff') {
            $num_staff = $gt_dt['TOTAL_USER'];
        } else {
            $num_teacher = $gt_dt['TOTAL_USER'];
        }

    }
    // $num_parent = DBGet(DBQuery('SELECT COUNT(distinct p.STAFF_ID) as TOTAL_PARENTS FROM people p,students_join_people sjp WHERE sjp.PERSON_ID=p.STAFF_ID AND sjp.STUDENT_ID IN (SELECT DISTINCT STUDENT_ID FROM student_enrollment WHERE SYEAR=' . UserSyear() . ' AND SCHOOL_ID=' . UserSchool() . ')'));
    $num_parent = DBGet(DBQuery('SELECT COUNT(distinct p.STAFF_ID) as TOTAL_PARENTS FROM people p'));
    if ($num_parent[1]['TOTAL_PARENTS'] == '') {
        $num_parent = 0;
    } else {
        $num_parent = $num_parent[1]['TOTAL_PARENTS'];
    }

    echo '<div class="panel panel-default">';
    echo '<div class="tabbable">';
    echo '<ul class="nav nav-tabs nav-tabs-bottom no-margin-bottom"><li class="active" id="tab[]"><a href="javascript:void(0);">' . _atAGlance . '</a></li></ul>';
    echo '<div class="panel-body institute-report">';
    echo '<div class="row">';
    echo '<div class="col-md-4">';
    echo ' <div class="well m-b-15">';
    echo '<div class="media-left media-middle"><span class="institute-report-icon icon-school"></span></div>';
    echo '<div class="media-left">';
    echo '<h6 class="text-semibold no-margin">' . _institutions . '<span class="display-block no-margin text-success">' . $num_schools . '</span></h6>';
    echo '</div>';
    echo '</div>'; //.well
    echo '</div>'; //.col-md-4
    echo '<div class="col-md-4">';
    echo ' <div class="well m-b-15">';
    echo '<div class="media-left media-middle"><span class="institute-report-icon icon-student"></span></div>';
    echo '<div class="media-left">';
    echo '<h6 class="text-semibold no-margin">' . _students . '<span class="display-block no-margin text-success">' . $num_students . ' <small class="no-margin">(' . _male . ' : ' . $male . '  &nbsp; | &nbsp;  ' . _female . ' : ' . $female . ')</small></span></h6>';
    echo '</div>';
    echo '</div>'; //.well
    echo '</div>'; //.col-md-4
    echo '<div class="col-md-4">';
    echo ' <div class="well m-b-15">';
    echo '<div class="media-left media-middle"><span class="institute-report-icon icon-teacher"></span></div>';
    echo '<div class="media-left">';
    echo '<h6 class="text-semibold no-margin">' . _teachers . '<span class="display-block no-margin text-success">' . $num_teacher . '</span></h6>';
    echo '</div>';
    echo '</div>'; //.well
    echo '</div>'; //.col-md-4
    echo '</div>';
    echo '<div class="row">';
    echo '<div class="col-md-4">';
    echo ' <div class="well m-b-15">';
    echo '<div class="media-left media-middle"><span class="institute-report-icon icon-staff"></span></div>';
    echo '<div class="media-left">';
    echo '<h6 class="text-semibold no-margin">' . _staff . '<span class="display-block no-margin text-success">' . $num_staff . '</span></h6>';
    echo '</div>';
    echo '</div>'; //.well
    echo '</div>'; //.col-md-4
    echo '<div class="col-md-4">';
    echo ' <div class="well m-b-15">';
    echo '<div class="media-left media-middle"><span class="institute-report-icon icon-parent"></span></div>';
    echo '<div class="media-left">';
    echo '<h6 class="text-semibold no-margin">' . _parents . '<span class="display-block no-margin text-success">' . $num_parent . '</span></h6>';
    echo '</div>';
    echo '</div>'; //.well
    echo '</div>'; //.col-md-4
    echo '</div>'; //.row

    //    echo '<div id="d"><TABLE align=center cellpadding=5 cellspacing=5>';
    //    echo '<tr><td><b>Number of Institutions</b></td><td>:</td><td>&nbsp ' . $num_schools . ' &nbsp </td></tr>';
    //    echo '<tr><td><b>Number of Students</b></td><td>:</td><td>&nbsp ' . $num_students . ' &nbsp </td><td> &nbsp Male : ' . $male . ' &nbsp| &nbspFemale : ' . $female . '</td></tr>';
    //    echo '<tr><td><b>Number of Teachers</b></td><td>:</td><td colspan=2>&nbsp ' . $num_teacher . '</td></tr>';
    //    echo '<tr><td><b>Number of Staff</b></td><td>:</td><td colspan=2>&nbsp ' . $num_staff . '</td></tr>';
    //    echo '<tr><td><b>Number of Parents</b></td><td>:</td><td colspan=2>&nbsp ' . $num_parent . '</td></tr>';
    //    echo '</TABLE></div>';

    echo '</div>';
    echo '</div>'; //.tabbable
    echo '</div>'; //.panel
}

if ($_REQUEST['func'] == 'Ins_r') {
    if (clean_param($_REQUEST['modfunc'], PARAM_ALPHAMOD) == 'save') {
        echo "<table width=100%  style=\" font-family:Arial; font-size:12px;\" >";
        echo "<tr><td width=105>" . DrawLogo() . "</td><td style=\"font-size:15px; font-weight:bold; padding-top:20px;\">" . _instituteReports . "</td><td align=right style=\"padding-top:20px;\">" . ProperDate(DBDate()) . "<br />" . _poweredByOpenSis . "</td></tr><tr><td colspan=3 style=\"border-top:1px solid #333;\">&nbsp;</td></tr></table>";
        echo "<table >";

        $arr = array();

        if ($_REQUEST['fields']) {
            $i = 0;
            foreach ($_REQUEST['fields'] as $field => $on) {
                $columns .= $field . ',';
                $arr[$field] = $field;
            }

            $columns = substr($columns, 0, -1);
            foreach ($arr as $m => $n) {

                if ($m == 'E_MAIL') {
                    $arr[$m] = 'Email';
                } elseif ($m == 'TITLE') {
                    $arr[$m] = 'School Name';
                } elseif ($m == 'REPORTING_GP_SCALE') {
                    $arr[$m] = 'Base Grading Scale';
                } elseif ($m == 'MAIL_ADDRESS') {
                    $arr[$m] = 'Mailling Address';
                } elseif ($m == 'MAIL_CITY') {
                    $arr[$m] = 'Mailling City';
                } elseif ($m == 'MAIL_STATE') {
                    $arr[$m] = 'Malling State';
                } elseif ($m == 'MAIL_ZIP') {
                    $arr[$m] = 'Malling Zip';
                } elseif ($m == 'WWW_ADDRESS') {
                    $arr[$m] = 'Website';
                } else {
                    $col = explode('_', $m);
                    if ($col[0] == 'CUSTOM' && $col[1] != '') {
                        $get_field_name = DBGet(DBQuery('SELECT TITLE FROM school_custom_fields WHERE ID=' . $col[1]));
                    }

                    foreach ($col as $col_i => $col_d) {

                        $f_c = substr($col_d, 0, 1);
                        $r_c = substr($col_d, 1);
                        $txt = $f_c . strtolower($r_c);
                        unset($f_c);
                        unset($r_c);

                        $col[$col_i] = $txt;
                        unset($txt);
                    }
                    unset($col_i);
                    unset($col_d);
                    $col = implode(' ', $col);

                    if ($get_field_name[1]['TITLE'] != '') {
                        $arr[$m] = $get_field_name[1]['TITLE'];
                    } else {
                        $arr[$m] = $col;
                    }

                    unset($get_field_name);
                }
            }
            echo '<br>';

            $get_school_info = DBGet(DBQuery('SELECT ID,' . $columns . ' FROM schools'));

            echo '<br>';
            foreach ($get_school_info as $key => $value) {

                foreach ($value as $i => $j) {

                    $column_check = explode('_', $i);
                    if ($column_check[0] == 'CUSTOM') {
                        $check_validity = DBGet(DBQuery('SELECT COUNT(*) as REC_EX FROM school_custom_fields WHERE ID=' . $column_check[1] . ' AND (SCHOOL_ID=' . $get_school_info[$key]['ID'] . ' OR SCHOOL_ID=0)'));
                        if ($check_validity[1]['REC_EX'] == 0) {
                            $j = 'NOT_AVAILABLE_FOR';
                        }

                    }
                    $get_school_info[$key][$i] = trim($j);
                }
            }
            $show_legend = 'no';
            foreach ($get_school_info as $key => $value) {

                foreach ($value as $i => $j) {

                    if ($j == 'NOT_AVAILABLE_FOR') {
                        $show_legend = 'yes';
                        $get_school_info[$key][$i] = "<img src='assets/not_available.png' title='Not Applicable'/>";
                    }
                }
            }
            // print_r($get_school_info);

            echo "<html><link rel='stylesheet' type='text/css' href='styles/Export.css'><body style=\" font-family:Arial; font-size:12px;\">";
            ListOutputPrint_Institute_Report($get_school_info, $arr);

            echo "</body></html>";
        }
    } else {
        echo "<FORM action=ForExport.php?modname=$_REQUEST[modname]&head_html=Institute+Report&modfunc=save&_openSIS_PDF=true method=POST target=_blank>";
        echo '<DIV id=fields_div></DIV>';
        echo '<br/>';

        $fields_list['Available School Fields'] = array(
            'TITLE' => _schoolName,
            'ADDRESS' => _address,
            'CITY' => _city,
            'STATE' => _state,
            'ZIPCODE' => _zipcode,
            'PHONE' => _telephone,
            'PRINCIPAL' => _principal,
            'REPORTING_GP_SCALE' => _baseGradingScale,
            'E_MAIL' => _email,
            'WWW_ADDRESS' => _website,
        );
        $get_schools_cf = DBGet(DBQuery('SELECT * FROM school_custom_fields'));
        if (count($get_schools_cf) > 0) {
            foreach ($get_schools_cf as $gsc) {
                $fields_list['Available School Fields']['CUSTOM_' . $gsc[ID]] = $gsc['TITLE'];
            }
        }
        echo '<div class="row">';
        echo '<div class="col-md-8">';
        PopTable('header', '<i class=\"glyphicon glyphicon-tasks\"></i> &nbsp;' . _selectFieldsToGenerateReport . '');

        foreach ($fields_list as $category => $fields) {

            echo '<h5 class="text-primary">' . $category . '</h5>';
            $i = 0;
            $j = 0;
            foreach ($fields as $field => $title) {
                if ($i == 0 && $j == 0) {
                    echo '<div class="row">';
                } elseif ($i == 0 && $j > 0) {
                    echo '</div><div class="row">';
                }
                echo '<div class="col-md-6"><label class="checkbox-inline"><INPUT type=checkbox onclick="addHTML(\'<LI>' . $title . '</LI>\',\'names_div\',false);addHTML(\'<INPUT type=hidden name=fields[' . $field . '] value=Y>\',\'fields_div\',false);addHTML(\'\',\'names_div_none\',true);this.disabled=true">' . $title . '<label></div>';

                /*if ($i % 2 == 0)
                echo '</TR><TR>';*/
                $i++;
                if ($i == 2) {
                    $i = 0;
                }
                $j++;
            }
            echo '</div>';
            /*if ($i % 2 != 0) {
        echo '<TD></TD></TR><TR>';
        $i++;
        }*/
        }
        PopTable('footer');
        echo '</div><div class="col-md-4">';
        PopTable("header", "<i class=\"glyphicon glyphicon-saved\"></i> &nbsp;" . _selectedFields);
        echo '<div id="names_div_none" class="error_msg" style="padding:6px 0px 0px 6px;">' . _noFieldsSelected . '</div><ol id=names_div class="selected_report_list"></ol>';

        $btn = '<INPUT type=submit value=\'' . _createReportForInstitutes . '\' class="btn btn-primary">';
        PopTable('footer', $btn);
        echo '</div>'; //.col-md-6
        echo '</div>'; //.row
        echo "</FORM>";
    }
}
if ($_REQUEST['func'] == 'Ins_cf') {
    $get_schools_cf = DBGet(DBQuery('SELECT s.TITLE AS SCHOOL,s.ID,sc.* FROM schools s,school_custom_fields sc WHERE s.ID=sc.SCHOOL_ID OR sc.SCHOOL_ID=0 ORDER BY sc.SCHOOL_ID'));
    foreach ($get_schools_cf as $cf_i => $cf_d) {
        foreach ($cf_d as $cfd_i => $cfd_d) {
            if ($cfd_i == 'TYPE') {
                $fc = substr($cfd_d, 0, 1);
                $lc = substr($cfd_d, 1);
                $cfd_d = strtoupper($fc) . $lc;
                $get_schools_cf[$cf_i][$cfd_i] = $cfd_d;
                unset($fc);
                unset($lc);
            }
            if ($cfd_i == 'SELECT_OPTIONS' && $cf_d['TYPE'] != 'text') {

                for ($i = 0; $i < strlen($cfd_d); $i++) {
                    $char = substr($cfd_d, $i, 1);
                    if (ord($char) == '13') {
                        $char = '<br/>';
                    }

                    $new_char[] = $char;
                }

                $cfd_d = implode('', $new_char);
                $get_schools_cf[$cf_i][$cfd_i] = $cfd_d;
                unset($char);
                unset($new_char);
            }
            if ($cfd_i == 'REQUIRED') {
                if ($cfd_d == null) {
                    $get_schools_cf[$cf_i][$cfd_i] = 'No';
                }

                if ($cfd_d == 'Y') {
                    $get_schools_cf[$cf_i][$cfd_i] = 'Yes';
                }

            }
            if ($cfd_i == 'SCHOOL_ID') {
                if ($cfd_d == 0) {
                    $get_schools_cf[$cf_i]['SYSTEM_FIELD'] = 'Yes';
                } else {
                    $get_schools_cf[$cf_i]['SYSTEM_FIELD'] = 'No';
                }

            }
        }
        unset($cfd_i);
        unset($cfd_d);
    }
    foreach ($get_schools_cf as $g_i => $gd) {
        $gt_fld_v = DBGet(DBQuery('SELECT CUSTOM_' . $gd['ID'] . ' as FIELD from schools WHERE ID=' . $gd['SCHOOL_ID']));
        $get_schools_cf[$g_i]['C_VALUE'] = $gt_fld_v[1]['FIELD'];
    }

    $column = array(
        'SCHOOL' => _school,
        'TYPE' => _customFieldType,
        'TITLE' => _customFieldName,
        'SELECT_OPTIONS' => _options,
        'SYSTEM_FIELD' => _systemField,
        'REQUIRED' => _requiredField,
    );

    echo '<div class="panel panel-default">';
    ListOutput($get_schools_cf, $column, _customField, _customFields);
    echo '</div>';
}
