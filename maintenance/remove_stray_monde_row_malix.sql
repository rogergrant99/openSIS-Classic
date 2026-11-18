-- Remove the empty "Monde Contemporain" report card row that linked Malix Labrie (student 35,
-- Secondaire 2) to "Monde sec 5 - Nathalie Truchon" (course period 604), Étape 1 (MP 44).
-- He was never scheduled in that class; the row was created on 2026-11-06 by Input Final Grades
-- saving a student who wasn't on the current course period's roster (fixed in InputFinalGrades.php).
--
-- Run:  mysql opensis < remove_stray_monde_row_malix.sql
-- Undo: INSERT INTO student_report_card_grades SELECT * FROM bak_srcg_15930_malix_monde;

CREATE TABLE IF NOT EXISTS bak_srcg_15930_malix_monde AS
    SELECT * FROM student_report_card_grades WHERE id = 15930;

DELETE FROM student_report_card_grades
 WHERE id = 15930
   AND student_id = 35
   AND course_period_id = 604
   AND marking_period_id = 44
   AND grade_letter IS NULL
   AND grade_percent IS NULL
   AND NOT EXISTS (SELECT 1 FROM schedule ss WHERE ss.student_id = 35 AND ss.course_period_id = 604);

SELECT ROW_COUNT() AS deleted;
SELECT COUNT(*) AS backup_rows FROM bak_srcg_15930_malix_monde;
SELECT COUNT(*) AS remaining FROM student_report_card_grades WHERE student_id = 35 AND course_period_id = 604;
