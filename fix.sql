USE opensis;
ALTER TABLE gradebook_assignments
ADD ASSIGNMENT_WEIGHT  decimal(10,0) AFTER points;
ALTER TABLE student_report_card_grades
ADD comment1  blob AFTER comment;
ALTER TABLE student_report_card_grades
ADD comment2  blob AFTER comment1;
ALTER TABLE user_file_upload CONVERT TO CHARACTER SET utf8;

ALTER TABLE grades_completed
ADD GRADE_LEVEL decimal(9,0) after PERIOD_ID


CREATE TABLE CADO_report_card_comments (
    student_id int,
    marking_period int,
    com_competences blob ,
    com_general blob
);
ALTER TABLE CADO_report_card_comments CONVERT TO CHARACTER SET utf8;

ALTER TABLE student_report_card_grades
DROP COLUMN comment1;
ALTER TABLE student_report_card_grades
DROP COLUMN comment2;


ALTER TABLE course_periods
ADD tertiary_teacher_id  long AFTER SECONDARY_TEACHER_ID;

DROP VIEW `opensis`.`course_details`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `course_details` AS select `cp`.`school_id` AS `school_id`,`cp`.`syear` AS `syear`,`cp`.`marking_period_id` AS `marking_period_id`,`c`.`subject_id` AS `subject_id`,`cp`.`course_id` AS `course_id`,`cp`.`course_period_id` AS `course_period_id`,`cp`.`teacher_id` AS `teacher_id`,`cp`.`secondary_teacher_id` AS `secondary_teacher_id`,`cp`.`tertiary_teacher_id` AS `tertiary_teacher_id`,`c`.`title` AS `course_title`,`cp`.`title` AS `cp_title`,`cp`.`grade_scale_id` AS `grade_scale_id`,`cp`.`mp` AS `mp`,`cp`.`credits` AS `credits`,`cp`.`begin_date` AS `begin_date`,`cp`.`end_date` AS `end_date` from (`course_periods` `cp` join `courses` `c`) where `cp`.`course_id` = `c`.`course_id`;
