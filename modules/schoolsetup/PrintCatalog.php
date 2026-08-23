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
include('lang/language.php');
include('../../RedirectModulesInc.php');


$colors= array();
$data = array();
$colors[0]='255, 255, 255'; // room 108
$colors[1]='255, 217, 204'; // room 109
$colors[2]='255, 229, 204'; // room 113
$colors[3]='204, 204, 255'; // room 114
$colors[4]='255, 204, 255'; // room 201
$colors[5]='242, 255, 204'; // room 202
$colors[6]='255, 242, 204'; // room 206
$colors[7]='134, 255, 229'; // room 207
$colors[8]='204, 185, 255'; // room 211
$colors[9]='204, 229, 255'; // room 213
$colors[10]='229, 255, 204';// room Gym
$colors[11]='229, 204, 100';// room Mat
$colors[12]='255, 255, 204';// room Pr 1-2
$colors[16]='229, 204, 100';// room Ext
if(! $_REQUEST['_openSIS_PDF']){
    echo "<FORM name=exp class=no-margin-bottom id=exp action=ForExport.php?modname=" . strip_tags(trim($_REQUEST['modname'])) . "&modfunc=print&id=" . $_REQUEST['id'] . "&_openSIS_PDF=true&report=true method=POST target=_blank>";
    echo '<div class="text-right"><INPUT type=submit class="btn btn-primary" value=\'' . _print . '\'></div>';
}

if(! $_REQUEST['_openSIS_PDF']){
    $grade_level_RET = DBGet(DBQuery('SELECT ID,TITLE FROM school_gradelevels WHERE  school_id=\'' . UserSchool() . '\''));
    if (count($grade_level_RET)) {
        echo '<div class="form-group"><div style="width: 300px;" class="col-md-12">' . CreateSelect($grade_level_RET, 'id', 'Tous', _selectGradeLevel . ' : ', 'Modules.php?modname=' . strip_tags(trim($_REQUEST['modname'])) . '&id=') . '</div></div><br><br>';
    }
}
do_style();
//echo $_REQUEST['id'];
if($_REQUEST['id']==''){
    if (prescolaire('1','2'))
        echo '<br>';
    primaire('0','6');
    echo '<br>';
    secondaire('0','5');
}

if($_REQUEST['id'] < 8 && $_REQUEST['id'] != 1)
    primaire(strip_tags(trim($_REQUEST['id']))-2,strip_tags(trim($_REQUEST['id']))-1);
if($_REQUEST['id'] > 7)
    secondaire(strip_tags(trim($_REQUEST['id']))-8,strip_tags(trim($_REQUEST['id']))-7);
if($_REQUEST['id'] == 1)
    prescolaire(1,2);

// if (clean_param($_REQUEST['modfunc'], PARAM_ALPHAMOD) == 'print' && $_REQUEST['report']) {
//     echo '<style type="text/css">*{font-family:arial; font-size:10px;}</style>';
// //    echo '<link rel="stylesheet" type="text/css" href="assets/css/export_print.css" />';
// }
    
function prescolaire($start,$end){
    global $colors;

    $get_subjects = DBGet(DBQuery("SELECT subject_id, title FROM `course_subjects` WHERE `school_id` = '".UserSchool()."' AND syear = '".UserSyear()."' ORDER BY `subject_id`"));
    $get_periods = DBGet(DBQuery("SELECT attendance,period_id, title, short_name, start_time, end_time , sort_order FROM `school_periods` WHERE short_name like 'M%' AND `syear` = '".UserSyear()."' AND `school_id` = '".UserSchool()."' ORDER BY `sort_order`"));
    $course_periods = DBGet(DBQuery("SELECT rooms.title as ROOM,rooms.sort_order as COLOUR, course_periods.TITLE,DAYS,START_TIME,END_TIME,course_periods.COURSE_PERIOD_ID,courses.grade_level from course_period_var cpv LEFT JOIN course_periods ON cpv.COURSE_PERIOD_ID = course_periods.COURSE_PERIOD_ID LEFT JOIN rooms ON rooms.room_id = cpv.room_id  LEFT JOIN courses ON courses.course_id = course_periods.course_id where course_periods.SYEAR= '".UserSyear()."'and grade_level = '1'"));
    if (empty($get_periods) || empty($course_periods))
        return false;
    $data = array();
    // echo '<pre>'; print_r($course_periods); echo '</pre>';

    foreach($course_periods as $key => $cp){
        $len=strlen($cp['DAYS']);
        $days=$cp['DAYS'];
        for($x = 1; $x <= $len ; $x++) {
            $cp['DAYS']=substr($days,$x-1,1);
            // echo $cp['DAYS'];
            $data[$cp['GRADE_LEVEL']][$cp['START_TIME']]['COLOUR'][$cp['DAYS']]=$colors[$cp['COLOUR']];
            if($data[$cp['GRADE_LEVEL']][$cp['START_TIME']][$cp['DAYS']]){
                $data[$cp['GRADE_LEVEL']][$cp['START_TIME']][$cp['DAYS']].='<br><b style="color:red;">';
                $data[$cp['GRADE_LEVEL']][$cp['START_TIME']][$cp['DAYS']].=$cp['TITLE'];
                $data[$cp['GRADE_LEVEL']][$cp['START_TIME']][$cp['DAYS']].=' - ';
                $data[$cp['GRADE_LEVEL']][$cp['START_TIME']][$cp['DAYS']].=$cp['ROOM'];
                $data[$cp['GRADE_LEVEL']][$cp['START_TIME']][$cp['DAYS']].='</b>';
            }
            else{
                $data[$cp['GRADE_LEVEL']][$cp['START_TIME']][$cp['DAYS']]=$cp['TITLE'];
                $data[$cp['GRADE_LEVEL']][$cp['START_TIME']][$cp['DAYS']].=' - ';
                $data[$cp['GRADE_LEVEL']][$cp['START_TIME']][$cp['DAYS']].=$cp['ROOM'];

            }
        }
    }
    // echo '<pre>'; print_r($data); echo '</pre>';
        // $data[2]['12:30:00']['COLOUR']['F']='77, 81, 77, 0.52';
        // $data[2]['12:30:00']['F']='dîner';
        // $data[3]['12:30:00']['COLOUR']['F']='77, 81, 77, 0.52';
        // $data[3]['12:30:00']['F']='dîner';
    echo '
    <body>   
        <table>
            <thead>
                <tr>
                    <th>Période scolaire</th>
                    <th class="regular-cell1">Niveau</th>
                    <th>Lundi</th>
                    <th>Mardi</th>
                    <th>Mercredi</th>
                    <th>Jeudi</th>
                    <th>Vendredi</th>
                </tr>
            </thead>
            <tbody>
    ';       
    foreach ($get_periods as $key => $period) {
        if( $period["ATTENDANCE"]=='Y' ){
echo '                <tr>
                    <td class="spanning-cell" rowspan="' . $end - $start. '">'. $get_periods[$key]["TITLE"] .'<br> ' . substr($get_periods[$key]["START_TIME"], 0, -3) . ' - '. substr($get_periods[$key]["END_TIME"], 0, -3) . '</td>
        ';
         for ($i = $start; $i < $end; $i++){
            if(($end - $start)  < 2) 
                $cell=1;
            else 
                $cell=$i+1;
            echo '     <!-- Rows 1-5 -->
                    <td class="regular-cell'. $cell .'">'. $i .'</td>
                    <td class="data-cell" style="background-color:rgb('. $data[$i][$get_periods[$key]["START_TIME"]]['COLOUR']['M'] .');  ">'. $data[$i][$get_periods[$key]["START_TIME"]]['M']. '</td>
                    <td class="data-cell" style="background-color:rgb('. $data[$i][$get_periods[$key]["START_TIME"]]['COLOUR']['T'] .');  ">'. $data[$i][$get_periods[$key]["START_TIME"]]['T']. '</td>
                    <td class="data-cell" style="background-color:rgb('. $data[$i][$get_periods[$key]["START_TIME"]]['COLOUR']['W'] .');  ">'. $data[$i][$get_periods[$key]["START_TIME"]]['W']. '</td>
                    <td class="data-cell" style="background-color:rgb('. $data[$i][$get_periods[$key]["START_TIME"]]['COLOUR']['H'] .');  ">'. $data[$i][$get_periods[$key]["START_TIME"]]['H']. '</td>
                    <td class="data-cell" style="background-color:rgb('. $data[$i][$get_periods[$key]["START_TIME"]]['COLOUR']['F'] .');  ">'. $data[$i][$get_periods[$key]["START_TIME"]]['F']. '</td>
                
                
            ';
        echo '</tr>';
         }
        }else{
                echo'<tr>
                    <td class="lunch last-tr" rowspan="1">'. $get_periods[$key]["TITLE"] .'<br> ' . substr($get_periods[$key]["START_TIME"], 0, -3) . ' - '. substr($get_periods[$key]["END_TIME"], 0, -3) . '</td>
                    <td class="lunch last-tr"></td>
                    <td class="lunch last-tr"></tdr>
                    <td class="lunch last-tr"></td>
                    <td class="lunch last-tr"></td>
                    <td class="lunch last-tr"></td>
                    <td class="lunch last-tr" style="background-color:rgb('. $data[2][$get_periods[$key]["START_TIME"]]['COLOUR']['F'] .');  ">'. $data[2][$get_periods[$key]["START_TIME"]]['F']. ' <br> '. $data[3][$get_periods[$key]["START_TIME"]]['F']. '</td>
                    </tr>
                ';
        }

    }
    echo '</tbody>
        </table>
    </body>
    ';
    return true;
}

// Finds, for each primaire period digit (1-4), which levels (1-6) are claimed by an
// override short_name (e.g. PP212 claims levels 1 & 2 of period 2). A basic short_name
// (PP2) only applies to levels that no override has claimed for that period.
function getPrimaireOverrides($periods)
{
    $overrides = array();
    foreach ($periods as $period) {
        if (preg_match('/^PP(\d)(\d+)$/', strtoupper(trim($period["SHORT_NAME"])), $m)) {
            $existing = isset($overrides[$m[1]]) ? $overrides[$m[1]] : array();
            $overrides[$m[1]] = array_unique(array_merge($existing, array_map('intval', str_split($m[2]))));
        }
    }
    return $overrides;
}

// Parses a primaire school_periods short_name to find which levels (1-6) it applies to.
// Special short names encode the levels directly after the period digit,
// e.g. PP212 = period 2, levels 1 & 2 only; PP33456 = period 3, levels 3-6 only.
// A basic short name (PP1, PP2, PP3, PP4) applies to every level EXCEPT those already
// claimed by an override for that same period (see getPrimaireOverrides()); if every
// level is overridden, the basic row applies to no level and is skipped.
// Non-period short names (P-AM, P-Diner, P-PM) always apply to every level.
function getPrimaireLevels($short_name, $start, $end, $overrides = array())
{
    $short_name = strtoupper(trim($short_name));
    if (preg_match('/^PP(\d)(\d+)$/', $short_name, $m)) {
        $levels = array_map('intval', str_split($m[2]));
    } elseif (preg_match('/^PP(\d)$/', $short_name, $m)) {
        $claimed = isset($overrides[$m[1]]) ? $overrides[$m[1]] : array();
        $levels = array_diff(range(1, 6), $claimed);
    } else {
        $levels = range(1, 6);
    }
    return array_values(array_intersect($levels, range($start + 1, $end)));
}

// Groups the levels in [start+1, end] by which set of class periods (school_periods
// rows, identified by PERIOD_ID) applies to each of them. Levels that share the exact
// same set of periods/times end up in the same group, e.g. if period 2 has separate
// PP212 / PP23456 rows, levels 1-2 and 3-6 land in different groups even though periods
// 1, 3 and 4 are shared by everyone.
function getPrimaireLevelGroups($periods, $overrides, $start, $end)
{
    $signatures = array();
    foreach (range($start + 1, $end) as $lvl) {
        $sig = array();
        foreach ($periods as $period) {
            if ($period["ATTENDANCE"] != 'Y') continue;
            if (in_array($lvl, getPrimaireLevels($period["SHORT_NAME"], $start, $end, $overrides)))
                $sig[] = $period["PERIOD_ID"];
        }
        $signatures[$lvl] = implode('-', $sig);
    }
    $groups = array();
    foreach ($signatures as $lvl => $sig) {
        $groups[$sig][] = $lvl;
    }
    return array_values($groups);
}

// Formats a level list like [3,4,5,6] as "3-6" and [1,2,4] as "1-2, 4" for headings.
function formatPrimaireLevelLabel($levels)
{
    sort($levels);
    $ranges = array();
    $rangeStart = $rangeEnd = null;
    foreach ($levels as $lvl) {
        if ($rangeStart === null) {
            $rangeStart = $rangeEnd = $lvl;
        } elseif ($lvl == $rangeEnd + 1) {
            $rangeEnd = $lvl;
        } else {
            $ranges[] = ($rangeStart == $rangeEnd) ? $rangeStart : "$rangeStart-$rangeEnd";
            $rangeStart = $rangeEnd = $lvl;
        }
    }
    if ($rangeStart !== null)
        $ranges[] = ($rangeStart == $rangeEnd) ? $rangeStart : "$rangeStart-$rangeEnd";
    return implode(', ', $ranges);
}

function primaire($start,$end){
    global $colors;

    $get_subjects = DBGet(DBQuery("SELECT subject_id, title FROM `course_subjects` WHERE `school_id` = '".UserSchool()."' AND syear = '".UserSyear()."' ORDER BY `subject_id`"));
    $get_periods = DBGet(DBQuery("SELECT attendance,period_id, title, short_name, start_time, end_time , sort_order FROM `school_periods` WHERE short_name like 'P%' AND `syear` = '".UserSyear()."' AND `school_id` = '".UserSchool()."' ORDER BY `sort_order`"));
    $primaire_overrides = getPrimaireOverrides($get_periods);
    $course_periods = DBGet(DBQuery("SELECT rooms.title as ROOM,rooms.sort_order as COLOUR, course_periods.TITLE,DAYS,START_TIME,END_TIME,course_periods.COURSE_PERIOD_ID,courses.grade_level from course_period_var cpv LEFT JOIN course_periods ON cpv.COURSE_PERIOD_ID = course_periods.COURSE_PERIOD_ID LEFT JOIN rooms ON rooms.room_id = cpv.room_id  LEFT JOIN courses ON courses.course_id = course_periods.course_id where course_periods.SYEAR= '".UserSyear()."'and grade_level in (2,3,4,5,6,7)"));
    $data = array();
    // echo '<pre>'; print_r($get_periods); echo '</pre>';

    foreach($course_periods as $key => $cp){
        $len=strlen($cp['DAYS']);
        $days=$cp['DAYS'];
        for($x = 1; $x <= $len ; $x++) {
            $cp['DAYS']=substr($days,$x-1,1);
            $data[$cp['GRADE_LEVEL']][$cp['START_TIME']]['COLOUR'][$cp['DAYS']]=$colors[$cp['COLOUR']];
            if($data[$cp['GRADE_LEVEL']][$cp['START_TIME']][$cp['DAYS']]){
                $data[$cp['GRADE_LEVEL']][$cp['START_TIME']][$cp['DAYS']].='<br><b style="color:red;">';
                $data[$cp['GRADE_LEVEL']][$cp['START_TIME']][$cp['DAYS']].=$cp['TITLE'];
                $data[$cp['GRADE_LEVEL']][$cp['START_TIME']][$cp['DAYS']].=' - ';
                $data[$cp['GRADE_LEVEL']][$cp['START_TIME']][$cp['DAYS']].=$cp['ROOM'];
                $data[$cp['GRADE_LEVEL']][$cp['START_TIME']][$cp['DAYS']].='</b>';
            }
            else{
                $data[$cp['GRADE_LEVEL']][$cp['START_TIME']][$cp['DAYS']]=$cp['TITLE'];
                $data[$cp['GRADE_LEVEL']][$cp['START_TIME']][$cp['DAYS']].=' - ';
                $data[$cp['GRADE_LEVEL']][$cp['START_TIME']][$cp['DAYS']].=$cp['ROOM'];

            }
        }
    }
    //echo '<pre>'; print_r($data); echo '</pre>';
    // $data[2]['12:30:00']['COLOUR']['F']='77, 81, 77, 0.52';
    // $data[2]['12:30:00']['F']='dîner';
    // $data[3]['12:30:00']['COLOUR']['F']='77, 81, 77, 0.52';
    // $data[3]['12:30:00']['F']='dîner';
    $groups = getPrimaireLevelGroups($get_periods, $primaire_overrides, $start, $end);
    $multipleSchedules = count($groups) > 1;
    foreach ($groups as $group) {
        sort($group);
        if ($multipleSchedules)
            echo '<h4>Horaire Primaire ' . formatPrimaireLevelLabel($group) . '</h4>';
        renderPrimaireGroupTable($get_periods, $data, $group, $primaire_overrides);
    }
}

// Renders one primaire schedule table scoped to the given levels (a subset of 1-6).
function renderPrimaireGroupTable($periods, $data, $levels, $overrides)
{
    $singleLevel = count($levels) < 2;
    echo '
    <body>
        <table>
            <thead>
                <tr>
                    <th>Période scolaire</th>
                    <th class="regular-cell1">Niveau</th>
                    <th>Lundi</th>
                    <th>Mardi</th>
                    <th>Mercredi</th>
                    <th>Jeudi</th>
                    <th>Vendredi</th>
                </tr>
            </thead>
            <tbody>
    ';
    foreach ($periods as $key => $period) {
        if( $period["ATTENDANCE"]=='Y' ){
            $rowLevels = array_values(array_intersect(getPrimaireLevels($period["SHORT_NAME"], 0, 6, $overrides), $levels));
            if (empty($rowLevels)) continue;
echo '                <tr>
                    <td class="spanning-cell" rowspan="' . count($rowLevels). '">'. $period["TITLE"] .'<br> ' . substr($period["START_TIME"], 0, -3) . ' - '. substr($period["END_TIME"], 0, -3) . '</td>
        ';
         foreach ($rowLevels as $lvl){
            if($singleLevel)
                $cell=1;
            else
                $cell=$lvl;
            echo '     <!-- Rows 1-5 -->
                    <td class="regular-cell'. $cell .'">'. $lvl .'</td>
                    <td class="data-cell" style="background-color:rgb('. $data[$lvl+1][$period["START_TIME"]]['COLOUR']['M'] .');  ">'. $data[$lvl+1][$period["START_TIME"]]['M']. '</td>
                    <td class="data-cell" style="background-color:rgb('. $data[$lvl+1][$period["START_TIME"]]['COLOUR']['T'] .');  ">'. $data[$lvl+1][$period["START_TIME"]]['T']. '</td>
                    <td class="data-cell" style="background-color:rgb('. $data[$lvl+1][$period["START_TIME"]]['COLOUR']['W'] .');  ">'. $data[$lvl+1][$period["START_TIME"]]['W']. '</td>
                    <td class="data-cell" style="background-color:rgb('. $data[$lvl+1][$period["START_TIME"]]['COLOUR']['H'] .');  ">'. $data[$lvl+1][$period["START_TIME"]]['H']. '</td>
                    <td class="data-cell" style="background-color:rgb('. $data[$lvl+1][$period["START_TIME"]]['COLOUR']['F'] .');  ">'. $data[$lvl+1][$period["START_TIME"]]['F']. '</td>


            ';
        echo '</tr>';
         }
        }else{
                echo'<tr>
                    <td class="lunch last-tr" rowspan="1">'. $period["TITLE"] .'<br> ' . substr($period["START_TIME"], 0, -3) . ' - '. substr($period["END_TIME"], 0, -3) . '</td>
                    <td class="lunch last-tr"></td>
                    <td class="lunch last-tr"></tdr>
                    <td class="lunch last-tr"></td>
                    <td class="lunch last-tr"></td>
                    <td class="lunch last-tr"></td>
                    <td class="lunch last-tr" style="background-color:rgb('. $data[2][$period["START_TIME"]]['COLOUR']['F'] .');  ">'. $data[2][$period["START_TIME"]]['F']. ' <br> '. $data[3][$period["START_TIME"]]['F']. '</td>
                    </tr>
                ';
        }

    }
    echo '</tbody>
        </table>
    </body>
    ';

}

function secondaire($start,$end){
    global $colors;

    $get_subjects = DBGet(DBQuery("SELECT subject_id, title FROM `course_subjects` WHERE `school_id` = '".UserSchool()."' AND syear = '".UserSyear()."' ORDER BY `subject_id`"));
    $get_periods = DBGet(DBQuery("SELECT attendance,period_id, title, short_name, start_time, end_time , sort_order FROM `school_periods` WHERE short_name like 'S%' AND `syear` = '".UserSyear()."' AND `school_id` = '".UserSchool()."' ORDER BY `sort_order`"));
    $course_periods = DBGet(DBQuery("SELECT rooms.title as ROOM,rooms.sort_order as COLOUR, course_periods.TITLE,DAYS,START_TIME,END_TIME,course_periods.COURSE_PERIOD_ID,courses.grade_level from course_period_var cpv LEFT JOIN course_periods ON cpv.COURSE_PERIOD_ID = course_periods.COURSE_PERIOD_ID LEFT JOIN rooms ON rooms.room_id = cpv.room_id  LEFT JOIN courses ON courses.course_id = course_periods.course_id where course_periods.SYEAR= '".UserSyear()."'and grade_level in (8,9,10,11,12)"));

    foreach($course_periods as $key => $cp){
    $len=strlen($cp['DAYS']);
    $days=$cp['DAYS'];
    for($x = 1; $x <= $len ; $x++) {
        $cp['DAYS']=substr($days,$x-1,1);
        $data[$cp['GRADE_LEVEL']][$cp['START_TIME']]['COLOUR'][$cp['DAYS']]=$colors[$cp['COLOUR']];
        if($data[$cp['GRADE_LEVEL']][$cp['START_TIME']][$cp['DAYS']]){
            $data[$cp['GRADE_LEVEL']][$cp['START_TIME']][$cp['DAYS']].='<br><b style="color:red;">';
            $data[$cp['GRADE_LEVEL']][$cp['START_TIME']][$cp['DAYS']].=$cp['TITLE'];
            $data[$cp['GRADE_LEVEL']][$cp['START_TIME']][$cp['DAYS']].=' - ';
            $data[$cp['GRADE_LEVEL']][$cp['START_TIME']][$cp['DAYS']].=$cp['ROOM'];
            $data[$cp['GRADE_LEVEL']][$cp['START_TIME']][$cp['DAYS']].='</b>';
        }
        else{
            $data[$cp['GRADE_LEVEL']][$cp['START_TIME']][$cp['DAYS']]=$cp['TITLE'];
            $data[$cp['GRADE_LEVEL']][$cp['START_TIME']][$cp['DAYS']].=' - ';
            $data[$cp['GRADE_LEVEL']][$cp['START_TIME']][$cp['DAYS']].=$cp['ROOM'];

        }
    }
    }
    // echo '<pre>'; print_r( $data); echo '</pre>';

    echo '
    <body>   
        <table>
            <thead>
                <tr>
                    <th>Période scolaire</th>
                    <th class="regular-cell1">Niveau</th>
                    <th>Lundi</th>
                    <th>Mardi</th>
                    <th>Mercredi</th>
                    <th>Jeudi</th>
                    <th>Vendredi</th>
                </tr>
            </thead>
            <tbody>
    ';       
    foreach ($get_periods as $key => $period) {
        if( $period["ATTENDANCE"]=='Y' ){
            echo '                <tr>
                    <td class="spanning-cell" rowspan="' . $end - $start. '">'. $get_periods[$key]["TITLE"] .'<br> ' . substr($get_periods[$key]["START_TIME"], 0, -3) . ' - '. substr($get_periods[$key]["END_TIME"], 0, -3) . '</td>
            ';
         for ($i = $start; $i < $end; $i++){
            echo '     <!-- Rows 1-5 -->
                    <td class="regular-cell1">'. $i+1 .'</td>
                    <td class="data-cell" style="background-color:rgb('. $data[$i+8][$get_periods[$key]["START_TIME"]]['COLOUR']['M'] .');  ">'. $data[$i+8][$get_periods[$key]["START_TIME"]]['M']. '</td>
                    <td class="data-cell" style="background-color:rgb('. $data[$i+8][$get_periods[$key]["START_TIME"]]['COLOUR']['T'] .');  ">'. $data[$i+8][$get_periods[$key]["START_TIME"]]['T']. '</td>
                    <td class="data-cell" style="background-color:rgb('. $data[$i+8][$get_periods[$key]["START_TIME"]]['COLOUR']['W'] .');  ">'. $data[$i+8][$get_periods[$key]["START_TIME"]]['W']. '</td>
                    <td class="data-cell" style="background-color:rgb('. $data[$i+8][$get_periods[$key]["START_TIME"]]['COLOUR']['H'] .');  ">'. $data[$i+8][$get_periods[$key]["START_TIME"]]['H']. '</td>
                    <td class="data-cell" style="background-color:rgb('. $data[$i+8][$get_periods[$key]["START_TIME"]]['COLOUR']['F'] .');  ">'. $data[$i+8][$get_periods[$key]["START_TIME"]]['F']. '</td>
                
                
            ';
        echo '</tr>';
         }
        }else{
                echo'<tr>
                    <td class="lunch last-tr" rowspan="1">'. $get_periods[$key]["TITLE"] .'<br> ' . substr($get_periods[$key]["START_TIME"], 0, -3) . ' - '. substr($get_periods[$key]["END_TIME"], 0, -3) . '</td>
                    <td class="lunch last-tr"></td>
                    <td class="lunch last-tr"></tdr>
                    <td class="lunch last-tr"></td>
                    <td class="lunch last-tr"></td>
                    <td class="lunch last-tr"></td>
                    <td class="lunch last-tr" style="background-color:rgb('. $data[2][$get_periods[$key]["START_TIME"]]['COLOUR']['F'] .');  ">'. $data[2][$get_periods[$key]["START_TIME"]]['F']. ' <br> '. $data[3][$get_periods[$key]["START_TIME"]]['F']. '</td>
                    </tr>
                ';
        }

    }
    echo '</tbody>
        </table>
    </body>

    ';
}


function CreateSelect($val, $name, $opt, $cap, $link)
{
    $html = '<label class="control-label text-uppercase"><b>' . $cap . '</b></label>';
    $html .= "<select name=" . $name . " id=" . $name . " class=\"form-control\" onChange=\"window.location='" . $link . "' + this.options[this.selectedIndex].value;\">";
    $html .= "<option value=''>" . $opt . "</option>";

    foreach ($val as $key => $value) {
        if ($value[strtoupper($name)] == $_REQUEST[$name])
            $html .= "<option selected value=" . $value[strtoupper($name)] . ">" . $value['TITLE'] . "</option>";
        else
            $html .= "<option value=" . $value[strtoupper($name)] . ">" . $value['TITLE'] . "</option>";
    }
    $html .= "</select>";
    return $html;
}


function do_style(){
    echo '
    <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Périodes par jour</title>
            <style>
                @media print {
                    table {page-break-inside: avoid;}
                }
                table {
                    border-collapse: collapse;
                    margin: 15px;
                    font-size:10px;
                    font-family: Arial, sans-serif;
                }
                
                th, td {
                    border: 1px solid #333;
                    padding: 4px 6px;
                    text-align: center;

                }
                
                th {
                    background-color: #f0f0f0;
                    font-weight: bold;
                }
                
                .spanning-cell {
                    background-color:rgb(166, 206, 243);
                    font-weight: bold;
                    vertical-align: middle;
                    width: 180px;
                }
                .level-tr {
                    border-bottom-width: 2px;
                    border-bottom-style: solid;
                    border-bottom-color: black;
                }
                .last-tr {
                    border-bottom-width: 2px;
                    border-bottom-style: solid;
                    border-bottom-color: black;
                }
                .regular-cell {
                background-color:rgb(246, 242, 242); color: black;
                width: 50px;
                }
                .regular-cell1 {
                background-color:rgb(239, 237, 237); color: black;
                width: 50px;
                }
                .regular-cell2 {
                background-color:rgb(239, 237, 237); color: black;
                width: 50px;
                }
                .regular-cell3 {
                background-color:rgb(210, 208, 208); color: black;
                width: 50px;
                }
                .regular-cell4 {
                background-color:rgb(210, 208, 208); color: black;
                width: 50px;
                }
                .regular-cell5 {
                background-color:rgb(178, 175, 175); color: black;
                width: 50px;
                }
                .regular-cell6 {
                background-color:rgb(178, 175, 175); color: black;
                width: 50px;
                }
                .data-cell {
                background-color:rgba(77, 81, 77, 0.52); color: black;
                width: 300px;
                }
                .lunch {
                background-color:rgba(77, 81, 77, 0.52); color: black;
                width: 50px;
                }
            </style>
        </head>   
    ';
}