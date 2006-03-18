<?php
// edit_product.php
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2000-2006 Salford Software Ltd.
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//


// Author: Ivan Lucas

$permission=24; // Add Product

require('db_connect.inc.php');
require('functions.inc.php');
// This page requires authentication
require('auth.inc.php');

// External variables
$id = cleanvar($_REQUEST['id']);
$name = $_REQUEST['name'];

if (empty($name))
{
    $title='Edit Product';
    include('htmlheader.inc.php');

    echo "<h2>$title</h2>\n";

    echo "<form action='{$_SERVER['PHP_SELF']}' method='post' >";
    echo "<table align='center' class='vertical'>";

    $sql = "SELECT * FROM products WHERE id='$id' ";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

    $row = mysql_fetch_object($result);

    echo "<tr><th>Vendor: <sup class='red'>*</sup></th>";
    echo "<td>";
    vendor_drop_down('vendor', $row->vendorid);
    echo "</td></tr>";
    echo "<tr><th>Product Name: <sup class='red'>*</sup></td>";
    echo "<td>";
    echo "<input class='textbox' maxlength='255' name='name' size='40' value='{$row->name}' />";
    echo "</td></tr>";
    echo "<tr><th>Description:</th>";
    echo "<td>";
    echo "<textarea name='description' cols='40' rows='6'>{$row->description}</textarea>";
    echo "</td></tr>";
    echo "</table>";
    echo "<input type='hidden' name='productid' value='$id' />";
    echo "<p align='center'><input type='submit' value='Save' /></p>";
    echo "</td></tr>";
    echo "</form>";
    mysql_free_result($result);

    include('htmlfooter.inc.php');
}
else
{
    // External variables
    $vendor = cleanvar($_POST['vendor']);
    $name = cleanvar($_POST['name']);
    $description = cleanvar($_POST['description']);
    $productid = cleanvar($_POST['productid']);

    // update database
    $sql = "UPDATE products SET vendorid='$vendor', name='$name', description='$description' WHERE id='$productid' LIMIT 1 ";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

    if (!$result) throw_error('Update of product failed:',$sql);
    else
    {
        journal(CFG_LOGGING_NORMAL, 'Product Edited', "Product $productid was edited", CFG_JOURNAL_PRODUCTS, $productid);
        confirmation_page("2", "products.php", "<h2>Product Edited Successfully</h2><p align='center'>Please wait while you are redirected...</p>");
    }
}
include('db_disconnect.inc.php');
?>