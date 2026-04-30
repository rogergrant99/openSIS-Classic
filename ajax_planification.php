<?php
/**
 * Standalone Ajax Endpoint for Planification Module
 * Place this file in the openSIS root directory (same level as Modules.php)
 * Access via: ajax_planification.php
 */

// Absolute first thing - check if this is our Ajax request
if (!isset($_POST['ajax_action'])) {
    http_response_code(400);
    die('Invalid request');
}

// Kill all output buffering
while (@ob_end_clean());

// Start session
session_start();

// Include only essential files
require_once('Warehouse.php');

// Set JSON header
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Helper function
function dateFr($format, $timestamp = null) {
    if ($timestamp === null) $timestamp = time();
    $result = date($format, $timestamp);
    $result = str_replace(['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'], ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'], $result);
    $result = str_replace(['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'], ['janv', 'févr', 'mars', 'avr', 'mai', 'juin', 'juil', 'août', 'sept', 'oct', 'nov', 'déc'], $result);
    return $result;
}

// Safe date conversion - anchors to noon to prevent timezone day-shifting
function safeDate($date_string) {
    // If already in Y-m-d format, use it directly without strtotime
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_string)) {
        return $date_string;
    }
    // Otherwise convert with noon anchor to prevent timezone shifting
    return date('Y-m-d', strtotime($date_string . ' 12:00:00'));
}

// Handle actions
try {
    $action = $_POST['ajax_action'];
    
    switch ($action) {
        case 'auto_save':
            $week = $_POST['week'] ?? '';
            $field = $_POST['field'] ?? '';
            $content = $_POST['content'] ?? '';
            $course_id = intval($_POST['course_id'] ?? 0);
            $primaire = intval($_POST['primaire'] ?? 0);
            $week_start = $_POST['week_start'] ?? '';
            
            if (!$week || !$field || !$week_start) {
                throw new Exception('Missing parameters');
            }
            
            // Use safeDate to avoid timezone day-shifting
            $week_start_safe = safeDate($week_start);
            
            $allowed_tags = '<b><strong><i><em><u><br><p><ul><ol><li><span><div><font><mark><a>';
            $content = strip_tags($content, $allowed_tags);
            
            if ($week === 'week1' && $field) {
                // CRITICAL: Always fetch existing data for THIS specific week from DB
                // Never use session data which may belong to a different week
                $RET = DBGet(DBQuery('SELECT * FROM planification WHERE start_date=\'' . $week_start_safe . '\' AND course_id=' . $course_id . ' AND is_primary=' . $primaire));
                
                // Start with empty data for this week
                $week_data = [
                    'lundi_notions' => '', 'lundi_devoirs' => '', 'lundi_materiel' => '',
                    'mardi_notions' => '', 'mardi_devoirs' => '', 'mardi_materiel' => '',
                    'mercredi_notions' => '', 'mercredi_devoirs' => '', 'mercredi_materiel' => '',
                    'jeudi_notions' => '', 'jeudi_devoirs' => '', 'jeudi_materiel' => '',
                    'vendredi_notions' => '', 'vendredi_devoirs' => '', 'vendredi_materiel' => ''
                ];
                
                // If existing data found in DB for this week, load it
                if (!empty($RET) && isset($RET[1]['TEXT'])) {
                    $raw_content = base64_decode($RET[1]['TEXT']);
                    $unserialized = @unserialize($raw_content);
                    if ($unserialized !== false) {
                        $week_data = $unserialized;
                    }
                }
                
                // Now update only the specific field that changed
                $week_data[$field] = $content;
                
                if (empty($RET)) {
                    // No record yet for this week - insert one
                    DBQuery('INSERT INTO planification (start_date, updated_by, is_primary, course_id) VALUES ("' . $week_start_safe . '", ' . UserID() . ', ' . $primaire . ', ' . $course_id . ')');
                }
                
                // Save the complete week data back to DB
                $serializedArray = serialize($week_data);
                DBQuery('UPDATE planification SET updated_by = ' . UserID() . ', text = "' . base64_encode($serializedArray) . '" WHERE course_id= ' . $course_id . ' AND is_primary= ' . $primaire . ' AND start_date = "' . $week_start_safe . '"');
                
                $get_teacher = DBGet(DBQuery('SELECT CONCAT(FIRST_NAME," ",LAST_NAME) AS FULLNAME FROM staff WHERE STAFF_ID=' . UserID()));
                $updated_by = !empty($get_teacher[1]['FULLNAME']) ? $get_teacher[1]['FULLNAME'] : 'Unknown';
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Saved successfully',
                    'updated_by' => $updated_by,
                    'timestamp' => date('H:i:s'),
                    'saved_to_date' => $week_start_safe
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid data']);
            }
            break;
            
        case 'change_week':
            $week_range = $_POST['week_range'] ?? '';
            $course_id = intval($_POST['course_id'] ?? 0);
            $primaire = intval($_POST['primaire'] ?? 0);
            
            if (!$week_range) {
                throw new Exception('Week range required');
            }
            
            // Use safeDate to avoid timezone day-shifting
            $week_start_safe = safeDate($week_range);
            $week1_sec = strtotime($week_start_safe . ' 12:00:00');
            $week1_date_start = dateFr('d-M', $week1_sec);
            
            $RET = DBGet(DBQuery('SELECT * FROM planification WHERE start_date=\'' . $week_start_safe . '\' AND is_primary=' . $primaire . ' AND course_id=' . $course_id));
            
            $data = ['week1' => []];
            $updated_by = null;
            
            if (!empty($RET) && isset($RET[1]['TEXT'])) {
                $raw_content = base64_decode($RET[1]['TEXT']);
                $unserialized = @unserialize($raw_content);
                if ($unserialized !== false) {
                    $data['week1'] = $unserialized;
                }
                
                if (!empty($RET[1]['UPDATED_BY'])) {
                    $get_teacher = DBGet(DBQuery('SELECT CONCAT(FIRST_NAME," ",LAST_NAME) AS FULLNAME FROM staff WHERE STAFF_ID=' . intval($RET[1]['UPDATED_BY'])));
                    if (!empty($get_teacher[1]['FULLNAME'])) {
                        $updated_by = $get_teacher[1]['FULLNAME'];
                    }
                }
            }
            
            if (empty($data['week1'])) {
                $data['week1'] = [
                    'lundi_notions' => '', 'lundi_devoirs' => '', 'lundi_materiel' => '',
                    'mardi_notions' => '', 'mardi_devoirs' => '', 'mardi_materiel' => '',
                    'mercredi_notions' => '', 'mercredi_devoirs' => '', 'mercredi_materiel' => '',
                    'jeudi_notions' => '', 'jeudi_devoirs' => '', 'jeudi_materiel' => '',
                    'vendredi_notions' => '', 'vendredi_devoirs' => '', 'vendredi_materiel' => ''
                ];
            }
            
            if ($primaire) {
                $course_name = 'Planification ' . ($primaire == 1 ? 'préscolaire' : 'primaire ' . ($primaire - 1));
            } else {
                $course_RET = DBGet(DBQuery('SELECT short_name FROM course_details WHERE course_id=' . $course_id));
                $course_name = 'Planification';
                if (!empty($course_RET[1]['SHORT_NAME'])) {
                    $course_name .= ' ' . $course_RET[1]['SHORT_NAME'];
                }
            }
            
            $one_week = 60 * 60 * 24 * 7;
            echo json_encode([
                'success' => true,
                'data' => $data,
                'week_start' => $week1_date_start,
                'week_end' => dateFr('d-M', $week1_sec + (60 * 60 * 24 * 6)),
                'prev_week' => date('Y-m-d', $week1_sec - $one_week),
                'next_week' => date('Y-m-d', $week1_sec + $one_week),
                'course_name' => $course_name,
                'updated_by' => $updated_by
            ]);
            break;
            
        case 'change_course':
            $course_id = intval($_POST['course_id'] ?? 0);
            $week_range = $_POST['week_range'] ?? '';
            
            if (!$course_id || !$week_range) {
                throw new Exception('Missing parameters');
            }
            
            $course_RET = DBGet(DBQuery('SELECT grade_level, teacher_id, short_name FROM course_details WHERE SYEAR=\'' . UserSyear() . '\' AND course_id=' . $course_id));
            
            if (empty($course_RET)) {
                throw new Exception('Course not found');
            }
            
            $grade_level = $course_RET[1]['GRADE_LEVEL'];
            $primaire = 0;
            $temp_course_id = $course_id;
            
            if ($grade_level >= 1 && $grade_level <= 7) {
                $primaire = $grade_level;
                $temp_course_id = 0;
            }
            
            // Get course name
            if ($primaire) {
                $course_name = 'Planification ' . ($primaire == 1 ? 'préscolaire' : 'primaire ' . ($primaire - 1));
            } else {
                $course_name = 'Planification';
                if (!empty($course_RET[1]['SHORT_NAME'])) {
                    $course_name .= ' ' . $course_RET[1]['SHORT_NAME'];
                }
            }
            
            // Get days
            $days = [
                'mondayClass' => '',
                'tuesdayClass' => '',
                'wednesdayClass' => '',
                'thursdayClass' => '',
                'fridayClass' => ''
            ];
            
            if ($temp_course_id) {
                $days_RET = DBGet(DBQuery('SELECT cpv.days FROM course_details cd JOIN course_period_var cpv WHERE SYEAR=\'' . UserSyear() . '\' AND course_id=' . $temp_course_id . ' AND cpv.course_period_id = cd.course_period_id'));
                $result = '';
                if ($days_RET) {
                    foreach ($days_RET as $d) {
                        $result .= $d['DAYS'];
                    }
                }
                $array = str_split($result);
                $days = [
                    'mondayClass' => in_array('M', $array) ? '' : 'hidden-day',
                    'tuesdayClass' => in_array('T', $array) ? '' : 'hidden-day',
                    'wednesdayClass' => in_array('W', $array) ? '' : 'hidden-day',
                    'thursdayClass' => in_array('H', $array) ? '' : 'hidden-day',
                    'fridayClass' => in_array('F', $array) ? '' : 'hidden-day'
                ];
            }
            
            // Get week data
            $week_start_safe = safeDate($week_range);
            $week1_sec = strtotime($week_start_safe . ' 12:00:00');
            $RET = DBGet(DBQuery('SELECT * FROM planification WHERE start_date=\'' . $week_start_safe . '\' AND is_primary=' . $primaire . ' AND course_id=' . $temp_course_id));
            
            $data = ['week1' => []];
            $updated_by = null;
            
            if (!empty($RET) && isset($RET[1]['TEXT'])) {
                $raw_content = base64_decode($RET[1]['TEXT']);
                $unserialized = @unserialize($raw_content);
                if ($unserialized !== false) {
                    $data['week1'] = $unserialized;
                }
                
                if (!empty($RET[1]['UPDATED_BY'])) {
                    $get_teacher = DBGet(DBQuery('SELECT CONCAT(FIRST_NAME," ",LAST_NAME) AS FULLNAME FROM staff WHERE STAFF_ID=' . intval($RET[1]['UPDATED_BY'])));
                    if (!empty($get_teacher[1]['FULLNAME'])) {
                        $updated_by = $get_teacher[1]['FULLNAME'];
                    }
                }
            }
            
            if (empty($data['week1'])) {
                $data['week1'] = [
                    'lundi_notions' => '', 'lundi_devoirs' => '', 'lundi_materiel' => '',
                    'mardi_notions' => '', 'mardi_devoirs' => '', 'mardi_materiel' => '',
                    'mercredi_notions' => '', 'mercredi_devoirs' => '', 'mercredi_materiel' => '',
                    'jeudi_notions' => '', 'jeudi_devoirs' => '', 'jeudi_materiel' => '',
                    'vendredi_notions' => '', 'vendredi_devoirs' => '', 'vendredi_materiel' => ''
                ];
            }
            
            echo json_encode([
                'success' => true,
                'data' => $data,
                'primaire' => $primaire,
                'course_id' => $temp_course_id,
                'course_name' => $course_name,
                'days' => $days,
                'updated_by' => $updated_by
            ]);
            break;
            
        case 'delete_file':
            $file_name = $_POST['file_name'] ?? '';
            $teacher_id = intval($_POST['teacher_id'] ?? 0);
            
            if (!$file_name) {
                throw new Exception('File name required');
            }
            
            // Delete from database first
            $delete_result = DBQuery('DELETE FROM user_file_upload WHERE name="' . addslashes($file_name) . '" AND user_id=' . $teacher_id);
            
            // Try to delete the physical file
            $target_path = 'assets/stafffiles/' . $file_name;
            $file_deleted = false;
            $file_error = '';
            
            if (file_exists($target_path)) {
                if (is_writable($target_path)) {
                    $file_deleted = unlink($target_path);
                    if (!$file_deleted) {
                        $file_error = 'Failed to delete physical file (unlink failed)';
                    }
                } else {
                    $file_error = 'File exists but is not writable';
                }
            } else {
                $file_error = 'Physical file not found at: ' . $target_path;
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'File deleted from database',
                'file_deleted' => $file_deleted,
                'file_path' => $target_path,
                'file_error' => $file_error,
                'db_deleted' => true
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action: ' . $action]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

exit;