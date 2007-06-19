<?php
// products.php - List products
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2000-2006 Salford Software Ltd.
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>

$permission=28; // View Products and Software
$title='Products List';

require('db_connect.inc.php');
require('functions.inc.php');

// This page requires authentication
require('auth.inc.php');

// External Variables
$productid = cleanvar($_REQUEST['productid']);

include('htmlheader.inc.php');

if (empty($productid))
{
    $sql = "SELECT * FROM vendors ORDER BY name";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

    if (mysql_num_rows($result) >= 1)
    {
        while ($vendor = mysql_fetch_object($result))
        {
            echo "<h2>{$vendor->name}</h2>";
            $psql = "SELECT * FROM products WHERE vendorid='{$vendor->id}' ORDER BY name";
            $presult = mysql_query($psql);
            if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
            if (mysql_num_rows($presult) >= 1)
            {
                echo "<table summary='List of products' align='center' width='95%'>";
                echo "<tr><th width='20%'>Product Name</th><th width='60%'>Description</th><th width='10%'>Linked Skills</th><th width='10%'>Active Contracts</th></tr>\n";
                $shade='shade1';
                while ($product = mysql_fetch_object($presult))
                {
                    // Count linked skills
                    $ssql = "SELECT COUNT(softwareid) FROM softwareproducts WHERE productid={$product->id}";
                    $sresult = mysql_query($ssql);
                    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
                    list($countlinked)=mysql_fetch_row($sresult);

                    // Count contracts
                    $ssql = "SELECT COUNT(id) FROM maintenance WHERE product='{$product->id}' AND term!='yes' AND expirydate > '{$now}'";
                    $sresult = mysql_query($ssql);
                    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
                    list($countcontracts)=mysql_fetch_row($sresult);

                    echo "<tr class='{$shade}'><td><a href='{$_SERVER['PHP_SELF']}?productid={$product->id}'>{$product->name}</a></td>";
                    echo "<td>{$product->description}</td>";
                    echo "<td align='right'>{$countlinked}</td>";
                    echo "<td align='right'>";
                    if ($countcontracts > 0) echo "<a href='browse_maintenance.php?search_string=&productid={$product->id}'>{$countcontracts}</a>";
                    else echo "{$countcontracts}";
                    echo "</td>";
                    // FIXME
                    // echo "<td><a href='edit_product.php?id={$product->id}'>Edit</a> | <a href='delete_product.php?id={$product->id}'>Delete</a></td>";
                    echo "</tr>\n";
                    if ($shade=='shade1') $shade='shade2';
                    else $shade='shade1';
                }
                echo "</table>\n";
            }
            else echo "<p class='warning'>No products for this vendor</p>\n";
        }
    }
    else echo "<p class='error'>No software vendors defined</p>";

    echo "<h2>Software not linked</h2>";
    echo "<p align='center'>This software is not linked to any product</p>";
    $sql = "SELECT software.* FROM software LEFT JOIN softwareproducts ON software.id=softwareproducts.softwareid WHERE softwareproducts.softwareid IS NULL";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
    echo "<table summary='' align='center' width='55%'>";
    echo "<tr><th>Software</th><th>Lifetime</th><th>Engineers</th><th>Incidents</th><th>Actions</th></tr>";
    while ($software = mysql_fetch_array($result))
    {
        $ssql = "SELECT COUNT(userid) FROM usersoftware, users WHERE usersoftware.userid = users.id AND users.status!=0 AND usersoftware.softwareid='{$software['id']}'";
        $sresult = mysql_query($ssql);
        if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
        list($countengineers) = mysql_fetch_row($sresult);

        $ssql = "SELECT COUNT(id) FROM incidents WHERE softwareid='{$software['id']}'";
        $sresult = mysql_query($ssql);
        if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
        list($countincidents) = mysql_fetch_row($sresult);

        echo "<tr class='$shade'><td>{$software['name']}</td>";
        echo "<td>";
        if ($software['lifetime_start'] > 1) echo date($CONFIG['dateformat_shortdate'],mysql2date($software['lifetime_start'])).' to ';
        else echo "&#8734;";
        if ($software['lifetime_end'] > 1) echo date($CONFIG['dateformat_shortdate'],mysql2date($software['lifetime_end']));
        elseif ($software['lifetime_start'] >1) echo "&#8734;";
        echo "</td>";
        echo "<td>{$countengineers}</td>";
        echo "<td>{$countincidents}</td>";
        echo "<td><a href='add_product_software.php?softwareid={$software['id']}'>Link</a> ";
        echo "| <a href='edit_software.php?id={$software['id']}'>Edit</a> ";
        echo "| <a href='edit_software.php?id={$software['id']}&amp;action=delete'>Delete</a>";
        echo "</td>";
        echo "</tr>\n";
        if ($shade=='shade1') $shade='shade2';
        else $shade='shade1';
    }
    echo "</table>";
}
else
{
    $psql = "SELECT * FROM products WHERE id='{$productid}' LIMIT 1";
    $presult = mysql_query($psql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
    if (mysql_num_rows($presult) >= 1)
    {
        while ($product = mysql_fetch_object($presult))
        {
            echo "<table summary='List of skills linked to product' align='center'>";
            echo "<tr><thead><th colspan='0'>Product: {$product->name} (<a href='edit_product.php?id={$product->id}'>Edit</a> | <a href='delete_product.php?id={$product->id}'>Delete</a>)</th></thead></tr>";
            if (!empty($product->description)) echo "<tr class='shade1'><td colspan='0'>".nl2br($product->description)."</td></tr>";

            $swsql = "SELECT * FROM softwareproducts, software WHERE softwareproducts.softwareid=software.id AND productid='{$product->id}' ORDER BY name";
            $swresult=mysql_query($swsql);
            if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

            if (mysql_num_rows($swresult) > 0)
            {
                echo "<tr><th>Skill</th><th>Lifetime</th><th>Engineers</th><th>Incidents</th><th>Actions</th></tr>";
                $shade='shade2';
                while ($software=mysql_fetch_array($swresult))
                {
                    $ssql = "SELECT COUNT(userid) FROM usersoftware, users WHERE usersoftware.userid = users.id AND users.status!=0 AND usersoftware.softwareid='{$software['id']}'";
                    $sresult = mysql_query($ssql);
                    if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
                    list($countengineers) = mysql_fetch_row($sresult);

                    $ssql = "SELECT COUNT(id) FROM incidents WHERE softwareid='{$software['id']}'";
                    $sresult = mysql_query($ssql);
                    if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
                    list($countincidents) = mysql_fetch_row($sresult);

                    echo "<tr class='$shade'><td>{$software['name']}</td>";
                    echo "<td>";
                    if ($software['lifetime_start'] > 1) echo date($CONFIG['dateformat_shortdate'],mysql2date($software['lifetime_start'])).' to ';
                    else echo "&#8734;";
                    if ($software['lifetime_end'] > 1) echo date($CONFIG['dateformat_shortdate'],mysql2date($software['lifetime_end']));
                    elseif ($software['lifetime_start'] >1) echo "&#8734;";
                    echo "</td>";
                    echo "<td>{$countengineers}</td>";
                    echo "<td>{$countincidents}</td>";
                    echo "<td><a href='delete_product_software.php?productid={$product->id}&amp;softwareid={$software['softwareid']}'>Unlink</a> ";
                    echo "| <a href='edit_software.php?id={$software['softwareid']}'>Edit</a> ";
                    echo "| <a href='edit_software.php?id={$software['softwareid']}&amp;action=delete'>Delete</a>";
                    echo "</td>";
                    echo "</tr>\n";
                    if ($shade=='shade1') $shade='shade2';
                    else $shade='shade1';
                }
            }
            else
            {
                echo "<tr><td>&nbsp;</td><td><em>No software linked to this product</em></td><td>&nbsp;</td></tr>\n";
            }
            echo "</table>\n";
            echo "<p align='center'><a href='add_product_software.php?productid={$product->id}'>Link software to {$product->name}</a></p>\n";
        }

    }
    else echo "<p class='error'>No matching product</p>";

    echo "<p align='center'><a href='{$_SERVER['PHP_SELF']}'>Back to list of products</a></p>";
}

echo "<p align='center'><a href='add_vendor.php'>Add Vendor</a> | <a href='add_product.php'>Add Product</a> | <a href='add_software.php'>Add Software</a></p>";

include('htmlfooter.inc.php');
?>