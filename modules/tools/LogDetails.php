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
if (!$_REQUEST['modfunc']) {

    $start_date = date('Y-m') . '-01';
    $end_date = DBDate('mysql');
    echo '<div class="row">';
    echo '<div class="col-md-8 col-md-offset-2">';
    echo "<FORM class=\"form-horizontal\" name=log id=log action=Modules.php?modname=$_REQUEST[modname]&modfunc=generate method=POST>";
    PopTable('header',  _logDetails);

    echo '<h5 class="text-center">'._pleaseSelectDateRange.'</h5>';

    echo '<div class="row">';
    echo '<div class="col-lg-6 col-lg-offset-3">';

    echo '<div class="form-group">';
    echo '<label class="col-md-2 control-label text-right">'._from.'</label><div class="col-md-10">';
    echo DateInputAY($start_date, 'start', 1);
    echo '</div>'; //.col-md-10
    echo '</div>'; //.form-group

    echo '</div>'; //.col-lg-6
    echo '</div>'; //.row
    echo '<div class="row">';
    echo '<div class="col-lg-6 col-lg-offset-3">';

    echo '<div class="form-group">';
    echo '<label class="col-md-2 control-label text-right">'._to.'</label><div class="col-md-10">';
    echo DateInputAY($end_date, 'end', 2);
    echo '</div>'; //.col-md-10
    echo '</div>'; //.form-group

    echo '</div>'; //.col-lg-6
    echo '</div>'; //.row

    $btn = '<input type="submit" class="btn btn-primary" value="'._generate.'" name="generate" onclick="self_disable(this);">';
    PopTable('footer', $btn);
    echo '</FORM>';
    echo '</div>';
    echo '</div>'; //.row
}


if ($_REQUEST['day_start'] && $_REQUEST['month_start'] && $_REQUEST['year_start']) {
    $conv_st_date=$_REQUEST['year_start'].'-'.$_REQUEST['month_start'].'-'.$_REQUEST['day_start'].' '.'00:00:00';
}

if ($_REQUEST['day_end'] && $_REQUEST['month_end'] && $_REQUEST['year_end']) {
    $conv_end_date=$_REQUEST['year_end'].'-'.$_REQUEST['month_end'].'-'.$_REQUEST['day_end'].' '.'23:59:59';
}

if($_REQUEST['modfunc']=='del')
{
     if (DeletePromptMod('Acess log', $qs)) {
         
        if(count($_REQUEST['log_arr'])>0)
        {
            $del_id=  implode(',', $_REQUEST['log_arr']);
            DBQuery("DELETE FROM login_records WHERE id in($del_id)");
            echo '<script>window.location.href="Modules.php?modname=tools/LogDetails.php"</script>';   
        }
        unset($_REQUEST['modfunc']);
        }
}

if ($_REQUEST['modfunc'] == 'generate') {

    if (isset($conv_st_date) && isset($conv_end_date)) {
        // Profile filter section (moved to top for both chart and logs)
        echo '<div class="row" style="margin: 15px 0;">';
        echo '<div class="col-md-12">';
        echo '<div class="panel panel-default">';
        // echo '<div class="panel-heading"><h5><i class="fa fa-filter"></i> Filtres</h5></div>';
        echo '<div class="panel-body">';
        echo '<form method="post" action="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=generate" id="filterForm">';
        
        // Hidden fields to preserve date range
        echo '<input type="hidden" name="day_start" value="' . $_REQUEST['day_start'] . '">';
        echo '<input type="hidden" name="month_start" value="' . $_REQUEST['month_start'] . '">';
        echo '<input type="hidden" name="year_start" value="' . $_REQUEST['year_start'] . '">';
        echo '<input type="hidden" name="day_end" value="' . $_REQUEST['day_end'] . '">';
        echo '<input type="hidden" name="month_end" value="' . $_REQUEST['month_end'] . '">';
        echo '<input type="hidden" name="year_end" value="' . $_REQUEST['year_end'] . '">';
        
        echo '<div class="row">';
        echo '<div class="col-md-4">';
        echo '<label for="profile_filter">Filtrer par profil:</label>';
        echo '<select name="profile_filter" id="profile_filter" class="form-control" onchange="this.form.submit();">';
        echo '<option value="">Tous les profils</option>';
        echo '<option value="Student"' . ($_REQUEST['profile_filter'] == 'Student' ? ' selected' : '') . '>Étudiant</option>';
        echo '<option value="parent"' . ($_REQUEST['profile_filter'] == 'parent' ? ' selected' : '') . '>Parent</option>';
        echo '<option value="teacher"' . ($_REQUEST['profile_filter'] == 'teacher' ? ' selected' : '') . '>Enseignant</option>';
        echo '<option value="admin"' . ($_REQUEST['profile_filter'] == 'admin' ? ' selected' : '') . '>Administrateur</option>';
        echo '<option value="Super Administrator"' . ($_REQUEST['profile_filter'] == 'Super Administrator' ? ' selected' : '') . '>Super Administrateur</option>';
        echo '</select>';
        echo '</div>';
        
        echo '<div class="col-md-4">';
        echo '<label for="status_filter">Filtrer par statut:</label>';
        echo '<select name="status_filter" id="status_filter" class="form-control" onchange="this.form.submit();">';
        echo '<option value="">Tous les statuts</option>';
        echo '<option value="Success"' . ($_REQUEST['status_filter'] == 'Success' ? ' selected' : '') . '>Succès</option>';
        echo '<option value="Failed"' . ($_REQUEST['status_filter'] == 'Failed' ? ' selected' : '') . '>Échec</option>';
        echo '</select>';
        echo '</div>';
        
        echo '<div class="col-md-4">';
        echo '<label>&nbsp;</label><br>';
        echo '<button type="button" class="btn btn-default" onclick="clearFilters();">Effacer les filtres</button>';
        echo '</div>';
        
        echo '</div>'; // .row
        echo '</form>';
        echo '</div>'; // .panel-body
        echo '</div>'; // .panel
        echo '</div>'; // .col-md-12
        echo '</div>'; // .row
        
        // JavaScript for clearing filters
        echo '<script>
        function clearFilters() {
            document.getElementById("profile_filter").value = "";
            document.getElementById("status_filter").value = "";
            document.getElementById("filterForm").submit();
        }
        </script>';

        // Build the common where clause with filters for both chart and logs
        $where_clause = "LOGIN_TIME >='" . $conv_st_date . "' AND LOGIN_TIME <='" . $conv_end_date . "' AND SCHOOL_ID=" . UserSchool();
        
        // Add profile filter
        if (!empty($_REQUEST['profile_filter'])) {
            $profile_filter = mysqli_real_escape_string($connection, $_REQUEST['profile_filter']);
            $where_clause .= " AND PROFILE = '$profile_filter'";
        }
        
        // Add status filter
        if (!empty($_REQUEST['status_filter'])) {
            $status_filter = mysqli_real_escape_string($connection, $_REQUEST['status_filter']);
            $where_clause .= " AND STATUS = '$status_filter'";
        }

        // Generate login statistics for graph with filters applied
        $stats_query = "SELECT DATE(LOGIN_TIME) as login_date, COUNT(*) as login_count 
                       FROM login_records 
                       WHERE $where_clause 
                       GROUP BY DATE(LOGIN_TIME) 
                       ORDER BY login_date ASC";
        
        $stats_RET = DBGet(DBQuery($stats_query));
        
        // Prepare data for chart
        $chart_dates = array();
        $chart_counts = array();
        
        if ($stats_RET) {
            foreach ($stats_RET as $stat) {
                $chart_dates[] = "'" . date('M d', strtotime($stat['LOGIN_DATE'])) . "'";
                $chart_counts[] = $stat['LOGIN_COUNT'];
            }
        }
        
        // Display filter summary
        if (!empty($_REQUEST['profile_filter']) || !empty($_REQUEST['status_filter'])) {
            echo '<div class="alert alert-info">';
            echo '<strong>Filtres actifs:</strong> ';
            $filters = array();
            if (!empty($_REQUEST['profile_filter'])) {
                $filters[] = 'Profil: ' . $_REQUEST['profile_filter'];
            }
            if (!empty($_REQUEST['status_filter'])) {
                $filters[] = 'Statut: ' . $_REQUEST['status_filter'];
            }
            echo implode(' | ', $filters);
            echo '</div>';
        }
        
        // Display the chart
        echo '<div class="row" style="margin-bottom: 20px;">';
        echo '<div class="col-md-12">';
        echo '<div class="panel panel-default">';
        
        // Update chart title based on filters
        $chart_title = 'Tableau d\'activité de connexion';
        if (!empty($_REQUEST['profile_filter'])) {
            $profile_names = array(
                'Student' => 'étudiants',
                'parent' => 'parents',
                'teacher' => 'enseignants',
                'admin' => 'administrateurs',
                'Super Administrator' => 'super administrateurs'
            );
            $chart_title .= ' - ' . ucfirst($profile_names[$_REQUEST['profile_filter']] ?? $_REQUEST['profile_filter']);
        }
        if (!empty($_REQUEST['status_filter'])) {
            $chart_title .= ' (' . $_REQUEST['status_filter'] . ')';
        }
        
        echo '<div class="panel-heading"><h4><i class="fa fa-bar-chart"></i> ' . $chart_title . '</h4></div>';
        echo '<div class="panel-body">';
        
        if (!empty($chart_dates) && !empty($chart_counts)) {
            echo '<canvas id="loginChart" width="800" height="300"></canvas>';
        } else {
            echo '<div class="alert alert-warning text-center">';
            echo '<strong>Aucune donnée à afficher</strong><br>';
            echo 'Aucun enregistrement de connexion trouvé pour la période sélectionnée avec les filtres appliqués.';
            echo '</div>';
        }
        
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        // Chart.js script (only if we have data)
        if (!empty($chart_dates) && !empty($chart_counts)) {
            echo '<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>';
            echo '<script>
            var ctx = document.getElementById("loginChart").getContext("2d");
            var loginChart = new Chart(ctx, {
                type: "line",
                data: {
                    labels: [' . implode(',', $chart_dates) . '],
                    datasets: [{
                        label: "Connexions par jour",
                        data: [' . implode(',', $chart_counts) . '],
                        borderColor: "#007bff",
                        backgroundColor: "rgba(0, 123, 255, 0.1)",
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: "#007bff",
                        pointBorderColor: "#fff",
                        pointBorderWidth: 2,
                        pointRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: "top"
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            },
                            grid: {
                                color: "#e9ecef"
                            }
                        },
                        x: {
                            grid: {
                                color: "#e9ecef"
                            }
                        }
                    },
                    elements: {
                        point: {
                            hoverRadius: 8
                        }
                    }
                }
            });
            </script>';
        }

        // Add tabs for switching between chart and detailed logs
        echo '<div class="row">';
        echo '<div class="col-md-12">';
        echo '<ul class="nav nav-tabs" role="tablist">';
        // echo '<li role="presentation" class="active"><a href="#chart-tab" aria-controls="chart-tab" role="tab" data-toggle="tab">Vue graphique</a></li>';
        echo '<li role="presentation"><a href="#details-tab" aria-controls="details-tab" role="tab" data-toggle="tab">Journaux détaillés</a></li>';
        echo '</ul>';
        
        echo '<div class="tab-content">';
        
        // Chart tab content
        echo '<div role="tabpanel" class="tab-pane active" id="chart-tab">';
        echo '<div style="padding: 20px;">';
        echo '<div class="alert alert-info">';
        echo '<strong>Résumé du tableau de connexion:</strong> ';
        if ($stats_RET && !empty($chart_counts)) {
            $total_logins = array_sum($chart_counts);
            $avg_logins = round($total_logins / count($chart_counts), 2);
            $max_logins = max($chart_counts);
            echo "Nombre total de connexions: $total_logins | Moyenne par jour: $avg_logins | Pointe: $max_logins connexions";
        } else {
            echo "Aucune donnée de connexion disponible pour la période sélectionnée avec les filtres appliqués.";
        }
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        // Detailed logs tab content
        echo '<div role="tabpanel" class="tab-pane" id="details-tab">';
        
        echo "<FORM action=Modules.php?modname=" . strip_tags(trim($_REQUEST[modname])) . "&modfunc=del method=POST >";
        
        // Use the same query with filters for detailed logs
        $logs_query = "SELECT ID, FIRST_NAME,CONCAT('<INPUT type=checkbox name=log_arr[] value=',ID,' checked >') AS CHECKBOX,USER_NAME,LAST_NAME,LOGIN_TIME,PROFILE,STAFF_ID,FAILLOG_COUNT,FAILLOG_TIME,USER_NAME,IF(IP_ADDRESS LIKE '::1','127.0.0.1',IP_ADDRESS) as IP_ADDRESS,STATUS FROM login_records WHERE $where_clause ORDER BY LOGIN_TIME DESC";
        
        $alllogs_RET = DBGet(DBQuery($logs_query), array('CHECKBOX' => '_makeChooseCheckbox'));

        foreach($alllogs_RET as $k => $v)
        {
            if($v['PROFILE']!='Student' && $v['PROFILE']!='parent')
            {
                $profile = DBGet(DBQuery('SELECT PROFILE_ID FROM staff WHERE STAFF_ID='.$v['STAFF_ID'].''));
                if($profile[1]['PROFILE_ID']==0)
                {
                    $alllogs_RET[$k]['PROFILE']='Super Administrator';   
                }
            }
        }
        
        echo '<div id="hidden_checkboxes" />';
        echo '</div>';
        $check_all_arr=array();
        foreach($alllogs_RET as $xy)
        {
            $check_all_arr[]=$xy['ID'];
        }
        $check_all_stu_list=implode(',',$check_all_arr);
        echo'<input type=hidden name=res_length id=res_length value=\''.count($check_all_arr).'\'>';
        echo'<input type=hidden name=all_stu_res id=all_stu_res value=\''.$check_all_stu_list.'\'>';
        echo'<input type=hidden name=checked_all id=checked_all value=false>';
        echo '<br>';
        echo'<input type=hidden name=res_len id=res_len value=\''.$check_all_stu_list.'\'>'; 

        // Display count of records found
        echo '<div class="alert alert-info">';
        echo '<strong>Résultats:</strong> ' . count($alllogs_RET) . ' enregistrements trouvés';
        echo '</div>';

        echo '<div class="panel panel-default">';
        ListOutput($alllogs_RET, array('CHECKBOX' => '</A><INPUT type=checkbox value=Y name=controller  onclick="checkAllDtMod(this,\'log_arr\');"><A>','LOGIN_TIME' => _loginTime,
         'USER_NAME' => _userName,
         'FIRST_NAME' =>_firstName,
         'LAST_NAME' => _lastName,
         'PROFILE' => _profile,
         'FAILLOG_COUNT' => _failureCount,
         'STATUS' => _status,
         'IP_ADDRESS' => _ipAddress,
        ), _loginRecord, _loginRecords, array(), array(), array('count' =>_firstName, 'save' =>true));
       
        if(count($alllogs_RET)>0) 
        echo '<div class="panel-footer text-center"><INPUT type=submit value="'._deleteLog.'" class="btn btn-primary" onclick="self_disable(this);"></div>';
        echo '</div>';
        echo "</FORM>";
        
        echo '</div>'; // End col-md-12
        echo '</div>'; // End row
            
    }
    if ((!isset($conv_st_date) || !isset($conv_end_date))) {
        echo '<center><font color="red"><b>'._youHaveToSelectDateFromTheDateRange.'</b></font></center>';
    }
}

function con_date($date) {
    $mother_date = $date;
    $year = substr($mother_date, 7);
    $temp_month = substr($mother_date, 3, 3);

    if ($temp_month == 'JAN')
        $month = '01';
    elseif ($temp_month == 'FEB')
        $month = '02';
    elseif ($temp_month == 'MAR')
        $month = '03';
    elseif ($temp_month == 'APR')
        $month = '04';
    elseif ($temp_month == 'MAY')
        $month = '05';
    elseif ($temp_month == 'JUN')
        $month = '06';
    elseif ($temp_month == 'JUL')
        $month = '07';
    elseif ($temp_month == 'AUG')
        $month = '08';
    elseif ($temp_month == 'SEP')
        $month = '09';
    elseif ($temp_month == 'OCT')
        $month = '10';
    elseif ($temp_month == 'NOV')
        $month = '11';
    elseif ($temp_month == 'DEC')
        $month = '12';

    $day = substr($mother_date, 0, 2);

    $select_date = $year . '-' . $month . '-' . $day . ' ' . '00:00:00';
    return $select_date;
}

function con_date_end($date) {
    $mother_date = $date;
    $year = substr($mother_date, 7);
    $temp_month = substr($mother_date, 3, 3);

    if ($temp_month == 'JAN')
        $month = '01';
    elseif ($temp_month == 'FEB')
        $month = '02';
    elseif ($temp_month == 'MAR')
        $month = '03';
    elseif ($temp_month == 'APR')
        $month = '04';
    elseif ($temp_month == 'MAY')
        $month = '05';
    elseif ($temp_month == 'JUN')
        $month = '06';
    elseif ($temp_month == 'JUL')
        $month = '07';
    elseif ($temp_month == 'AUG')
        $month = '08';
    elseif ($temp_month == 'SEP')
        $month = '09';
    elseif ($temp_month == 'OCT')
        $month = '10';
    elseif ($temp_month == 'NOV')
        $month = '11';
    elseif ($temp_month == 'DEC')
        $month = '12';

    $day = substr($mother_date, 0, 2);

    $select_date = $year . '-' . $month . '-' . $day . ' ' . '23:59:59';
    return $select_date;
}


function _makeChooseCheckbox($value, $title) {
    global $THIS_RET;
    return "<input  type=checkbox name=unused[$THIS_RET[ID]] value=" . $THIS_RET[ID] . "   id=$THIS_RET[ID] onClick='setHiddenCheckboxStudents(\"log_arr[$THIS_RET[ID]]\",this,$THIS_RET[ID]);' />";
}

?>