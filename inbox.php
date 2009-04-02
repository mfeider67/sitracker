<?php
// review_incoming_updates.php - Review/Delete Incident Updates
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Authors: Tom Gerrard, Ivan Lucas <ivanlucas[at]users.sourceforge.net>
//                       Paul Heaney <paulheaney[at]users.sourceforge.net>

// This Page Is Valid XHTML 1.0 Transitional! 31Oct05


$permission = 42;
require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

// External variables
$sort = cleanvar($_REQUEST['sort']);
$order = cleanvar($_REQUEST['order']);
$filter = cleanvar($_REQUEST['filter']);

$refresh = 60;
$title = $strInbox;
include (APPLICATION_INCPATH . 'htmlheader.inc.php');

if (empty($sort)) $sort='date';

function contact_info($contactid, $email, $name)
{
    global $strUnknown;
    //$info .= "<span style='float:right;'>".gravatar($email, 16) . '</span>';
    if (!empty($contactid))
    {
        $info .= "<a href='contact.php?id={$contactid}'>";
        $info .= icon('contact', 16);
        $info .= "</a>";
    }
    else
    {
        $info .= icon('email', 16);
    }
    $info .= ' ';
    if (!empty($email)) $info .= "<a href=\"mailto:{$email}\" class='info'>";
    if (!empty($name)) $info .= "{$name}";
    elseif (!empty($email)) $info .= "{$email}";
    else $info .= "{$strUnknown}";
    if (!empty($email))
    {
        $info .= "<span>".gravatar($email, 50, FALSE)."</span>";
        $info .= "</a>";
    }

    if (!empty($contactid))
    {
        $info .= " (".contact_site($contactid).")";
    }

    return $info;
}













echo "<h2>".icon('email', 32)." {$strInbox}</h2>";

$sql = "SELECT * FROM `$dbTempIncoming` ";

if (!empty($sort))
{
    if ($order=='a' OR $order=='ASC' OR $order='') $sortorder = "ASC";
    else $sortorder = "DESC";
    switch ($sort)
    {
        case 'from': $sql .= " ORDER BY `from` $sortorder"; break;
        case 'subject': $sql .= " ORDER BY `subject` $sortorder"; break;
        default:   $sql .= " ORDER BY `id` DESC"; break;
    }

}
$result = mysql_query($sql);
if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
$countresults = mysql_num_rows($result);

echo "<p align='center'>{$strIncomingEmailText}</p>";
$shade = 'shade1';
echo "<table align='center' style='width: 95%'>";
echo "<tr>";
echo colheader('select', '', FALSE, '', '', '', '1%');
echo colheader('from', $strFrom, $sort, $order, $filter, '', '25%');
echo colheader('subject', $strSubject, $sort, $order, $filter);
echo colheader('date', $strDate, $sort, $order, $filter, '', '15%');
echo "</tr>";
while ($incoming = mysql_fetch_object($result))
{
    if (!empty($incoming->updateid))
    {
        $usql = "SELECT * FROM `{$dbUpdates}` WHERE id = '{$incoming->updateid}' LIMIT 1";
        $uresult = mysql_query($usql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
        $update = mysql_fetch_object($uresult);
    }

    echo "<tr class='{$shade}'>";
    echo "<td>".html_checkbox('item', FALSE)."</td>";
    echo "<td>".contact_info($incoming->contactid, $incoming->from, $incoming->emailfrom)."</td>";
    echo "</td>";
    // Subject
    echo "<td>";
    if (($incoming->locked != $sit[2]) && ($incoming->locked > 0))
    {
        echo "Locked by ".user_realname($update['locked'],TRUE);
    }
    else
    {
        echo "<a href=\"javascript:incident_details_window('{$incoming->id}'";
        echo ",'incomingview');\" id='update{$incoming->updateid}' class='info'";
        echo " title='View and lock this held e-mail'>";
        echo htmlentities($incoming->subject,ENT_QUOTES, $GLOBALS['i18ncharset']);
        if (!empty($update->bodytext)) echo '<span>'.parse_updatebody(truncate_string($update->bodytext,1024)).'</span>';
        echo "</a>";
    }

    echo "</td>";
    // echo "<td><pre>".print_r($incoming,true)."</pre><hr /></td>";
    // Date
    echo "<td>";
    if (!empty($update->timestamp)) echo date($CONFIG['dateformat_datetime'], $update->timestamp);
    echo "</td>";
    echo "</tr>";
    if ($shade == 'shade1') $shade = 'shade2';
    else $shade = 'shade1';
}

echo "<tr>";
echo "<td>".html_checkbox('item', FALSE)."</td>";
echo "<td colspan='*'>";
echo "<select>";
echo "<option value='' selected='selected'></option>";
echo "<option value='deleteall'>{$strDelete}</option>";
echo "<option value='assignall'>{$strAssign}</option>";
echo "</select>";
echo "</td>";
echo "</tr>";

echo "</table>";


include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
?>