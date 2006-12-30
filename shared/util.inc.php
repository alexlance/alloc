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

function get_default_from_address() {
  // Wrap angle brackets around the default From: email address 
  $f = config::get_config_item("AllocFromEmailAddress");
  $l = strpos($f, "<");
  $r = strpos($f, ">");
  $l === false and $f = "<".$f;
  $r === false and $f .= ">";
  return "allocPSA ".$f;
}
function get_alloc_version() {
  if (file_exists(ALLOC_MOD_DIR."/util/alloc_version") && is_readable(ALLOC_MOD_DIR."/util/alloc_version")) {
    $v = file(ALLOC_MOD_DIR."/util/alloc_version");
    return $v[0];
  } else {
    die("No alloc_version file found.");
  }
}
function get_script_path() {
  // Has to return something like
  // /alloc_dev/
  // /

  $modules = get_alloc_modules();
  $path = dirname($_SERVER["SCRIPT_NAME"]);
  $bits = explode("/",$path);
  $last_bit = end($bits);

  if ($modules[$last_bit]) {
    array_pop($bits);
  }
  is_array($bits) and $path = implode("/",$bits);

  $path[0] != "/" and $path = "/".$path;
  $path[strlen($path)-1] != "/" and $path.="/";
  return $path;

}
function seconds_to_display_format($seconds) {
  $day = config::get_config_item("hoursInDay");

  $day_in_seconds = $day * 60 * 60;
  $hours = $seconds / 60 / 60;
  
  if ($seconds < $day_in_seconds) {
    return sprintf("%0.2f hrs",$hours);
  } else {
    $days = $seconds / $day_in_seconds;
    #return sprintf("%0.1f days", $days);
    return sprintf("%0.2f hrs (%0.1f days)",$hours, $days);
     
  }
  
}
function get_alloc_modules() {
  if (defined("ALLOC_MODULES")) {
    return unserialize(ALLOC_MODULES);
  } else {
    echo "ALLOC_MODULES is not defined!";
  }
}
function page_close() {
  $sess = new Session;
  $sess->Save();

  global $current_user;
  if (is_object($current_user) && $current_user->get_id()) {
    $p = new person;
    $p->set_id($current_user->get_id());
    $p->select();

    if (is_array($current_user->prefs)) {
      $arr = serialize($current_user->prefs);
      $p->set_value("sessData",$arr);
    }
    $p->save();
  }
}
function get_all_form_data($array=array(),$defaults=array()) {
  // Load up $_FORM with $_GET and $_POST
  $_FORM = array();
  foreach ($array as $name) {
    $_FORM[$name] = $defaults[$name] or $_FORM[$name] = $_POST[$name] or $_FORM[$name] = urldecode($_GET[$name]);
  } 
  return $_FORM;
} 
function timetook($start, $text="Duration: ") {
  $end = microtime();
  list($start_micro,$start_epoch,$end_micro,$end_epoch) = explode(" ",$start." ".$end);
  $started  = (substr($start_epoch,-4) + $start_micro);
  $finished = (substr($end_epoch  ,-4) + $end_micro);
  $dur = $finished - $started;
  $unit = " seconds.";
  $dur > 60 and $unit = " mins.";
  $dur > 60 and $dur = $dur / 60;
  echo "<br>".$text.sprintf("%0.5f", $dur) . $unit;
}
function get_cached_table($table) {
  static $cache;
  if (!$cache) {
    $cache = new alloc_cache(array("person","taskType","timeUnit"));
    $cache->load_cache();

    // Special processing for person table
    $people = $cache->get_cached_table("person");
    foreach ($people as $id => $row) {
      if ($people[$id]["firstName"] && $people[$id]["surname"]) {
        $people[$id]["name"] = $people[$id]["firstName"]." ".$people[$id]["surname"];
      } else {
        $people[$id]["name"] = $people[$id]["username"];
      }
    }
    $cache->set_cached_table("person",$people);
  }
  return $cache->get_cached_table($table);
}
function get_option($label, $value = "", $selected = false) {
  $rtn = "<option";
  $rtn.= " value=\"$value\"";
  if ($selected) {
    $rtn.= " selected";
  }
  $rtn.= ">".$label."</option>";
  return $rtn;
}
function show_header() {
  include_template(ALLOC_MOD_DIR."/shared/templates/headerS.tpl");
}
function get_stylesheet_name() {
  global $current_user;

  $themes = get_customizedTheme_array();
  $fonts  = get_customizedFont_array();

  $style = strtolower($themes[sprintf("%d", $current_user->prefs["customizedTheme2"])]);
  $font = $fonts[sprintf("%d",$current_user->prefs["customizedFont"])];
  echo "style_".$style."_".$font.".css";
}
function get_customizedFont_array() {
  return array("-3"=>1, "-2"=>2, "-1"=>3, "0"=>"4", "1"=>5, "2"=>6, "3"=>7, "4"=>8, "5"=>9, "6"=>10);
}
function get_customizedTheme_array() {
  return array("Default","Leaf");
}
function show_footer() {
  include_template(ALLOC_MOD_DIR."/shared/templates/footerS.tpl");
}
function show_tabs() {
  global $TPL;

  $menu_links = array("Home"     =>array("url"=>$TPL["url_alloc_home"],"module"=>"home")
                     ,"Clients"  =>array("url"=>$TPL["url_alloc_clientList"],"module"=>"client")
                     ,"Projects" =>array("url"=>$TPL["url_alloc_projectList"],"module"=>"project")
                     ,"Tasks"    =>array("url"=>$TPL["url_alloc_taskSummary"],"module"=>"task")
                     ,"Time"     =>array("url"=>$TPL["url_alloc_timeSheetList"],"module"=>"time")
                     ,"Finance"  =>array("url"=>$TPL["url_alloc_financeMenu"],"module"=>"finance")
                     ,"People"   =>array("url"=>$TPL["url_alloc_personList"],"module"=>"person")
                     ,"Tools"    =>array("url"=>$TPL["url_alloc_tools"],"module"=>"tools")
                     );

  $x = -1;
  foreach ($menu_links as $name => $arr) {
    $TPL["x"] = $x;
    $x+=81;
    $TPL["url"] = $arr["url"];
    $TPL["name"] = $name;
    unset($TPL["active"]);
    if (preg_match("/".str_replace("/", "\\/", $_SERVER["PHP_SELF"])."/", $url) || preg_match("/".$arr["module"]."/",$_SERVER["PHP_SELF"])) {
       $TPL["active"] = " active";
    }
    include_template(ALLOC_MOD_DIR."/shared/templates/tabR.tpl");
  }
}
function show_toolbar() {
  global $TPL, $modules, $category;

  $TPL["category_options"] = get_category_options($_POST["category"]);
  $TPL["needle"] = $_POST["needle"] or $TPL["needle"] = "Search...";

  include_template(ALLOC_MOD_DIR."/shared/templates/toolbarS.tpl");
}
function move_attachment($entity, $id) {
  global $TPL;

  if ($_FILES["attachment"]) {
    is_uploaded_file($_FILES["attachment"]["tmp_name"]) || die("Uploaded document error.  Please try again.");

    $dir = $TPL["url_alloc_attachments_dir"].$entity."/".$id;
    if (!is_dir($dir)) {
      mkdir($dir, 0777);
    }

    if (!move_uploaded_file($_FILES["attachment"]["tmp_name"], $dir."/".$_FILES["attachment"]["name"])) {
      die("could not move attachment to: ".$dir."/".$_FILES["attachment"]["name"]);
    } else {
      chmod($dir."/".$_FILES["attachment"]["name"], 0777);
    }
  }
}
function get_attachments($entity, $id) {
  
  global $TPL;
  $rows = array();
  $dir = $TPL["url_alloc_attachments_dir"].$entity."/".$id;

  if ($id) {
    if (!is_dir($dir)) {
      mkdir($dir, 0777);
    }

    if (is_dir($dir)) {
      $handle = opendir($dir);

      while (false !== ($file = readdir($handle))) {

        if ($file != "." && $file != "..") {
          $size = filesize($dir."/".$file);
          $row["file"] = "<a href=\"".$TPL["url_alloc_getDoc"]."id=".$id."&entity=".$entity."&file=".urlencode($file)."\">".htmlentities($file)."</a>";
          $row["size"] = sprintf("%dkb",$size/1024);
          $rows[] = $row;    
        }
      }
    }
    return $rows;
  }
}
function util_show_attachments($entity, $id) {
  global $TPL;
  $TPL["entity_url"] = $TPL["url_alloc_".$entity];
  $TPL["entity_key_name"] = $entity."ID";
  $TPL["entity_key_value"] = $id;

  $rows = get_attachments($entity, $id);
  $rows or $rows = array();
  foreach ($rows as $row) {
    $TPL["attachments"].= "<tr><td>".$row["size"]."</td><td>".$row["file"]."</td></tr>";
  }

  include_template("../shared/templates/attachmentM.tpl");
}
function sort_task_comments_callback_func($a, $b) {
  return $a["date"] > $b["date"];
}
function util_get_comments($entity, $id, $options=array()) {
  global $TPL, $current_user;

  // Need to get timeSheet comments too for task comments
  if ($entity == "task") {
    $rows = comment::get_comments($entity,$id);
    $rows2 = timeSheetItem::get_timeSheetItemComments($id);

    if (is_array($rows2) && is_array($rows)) {
      $rows = array_merge($rows,$rows2);
    }
    if (is_array($rows)) {
      usort($rows, "sort_task_comments_callback_func");
    }

  } else {
    $rows = comment::get_comments($entity,$id);
  }
  $rows or $rows = array();

  foreach ($rows as $v) {

    if (!$v["comment"])
      continue ;

    $person = new person;
    $person->set_id($v["personID"]);
    $person->select();

    $comment_buttons = "";
    $ts_label = "";

    if ($v["timeSheetID"]) {
      $ts_label = "(Time Sheet Comment)";

    } else if ($v["personID"] == $current_user->get_id() && $options["showEditButtons"]) {
      $comment_buttons = "<nobr><input type=\"submit\" name=\"taskComment_edit\" value=\"Edit\">
                                <input type=\"submit\" name=\"taskComment_delete\" value=\"Delete\"></nobr>";
    }

    if (!$_GET["commentID"] || $_GET["commentID"] != $v["commentID"]) {

      $edit = false;
      if ($options["showEditButtons"]) {
        $edit = true;
      } 

      $edit and $rtn[] =  '<form action="'.$TPL["url_alloc_taskComment"].'" method="post">';
      $edit and $rtn[] =  '<input type="hidden" name="'.$entity.'ID" value="'.$v["commentLinkID"].'">';
      $edit and $rtn[] =  '<input type="hidden" name="commentID" value="'.$v["commentID"].'">';
      $edit and $rtn[] =  '<input type="hidden" name="taskComment_id" value="'.$v["commentID"].'">';
      $rtn[] =  '<table width="100%" cellspacing="0" border="0" class="comments">';
      $rtn[] =  '<tr>';
      $rtn[] =  '<th>Comment by <b>'.$person->get_username(1).'</b> '.$v["date"].' '.$ts_label."</th>";
      $edit and $rtn[] =  '<th align="right" width="2%">'.$comment_buttons.'</th>';
      $rtn[] =  '</tr>';
      $rtn[] =  '<tr>';
      $rtn[] =  '<td>'.nl2br(htmlentities($v["comment"])).'</td>';
      $edit and $rtn[] =  '<td>&nbsp;</td>';
      $rtn[] =  '</tr>';
      $rtn[] =  '</table>';
      $edit and $rtn[] =  '</form>';

    }
  }
  if (is_array($rtn)) {
    return implode("\n",$rtn);
  }
}
function get_display_date($db_date) {
  // Convert date from database format (yyyy-mm-dd) to display format (d/m/yyyy)
  if ($db_date == "0000-00-00 00:00:00") {
    return "";
  } else if (ereg("([0-9]{4})-?([0-9]{2})-?([0-9]{2})", $db_date, $matches)) {
    return sprintf("%d/%d/%d", $matches[3], $matches[2], $matches[1]);
  } else {
    return "";
  }
}
function get_date_stamp($db_date) {
  // Converts from DB date string of YYYY-MM-DD to a Unix time stamp
  ereg("^([0-9]{4})-([0-9]{2})-([0-9]{2})", $db_date, $matches);
  $date_stamp = mktime(0, 0, 0, $matches[2], $matches[3], $matches[1]);
  return $date_stamp;
}
function get_mysql_date_stamp($db_date) {
  // Converts mysql timestamp 20011024161045 to YYYY-MM-DD - AL
  ereg("^([0-9]{4})([0-9]{2})([0-9]{2})", $db_date, $matches);
  $date_stamp = mktime(0, 0, 0, $matches[2], $matches[3], $matches[1]);
  $date = date("Y", $date_stamp)."-".date("m", $date_stamp)."-".date("d", $date_stamp);
  return $date;
}
function get_select_options($options,$selected_value=NULL,$max_length=45) {
  /**
  * Builds up options for use in a html select widget (works with multiple selected too)
  *
  * @param   $options          mixed   An sql query or an array of options
  * @param   $selected_value   string  The current selected element
  * @param   $max_length       int     The maximum string length of the label
  * @return                    string  The string of options
  */

  // Build options from an SQL query: "SELECT col_a as name, col_b as value FROM"
  if (is_string($options)) {
    $db = new db_alloc;
    $db->query($options);
    while ($row = $db->row()) {
      $rows[$row["name"]] = $row["value"];
    }

  // Build options from an array: array(array("name1","value1"),array("name2","value2"))
  } else if (is_array($options)) {
    foreach ($options as $k => $v) {
      $rows[$k] = $v;
    }
  }

  if (is_array($rows)) {
    foreach ($rows as $value=>$label) {
      $sel = "";

      if (!$value && $value!==0 && !$value!=="0" && $label) {
        $value = $label; 
      }
      !$label && $value and $label = $value;

      // If an array of selected values!
      if (is_array($selected_value)) {
        foreach ($selected_value as $id) {
          $id == $value and $sel = " selected";
        }
      } else {
        $selected_value == $value and $sel = " selected";
      }

      $label = stripslashes($label);
      if (strlen($label) > $max_length) {
        $label = substr($label, 0, $max_length - 3)."...";
      } 

      $str.= "\n<option value=\"".$value."\"".$sel.">".$label."</option>";
    }
  }
  return $str;
}
function get_options_from_array($options, $selected_value, $use_values = true, $max_label_length = 40, $bitwise_values = false, $reverse_results = false) {
  // Get options for a <select> using an array of the form value=>label
  is_array($options) or $options = array();

  if ($reverse_results) {
    $options = array_reverse($options, TRUE);
  }
  foreach ($options as $value => $label) {
    $rtn.= "\n<option";
    if ($use_values) {
      $rtn.= " value=\"$value\"";

      if ($value == $selected_value || ($bitwise_values && (($selected_value & $value) == $value))) {
        $rtn.= " selected";
      }
    } else {
      $rtn.= " value=\"$label\"";
      if ($label == $selected_value) {
        $rtn.= " selected";
      }
    }
    $rtn.= ">";
    $label = stripslashes($label);
    if (strlen($label) > $max_label_length) {
      $rtn.= substr($label, 0, $max_label_length - 3)."...";
    } else {
      $rtn.= $label;
    }
    $rtn.= "</option>";
  }
  return $rtn;
}
function get_array_from_db($db, $key_field, $label_field) {
  // Constructs an array from a database containing 
  // $key_field=>$label_field entries
  // ALLA: Edited function so that an array of 
  // label_field could be passed $return is the 
  // _complete_ label string.
  // TODO: Make this function SORT
  $rtn = array();
  while ($db->next_record()) {
    if (is_array($label_field)) {
      $return = "";
      foreach($label_field as $key=>$label) {

        // Every second array element (starting with zero) will 
        // be the string separator. This really isn't quite as 
        // lame as it seems.  Although it's close.
        if (!is_int($key / 2)) {
          $return.= $db->f($label);
        } else {
          $return.= $label;
        }
      }
    } else {
      $return = $db->f($label_field);
    }
    if ($key_field) {
      $rtn[$db->f($key_field)] = stripslashes($return);
    } else {
      $rtn[] = stripslashes($return);
    }
  }
  return $rtn;
}
function get_options_from_db($db, $label_field, $value_field = "", $selected_value, $max_label_length = 40, $reverse_results = false) {
  // Get options for a <select> using a database object
  $options = get_array_from_db($db, $value_field, $label_field);
  return get_options_from_array($options, $selected_value, $value_field != "", $max_label_length, $bitwise_values = false, $reverse_results);
}
function get_tf_name($tfID) {
  if (!$tfID) {
    return false;
  } else {
    $db = new db_alloc;
    $db->query("select tfName from tf where tfID= ".$tfID);
    $db->next_record();
    return $db->f("tfName");
  }
}
function db_esc($str = "") {
  // If they're using magic_quotes_gpc then we gotta strip the 
  // automatically added backslashes otherwise they'll be added again..
  if (get_magic_quotes_gpc()) {
    $str = stripslashes($str);
  }
  $esc_function = "mysql_escape_string";
  if (version_compare(phpversion(), "4.3.0", ">")) {
    $esc_function = "mysql_real_escape_string";
  }
  
  if (is_numeric($str)) {
    return $str;
  }
  return $esc_function($str);
}
function db_get_where($where = array()) {
  // Okay so $value can be like eg: $where["status"] = array(" LIKE ","hey")
  // Or $where["status"] = "hey";
  foreach($where as $column_name=>$value) {
    $op = " = ";
    if (is_array($value)) {
      $op = $value[0];
      $value = $value[1];
    }
    $rtn.= " ".$and.$column_name.$op." '".db_esc($value)."'";
    $and = " AND ";
  }
  return $rtn;
}
function format_date($format="Y/m/d", $date="") {

  // If looks like this: 2003-07-07 21:37:01
  if (preg_match("/^[\d]{4}-[\d]{2}-[\d]{2} [\d]{2}:[\d]{2}:[\d]{2}$/",$date)) {
    list($d,$t) = explode(" ", $date);

  // If looks like this: 2003-07-07
  } else if (preg_match("/^[\d]{4}-[\d]{2}-[\d]{2}$/",$date)) {
    $d = $date;

  // If looks like this: 12:01:01
  } else if (preg_match("/^[\d]{2}:[\d]{2}:[\d]{2}$/",$date)) {
    $d = "2000-01-01";
    $t = $date;

  // Nasty hobbitses!
  } else if ($date) {
    return "Date unrecognized: ".$date;
  } else {
    return;
  }
  list($y,$m,$d) = explode("-", $d);
  list($h,$i,$s) = explode(":", $t);
  list($y,$m,$d,$h,$i,$s) = array(sprintf("%d",$y),sprintf("%d",$m),sprintf("%d",$d)
                                 ,sprintf("%d",$h),sprintf("%d",$i),sprintf("%d",$s)
                                 );
  return date($format, mktime(date($h),date($i),date($s),date($m),date($d),date($y)));
}
function get_config_link() {
  global $current_user, $TPL;
  if (have_entity_perm("config", PERM_UPDATE, $current_user, true)) {
    echo "<a href=\"".$TPL["url_alloc_config"]."\">Config</a>";
  }
}
function parse_sql_file($file) {
  
  // Filename must be readable and end in .sql
  if (!is_readable($file) || substr($file,-3) != strtolower("sql")) {
    return;
  }

  $sql = array();
  
  $mqr = @get_magic_quotes_runtime();
  @set_magic_quotes_runtime(0);
  $lines = file($file);
  @set_magic_quotes_runtime($mqr);

  foreach ($lines as $line) {
    if (preg_match("/^[\s]*(--[^\n]*)$/", $line, $m)) {
      $comments[] = str_replace("-- ","",$m[1]);
      $comments_html[] = $m[1];
    } else if (!empty($line) && substr($line,0,2) != "--" && $line) {
      $queries[] = $line;
    }
  }

  is_array($comments_html) and $comments_html = implode("<br/>",$comments_html);
  $queries = implode(" ",$queries);
  $queries = explode(";\n",$queries.";\n");

  #echo "<br/><br/><br/>---------".$file."----------<br/><pre>";
  #echo "<br/>NEW QUERY: ".implode("<br/>NEW QUERY: ",$sql)."</pre>";

  foreach ($queries as $query) {
    $query = trim($query);
    if(!empty($query) && $query != ";\n") {
      $sql[] = $query;
    }
  }
  
  return array($sql,$comments,$comments_html);
}



?>
