<?php
/**
 * SecureFilesFnc.php
 * Access control and safe deletion for uploaded student / staff files
 * (records in user_file_upload, files in assets/studentfiles and assets/stafffiles).
 *
 * Loaded automatically by Warehouse.php (every file in functions/ is included).
 * Added during the 2026-11-18 security audit: these folders are no longer served
 * directly by Apache, so every download goes through DownloadWindow.php, which
 * calls SecureFileCanDownload() before sending anything.
 */

/**
 * Profile ID used for student-owned records in user_file_upload.
 * Staff-owned records use the uploader's profile (admin / teacher), never 3.
 */
define('SECURE_FILE_STUDENT_PROFILE_ID', 3);

/**
 * Is the logged-in parent (people.STAFF_ID) linked to this student?
 */
function SecureFileParentHasStudent($parentId, $studentId)
{
    $parentId = (int) $parentId;
    $studentId = (int) $studentId;
    if ($parentId <= 0 || $studentId <= 0) {
        return false;
    }
    $ret = DBGet(DBQuery('SELECT 1 AS OK FROM students_join_people WHERE PERSON_ID=' . $parentId . ' AND STUDENT_ID=' . $studentId . ' LIMIT 1'));
    return !empty($ret);
}

/**
 * Does this teacher (primary, secondary or tertiary) teach this student this school year?
 */
function SecureFileTeacherHasStudent($teacherId, $studentId, $syear)
{
    $teacherId = (int) $teacherId;
    $studentId = (int) $studentId;
    $syear = (int) $syear;
    if ($teacherId <= 0 || $studentId <= 0) {
        return false;
    }
    $ret = DBGet(DBQuery('SELECT 1 AS OK FROM schedule s JOIN course_periods cp ON cp.COURSE_PERIOD_ID=s.COURSE_PERIOD_ID'
        . ' WHERE s.STUDENT_ID=' . $studentId . ' AND s.SYEAR=' . $syear
        . ' AND ' . $teacherId . ' IN (cp.TEACHER_ID, cp.SECONDARY_TEACHER_ID, cp.TERTIARY_TEACHER_ID) LIMIT 1'));
    return !empty($ret);
}

/**
 * Does this teacher teach any child of this parent this school year?
 */
function SecureFileTeacherHasParentChild($teacherId, $parentId, $syear)
{
    $teacherId = (int) $teacherId;
    $parentId = (int) $parentId;
    $syear = (int) $syear;
    if ($teacherId <= 0 || $parentId <= 0) {
        return false;
    }
    $ret = DBGet(DBQuery('SELECT 1 AS OK FROM students_join_people sjp'
        . ' JOIN schedule s ON s.STUDENT_ID=sjp.STUDENT_ID'
        . ' JOIN course_periods cp ON cp.COURSE_PERIOD_ID=s.COURSE_PERIOD_ID'
        . ' WHERE sjp.PERSON_ID=' . $parentId . ' AND s.SYEAR=' . $syear
        . ' AND ' . $teacherId . ' IN (cp.TEACHER_ID, cp.SECONDARY_TEACHER_ID, cp.TERTIARY_TEACHER_ID) LIMIT 1'));
    return !empty($ret);
}

/**
 * May the logged-in user download this user_file_upload record?
 *
 *  Student files (PROFILE_ID 3): admins, the student, the student's parents,
 *                                and teachers who teach the student this year.
 *  Staff planning files (stafffile): admins, teachers, the owner, and parents /
 *                                students whose child / self is taught by the owner this year.
 *  School logo, internal message attachments: any logged-in user (unchanged behaviour).
 *  Any other staff file: admins and the owner.
 *
 * @param array $file One row from DBGet() on user_file_upload (upper-case keys).
 */
function SecureFileCanDownload($file)
{
    if (empty($file) || !is_array($file)) {
        return false;
    }

    $profile = User('PROFILE');
    if ($profile === 'admin') {
        return true;
    }

    $info = (string) $file['FILE_INFO'];
    $owner = (int) $file['USER_ID'];
    $syear = (int) UserSyear();
    $staffId = (int) ($_SESSION['STAFF_ID'] ?? 0);       // staff.STAFF_ID, or people.STAFF_ID for parents
    $studentId = (int) ($_SESSION['STUDENT_ID'] ?? 0);

    if (in_array($info, array('schlogo', 'intmsg'), true)) {
        return true;
    }

    // Student-owned file.
    if ((int) $file['PROFILE_ID'] === SECURE_FILE_STUDENT_PROFILE_ID) {
        switch ($profile) {
            case 'student':
                return $owner === $studentId;
            case 'parent':
                return SecureFileParentHasStudent($staffId, $owner);
            case 'teacher':
                return SecureFileTeacherHasStudent($staffId, $owner, $syear);
        }
        return false;
    }

    // Staff-owned file. Note: parent IDs (people table) and staff IDs (staff table)
    // can be equal numbers, so "owner" only applies to staff profiles.
    if ($info === 'stafffile') {
        switch ($profile) {
            case 'teacher':
                return true;
            case 'student':
                return SecureFileTeacherHasStudent($owner, $studentId, $syear);
            case 'parent':
                return SecureFileTeacherHasParentChild($owner, $staffId, $syear);
        }
        return false;
    }

    return $profile !== 'parent' && $profile !== 'student' && $owner === $staffId;
}

/**
 * Delete a file only if it really is inside $dir (blocks ../ and absolute paths).
 */
function SecureFileUnlinkInDir($dir, $path)
{
    $base = realpath($dir);
    $real = realpath((string) $path);
    if ($base === false || $real === false) {
        return false;
    }
    if (strpos($real, $base . DIRECTORY_SEPARATOR) !== 0 || !is_file($real)) {
        return false;
    }
    return unlink($real);
}

/**
 * Delete an uploaded file by its user_file_upload ID, but only if the record belongs
 * to $ownerId. The file name comes from the database, never from the request.
 *
 * @param string $dir       assets/studentfiles or assets/stafffiles
 * @param mixed  $id        user_file_upload.ID (from the request; cast to int)
 * @param int    $ownerId   UserStudentID() or UserStaffID()
 * @param bool   $isStudent true for student files (PROFILE_ID 3), false for staff files
 */
function SecureFileDeleteUpload($dir, $id, $ownerId, $isStudent)
{
    $id = (int) $id;
    $ownerId = (int) $ownerId;
    if ($id <= 0 || $ownerId <= 0) {
        return false;
    }
    $profileCond = $isStudent
        ? 'PROFILE_ID=' . SECURE_FILE_STUDENT_PROFILE_ID
        : 'PROFILE_ID<>' . SECURE_FILE_STUDENT_PROFILE_ID;
    $rec = DBGet(DBQuery('SELECT NAME FROM user_file_upload WHERE ID=' . $id . ' AND USER_ID=' . $ownerId . ' AND ' . $profileCond));
    if (empty($rec)) {
        return false;
    }
    SecureFileUnlinkInDir($dir, $dir . '/' . basename($rec[1]['NAME']));
    DBQuery('DELETE FROM user_file_upload WHERE ID=' . $id);
    return true;
}
