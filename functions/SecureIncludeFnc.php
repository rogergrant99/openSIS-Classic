<?php
/**
 * SecureIncludeFnc.php
 * Validation for the "include" request parameter that Staff.php and User.php
 * (and Student.php, inline) use to choose which tab file to include().
 *
 * Loaded automatically by Warehouse.php (every file in functions/ is included).
 * Added during the 2026-11-18 security audit.
 */

/**
 * Return a safe tab include name, or $default if $value is not a real tab.
 *
 * Accepted:
 *   - "SomeInc"       when $includesDir/SomeInc.php exists (core tabs)
 *   - "module/File"   when it is registered in $categoryTable.INCLUDE and modules/module/File.php exists
 *                     (tabs added by other modules)
 * Everything else (../ paths, other characters, other modules' pages) returns $default.
 *
 * @param string $value         the requested include
 * @param string $includesDir   absolute path of the page's includes/ folder
 * @param string $categoryTable student_field_categories, staff_field_categories or people_field_categories
 * @param string $default       the tab to fall back to
 */
function SecureTabInclude($value, $includesDir, $categoryTable, $default)
{
    $inc = trim((string) $value);

    if (preg_match('/^[A-Za-z0-9_]+$/', $inc)) {
        return is_file($includesDir . '/' . $inc . '.php') ? $inc : $default;
    }

    $tables = array('student_field_categories', 'staff_field_categories', 'people_field_categories');
    if (preg_match('/^[A-Za-z0-9_]+\/[A-Za-z0-9_]+$/', $inc) && in_array($categoryTable, $tables, true)) {
        // Safe in SQL: the regex only allows letters, digits, _ and one /
        $cat = DBGet(DBQuery("SELECT ID FROM " . $categoryTable . " WHERE INCLUDE='" . $inc . "'"));
        if (!empty($cat) && is_file(dirname($includesDir, 2) . '/' . $inc . '.php')) {
            return $inc;
        }
    }

    return $default;
}
