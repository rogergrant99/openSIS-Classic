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
include 'RedirectRootInc.php';
include 'Warehouse.php';
include 'Data.php';

// CALLED WHEN A PRIMARY/SECONDARY CONTACT'S PORTAL USERNAME IS CHANGED - TELLS THE FRONT END WHETHER THE USERNAME
// IS FREE, ALREADY BELONGS TO AN ACCOUNT SAFE TO REUSE (NOT LINKED TO ANOTHER STUDENT), OR NEEDS CONFIRMATION
// (ALREADY LINKED TO A DIFFERENT STUDENT'S CONTACT) BEFORE THIS CONTACT IS MERGED INTO THAT ACCOUNT.

$username = sqlSecurityFilter($_REQUEST['username']);
$person_id = intval($_REQUEST['person_id']);

if (isset($_REQUEST['username']) && trim($_REQUEST['username']) != '') {
    $owner = DBGet(DBQuery('SELECT USER_ID FROM login_authentication WHERE USERNAME=\'' . $username . '\' AND USER_ID != ' . $person_id));

    if (count($owner) == 0) {
        echo 'available';
    } else {
        $owner_id = intval($owner[1]['USER_ID']);
        $linked_elsewhere = DBGet(DBQuery('SELECT COUNT(*) AS TOTAL FROM students_join_people WHERE PERSON_ID=' . $owner_id . ' AND STUDENT_ID != ' . intval(UserStudentID())));

        if ($linked_elsewhere[1]['TOTAL'] > 0) {
            echo 'confirm:' . $owner_id;
        } else {
            echo 'safe:' . $owner_id;
        }
    }
    exit;
}
