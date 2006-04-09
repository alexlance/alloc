<?php

/*
 *
 * Copyright 2006, Alex Lance, Clancy Malcolm, Cybersource Pty. Ltd.
 * 
 * This file is part of AllocPSA <info@cyber.com.au>.
 * 
 * AllocPSA is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 * 
 * AllocPSA is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE.  See the GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License along with
 * AllocPSA; if not, write to the Free Software Foundation, Inc., 51 Franklin
 * St, Fifth Floor, Boston, MA 02110-1301 USA
 *
 */

require_once("alloc.inc");

$current_user->check_employee();


include_template("templates/itemLoanM.tpl");




function show_overdue($template_name) {

  global $db, $TPL, $current_user;

  $db = new db_alloc;
  $temp = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
  $today = date("Y", $temp)."-".date("m", $temp)."-".date("d", $temp);

  $q = sprintf("SELECT itemName,itemType,item.itemID,dateBorrowed,dateToBeReturned,loan.personID 
                  FROM loan,item 
                 WHERE dateToBeReturned < '%s' 
					         AND dateReturned = '0000-00-00' 
					         AND item.itemID = loan.itemID
               ",$today);

  if (!have_entity_perm("loan", PERM_READ, $current_user, false)) {
    $q .= sprintf("AND loan.personID = %d",$current_user->get_id());
  }

  $db->query($q);

  while ($db->next_record()) {
    $i++;
    $TPL["row_class"] = "odd";
    $i % 2 == 0 and $TPL["row_class"] = "even";

    $item = new item;
    $loan = new loan;
    $item->read_db_record($db);
    $loan->read_db_record($db);
    $item->set_tpl_values();
    $loan->set_tpl_values();
    $person = new person;
    $person->set_id($loan->get_value("personID"));
    $person->select();
    $TPL["person"] = $person->get_username(1);
    $TPL["overdue"] = "<a href=\"".$TPL["url_alloc_item"]."itemID=".$item->get_id()."&return=true\">Overdue!</a>";

    include_template($template_name);
  }
}


page_close();



?>
