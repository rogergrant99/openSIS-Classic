<?php
include('RedirectRootInc.php');
include'ConfigInc.php';
include 'Warehouse.php';
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

 DBQuery("CREATE TABLE IF NOT EXISTS temp_schedule AS SELECT * FROM schedule WHERE 0");
 DBQuery("DELETE FROM temp_schedule WHERE student_id='".UserStudentID()."' AND modified_by='".User('STAFF_ID')."'");
 
 ?>