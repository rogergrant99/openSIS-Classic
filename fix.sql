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


ALTER TABLE school_quarters
ADD DAYS decimal(9,0) after post_end_date


course_details Structure:
select `c`.`title` AS `course_name`,`c`.`short_name` AS `course_number`,`c`.`grade_level` AS `grade_level`,`cp`.`school_id` AS `school_id`,`cp`.`syear` AS `syear`,`cp`.`marking_period_id` AS `marking_period_id`,`cp`.`short_name` AS `short_name`,`c`.`subject_id` AS `subject_id`,`cp`.`course_id` AS `course_id`,`cp`.`course_period_id` AS `course_period_id`,`cp`.`teacher_id` AS `teacher_id`,`c`.`rollover_id` AS `rollover_id`,`cp`.`secondary_teacher_id` AS `secondary_teacher_id`,`cp`.`tertiary_teacher_id` AS `tertiary_teacher_id`,`c`.`title` AS `course_title`,`cp`.`title` AS `cp_title`,`cp`.`grade_scale_id` AS `grade_scale_id`,`cp`.`marking_period_id` AS `mpid`,`cp`.`mp` AS `mp`,`cp`.`credits` AS `credits`,`cp`.`begin_date` AS `begin_date`,`cp`.`end_date` AS `end_date` from (`opensis`.`course_periods` `cp` join `opensis`.`courses` `c`) where `cp`.`course_id` = `c`.`course_id`


CREATE TABLE `planification` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) DEFAULT NULL,
  `text` mediumblob DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `start_date` date DEFAULT NULL,
  `is_primary` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=502 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


/* Use when auto type assigments are wrong.*/

UPDATE gradebook_assignment_types gat
JOIN course_periods cp ON gat.course_period_id = cp.course_period_id
SET gat.course_id = cp.course_id  WHERE gat.last_updated > "2025-08-29 11:00:06";


ALTER TABLE `opensis`.`course_periods`
ADD COLUMN `does_no_planning` varchar(1) NULL;

course_details Structure V2:
select `c`.`title` AS `course_name`,`c`.`short_name` AS `course_number`,`c`.`grade_level` AS `grade_level`,`cp`.`school_id` AS `school_id`,`cp`.`syear` AS `syear`,`cp`.`marking_period_id` AS `marking_period_id`,`cp`.`short_name` AS `short_name`,`c`.`subject_id` AS `subject_id`,`cp`.`course_id` AS `course_id`,`cp`.`course_period_id` AS `course_period_id`,`cp`.`teacher_id` AS `teacher_id`,`c`.`rollover_id` AS `rollover_id`,`cp`.`secondary_teacher_id` AS `secondary_teacher_id`,`cp`.`tertiary_teacher_id` AS `tertiary_teacher_id` ,`cp`.`does_no_planning` AS `does_no_planning` ,`c`.`title` AS `course_title`,`cp`.`title` AS `cp_title`,`cp`.`grade_scale_id` AS `grade_scale_id`,`cp`.`marking_period_id` AS `mpid`,`cp`.`mp` AS `mp`,`cp`.`credits` AS `credits`,`cp`.`begin_date` AS `begin_date`,`cp`.`end_date` AS `end_date` from (`opensis`.`course_periods` `cp` join `opensis`.`courses` `c`) where `cp`.`course_id` = `c`.`course_id`

/*  add habillement table */
CREATE TABLE IF NOT EXISTS `habillement` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `STUDENT_ID` int(11) NOT NULL,
  `SCHOOL_ID` int(11) NOT NULL,
  `SYEAR` int(4) NOT NULL,
  `WEEK_START` date NOT NULL COMMENT 'Lundi de la semaine',
  `WEEK_END` date NOT NULL COMMENT 'Dimanche de la semaine',
  `COMPLIANT` enum('Y','N') NOT NULL DEFAULT 'N' COMMENT 'Y=Conforme, N=Non conforme',
  `CREATED_AT` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UPDATED_AT` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `unique_student_week` (`STUDENT_ID`, `SCHOOL_ID`, `SYEAR`, `WEEK_START`),
  KEY `idx_student` (`STUDENT_ID`),
  KEY `idx_school_year` (`SCHOOL_ID`, `SYEAR`),
  KEY `idx_week` (`WEEK_START`, `WEEK_END`),
  KEY `idx_compliant` (`COMPLIANT`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Suivi de la conformité vestimentaire par semaine';

-- Index pour améliorer les performances des requêtes
CREATE INDEX idx_student_week ON habillement(STUDENT_ID, WEEK_START);
CREATE INDEX idx_school_syear_week ON habillement(SCHOOL_ID, SYEAR, WEEK_START);



SELECT 
    COUNT(DISTINCT school_date) AS nombre_de_jours
FROM 
    attendance_calendar
WHERE 
    school_date BETWEEN '2025-08-29' AND '2025-11-07';


SELECT 
    COUNT(DISTINCT school_date) AS nombre_de_jours
FROM 
    attendance_calendar
WHERE 
    school_date BETWEEN '2025-11-10' AND '2026-02-27';

SELECT 
    COUNT(DISTINCT school_date) AS nombre_de_jours
FROM 
    attendance_calendar
WHERE 
    school_date BETWEEN '2026-03-02' AND '2026-06-23';


CREATE TABLE IF NOT EXISTS `plan_intervention` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `STUDENT_ID` int(11) NOT NULL,
  `SYEAR` int(4) NOT NULL,
  `SCHOOL_ID` int(11) NOT NULL,
  `NOM_ELEVE` varchar(255) DEFAULT NULL,
  `ANNEE_SCOLAIRE` varchar(20) DEFAULT NULL,
  `CODE_PERMANENT` varchar(50) DEFAULT NULL,
  `DATE_NAISSANCE` date DEFAULT NULL,
  `NIVEAU_SCOLAIRE` varchar(50) DEFAULT NULL,
  `REPRISE` varchar(100) DEFAULT NULL,
  `DIAGNOSTIC` text DEFAULT NULL,
  `AUTRES_DIAGNOSTIC` varchar(255) DEFAULT NULL,
  `DATE_EVALUATION` varchar(255) DEFAULT NULL,
  `PRECISIONS` text DEFAULT NULL,
  `HYPOTHESE` text DEFAULT NULL,
  `MEDICATION` varchar(255) DEFAULT NULL,
  `SPHERES_PROBLEMATIQUES` varchar(255) DEFAULT NULL,
  `MANIFESTATIONS_COMPORTEMENTALE` text DEFAULT NULL,
  `MANIFESTATIONS_APPRENTISSAGE` text DEFAULT NULL,
  `BESOINS_OBJECTIFS` text DEFAULT NULL,
  `MESURES_APPUI` text DEFAULT NULL,
  `AUTRES_MESURES` text DEFAULT NULL,
  `RECOMMANDATIONS` text DEFAULT NULL,
  `AUTORITE_PARENTALE_1` varchar(255) DEFAULT NULL,
  `DATE_SIGNATURE_PARENT_1` date DEFAULT NULL,
  `AUTORITE_PARENTALE_2` varchar(255) DEFAULT NULL,
  `DATE_SIGNATURE_PARENT_2` date DEFAULT NULL,
  `ELEVE_SIGNATURE` varchar(255) DEFAULT NULL,
  `DATE_SIGNATURE_ELEVE` date DEFAULT NULL,
  `DIRECTION_SIGNATURE` varchar(255) DEFAULT NULL,
  `DATE_SIGNATURE_DIRECTION` date DEFAULT NULL,
  `CREATED_AT` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UPDATED_AT` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  KEY `STUDENT_ID` (`STUDENT_ID`),
  KEY `SYEAR` (`SYEAR`),
  KEY `SCHOOL_ID` (`SCHOOL_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `note_evolutive` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `STUDENT_ID` int(11) NOT NULL,
  `SYEAR` int(4) NOT NULL,
  `SCHOOL_ID` int(11) NOT NULL,
  `NOTE_DATE` date NOT NULL,
  `NOTE_TEXT` text NOT NULL,
  `CREATED_BY` int(11) DEFAULT NULL COMMENT 'Staff ID who created the note',
  `CREATED_AT` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UPDATED_AT` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  KEY `STUDENT_ID` (`STUDENT_ID`),
  KEY `SYEAR` (`SYEAR`),
  KEY `SCHOOL_ID` (`SCHOOL_ID`),
  KEY `NOTE_DATE` (`NOTE_DATE`),
  KEY `CREATED_BY` (`CREATED_BY`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `profil_eleves` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `STUDENT_ID` int(11) NOT NULL,
  `SYEAR` int(4) NOT NULL,
  `SCHOOL_ID` int(11) NOT NULL,
  `STUDENT_NAME` varchar(255) DEFAULT NULL,
  `BIRTH_DATE` date DEFAULT NULL,
  `GRADE_LEVEL` varchar(50) DEFAULT NULL,
  `PROFIL_DATE` date DEFAULT NULL,
  
  -- Section: Forces de l'élève
  `ACADEMIC_STRENGTHS` text,
  `SOCIAL_STRENGTHS` text,
  `BEHAVIORAL_STRENGTHS` text,
  `CREATIVE_STRENGTHS` text,
  
  -- Section: Défis/Difficultés
  `ACADEMIC_CHALLENGES` text,
  `SOCIAL_CHALLENGES` text,
  `BEHAVIORAL_CHALLENGES` text,
  `LEARNING_CHALLENGES` text,
  
  -- Section: Intérêts et Préférences
  `INTERESTS` text,
  `LEARNING_STYLE` varchar(100) DEFAULT NULL,
  `PREFERRED_ACTIVITIES` text,
  `HOBBIES` text,
  
  -- Section: Stratégies efficaces
  `TEACHING_STRATEGIES` text,
  `MOTIVATION_STRATEGIES` text,
  `BEHAVIOR_STRATEGIES` text,
  `COMMUNICATION_STRATEGIES` text,
  
  -- Section: Objectifs à court terme
  `SHORT_TERM_GOAL_1` text,
  `SHORT_TERM_DEADLINE_1` date DEFAULT NULL,
  `SHORT_TERM_STATUS_1` varchar(50) DEFAULT NULL,
  `SHORT_TERM_GOAL_2` text,
  `SHORT_TERM_DEADLINE_2` date DEFAULT NULL,
  `SHORT_TERM_STATUS_2` varchar(50) DEFAULT NULL,
  `SHORT_TERM_GOAL_3` text,
  `SHORT_TERM_DEADLINE_3` date DEFAULT NULL,
  `SHORT_TERM_STATUS_3` varchar(50) DEFAULT NULL,
  
  -- Section: Objectifs à long terme
  `LONG_TERM_GOAL_1` text,
  `LONG_TERM_DEADLINE_1` date DEFAULT NULL,
  `LONG_TERM_STATUS_1` varchar(50) DEFAULT NULL,
  `LONG_TERM_GOAL_2` text,
  `LONG_TERM_DEADLINE_2` date DEFAULT NULL,
  `LONG_TERM_STATUS_2` varchar(50) DEFAULT NULL,
  
  -- Section: Soutien et ressources
  `FAMILY_SUPPORT` text,
  `SCHOOL_RESOURCES` text,
  `EXTERNAL_SERVICES` text,
  `ACCOMMODATIONS` text,
  
  -- Section: Observations et notes
  `TEACHER_OBSERVATIONS` text,
  `PARENT_FEEDBACK` text,
  `STUDENT_SELF_ASSESSMENT` text,
  `ADDITIONAL_NOTES` text,
  
  -- Section: Personnes ressources
  `PERSON_1_NAME` varchar(255) DEFAULT NULL,
  `PERSON_1_ROLE` varchar(100) DEFAULT NULL,
  `PERSON_1_CONTACT` varchar(255) DEFAULT NULL,
  `PERSON_2_NAME` varchar(255) DEFAULT NULL,
  `PERSON_2_ROLE` varchar(100) DEFAULT NULL,
  `PERSON_2_CONTACT` varchar(255) DEFAULT NULL,
  `PERSON_3_NAME` varchar(255) DEFAULT NULL,
  `PERSON_3_ROLE` varchar(100) DEFAULT NULL,
  `PERSON_3_CONTACT` varchar(255) DEFAULT NULL,
  
  -- Signatures et dates
  `TEACHER_SIGNATURE` varchar(255) DEFAULT NULL,
  `TEACHER_DATE` date DEFAULT NULL,
  `PARENT_SIGNATURE` varchar(255) DEFAULT NULL,
  `PARENT_DATE` date DEFAULT NULL,
  `CREATED_AT` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UPDATED_AT` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`ID`),
  KEY `STUDENT_ID` (`STUDENT_ID`),
  KEY `SYEAR` (`SYEAR`),
  KEY `SCHOOL_ID` (`SCHOOL_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


INSERT INTO `student_field_categories` (`ID`, `TITLE`, `SORT_ORDER`, `INCLUDE`) 
VALUES (13, 'Note évolutive', 13, 'NoteEvolutiveInc')
ON DUPLICATE KEY UPDATE `TITLE` = 'Note évolutive';

INSERT INTO `student_field_categories` (`ID`, `TITLE`, `SORT_ORDER`, `INCLUDE`) 
VALUES (14, 'Note évolutive', 14, 'PlandinterventionInc')
ON DUPLICATE KEY UPDATE `TITLE` = "Plan d'intervention";

INSERT INTO `student_field_categories` (`ID`, `TITLE`, `SORT_ORDER`, `INCLUDE`) 
VALUES (15, 'Note évolutive', 15, 'ProfildeleveInc')
ON DUPLICATE KEY UPDATE `TITLE` = "Profil d'élève";