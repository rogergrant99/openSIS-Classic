-- Compare current attendance totals (2025, 2026) with the snapshot in bak_attn_totals_before.
-- Run after fix_attendance_period_ids.sql and Fix Daily Attendance.

DROP TEMPORARY TABLE IF EXISTS attn_totals_after;
CREATE TEMPORARY TABLE attn_totals_after AS
SELECT t.syear, t.student_id, t.period_rows, t.present_periods, t.absent_periods, t.late_periods,
       t.other_code_periods, t.bad_period_rows,
       COALESCE(d.days_recorded, 0) AS days_recorded,
       COALESCE(d.days_present_value, 0) AS days_present_value,
       COALESCE(d.full_days_absent, 0) AS full_days_absent,
       COALESCE(d.half_days, 0) AS half_days
FROM (
  SELECT sy.syear, ap.student_id, COUNT(*) AS period_rows,
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
  SELECT syear, student_id, COUNT(*) AS days_recorded, SUM(state_value) AS days_present_value,
         SUM(state_value = 0) AS full_days_absent, SUM(state_value = 0.5) AS half_days
  FROM attendance_day WHERE syear IN (2025, 2026) GROUP BY syear, student_id
) d ON d.syear = t.syear AND d.student_id = t.student_id;

-- Totals per year: before vs after
SELECT 'before' AS which, syear, COUNT(*) students, SUM(period_rows) period_rows, SUM(present_periods) present,
       SUM(absent_periods) absent, SUM(late_periods) late, SUM(bad_period_rows) bad_rows,
       SUM(days_recorded) days, SUM(days_present_value) days_present, SUM(full_days_absent) days_absent
FROM bak_attn_totals_before GROUP BY syear
UNION ALL
SELECT 'after', syear, COUNT(*), SUM(period_rows), SUM(present_periods), SUM(absent_periods),
       SUM(late_periods), SUM(bad_period_rows), SUM(days_recorded), SUM(days_present_value), SUM(full_days_absent)
FROM attn_totals_after GROUP BY syear
ORDER BY syear, which DESC;

-- Students whose numbers changed (deleted duplicate rows show up here)
SELECT b.syear, b.student_id, b.student_name,
       b.period_rows AS rows_before, a.period_rows AS rows_after,
       b.absent_periods AS abs_before, a.absent_periods AS abs_after,
       b.bad_period_rows AS bad_before, a.bad_period_rows AS bad_after,
       b.days_present_value AS days_pres_before, a.days_present_value AS days_pres_after,
       b.full_days_absent AS days_abs_before, a.full_days_absent AS days_abs_after
FROM bak_attn_totals_before b
LEFT JOIN attn_totals_after a ON a.syear = b.syear AND a.student_id = b.student_id
WHERE a.student_id IS NULL
   OR (b.period_rows, b.present_periods, b.absent_periods, b.late_periods, b.bad_period_rows,
       b.days_recorded, b.days_present_value, b.full_days_absent)
   <> (a.period_rows, a.present_periods, a.absent_periods, a.late_periods, a.bad_period_rows,
       a.days_recorded, a.days_present_value, a.full_days_absent)
ORDER BY b.syear, b.student_name;
