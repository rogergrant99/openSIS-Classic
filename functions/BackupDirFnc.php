<?php
/**
 * Directory where database backups are written.
 *
 * Backups contain the whole database, so they must never be inside the web root.
 * Set $BackupDirectory in Data.php to choose the location explicitly; otherwise a
 * folder named "opensis_backups" next to (not inside) the openSIS folder is used,
 * falling back to the system temp directory if that can't be created.
 *
 * Returns the absolute path without a trailing slash, or false if no writable
 * directory outside the web root is available.
 */
function openSISBackupDir()
{
    global $BackupDirectory;

    $web_root = realpath(dirname(__DIR__));
    $candidates = array();
    if (!empty($BackupDirectory))
        $candidates[] = rtrim($BackupDirectory, '/\\');
    $candidates[] = dirname($web_root) . DIRECTORY_SEPARATOR . 'opensis_backups';
    $candidates[] = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'opensis_backups';

    foreach ($candidates as $dir) {
        if (!is_dir($dir) && !@mkdir($dir, 0700, true))
            continue;
        $real = realpath($dir);
        if ($real === false || !is_writable($real))
            continue;
        // Refuse anything inside the web root.
        if (strpos($real . DIRECTORY_SEPARATOR, $web_root . DIRECTORY_SEPARATOR) === 0)
            continue;
        return $real;
    }
    return false;
}
