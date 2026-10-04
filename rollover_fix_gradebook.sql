-- ============================================================
-- Rollover gap-fix: gradebook setup for the new school year
--
-- Run AFTER the assignment-type COURSE_ID repair (fix.sql, "Use when
-- auto type assigments are wrong"). Take a backup first.
-- Set @syear / @school below, then run the whole file. Every step
-- only touches what is missing or wrong, so it is safe to re-run.
--
-- What the rollover routine (CadoTeacherFix in modules/tools/Reports.php)
-- got wrong, fixed in code on 2026-10-04:
--   1. Breakoff points ("Points d'arrêt") were written against
--      hardcoded report_card_grades IDs 65-70, which are LAST year's
--      grade IDs. The new year's scale has new IDs, so the letter-grade
--      lookup finds no breakoff and returns nothing.
--   2. New courses (no rollover_id) got their three 1ère communication
--      assignments in the full-year marking period and under the
--      course-named type, instead of the 1ère communication period
--      and type, so they never show on the 1ère communication bulletin.
--   3. Rolled-over courses whose old course had no 1ère communication
--      type got none.
--   4. Course periods created after the routine ran got no gradebook
--      config, no assignment types and no 1ère communication at all.
-- ============================================================

SET @syear  = 2026;
SET @school = 1;

SET @comm_mp = (SELECT MARKING_PERIOD_ID FROM school_quarters WHERE SYEAR=@syear AND SCHOOL_ID=@school AND TITLE='1ère communication');
SET @year_mp = (SELECT MARKING_PERIOD_ID FROM school_years    WHERE SYEAR=@syear AND SCHOOL_ID=@school);
SET @e1_mp   = (SELECT MARKING_PERIOD_ID FROM school_quarters WHERE SYEAR=@syear AND SCHOOL_ID=@school AND TITLE='Étape 1');
SET @e2_mp   = (SELECT MARKING_PERIOD_ID FROM school_quarters WHERE SYEAR=@syear AND SCHOOL_ID=@school AND TITLE='Étape 2');
SET @e3_mp   = (SELECT MARKING_PERIOD_ID FROM school_quarters WHERE SYEAR=@syear AND SCHOOL_ID=@school AND TITLE='Étape 3');
SET @comm_start = (SELECT POST_START_DATE FROM school_quarters WHERE MARKING_PERIOD_ID=@comm_mp);
SET @comm_end   = (SELECT POST_END_DATE   FROM school_quarters WHERE MARKING_PERIOD_ID=@comm_mp);

START TRANSACTION;

-- ------------------------------------------------------------
-- 1. Breakoff points keyed to last year's grade IDs -> remap to
--    the course period's own scale, matching grades by SORT_ORDER.
--    Keeps any value the teacher changed.
-- ------------------------------------------------------------
UPDATE program_user_config p
JOIN course_periods cp
  ON cp.SYEAR=@syear AND cp.SCHOOL_ID=@school
 AND p.TITLE LIKE CONCAT(cp.COURSE_PERIOD_ID,'-%')
 AND p.VALUE LIKE CONCAT('%\_',cp.COURSE_PERIOD_ID)
JOIN report_card_grades old_g
  ON old_g.ID = SUBSTRING_INDEX(p.TITLE,'-',-1)
 AND old_g.SYEAR <> cp.SYEAR
JOIN report_card_grades new_g
  ON new_g.GRADE_SCALE_ID=cp.GRADE_SCALE_ID AND new_g.SYEAR=cp.SYEAR
 AND new_g.SORT_ORDER=old_g.SORT_ORDER
SET p.TITLE = CONCAT(cp.COURSE_PERIOD_ID,'-',new_g.ID)
WHERE p.PROGRAM='Gradebook'
  AND p.TITLE REGEXP '^[0-9]+-[0-9]+$';

-- ------------------------------------------------------------
-- 2. Course periods with no gradebook config at all -> defaults
--    (same values as the rollover routine).
-- ------------------------------------------------------------
CREATE TEMPORARY TABLE fix_cp_noconf AS
SELECT cp.COURSE_PERIOD_ID, cp.TEACHER_ID, cp.SCHOOL_ID, cp.GRADE_SCALE_ID
FROM course_periods cp
WHERE cp.SYEAR=@syear AND cp.SCHOOL_ID=@school AND cp.TEACHER_ID IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM program_user_config p
                  WHERE p.PROGRAM='Gradebook' AND p.VALUE LIKE CONCAT('%\_',cp.COURSE_PERIOD_ID));

INSERT INTO program_user_config (user_id,school_id,program,title,value,last_updated,updated_by)
SELECT f.TEACHER_ID, f.SCHOOL_ID, 'Gradebook', d.title, CONCAT(d.val,'_',f.COURSE_PERIOD_ID), NOW(), f.TEACHER_ID
FROM fix_cp_noconf f
JOIN (          SELECT 'ROUNDING' title,'NORMAL' val
      UNION ALL SELECT 'ASSIGNMENT_SORTING','ASSIGNMENT_ID'
      UNION ALL SELECT 'WEIGHT','Y'
      UNION ALL SELECT 'ANOMALOUS_MAX','100'
      UNION ALL SELECT 'LATENCY','0'
      UNION ALL SELECT CONCAT('Q-',@e1_mp),'100'
      UNION ALL SELECT CONCAT('Q-',@e2_mp),'100'
      UNION ALL SELECT CONCAT('Q-',@comm_mp),'100'
      UNION ALL SELECT CONCAT('Q-',@e3_mp),'100'
      UNION ALL SELECT CONCAT('FY-',@e1_mp),'20'
      UNION ALL SELECT CONCAT('FY-',@e2_mp),'20'
      UNION ALL SELECT CONCAT('FY-',@comm_mp),'0'
      UNION ALL SELECT CONCAT('FY-',@e3_mp),'60'
      UNION ALL SELECT CONCAT('FY-E',@year_mp),'0') d;

INSERT INTO program_user_config (user_id,school_id,program,title,value,last_updated,updated_by)
SELECT f.TEACHER_ID, f.SCHOOL_ID, 'Gradebook', 'COMMENT_A', NULL, NOW(), f.TEACHER_ID
FROM fix_cp_noconf f;

INSERT INTO program_user_config (user_id,school_id,program,title,value,last_updated,updated_by)
SELECT f.TEACHER_ID, f.SCHOOL_ID, 'Gradebook', CONCAT(f.COURSE_PERIOD_ID,'-',g.ID), CONCAT(FLOOR(g.BREAK_OFF),'_',f.COURSE_PERIOD_ID), NOW(), f.TEACHER_ID
FROM fix_cp_noconf f
JOIN report_card_grades g ON g.GRADE_SCALE_ID=f.GRADE_SCALE_ID AND g.SYEAR=@syear AND g.SCHOOL_ID=f.SCHOOL_ID
WHERE g.BREAK_OFF IS NOT NULL;

-- ------------------------------------------------------------
-- 3. Rolled-over course periods that got no assignment types at all
--    -> copy last year's types (with this year's COURSE_ID).
-- ------------------------------------------------------------
INSERT INTO gradebook_assignment_types (STAFF_ID,COURSE_PERIOD_ID,COURSE_ID,TITLE,FINAL_GRADE_PERCENT)
SELECT cdnew.TEACHER_ID, cdnew.COURSE_PERIOD_ID, cdnew.COURSE_ID, t.TITLE, t.FINAL_GRADE_PERCENT
FROM course_details cdnew
JOIN course_details cdold ON cdold.COURSE_ID=cdnew.ROLLOVER_ID
JOIN gradebook_assignment_types t ON t.COURSE_PERIOD_ID=cdold.COURSE_PERIOD_ID
WHERE cdnew.SYEAR=@syear AND cdnew.SCHOOL_ID=@school
  AND NOT EXISTS (SELECT 1 FROM gradebook_assignment_types x WHERE x.COURSE_PERIOD_ID=cdnew.COURSE_PERIOD_ID);

-- ------------------------------------------------------------
-- 4. Course periods with no 1ère communication type -> create it.
-- ------------------------------------------------------------
INSERT INTO gradebook_assignment_types (STAFF_ID,COURSE_PERIOD_ID,COURSE_ID,TITLE,FINAL_GRADE_PERCENT)
SELECT cp.TEACHER_ID, cp.COURSE_PERIOD_ID, cp.COURSE_ID, '1ère communication', NULL
FROM course_periods cp
WHERE cp.SYEAR=@syear AND cp.SCHOOL_ID=@school AND cp.TEACHER_ID IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM gradebook_assignment_types t
                  WHERE t.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID AND t.TITLE='1ère communication');

-- ------------------------------------------------------------
-- 5. 1ère communication assignments created in the wrong marking
--    period / under the wrong type (new courses) -> move them.
--    Grades already entered on them are kept.
-- ------------------------------------------------------------
UPDATE gradebook_assignments a
JOIN course_periods cp ON cp.COURSE_PERIOD_ID=a.COURSE_PERIOD_ID AND cp.SYEAR=@syear AND cp.SCHOOL_ID=@school
JOIN gradebook_assignment_types ct ON ct.COURSE_PERIOD_ID=a.COURSE_PERIOD_ID AND ct.TITLE='1ère communication'
SET a.MARKING_PERIOD_ID=@comm_mp, a.ASSIGNMENT_TYPE_ID=ct.ASSIGNMENT_TYPE_ID
WHERE a.MARKING_PERIOD_ID=@year_mp
  AND a.TITLE IN ('En voie de réussite','Complète et remet ses travaux','Attitude et comportement')
  AND a.ASSIGNMENT_TYPE_ID<>ct.ASSIGNMENT_TYPE_ID
  AND NOT EXISTS (SELECT 1 FROM (SELECT COURSE_PERIOD_ID FROM gradebook_assignments WHERE MARKING_PERIOD_ID=@comm_mp) y
                  WHERE y.COURSE_PERIOD_ID=a.COURSE_PERIOD_ID);

-- ------------------------------------------------------------
-- 6. Course periods still without 1ère communication assignments
--    -> create the three standard ones.
-- ------------------------------------------------------------
INSERT INTO gradebook_assignments (staff_id,marking_period_id,assignment_type_id,course_period_id,title,due_date,assigned_date,points,ASSIGNMENT_WEIGHT,ungraded,last_updated)
SELECT cp.TEACHER_ID, @comm_mp, ct.ASSIGNMENT_TYPE_ID, cp.COURSE_PERIOD_ID, d.title, @comm_end, @comm_start, 100, d.weight, 1, CURDATE()
FROM course_periods cp
JOIN gradebook_assignment_types ct ON ct.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID AND ct.TITLE='1ère communication'
JOIN (          SELECT 'En voie de réussite' title, 33 weight
      UNION ALL SELECT 'Complète et remet ses travaux', 33
      UNION ALL SELECT 'Attitude et comportement', 34) d
WHERE cp.SYEAR=@syear AND cp.SCHOOL_ID=@school AND cp.TEACHER_ID IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM gradebook_assignments a
                  WHERE a.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID AND a.MARKING_PERIOD_ID=@comm_mp);

DROP TEMPORARY TABLE fix_cp_noconf;

COMMIT;
