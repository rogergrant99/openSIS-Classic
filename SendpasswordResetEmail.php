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
error_reporting(0);
session_start();
include "functions/ParamLibFnc.php";
include "Data.php";
include "functions/DbGetFnc.php";
require_once "functions/PragRepFnc.php";
include "AuthCryp.php";
include 'functions/SqlSecurityFnc.php';
include_once("functions/PasswordHashFnc.php");
include("lang/language.php");
include("functions/langFnc.php");


//print_r($_REQUEST);

function db_start() {
    global $DatabaseServer, $DatabaseUsername, $DatabasePassword, $DatabaseName, $DatabasePort, $DatabaseType;

    switch ($DatabaseType) {
        case 'mysqli':
            $connection = new mysqli($DatabaseServer, $DatabaseUsername, $DatabasePassword, $DatabaseName);
            break;
    }

    // Error code for both.
    if ($connection === false) {
        switch ($DatabaseType) {
            case 'mysqli':
                $errormessage = mysqli_error($connection);
                break;
        }
        db_show_error("", ""._couldNotConnectToDatabase.": $DatabaseServer", $errstring);
    }
    return $connection;
}


##### Connection help #####
$connection = mysqli_connect($DatabaseServer, $DatabaseUsername, $DatabasePassword, $DatabaseName);

if (!$connection)
{
	die('Could Not Connect: ' . mysqli_error($connection) . mysqli_errno($connection));
}


// This function connects, and does the passed query, then returns a connection identifier.
// Not receiving the return == unusable search.
//		ie, $processable_results = DBQuery("select * from students");
function DBQuery($sql) {
    global $DatabaseType, $_openSIS, $connection;

    // $connection = db_start();

    switch ($DatabaseType) {
        case 'mysqli':
            $sql = str_replace('&amp;', "", $sql);
            $sql = str_replace('&quot', "", $sql);
            $sql = str_replace('&#039;', "", $sql);
            $sql = str_replace('&lt;', "", $sql);
            $sql = str_replace('&gt;', "", $sql);
            $sql = par_rep("/([,\(=])[\r\n\t ]*''/", '\\1NULL', $sql);
            if (preg_match_all("/'(\d\d-[A-Za-z]{3}-\d{2,4})'/", $sql, $matches)) {
                foreach ($matches[1] as $match) {
                    $dt = date('Y-m-d', strtotime($match));
                    $sql = par_rep("/'$match'/", "'$dt'", $sql);
                }
            }
            if (substr($sql, 0, 6) == "BEGIN;") {
                $array = explode(";", $sql);
                foreach ($array as $value) {
                    if ($value != "") {
                        $result = $connection->query($value);
                        if (!$result) {
                            $connection->query("ROLLBACK");
                            die(db_show_error($sql, _dbExecuteFailed, mysql_error()));
                        }
                    }
                }
            } else {
                $result = $connection->query($sql) or die(db_show_error($sql, _dbExecuteFailed, mysql_error()));
            }
            break;
    }
    return $result;
}

// return next row.
function db_fetch_row($result) {
    global $DatabaseType;

    switch ($DatabaseType) {
        case 'mysqli':
            $return = $result->fetch_assoc();
            if (is_array($return)) {
                foreach ($return as $key => $value) {
                    if (is_int($key))
                        unset($return[$key]);
                }
            }
            break;
    }
    return (is_array($return)) ? array_change_key_case($return, CASE_UPPER) : $return;
}

//page access validation code start
if ($_SESSION['PageAccess']!= 'stu_pass' && $_SESSION['PageAccess']!= 'stf_pass' && $_SESSION['PageAccess']!= 'par_pass')
    {
        $sql='SELECT username FROM login_authentication WHERE USERNAME=\'' . trim($_REQUEST['uname']) . '\'';
        $login_RET=DBGet(DBQuery($sql));
        if($login_RET && filter_var(trim($_REQUEST['uname']), FILTER_VALIDATE_EMAIL)){
            $pass=generatePassword();
            $message = _your_temporary_password_is;
            $message .= $pass;
            $message .= "\n";
            $message .= _if_you_did_not_ask_this_password_reset;
            //echo $message;
            DBQuery('UPDATE login_authentication SET password=\'' . GenerateNewHash($pass) . '\' WHERE USERNAME=\'' . trim($_REQUEST['uname']) . '\' ');
            mail(trim($_REQUEST['uname']), _new_password_request, $message);
            $msg = _new_password_sent . $_REQUEST['uname'];
            echo "<script type='text/javascript'>alert('$msg');</script>";
        }else{
            $msg = "Invalid";
             echo "<script type='text/javascript'>alert('$msg');</script>";
        }
        echo'<script>window.location.href="index.php"</script>';
    }

function generatePassword(){
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $pass = array(); //remember to declare $pass as an array
        $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
        for ($i = 0; $i < 8; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        return implode($pass); //turn the array into a string
    }
//end
