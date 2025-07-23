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


$get_subjects = DBGet(DBQuery("SELECT subject_id, title FROM `course_subjects` WHERE `school_id` = '".UserSchool()."' AND syear = '".UserSyear()."' ORDER BY `subject_id`"));
$get_periods = DBGet(DBQuery("SELECT attendance,period_id, title, short_name, start_time, end_time , sort_order FROM `school_periods` WHERE short_name like 'S%' AND `syear` = '".UserSyear()."' AND `school_id` = '".UserSchool()."' ORDER BY `sort_order`"));
$course_periods = DBGet(DBQuery("SELECT rooms.title as ROOM,rooms.sort_order as COLOUR, course_periods.TITLE,DAYS,START_TIME,END_TIME,course_periods.COURSE_PERIOD_ID,courses.grade_level from course_period_var cpv LEFT JOIN course_periods ON cpv.COURSE_PERIOD_ID = course_periods.COURSE_PERIOD_ID LEFT JOIN rooms ON rooms.room_id = cpv.room_id  LEFT JOIN courses ON courses.course_id = course_periods.course_id where course_periods.SYEAR= '".UserSyear()."'and grade_level in (8,9,10,11,12)"));

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

echo '<!DOCTYPE html>
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
            font-size:11px;
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
         background-color:rgb(210, 208, 208); color: black;
         width: 50px;
        }
        .regular-cell3 {
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
    echo '     <!-- Rows 1-5 -->
                <tr>
                    <td class="spanning-cell" rowspan="5">'. $get_periods[$key]["TITLE"] .'<br> ' . substr($get_periods[$key]["START_TIME"], 0, -3) . ' - '. substr($get_periods[$key]["END_TIME"], 0, -3) . '</td>
                    <td class="regular-cell1">1</td>
                    <td class="data-cell" class="data-cell" style="background-color:rgb('. $data[8][$get_periods[$key]["START_TIME"]]['COLOUR']['M'] .');  ">'. $data[8][$get_periods[$key]["START_TIME"]]['M']. '</td>
                    <td class="data-cell" style="background-color:rgb('. $data[8][$get_periods[$key]["START_TIME"]]['COLOUR']['T'] .');  ">'. $data[8][$get_periods[$key]["START_TIME"]]['T']. '</td>
                    <td class="data-cell" style="background-color:rgb('. $data[8][$get_periods[$key]["START_TIME"]]['COLOUR']['W'] .');  ">'. $data[8][$get_periods[$key]["START_TIME"]]['W']. '</td>
                    <td class="data-cell" style="background-color:rgb('. $data[8][$get_periods[$key]["START_TIME"]]['COLOUR']['H'] .');  ">'. $data[8][$get_periods[$key]["START_TIME"]]['H']. '</td>
                    <td class="data-cell" style="background-color:rgb('. $data[8][$get_periods[$key]["START_TIME"]]['COLOUR']['F'] .');  ">'. $data[8][$get_periods[$key]["START_TIME"]]['F']. '</td>
                </tr>
                <tr>
                    <td class="regular-cell1">2</td>
                    <td class="data-cell" style="background-color:rgb('. $data[9][$get_periods[$key]["START_TIME"]]['COLOUR']['M'] .');  ">'. $data[9][$get_periods[$key]["START_TIME"]]['M']. '</td>
                    <td class="data-cell" style="background-color:rgb('. $data[9][$get_periods[$key]["START_TIME"]]['COLOUR']['T'] .');  ">'. $data[9][$get_periods[$key]["START_TIME"]]['T']. '</td>
                    <td class="data-cell" style="background-color:rgb('. $data[9][$get_periods[$key]["START_TIME"]]['COLOUR']['W'] .');  ">'. $data[9][$get_periods[$key]["START_TIME"]]['W']. '</td>
                    <td class="data-cell" style="background-color:rgb('. $data[9][$get_periods[$key]["START_TIME"]]['COLOUR']['H'] .');  ">'. $data[9][$get_periods[$key]["START_TIME"]]['H']. '</td>
                    <td class="data-cell" style="background-color:rgb('. $data[9][$get_periods[$key]["START_TIME"]]['COLOUR']['F'] .');  ">'. $data[9][$get_periods[$key]["START_TIME"]]['F']. '</td>
                </tr>
                <tr>
                    <td class="regular-cell1">3</td>
                    <td class="data-cell" style="background-color:rgb('. $data[10][$get_periods[$key]["START_TIME"]]['COLOUR']['M'] .');  ">'. $data[10][$get_periods[$key]["START_TIME"]]['M']. '</td>
                    <td class="data-cell" style="background-color:rgb('. $data[10][$get_periods[$key]["START_TIME"]]['COLOUR']['T'] .');  ">'. $data[10][$get_periods[$key]["START_TIME"]]['T']. '</td>
                    <td class="data-cell" style="background-color:rgb('. $data[10][$get_periods[$key]["START_TIME"]]['COLOUR']['W'] .');  ">'. $data[10][$get_periods[$key]["START_TIME"]]['W']. '</td>
                    <td class="data-cell" style="background-color:rgb('. $data[10][$get_periods[$key]["START_TIME"]]['COLOUR']['H'] .');  ">'. $data[10][$get_periods[$key]["START_TIME"]]['H']. '</td>
                    <td class="data-cell" style="background-color:rgb('. $data[10][$get_periods[$key]["START_TIME"]]['COLOUR']['F'] .');  ">'. $data[10][$get_periods[$key]["START_TIME"]]['F']. '</td>
                </tr>
                <tr>
                    <td class="regular-cell1">4</td>
                    <td class="data-cell" style="background-color:rgb('. $data[11][$get_periods[$key]["START_TIME"]]['COLOUR']['M'] .');  ">'. $data[11][$get_periods[$key]["START_TIME"]]['M']. '</td>
                    <td class="data-cell" style="background-color:rgb('. $data[11][$get_periods[$key]["START_TIME"]]['COLOUR']['T'] .');  ">'. $data[11][$get_periods[$key]["START_TIME"]]['T']. '</td>
                    <td class="data-cell" style="background-color:rgb('. $data[11][$get_periods[$key]["START_TIME"]]['COLOUR']['W'] .');  ">'. $data[11][$get_periods[$key]["START_TIME"]]['W']. '</td>
                    <td class="data-cell" style="background-color:rgb('. $data[11][$get_periods[$key]["START_TIME"]]['COLOUR']['H'] .');  ">'. $data[11][$get_periods[$key]["START_TIME"]]['H']. '</td>
                    <td class="data-cell" style="background-color:rgb('. $data[11][$get_periods[$key]["START_TIME"]]['COLOUR']['F'] .');  ">'. $data[11][$get_periods[$key]["START_TIME"]]['F']. '</td>
                </tr>
                <tr>
                    <td class="regular-cell1">5</td>
                    <td class="data-cell" style="background-color:rgb('. $data[12][$get_periods[$key]["START_TIME"]]['COLOUR']['M'] .');  ">'. $data[12][$get_periods[$key]["START_TIME"]]['M']. '</td>
                    <td class="data-cell" style="background-color:rgb('. $data[12][$get_periods[$key]["START_TIME"]]['COLOUR']['T'] .');  ">'. $data[12][$get_periods[$key]["START_TIME"]]['T']. '</td>
                    <td class="data-cell" style="background-color:rgb('. $data[12][$get_periods[$key]["START_TIME"]]['COLOUR']['W'] .');  ">'. $data[12][$get_periods[$key]["START_TIME"]]['W']. '</td>
                    <td class="data-cell" style="background-color:rgb('. $data[12][$get_periods[$key]["START_TIME"]]['COLOUR']['H'] .');  ">'. $data[12][$get_periods[$key]["START_TIME"]]['H']. '</td>
                    <td class="data-cell" style="background-color:rgb('. $data[12][$get_periods[$key]["START_TIME"]]['COLOUR']['F'] .');  ">'. $data[12][$get_periods[$key]["START_TIME"]]['F']. '</td>
                </tr>
    ';
    }else{
            echo'<tr>
                <td class="lunch" rowspan="1">'. $get_periods[$key]["TITLE"] .'<br> ' . substr($get_periods[$key]["START_TIME"], 0, -3) . ' - '. substr($get_periods[$key]["END_TIME"], 0, -3) . '</td>
                <td class="lunch"></td>
                <td class="lunch"></tdr>
                <td class="lunch"></td>
                <td class="lunch"></td>
                <td class="lunch"></td>
                <td class="lunch"></td>
                </tr>
            ';
    }

}
echo '</tbody>
    </table>
</body>
</html>
<br><br><br><br>
<div class="page-break"></div>

';

$get_subjects = DBGet(DBQuery("SELECT subject_id, title FROM `course_subjects` WHERE `school_id` = '".UserSchool()."' AND syear = '".UserSyear()."' ORDER BY `subject_id`"));
$get_periods = DBGet(DBQuery("SELECT attendance,period_id, title, short_name, start_time, end_time , sort_order FROM `school_periods` WHERE short_name like 'P%' AND `syear` = '".UserSyear()."' AND `school_id` = '".UserSchool()."' ORDER BY `sort_order`"));
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
$data[2]['12:30:00']['COLOUR']['F']='77, 81, 77, 0.52';
$data[2]['12:30:00']['F']='dîner';
$data[3]['12:30:00']['COLOUR']['F']='77, 81, 77, 0.52';
$data[3]['12:30:00']['F']='dîner';
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
    echo '     <!-- Rows 1-5 -->
            <tr>
                <td class="spanning-cell" rowspan="6">'. $get_periods[$key]["TITLE"] .'<br> ' . substr($get_periods[$key]["START_TIME"], 0, -3) . ' - '. substr($get_periods[$key]["END_TIME"], 0, -3) . '</td>
                <td class="regular-cell1">1</td>
                <td class="data-cell" style="background-color:rgb('. $data[2][$get_periods[$key]["START_TIME"]]['COLOUR']['M'] .');  ">'. $data[2][$get_periods[$key]["START_TIME"]]['M']. '</td>
                <td class="data-cell" style="background-color:rgb('. $data[2][$get_periods[$key]["START_TIME"]]['COLOUR']['T'] .');  ">'. $data[2][$get_periods[$key]["START_TIME"]]['T']. '</td>
                <td class="data-cell" style="background-color:rgb('. $data[2][$get_periods[$key]["START_TIME"]]['COLOUR']['W'] .');  ">'. $data[2][$get_periods[$key]["START_TIME"]]['W']. '</td>
                <td class="data-cell" style="background-color:rgb('. $data[2][$get_periods[$key]["START_TIME"]]['COLOUR']['H'] .');  ">'. $data[2][$get_periods[$key]["START_TIME"]]['H']. '</td>
                <td class="data-cell" style="background-color:rgb('. $data[2][$get_periods[$key]["START_TIME"]]['COLOUR']['F'] .');  ">'. $data[2][$get_periods[$key]["START_TIME"]]['F']. '</td>
            </tr>
            <tr class="level-tr">
                <td class="regular-cell1">2</td>
                <td class="data-cell" style="background-color:rgb('. $data[3][$get_periods[$key]["START_TIME"]]['COLOUR']['M'] .');  ">'. $data[3][$get_periods[$key]["START_TIME"]]['M']. '</td>
                <td class="data-cell" style="background-color:rgb('. $data[3][$get_periods[$key]["START_TIME"]]['COLOUR']['T'] .');  ">'. $data[3][$get_periods[$key]["START_TIME"]]['T']. '</td>
                <td class="data-cell" style="background-color:rgb('. $data[3][$get_periods[$key]["START_TIME"]]['COLOUR']['W'] .');  ">'. $data[3][$get_periods[$key]["START_TIME"]]['W']. '</td>
                <td class="data-cell" style="background-color:rgb('. $data[3][$get_periods[$key]["START_TIME"]]['COLOUR']['H'] .');  ">'. $data[3][$get_periods[$key]["START_TIME"]]['H']. '</td>
                <td class="data-cell" style="background-color:rgb('. $data[3][$get_periods[$key]["START_TIME"]]['COLOUR']['F'] .');  ">'. $data[3][$get_periods[$key]["START_TIME"]]['F']. '</td>
            </tr>
            <tr>
                <td class="regular-cell2">3</td>
                <td class="data-cell" style="background-color:rgb('. $data[4][$get_periods[$key]["START_TIME"]]['COLOUR']['M'] .');  ">'. $data[4][$get_periods[$key]["START_TIME"]]['M']. '</td>
                <td class="data-cell" style="background-color:rgb('. $data[4][$get_periods[$key]["START_TIME"]]['COLOUR']['T'] .');  ">'. $data[4][$get_periods[$key]["START_TIME"]]['T']. '</td>
                <td class="data-cell" style="background-color:rgb('. $data[4][$get_periods[$key]["START_TIME"]]['COLOUR']['W'] .');  ">'. $data[4][$get_periods[$key]["START_TIME"]]['W']. '</td>
                <td class="data-cell" style="background-color:rgb('. $data[4][$get_periods[$key]["START_TIME"]]['COLOUR']['H'] .');  ">'. $data[4][$get_periods[$key]["START_TIME"]]['H']. '</td>
                <td class="data-cell" style="background-color:rgb('. $data[4][$get_periods[$key]["START_TIME"]]['COLOUR']['F'] .');  ">'. $data[4][$get_periods[$key]["START_TIME"]]['F']. '</td>
            </tr>
            <tr class="level-tr">
                <td class="regular-cell2">4</td>
                <td class="data-cell" style="background-color:rgb('. $data[5][$get_periods[$key]["START_TIME"]]['COLOUR']['M'] .');  ">'. $data[5][$get_periods[$key]["START_TIME"]]['M']. '</td>
                <td class="data-cell" style="background-color:rgb('. $data[5][$get_periods[$key]["START_TIME"]]['COLOUR']['T'] .');  ">'. $data[5][$get_periods[$key]["START_TIME"]]['T']. '</td>
                <td class="data-cell" style="background-color:rgb('. $data[5][$get_periods[$key]["START_TIME"]]['COLOUR']['W'] .');  ">'. $data[5][$get_periods[$key]["START_TIME"]]['W']. '</td>
                <td class="data-cell" style="background-color:rgb('. $data[5][$get_periods[$key]["START_TIME"]]['COLOUR']['H'] .');  ">'. $data[5][$get_periods[$key]["START_TIME"]]['H']. '</td>
                <td class="data-cell" style="background-color:rgb('. $data[5][$get_periods[$key]["START_TIME"]]['COLOUR']['F'] .');  ">'. $data[5][$get_periods[$key]["START_TIME"]]['F']. '</td>
            </tr>
            <tr>
                <td class="regular-cell3">5</td>
                <td class="data-cell" style="background-color:rgb('. $data[6][$get_periods[$key]["START_TIME"]]['COLOUR']['M'] .');  ">'. $data[6][$get_periods[$key]["START_TIME"]]['M']. '</td>
                <td class="data-cell" style="background-color:rgb('. $data[6][$get_periods[$key]["START_TIME"]]['COLOUR']['T'] .');  ">'. $data[6][$get_periods[$key]["START_TIME"]]['T']. '</td>
                <td class="data-cell" style="background-color:rgb('. $data[6][$get_periods[$key]["START_TIME"]]['COLOUR']['W'] .');  ">'. $data[6][$get_periods[$key]["START_TIME"]]['W']. '</td>
                <td class="data-cell" style="background-color:rgb('. $data[6][$get_periods[$key]["START_TIME"]]['COLOUR']['H'] .');  ">'. $data[6][$get_periods[$key]["START_TIME"]]['H']. '</td>
                <td class="data-cell" style="background-color:rgb('. $data[6][$get_periods[$key]["START_TIME"]]['COLOUR']['F'] .');  ">'. $data[6][$get_periods[$key]["START_TIME"]]['F']. '</td>
            </tr>
            <tr class="last-tr">
                <td class="regular-cell3">6</td>
                <td class="data-cell" style="background-color:rgb('. $data[7][$get_periods[$key]["START_TIME"]]['COLOUR']['M'] .');  ">'. $data[7][$get_periods[$key]["START_TIME"]]['M']. '</td>
                <td class="data-cell" style="background-color:rgb('. $data[7][$get_periods[$key]["START_TIME"]]['COLOUR']['T'] .');  ">'. $data[7][$get_periods[$key]["START_TIME"]]['T']. '</td>
                <td class="data-cell" style="background-color:rgb('. $data[7][$get_periods[$key]["START_TIME"]]['COLOUR']['W'] .');  ">'. $data[7][$get_periods[$key]["START_TIME"]]['W']. '</td>
                <td class="data-cell" style="background-color:rgb('. $data[7][$get_periods[$key]["START_TIME"]]['COLOUR']['H'] .');  ">'. $data[7][$get_periods[$key]["START_TIME"]]['H']. '</td>
                <td class="data-cell" style="background-color:rgb('. $data[7][$get_periods[$key]["START_TIME"]]['COLOUR']['F'] .');  ">'. $data[7][$get_periods[$key]["START_TIME"]]['F']. '</td>
            </tr>
            
    ';
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
</html>
';

if (clean_param($_REQUEST['modfunc'], PARAM_ALPHAMOD) == 'print' && $_REQUEST['report']) {
    echo '<style type="text/css">*{font-family:arial; font-size:10px;}</style>';
//    echo '<link rel="stylesheet" type="text/css" href="assets/css/export_print.css" />';
}
    echo "<FORM name=exp class=no-margin-bottom id=exp action=ForExport.php?modname=" . strip_tags(trim($_REQUEST['modname'])) . "&modfunc=print&marking_period_id=" . $_REQUEST['marking_period_id'] . "&_openSIS_PDF=true&report=true method=POST target=_blank>";
    echo '<div class="text-right"><INPUT type=submit class="btn btn-primary" value=\'' . _print . '\'></div>';


// if (clean_param($_REQUEST['modfunc'], PARAM_ALPHAMOD) == 'print' && $_REQUEST['report']) {
//     echo '<style type="text/css">*{font-family:arial; font-size:12px;}</style>';
//     echo '<link rel="stylesheet" type="text/css" href="assets/css/export_print.css" />';
//     if (clean_param($_REQUEST['marking_period_id'], PARAM_ALPHANUM))
//         $where = ' AND MARKING_PERIOD_ID=' . $_REQUEST['marking_period_id'];
//     $sql = 'select distinct
// 				(select title from course_subjects where subject_id=(select subject_id from courses where course_id=course_periods.course_id)) as subject,
// 				(select title from courses where course_id=course_periods.course_id) as COURSE_TITLE,course_id
// 				from course_periods where school_id=\'' . UserSchool() . '\' and syear=\'' . UserSyear() . '\' ' . $where . ' order by subject,COURSE_TITLE';


//     $ret = DBGet(DBQuery($sql));

//     if (count($ret)) {

//         foreach ($ret as $s_id) {
//             echo "<table width=100%  style=\" font-family:Arial; font-size:12px;\" >";
//             $mark_name_rp = DBGet(DBQuery('SELECT TITLE,SHORT_NAME,\'2\'  FROM school_quarters WHERE MARKING_PERIOD_ID=\'' . $_REQUEST['marking_period_id'] . '\' AND SCHOOL_ID=\'' . UserSchool() . '\' AND SYEAR=\'' . UserSyear() . '\' UNION SELECT TITLE,SHORT_NAME,\'1\'  FROM school_semesters WHERE MARKING_PERIOD_ID=\'' . $_REQUEST['marking_period_id'] . '\' AND SCHOOL_ID=\'' . UserSchool() . '\' AND SYEAR=\'' . UserSyear() . '\' UNION SELECT TITLE,SHORT_NAME,\'0\'  FROM school_years WHERE MARKING_PERIOD_ID=\'' . $_REQUEST['marking_period_id'] . '\' AND SCHOOL_ID=\'' . UserSchool() . '\' AND SYEAR=\'' . UserSyear() . '\' ORDER BY 3'));
//             $mark_name_rpt = $mark_name_rp[1]['TITLE'];

//             if ($mark_name_rpt != '') {
//                 echo "<tr><td width=105>" . DrawLogo() . "</td><td  style=\"font-size:15px; font-weight:bold; padding-top:20px;\">" . GetSchool(UserSchool()) . "<div style=\"font-size:12px;\">" . _courseCatalogByTerm . ": " . $mark_name_rpt . "</div></td><td align=right style=\"padding-top:20px;\">" . ProperDate(DBDate()) . "<br />" . _poweredBy . " OpenSIS</td></tr><tr><td colspan=3 style=\"border-top:1px solid #333;\">&nbsp;</td></tr></table>";
//             } else {
//                 echo "<tr><td width=105>" . DrawLogo() . "</td><td  style=\"font-size:15px; font-weight:bold; padding-top:20px;\">" . GetSchool(UserSchool()) . "<div style=\"font-size:12px;\">" . _courseCatalogByTerm . ": " . _all . "</div></td><td align=right style=\"padding-top:20px;\">" . ProperDate(DBDate()) . "<br />" . _poweredBy . " openSIS</td></tr><tr><td colspan=3 style=\"border-top:1px solid #333;\">&nbsp;</td></tr></table>";
//             }

//             echo '<div align="center">';
//             echo '<table border="0" width="100%" align="center"><tr><td><font face=verdana size=-1><b>' . $s_id['SUBJECT'] . '</b></font></td></tr>';
//             echo '<tr><td align="right"><table border="0" width="97%"><tr><td><font face=verdana size=-1><b>' . $s_id['COURSE_TITLE'] . '</b></font></td></tr>';


//             if (!$_REQUEST['marking_period_id']) {

//                 $sql_periods = 'SELECT cp.SHORT_NAME,(SELECT TITLE FROM school_periods WHERE period_id=cpv.period_id) AS PERIOD,r.TITLE as ROOM,SCHEDULE_TYPE, DAYOFWEEK(COURSE_PERIOD_DATE) AS CP_DAYS,cpv.DAYS,(SELECT CONCAT(LAST_NAME,\' \',FIRST_NAME,\' \') from staff where staff_id=cp.TEACHER_ID) as TEACHER from course_periods cp,course_period_var cpv,rooms r where cp.course_id=' . $s_id['COURSE_ID'] . ' and cp.syear=\'' . UserSyear() . '\' and cp.course_period_id=cpv.course_period_id and cpv.room_id=r.room_id and cp.school_id=\'' . UserSchool() . '\'';
//             } else {

//                 $sql_periods = 'SELECT distinct cp.SHORT_NAME,(select CONCAT(START_TIME,\' - \',END_TIME,\' \') from school_periods where period_id=cpv.period_id) as PERIOD,r.TITLE as ROOM,SCHEDULE_TYPE, DAYOFWEEK(COURSE_PERIOD_DATE) AS CP_DAYS,cpv.DAYS,(select CONCAT(LAST_NAME,\' \',FIRST_NAME,\' \') from staff where staff_id=cp.TEACHER_ID) as TEACHER from course_periods cp,course_period_var cpv,rooms r where cp.course_id=' . $s_id['COURSE_ID'] . ' and cp.syear=\'' . UserSyear() . '\' and cp.course_period_id=cpv.course_period_id and cpv.room_id=r.room_id and cp.school_id=\'' . UserSchool() . '\' and cp.marking_period_id=\'' . $_REQUEST['marking_period_id'] . '\'';
//             }



//             $period_list = DBGet(DBQuery($sql_periods));
//             //print_r($period_list);
//             foreach ($period_list as $key => $val) {
//                 $cal_days = '';
//                 if ($val['CP_DAYS'] != '' && $val['SCHEDULE_TYPE'] == 'BLOCKED') {
//                     switch ($val['CP_DAYS']) {
//                         case 1:
//                             $cal_days = 'U';
//                             break;
//                         case 2:
//                             $cal_days = 'M';
//                             break;
//                         case 3:
//                             $cal_days = 'T';
//                             break;
//                         case 4:
//                             $cal_days = 'W';
//                             break;
//                         case 5:
//                             $cal_days = 'H';
//                             break;
//                         case 6:
//                             $cal_days = 'F';
//                             break;
//                         case 7:
//                             $cal_days = 'S';
//                             break;
//                     }
//                     $period_list[$key]['DAYS'] = $cal_days;
//                 }
//             }
//             ##############################################List Output Generation##################################################

//             $columns = array('SHORT_NAME' => _coursePeriod, 'PERIOD' => _time, 'DAYS' => _days, 'ROOM' => _location, 'TEACHER' => _teacher);


//             echo '<tr><td colspan="2" valign="top" align="right">';
//             PrintCatalog($period_list, $columns, _course, _courses, '', '', array('search' => false));
//             echo '</td></tr></table></td></tr></table></td></tr>';

//             ######################################################################################################################
//             echo '</table></div>';

//             echo "<div style=\"page-break-before: always;\"></div>";
//         }
//     } else
//         echo '<table width=100%><tr><td align=center><font color=red face=verdana size=2><strong>' . _noCoursesAreFoundInThisTerm . '</strong></font></td></tr></table>';
// } else {
//     echo '<div class="row">';
//     echo '<div class="col-md-6 col-md-offset-3">';
//     PopTable('header', _printCatalogByTerm, 'class="panel panel-default"');
//     echo "<FORM id='search' name='search' class='form-horizontal' method=POST action=Modules.php?modname=" . strip_tags(trim($_REQUEST['modname'])) . ">";
//     $mp_RET = DBGet(DBQuery('SELECT MARKING_PERIOD_ID,TITLE,SHORT_NAME,\'2\'  FROM school_quarters WHERE SCHOOL_ID=\'' . UserSchool() . '\' AND SYEAR=\'' . UserSyear() . '\' UNION SELECT MARKING_PERIOD_ID,TITLE,SHORT_NAME,\'1\'  FROM school_semesters WHERE SCHOOL_ID=\'' . UserSchool() . '\' AND SYEAR=\'' . UserSyear() . '\' UNION SELECT MARKING_PERIOD_ID,TITLE,SHORT_NAME,\'0\'  FROM school_years WHERE SCHOOL_ID=\'' . UserSchool() . '\' AND SYEAR=\'' . UserSyear() . '\' ORDER BY 3'));
//     unset($options);
//     if (count($mp_RET)) {
//         foreach ($mp_RET as $key => $value) {
//             if ($value['MARKING_PERIOD_ID'] == $_REQUEST['marking_period_id'])
//                 $mp_RET[$key]['row_color'] = Preferences('HIGHLIGHT');
//         }

//         $columns = array('TITLE' => _markingPeriods);
//         $link = array();
//         $link['TITLE']['link'] = "Modules.php?modname=$_REQUEST[modname]";
//         $link['TITLE']['variables'] = array('marking_period_id' => 'MARKING_PERIOD_ID', 'mp_name' => 'SHORT_NAME');
//         $link['TITLE']['link'] .= "&modfunc=$_REQUEST[modfunc]";

//         echo '<div class="form-group"><div class="col-md-12">' . CreateSelect($mp_RET, 'marking_period_id', 'All', _selectTerm, 'Modules.php?modname=' . strip_tags(trim($_REQUEST['modname'])) . '&marking_period_id=') . '</div></div>';
//     }
//     if (clean_param($_REQUEST['marking_period_id'], PARAM_ALPHANUM)) {
//         $mark_name = DBGet(DBQuery('SELECT TITLE,SHORT_NAME,\'2\'  FROM school_quarters WHERE MARKING_PERIOD_ID=\'' . $_REQUEST['marking_period_id'] . '\' AND SCHOOL_ID=\'' . UserSchool() . '\' AND SYEAR=\'' . UserSyear() . '\' UNION SELECT TITLE,SHORT_NAME,\'1\'  FROM school_semesters WHERE MARKING_PERIOD_ID=\'' . $_REQUEST['marking_period_id'] . '\' AND SCHOOL_ID=\'' . UserSchool() . '\' AND SYEAR=\'' . UserSyear() . '\' UNION SELECT TITLE,SHORT_NAME,\'0\'  FROM school_years WHERE MARKING_PERIOD_ID=\'' . $_REQUEST['marking_period_id'] . '\' AND SCHOOL_ID=\'' . UserSchool() . '\' AND SYEAR=\'' . UserSyear() . '\' ORDER BY 3'));
//         $mark_name = $mark_name[1]['SHORT_NAME'];
//         echo '<div class="alert bg-success alert-styled-left">' . _reportGeneratedFor . ' ' . $mark_name . ' ' . _term . '</div>';
//     } else {
//         echo '<div class="alert bg-success alert-styled-left">' . _reportGeneratedForAllTerms . '</div>';
//     }
//     echo '</form>';
//     echo "<FORM name=exp class=no-margin-bottom id=exp action=ForExport.php?modname=" . strip_tags(trim($_REQUEST['modname'])) . "&modfunc=print&marking_period_id=" . $_REQUEST['marking_period_id'] . "&_openSIS_PDF=true&report=true method=POST target=_blank>";
//     echo '<div class="text-right"><INPUT type=submit class="btn btn-primary" value=\'' . _print . '\'></div>';
//     echo '</form>';
//     PopTable('footer');
//     echo '</div>'; //.col-md-6.col-md-offset-3
//     echo '</div>'; //.row
// }

// ##########functions###################

// function CreateSelect($val, $name, $opt, $cap, $link)
// {

//     $html = '<label class="control-label text-uppercase"><b>' . $cap . '</b></label>';
//     $html .= "<select name=" . $name . " id=" . $name . " class=\"form-control\" onChange=\"window.location='" . $link . "' + this.options[this.selectedIndex].value;\">";
//     $html .= "<option value=''>" . $opt . "</option>";

//     foreach ($val as $key => $value) {
//         if ($value[strtoupper($name)] == $_REQUEST[$name])
//             $html .= "<option selected value=" . $value[strtoupper($name)] . ">" . $value['TITLE'] . "</option>";
//         else
//             $html .= "<option value=" . $value[strtoupper($name)] . ">" . $value['TITLE'] . "</option>";
//     }


//     $html .= "</select>";
//     return $html;
// }
