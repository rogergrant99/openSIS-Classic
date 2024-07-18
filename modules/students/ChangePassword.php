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
DrawBC(""._users." > " . ProgramTitle());
if ($_REQUEST['tab']== 'password' && $_REQUEST['button'] == _save) {
        $column_name = _password;
        $pass_current = paramlib_validation($column_name, $_REQUEST['old']);
        $pass_new = paramlib_validation($column_name, $_REQUEST['new']);
        $pass_verify = paramlib_validation($column_name, $_REQUEST['retype']);
        $pass_new_after = GenerateNewHash($pass_new);
        $password_RET = DBGet(DBQuery('SELECT la.PASSWORD FROM login_authentication la, students s WHERE s.STUDENT_ID=\'' . UserStudentId() . '\' AND la.USER_ID=s.STUDENT_ID AND la.PROFILE_ID=3'));
        $password_status = VerifyHash($pass_current,$password_RET[1]['PASSWORD']);

        // Validate current password
        if ( ! $password_status || !$pass_current )
            $error = ''._yourCurrentPasswordWasIncorrect.'.';
        // Validate new password match and strength
        if($pass_new != $pass_verify || !$pass_new || !$pass_verify ){
            $password_match=0;
            $error = ''._yourNewPasswordsDidNotMatch.'.';
        }else {
            $password_match=1;
            $uppercase = preg_match('@[A-Z]@', $pass_new);
            $lowercase = preg_match('@[a-z]@', $pass_new);
            $number    = preg_match('@[0-9]@', $pass_new);
            $specialChars = preg_match('@[^\w]@', $pass_new);
            if(!$uppercase || !$lowercase || !$number || !$specialChars || strlen($pass_new) < 8){
                $error = ''._yourNewPassworddNotComplexEnough.'.';
                $password_strength=0;
            }else $password_strength=1;
        }
        if($pass_new == $pass_verify && $password_status && $password_strength){
            if($password_strength){
                DBQuery('UPDATE login_authentication SET PASSWORD=\'' . $pass_new_after . '\' WHERE USER_ID=\'' . UserStudentId() . '\' AND PROFILE_ID=3');
                $note = ''._yourNewPasswordWasSaved.'.';
            }
        }
        unset($_REQUEST['values']);
        unset($_SESSION['_REQUEST_vars']['values']);
}

unset($_REQUEST['search_modfunc']);
unset($_SESSION['_REQUEST_vars']['search_modfunc']);

if (!$_REQUEST['modfunc']) {
    echo '<input type=hidden id=json_encoder value=' . json_encode(array("family", "all_school")) . ' />';
    $current_RET = DBGet(DBQuery('SELECT TITLE,VALUE,PROGRAM FROM program_user_config WHERE USER_ID=\'' . User('STAFF_ID') . '\' AND PROGRAM IN (\'' . 'Preferences' . '\',\'' . 'StudentFieldsSearchable' . '\',\'' . 'StudentFieldsSearch' . '\',\'' . 'StudentFieldsView' . '\') '), array(), array('PROGRAM', 'TITLE'));

    if (!$_REQUEST['tab'])
        $_REQUEST['tab'] = 'password';

    echo "<FORM class=\"form-horizontal\" name=perf_form id=perf_form action=Modules.php?modname=$_REQUEST[modname]&amp;tab=$_REQUEST[tab] method=POST onload='document.forms[0].submit;'>";
    $tabs = array(array('title' => ''._password.'', 'link' => "Modules.php?modname=$_REQUEST[modname]&amp;tab=password"));
    $_openSIS['selected_tab'] = "Modules.php?modname=$_REQUEST[modname]&amp;tab=" . $_REQUEST['tab'];
    PopTable('header', $tabs);
    if (clean_param($_REQUEST['tab'], PARAM_ALPHAMOD) == 'password') {
        echo '<div id=divErr style=display:none></div>';
        if ($error)
            echo ErrorMessage(array($error));
        if ($note)
            echo ErrorMessage(array($note), 'note');echo "<span id='error' name='error'></span>";
            echo "<FORM name=change_password class=form-horizontal id=change_password action=Modules.php?modname=" . strip_tags(trim($_REQUEST[modname]). "&action=update") . " method=POST>";
 //           PopTable('header',  _changePassword);

            echo '<div class="row">';
            echo '<div class="col-lg-8">';
            echo '<div class="form-group">';
            echo '<label class="control-label col-md-3 col-lg-3">'._oldPassword.'</label>';
            echo '<div class="col-md-5 col-lg-5">';
            echo '<INPUT type="password" class="form-control" name="old" AUTOCOMPLETE="off" placeholder="'._enterOldPassword.'" />';
            echo '</div>'; //.col-md-7
            echo '</div>'; //.form-group
            echo '</div>'; //.col-md-5
            echo '</div>'; //.row

            echo '<div class="row">';
            echo '<div class="col-lg-8">';
            echo '<div class="form-group">';
            echo '<label class="control-label col-md-3 col-lg-3">'._newPassword.'</label>';
            echo '<div class="col-md-5 col-lg-5">';
            echo '<INPUT type="password" id="new_pass" class="form-control" name="new" placeholder="'._enterNewPassword.'" AUTOCOMPLETE="off" onkeyup="passwordStrength(this.value);passwordMatch();">';
            echo '</div>'; //.col-md-5
            echo '<div class="col-md-4 col-lg-4 no-margin-top">';
            echo '<p class="help-block mt-10" id=passwordStrength></p>';
            echo '</div>'; //.col-md-3
            echo '</div>'; //.form-group
            echo '</div>'; //.col-md-5
            echo '</div>'; //.row

            echo '<div class="row">';
            echo '<div class="col-lg-8">';
            echo '<div class="form-group">';
            echo '<label class="control-label col-md-3 col-lg-3">'._retypePassword.'</label>';
            echo '<div class=" col-md-5 col-lg-5">';
            echo '<INPUT type="password" id="ver_pass" class="form-control" name="retype" placeholder="'._retypeNewPassword.'" AUTOCOMPLETE="off" onkeyup="passwordMatch();">';
            echo '</div>'; //.col-md-5
            echo '<div class="col-md-4 col-lg-4 no-margin-top">';
            echo '<p class="help-block mt-10" id="passwordMatch"></p>';
            echo '</div>'; //.col-md-3
            echo '</div>'; //.form-group
            echo '</div>'; //.col-md-5
            echo '</div>'; //.row

            echo "</FORM>";
    }

    if (clean_param($_REQUEST['tab'], PARAM_ALPHAMOD) == 'student_fields') {
        if (User('PROFILE_ID') != '')
            $custom_fields_RET = DBGet(DBQuery('SELECT CONCAT(\'' . '<b>' . '\',sfc.TITLE,\'' . '</b>' . '\') AS CATEGORY,cf.ID,cf.TITLE,\'' . '' . '\' AS SEARCH,\'' . '' . '\' AS DISPLAY ,\'' . '' . '\' AS SEARCHABLE FROM custom_fields cf,student_field_categories sfc WHERE sfc.ID=cf.CATEGORY_ID AND (SELECT DISTINCT CAN_USE FROM profile_exceptions WHERE PROFILE_ID=\'' . User('PROFILE_ID') . '\' AND MODNAME=CONCAT(\'' . 'students/Student.php&category_id=' . '\',cf.CATEGORY_ID))=\'' . 'Y' . '\' ORDER BY sfc.SORT_ORDER,sfc.TITLE,cf.SORT_ORDER,cf.TITLE'), array('SEARCH' => '_make', 'DISPLAY' => '_make', 'SEARCHABLE' => '_make'), array('CATEGORY'));
        else {
            $profile_id_mod = DBGet(DBQuery("SELECT PROFILE_ID FROM staff WHERE USER_ID='" . User('STAFF_ID')));
            $profile_id_mod = $profile_id_mod[1]['PROFILE_ID'];
            if ($profile_id_mod != '')
                $custom_fields_RET = DBGet(DBQuery('SELECT CONCAT(\'' . '<b>' . '\',sfc.TITLE,\'' . '</b>' . '\') AS CATEGORY,cf.ID,cf.TITLE,\'' . '' . '\' AS SEARCH,\'' . '' . '\' AS DISPLAY,\'' . '' . '\' AS SEARCHABLE FROM custom_fields cf,student_field_categories sfc WHERE sfc.ID=cf.CATEGORY_ID AND (SELECT DISTINCT CAN_USE FROM profile_exceptions WHERE PROFILE_ID=\'' . $profile_id_mod . '\' AND MODNAME=CONCAT(\'' . 'students/Student.php&category_id=' . '\',cf.CATEGORY_ID))=\'' . 'Y' . '\' ORDER BY sfc.SORT_ORDER,sfc.TITLE,cf.SORT_ORDER,cf.TITLE'), array('SEARCH' => '_make', 'DISPLAY' => '_make', 'SEARCHABLE' => '_make'), array('CATEGORY'));
        }

        $THIS_RET['ID'] = 'CONTACT_INFO';
        $custom_fields_RET[-1][1] = array('CATEGORY' => '<B>'._contactInformation.'</B>', 'ID' => 'CONTACT_INFO', 'TITLE' => '<IMG SRC=assets/down_phone_button.gif width=15> '._contactInformation.'', 'DISPLAY' => _make('', 'DISPLAY'));
        $THIS_RET['ID'] = 'HOME_PHONE';
        $custom_fields_RET[-1][] = array('CATEGORY' => '<B>'._contactInformation.'</B>', 'ID' => 'HOME_PHONE', 'TITLE' =>_homePhoneNumber, 'DISPLAY' => _make('', 'DISPLAY'));
        $THIS_RET['ID'] = 'GUARDIANS';
        $custom_fields_RET[-1][] = array('CATEGORY' => '<B>'._contactInformation.'</B>', 'ID' => 'GUARDIANS', 'TITLE' =>_guardians, 'DISPLAY' => _make('', 'DISPLAY'));
        $THIS_RET['ID'] = 'ALL_CONTACTS';
        $custom_fields_RET[-1][] = array('CATEGORY' => '<B>'._contactInformation.'</B>', 'ID' => 'ALL_CONTACTS', 'TITLE' =>_allContacts, 'DISPLAY' => _make('', 'DISPLAY'));

        $custom_fields_RET[0][1] = array('CATEGORY' => '<B>'._addresses.'</B>', 'ID' => 'ADDRESS', 'TITLE' =>_none, 'DISPLAY' => _makeAddress(''));
        $custom_fields_RET[0][] = array('CATEGORY' => '<B>'._addresses.'</B>', 'ID' => 'ADDRESS', 'TITLE' => '<IMG SRC=assets/house_button.gif> '._residence.'', 'DISPLAY' => _makeAddress('RESIDENCE'));
        $custom_fields_RET[0][] = array('CATEGORY' => '<B>'._addresses.'</B>', 'ID' => 'ADDRESS', 'TITLE' => '<IMG SRC=assets/mailbox_button.gif> '._mailing.'', 'DISPLAY' => _makeAddress('MAILING'));
        $custom_fields_RET[0][] = array('CATEGORY' => '<B>'._addresses.'</B>', 'ID' => 'ADDRESS', 'TITLE' => '<IMG SRC=assets/bus_button.gif> '._busPickup.'', 'DISPLAY' => _makeAddress('BUS_PICKUP'));
        $custom_fields_RET[0][] = array('CATEGORY' => '<B>'._addresses.'</B>', 'ID' => 'ADDRESS', 'TITLE' => '<IMG SRC=assets/bus_button.gif> '._busDropoff.'', 'DISPLAY' => _makeAddress('BUS_DROPOFF'));

    }


   // if ($_REQUEST['tab'] == 'display_options')
   //     echo "<div class=\"panel-footer p-b-0 text-right\"><INPUT type=submit class=\"btn btn-primary\" value="._save." onclick=\"self_disable(this);\" ></div></div>";
    //else
    //    echo "<div class=\"panel-footer p-b-0 text-right\"><INPUT id=\"listingStuBtn\" type=submit class=\"btn btn-primary\" value="._save." onclick='return pass_check(this);'></div>";
            PopTable('footer', '<INPUT TYPE="SUBMIT" name="button" id="button" class="btn btn-primary heading-btn pull-right" value="'._save.'" AUTOCOMPLETE="off" >');
 //PopTable('footer');
    echo '</FORM>';
}

function _make($value, $name) {
    global $THIS_RET, $categories_RET, $current_RET;
    //echo "<pre>";
//print_r($current_RET);
    switch ($name) {
        case 'SEARCH':

            if ($current_RET['StudentFieldsSearch'][$THIS_RET['ID']])
                $checked = ' checked';
            return '<label class="checkbox-inline checkbox-switch switch-success"><INPUT type=checkbox name=values[StudentFieldsSearch][' . $THIS_RET['ID'] . '] value=Y' . $checked . '><span></span></label>';
            break;

        case 'DISPLAY':

            if ($current_RET['StudentFieldsView'][$THIS_RET['ID']])
                $checked = ' checked';
            return '<div class="text-center"><INPUT type=checkbox class="styled" name=values[StudentFieldsView][' . $THIS_RET['ID'] . '] value=Y' . $checked . '></div>';
            break;
        case 'SEARCHABLE':

            if ($current_RET['StudentFieldsSearchable'][$THIS_RET['ID']])
                $checked = ' checked';
            return '<div class="text-center"><INPUT type=checkbox class="styled" name=values[StudentFieldsSearchable][' . $THIS_RET['ID'] . '] value=Y' . $checked . '></div>';
            break;
    }
}

function _makeAddress($value) {
    global $current_RET;

    if ($current_RET['StudentFieldsView']['ADDRESS'][1]['VALUE'] == $value || (!$current_RET['StudentFieldsView']['ADDRESS'][1]['VALUE'] && $value == ''))
        $checked = ' CHECKED';
    return '<div class="text-center"><INPUT type=radio class="styled" name=values[StudentFieldsView][ADDRESS] value="' . $value . '"' . $checked . '></div>';
}

?>
