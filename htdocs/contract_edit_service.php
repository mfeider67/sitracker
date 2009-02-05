<?php
// billing/edit_service.php - Allows balances to be edited or transfered
// TODO description
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author:  Paul Heaney Paul Heaney <paulheaney[at]users.sourceforge.net>

$lib_path = dirname( __FILE__ ).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR;
$permission =  80;

require_once ($lib_path.'db_connect.inc.php');
require_once ($lib_path.'functions.inc.php');
require_once ($lib_path . 'billing.inc.php');
// This page requires authentication
require_once ($lib_path.'auth.inc.php');

$mode = cleanvar($_REQUEST['mode']);
$amount = cleanvar($_REQUEST['amount']);
$contractid = cleanvar($_REQUEST['contractid']);
$sourceservice = cleanvar($_REQUEST['sourceservice']);
$destinationservice = cleanvar($_REQUEST['destinationservice']);
$reason = cleanvar($_REQUEST['reason']);
$serviceid = cleanvar($_REQUEST['serviceid']);
if (empty($mode)) $mode = 'showform';

switch ($mode)
{
    case 'editservice':
        if (user_permission($sit[2], $permission) == FALSE)
        {
            header("Location: {$CONFIG['application_webpath']}noaccess.php?id=$permission");
            exit;
        }
        else
        {
            $sql = "SELECT * FROM `{$dbService}` WHERE serviceid = {$serviceid}";
            $result = mysql_query($sql);
            if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

            include ('inc/htmlheader.inc.php');

            if (mysql_numrows($result) != 1)
            {
                echo "<h2>".sprintf($strNoServiceWithIDXFound, $serviceid)."</h2>";
            }
            else
            {
                $obj = mysql_fetch_object($result);

                echo "<h2>{$strEditService}</h2>";

                echo "<form id='serviceform' name='serviceform' action='{$_SERVER['PHP_SELF']}' method='post' onsubmit='return confirm_submit(\"{$strAreYouSureMakeTheseChanges}\");'>";
                echo "<table align='center' class='vertical'>";

                echo "<tr><th>{$strStartDate}</th>";
                echo "<td><input class='required' type='text' name='startdate' id='startdate' size='10'";
                echo "value='{$obj->startdate}' /> ";
                echo date_picker('serviceform.startdate');
                echo " <span class='required'>{$strRequired}</span></td></tr>";

                echo "<tr><th>{$strEndDate}</th>";
                echo "<td><input class='required' type='text' name='enddate' id='enddate' size='10'";
                echo "value='{$obj->enddate}' /> ";
                echo date_picker('serviceform.enddate');
                echo " <span class='required'>{$strRequired}</span></td></tr>\n";

                echo "<tr><th>{$strNotes}</th><td>";
                echo "<textarea rows='5' cols='20' name='notes'>{$obj->notes}</textarea></td></tr>";

                $timed = is_contract_timed($contractid);
                echo "<tr><th>{$strBilling}</th>";
                if ($timed)
                {
                    if ($obj->balance == $obj->creditamount)
                    {
                        echo "<td>";
                        echo "<input type='hidden' name='editbilling' id='editbilling' value='true' />";
                        echo "<input type='hidden' name='originalcredit' id='originalcredit' value='{$obj->creditamount}' />";
                        echo "<label>";
                        echo "<input type='radio' name='billtype' value='billperunit' onchange=\"addservice_showbilling('serviceform');\" ";
                        if (!empty($obj->unitrate) AND $obj->unitrate > 0)
                        {
                            echo "checked='checked' ";
                            $unitstyle = "";
                            $incidentstyle = "style='display:none'";
                        }
                        echo "/> {$strPerUnit}</label>";
                        echo "<label>";
                        echo "<input type='radio' name='billtype' value='billperincident' onchange=\"addservice_showbilling('serviceform');\" ";
                        if (!empty($obj->incidentrate) AND $obj->incidentrate > 0)
                        {
                            echo "checked='checked' ";
                            $unitstyle = "style='display:none'";
                            $incidentstyle = "";
                        }
                        echo "/> {$strPerIncident}</label>";
                        echo "</td></tr>\n";

                        echo "<tbody id='billingsection'>"; //FIXME not XHTML

                        echo "<tr><th>{$strCreditAmount}</th>";
                        echo "<td>{$CONFIG['currency_symbol']} ";
                        echo "<input class='required' type='text' name='amount' size='5' value='{$obj->creditamount}' />";
                        echo " <span class='required'>{$strRequired}</span></td></tr>";

                        echo "<tr id='unitratesection' {$unitstyle}><th>{$strUnitRate}</th>";
                        echo "<td>{$CONFIG['currency_symbol']} ";
                        echo "<input class='required' type='text' name='unitrate' size='5' value='{$obj->unitrate}' />";
                        echo " <span class='required'>{$strRequired}</span></td></tr>";

                        echo "<tr id='incidentratesection' {$incidentstyle}><th>{$strIncidentRate}</th>";
                        echo "<td>{$CONFIG['currency_symbol']} ";
                        echo "<input class='required' type='text' name='incidentrate' size='5' value='{$obj->incidentrate}' />";
                        echo " <span class='required'>{$strRequired}</span></td></tr>";

                        $fochecked = '';
                        if ($obj->foc == 'yes') $fochecked = "checked='checked'";

                        echo "<tr>";
                        echo "<th>{$strFreeOfCharge}</th>";
                        echo "<td><input type='checkbox' id='foc' name='foc' value='yes'  '{$fochecked}' /> {$strAboveMustBeCompletedToAllowDeductions}</td>";
                        echo "</tr>";

                        echo "</tbody>"; //FIXME not XHTML
                    }
                    else
                    {
                        echo "<input type='hidden' name='editbilling' id='editbilling' value='false' />";
                        echo "<tr><th colspan='2'>Unable to change amounts or rates as the service has been used.</th></tr>";
                    }
                //  Not sure how applicable daily rate is, INL 4Apr08
                //     echo "<tr><th>{$strDailyRate}</th>";
                //     echo "<td>{$CONFIG['currency_symbol']} <input type='text' name='dailyrate' size='5' />";
                //     echo "</td></tr>";
                }
                else
                {
                    echo "<td><label>";
                    echo "<input type='radio' name='billtype' value='' checked='checked' disabled='disabled' /> ";
                    echo "{$strNone}</label></td>";
                }

                echo "</table>\n\n";
                echo "<input type='hidden' name='contractid' value='{$contractid}' />";
                echo "<p><input name='submit' type='submit' value=\"{$strUpdate}\" /></p>";
                echo "<input type='hidden' name='serviceid' id='serviceid' value='{$serviceid}' />";
                echo "<input type='hidden' name='mode' id='mode' value='doupdate' />";
                echo "</form>\n";

                echo "<p align='center'><a href='contract_details.php?id={$contractid}'>{$strReturnWithoutSaving}</a></p>";
            }
            include ('inc/htmlfooter.inc.php');
        }

        break;
    case 'doupdate':
        $sucess = true;
        if (user_permission($sit[2], 80) == FALSE)
        {
            header("Location: {$CONFIG['application_webpath']}noaccess.php?id=80");
            exit;
        }
        else
        {
            $originalcredit = cleanvar($_REQUEST['originalcredit']);

            $startdate = strtotime($_REQUEST['startdate']);
            if ($startdate > 0) $startdate = date('Y-m-d',$startdate);
            else $startdate = date('Y-m-d',$now);
            $enddate = strtotime($_REQUEST['enddate']);
            if ($enddate > 0) $enddate = date('Y-m-d',$enddate);
            else $enddate = date('Y-m-d',$now);

            $notes = cleanvar($_REQUEST['notes']);

            $editbilling = cleanvar($_REQUEST['editbilling']);

            $foc = cleanvar($_REQUEST['foc']);
            if (empty($foc)) $foc = 'no';

            if ($editbilling == "true")
            {
                $amount =  cleanvar($_POST['amount']);
                if ($amount == '') $amount = 0;
                $unitrate =  cleanvar($_POST['unitrate']);
                if ($unitrate == '') $unitrate = 0;
                $incidentrate =  cleanvar($_POST['incidentrate']);
                if ($incidentrate == '') $incidentrate = 0;

                $billtype = cleanvar($_REQUEST['billtype']);

                if ($billtype == 'billperunit') $incidentrate = 0;
                elseif ($billtype == 'billperincident') $unitrate = 0;

                $updateBillingSQL = ", creditamount = '{$amount}', balance = '{$amount}', unitrate = '{$unitrate}', incidentrate = '{$incidentrate}' ";
            }

            if ($amount != $originalcredit)
            {
                $adjust = $amount - $originalcredit;

                update_contract_balance($contractid, "Credit adjusted to", $adjust, $serviceid);
            }

            $sql = "UPDATE `{$dbService}` SET startdate = '{$startdate}', enddate = '{$enddate}' {$updateBillingSQL}";
            $sql .= ", notes = '{$notes}', foc = '{$foc}' WHERE serviceid = {$serviceid}";

            mysql_query($sql);
            if (mysql_error())
            {
                trigger_error(mysql_error(),E_USER_ERROR);
                $sucess = false;
            }

            if (mysql_affected_rows() < 1)
            {
                trigger_error("Insert failed",E_USER_ERROR);
                $sucess = false;
            }

            $sql = "SELECT expirydate FROM `{$dbMaintenance}` WHERE id = {$contractid}";

            $result = mysql_query($sql);
            if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

            if (mysql_num_rows($result) > 0)
            {
                $obj = mysql_fetch_object($result);
                if ($obj->expirydate < strtotime($enddate))
                {
                    $update = "UPDATE `$dbMaintenance` ";
                    $update .= "SET expirydate = '".strtotime($enddate)."' ";
                    $update .= "WHERE id = {$contractid}";
                    mysql_query($update);
                    if (mysql_error())
                    {
                        trigger_error(mysql_error(),E_USER_ERROR);
                        $sucess = false;
                    }

                    if (mysql_affected_rows() < 1)
                    {
                        trigger_error("Expiry of contract update failed",E_USER_ERROR);
                        $sucess = false;
                    }
                }
            }

            if ($sucess)
            {
                html_redirect("{$CONFIG['application_webpath']}contract_details.php?id={$contractid}", TRUE, 'Sucessfully updated');
            }
            else
            {
                html_redirect("{$CONFIG['application_webpath']}contract_details.php?id={$contractid}", FALSE, 'NOT udpated');
            }


        }
        break;
    case 'showform':
        // Will be passed a $sourceservice to modify
        if (user_permission($sit[2], 79) == FALSE)
        {
            header("Location: {$CONFIG['application_webpath']}noaccess.php?id=79");
            exit;
        }
        else
        {
            include ('inc/htmlheader.inc.php');
            echo "<h2>{$strOneTimeBillingEditor}</h2>";

            echo "<form name='serviceform' action='{$_SERVER['PHP_SELF']}' method='post' onsubmit='return confirm_submit(\"{$strAreYouSureMakeTheseChanges}\");'>";

            echo "<table align='center' class='vertical'>";
            echo "<tr><th>{$strEdit}</th><td>{$sourceservice}</td></tr>";
            echo "<tr><th></th><td>";
            echo "<input type='radio' name='mode' id='edit' value='edit' checked='checked' onclick=\"$('transfersection').hide(); $('transfersectionbtn').hide(); $('editsection').show(); \" /> {$strEdit} ";

            // Only allow transfers on the same contractid
            $sql = "SELECT * FROM `{$dbService}` WHERE contractid = '{$contractid}' AND serviceid != {$sourceservice}";
            $result = mysql_query($sql);
            if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

            if (mysql_numrows($result) > 0)
            {

                echo "<input type='radio' name='mode' id='transfer' value='transfer' onclick=\"$('transfersection').show(); $('transfersectionbtn').show(); $('editsection').hide(); \" /> {$strTransfer} ";
                echo "</td></tr>";
                echo "<tbody  style='display:none' id='transfersection' >";
                echo "<tr><td colspan='2'>";
                if (get_service_balance($sourceservice) >= 0) echo $strTransferExamplePositiveService;
                else $strTransferExampleNegativeService;
                echo "</td></tr><tr><th>{$strDestinationService}</th>";
                echo "<td>";

                echo "<select name='destinationservice'>\n";

                while ($obj = mysql_fetch_object($result))
                {
                    echo "<option value='{$obj->serviceid}'>{$obj->serviceid} - {$obj->enddate} {$CONFIG['currency_symbol']}{$obj->balance}</option>\n";
                }

                echo "</select>\n";
                echo "</td></tr></tbody>\n";
            }
            else
            {
	                echo "</td></tr>";
            }

            echo "<tr><th>{$strAmountToEditBy}</th><td><input type='text' name='amount' id='amount' /></td></tr>";
            echo "<tr><th>{$strReason}</th><td><input type='text' name='reason' id='reason' /></td></tr>";

            echo "</table>";
            echo "<p align='center'><input type='submit' style='display:none'  name='runreport' id='transfersectionbtn' value='{$strTransfer}' /></p>";
            echo "<p align='center'><input type='submit' name='runreport' id='editsection' value='{$strEdit}' /></p>";

            echo "<input type='hidden' name='sourceservice' value='{$sourceservice}' />";
            echo "<input type='hidden' name='contractid' value='{$contractid}' />";

            echo "</form>";
        }
        include ('inc/htmlfooter.inc.php');
        break;
    case 'edit':
        if (user_permission($sit[2], 79) == FALSE)
        {
            header("Location: {$CONFIG['application_webpath']}noaccess.php?id=79");
            exit;
        }
        else
        {
            $status = update_contract_balance($contractid, $reason, $amount, $sourceservice);
            if ($status)
            {
                html_redirect("{$CONFIG['application_webpath']}contract_details.php?id={$contractid}", TRUE, $strSuccessfullyUpdated);
            }
            else
            {
                html_redirect("{$CONFIG['application_webpath']}contract_details.php?id={$contractid}", FALSE, $strUpdateFailed);
            }
        }
        break;
    case 'transfer':
        if (user_permission($sit[2], 79) == FALSE)
        {
            header("Location: {$CONFIG['application_webpath']}noaccess.php?id=79");
            exit;
        }
        else
        {
            $status = update_contract_balance($contractid, $reason, ($amount * -1), $sourceservice);
            if ($status)
            {
                $status = update_contract_balance($contractid, $reason, $amount, $destinationservice);
                if ($status) html_redirect("{$CONFIG['application_webpath']}contract_details.php?id={$contractid}", TRUE, $strTransferSucessful);
                else html_redirect("{$CONFIG['application_webpath']}contract_details.php?id={$contractid}", FALSE, $strTransferFailed);
                exit;
            }
            html_redirect('main.php', FALSE, $strTransferFailed);
            exit;
        }
        break;

}

?>