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
//include_once("fckeditor/fckeditor.php");
include('lang/language.php');
require_once 'libraries/htmlpurifier/library/HTMLPurifier.auto.php';
DrawBC(""._messaging." > " . ProgramTitle());
//PopTable('header', 'Compose Message');
global $content;

if(isset($_SESSION['BODY_EMPTY']) && $_SESSION['BODY_EMPTY']!='')
{
    // echo '<div class="alert bg-danger alert-styled-left">Message body cannot be empty</div>';
    echo '<div class="alert alert-danger alert-bordered"><button type="button" class="close" data-dismiss="alert"><span>×</span><span class="sr-only">'._close.'</span></button>'._messageBodyCannotBeEmpty.'</div>';
    unset($_SESSION['BODY_EMPTY']);
}
// echo '<div class="panel">';
// echo '<div class="tabbable">';
//print_r($_SERVER);
echo '<ul class="nav nav-tabs nav-tabs-bottom no-margin-bottom">';
echo '<li class="active" id="tab[]"><a href="javascript:void(0);">'._compose.'&nbsp;'._message.'</a></li>';
echo '</ul>';
$userName = User('USERNAME');
$_SESSION['course_period_id'] = '';
echo "<FORM name=ComposeMail id=Compose action=Modules.php?modname=messaging/Inbox.php&count=$c  METHOD=POST enctype=multipart/form-data >";
if ($_REQUEST['modfunc'] != 'choose_course') {
    if (User('PROFILE') == 'admin' || User('PROFILE') == 'teacher') {
        echo "<DIV id=course_div>";
    }
    if (isset($_REQUEST['mod']) && $_REQUEST['mod'] == 'draft') {
        $mail_id = $_REQUEST['mail_id'];
        $query = "select * from msg_inbox where mail_id='$mail_id'";
        $result = DBGet(DBQuery($query));

        foreach ($result as $v) {
            $to_user = $v['TO_USER'];
            $to_cc = $v['TO_CC'];
            $to_bcc = $v['TO_BCC'];
            $mail_subject = $v['MAIL_SUBJECT'];
            $mail_id = $v['MAIL_ID'];
            $mail_body = base64_decode($v['msgbody']);
        }
    }
    if (!isset($_REQUEST['modto']) && !isset($_REQUEST['mod'])) {
        $to_user = '';
        $to_cc = '';
        $to_bcc = '';
        $mail_subject = '';
        $mail_id = '';
        $mail_body = '';
    }
    echo '<div class="panel-body">';

    echo '<div class="row">';
    //echo '<div class="col-md-8">';

    echo '<div class="form-group">';
    echo '<div class="input-group">';
    if (isset($_REQUEST['modto']) && $_REQUEST['m'] == 'reply') {
        $to_user = $_REQUEST['modto'];
        $name = html_entity_decode($_REQUEST['fullname']);
        $mail_subject = base64_decode($_REQUEST['sub']);
        $content = base64_decode(base64_decode($_REQUEST['msgbody']));
        $temp=$mail_subject ;
        $mail_subject = 'RE: ';
        $mail_subject .=$temp;
        $hidden='hidden';
        // // echo '<script>window.location="Modules.php?modname=messaging/Compose.php"</script>';
        // return false;
        // header("Location: Modules.php?modname=messaging/Compose.php&modto=admin"); // Redirect to your desired page
        // exit();
    }
    echo TextInput_mail_hidden($to_user, 'txtToUser', '', 'onkeyup="nameslist(this.value,1)" autocomplete = "off" class=form-control');
    echo '</div>'; //.input-group
    echo '<ul class="dropdown-menu" id="ajax_response"></ul>';
    echo '</div>'; //.form-group

    //echo '</div>'; //.col-md-8
    echo '<div class="col-md-4 form-inline">';
    echo '<div class="input-group">';

    if (User('PROFILE') == 'teacher' ){
        $to_bcc = 'admin';
        $groupList = DBGet(DBQuery('SELECT concat(first_name, " ", last_name ) as GROUP_NAME , student_id , (select concat(first_name, " ", last_name ) as CONTACT_NAME from people p where staff_id IN (select person_id from students_join_people where emergency_type = "Primary" and student_id = st.student_id)) as CONTACT , (select STAFF_ID from people p where staff_id IN (select person_id from students_join_people where emergency_type = "Primary" and student_id = st.student_id )) as STAFF_ID , (select username from login_authentication where user_id = staff_id and profile_id=4) as email from students st where is_disable is null and STUDENT_ID IN (SELECT STUDENT_ID FROM schedule WHERE dropped = "N" and course_period_id = '. UserCoursePeriod() .')  order by last_name'));
        $index=count($groupList)+1;
        $groupList[$index]['EMAIL']='admin';
        $groupList[$index]['GROUP_NAME']='admin';
        $groupList[$index]['CONTACT']='CADO';

        echo "<SELECT name='groups' class=\"form-control ' . $hidden . ' \" onChange=\"list_of_groups(this.options[this.selectedIndex].value);\"><OPTION value=''>"._select_student ."</OPTION>";
        foreach ($groupList as $groupArr) {
            $option = $groupArr['EMAIL'];
            $value = $groupArr['GROUP_NAME'];
            $value .= '  (';
            $value .= $groupArr['CONTACT'];
            $value .= ')';
            if ($_REQUEST['sel_group'] == $value)
                echo "<OPTION selected='selected' value=\"$value\">$value</OPTION>";
            else
                echo "<OPTION value=\"$option\">$value</OPTION>";
        }
        echo '</SELECT>';
        echo '<span class="input-group-btn">';
    }
    if (User('PROFILE') == 'admin' ){
        $member_select = DBGet(DBQuery("SELECT (SELECT  username FROM login_authentication WHERE user_id=STAFF_ID and profile_id=2) AS EMAIL FROM staff where profile = 'teacher' and is_disable ='N' and staff_id order by last_name"));
        DBQuery('delete from mail_groupmembers where group_id="2"');
        foreach ($member_select as $member)
                DBQuery('INSERT INTO mail_groupmembers(GROUP_ID,USER_NAME,profile,SCHOOL_ID) VALUES(2,\'' . $member['EMAIL'] . '\',2,\'' . UserSchool(). '\')');
        $member_select = DBGet(DBQuery("SELECT  username as EMAIL FROM login_authentication WHERE user_id IN (select staff_id from people where profile='parent' and profile_id=4 and is_disable is null and staff_id IN (select person_id from students_join_people ) ) and profile_id=4 "));
        DBQuery('delete from mail_groupmembers where group_id="1"');
        foreach ($member_select as $member)
                DBQuery('INSERT INTO mail_groupmembers(GROUP_ID,USER_NAME,profile,SCHOOL_ID) VALUES(1,\'' . $member['EMAIL'] . '\',4,\'' . UserSchool(). '\')');
        $groupList = DBGet(DBQuery("SELECT GROUP_ID,GROUP_NAME FROM mail_group where user_name='" . $userName . "' AND SCHOOL_ID= '".UserSchool()."'"));
        if($_REQUEST['m'] != 'reply'){
            echo "<SELECT name='groups' class=\"form-control\" onChange=\"list_of_groups(this.options[this.selectedIndex].value);\"><OPTION value=''>"._selectGroup."</OPTION>";
            foreach ($groupList as $groupArr) {
                $option = $groupArr['GROUP_NAME'];
                $value = $groupArr['GROUP_ID'];

                if ($_REQUEST['sel_group'] == $value)
                    echo "<OPTION selected='selected' value=\"$value\">$option</OPTION>";
                else
                    echo "<OPTION value=\"$option\">$option</OPTION>";
            }
        }
        echo '</SELECT>';
        echo '<span class="input-group-btn">';
    }
    if (User('PROFILE') == 'parent'){
        $to_bcc = 'admin';
        $groupList = DBGet(DBQuery('SELECT concat(first_name, " ", last_name ) as GROUP_NAME ,STAFF_ID, (SELECT  username FROM login_authentication WHERE user_id=STAFF_ID and profile_id=2) AS EMAIL FROM staff where profile = "teacher" and is_disable ="N" and staff_id in (SELECT TEACHER_ID FROM course_periods WHERE course_period_id IN (SELECT course_period_id FROM schedule WHERE SYEAR= ' . UserSyear() . ' AND STUDENT_ID=' . UserStudentID(). '))order by last_name'));
        $index=count($groupList)+1;
        $groupList[$index]['EMAIL']='admin';
        $groupList[$index]['GROUP_NAME']='admin CADO';
        echo "<SELECT name='groups' class=\"form-control ' . $hidden . ' \" onChange=\"list_of_groups(this.options[this.selectedIndex].value);\"><OPTION value=''>"._select_teacher ."</OPTION>";
        foreach ($groupList as $groupArr) {
            $option = $groupArr['EMAIL'];
            $value = $groupArr['GROUP_NAME'];
            if ($_REQUEST['sel_group'] == $value)
                echo "<OPTION selected='selected' value=\"$value\">$value</OPTION>";
            else
                echo "<OPTION value=\"$option\">$value</OPTION>";
            }
        echo '</SELECT>';
        echo '<span class="input-group-btn">';
    }
    echo '</SELECT>';
    echo '<span class="input-group-btn">';
    // echo '<a href="#" class="btn btn-default" onclick="show_cc()">'._cc.'</a> &nbsp; ';
    // echo '<a href="#" class="btn btn-default" onclick="show_bcc()">'._bcc.'</a>';
    echo '</span>';
    echo '</div>'; //.input-group
    echo '</div>'; //.col-md-4
    echo '</div>'; //.row

    echo '<div id="message_my_class_div"></div>';

    echo '<div class="row">';
    echo '<div class="col-md-6" id="cc" style="display:none">';

    echo '<div class="form-group">';
    echo '<div class="input-group">';
    echo '<span class="input-group-addon">'._cc.'</span>';
    echo TextInput_mail($to_cc, 'txtToCCUser', '', 'onkeyup="nameslist(this.value,2)" class=mail_input');
    echo '</div>'; //.input-group
    echo '</div>'; //.form-group
    echo '<div id=ajax_response_cc></div>';

    echo '</div>'; //.col-md-6
    echo '<div class="col-md-6" id="bcc" style="display:none">';

    echo '<div class="form-group">';
    echo '<div class="input-group">';
    echo '<span class="input-group-addon">'._bcc.'</span>';
    echo TextInput_mail($to_bcc, 'txtToBCCUser', '', 'onkeyup="nameslist(this.value,3)" class=mail_input');
    echo '</div>'; //.input-group
    echo '</div>'; //.form-group
    echo '<div id=ajax_response_bcc></div>';

    echo '</div>'; //.col-md-6
    echo '</div>'; //.row


    echo '<div class="row">';
    echo '<div class="col-md-12">';

    echo '<div class="form-group">';
    if($_REQUEST['m'] == 'reply')
        echo TextInput_mail($mail_subject, 'txtSubj', '', 'readonly placeholder='._objet.'');
    else
        echo TextInput_mail($mail_subject, 'txtSubj', '', 'placeholder='._objet.'');
    echo '</div>'; //.form-group
    echo '<div id=ajax_response_cc></div>';

    echo '</div>'; //.col-md-12
    echo '</div>'; //.row


    /* $oFCKeditor = new FCKeditor("txtBody") ;
      $oFCKeditor->BasePath = "modules/messaging/fckeditor/" ;
      $oFCKeditor->Value = '';
      $oFCKeditor->Height = "350px";
      $oFCKeditor->Width = "600px";
      $oFCKeditor->ToolbarSet   = 'Mytoolbar ';
      $oFCKeditor->Create() ; */
    $temp="\n\r";
    if($_REQUEST['m'] == 'reply'){
        $mail_body = base64_decode($content);
        // $data_array = explode("\n", $mail_body);
        // foreach ($data_array as $data_str) {
        //     $temp .= '>' . $data_str . "\n";
        // }
    }
    echo '<textarea class="hidden" name="txtBody" id="txtBody" rows="22" cols="150">' . $mail_body . '</textarea>';
    // echo '<textarea name="txtBody" id="txtBody" rows="22" cols="150">' . $mail_body . '</textarea>';

 
     echo '<div>';
    wysisyg_editor();
     echo '</div>';
 


    //echo '<script type="text/javascript">$(function(){ CKEDITOR.replace(\'txtBody\', { height: \'400px\', extraPlugins: \'forms\'}); });</script>';

    echo '<h5 class="hidden">'._attachFile.'</h5>';
    echo '<div id="append_tab">';
    echo '<div id="tr1" class="form-group clearfix hidden"><div class="col-md-4"><input type="file" name="f[]" id="up1" onchange="attachfile(1);" multiple/></div><div id="del1" class="col-md-8"><input type="button" value="'._clear.'" class="btn btn-danger btn-xs" onclick="clearfile(1)" /></div></div>';
    echo '</div>'; //#append_tab
    echo '<input type="button" style="display:none;" class="btn btn-default"  id="attach1" onclick="appendFile();" value="'._attachAnotherFile.'" />';
    
    echo '</div>'; //.panel-body
    
    echo '<div class="panel-footer"><div class="heading-elements">';
    echo '<input type=hidden id=counter value=1 />';
    echo '<button class="hidden" type="submit" name=button id=button class="btn btn-primary heading-btn pull-right" VALUE="'._send.'" onClick="validate_email(this);">'._send.' <i class="icon-paperplane"></i></button>';
    echo '</div></div>';
    
}
if ($_REQUEST['modfunc'] == 'choose_course') {


    if (!$_REQUEST['course_period_id']) {
        $message_my_class = 'yes';
        include 'modules/scheduling/CoursesforWindow.php';
    } else {
        $_SESSION['MassSchedule.php']['subject_id'] = $_REQUEST['subject_id'];
        $_SESSION['MassSchedule.php']['course_id'] = $_REQUEST['course_id'];
        $_SESSION['MassSchedule.php']['course_period_id'] = $_REQUEST['course_period_id'];

        $course_title = DBGet(DBQuery('SELECT TITLE FROM courses WHERE COURSE_ID=\'' . $_SESSION['MassSchedule.php']['course_id'] . '\''));
        $course_title = $course_title[1]['TITLE'];
        $period_title_RET = DBGet(DBQuery('SELECT COURSE_PERIOD_ID,TITLE,MARKING_PERIOD_ID,GENDER_RESTRICTION FROM course_periods WHERE COURSE_PERIOD_ID=\'' . $_SESSION['MassSchedule.php']['course_period_id'] . '\''));
        $period_title = $period_title_RET[1]['TITLE'];
        $mperiod = $period_title_RET[1]['MARKING_PERIOD_ID'];
        $course_period_id = $period_title_RET[1]['COURSE_PERIOD_ID'];
        $_SESSION['course_period_id'] = $_REQUEST['course_period_id'];
        $grp = DBGet(DBQuery("select * from mail_group"));
        $title = trim($course_title) . ' ' . trim($period_title);
        echo "<script language=javascript>opener.document.getElementById(\"txtToUser\").value=\"$title\";opener.document.getElementById(\"ajax_response\").innerHTML='';opener.document.getElementById(\"txtToUser\").readOnly='true';opener.document.getElementById(\"message_my_class_div\").innerHTML = \"<input type=hidden name=cp_id id=cp_id value=$course_period_id><INPUT type=checkbox id=list_gpa_student name=list_gpa_student value=Y CHECKED>"._onlyStudents."<INPUT type=checkbox name=list_gpa_parent id=list_gpa_parent value=Y CHECKED>"._onlyParents."" . (User('PROFILE') != 'teacher' ? '<INPUT type=checkbox name=list_gpa_teacher id=list_gpa_teacher value=Y CHECKED>'._onlyTeachers.'' : '') . "&nbsp;&nbsp;<a href='Modules.php?modname=messaging/Compose.php'><font color='red'>"._removeCourse."</font>\";window.close();</script>";
    }
}
echo "</form>";
echo "</div>"; //.panel

function wysisyg_editor(){
    echo '<head>';
    style();
    body();
    scripts();
    echo '</head>';
}

function style(){
    echo '    <style>
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
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
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

    </style>';
}
function scripts(){
    echo '<script> 
        const editor = document.getElementById("editor");
        const autoSaveStatus = document.getElementById("autoSaveStatus");
        const autoSaveText = document.getElementById("autoSaveText");
        let savedSelection = null;
        let savedRange = null;

        // Auto-save configuration
        let autoSaveTimeout;
        let autoSaveInterval;
        // let lastSavedContent = editor.innerHTML;
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
                    const editor = document.getElementById("editor");
                    editor.focus();
                    // Move cursor to end as fallback
                    const range = document.createRange();
                    range.selectNodeContents(editor);
                    range.collapse(false);
                    selection.addRange(range);
                }
            }
        }

        // Exécuter les commandes d"édition
        function execCmd(command, value = null) {
            const editor = document.getElementById("editor");
            // Ensure editor has focus but don"t disturb selection
            if (!editor.contains(document.activeElement)) {
                editor.focus();
            }
            
            document.execCommand(command, false, value);
            updateWordCount();
            updateToolbarState();
        }

        // Auto-save functions
        // function updateAutoSaveStatus(status, message) {
        //     autoSaveStatus.className = `auto-save-status ${status}`;
        //     autoSaveText.textContent = message;
        // }

        // function triggerAutoSave() {
        //     hasUnsavedChanges = true;
            
        //     // Clear existing timeout
        //     if (autoSaveTimeout) {
        //         clearTimeout(autoSaveTimeout);
        //     }
            
        //     // Set new timeout
        //     autoSaveTimeout = setTimeout(() => {
        //         if (hasUnsavedChanges) {
        //             saveContent();
        //         }
        //     }, AUTO_SAVE_DELAY);
        // }

        // function startPeriodicAutoSave() {
        //     autoSaveInterval = setInterval(() => {
        //         if (hasUnsavedChanges) {
        //             saveContent();
        //         }
        //     }, AUTO_SAVE_INTERVAL);
        // }

        // function stopPeriodicAutoSave() {
        //     if (autoSaveInterval) {
        //         clearInterval(autoSaveInterval);
        //     }
        // }
        
        // Compter les mots
        function updateWordCount() {
            const text = editor.innerText || editor.textContent || "";
            const words = text.trim().split(/\s+/).filter(word => word.length > 0);
            const char = text.length;
            document.getElementById("wordCount").textContent = `Nombre de mots : ${words.length}`;
            document.getElementById("charCount").textContent = `Nombre de char : ${text.length }`;
            if(text.length > 40000){
                dont_save = true;
                alert("Texte trop long.... La sauvegarde n\'aura pas lieu. Enlevez du texte."); 
            }
            else
                dont_save = false;
        }
        
        // Insérer un lien
        function insertLink() {
            document.getElementById("linkModal").style.display = "block";
        }
        
        function insertLinkAction() {
            const text = document.getElementById("linkText").value;
            const url = document.getElementById("linkUrl").value;
            
            if (url) {
                const linkHtml = text ? `<a href="${url}" target="_blank">${text}</a>` : `<a href="${url}" target="_blank">${url}</a>`;
                execCmd("insertHTML", linkHtml);
            }
            
            closeModal("linkModal");
            document.getElementById("linkText").value = "";
            document.getElementById("linkUrl").value = "";
        }
        
        // Insérer un tableau
        function insertTable() {
            document.getElementById("tableModal").style.display = "block";
        }

        
        function insertTableAction() {
            const rows = parseInt(document.getElementById("tableRows").value) || 3;
            const cols = parseInt(document.getElementById("tableCols").value) || 3;
            let tableHtml = `<table border="1" style="border-collapse: collapse; width: 100%; margin: 10px 0;">`;
            for (let i = 0; i < rows; i++) {
                tableHtml += "<tr>";
                for (let j = 0; j < cols; j++) {
                    tableHtml += `<td style="padding: 8px; border: 1px solid #ddd;">&nbsp</td>`;
                }
                tableHtml += `</tr>`;
            }
            tableHtml += `</table>`;
            execCmd("insertHTML", tableHtml);            
            closeModal("tableModal");
        }
        // Table row coloring functions
        function colorTableRows() {
            populateTableSelector();
            document.getElementById("tableColorModal").style.display = "block";
        }

        function populateTableSelector() {
            const tables = editor.querySelectorAll("table");
            const selector = document.getElementById("tableSelector");
            
            // Clear existing options
            selector.innerHTML = `<option value="">Choisir un tableau...</option>`;
            
            tables.forEach((table, index) => {
                const option = document.createElement("option");
                option.value = index;
                option.textContent = `Tableau ${index + 1} (${table.rows.length} lignes)`;
                selector.appendChild(option);
            });
        }
       function applyTableColoring() {
            const tableIndex = document.getElementById("tableSelector").value;
            const selectedOption = document.querySelector(`input[name="colorOption"]:checked`);
            
            if (!tableIndex) {
                alert("Veuillez sélectionner un tableau.");
                return;
            }
            
            const tables = editor.querySelectorAll("table");
            const selectedTable = tables[parseInt(tableIndex)];
            
            if (!selectedTable) {
                alert("Tableau non trouvé.");
                return;
            }
            
            // Apply header styling first if enabled
            const headerEnabled = document.getElementById("headerEnabled").checked;
            if (headerEnabled) {
                applyHeaderStyling(selectedTable);
            }
            
            // Apply row coloring if selected
            if (selectedOption) {
                const colorOption = selectedOption.value;
                applyColoringToTable(selectedTable, colorOption, headerEnabled);
            }
            
            closeModal("tableColorModal");
            triggerAutoSave();
        }

        function applyHeaderStyling(table) {
            const headerColor = document.getElementById("headerColor").value;
            const headerTextColor = document.getElementById("headerTextColor").value;
            const firstRow = table.querySelector("tr");
            
            if (firstRow) {
                firstRow.style.backgroundColor = headerColor;
                firstRow.style.color = headerTextColor;
                firstRow.style.fontWeight = "bold";
                
                // Apply to all cells in the first row
                const cells = firstRow.querySelectorAll("td, th");
                cells.forEach(cell => {
                    cell.style.backgroundColor = headerColor;
                    cell.style.color = headerTextColor;
                    cell.style.fontWeight = "bold";
                });
            }
        }

        function applyColoringToTable(table, colorOption, skipFirstRow = false) {
            const rows = table.querySelectorAll("tr");
            const startIndex = skipFirstRow ? 1 : 0;
            
            for (let i = startIndex; i < rows.length; i++) {
                const row = rows[i];
                
                // Skip header row styling if it was already applied
                if (i === 0 && skipFirstRow) {
                    continue;
                }
                
                // Remove existing background color styles for non-header rows
                if (!(i === 0 && skipFirstRow)) {
                    row.style.backgroundColor = "";
                }
                
                const adjustedIndex = skipFirstRow ? i - 1 : i;
                
                switch (colorOption) {
                    case "zebra-light":
                        if (!(i === 0 && skipFirstRow)) {
                            row.style.backgroundColor = adjustedIndex % 2 === 0 ? "#f8f9fa" : "white";
                        }
                        break;
                    case "zebra-blue":
                        if (!(i === 0 && skipFirstRow)) {
                            row.style.backgroundColor = adjustedIndex % 2 === 0 ? "#e3f2fd" : "white";
                        }
                        break;
                    case "zebra-green":
                        if (!(i === 0 && skipFirstRow)) {
                            row.style.backgroundColor = adjustedIndex % 2 === 0 ? "#e8f5e8" : "white";
                        }
                        break;
                    case "zebra-yellow":
                        if (!(i === 0 && skipFirstRow)) {
                            row.style.backgroundColor = adjustedIndex % 2 === 0 ? "#fff8e1" : "white";
                        }
                        break;
                    case "custom":
                        if (!(i === 0 && skipFirstRow)) {
                            const color1 = document.getElementById("customColor1").value;
                            const color2 = document.getElementById("customColor2").value;
                            row.style.backgroundColor = adjustedIndex % 2 === 0 ? color1 : color2;
                        }
                        break;
                    case "remove":
                        if (!(i === 0 && skipFirstRow)) {
                            row.style.backgroundColor = "";
                            row.removeAttribute("style");
                        }
                        break;
                }
            }
        }

        // Fermer les modales
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = "none";
        }
        
        // Fermer les modales en cliquant à l"extérieur
        window.onclick = function(event) {
            const modals = document.querySelectorAll(".modal");
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = "none";
                }
            });
        }
        
        // Sauvegarder le contenu
        // function saveContent() {
        //     const content = editor.innerHTML;
        //     if(dont_save) return;
        //     // updateAutoSaveStatus("saving", "Sauvegarde manuelle...");
            
        //     const formData = new FormData();
        //     formData.append("content", content);
            
        //     fetch(window.location.href, {
        //         method: "POST",
        //         body: formData
        //     })
        //     .then(response => {
        //         if (response.ok) {
        //             lastSavedContent = content;
        //             hasUnsavedChanges = false;
        //             const now = new Date().toLocaleTimeString("fr-FR");
        //             // updateAutoSaveStatus("saved", `Sauvegardé à ${now}`);
        //         } else {
        //             throw new Error("Network response was not ok");
        //         }
        //     })
        //     .catch(error => {
        //         console.error("Manual save error:", error);
        //         // updateAutoSaveStatus("error", "Erreur de sauvegarde manuelle");
        //     });
        // }

        // Sauvegarder le contenu
        function saveContent2() {
            const content = editor.innerHTML;
            const usr = document.getElementById("txtToUser").value;
            // Créer un formulaire pour envoyer les données
            const form = document.createElement("form");
            form.method = "POST";
            form.style.display = "none";
            const input = document.createElement("input");
            input.type = "hidden";
            input.name = "content";
            input.value = content;
            form.appendChild(input);
            document.body.appendChild(form);
            if (usr  === "")
                 alert("Choisissez un destinataire");
            else
                if (content === "" || content === "<br>")
                    alert("Le message est vide... ");
                else
                    post("Modules.php?modname=messaging/inbox.php",{sendit});
            document.body.removeChild(form);
        }
        function post(path, params, method="post") {
            // The rest of this code assumes you are not using a library.
            // It can be made less verbose if you use one.
            const form = document.createElement("form");
            form.method = method;
            form.action = path;

            for (const key in params) {
                if (params.hasOwnProperty(key)) {
                const hiddenField = document.createElement("input");
                hiddenField.type = "hidden";
                hiddenField.name = key;
                hiddenField.value = params[key];
                form.appendChild(hiddenField);
                }
            }
            document.body.appendChild(form);
            form.submit();
        }

        // Event listeners for auto-save
        editor.addEventListener("input", function() {
            updateWordCount();
            // triggerAutoSave();
        });
        
        // editor.addEventListener("paste", function() {
        //     setTimeout(() => {
        //         triggerAutoSave();
        //     }, 100);
        // });

        function updateToolbarState() {
            // Get all formatting buttons
            const buttons = {
                bold: document.querySelector(`button[onclick="execCmd(\`bold\`);return false"]`),
                italic: document.querySelector(`button[onclick="execCmd(\`italic\`);return false"]`),
                underline: document.querySelector(`button[onclick="execCmd(\`underline\`);return false"]`),  
                strikeThrough: document.querySelector(`button[onclick="execCmd(\`strikeThrough\`);return false"]`)
            };
            // Check each formatting state and update button appearance
            for (let command in buttons) {
                const button = buttons[command];
                if (button) {
                    if (document.queryCommandState(command)) {
                        button.classList.add("active");
                    } else {
                        button.classList.remove("active");
                    }
                }
            }
            
            // Update color inputs to reflect current selection colors
            updateColorInputs();
        }

        
        // Mettre à jour le compteur de mots en temps réel
        editor.addEventListener("input", updateWordCount);
        editor.addEventListener("mouseup", updateToolbarState);
        editor.addEventListener("keyup", updateToolbarState);
        editor.addEventListener("focus", updateToolbarState);
         // Initialiser le compteur de mots
        updateWordCount();
        // startPeriodicAutoSave(); // Start the periodic auto-save
   

        if(document.readyState === "complete") {
            //post("Modules.php?modname=messaging/Compose.php","editor");
            // execCmd("fontSize", "3");
        }

        // Gestion des raccourcis clavier
        editor.addEventListener("keydown", function(e) {
            if (e.ctrlKey || e.metaKey) {
                switch(e.key) {
                    case "b":
                        e.preventDefault();
                        execCmd("bold");
                        break;
                    case "i":
                        e.preventDefault();
                        execCmd("italic");
                        break;
                    case "u":
                        e.preventDefault();
                        execCmd("underline");
                        break;
                    // case "s":
                    //     e.preventDefault();
                    //     saveContent();
                    //     break;
                }
            }
        });
       
        // Animation au survol des boutons de la barre d"outils
        document.querySelectorAll(".toolbar button").forEach(button => {
            button.addEventListener("mouseenter", function() {
                this.style.transform = "translateY(-2px)";
            });
            
            button.addEventListener("mouseleave", function() {
                this.style.transform = "translateY(0)";
            });
        });

        // Context menu for table rows
        let contextMenuTable = null;
        let contextMenuRow = null;

        // Add context menu for right-clicking on table rows
        editor.addEventListener("contextmenu", function(e) {
            const row = e.target.closest("tr");
            if (row && row.closest("table")) {
                e.preventDefault();
                contextMenuTable = row.closest("table");
                contextMenuRow = row;
                showRowContextMenu(e.pageX, e.pageY, row);
            }
        });

        function showRowContextMenu(x, y, row) {
            // Remove existing context menu
            const existingMenu = document.getElementById("rowContextMenu");
            if (existingMenu) {
                existingMenu.remove();
            }

            // Create context menu
            const menu = document.createElement("div");
            menu.id = "rowContextMenu";
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

            const table = row.closest("table");
            const isFirstRow = row === table.querySelector("tr");
            
            const menuItems = [
                { text: "🎨 Colorer cette ligne", action: () => colorSingleRow(row) },
                { text: "🗑️ Supprimer couleur", action: () => removeSingleRowColor(row) }
            ];

            // Add header-specific options if this is the first row
            if (isFirstRow) {
                menuItems.unshift({ text: "📋 Définir comme en-tête", action: () => makeRowHeader(row) });
            }

            menuItems.push({ text: "📊 Colorer tout le tableau", action: () => colorWholeTable(contextMenuTable) });

            menuItems.forEach(item => {
                const menuItem = document.createElement("div");
                menuItem.textContent = item.text;
                menuItem.style.cssText = `
                    padding: 8px 12px;
                    cursor: pointer;
                    border-bottom: 1px solid #eee;
                `;
                menuItem.addEventListener("mouseenter", () => {
                    menuItem.style.backgroundColor = "#f0f0f0";
                });
                menuItem.addEventListener("mouseleave", () => {
                    menuItem.style.backgroundColor = "";
                });
                menuItem.addEventListener("click", () => {
                    item.action();
                    menu.remove();
                });
                menu.appendChild(menuItem);
            });

            document.body.appendChild(menu);

            // Remove menu when clicking elsewhere
            setTimeout(() => {
                document.addEventListener("click", function removeMenu() {
                    menu.remove();
                    document.removeEventListener("click", removeMenu);
                }, 0);
            }, 0);
        }
        function makeRowHeader(row) {
            const headerColor = prompt("Couleur de fond de l\"en-tête (hex, nom, rgb):", "#7476789c");
            const textColor = prompt("Couleur du texte de l\"en-tête (hex, nom, rgb):", "#ffffff");
            
            if (headerColor && textColor) {
                row.style.backgroundColor = headerColor;
                row.style.color = textColor;
                row.style.fontWeight = "bold";
                
                // Apply to all cells in the row
                const cells = row.querySelectorAll("td, th");
                cells.forEach(cell => {
                    cell.style.backgroundColor = headerColor;
                    cell.style.color = textColor;
                    cell.style.fontWeight = "bold";
                });
                
                // triggerAutoSave();
            }
        }

        function colorSingleRow(row) {
            const color = prompt("Entrez une couleur (nom, hex, rgb):", "#e3f2fd");
            if (color) {
                row.style.backgroundColor = color;
                // triggerAutoSave();
            }
        }

        function removeSingleRowColor(row) {
            row.style.backgroundColor = "";
            row.style.color = "";
            row.style.fontWeight = "";
            
            // Remove styling from all cells in the row
            const cells = row.querySelectorAll("td, th");
            cells.forEach(cell => {
                cell.style.backgroundColor = "";
                cell.style.color = "";
                cell.style.fontWeight = "";
            });
            
            // triggerAutoSave();
        }
       function colorWholeTable(table) {
            // Find the table index and open the color modal
            const tables = editor.querySelectorAll("table");
            const tableIndex = Array.from(tables).indexOf(table);
            
            populateTableSelector();
            document.getElementById("tableSelector").value = tableIndex;
            document.getElementById("tableColorModal").style.display = "block";
        }

        function updateColorInputs() {
            const foreColorInput = document.getElementById("textColorPreview").style.backgroundColor;
            const backColorInput = document.getElementById("bgColorPreview").style.backgroundColor;
            const fore2ColorInput =  document.getElementById("textColorPreview");
            const selection = window.getSelection();
            
            // Get current fore color
            const currentForeColor = document.queryCommandValue("foreColor");
            if (currentForeColor && foreColorInput) {
                // Convert RGB to hex if needed
                const hexForeColor = rgbToHex(currentForeColor);
                if (hexForeColor) {
                    foreColorInput.value = hexForeColor;
                    document.getElementById("textColorPreview").style.backgroundColor= currentForeColor;
                }
            }
            
            // Get current background color
            const currentBackColor = document.queryCommandValue("backColor");
            if (currentBackColor && backColorInput) {
                // Convert RGB to hex if needed
                const hexBackColor = rgbToHex(currentBackColor);
                if (hexBackColor) {
                    backColorInput.value = hexBackColor;
                    document.getElementById("bgColorPreview").style.backgroundColor= currentBackColor;
                }
            }
        }
        function rgbToHex(color) {
            if (!color) return null;
            
            // If already hex, return as is
            if (color.startsWith("#")) {
                return color;
            }
            
            // Handle rgb() and rgba() formats
            const rgbMatch = color.match(/rgb\((\d+),\s*(\d+),\s*(\d+)\)/);
            const rgbaMatch = color.match(/rgba\((\d+),\s*(\d+),\s*(\d+),\s*[\d.]+\)/);
            
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
                return hex.length === 1 ? "0" + hex : hex;
            };
            
            return `#${toHex(r)}${toHex(g)}${toHex(b)}`;
        }
         // Color palettes
        const commonColors = [
            "#000000", "#ffffff", "#ff0000", "#00ff00", "#0000ff", "#ffff00", "#ff00ff", "#00ffff",
            "#800000", "#008000", "#000080", "#808000", "#800080", "#008080", "#c0c0c0", "#808080"
        ];

        const extendedColors = [
            "#ff4444", "#44ff44", "#4444ff", "#ffaa44", "#ff44aa", "#44aaff", "#aa44ff", "#44ffaa",
            "#ff8888", "#88ff88", "#8888ff", "#ffcc88", "#ff88cc", "#88ccff", "#cc88ff", "#88ffcc",
            "#ffcccc", "#ccffcc", "#ccccff", "#ffeecc", "#ffccee", "#cceeff", "#eeccff", "#ccffee",
            "#333333", "#666666", "#999999", "#bbbbbb", "#dddddd", "#f0f0f0", "#f8f8f8", "#fcfcfc"
        ];
        // Recent colors storage
        let recentTextColors = JSON.parse(localStorage.getItem("recentTextColors") || "[]");
        let recentBgColors = JSON.parse(localStorage.getItem("recentBgColors") || "[]");

        // Initialize color pickers
        function initializeColorPickers() {
            // Initialize common colors for both pickers
            // alert("initialise");

            createColorGrid("commonColors", commonColors, "text");
            createColorGrid("commonBgColors", commonColors, "bg");
            
            // Initialize extended colors for both pickers
            createColorGrid("extendedColors", extendedColors, "text");
            createColorGrid("extendedBgColors", extendedColors, "bg");
            
            // Load recent colors
            loadRecentColors();
        }

        function createColorGrid(containerId, colors, type) {
            const container = document.getElementById(containerId);
            container.innerHTML = "";
            
            colors.forEach(color => {
                const swatch = document.createElement("div");
                swatch.className = "color-swatch";
                swatch.style.backgroundColor = color;
                swatch.title = color;
                swatch.onclick = () => applyColor(type, color);
                container.appendChild(swatch);
            });
        }
        function toggleColorDropdown(pickerId) {
            // Close other dropdowns first
            document.querySelectorAll(".color-dropdown").forEach(dropdown => {
                if (dropdown.id !== pickerId) {
                    dropdown.classList.remove("show");
                }
            });
            
            const dropdown = document.getElementById(pickerId);
            dropdown.classList.toggle("show");
        }

        function applyColor(type, color) {
            // Restore cursor position first
            restoreCursorPosition();
            
            // Ensure editor has focus
            const editor = document.getElementById("editor");
            editor.focus();
            
            if (type === "text") {
                document.execCommand("foreColor", false, color);
                document.getElementById("textColorPreview").style.backgroundColor = color;
                addToRecentColors("text", color);
            } else {
                document.execCommand("backColor", false, color);
                document.getElementById("bgColorPreview").style.backgroundColor = color;
                addToRecentColors("bg", color);
            }
            
            // Close dropdown
            document.querySelectorAll(".color-dropdown").forEach(dropdown => {
                dropdown.classList.remove("show");
            });
            
            // Trigger auto-save
            // triggerAutoSave();
        }

        function applyCustomColor(type, color) {
            if (type === "text") {
                document.getElementById("customTextHex").value = color;
            } else {
                document.getElementById("customBgHex").value = color;
            }
            applyColor(type, color);
        }
        function applyCustomHex(type, hex) {
            // Validate hex color
            if (!/^#[0-9A-F]{6}$/i.test(hex)) {
                alert("Format de couleur invalide. Utilisez le format #RRGGBB");
                return;
            }
            
            if (type === "text") {
                document.getElementById("customTextColor").value = hex;
            } else {
                document.getElementById("customBgColor").value = hex;
            }
            applyColor(type, hex);
        }
        function addToRecentColors(type, color) {
            const recentColors = type === "text" ? recentTextColors : recentBgColors;
            
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
            if (type === "text") {
                recentTextColors = recentColors;
                localStorage.setItem("recentTextColors", JSON.stringify(recentColors));
            } else {
                recentBgColors = recentColors;
                localStorage.setItem("recentBgColors", JSON.stringify(recentColors));
            }
            
            loadRecentColors();
        }

        function loadRecentColors() {
            loadRecentColorsForType("text", recentTextColors, "recentTextColors");
            loadRecentColorsForType("bg", recentBgColors, "recentBgColors");
        }

        function loadRecentColorsForType(type, colors, containerId) {
            const container = document.getElementById(containerId);
            // Keep the clear button
            const clearButton = container.querySelector(".clear-recent");
            container.innerHTML = "";
            container.appendChild(clearButton);
            
            colors.forEach(color => {
                const swatch = document.createElement("div");
                swatch.className = "recent-color";
                swatch.style.backgroundColor = color;
                swatch.title = color;
                swatch.onclick = () => applyColor(type, color);
                container.appendChild(swatch);
            });
        }

        function clearRecentColors(type) {
            if (type === "text") {
                recentTextColors = [];
                localStorage.removeItem("recentTextColors");
            } else {
                recentBgColors = [];
                localStorage.removeItem("recentBgColors");
            }
            loadRecentColors();
        }
        // Initialize when page loads
        // document.addEventListener("DOMContentLoaded", function() {
            // const editor = document.getElementById("editor");
            // console.log("Script loaded.");
            // initializeColorPickers();
            // Save cursor position on various editor interactions
            editor.addEventListener("mouseup", saveCursorPosition);
            editor.addEventListener("keyup", function(e) {
                    saveCursorPosition();
                    document.getElementById("txtBody").innerHTML = editor.innerHTML;
                    // roger set $mail_body
            });
        
        const content = editor.innerHTML;
        // Initialize other functions
        initializeColorPickers();
        // });
        
            // Close dropdowns when clicking outside
            document.addEventListener("click", function(event) {
                if (!event.target.closest(".color-picker-container")) {
                    const wasOpen = document.querySelector(".color-dropdown.show");
                    document.querySelectorAll(".color-dropdown").forEach(dropdown => {
                        dropdown.classList.remove("show");
                    });
                    // If a dropdown was open and we clicked outside, restore focus to editor
                    if (wasOpen && savedRange) {
                        setTimeout(() => {
                            restoreCursorPosition();
                        }, 50);
                    }
                }
        });
        initializeColorPickers();
        //alert("allo")
</script>';
}
function body(){
    echo '<body>
    <div class="editor-container">
        <div class="editor-header"> Message</div>
        
        <div class="toolbar">
            <div class="toolbar-group">
                <button onclick="execCmd(`undo`);return false" title="Annuler">↶</button>
                <button onclick="execCmd(`redo`);return false" title="Rétablir">↷</button>
            </div>
            
            <div class="toolbar-font">
                <select onchange="execCmd(`fontSize`, this.value)">
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
                <button onclick="execCmd(`bold`);return false" title="Gras"><strong>G</strong></button>
                <button onclick="execCmd(`italic`);return false" title="Italique"><em>I</em></button>
                <button onclick="execCmd(`underline`);return false" title="Souligné"><u>S</u></button>
                <button onclick="execCmd(`strikeThrough`);return false" title="Barré"><strike>B</strike></button>
            </div>
            
            <div class="toolbar-group hidden">texte
                <input type="color" onchange="execCmd(`foreColor`, this.value)" title="Couleur du texte" value="#000000" >
                arrière-plan
                <input type="color" onchange="execCmd(`backColor`, this.value)" title="Couleur de fond" value="#ffffff" >
            </div>
    <div class="toolbar-group">
    <div class="color-picker-container">
        <div class="color-trigger" onclick="toggleColorDropdown(`textColorPicker`)">
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
                    <div class="recent-color clear-recent" onclick="clearRecentColors("text")" title="Effacer l"historique">×</div>
                </div>
            </div>
            
            <div class="custom-color-section">
                <h4>⚙️ Couleur personnalisée</h4>
                <div class="custom-color-input">
                    <input type="color" id="customTextColor" onchange="applyCustomColor("text", this.value)">
                    <input type="text" id="customTextHex" placeholder="#000000" onchange="applyCustomHex("text", this.value)">
                </div>
            </div>
        </div>
    </div>
    
    <div class="color-picker-container">
        <div class="color-trigger" onclick="toggleColorDropdown(`bgColorPicker`)">
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
                    <div class="recent-color clear-recent" onclick="clearRecentColors("bg")" title="Effacer l"historique">×</div>
                </div>
            </div>
            
            <div class="custom-color-section">
                <h4>⚙️ Couleur personnalisée</h4>
                <div class="custom-color-input">
                    <input type="color" id="customBgColor" onchange="applyCustomColor("bg", this.value)">
                    <input type="text" id="customBgHex" placeholder="#ffffff" onchange="applyCustomHex("bg", this.value)">
                </div>
            </div>
        </div>
    </div>
    </div>              
            <div class="toolbar-group">
                <button onclick="execCmd(`justifyLeft`);return false" title="Aligner à gauche">≡</button>
                <button onclick="execCmd(`justifyCenter`);return false" title="Centrer">≣</button>
                <button onclick="execCmd(`justifyRight`);return false" title="Aligner à droite">≡</button>
                <button onclick="execCmd(`justifyFull`);return false" title="Justifier">≣</button>
            </div>
            
            <div class="toolbar-group">
                <button onclick="execCmd(`insertUnorderedList`);return false" title="Liste à puces">• Liste</button>
                <button onclick="execCmd(`insertOrderedList`);return false" title="Liste numérotée">1. Liste</button>
                <button onclick="execCmd(`indent`);return false" title="Indenter">→|</button>
                <button onclick="execCmd(`outdent`);return false" title="Désindenter">|←</button>
            </div>
            
            <div class="toolbar-group hidden">
                <button onclick="insertTable()" title="Insérer un tableau">📊 Tableau</button>
                <button onclick="colorTableRows()" title="Colorer les lignes du tableau">🎨 Colorer lignes</button>
            </div>
            
        </div>
        
        <div <div id="editor" class="editor-content" contenteditable="true">';
        global $content;
        echo $content;
        echo '</div> 
        <div class="editor-footer">
            <div style="display: flex; align-items: center;">
                <div class="word-count" id="wordCount">Nombre de mots: 0</div>
                <div>&nbsp&nbsp&nbsp&nbsp</div>
                <div class="word-count" id="charCount">Nombre de char: 0</div>
                <div class="auto-save-status" id="autoSaveStatus">
                    <span class="auto-save-indicator"></span>
                    <span class="hidden" id="autoSaveText">Auto-sauvegarde activée</span>
                </div>
            </div>
            <button class="save-btn" onclick="saveContent2();return false">Envoyer le message <i class="icon-paperplane"></i></button>
        </div>
    </div>

    <!-- Modal pour les liens -->
    <div id="linkModal" class="modal hidden">
        <div class="modal-content">
            <h3>Insérer un lien</h3>
            <input type="text" id="linkText" placeholder="Texte à afficher">
            <input type="url" id="linkUrl" placeholder="URL (https://exemple.com)">
            <div class="modal-buttons">
                <button class="btn-secondary" onclick="closeModal("linkModal")">Annuler</button>
                <button class="btn-primary" onclick="insertLinkAction()">Insérer</button>
            </div>
        </div>
    </div>

    <!-- Modal pour les tableaux -->
    <div id="tableModal" class="modal hidden">
        <div class="modal-content">
            <h3>Insérer un tableau</h3>
            <input type="number" id="tableRows" placeholder="Nombre de lignes" min="1" max="10" value="4">
            <input type="number" id="tableCols" placeholder="Nombre de colonnes" min="1" max="10" value="4">
            <div class="modal-buttons">
                <button class="btn-secondary" onclick="closeModal("tableModal")">Annuler</button>
                <button class="btn-primary" onclick="insertTableAction()">Insérer</button>
            </div>
        </div>
    </div>

    <!-- Modal pour colorer les lignes du tableau -->
    <div id="tableColorModal" class="modal hidden">
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
                            <label hidden for="headerEnabled">Activer l"en-tête</label>
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
                <button class="btn-secondary" onclick="closeModal("tableColorModal")">Annuler</button>
                <button class="btn-primary" onclick="applyTableColoring()">Appliquer</button>
            </div>
        </div>
    </div>
</body>';
}
?>
