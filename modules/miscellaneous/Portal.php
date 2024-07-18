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
include "lang/language.php";
while (!UserSyear()) {
    session_write_close();
    session_start();
}
$this_portal_toggle = "";
$current_hour = date('H');
$welcome .= ''._user.' : ' . User('NAME');
$userName = User('USERNAME');
$link = array();
$id = array();
$arr = array();
$qr = "select to_user,mail_id,to_cc,to_bcc from msg_inbox where isdraft is NULL";
$fetch = DBGet(DBQuery($qr));
$id_arr = array();
foreach ($fetch as $key => $value) {
    $to = $value['TO_USER'];
    "<br>";
    $cc = $value['TO_CC'];
    $bcc = $value['TO_BCC'];
    $mul = $value['TO_MULTIPLE_USERS'];
    $mul_cc = $value['TO_CC_MULTIPLE'];
    $mul_bcc = $value['TO_BCC_MULTIPLE'];

    $to_arr = explode(',', $to);
    $arr_cc = explode(',', $cc);
    $arr_bcc = explode(',', $bcc);
    $arr_mul = explode(',', $mul);

    if (in_array($userName, $to_arr) || in_array($userName, $arr_mul) || in_array($userName, $arr_bcc) || in_array($userName, $arr_cc) || in_array($userName, $arr_cc) || in_array($userName, $arr_bcc)) {
        array_push($id_arr, $value['MAIL_ID']);
    }
}


$total_count = count($id_arr);
if ($total_count > 0)
    $to_user_id = implode(',', $id_arr);
else
    $to_user_id = 'null';
$inbox = "select count(*) as total from msg_inbox where mail_id in($to_user_id) and FIND_IN_SET('$userName', mail_read_unread )";

$in = DBGet(DBQuery($inbox));
$in = $in[1]['TOTAL'];

$inbox_info = $total_count - $in;
if ($inbox_info > 1) {
    echo '<div class="alert alert-danger alert-bordered">';
    echo '<i class="fa fa-info-circle"></i> '._youHave.' ' . $inbox_info . ' '._unreadMessages.'';
    echo '</div>';
} else {
    if ($inbox_info == 1) {
        echo '<div class="alert alert-danger alert-bordered">';
        echo '<i class="fa fa-info-circle"></i> '._youHaveOneUnreadMessage.'';
        echo '</div>';
    }
}

if ($_SESSION['PROFILE_ID'] == 0)
    $title1 = _superAdministrator;
if ($_SESSION['PROFILE_ID'] == 1)
    $title1 = _administrator;

switch (User('PROFILE')) {
    case 'admin':
        DrawBC($welcome . ' | '._role.' : ' . $title1);
        $update_notify_s = DBGet(DBQuery('SELECT VALUE FROM program_config WHERE school_id=\'' . UserSchool() . '\'  AND program=\'UPDATENOTIFY\' AND title=\'display_school\' LIMIT 0, 1'));
        if ($update_notify_s[1]['VALUE'] == 'Y') {
            $cal_setup = DBGet(DBQuery('SELECT COUNT(*) as REC FROM school_calendars WHERE SCHOOL_ID=' . UserSchool() . ' AND SYEAR=' . UserSyear()));
            $mp_setup = DBGet(DBQuery('SELECT COUNT(*) as REC FROM marking_periods WHERE SCHOOL_ID=' . UserSchool() . ' AND SYEAR=' . UserSyear()));
            $att_code_setup = DBGet(DBQuery('SELECT COUNT(*) as REC FROM attendance_codes WHERE SCHOOL_ID=' . UserSchool() . ' AND SYEAR=' . UserSyear()));
            $grade_scale_setup = DBGet(DBQuery('SELECT COUNT(*) as REC FROM report_card_grade_scales WHERE SCHOOL_ID=' . UserSchool() . ' AND SYEAR=' . UserSyear()));
            $enroll_code_setup = DBGet(DBQuery('SELECT COUNT(*) as REC FROM student_enrollment_codes WHERE SYEAR=' . UserSyear()));
            $grade_level_setup = DBGet(DBQuery('SELECT COUNT(*) as REC FROM school_gradelevels WHERE SCHOOL_ID=' . UserSchool()));
            $periods_setup = DBGet(DBQuery('SELECT COUNT(*) as REC FROM school_periods WHERE SCHOOL_ID=' . UserSchool() . ' AND SYEAR=' . UserSyear()));
            $rooms_setup = DBGet(DBQuery('SELECT COUNT(*) as REC FROM rooms WHERE SCHOOL_ID=' . UserSchool()));
            if ($cal_setup[1]['REC'] == 0 || $mp_setup[1]['REC'] < 1 || $att_code_setup[1]['REC'] == 0 || $grade_scale_setup[1]['REC'] == 0 || $enroll_code_setup[1]['REC'] == 0 || $grade_level_setup[1]['REC'] == 0 || $periods_setup[1]['REC'] == 0 || $rooms_setup[1]['REC'] == 0) {
                $width = 0;
                $percent = 0;

                if ($cal_setup[1]['REC'] > 0)
                    $width = $width + 52.5;
                if ($mp_setup[1]['REC'] > 1)
                    $width = $width + 52.5;
                if ($att_code_setup[1]['REC'] > 0)
                    $width = $width + 52.5;
                if ($grade_scale_setup[1]['REC'] > 0)
                    $width = $width + 52.5;
                if ($enroll_code_setup[1]['REC'] > 0)
                    $width = $width + 52.5;
                if ($grade_level_setup[1]['REC'] > 0)
                    $width = $width + 52.5;
                if ($periods_setup[1]['REC'] > 0)
                    $width = $width + 52.5;
                if ($rooms_setup[1]['REC'] > 0)
                    $width = $width + 52.5;

                $percent = ($width / 420) * 100;

                echo '<div class="panel panel-flat">
                        <div class="panel-heading">
                            <h6 class="panel-title">'._pleaseCompleteTheSetupBeforeUsingTheSystemTheFollowingComponentsNeedToBeSet.'</h6>
                            <div class="heading-elements">
                                <div class="progress">
                                    <div class="progress-bar progress-bar-success" style="width: ' . $percent . '%;">
                                        <span>' . $percent . '% Complete</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-xs-12 col-sm-6 col-md-4 col-lg-4 mb-15">
                                    <div class="well">
                                        <div class="media-left media-middle">' . (AllowUse('schoolsetup/Calendar.php') == true ? '<a href="javascript:void(0);" class="btn border-indigo-400 text-indigo-400 btn-flat btn-rounded btn-xs btn-icon" onClick="check_content(\'Ajax.php?modname=schoolsetup/Calendar.php\');">' : '') . '<i class="icon-calendar3"></i>' . (AllowUse('schoolsetup/Calendar.php') == true ? '</a>' : '') . '</div>

                                        <div class="media-left">
                                            <h6 class="text-semibold no-margin">' . (AllowUse('schoolsetup/Calendar.php') == true ? '<a href="javascript:void(0);" onClick="check_content(\'Ajax.php?modname=schoolsetup/Calendar.php\');">' : '') . ''._calendarSetup.' ' . ($cal_setup[1]['REC'] > 0 ? '<small class="display-block no-margin text-success"><i class="icon-checkmark2"></i> '._complete.'</small>' : '<small class="display-block no-margin text-danger"><i class="icon-cross3"></i> '._incomplete.'</small>') . (AllowUse('schoolsetup/Calendar.php') == true ? '</a>' : '') . '</h6>
                                        </div>
                                    </div>
                                </div>


                                <div class="col-xs-12 col-sm-6 col-md-4 col-lg-4 mb-15">
                                    <div class="well">
                                        <div class="media-left media-middle">' . (AllowUse('schoolsetup/MarkingPeriods.php') == true ? '<a href="javascript:void(0);" class="btn border-indigo-400 text-indigo-400 btn-flat btn-rounded btn-xs btn-icon" onClick="check_content(\'Ajax.php?modname=schoolsetup/MarkingPeriods.php\');">' : '') . '<i class="icon-tree7"></i>' . (AllowUse('schoolsetup/MarkingPeriods.php') == true ? '</a>' : '') . '</div>

                                        <div class="media-left">
                                            <h6 class="text-semibold no-margin">' . (AllowUse('schoolsetup/MarkingPeriods.php') == true ? '<a href="javascript:void(0);" onClick="check_content(\'Ajax.php?modname=schoolsetup/MarkingPeriods.php\');">' : '') . ''._markingPeriodSetup.'</a> ' . ($mp_setup[1]['REC'] > 1 ? '<small class="display-block no-margin text-success"><i class="icon-checkmark2"></i> '._complete.'</small>' : '<small class="display-block no-margin text-danger"><i class="icon-cross3"></i> '._incomplete.'</small>') . (AllowUse('schoolsetup/MarkingPeriods.php') == true ? '</a>' : '') . '</h6>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-xs-12 col-sm-6 col-md-4 col-lg-4 mb-15">
                                    <div class="well">
                                        <div class="media-left media-middle">' . (AllowUse('attendance/AttendanceCodes.php') == true ? '<a href="javascript:void(0);" class="btn border-indigo-400 text-indigo-400 btn-flat btn-rounded btn-xs btn-icon" onClick="check_content(\'Ajax.php?modname=attendance/AttendanceCodes.php\');">' : '') . '<i class="icon-clipboard5"></i>' . (AllowUse('attendance/AttendanceCodes.php') == true ? '</a>' : '') . '</div>

                                        <div class="media-left">
                                            <h6 class="text-semibold no-margin">' . (AllowUse('attendance/AttendanceCodes.php') == true ? '<a href="javascript:void(0);" onClick="check_content(\'Ajax.php?modname=attendance/AttendanceCodes.php\');">' : '') . ''._attendanceCodeSetup.' ' . ($att_code_setup[1]['REC'] > 0 ? '<small class="display-block no-margin text-success"><i class="icon-checkmark2"></i> '._complete.'</small>' : '<small class="display-block no-margin text-danger"><i class="icon-cross3"></i> '._incomplete.'</small>') . (AllowUse('attendance/AttendanceCodes.php') == true ? '</a>' : '') . '</h6>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-xs-12 col-sm-6 col-md-4 col-lg-4 mb-15">
                                    <div class="well">
                                        <div class="media-left media-middle">' . (AllowUse('grades/ReportCardGrades.php') == true ? '<a href="javascript:void(0);" class="btn border-indigo-400 text-indigo-400 btn-flat btn-rounded btn-xs btn-icon" onClick="check_content(\'Ajax.php?modname=grades/ReportCardGrades.php\');">' : '') . '<i class="icon-stack3"></i>' . (AllowUse('grades/ReportCardGrades.php') == true ? '</a>' : '') . '</div>

                                        <div class="media-left">
                                            <h6 class="text-semibold no-margin">' . (AllowUse('grades/ReportCardGrades.php') == true ? '<a href="javascript:void(0);" onClick="check_content(\'Ajax.php?modname=grades/ReportCardGrades.php\');">' : '') . ''._gradeScaleSetup.' ' . ($grade_scale_setup[1]['REC'] > 0 ? '<small class="display-block no-margin text-success"><i class="icon-checkmark2"></i> '._complete.'</small>' : '<small class="display-block no-margin text-danger"><i class="icon-cross3"></i> '._incomplete.'</small>') . (AllowUse('grades/ReportCardGrades.php') == true ? '</a>' : '') . '</h6>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-xs-12 col-sm-6 col-md-4 col-lg-4 mb-15">
                                    <div class="well">
                                        <div class="media-left media-middle">' . (AllowUse('students/EnrollmentCodes.php') == true ? '<a href="javascript:void(0);" class="btn border-indigo-400 text-indigo-400 btn-flat btn-rounded btn-xs btn-icon" onClick="check_content(\'Ajax.php?modname=students/EnrollmentCodes.php\');">' : '') . '<i class="icon-clipboard6"></i>' . (AllowUse('students/EnrollmentCodes.php') == true ? '</a>' : '') . '</div>

                                        <div class="media-left">
                                            <h6 class="text-semibold no-margin">' . (AllowUse('students/EnrollmentCodes.php') == true ? '<a href="javascript:void(0);" onClick="check_content(\'Ajax.php?modname=students/EnrollmentCodes.php\');">' : '') . ''._enrollmentCodeSetup.' ' . ($enroll_code_setup[1]['REC'] > 0 ? '<small class="display-block no-margin text-success"><i class="icon-checkmark2"></i> '._complete.'</small>' : '<small class="display-block no-margin text-danger"><i class="icon-cross3"></i> '._incomplete.'</small>') . (AllowUse('students/EnrollmentCodes.php') == true ? '</a>' : '') . '</h6>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-xs-12 col-sm-6 col-md-4 col-lg-4 mb-15">
                                    <div class="well">
                                        <div class="media-left media-middle">' . (AllowUse('schoolsetup/GradeLevels.php') == true ? '<a href="javascript:void(0);" class="btn border-indigo-400 text-indigo-400 btn-flat btn-rounded btn-xs btn-icon" onClick="check_content(\'Ajax.php?modname=schoolsetup/GradeLevels.php\');">' : '') . '<i class="icon-graph"></i>' . (AllowUse('schoolsetup/GradeLevels.php') == true ? '</a>' : '') . '</div>

                                        <div class="media-left">
                                            <h6 class="text-semibold no-margin">' . (AllowUse('schoolsetup/GradeLevels.php') == true ? '<a href="javascript:void(0);" onClick="check_content(\'Ajax.php?modname=schoolsetup/GradeLevels.php\');">' : '') . ''._gradeLevelSetup.' ' . ($grade_level_setup[1]['REC'] > 0 ? '<small class="display-block no-margin text-success"><i class="icon-checkmark2"></i> '._complete.'</small>' : '<small class="display-block no-margin text-danger"><i class="icon-cross3"></i> '._incomplete.'</small>') . (AllowUse('schoolsetup/GradeLevels.php') == true ? '</a>' : '') . '</h6>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-xs-12 col-sm-6 col-md-4 col-lg-4 mb-15">
                                    <div class="well">
                                        <div class="media-left media-middle">' . (AllowUse('schoolsetup/Periods.php') == true ? '<a href="javascript:void(0);" class="btn border-indigo-400 text-indigo-400 btn-flat btn-rounded btn-xs btn-icon" onClick="check_content(\'Ajax.php?modname=schoolsetup/Periods.php\');">' : '') . '<i class="icon-watch2"></i>' . (AllowUse('schoolsetup/Periods.php') == true ? '</a>' : '') . '</div>

                                        <div class="media-left">
                                            <h6 class="text-semibold no-margin">' . (AllowUse('schoolsetup/Periods.php') == true ? '<a href="javascript:void(0);" onClick="check_content(\'Ajax.php?modname=schoolsetup/Periods.php\');">' : '') . ''._schoolPeriodsSetup.' ' . ($periods_setup[1]['REC'] > 0 ? '<small class="display-block no-margin text-success"><i class="icon-checkmark2"></i> '._complete.'</small>' : '<small class="display-block no-margin text-danger"><i class="icon-cross3"></i> '._incomplete.'</small>') . (AllowUse('schoolsetup/Periods.php') == true ? '</a>' : '') . '</h6>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-xs-12 col-sm-6 col-md-4 col-lg-4 mb-15">
                                    <div class="well">
                                        <div class="media-left media-middle">' . (AllowUse('schoolsetup/Rooms.php') == true ? '<a href="javascript:void(0);" class="btn border-indigo-400 text-indigo-400 btn-flat btn-rounded btn-xs btn-icon" onClick="check_content(\'Ajax.php?modname=schoolsetup/Rooms.php\');">' : '') . '<i class="icon-grid6"></i>' . (AllowUse('schoolsetup/Rooms.php') == true ? '</a>' : '') . '</div>

                                        <div class="media-left">
                                            <h6 class="text-semibold no-margin">' . (AllowUse('schoolsetup/Rooms.php') == true ? '<a href="javascript:void(0);" onClick="check_content(\'Ajax.php?modname=schoolsetup/Rooms.php\');">' : '') . ''._roomsSetup.' ' . ($rooms_setup[1]['REC'] > 0 ? '<small class="display-block no-margin text-success"><i class="icon-checkmark2"></i> '._complete.'</small>' : '<small class="display-block no-margin text-danger"><i class="icon-cross3"></i> '._incomplete.'</small>') . (AllowUse('schoolsetup/Rooms.php') == true ? '</a>' : '') . '</h6>
                                        </div>
                                    </div>
                                </div>
                            </div><!-- /.row -->
                        </div><!-- //.panel-body -->
                                               

                    </div>';
            }
        }
        $notes_RET = DBGet(DBQuery('SELECT IF(pn.published_profiles like\'%all%\',\'All School\',(SELECT TITLE FROM schools WHERE id=pn.school_id)) AS SCHOOL,pn.LAST_UPDATED,CONCAT(\'<b>\',pn.TITLE,\'</b>\') AS TITLE,pn.CONTENT 
                                    FROM portal_notes pn
                                    WHERE pn.SYEAR=\'' . UserSyear() . '\' AND pn.START_DATE<=CURRENT_DATE AND 
                                        (pn.END_DATE>=CURRENT_DATE OR pn.END_DATE IS NULL)
                                        AND (pn.published_profiles like\'%all%\' OR pn.school_id IN(' . UserSchool() . '))
                                        AND (' . (User('PROFILE_ID') == '' ? ' FIND_IN_SET(\'admin\', pn.PUBLISHED_PROFILES)>0' : ' FIND_IN_SET(' . User('PROFILE_ID') . ',pn.PUBLISHED_PROFILES)>0)') .
                        'ORDER BY pn.SORT_ORDER,pn.LAST_UPDATED DESC'), array('LAST_UPDATED' => 'ProperDate', 'CONTENT' => '_nl2br'));
      
      
                        if (count($notes_RET)) {
            echo '<div class="panel panel-default">';
            ListOutput($notes_RET, array('LAST_UPDATED' =>_datePosted,
             'TITLE' =>_title,
             'CONTENT' =>_note,
             'SCHOOL' =>_school,
            ), _note, _notes, array(), array(), array('save' =>false, 'search' =>false));
            echo '</div>';
        }
        $events_RET = DBGet(DBQuery('SELECT ce.TITLE,ce.DESCRIPTION,ce.SCHOOL_DATE AS INDEX_DATE,ce.SCHOOL_DATE,s.TITLE AS SCHOOL 
                FROM calendar_events ce,calendar_events_visibility cev,schools s
                WHERE ce.SCHOOL_DATE BETWEEN CURRENT_DATE AND CURRENT_DATE + INTERVAL 30 DAY 
                    AND ce.SYEAR=\'' . UserSyear() . '\'
                    AND ce.SCHOOL_ID IN(' . UserSchool(). ')
                    AND s.ID=ce.SCHOOL_ID AND (ce.CALENDAR_ID=cev.CALENDAR_ID)
                    AND ' . (User('PROFILE_ID') == '' ? 'cev.PROFILE=\'admin\'' : 'cev.PROFILE_ID=\'' . User('PROFILE_ID')) . '\' 
                    ORDER BY ce.SCHOOL_DATE,s.TITLE'), array('SCHOOL_DATE' => 'ProperDate', 'DESCRIPTION' => 'makeDescription'));

        $events_RET1 = DBGet(DBQuery('SELECT ce.TITLE,ce.DESCRIPTION, ce.SCHOOL_DATE as index_date,ce.SCHOOL_DATE,s.TITLE AS SCHOOL 
                FROM calendar_events ce,schools s
                WHERE ce.SCHOOL_DATE BETWEEN CURRENT_DATE AND CURRENT_DATE + INTERVAL 30 DAY 
                    AND ce.SYEAR=\'' . UserSyear() . '\'
                    AND s.ID=ce.SCHOOL_ID AND ce.CALENDAR_ID=0 ORDER BY ce.SCHOOL_DATE,s.TITLE'), array('SCHOOL_DATE' => 'ProperDate', 'DESCRIPTION' => 'makeDescription'));
        $event_count = count($events_RET) + 1;
        foreach ($events_RET1 as $events_RET_key => $events_RET_value) {
            $events_RET[$event_count] = $events_RET_value;
            $event_count++;
        }


        $new_arr = array();
        foreach ($events_RET as $key => $val) {
            $new_arr[strtotime($val['INDEX_DATE'])][$key] = $val;
        }
        ksort($new_arr);
        $keyt = 1;
        foreach ($new_arr as $key1 => $val1) {
            foreach ($val1 as $val2) {
                $events_RET[$keyt] = $val2;
                $keyt++;
            }
        }
        if (count($events_RET)) {
            echo '<div class="panel panel-default">';
            ListOutput($events_RET, array('SCHOOL_DATE' =>_date,
             'TITLE' =>_event,
             'DESCRIPTION' =>_description,
             'SCHOOL' =>_school,
            ), _upcomingEvent, _upcomingEvents, array(), array(), array('save' =>false, 'search' =>false));
            echo '</div>'; //.panel
        }
            if (Preferences('HIDE_ALERTS') != 'Y') {
            $RET = DBGet(DBQuery('SELECT mi.SCHOOL_ID,mi.SCHOOL_DATE,mi.COURSE_PERIOD_ID,mi.TEACHER_ID,mi.SECONDARY_TEACHER_ID FROM missing_attendance mi,course_periods cp,schools s,course_period_var cpv WHERE mi.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID AND cp.COURSE_PERIOD_ID=cpv.COURSE_PERIOD_ID AND cpv.PERIOD_ID=mi.PERIOD_ID AND s.ID=mi.SCHOOL_ID and mi.SCHOOL_ID=\'' . UserSchool() . '\' AND mi.SYEAR=\'' . UserSyear() . '\' AND mi.SCHOOL_DATE<\'' . date('Y-m-d') . '\'  AND (mi.SCHOOL_DATE=cpv.COURSE_PERIOD_DATE OR POSITION(IF(DATE_FORMAT(mi.SCHOOL_DATE,\'%a\') LIKE \'Thu\',\'H\',(IF(DATE_FORMAT(mi.SCHOOL_DATE,\'%a\') LIKE \'Sun\',\'U\',SUBSTR(DATE_FORMAT(mi.SCHOOL_DATE,\'%a\'),1,1)))) IN cpv.DAYS)>0)'));

            if (count($RET)) {
                echo '<div class="alert alert-danger alert-styled-left alert-bordered">';
                //echo '<button type="button" class="close" data-dismiss="alert"><span>×</span><span class="sr-only">Close</span></button>';
                echo '<span class="text-bold">'._warning.'!!</span> - '._teachersHaveMissingAttendance.'. '._go_To.': <span class="text-bold">'._users.' <i class="icon-arrow-right13"></i>'._teacherPrograms.' <i class="icon-arrow-right13"></i> '._missingAttendance.'.</span>';
                echo '</div>';
            }
        }
        echo '<div id="attn_alert" style="display: none" class="alert alert-danger alert-styled-left alert-bordered"><span class="text-bold">'._warning.'!!</span> - '._teachersHaveMissingAttendance.'. '._go_To.' : <b>Users <i class="icon-arrow-right13"></i> '._teacherPrograms.'<i class="icon-arrow-right13"></i>'._missingAttendance.'</b></div>';
        //-------------------------------------------------------------------------------ROLLOVER NOTIFICATION STARTS----------------------------------------------------------------------------------------------------------------------------------------------------------------------------

        $notice_date = DBGet(DBQuery('SELECT END_DATE FROM school_years WHERE SYEAR=\'' . UserSyear() . '\' AND SCHOOL_ID=\'' . UserSchool() . '\''));
        $notice_roll_date = DBGet(DBQuery('SELECT SYEAR FROM school_years WHERE SYEAR>\'' . UserSyear() . '\' AND SCHOOL_ID=\'' . UserSchool() . '\''));
        $rolled = count($notice_roll_date);
        $last_date = strtotime($notice_date[1]['END_DATE']) - strtotime(DBDate());
        $last_date = $last_date / (60 * 60 * 24);
        if ($last_date <= 15 && $rolled == 0) {
            echo '<div class="alert alert-warning alert-styled-left">'._schoolYearIsEndingOrHasEndedRolloverRequired.'.</div>';
        }
        //-------------------------------------------------------------------------------ROLLOVER NOTIFICATION ENDS----------------------------------------------------------------------------------------------------------------------------------------------------------------------------


        break;

    case 'teacher':
        DrawBC($welcome . ' | '._role.' : '._teacher.'');
        $notes_RET = DBGet(DBQuery('SELECT IF(pn.school_id IS NULL,\'All School\',(SELECT TITLE FROM schools WHERE id=pn.school_id)) AS SCHOOL,pn.LAST_UPDATED,CONCAT(\'<b>\',pn.TITLE,\'</b>\') AS TITLE,pn.CONTENT 
                            FROM portal_notes pn
                            WHERE pn.SYEAR=\'' . UserSyear() . '\' AND pn.START_DATE<=CURRENT_DATE AND 
                                (pn.END_DATE>=CURRENT_DATE OR pn.END_DATE IS NULL)
                                AND (pn.school_id IS NULL OR pn.school_id IN(' . GetUserSchools(UserID(), true) . '))
                                AND (' . (User('PROFILE_ID') == '' ? ' FIND_IN_SET(\'teacher\', pn.PUBLISHED_PROFILES)>0' : ' FIND_IN_SET(' . User('PROFILE_ID') . ',pn.PUBLISHED_PROFILES)>0)') . '
                                ORDER BY pn.SORT_ORDER,pn.LAST_UPDATED DESC'), array('LAST_UPDATED' => 'ProperDate', 'CONTENT' => '_nl2br'));

        if (count($notes_RET)) {
            echo '<div class="panel panel-default">';
            ListOutput($notes_RET, array('LAST_UPDATED' =>_datePosted,
             'TITLE' =>_title,
             'CONTENT' =>_note,
             'SCHOOL' =>_school,
            ), _note, _notes, array(), array(), array('save' =>false, 'search' =>false));
            echo '</div>';
        }
        do_cado_teacher_courses_files();
        break;

    case 'parent':
        $notes_RET = DBGet(DBQuery('SELECT IF(pn.school_id IS NULL,\'All School\',(SELECT TITLE FROM schools WHERE id=pn.school_id)) AS SCHOOL,pn.LAST_UPDATED,pn.TITLE,pn.CONTENT 
            FROM portal_notes pn
            WHERE pn.SYEAR=\'' . UserSyear() . '\' 
                AND pn.START_DATE<=CURRENT_DATE AND (pn.END_DATE>=CURRENT_DATE OR pn.END_DATE IS NULL) 
                AND (pn.school_id IS NULL OR pn.school_id IN(' . GetUserSchools(UserID(), true) . '))
                AND (' . (User('PROFILE_ID') == '' ? ' FIND_IN_SET(\'parent\', pn.PUBLISHED_PROFILES)>0' : ' FIND_IN_SET(' . User('PROFILE_ID') . ',pn.PUBLISHED_PROFILES)>0)') . '
                ORDER BY pn.SORT_ORDER,pn.LAST_UPDATED DESC'), array('LAST_UPDATED' => 'ProperDate', 'CONTENT' => '_nl2br'));

        if (count($notes_RET)) {
            echo '<div class="panel">';
            ListOutput($notes_RET, array('LAST_UPDATED' =>_datePosted,
             'TITLE' =>_title,
             'CONTENT' =>_note,
             'SCHOOL' =>_school,
            ), _note, _notes, array(), array(), array('save' =>false, 'search' =>false));
            echo '</div>';
        }
        DrawBC($welcome . ' | '._role.' : '._parent.'');
        do_cado_bulletins();
        do_cado_courses_files();
        break;

    case 'student':
        DrawBC($welcome . ' | '._role.' : '._student.'');

        $notes_RET = DBGet(DBQuery('SELECT IF(pn.school_id IS NULL,\'All School\',(SELECT TITLE FROM schools WHERE id=pn.school_id)) AS SCHOOL,pn.LAST_UPDATED,pn.TITLE,pn.CONTENT 
            FROM portal_notes pn
            WHERE pn.SYEAR=\'' . UserSyear() . '\' 
                AND pn.START_DATE<=CURRENT_DATE AND (pn.END_DATE>=CURRENT_DATE OR pn.END_DATE IS NULL) 
                AND (pn.school_id IS NULL OR pn.SCHOOL_ID=\'' . UserSchool() . '\') 
                AND  position(\',3,\' IN pn.PUBLISHED_PROFILES)>0
                ORDER BY pn.SORT_ORDER,pn.LAST_UPDATED DESC'), array('LAST_UPDATED' => 'ProperDate', 'CONTENT' => '_nl2br'));

        if (count($notes_RET)) {
            echo '<div class="panel panel-default">';

            ListOutput($notes_RET, array('LAST_UPDATED' => _datePosted,
             'TITLE' => _title,
             'CONTENT' => _note,
            ), _note, _notes, array(), array(), array('save' =>false, 'search' =>false));
            echo '</div>';
        }

        do_cado_bulletins();
        do_cado_courses_files();
        break;
}

function _nl2br($value, $column) {
    return nl2br($value);
}

function makeDescription($value, $column) {
    return '<div style="width:450px;word-wrap:break-word;">' . $value . '</div>';
}

function do_cado_teacher_courses_files(){
    $courses_RET = DBGet(DBQuery('SELECT DISTINCT c.TITLE ,cp.SHORT_NAME,cp.COURSE_PERIOD_ID,cp.COURSE_ID,cp.TEACHER_ID AS STAFF_ID,cpv.PERIOD_ID AS PERIOD_ID FROM schedule s,course_periods cp,course_period_var cpv,courses c,attendance_calendar acc WHERE s.SYEAR=\'' . UserSyear() . '\' AND cp.COURSE_PERIOD_ID=s.COURSE_PERIOD_ID  AND cp.COURSE_PERIOD_ID=cpv.COURSE_PERIOD_ID  AND (s.MARKING_PERIOD_ID IN (SELECT MARKING_PERIOD_ID FROM school_years WHERE SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE  UNION SELECT MARKING_PERIOD_ID FROM school_semesters WHERE SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE  UNION SELECT MARKING_PERIOD_ID FROM school_quarters WHERE SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE )or s.MARKING_PERIOD_ID  is NULL) AND cp.GRADE_SCALE_ID IS NOT NULL' . (User('PROFILE') == 'teacher' ? ' AND cp.TEACHER_ID=\'' . User('STAFF_ID') . '\'' : '') . ' AND c.COURSE_ID=cp.COURSE_ID ORDER BY SHORT_NAME'));
    echo '<div class="H2"> Liste de vos cours et fichiers publiés.';
    echo '</div>';
    $num_course=1;
    $last_course_id=0;
    foreach ($courses_RET as $course) {
        if($course['COURSE_ID'] != $last_course_id) {
        //print_r($course);
        $staff_id = $course['STAFF_ID'];
        if (count($courses_RET)) {
            $list_RET = '';
            $bad_weght=check_weight($course['COURSE_PERIOD_ID'],$course['STAFF_ID'],UserMP(),$course['COURSE_ID']);
            $bad_config=check_config($course['COURSE_PERIOD_ID'],$course['STAFF_ID'],UserMP(),$course['COURSE_ID']);
            if(round(GetGroupAverage($course['COURSE_PERIOD_ID'],UserMP(),UserSyear(),$course['SHORT_NAME'])) > 0 && round(GetGroupAverage($course['COURSE_PERIOD_ID'],UserMP(),UserSyear(),$course['SHORT_NAME'])) != 'NAN')
                $bad_final = 0;
            else 
                $bad_final = 1;
            if($bad_config)
                $list_RET .= '<b style="color:red;"></b><i class="fa fa-times fa-lg text-danger"></i>Configuration';
            else 
                $list_RET .= '<i class="fa fa-check fa-lg text-success"></i>Configuration';
            if($bad_weght) 
                $list_RET .= '<b style="color:red;"></b><i class="fa fa-times fa-lg text-danger"></i>Pondération';
            else 
                $list_RET .= '<i class="fa fa-check fa-lg text-success"></i>Pondération';
            if($bad_final)
                $list_RET .= '<b style="color:red;"></b><i class="fa fa-times fa-lg text-danger"></i>Note Final';
            else 
                $list_RET .= '<i class="fa fa-check fa-lg text-success"></i>Note finale';
                $list_RET .= '<br>';
            // if(! $bad_final && ! $bad_config && ! $bad_weght)
            // $list_RET .= '<i class="fa fa-check fa-lg text-success"></i>';
            $fileIcon = '<i class="fa fa-file-word-o"></i>';
            $search='%[';
            $search.=$course['COURSE_PERIOD_ID'];
            $search.=']%';
            $fileid = DBGet(DBQuery('SELECT * FROM user_file_upload WHERE name like "' . $search . '" AND PROFILE_ID=2 AND syear=' . UserSyear() . ' AND user_id=' . $course['STAFF_ID'] . ' AND FILE_INFO="stafffile" ORDER BY NAME'));
            echo '<div class="panel">';
            DrawHeader('<span class="text-bold">'. $num_course++ .'  - ' . substr($course['SHORT_NAME'] . ' </span>', strrpos(str_replace(' - ', ' ^ ', $course['TITLE']), '^')),$list_RET);
            //echo $list_RET;
            echo '<hr class="no-margin" />';
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
                if($file['DOWNLOAD_ID'])
                    $show_filename=strstr($file['NAME'], ']');
                    $show_filename=trim($show_filename, "]");
                     echo '<a class="files" href="DownloadWindow.php?down_id=' . $file['DOWNLOAD_ID'] . '&stafffile=Y"> ' . $fileIcon . ' &nbsp; '. $show_filename . '</a>';
                 echo '<div></div>';
            }
            echo '</div>';
            echo '</td></tr></table>';
        }
     $last_course_id = $course['COURSE_ID'];
    }
    }
}
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
function do_cado_courses_files(){
    $courses_RET = DBGet(DBQuery('SELECT DISTINCT c.TITLE ,cp.SHORT_NAME,cp.COURSE_PERIOD_ID,cp.COURSE_ID,cp.TEACHER_ID AS STAFF_ID FROM schedule s,course_periods cp,course_period_var cpv,courses c,attendance_calendar acc WHERE s.SYEAR=\'' . UserSyear() . '\' AND cp.COURSE_PERIOD_ID=s.COURSE_PERIOD_ID  AND cp.COURSE_PERIOD_ID=cpv.COURSE_PERIOD_ID  AND (s.MARKING_PERIOD_ID IN (SELECT MARKING_PERIOD_ID FROM school_years WHERE SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE  UNION SELECT MARKING_PERIOD_ID FROM school_semesters WHERE SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE  UNION SELECT MARKING_PERIOD_ID FROM school_quarters WHERE SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE )or s.MARKING_PERIOD_ID  is NULL) AND (\'' . DBDate() . '\' BETWEEN s.START_DATE AND s.END_DATE OR \'' . DBDate() . '\'>=s.START_DATE AND s.END_DATE IS NULL) AND s.STUDENT_ID=\'' . UserStudentID() . '\' AND cp.GRADE_SCALE_ID IS NOT NULL' . (User('PROFILE') == 'teacher' ? ' AND cp.TEACHER_ID=\'' . User('STAFF_ID') . '\'' : '') . ' AND c.COURSE_ID=cp.COURSE_ID ORDER BY TITLE'));
    $num_course=1;
    foreach ($courses_RET as $course) {
        $staff_id = $course['STAFF_ID'];
        if (count($courses_RET)) {
            $search='%[';
            $search.=$course['COURSE_PERIOD_ID'];
            $search.=']%';
            $fileid = DBGet(DBQuery('SELECT * FROM user_file_upload WHERE name like "' . $search . '" AND PROFILE_ID=2 AND syear=' . UserSyear() . ' AND user_id=' . $course['STAFF_ID'] . ' AND FILE_INFO="stafffile" ORDER BY NAME'));
            echo '<div class="panel">';
            if($fileid){
                DrawHeader('<span class="text-bold">' . substr($course['SHORT_NAME'] . ' </span>', strrpos(str_replace(' - ', ' ^ ', $course['TITLE']), '^')));
                echo '<hr class="no-margin" />';
            }
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
                if($file['DOWNLOAD_ID'])
                    $show_filename=strstr($file['NAME'], ']');
                    $show_filename=trim($show_filename, "]");
                     echo '<a class="files" href="DownloadWindow.php?down_id=' . $file['DOWNLOAD_ID'] . '&stafffile=Y"> ' . $fileIcon . ' &nbsp; '. $show_filename . '</a>';
                 echo '<div></div>';
            }
            echo '</div>';
            echo '</td></tr></table>';
        }
    }
}
function do_cado_bulletins(){
    $file_info = DBGet(DBQuery('SELECT* FROM user_file_upload WHERE USER_ID=' . UserStudentID() . ' AND PROFILE_ID=3 AND SCHOOL_ID=' . UserSchool() . ' AND file_info=\'stufile\''));
    echo '<tbody>';
    $found = false;
    $gridClass = "";
    $file_no = 1;
    echo '</div>';
    echo '
    <style>
    .files {
        color: ligth-blue;
        font-size: 14px;
        font-weight: 400;
      }
      .listing {
        background: white;
        padding: 10px;
        width: 1000%;
        border-spacing: 15px;
      }
      th, td {
        border: 1px solid grey;
        padding: 5px;
        border-spacing: 15px;
      }
    </style>
    ';
    echo '<div class="panel">';
    DrawHeader('<span class="text-black">Bulletins: ');
    foreach ($file_info as $key => $file_val) {
        if ($gridClass == "even") {
            $gridClass = "odd";
        } else {
            $gridClass = "even";
        }
        if ($file_val['NAME']) {
            if ($file_val['NAME'] == '.' || $file_val['NAME'] == '..')
                continue;
            else {
                $found = true;
                $sub = $file_val['NAME'];
                if (strstr($sub, '-_')) {
                    $file_display = substr($sub, 0, strrpos($sub, '-_'));
                } else {
                    $file_display = $sub;
                }
                $file = explode('.', $file_display);
                echo '<table class="myfiles"><tr><td>';
                echo '<a class="files" href="DownloadWindow.php?down_id=' . $file_val['DOWNLOAD_ID'] . '&studentfile=Y"><i class="fa fa-file-pdf-o"></i> &nbsp; '. str_replace("opensis_space_here", " ", str_replace(UserStudentID()."-","",$file_display)) . '</a>';
                echo '</td></tr></table>';
            }
        }
    }
    echo '</div>';
    if(count($file_info)==0){
        echo '<table class="listing"><tr><td>';
        echo '<a class="files">' . _no_report_card_found . '</a>';
        echo '</td></tr></table>';
    }
}
?>
