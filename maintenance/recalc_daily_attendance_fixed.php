<?php
// Recalculate attendance_day for the student/date pairs touched by fix_attendance_period_ids.sql
// (same as Fix Daily Attendance, limited to those pairs).
// Usage, from the openSIS root:  php maintenance/recalc_daily_attendance_fixed.php
// Back up first: CREATE TABLE bak_attn_day_before_recalc AS SELECT * FROM attendance_day
//   WHERE (student_id, school_date) IN (SELECT student_id, school_date FROM bak_attn_fix_rows);
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
chdir(dirname(__DIR__));
error_reporting(E_ERROR);
$_SERVER['PHP_SELF'] = '/index.php';           // skip Warehouse's login redirect
$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (cli)';      // ConnectionClass only connects for browsers
$_SERVER['SERVER_SOFTWARE'] = $_SERVER['SERVER_SOFTWARE'] ?? 'cli';
ob_start();
include 'Warehouse.php';
$_SESSION['language'] = 'en'; include 'lang/language.php';
ob_end_clean();

$pairs = DBGet(DBQuery("SELECT DISTINCT r.student_id, r.school_date, sy.syear, sy.school_id
    FROM bak_attn_fix_rows r
    JOIN school_years sy ON r.school_date BETWEEN sy.start_date AND sy.end_date
    ORDER BY r.school_date, r.student_id"));

$before = DBGet(DBQuery("SELECT COUNT(*) N, SUM(STATE_VALUE) SV FROM attendance_day d
    WHERE (d.student_id, d.school_date) IN (SELECT student_id, school_date FROM bak_attn_fix_rows)"));
$n = 0;
$cur_year = null;
foreach ($pairs as $p) {
    if ($p['SYEAR'] !== $cur_year) {      // marking-period caches are per school year
        unset($_openSIS['GetAllMP'], $_openSIS['GetMP'], $_openSIS['GetChildrenMP'], $_openSIS['GetCurrentMP']);
        $cur_year = $p['SYEAR'];
    }
    $_SESSION['UserSyear'] = $p['SYEAR'];
    $_SESSION['UserSchool'] = $p['SCHOOL_ID'];
    UpdateAttendanceDaily($p['STUDENT_ID'], $p['SCHOOL_DATE']);
    $n++;
}
$after = DBGet(DBQuery("SELECT COUNT(*) N, SUM(STATE_VALUE) SV FROM attendance_day d
    WHERE (d.student_id, d.school_date) IN (SELECT student_id, school_date FROM bak_attn_fix_rows)"));
echo "pairs recalculated: $n\n";
echo "attendance_day rows for those pairs: before {$before[1]['N']} (sum state {$before[1]['SV']}), after {$after[1]['N']} (sum state {$after[1]['SV']})\n";
