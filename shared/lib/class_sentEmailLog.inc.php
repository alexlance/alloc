<?php

/*
 *
 * Copyright 2006, Alex Lance, Clancy Malcolm, Cybersource Pty. Ltd.
 * 
 * This file is part of allocPSA <info@cyber.com.au>.
 * 
 * allocPSA is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 * 
 * allocPSA is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE.  See the GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License along with
 * allocPSA; if not, write to the Free Software Foundation, Inc., 51 Franklin
 * St, Fifth Floor, Boston, MA 02110-1301 USA
 *
 */


class sentEmailLog extends db_entity {
  var $classname = "sentEmailLog";
  var $data_table = "sentEmailLog";

  function sentEmailLog() {
      $this->db_entity();       // Call constructor of parent class
      $this->key_field = new db_field("sentEmailLogID");
      $this->data_fields = array("sentEmailTo"=>new db_field("sentEmailTo")
                                 , "sentEmailSubject"=>new db_field("sentEmailSubject")
                                 , "sentEmailBody"=>new db_field("sentEmailBody")
                                 , "sentEmailHeader"=>new db_field("sentEmailHeader")
                                 , "sentEmailType"=>new db_field("sentEmailType")
                                 , "sentEmailLogModifiedTime"=>new db_field("sentEmailLogModifiedTime")
                                 , "sentEmailLogModifiedUser"=>new db_field("sentEmailLogModifiedUser")
      );
  }
}


?>
