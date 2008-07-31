<?php

/*
 * Copyright (C) 2006, 2007, 2008 Alex Lance, Clancy Malcolm, Cybersource
 * Pty. Ltd.
 * 
 * This file is part of the allocPSA application <info@cyber.com.au>.
 * 
 * allocPSA is free software: you can redistribute it and/or modify it
 * under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or (at
 * your option) any later version.
 * 
 * allocPSA is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public
 * License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with allocPSA. If not, see <http://www.gnu.org/licenses/>.
*/

require_once("../alloc.php");
$current_user->check_employee();

global $current_user, $TPL, $db, $save, $saveAndNew, $saveGoTf;

$db = new db_alloc;
$transaction = new transaction;
$transactionID = $_POST["transactionID"] or $transactionID = $_GET["transactionID"];

if ($transactionID && !$_GET["new"]) {
  $transaction->set_id($transactionID);
  $transaction->select();
}

$tf = $transaction->get_foreign_object("tf");
$tf->check_perm();

$invoice_item = $transaction->get_foreign_object("invoiceItem");
$invoice_item->set_tpl_values();
$invoice = $invoice_item->get_foreign_object("invoice");
if (!$invoice->get_id()) {
  $invoice = $transaction->get_foreign_object("invoice");
}
$invoice->set_tpl_values();
if ($invoice->get_id()) {
  $TPL["invoice_link"] = "<a href=\"".$TPL["url_alloc_invoice"]."invoiceID=".$invoice->get_id()."\">#".$invoice->get_value("invoiceNum");
  $TPL["invoice_link"].= " ".$invoice->get_value("invoiceDateFrom")." to ". $invoice->get_value("invoiceDateTo")."</a>";
}

$expenseForm = $transaction->get_foreign_object("expenseForm");
if ($expenseForm->get_id()) {
  $TPL["expenseForm_link"] = "<a href=\"".$TPL["url_alloc_expenseForm"]."expenseFormID=".$expenseForm->get_id()."\">#".$expenseForm->get_id()."</a>";
}

$timeSheet = $transaction->get_foreign_object("timeSheet");
if ($timeSheet->get_id()) {
  $TPL["timeSheet_link"] = "<a href=\"".$TPL["url_alloc_timeSheet"]."timeSheetID=".$timeSheet->get_id()."\">#".$timeSheet->get_id()."</a>";
}

$transaction->set_tpl_values();



if ($_POST["save"] || $_POST["saveAndNew"] || $_POST["saveGoTf"]) {
/*
  if ($transaction->get_value("status") != "pending") {
    $TPL["message"][] = "This transaction is no longer editable.";
  }
*/
  $transaction->read_globals();

  // Tweaked validation to allow reporting of multiple errors
  $transaction->get_value("amount")          or $TPL["message"][] = "You must enter a valid amount";
  $transaction->get_value("transactionDate") or $TPL["message"][] = "You must enter a date for the transaction";
  $transaction->get_value("product")         or $TPL["message"][] = "You must enter a product"; 
  $transaction->get_value("status")          or $TPL["message"][] = "You must set the status of the transaction";
  $transaction->get_value("fromTfID")        or $TPL["message"][] = "You must select a Source Tagged Fund to take this transaction from";
  $transaction->get_value("tfID")            or $TPL["message"][] = "You must select a Destination Tagged Fund to add this transaction against";
  $transaction->get_value("transactionType") or $TPL["message"][] = "You must set a transaction type";
  #$transaction->get_value("projectID")       or $TPL["message"][] = "You must select a project";
  #$transaction->get_value("companyDetails")  or $TPL["message"][] = "You must enter the company details";

  if (!count($TPL["message"]))  {
    $transaction->check_perm(PERM_FINANCE_WRITE_FREE_FORM_TRANSACTION);
    $transaction->set_value("amount",str_replace(array("$",","),"",$transaction->get_value("amount")));
    $transaction->save();
    $TPL["message_good"][] = "Transaction Saved";

    if ($_POST["saveAndNew"]) {
      header("Location: ".$TPL["url_alloc_transaction"]."new=true");
    }

    if ($_POST["saveGoTf"]) {
      header("Location: ".$TPL["url_alloc_transactionList"]."tfID=".$transaction->get_value("tfID"));
    }
    $transaction->set_tpl_values();

  }
    
} else if ($_POST["delete"]) {
  $transaction->delete();
  header("location:".$TPL["url_alloc_transactionList"]."tfID=".$transaction->get_value("tfID"));
}

$transaction->set_tpl_values();

$TPL["product"] = htmlentities($transaction->get_value("product"));
$TPL["statusOptions"] = get_options_from_array(array("pending", "rejected", "approved"), $transaction->get_value("status"), false);
$transactionTypes = transaction::get_transactionTypes();
$TPL["transactionTypeOptions"] = get_select_options($transactionTypes, $transaction->get_value("transactionType"));

is_object($transaction) and $TPL["transactionType"] = $transaction->get_transaction_type_link();

$db = new db_alloc;
$db->query("SELECT tfID, tfName FROM tf WHERE status = 'active' ORDER BY tfName");
$TPL["tfIDOptions"] = get_options_from_db($db, "tfName", "tfID", $transaction->get_value("tfID"));
$db->query("SELECT tfID, tfName FROM tf WHERE status = 'active' ORDER BY tfName");
$TPL["fromTfIDOptions"] = get_options_from_db($db, "tfName", "tfID", $transaction->get_value("fromTfID"));

$db->query("SELECT projectName, projectID FROM project WHERE projectStatus = 'current' ORDER BY projectName");
$TPL["projectIDOptions"] = get_options_from_db($db, "projectName", "projectID", $transaction->get_value("projectID"));

$TPL["transactionModifiedUser"] = person::get_fullname($TPL["transactionModifiedUser"]);
$TPL["transactionCreatedUser"] = person::get_fullname($TPL["transactionCreatedUser"]);

$TPL["tf_link"] = "<a href=\"".$TPL["url_alloc_transactionList"]."tfID=".$TPL["tfID"]."\">".get_tf_name($TPL["tfID"])."</a>";
$TPL["from_tf_link"] = "<a href=\"".$TPL["url_alloc_transactionList"]."tfID=".$TPL["fromTfID"]."\">".get_tf_name($TPL["fromTfID"])."</a>";

$p = $transaction->get_foreign_object("project");
$TPL["project_link"] = "<a href=\"".$TPL["url_alloc_project"]."projectID=".$p->get_id()."\">".$p->get_value("projectName")."</a>";

$TPL["taxName"] = config::get_config_item("taxName");

if ($transaction->have_perm(PERM_FINANCE_WRITE_FREE_FORM_TRANSACTION) && !$transaction->is_final()) {
  $TPL["main_alloc_title"] = "Create Transaction - ".APPLICATION_NAME;
  include_template("templates/editTransactionM.tpl");
} else {
  $TPL["main_alloc_title"] = "View Transaction - ".APPLICATION_NAME;
  include_template("templates/viewTransactionM.tpl");
}

page_close();




?>
