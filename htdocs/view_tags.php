<?php
// view_tags.php - Page to view the tags on either a record or in general
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2000-2006 Salford Software Ltd.
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author: Paul Heaney <paulheaney[at]users.sourceforge.net>

$permission=0; // not required
require('db_connect.inc.php');
require('functions.inc.php');

// This page requires authentication
require('auth.inc.php');

$tagid = $_REQUEST['tagid'];
$orderby = $_REQUEST['orderby'];

if(empty($orderby)) $orderby = "name";

if(empty($tagid))
{
    //show all tags
    $sql = "SELECT COUNT(name) AS occurrences, name, tags.tagid FROM tags, set_tags WHERE tags.tagid = set_tags.tagid GROUP BY name ORDER BY $orderby";
    if($orderby == "occurrences") $sql .= " DESC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);

    include('htmlheader.inc.php');
    echo "<h2>Tags</h2>";
    $countsql = "SELECT COUNT(*) AS counted FROM set_tags GROUP BY tagid ORDER BY counted DESC LIMIT 1";

    $countresult = mysql_query($countsql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
    list($max) = mysql_fetch_row($countresult);

    echo "<p align='center'>Sort: <a href='".$_SERVER['PHP_SELF']."?orderby=name'>alphabetically</a> | ";
    echo "<a href='".$_SERVER['PHP_SELF']."?orderby=occurrences'>popularity</a></p>";
    if(mysql_num_rows($result) > 0)
    {
        echo "<table align='center'><tr><td>";
        $min=1;

        while($obj = mysql_fetch_object($result))
        {
            $size = (($obj->occurrences) * 300 / $max) +100;
            if ($size==0) $size=100;
            echo "<a href='".$_SERVER['PHP_SELF']."?tagid=$obj->tagid' style='font-size: {$size}%;' title='{$obj->occurrences}'>{$obj->name}</a> &nbsp;";
        }
        echo "</td></tr></table>";
    }
    include('htmlfooter.inc.php');
}
else
{
    //show only this tag
    $sql = "SELECT * FROM set_tags WHERE tagid = '$tagid'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);

    include('htmlheader.inc.php');
    echo "<h2>Tags</h2>";
    if(mysql_num_rows($result) > 0)
    {
        echo "<table align='center'>";
        while($obj = mysql_fetch_object($result))
        {
            echo "<tr>";
            switch($obj->type)
            {
                case 1: //contact
                    $sql = "SELECT forenames, surname FROM contacts WHERE id = '$obj->id'";
                    $resultcon = mysql_query($sql);
                    if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
                    if(mysql_num_rows($resultcon) > 0)
                    {
                        $objcon = mysql_fetch_object($resultcon);
                        echo "<th>CONTACT</th><td><a href='contact_details.php?id=$obj->id'>";
                        echo $objcon->forenames." ".$objcon->surname."</a></td>";
                    }
                    break;
                case 2: //incident
                    $sql = "SELECT title FROM incidents WHERE id = '$obj->id'";
                    $resultinc = mysql_query($sql);
                    if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
                    if(mysql_num_rows($resultinc) > 0)
                    {
                        $objinc = mysql_fetch_object($resultinc);
//javascript:incident_details_window('119','incident119')
                        echo "<th>INCIDENT</th><td><a href=\"javascript:incident_details_window('$obj->id','incident$obj->id')\">";
                        echo $objinc->title."</a></td>";
                    }
                    break;
                case 3: //site
                    $sql = "SELECT name FROM sites WHERE id = '$obj->id'";
                    $resultsite = mysql_query($sql);
                    if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
                    if(mysql_num_rows($resultsite) > 0)
                    {
                        $objsite = mysql_fetch_object($resultsite);
                         echo "<th>SITE</th><td><a href='site_details.php?id=$obj->id&action=show'>";
                        echo $objsite->name."</a></td>";
                    }
            }
            echo "</tr>";
        }
        echo "</table>";
    }
    include('htmlfooter.inc.php');
}

?>