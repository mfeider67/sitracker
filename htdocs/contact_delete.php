<?php
// delete_contact.php - Form for deleting contacts, moves any associated records to another contact the user chooses
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>

// This Page Is Valid XHTML 1.0 Transitional!   31Oct05

@include ('set_include_path.inc.php');
$permission = 55; // Delete Sites/Contacts

require ($lib_path.'db_connect.inc.php');
require ($lib_path.'functions.inc.php');
// This page requires authentication
require ($lib_path.'auth.inc.php');

// External variables
$process = $_REQUEST['process'];
$id = cleanvar($_REQUEST['id']);
$newcontact = mysql_real_escape_string($_REQUEST['newcontact']);

include ('./inc/htmlheader.inc.php');
if (empty($process))
{
    if (empty($id))
    {
        ?>
        <h2>Select Contact To Delete</h2>
        <form action="<?php echo $_SERVER['PHP_SELF'] ?>?action=delete" method="post">
        <table>
        <tr><th>Contact:</th><td><?php echo contact_site_drop_down("id", 0); ?></td></tr>
        </table>
        <p><input name="submit1" type="submit" value="Continue" /></p>
        <?php
        echo "</form>";
    }
    else
    {
        echo "<h2>Delete Contact</h2>\n";
        $sql="SELECT * FROM `{$dbContacts}` WHERE id='$id' ";
        $contactresult = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
        while ($contactrow=mysql_fetch_array($contactresult))
        {
            ?>
            <table align='center' class='vertical'>
            <?php
            echo "<tr><th>{$strName}:</th><td><h3>".$contactrow['forenames'].' '.$contactrow['surname']."</h3></td></tr>";
            echo "<tr><th>{$strSite}:</th><td><a href=\"site_details.php?id=".$contactrow['siteid']."\">".site_name($contactrow['siteid'])."</a></td></tr>";
            echo "<tr><th>{$strDepartment}:</th><td>".$contactrow['department']."</td></tr>";
            echo "<tr><th>{$strEmail}:</th><td><a href=\"mailto:".$contactrow['email']."\">".$contactrow['email']."</a></td></tr>";
            echo "<tr><th>{$strTelephone}:</th><td>".$contactrow['phone']."</td></tr>";
            echo "<tr><th>{$strNotes}:</th><td>".$contactrow['notes']."</td></tr>";
        }
        mysql_free_result($contactresult);
        echo "</table>\n";
        $totalincidents=contact_count_incidents($id);
        if ($totalincidents > 0)
        {
            echo "<p align='center' class='error'>There are $totalincidents incidents assigned to this contact</p>";
        }
        $sql  = "SELECT sc.maintenanceid AS maintenanceid, m.product, p.name AS productname, ";
        $sql .= "m.expirydate, m.term ";
        $sql .= "FROM `{$dbSupportContacts}` AS sc, `{$dbMaintenance}` AS m, `{$dbProducts}` AS p ";
        $sql .= "WHERE sc.maintenanceid = m.id AND m.product = p.id AND sc.contactid = '$id' ";
        $result=mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
        $totalcontracts=mysql_num_rows($result);
        if ($totalcontracts>0)
        {
            echo "<p align='center' class='error'>There are $totalcontracts contracts assigned to this person</p>";
        }

        if ($totalincidents > 0 || $totalcontracts > 0)
        {
            echo "<form action='{$_SERVER['PHP_SELF']}' onsubmit=\"return confirm_action('{$strAreYouSureDelete}')\" method='post'>\n";
            echo "<p align='center'>Before you can delete you must select another contact to receive any incidents and/or maintenance contracts.</p>";
            $sql  = "SELECT id, forenames, surname, siteid FROM `{$dbContacts}` ORDER BY surname ASC";
            $result = mysql_query($sql);
            echo "<p align='center'>";
            echo "<select name='newcontact'>";
            if ($id == 0)
            echo "<option selected='selected' value='0'>Select A Contact\n";
            while ($contacts = mysql_fetch_array($result))
            {
                $site='';
                if ($contacts['siteid']!='' && $contacts['siteid']!=0)
                {
                    $site=" of ".site_name($contacts['siteid']);
                }
                if ($contacts['id']!=$id)
                {
                    echo "<option value=\"{$contacts['id']}\">";
                    echo htmlspecialchars($contacts['surname'].', '.$contacts['forenames'].$site);
                    echo "</option>\n";
                }
            }
            echo "</select><br />";
            echo "<br />";
            echo "<input type='hidden' name='id' value='$id' />";
            echo "<input type='hidden' name='process' value='true' />";
            echo "<input type='submit' value='{$strDelete}' />";
            echo "</p>";
            echo "</form>";
        }
        else
        {
            // plain delete
            echo "<br />";
            echo "<form action='{$_SERVER['PHP_SELF']}' onsubmit=\"return confirm_action('{$strAreYouSureDelete}')\" method='post'>\n";
            echo "<input type='hidden' name='newcontact' value='' />";  // empty
            echo "<input type='hidden' name='id' value='$id' />";
            echo "<input type='hidden' name='process' value='true' />";
            echo "<p align='center'>";
            echo "<input type='submit' value='{$strDelete}' />";
            echo "</p>";
            echo "</form>\n";
        }
    }
    include ('./inc/htmlfooter.inc.php');
}
else
{
    // save to db
    if (!empty($newcontact))
    {
        $sql = "UPDATE `{$dbSupportContacts}` SET contactid='$newcontact' WHERE contactid='$id' ";
        mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

        $sql = "UPDATE `{$dbIncidents}` SET contact='$newcontact' WHERE contact='$id' ";
        mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

        $sql = "UPDATE `{$dbMaintenance}` SET admincontact='$newcontact' WHERE admincontact='$id' ";
        mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
    }

    // do the delete
    $sql = "DELETE FROM `{$dbContacts}` WHERE id='$id' LIMIT 1";
    mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

    journal(CFG_LOGGING_NORMAL, 'Contact Deleted', "Contact $id was deleted", CFG_JOURNAL_CONTACTS, $id);

    if (!empty($newcontact)) html_redirect("contact_details.php?id={$newcontact}");
    else  html_redirect("contacts.php?search_string=A");
}
?>