<?php
// planner_schedule_delete.php - deletes an event from the tasks table
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author: Tom Gerrard <tom.gerrard[at]salfordsoftware.co.uk>

$lib_path = dirname( __FILE__ ).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR;
$permission = 27; // View your calendar
require ($lib_path.'db_connect.inc.php');
require ($lib_path.'functions.inc.php');
require ($lib_path.'auth.inc.php');

$eventToDelete = cleanvar($_GET['eventToDeleteId']);

if (isset($eventToDelete))
{
    // TODO there should be a permission check here
    if (true)
    {
        mysql_query("DELETE FROM `{$dbTasks}` WHERE id='".$eventToDelete."'");
        if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
        echo "OK";
    }
}

?>