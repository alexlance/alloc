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

$modules = get_alloc_modules();

$_GET["module"] or $_GET["module"] = ALLOC_CURRENT_MODULE;

$file = "../".$_GET["module"]."/help/".$_GET["topic"].".html";

if (file_exists($file)) {

  // Security checks - do not allow arbitrary file access
  if (!(eregi("^[a-z0-9_]+$", $_GET["topic"]))) {
    echo "Invalid topic";
    return;
  }

  if (!isset($modules[$_GET["module"]])) {
    echo "Invalid module";
    return;
  }

  include_template($file);
}


page_close();



?>
