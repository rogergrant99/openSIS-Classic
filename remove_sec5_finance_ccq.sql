-- Remove "Éducation financière" and "CCQ" (Culture et citoyenneté québécoise)
-- from Secondaire 5 (grade_level 12) for 2026, with all dependent data:
-- course periods, schedules, attendance, gradebook types/assignments/grades,
-- report card grades/comments and planification.
--
-- Every deleted row is first copied into a bak_sec5_* table so it can be restored.
-- Drop the bak_sec5_* tables once you're satisfied.

SET @syear = 2026;
SET @grade = 12; -- Secondaire 5

-- ---------------------------------------------------------------------------
-- 1. Resolve IDs (by year/grade/title, so it works whatever the IDs are)
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS bak_sec5_ids_c, bak_sec5_ids_cp, bak_sec5_ids_asg;

CREATE TABLE bak_sec5_ids_c AS
SELECT course_id FROM courses
WHERE syear = @syear AND grade_level = @grade
  AND (title LIKE '%ducation financi%' OR title = 'Culture et citoyenneté québécoise');

CREATE TABLE bak_sec5_ids_cp AS
SELECT course_period_id FROM course_periods
WHERE course_id IN (SELECT course_id FROM bak_sec5_ids_c);

CREATE TABLE bak_sec5_ids_asg AS
SELECT assignment_id FROM gradebook_assignments
WHERE course_id IN (SELECT course_id FROM bak_sec5_ids_c)
   OR course_period_id IN (SELECT course_period_id FROM bak_sec5_ids_cp);

-- Check this shows exactly the two courses before going further
SELECT c.course_id, c.title, c.grade_level, c.syear, cp.course_period_id, cp.title AS cp_title
FROM courses c LEFT JOIN course_periods cp ON cp.course_id = c.course_id
WHERE c.course_id IN (SELECT course_id FROM bak_sec5_ids_c);

-- ---------------------------------------------------------------------------
-- 2. Backups (DDL, so done before the transaction)
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS bak_sec5_courses, bak_sec5_course_periods, bak_sec5_course_period_var,
  bak_sec5_schedule, bak_sec5_schedule_requests, bak_sec5_attendance_period,
  bak_sec5_attendance_completed, bak_sec5_missing_attendance, bak_sec5_eligibility,
  bak_sec5_gradebook_grades, bak_sec5_gradebook_assignments, bak_sec5_gradebook_assignment_types,
  bak_sec5_student_report_card_grades, bak_sec5_student_report_card_comments,
  bak_sec5_report_card_comments, bak_sec5_planification;

CREATE TABLE bak_sec5_courses AS SELECT * FROM courses WHERE course_id IN (SELECT course_id FROM bak_sec5_ids_c);
CREATE TABLE bak_sec5_course_periods AS SELECT * FROM course_periods WHERE course_period_id IN (SELECT course_period_id FROM bak_sec5_ids_cp);
CREATE TABLE bak_sec5_course_period_var AS SELECT * FROM course_period_var WHERE course_period_id IN (SELECT course_period_id FROM bak_sec5_ids_cp);
CREATE TABLE bak_sec5_schedule AS SELECT * FROM schedule WHERE course_period_id IN (SELECT course_period_id FROM bak_sec5_ids_cp) OR course_id IN (SELECT course_id FROM bak_sec5_ids_c);
CREATE TABLE bak_sec5_schedule_requests AS SELECT * FROM schedule_requests WHERE course_id IN (SELECT course_id FROM bak_sec5_ids_c);
CREATE TABLE bak_sec5_attendance_period AS SELECT * FROM attendance_period WHERE course_period_id IN (SELECT course_period_id FROM bak_sec5_ids_cp);
CREATE TABLE bak_sec5_attendance_completed AS SELECT * FROM attendance_completed WHERE course_period_id IN (SELECT course_period_id FROM bak_sec5_ids_cp);
CREATE TABLE bak_sec5_missing_attendance AS SELECT * FROM missing_attendance WHERE course_period_id IN (SELECT course_period_id FROM bak_sec5_ids_cp);
CREATE TABLE bak_sec5_eligibility AS SELECT * FROM eligibility WHERE course_period_id IN (SELECT course_period_id FROM bak_sec5_ids_cp);
CREATE TABLE bak_sec5_gradebook_grades AS SELECT * FROM gradebook_grades WHERE course_period_id IN (SELECT course_period_id FROM bak_sec5_ids_cp) OR assignment_id IN (SELECT assignment_id FROM bak_sec5_ids_asg);
CREATE TABLE bak_sec5_gradebook_assignments AS SELECT * FROM gradebook_assignments WHERE assignment_id IN (SELECT assignment_id FROM bak_sec5_ids_asg);
CREATE TABLE bak_sec5_gradebook_assignment_types AS SELECT * FROM gradebook_assignment_types WHERE course_period_id IN (SELECT course_period_id FROM bak_sec5_ids_cp) OR course_id IN (SELECT course_id FROM bak_sec5_ids_c);
CREATE TABLE bak_sec5_student_report_card_grades AS SELECT * FROM student_report_card_grades WHERE course_period_id IN (SELECT course_period_id FROM bak_sec5_ids_cp);
CREATE TABLE bak_sec5_student_report_card_comments AS SELECT * FROM student_report_card_comments WHERE course_period_id IN (SELECT course_period_id FROM bak_sec5_ids_cp);
CREATE TABLE bak_sec5_report_card_comments AS SELECT * FROM report_card_comments WHERE course_id IN (SELECT course_id FROM bak_sec5_ids_c);
CREATE TABLE bak_sec5_planification AS SELECT * FROM planification WHERE course_id IN (SELECT course_id FROM bak_sec5_ids_c);

-- ---------------------------------------------------------------------------
-- 3. Delete
-- ---------------------------------------------------------------------------
START TRANSACTION;

DELETE FROM gradebook_grades WHERE course_period_id IN (SELECT course_period_id FROM bak_sec5_ids_cp) OR assignment_id IN (SELECT assignment_id FROM bak_sec5_ids_asg);
DELETE FROM gradebook_assignments WHERE assignment_id IN (SELECT assignment_id FROM bak_sec5_ids_asg);
DELETE FROM gradebook_assignment_types WHERE course_period_id IN (SELECT course_period_id FROM bak_sec5_ids_cp) OR course_id IN (SELECT course_id FROM bak_sec5_ids_c);
DELETE FROM student_report_card_grades WHERE course_period_id IN (SELECT course_period_id FROM bak_sec5_ids_cp);
DELETE FROM student_report_card_comments WHERE course_period_id IN (SELECT course_period_id FROM bak_sec5_ids_cp);
DELETE FROM report_card_comments WHERE course_id IN (SELECT course_id FROM bak_sec5_ids_c);
DELETE FROM attendance_period WHERE course_period_id IN (SELECT course_period_id FROM bak_sec5_ids_cp);
DELETE FROM attendance_completed WHERE course_period_id IN (SELECT course_period_id FROM bak_sec5_ids_cp);
DELETE FROM missing_attendance WHERE course_period_id IN (SELECT course_period_id FROM bak_sec5_ids_cp);
DELETE FROM eligibility WHERE course_period_id IN (SELECT course_period_id FROM bak_sec5_ids_cp);
DELETE FROM schedule WHERE course_period_id IN (SELECT course_period_id FROM bak_sec5_ids_cp) OR course_id IN (SELECT course_id FROM bak_sec5_ids_c);
DELETE FROM schedule_requests WHERE course_id IN (SELECT course_id FROM bak_sec5_ids_c);
DELETE FROM planification WHERE course_id IN (SELECT course_id FROM bak_sec5_ids_c);
DELETE FROM course_period_var WHERE course_period_id IN (SELECT course_period_id FROM bak_sec5_ids_cp);
DELETE FROM course_periods WHERE course_period_id IN (SELECT course_period_id FROM bak_sec5_ids_cp);
DELETE FROM courses WHERE course_id IN (SELECT course_id FROM bak_sec5_ids_c);

COMMIT;

-- Should all be 0
SELECT
  (SELECT COUNT(*) FROM courses WHERE course_id IN (SELECT course_id FROM bak_sec5_ids_c)) AS courses_left,
  (SELECT COUNT(*) FROM course_periods WHERE course_period_id IN (SELECT course_period_id FROM bak_sec5_ids_cp)) AS cps_left,
  (SELECT COUNT(*) FROM schedule WHERE course_period_id IN (SELECT course_period_id FROM bak_sec5_ids_cp)) AS schedule_left,
  (SELECT COUNT(*) FROM gradebook_grades WHERE course_period_id IN (SELECT course_period_id FROM bak_sec5_ids_cp)) AS grades_left;
