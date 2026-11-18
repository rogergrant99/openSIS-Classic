-- Repair attendance_period rows corrupted by the old TakeAttendance.php bug:
--   ... ON DUPLICATE KEY UPDATE PERIOD_ID = <course_period_id>
-- When a student already had a row for the same date + period under another course
-- period, the old code overwrote that row's PERIOD_ID with the course period ID
-- (e.g. 590) instead of leaving it on the real period (e.g. 73).
--
-- What this script does:
--   * finds rows whose PERIOD_ID is not a real school period but is a course period ID
--   * works out the real period: the period shared by the row's own course period
--     and the course period whose ID ended up in PERIOD_ID
--   * moves the row back to that period, or deletes it if a row for that
--     student/date/period already exists (newer entry, which wins)
--   * leaves anything ambiguous untouched and lists it for manual review
--
-- Every affected row is first copied into bak_attn_fix_rows so it can be restored.
-- Drop the bak_attn_fix_* tables once you're satisfied.

-- ---------------------------------------------------------------------------
-- 1. Find corrupted rows + backup (DDL, so done before the transaction)
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS bak_attn_fix_rows, bak_attn_fix_map;

CREATE TABLE bak_attn_fix_rows AS
SELECT ap.* FROM attendance_period ap
WHERE NOT EXISTS (SELECT 1 FROM school_periods sp WHERE sp.period_id = ap.period_id)
  AND EXISTS (SELECT 1 FROM course_periods cp WHERE cp.course_period_id = ap.period_id);

-- Real period = a period both course periods meet in on that weekday
-- (course_period_var.days uses U M T W H F S; DAYOFWEEK: 1 = Sunday)
CREATE TABLE bak_attn_fix_map AS
SELECT r.student_id, r.school_date, r.period_id AS bad_period_id, r.course_period_id,
       MIN(v1.period_id) AS correct_period_id,
       COUNT(DISTINCT v1.period_id) AS n_candidates
FROM bak_attn_fix_rows r
JOIN course_period_var v1 ON v1.course_period_id = r.course_period_id
 AND (v1.days IS NULL OR v1.days = '' OR LOCATE(SUBSTRING('UMTWHFS', DAYOFWEEK(r.school_date), 1), v1.days) > 0)
JOIN course_period_var v2 ON v2.course_period_id = r.period_id AND v2.period_id = v1.period_id
 AND (v2.days IS NULL OR v2.days = '' OR LOCATE(SUBSTRING('UMTWHFS', DAYOFWEEK(r.school_date), 1), v2.days) > 0)
GROUP BY r.student_id, r.school_date, r.period_id, r.course_period_id;

-- Review: everything found, with the planned action
SELECT r.student_id, r.school_date, r.period_id AS bad_period_id, r.course_period_id,
       r.attendance_code, m.correct_period_id, m.n_candidates,
       CASE
         WHEN m.student_id IS NULL OR m.n_candidates <> 1 THEN 'MANUAL REVIEW (left as is)'
         WHEN EXISTS (SELECT 1 FROM attendance_period x
                      WHERE x.student_id = r.student_id AND x.school_date = r.school_date
                        AND x.period_id = m.correct_period_id) THEN 'delete (real period already has a row)'
         ELSE 'move to correct period'
       END AS planned_action
FROM bak_attn_fix_rows r
LEFT JOIN bak_attn_fix_map m
  ON m.student_id = r.student_id AND m.school_date = r.school_date AND m.bad_period_id = r.period_id
ORDER BY r.school_date, r.student_id;

-- ---------------------------------------------------------------------------
-- 2. Repair
-- ---------------------------------------------------------------------------
START TRANSACTION;

-- Move back to the real period; IGNORE skips rows whose target key already exists
UPDATE IGNORE attendance_period ap
JOIN bak_attn_fix_map m
  ON m.student_id = ap.student_id AND m.school_date = ap.school_date AND m.bad_period_id = ap.period_id
SET ap.period_id = m.correct_period_id
WHERE m.n_candidates = 1;

-- Whatever is still on a bad period with a resolved target collided with an existing row: remove it
DELETE ap FROM attendance_period ap
JOIN bak_attn_fix_map m
  ON m.student_id = ap.student_id AND m.school_date = ap.school_date AND m.bad_period_id = ap.period_id
WHERE m.n_candidates = 1;

-- Should only list the MANUAL REVIEW rows from the review query above
SELECT ap.* FROM attendance_period ap
WHERE NOT EXISTS (SELECT 1 FROM school_periods sp WHERE sp.period_id = ap.period_id)
  AND EXISTS (SELECT 1 FROM course_periods cp WHERE cp.course_period_id = ap.period_id);

COMMIT;
-- ROLLBACK;  -- use instead of COMMIT if the results above look wrong

-- ---------------------------------------------------------------------------
-- 3. Afterwards: recalculate daily attendance for the affected dates
--    (Attendance > Utilities > Fix Daily Attendance / FixDailyAttendance.php),
--    covering MIN..MAX of:
-- ---------------------------------------------------------------------------
SELECT MIN(school_date) AS from_date, MAX(school_date) AS to_date, COUNT(*) AS rows_found
FROM bak_attn_fix_rows;

-- Restore, if ever needed (puts moved rows back, then re-adds deleted ones):
--   UPDATE IGNORE attendance_period ap JOIN bak_attn_fix_map m
--     ON m.student_id = ap.student_id AND m.school_date = ap.school_date
--    AND m.correct_period_id = ap.period_id AND m.course_period_id = ap.course_period_id
--   SET ap.period_id = m.bad_period_id WHERE m.n_candidates = 1;
--   INSERT IGNORE INTO attendance_period SELECT * FROM bak_attn_fix_rows;
