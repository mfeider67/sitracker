<?php
// review_incoming_updates.php - Review/Delete Incident Updates
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2000-2007 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Authors: Tom Gerrard, Ivan Lucas <ivanlucas[at]users.sourceforge.net>
//                       Paul Heaney <paulheaney[at]users.sourceforge.net>

// This Page Is Valid XHTML 1.0 Transitional! 31Oct05

$permission=42;
require('db_connect.inc.php');
require('functions.inc.php');

// This page requires authentication
require('auth.inc.php');


function generate_row($update)
{
    global $CONFIG, $sit;
    if (strlen($update['bodytext']) > 1003) $updatebodytext = substr($update['bodytext'],0,1000).'...';
    else $updatebodytext=$update['bodytext'];

    $search = array( '<b>',  '</b>',  '<i>',  '</i>',  '<u>',  '</u>',  '&lt;',  '&gt;');
    $replace = '';
    $updatebodytext=htmlspecialchars(str_replace($search, $replace, $updatebodytext));
    if ($updatebodytext=='') $updatebodytext='&nbsp;';

    $html_row="<tr class='shade1'>";
    $html_row.="<td style='text-align: center'><input type='checkbox' name='selected[]' value='".$update['id']."' /></td>";
    $html_row.="<td align='center' width='20%'>".date($CONFIG['dateformat_datetime'],$update['timestamp']).'</td>';
    $html_row.="<td width='20%'>".htmlentities($update['emailfrom'],ENT_QUOTES)."</td>";
    $html_row.="<td width='20%'><a id='update{$update['id']}' class='info' style='cursor:help;'>";
    if (empty($update['subject'])) $update['subject']='Untitled';
    $html_row.=htmlentities($update['subject'],ENT_QUOTES);
    $html_row.='<span>'.parse_updatebody($updatebodytext).'</span></a></td>';
    $html_row.="<td align='center' width='20%'>".$update['reason'].'</td>';
    $html_row.="<td align='center' width='20%'>";
    if (($update['locked'] != $sit[2]) && ($update['locked']>0))
    $html_row.= "Locked by ".user_realname($update['locked'],TRUE);
    else
    {
        if ($update['locked'] == $sit[2])
        {
            $html_row.="<a href='{$_SERVER['PHP_SELF']}?unlock={$update['tempid']}' title='Unlock this update so it can be modified by someone else'> Unlock</a> | ";
            $html_row.="<a href=\"move_update.php?updateid=".$update['id']."&amp;incidentidnumber=".$update['incidentid']."\" title=\"Assign this text to an existing incident\">Assign</a> | ";
            $html_row.="<a href='add_incident.php?action=findcontact&amp;updateid=".$update['id']."&amp;search_string=".urlencode($update['emailfrom']);
            if ($update['contactid'])
                $html_row.="&amp;contactid=".$update['contactid'];
            $html_row.= "' title=\"Add a new incident from this text\">Create</a> | ";
        }
        else $html_row.= "<a href='{$_SERVER['PHP_SELF']}?lock={$update['tempid']}' title='Lock this update so it cannot be modified by anyone else'> Lock</a> | ";
        $html_row.= "<a href='delete_update.php?updateid=".$update['id']."&amp;tempid=".$update['tempid']."&amp;timestamp=".$update['timestamp']."' title='Remove this item permanently' onclick='return confirm_delete();'> Delete</a>";
    }
    $html_row.="</td></tr>\n";
    return $html_row;
}

function deldir($location)
{
    if (substr($location,-1) <> "/")
    $location = $location."/";
    $all=opendir($location);
    while ($file=readdir($all))
    {
        if (is_dir($location.$file) && $file <> ".." && $file <> ".")
        {
            deldir($location.$file);
            rmdir($location.$file);
            unset($file);
        }
        elseif (!is_dir($location.$file))
        {
            unlink($location.$file);
            unset($file);
        }
    }
    rmdir($location);
}

$title = 'Review Held Updates';
$refresh = $_SESSION['incident_refresh'];
$selected = $_REQUEST['selected'];
include('htmlheader.inc.php');

if ($lock=$_REQUEST['lock'])
{
    $lockeduntil=date('Y-m-d H:i:s',$now+$CONFIG['record_lock_delay']);
    $sql = "UPDATE tempincoming SET locked='{$sit[2]}', lockeduntil='{$lockeduntil}' WHERE tempincoming.id='{$lock}' AND (locked = 0 OR locked IS NULL)";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
}
elseif ($unlock=$_REQUEST['unlock'])
{
    $sql = "UPDATE tempincoming SET locked=NULL, lockeduntil=NULL WHERE tempincoming.id='{$unlock}' AND locked = '{$sit[2]}'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
}
else
{
    // Unlock any expired locks
    $nowdatel=date('Y-m-d H:i:s');
    $sql = "UPDATE tempincoming SET locked=NULL, lockeduntil=NULL WHERE UNIX_TIMESTAMP(lockeduntil) < '$now' ";
    mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
}

if ($spam_string=$_REQUEST['delete_all_spam'])
{
    $spam_array=explode(',',$spam_string);
    foreach ($spam_array as $spam)
    {
        $ids=explode('_',$spam);

        $sql = "DELETE FROM tempincoming WHERE id='".$ids[1]."' AND SUBJECT LIKE '%SPAMASSASSIN%' AND updateid='".$ids[0]."' LIMIT 1";
        mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
        if (mysql_affected_rows()==1)
        {
            $sql = "DELETE FROM updates WHERE id='".$ids[0]."'";
            mysql_query($sql);
            if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
            $path=$CONFIG['attachment_fspath'].'updates/'.$ids[0];
            if (file_exists($path)) deldir($path);
        }
    }
    unset($spam_array);
}

if(!empty($selected))
{
    foreach($selected as $updateid)
    {
        // We delete using ID and timestamp to make sure we dont' delete the wrong update by accident
        $sql = "DELETE FROM updates WHERE id='$updateid'";
        mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

        $sql = "DELETE FROM tempincoming WHERE updateid='$updateid'";
        mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
        $path=$incident_attachment_fspath.'updates/'.$updateid;
        if (file_exists($path)) deldir($path);

        journal(CFG_LOGGING_NORMAL, 'Incident Log Entry Deleted', "Incident Log Entry $updateid was deleted", CFG_JOURNAL_INCIDENTS, $updateid);
    }
}


    ?>
    <script type="text/javascript">
    <!--
        function confirm_delete()
        {
            return window.confirm("This item will be permanently deleted.  Are you sure you want to continue?");
        }
    -->
    </script>
    
    <script type="text/javascript">
    <!--
    function submitform()
    {
    document.held_emails.submit();
    }
    
    function checkAll(checkStatus)
    {
        var frm = document.held_emails.elements;
        for(i = 0; i < frm.length; i++)
        {
            if(frm[i].type == 'checkbox')
            {
                if(checkStatus)
                {
                    frm[i].checked = true;
                }
                else
                {
                    frm[i].checked = false;
                }
            }
        }
    }
    -->
    </script>

<?php

// extract updates
$sql  = 'SELECT updates.id as id, updates.bodytext as bodytext, tempincoming.emailfrom as emailfrom, tempincoming.subject as subject, ';
$sql .= 'updates.timestamp as timestamp, tempincoming.incidentid as incidentid, tempincoming.id as tempid, tempincoming.locked as locked, ';
$sql .= 'tempincoming.reason as reason, tempincoming.contactid as contactid ';
$sql .= 'FROM updates, tempincoming WHERE updates.incidentid=0 AND tempincoming.updateid=updates.id ';
$sql .= 'ORDER BY timestamp ASC, id ASC';
$result = mysql_query($sql);
if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
$countresults=mysql_num_rows($result);
$spamcount=0;
if($countresults > 0)
{
    if ($countresults) mysql_data_seek($result, 0);
    
    while ($updates = mysql_fetch_array($result))
        if (!stristr($updates['subject'],$CONFIG['spam_email_subject'])) $queuerows[$updates->timestamp] = generate_row($updates);
        else $spamcount++;
}

$sql = "SELECT * FROM incidents WHERE owner='0' AND status!='2'";
$resultnew = mysql_query($sql);
if (mysql_num_rows($resultnew) >= 1)
{
    while ($new = mysql_fetch_object($resultnew))
    {
        // Get Last Update
        list($update_userid, $update_type, $update_currentowner, $update_currentstatus, $update_body, $update_timestamp, $update_nextaction, $update_id)=incident_lastupdate($new->id);
        $update_body = parse_updatebody($update_body);
        $html = "<tr class='shade1'><td />";
        $html .= "<td align='center'>".date($CONFIG['dateformat_datetime'], $new->opened)."</td>";
        $html .= "<td>".contact_realname($new->contact)."</td>";
        $html .= "<td>".product_name($new->product)." / ".software_name($new->softwareid)."<br />";
        $html .= "[{$new->id}] <a href=\"javascript:incident_details_window('{$new->id}','holdingview');\" class='info'>{$new->title}<span>{$update_body}</span></a></td>";
        $html .= "<td style='text-align:center;'>Unassigned</td>";
        $html .= "<td style='text-align:center;'>";
        $html .= "<a href= \"javascript:incident_details_window('{$new->id}','holdingview');\" title='View this incident'>View</a> | ";
        $html .= "<a href= \"javascript:wt_winpopup('reassign_incident.php?id={$new->id}&amp;reason=Initial%20assignment%20to%20engineer&amp;popup=yes','mini');\" title='Assign this incident'>Assign</a></td>";
        $html .= "</tr>";
        $queuerows[$update_timestamp] = $html;
    }
}

$realemails = $countresults-$spamcount;

if((mysql_num_rows($resultnew) > 0) OR ($realemails > 0))
{
    $totalheld = $countresults + mysql_num_rows($resultnew) - $spamcount;
    echo "<h2>Held Email";
    if ($totalheld >1) echo 's';
    echo " ($totalheld total) </h2>";
    echo "<p align='center'>Incoming email that cannot be handled automatically</p>";
    ?>
    <form action='review_incoming_updates.php' name='held_emails'>
    <table align='center' style='width: 95%'>
    <tr>
    <th>
    <?php if($realemails > 0) echo "<input type='checkbox' name='selectAll' value='CheckAll' onclick=\"checkAll(this.checked);\" />"?>
    </th>
    <th>Date</th>
    <th>From</th>
    <th>Subject</th>
    <th>Reason</th>
    <th>Operation</th>
    </tr>

    <?php
    sort($queuerows);
    foreach($queuerows AS $row)
    {
        echo $row;
    }
    if($realemails > 0) echo "<tr><td><a href=\"javascript: submitform()\" onclick='return confirm_delete();'>Delete</a></td></tr>";
    echo "</table>\n";
    echo "</form>";
}
else if($spamcount == 0)
{
    echo "<h2>No emails pending</h2>";
}

if($spamcount > 0)
{
    echo "<h2>Spam Email";
    if($spamcount > 1) echo "s";
    echo " ({$spamcount} total)</h2>\n";
    echo "<p align='center'>Incoming email that is suspected to be spam</p>";
    
    // Reset back for 'nasty' emails
    if ($countresults) mysql_data_seek($result, 0);
    
    echo "<table align='center' style='width: 95%;'>";
    echo "<tr><th /><th>Date</th><th>From</th>";
    echo "<th>Subject</th><th>Reason</th>";
    echo "<th>Operation</th></tr>\n";
    
    while ($updates = mysql_fetch_array($result))
    {
        if (stristr($updates['subject'],$CONFIG['spam_email_subject']))
        {
            echo generate_row($updates);
            $spam_array[]=$updates['id'].'_'.$updates['tempid'];
        }
    }
    echo "</table>";
    if (is_array($spam_array)) echo "<p align='center'><a href={$_SERVER['PHP_SELF']}?delete_all_spam=".implode(',',$spam_array).'>Delete all mail from spam queue</a></p>';
    
    
    echo "<br /><br />"; //gap
}



$sql = "SELECT * FROM tempassigns,incidents WHERE tempassigns.incidentid=incidents.id AND assigned='no' ";
$result = mysql_query($sql);

if (mysql_num_rows($result) >= 1)
{
    echo "<br /><br />\n";
    
    echo "<h2>Pending Re-Assignments</h2>";
    echo "<p align='center'>Automatic reassignments that could not be made because users were set to 'not accepting'</p>";
    echo "<table align='center' style='width: 95%;'>";
    echo "<tr><th title='Last Updated'>Date</th><th title='Current Owner'>From</th>";
    echo "<th title='Incident Title'>Subject</th><th>Reason</th>";
    echo "<th>Operation</th></tr>\n";

    while ($assign = mysql_fetch_object($result))
    {
        // $originalownerstatus=user_status($assign->originalowner);
        $useraccepting=strtolower(user_accepting($assign->originalowner));
        if (($assign->owner == $assign->originalowner || $assign->towner == $assign->originalowner) AND $useraccepting=='no')
        {
            echo "<tr class='shade1'>";
            echo "<td align='center'>".date($CONFIG['dateformat_datetime'], $assign->lastupdated)."</td>";
            echo "<td>".user_realname($assign->originalowner,TRUE)."</td>";
            echo "<td>[<a href=\"javascript:wt_winpopup('incident_details.php?id={$assign->id}&amp;popup=yes', 'mini')\">{$assign->id}</a>] ".stripslashes($assign->title)."</td>";
            $userstatus=userstatus_name($assign->userstatus);
            $usermessage=user_message($assign->originalowner);
            $username=user_realname($assign->originalowner,TRUE);
            echo "<td>Owner {$userstatus} &amp; not accepting<br />{$usermessage}</td>";
            $backupid=software_backup_userid($assign->originalowner, $assign->softwareid);
            $backupname=user_realname($backupid,TRUE);
            $reason = urlencode(trim("Previous Incident Owner ($username) {$userstatus}  {$usermessage}"));
            echo "<td>";
            if ($backupid >= 1) echo "<a href=\"javascript:wt_winpopup('reassign_incident.php?id={$assign->id}&amp;reason={$reason}&amp;backupid={$backupid}&amp;asktemp=temporary&amp;popup=yes','mini');\" title='Re-assign this incident to {$backupname}'>Assign to Backup</a> | ";

            echo "<a href=\"javascript:wt_winpopup('reassign_incident.php?id={$assign->id}&amp;reason={$reason}&amp;asktemp=temporary&amp;popup=yes','mini');\" title='Re-assign this incident to another engineer'>Assign to other</a> | <a href='set_user_status.php?mode=deleteassign&amp;incidentid={$assign->incidentid}&amp;originalowner={$assign->originalowner}' title='Ignore this reassignment and delete this notice'>Ignore</a></td>";
            echo "</tr>\n";
        }
        elseif ($assign->owner != $assign->originalowner AND $useraccepting=='yes')
        {
            // display a row to assign the incident back to the original owner
            echo "<tr class='shade2'>";
            echo "<td>".date($CONFIG['dateformat_datetime'], $assign->lastupdated)."</td>";
            echo "<td>".user_realname($assign->owner,TRUE)."</td>";
            echo "<td>[<a href=\"javascript:wt_winpopup('incident_details.php?id={$assign->id}&amp;popup=yes', 'mini')\">{$assign->id}</a>] {$assign->title}</td>";
            $userstatus=user_status($assign->originalowner);
            $userstatusname=userstatus_name($userstatus);
            $origstatus=userstatus_name($assign->userstatus);
            $usermessage=user_message($assign->originalowner);
            $username=user_realname($assign->owner,TRUE);
            echo "<td>Owner {$userstatusname} &amp; accepting again<br />{$usermessage}</td>";
            $originalname=user_realname($assign->originalowner,TRUE);
            $reason = urlencode(trim("{$originalname} is now accepting incidents again. Previous status {$origstatus} and not accepting."));
            echo "<td>";
            echo "<a href=\"javascript:wt_winpopup('reassign_incident.php?id={$assign->id}&amp;reason={$reason}&amp;originalid={$assign->originalowner}&amp;popup=yes','mini');\" title='Re-assign this incident to {$originalname}'>Return to original owner</a> | ";

            echo "<a href=\"javascript:wt_winpopup('reassign_incident.php?id={$assign->id}&amp;reason={$reason}&amp;asktemp=temporary&amp;popup=yes','mini');\" title='Re-assign this incident to another engineer'>Assign to other</a> | <a href='set_user_status.php?mode=deleteassign&amp;incidentid={$assign->incidentid}&amp;originalowner={$assign->originalowner}' title='Ignore this reassignment and delete this notice'>Ignore</a></td>";
            echo "</tr>\n";
        }
    }
    echo "</table>\n";
}


// TODO v3.2x Merge the sections into a single queue using an array

include('htmlfooter.inc.php');
?>
