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

if ($_POST["owner"]) {
  $TPL["owner_checked"] = " checked";
  $filter[] = sprintf("tfPerson.personID = %d",$current_user->get_id());
} else {
  $TPL["owner_checked"] = "";
}


include_template("templates/tfListM.tpl");

function show_tf($template_name) {
  global $TPL, $filter;



  if (is_array($filter) && count($filter)) {
    $f = " WHERE ".implode(" AND ",$filter);
  }


  $db = new db_alloc;
  $q = sprintf("SELECT tf.* FROM tfPerson LEFT JOIN tf ON tf.tfID = tfPerson.tfID %s GROUP BY tf.tfID ORDER BY tf.tfName",$f);  
  $db->query($q);

  while ($db->next_record()) {

    $tf = new tf;
    $tf->read_db_record($db);
    $tf->set_tpl_values();

    if (have_entity_perm("transaction", PERM_READ, $current_user, $tf->is_owner())) {
      $TPL["tfBalance"] = number_format($tf->get_balance(), 2);
      $grand_total += $tf->get_balance();
    } else {
      $TPL["tfBalance"] = "not available";
    }
    $TPL["odd_even"] = $TPL["odd_even"] == "odd" ? "even" : "odd";

    $nav_links = $tf->get_nav_links();
    $TPL["nav_links"] = format_nav_links($nav_links);
    include_template($template_name);
  }

  $TPL["grand_total"] = number_format($grand_total, 2);

}

page_close();



?>
