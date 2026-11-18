-- Snapshot per-student attendance totals for 2025 and 2026 into bak_attn_totals_before.
-- Run BEFORE fix_attendance_period_ids.sql; compare afterwards with compare_attendance_totals.sql.

DROP TABLE IF EXISTS bak_attn_totals_before;
CREATE TABLE bak_attn_totals_before AS
SELECT t.syear, t.student_id,
       CONCAT(s.last_name, ', ', s.first_name) AS student_name,
       t.period_rows, t.present_periods, t.absent_periods, t.late_periods, t.other_code_periods,
       t.bad_period_rows,
       COALESCE(d.days_recorded, 0) AS days_recorded,
       COALESCE(d.days_present_value, 0) AS days_present_value,
       COALESCE(d.full_days_absent, 0) AS full_days_absent,
       COALESCE(d.half_days, 0) AS half_days,
       NOW() AS snapshot_at
FROM (
  SELECT sy.syear, ap.student_id,
         COUNT(*) AS period_rows,
         SUM(ac.state_code = 'P') AS present_periods,
         SUM(ac.state_code = 'A') AS absent_periods,
         SUM(ac.state_code = 'H') AS late_periods,
         SUM(ac.id IS NULL OR ac.state_code NOT IN ('P','A','H')) AS other_code_periods,
         SUM(NOT EXISTS (SELECT 1 FROM school_periods sp WHERE sp.period_id = ap.period_id)) AS bad_period_rows
  FROM attendance_period ap
  JOIN school_years sy ON sy.school_id = 1 AND ap.school_date BETWEEN sy.start_date AND sy.end_date
  LEFT JOIN attendance_codes ac ON ac.id = ap.attendance_code
  WHERE sy.syear IN (2025, 2026)
  GROUP BY sy.syear, ap.student_id
) t
LEFT JOIN (
  SELECT syear, student_id, COUNT(*) AS days_recorded,
         SUM(state_value) AS days_present_value,
         SUM(state_value = 0) AS full_days_absent,
         SUM(state_value = 0.5) AS half_days
  FROM attendance_day WHERE syear IN (2025, 2026)
  GROUP BY syear, student_id
) d ON d.syear = t.syear AND d.student_id = t.student_id
LEFT JOIN students s ON s.student_id = t.student_id;

SELECT syear, COUNT(*) students, SUM(period_rows) period_rows, SUM(present_periods) present,
       SUM(absent_periods) absent, SUM(late_periods) late, SUM(bad_period_rows) bad_period_rows,
       SUM(days_recorded) days, SUM(days_present_value) days_present, SUM(full_days_absent) days_absent
FROM bak_attn_totals_before GROUP BY syear;

-- Export to CSV (run separately; adjust credentials):
--   mysql -B -N opensis_db -e "SELECT 'syear,student_id,student_name,period_rows,present_periods,absent_periods,late_periods,other_code_periods,bad_period_rows,days_recorded,days_present_value,full_days_absent,half_days,snapshot_at' UNION ALL SELECT * FROM (SELECT CONCAT_WS(',',syear,student_id,CONCAT('\"',REPLACE(student_name,'\"','\"\"'),'\"'),period_rows,present_periods,absent_periods,late_periods,other_code_periods,bad_period_rows,days_recorded,days_present_value,full_days_absent,half_days,snapshot_at) FROM bak_attn_totals_before ORDER BY syear,student_name) x" > attendance_totals_before_fix.csv
