<?php
#**************************************************************************
# openSIS is a free student information system for public and non-public
# schools from Open Solutions for Education, Inc. web: www.os4ed.com
#
# openSIS is web-based, open source, and comes packed with features that
# include student demographic info, scheduling, grade book, attendance,
# report cards, eligibility, transcripts, parent portal,
# student portal and more.
#
# Visit the openSIS web site at http://www.opensis.com to learn more.
# If you have question regarding this system or the license, please send
# an email to info@os4ed.com.
#
# This program is released under the terms of the GNU General Public License as
# published by the Free Software Foundation, version 2 of the License.
# See license.txt.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <http://www.gnu.org/licenses/>.
#
#***************************************************************************************
include('../../RedirectModulesInc.php');

$coursesWithType = 0;
$courses = DBGet(DBQuery('SELECT COURSE_PERIOD_ID,COURSE_ID,CP_TITLE as SHORT from course_details where syear=' . UserSyear() . ' ORDER by SHORT'));

echo '<h2>Liste des types de devoir par cours</h2>';
echo '<p><strong>Nombre de cours total:</strong> ' . count($courses) . '</p>';

// Start HTML table
echo '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse: collapse; width: 100%; margin: 20px 0;">';
echo '<thead style="background-color: #f2f2f2;">';
echo '<tr>';
echo '<th style="text-align: left; padding: 10px;">ID</th>';
echo '<th style="text-align: left; padding: 10px;">Cours</th>';
echo '<th style="text-align: left; padding: 10px;">Types de devoir</th>';
echo '<th style="text-align: center; padding: 10px;">Pondération %</th>';
echo '<th style="text-align: center; padding: 10px;">Types présent ?</th>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';

foreach($courses as $individual) {
    $types = DBGet(DBQuery('SELECT TITLE,COURSE_ID,COURSE_PERIOD_ID,FINAL_GRADE_PERCENT from gradebook_assignment_types where COURSE_PERIOD_ID= ' . $individual['COURSE_PERIOD_ID'] . ' AND TITLE != \'1ère communication\' '));
    
    $hasTypes = count($types) > 0;
    if($hasTypes) $coursesWithType++;
    
    echo '<tr style="' . ($hasTypes ? '' : 'background-color: #fff2f2;') . '">';
    echo '<td style="padding: 8px;">' . htmlspecialchars($individual['COURSE_PERIOD_ID']) . '</td>';
    echo '<td style="padding: 8px; font-weight: bold;">' . htmlspecialchars($individual['SHORT']) . '</td>';
    
    // Assignment types column
    echo '<td style="padding: 8px;">';
    $total=0;
    if($hasTypes) {
        $typeList = array();
        foreach($types as $type) {
            $typeList[] = htmlspecialchars($type['TITLE']) . 
                         ($type['FINAL_GRADE_PERCENT'] ? ' (' . $type['FINAL_GRADE_PERCENT'] . '%)' : '');
            $total+=$type['FINAL_GRADE_PERCENT'] ;
        }
        echo implode('<br>', $typeList);
    } else {
        echo '<em style="color: #888;">Pas de types de devoir</em>';
    }
    echo '</td>';
    
    // Type count column
    echo '<td style="padding: 8px; text-align: center;">' . number_format($total * 100) . '%</td>';
    
    // Has types column
    echo '<td style="padding: 8px; text-align: center;">';
    if($hasTypes) {
        echo '<span style="color: green; font-weight: bold;">✓ Oui</span>';
    } else {
        echo '<span style="color: red; font-weight: bold;">✗ Non</span>';
    }
    echo '</td>';
    
    echo '</tr>';
}

echo '</tbody>';
echo '</table>';

// Summary information
echo '<div style="margin: 20px 0; padding: 15px; background-color: #f9f9f9; border-left: 4px solid #007cba;">';
echo '<h3>Sommaire</h3>';
echo '<p><strong>Cours avec types de devoirs:</strong> ' . $coursesWithType . ' sur ' . count($courses) . '</p>';
echo '<p><strong>Cours sans types de devoirs:</strong> ' . (count($courses) - $coursesWithType) . '</p>';

if(count($courses) > 0) {
    $percentageWithTypes = round(($coursesWithType / count($courses)) * 100, 1);
    echo '<p><strong>Pourcentage de cours configurés:</strong> ' . $percentageWithTypes . '%</p>';
}

echo '</div>';

// Add some basic styling
echo '<style>
table {
    font-family: Arial, sans-serif;
    font-size: 14px;
}
th {
    background-color: #007cba !important;
    color: white;
    font-weight: bold;
}
tr:nth-child(even) {
    background-color: #f9f9f9;
}
tr:hover {
    background-color: #e6f3ff;
}
</style>';
?>