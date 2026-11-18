-- ============================================================
-- Rollover gap-fix: enroll the students skipped by rollover
--
-- Root cause: rollover (RolloverShadow.php, case 'student_enrollment')
-- only creates a new-year enrollment row for a student if their OLD
-- row's NEXT_SCHOOL is set to the current school, 0, or another
-- school. Students whose enrollment was never finished have
-- NEXT_SCHOOL (and CALENDAR_ID) left NULL, so none of rollover's
-- three INSERT branches match them -- they're silently skipped.
-- One student (Trottier, see below) was skipped for a different
-- reason: his START_DATE was later than the date rollover ran, so
-- the "currently enrolled" check excluded him even though his
-- NEXT_SCHOOL/CALENDAR_ID were fine.
--
-- Verified against a restore of the actual post-rollover production
-- DB (2025->2026 rollover, SCHOOL_ID=1). Of the 26 rows rollover
-- closed (DROP_CODE = the "Roll" code, i.e. "Rolled Over") with no
-- 2026 row:
--   - 15 are grade-12 students with NEXT_SCHOOL=-1 -> legitimately
--     graduated, correctly excluded.
--   - 10 are the real bug, listed below.
-- (A separate, much larger set of "missing" rows have DROP_CODE =
-- the "Drop" code, i.e. real mid-year "Dropped Out" withdrawals --
-- unrelated to this bug and correctly excluded; ignore those.)
--
-- The 10 confirmed missing students (STUDENT_ID / name):
--   288 Dupel, Alexis            (NEXT_SCHOOL/CALENDAR_ID NULL)
--   335 Trottier, Léo            (future START_DATE, 2026-08-31)
--   336 Viviers, Louka           (NEXT_SCHOOL/CALENDAR_ID NULL)
--   338 Bergeron, Kyara Enora    (NEXT_SCHOOL/CALENDAR_ID NULL)
--   339 Laflamme, Tristan        (NEXT_SCHOOL/CALENDAR_ID NULL)
--   342 El riahi, Karim          (NEXT_SCHOOL/CALENDAR_ID NULL)
--   343 Nadeau, Dali             (NEXT_SCHOOL/CALENDAR_ID NULL)
--   344 Claude, Alexis           (NEXT_SCHOOL/CALENDAR_ID NULL)
--   345 Isabelle, Caleb          (NEXT_SCHOOL/CALENDAR_ID NULL)
--   346 Lemay, Anabelle          (NEXT_SCHOOL/CALENDAR_ID NULL)
--
-- NOT included on purpose:
--   341 Mushonga, Craig Carlos - grade 12, confirmed graduated,
--       leave untouched like every other grade-12 student.
--   340 Piché, Damien / 347 Couillard, William - already rolled
--       correctly, nothing to do.
--
-- IMPORTANT: production's auto-increment IDs (enrollment codes,
-- calendar IDs) may differ from the copy this was verified against,
-- so STEP 2 below looks all of those up dynamically instead of
-- hardcoding them. Still: ALWAYS run STEP 0 first on production and
-- confirm the returned students match the list above before running
-- STEP 2. Take a fresh backup before running STEP 2.
-- ============================================================

SET @OLD_SYEAR = 2025;   -- year they were enrolled in before rollover
SET @NEW_SYEAR = 2026;   -- year rollover just created (current year now)
SET @SCHOOL_ID = 1;
SET @STUDENT_IDS_TO_FIX = '288,335,336,338,339,342,343,344,345,346';

-- ------------------------------------------------------------
-- STEP 0 -- Confirm on production. Must return exactly the 10
-- students listed above. Filtered on DROP_CODE = the "Roll" code
-- to isolate rows rollover actually processed (excluding real
-- mid-year "Dropped Out" rows), and on
-- NEXT_SCHOOL IS NULL OR NEXT_SCHOOL <> -1 (NOT just "<> -1" --
-- that silently drops NULL rows under SQL's three-valued logic)
-- to exclude the legitimately-graduated grade-12 students.
-- If this returns different students, STOP and re-derive the
-- list rather than running STEP 2 blind.
-- ------------------------------------------------------------
SELECT e.STUDENT_ID, s.FIRST_NAME, s.LAST_NAME, e.GRADE_ID, g.NEXT_GRADE_ID,
       e.START_DATE, e.NEXT_SCHOOL, e.CALENDAR_ID, e.DROP_CODE, e.END_DATE
FROM student_enrollment e
JOIN students s ON s.STUDENT_ID = e.STUDENT_ID
LEFT JOIN school_gradelevels g ON g.ID = e.GRADE_ID
WHERE e.SYEAR = @OLD_SYEAR
  AND e.SCHOOL_ID = @SCHOOL_ID
  AND e.DROP_CODE = (SELECT ID FROM student_enrollment_codes WHERE SYEAR = @OLD_SYEAR AND TYPE = 'Roll')
  AND (e.NEXT_SCHOOL IS NULL OR e.NEXT_SCHOOL <> -1)   -- excludes grade-12 graduates, NULL-safe
  AND NOT EXISTS (SELECT 1 FROM student_enrollment ne
                  WHERE ne.STUDENT_ID = e.STUDENT_ID AND ne.SYEAR = @NEW_SYEAR)
ORDER BY e.STUDENT_ID;

-- ------------------------------------------------------------
-- STEP 1 -- Eyeball what rollover actually used for everyone else
-- (informational only -- STEP 2 looks these up itself, it doesn't
-- rely on you transcribing these values anywhere).
-- ------------------------------------------------------------
SELECT DISTINCT START_DATE, ENROLLMENT_CODE, CALENDAR_ID
FROM student_enrollment WHERE SYEAR = @NEW_SYEAR AND SCHOOL_ID = @SCHOOL_ID;

-- ------------------------------------------------------------
-- STEP 2 -- The fix. Explicit STUDENT_ID list (never a blanket
-- "everyone missing" match) so this can only ever touch these 10
-- students. NOT EXISTS guard makes it a no-op for anyone who
-- already has a @NEW_SYEAR row, so it's safe to re-run.
--
-- START_DATE / ENROLLMENT_CODE / CALENDAR_ID are all derived from
-- what rollover actually used for everyone else in @NEW_SYEAR
-- (the single most common START_DATE/CALENDAR_ID pairing), so this
-- matches production's real IDs even if they differ from this
-- script's dev copy.
-- ------------------------------------------------------------
INSERT INTO student_enrollment
    (SYEAR, NEXT_SCHOOL, SCHOOL_ID, STUDENT_ID, GRADE_ID, START_DATE,
     END_DATE, ENROLLMENT_CODE, DROP_CODE, CALENDAR_ID, LAST_SCHOOL)
SELECT
    @NEW_SYEAR,
    @SCHOOL_ID,
    @SCHOOL_ID,
    e.STUDENT_ID,
    (SELECT g.NEXT_GRADE_ID FROM school_gradelevels g WHERE g.ID = e.GRADE_ID),
    (SELECT ne.START_DATE FROM student_enrollment ne
       WHERE ne.SYEAR = @NEW_SYEAR AND ne.SCHOOL_ID = @SCHOOL_ID
       GROUP BY ne.START_DATE ORDER BY COUNT(*) DESC LIMIT 1),
    NULL,
    (SELECT ID FROM student_enrollment_codes WHERE SYEAR = @NEW_SYEAR AND TYPE = 'Roll'),
    NULL,
    (SELECT ne.CALENDAR_ID FROM student_enrollment ne
       WHERE ne.SYEAR = @NEW_SYEAR AND ne.SCHOOL_ID = @SCHOOL_ID
       GROUP BY ne.CALENDAR_ID ORDER BY COUNT(*) DESC LIMIT 1),
    @SCHOOL_ID
FROM student_enrollment e
WHERE e.SYEAR = @OLD_SYEAR
  AND e.SCHOOL_ID = @SCHOOL_ID
  AND FIND_IN_SET(e.STUDENT_ID, @STUDENT_IDS_TO_FIX)
  AND NOT EXISTS (SELECT 1 FROM student_enrollment ne
                  WHERE ne.STUDENT_ID = e.STUDENT_ID AND ne.SYEAR = @NEW_SYEAR);

-- ------------------------------------------------------------
-- STEP 3 -- Verify: should return exactly 10 rows.
-- ------------------------------------------------------------
SELECT e.STUDENT_ID, s.FIRST_NAME, s.LAST_NAME, e.GRADE_ID, e.START_DATE,
       e.CALENDAR_ID, e.NEXT_SCHOOL, e.ENROLLMENT_CODE
FROM student_enrollment e
JOIN students s ON s.STUDENT_ID = e.STUDENT_ID
WHERE e.SYEAR = @NEW_SYEAR AND e.SCHOOL_ID = @SCHOOL_ID
  AND FIND_IN_SET(e.STUDENT_ID, @STUDENT_IDS_TO_FIX)
ORDER BY s.LAST_NAME;

-- ------------------------------------------------------------
-- Reminder: this only creates the enrollment record. Course
-- scheduling (course requests / schedule) for these 10 students
-- still needs to be done separately through the Scheduling module,
-- same as it would for any other student.
-- ------------------------------------------------------------
