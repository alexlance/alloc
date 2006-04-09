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

// Task types
define("TT_TASK", 1);
define("TT_PHASE", 2);
define("TT_MESSAGE", 3);
define("TT_FAULT", 4);
define("TT_MILESTONE", 5);

define("PERM_PROJECT_READ_TASK_DETAIL", 256);

$default_task_options = array("show_links"=>true);

class task extends db_entity {
  var $classname = "task";
  var $data_table = "task";
  var $fire_events = true;
  var $display_field_name = "taskName";

  // Constructor - set creatorID to current user ID
  function task() {
    global $current_user;

      $this->db_entity();       // Call constructor of parent class
      $this->key_field = new db_text_field("taskID");
      $this->data_fields = array("taskName"=>new db_text_field("taskName", "Name", "", array("allow_null"=>false))
                                 , "taskDescription"=>new db_text_field("taskDescription")
                                 , "creatorID"=>new db_text_field("creatorID")
                                 , "priority"=>new db_text_field("priority")
                                 , "timeEstimate"=>new db_text_field("timeEstimate", "Time Estimate", 0, array("empty_to_null"=>true))
                                 , "timeEstimateUnitID"=>new db_text_field("timeEstimateUnitID")
                                 , "dateCreated"=>new db_text_field("dateCreated")
                                 , "dateAssigned"=>new db_text_field("dateAssigned")
                                 , "dateTargetStart"=>new db_text_field("dateTargetStart")
                                 , "dateTargetCompletion"=>new db_text_field("dateTargetCompletion")
                                 , "dateActualStart"=>new db_text_field("dateActualStart")
                                 , "dateActualCompletion"=>new db_text_field("dateActualCompletion")
                                 , "taskComments"=>new db_text_field("taskComments")
                                 , "projectID"=>new db_text_field("projectID")
                                 , "percentComplete"=>new db_text_field("percentComplete")
                                 , "parentTaskID"=>new db_text_field("parentTaskID")
                                 , "taskTypeID"=>new db_text_field("taskTypeID")
                                 , "personID"=>new db_text_field("personID")
                                 
      );

    if (isset($current_user)) {
      $this->set_value("creatorID", $current_user->get_id());
    }

    $this->permissions[PERM_PROJECT_READ_TASK_DETAIL] = "Read details";
  }

  function close_off_children_recursive() {
    // mark all children as complete
    $task = new task;
    $db = new db_alloc;
    if ($this->get_id()) {
      $query = "SELECT * FROM task WHERE parentTaskID = ".$this->get_id();
      $db->query($query);
                                                                                                                               
      while ($db->next_record()) {
        $task->read_db_record($db);
        $task->get_value("percentComplete")      != "100" && $task->set_value("percentComplete", "100");
        $task->get_value("dateActualStart")      == ""    && $task->set_value("dateActualStart", date("Y-m-d"));
        $task->get_value("dateActualCompletion") == ""    && $task->set_value("dateActualCompletion", date("Y-m-d"));
        $task->save();
        $task->close_off_children_recursive();
      }
    }
  }


  function new_message_task() {
    // Create a reminder with its regularity being based upon what the task priority is

    if ($this->get_value("priority") == 1) {
      $reminderInterval = "Day";
      $intervalValue = 1;
      $message = "A priority 1 message has been created for you.  You will continue to receive these ";
      $message.= "emails until you kill off this task either by deleting it or putting a date in its ";
      $message.= "'Date Actual Completion' box.";
    } else {
      $reminderInterval = "Day";
      $intervalValue = $this->get_value("priority");
      $message = "A priority ".$this->get_value("priority")." message has been created for you.  You will ";
      $message.= "continue to receive these ";
      $message.= "every ".$this->get_value("priority")." days until you kill this task either by deleting it ";
      $message.= "or putting a date in its 'Date Actual Completion' box.";
    }
    $people[] = $this->get_value("personID");
    $this->create_reminders($people, $message, $reminderInterval, $intervalValue);
  }


  function new_fault_task() {
    // Create a reminder with its regularity being based upon what the task priority is
    $db = new db_alloc;

    if ($this->get_value("priority") == 1) {
      if ($this->get_value("projectID")) {
        $db->query("SELECT * from projectPerson WHERE projectID = ".$this->get_value("projectID"));
        while ($db->next_record()) {
          $people[] = $db->f("personID");
        }
      } else {
        $people[] = $this->get_value("personID");
      }
      $message = "THIS IS IMPORTANT.\nThis is a priority 1 fault/task/alert.  See the task immediately for details.";
      $message.= "\nYou will receive one of these emails every four hours until the task has a date in its 'Actual ";
      $message.= "Completion' box.";
      // $message.= "\n\n<a href=\"" . $this->get_url() . "\">";
      $reminderInterval = "Hour";
      $intervalValue = 4;
    } else if ($this->get_value("priority") == 2) {
      if ($this->get_value("projectID")) {
        $db->query("SELECT * 
                      FROM projectPerson LEFT JOIN projectPersonRole on projectPerson.projectPersonRoleID = projectPersonRole.projectPersonRoleID 
                     WHERE (projectPersonRole.projectPersonRoleHandle = 'isManager'  OR projectPersonRole.projectPersonRoleHandle = 'timeSheetRecipient') AND projectID = ".$this->get_value("projectID"));
        while ($db->next_record()) {
          $people[] = $db->f("personID");
        }
      } else {
        $people[] = $this->get_value("personID");
      }
      $message = "This is a priority 2 fault/task/alert.  See the task immediately for details.";
      $message.= "You will receive an email once a day everyday until the task is resolved.";
      // $message.= "\n\n<a href=\"" . $this->get_url() . "\">";
      $reminderInterval = "Day";
      $intervalValue = 1;
    }

    $this->create_reminders($people, $message, $reminderInterval, $intervalValue);
  }



  // 'internal' function used to create reminders for an array of people
  function create_reminders($people, $message, $reminderInterval, $intervalValue) {
    if (is_array($people)) {
      foreach($people as $personID) {
        $person = new person;
        $person->set_id($personID);
        $person->select();
        if ($person->get_value("emailAddress")) {
          $this->create_reminder($personID, $message, $reminderInterval, $intervalValue);
        }
      }
    }
  }


  function create_reminder($personID, $message, $reminderInterval, $intervalValue) {
    global $current_user;
    $reminder = new reminder;
    $reminder->set_value('reminderType', "task");
    $reminder->set_value('reminderLinkID', $this->get_id());
    $reminder->set_value('reminderRecuringInterval', $reminderInterval);
    $reminder->set_value('reminderRecuringValue', $intervalValue);
    $reminder->set_value('reminderSubject', $this->get_value("taskName"));
    $reminder->set_value('reminderContent', "\nReminder Created By: ".$current_user->get_display_value()
                         ."\n\n".$message."\n\n".$this->get_value("taskDescription"));

    $reminder->set_value('reminderAdvNoticeSent', "0");
    $reminder->set_value('reminderAdvNoticeInterval', "No");
    $reminder->set_value('reminderAdvNoticeValue', "0");

    $reminder->set_value('reminderModifiedTime', date("Y-m-d H:i:s"));
    $reminder->set_value('reminderModifiedUser', $current_user->get_display_value());
    $reminder->set_value('reminderTime', date("Y-m-d H:i:s"));
    $reminder->set_value('personID', $personID);

    $reminder->save();
  }



  function is_owner($person = "") {
    // A user owns a task if the 'own' the project
    if ($this->get_id()) {
      // Check for existing task
      $p = $this->get_foreign_object("project");
    } else if ($_POST["projectID"]) {
      // Or maybe they are creating a new task
      $p = new project;
      $p->set_id($_POST["projectID"]);
    }

    // if this task doesn't exist (no ID) 
    // OR the person has isManager or canEditTasks for this tasks project 
    // OR if this person is the Creator of this task.
    // OR if this person is the For Person of this task.
    // OR if this person has super 'manage' perms
    if (
       !$this->get_id() 
    || (is_object($p) && ($p->has_project_permission($person, array("isManager", "canEditTasks"))) 
    || $this->get_value("creatorID") == $person->get_id()
    || $this->get_value("personID") == $person->get_id()
    || $person->have_role("manage")
    )) {
      return true;
    }
  }

  function get_parent_task_select($projectID="") {
    global $TPL;
    
    $options = get_option("None", "0");
    if (is_object($this)) {
      $projectID = $this->get_value("projectID");
      $parentTaskID = $this->get_value("parentTaskID");
    }

    $db = new db_alloc;
    if ($projectID) {
      $query = sprintf("SELECT * 
                        FROM task 
                        WHERE projectID= '%d' 
                        AND taskTypeID = 2 
                        AND (dateActualCompletion IS NULL or dateActualCompletion = '') 
                        ORDER BY taskName", $projectID);
      $db->query($query);
      $options = get_option("None", "0");
      $options.= get_options_from_db($db, "taskName", "taskID", $parentTaskID,70);
    }
    return "<select name=\"parentTaskID\">".$options."</select>";
  }



  function get_task_cc_list_select($projectID="") {
    global $TPL;
    $db = new db_alloc;
    
    if (is_object($this)) {
      $projectID = $this->get_value("projectID");
      $q = sprintf("SELECT fullName,emailAddress FROM taskCCList WHERE taskID = %d",$this->get_id());
      $db->query($q);  
      while ($db->next_record()) {
        $taskCCList[] = urlencode(base64_encode(serialize(array("name"=>sprintf("%s",stripslashes($db->f("fullName"))),"email"=>$db->f("emailAddress")))));
        // And add the list of people who are already in the taskCCList for this task, just in case they get deleted from the client pages
        // This email address will be overwritten by later entries
        $taskCCListOptions[$db->f("emailAddress")] = stripslashes($db->f("fullName"));
      }
    }

    if ($projectID) {
      $taskCCListOptions = array();

      // Get primary client contact from Project page
      $q = sprintf("SELECT projectClientName,projectClientEMail FROM project WHERE projectID = %d",$projectID);
      $db->query($q);
      $db->next_record();
      $taskCCListOptions[$db->f("projectClientEMail")] = stripslashes($db->f("projectClientName"));
  
      // Get all other client contacts from the Client pages for this Project
      $q = sprintf("SELECT clientID FROM project WHERE projectID = %d",$projectID);
      $db->query($q);
      $db->next_record();
      $clientID = $db->f("clientID");
      $q = sprintf("SELECT clientContactName, clientContactEmail FROM clientContact WHERE clientID = %d",$clientID);
      $db->query($q);
      while ($db->next_record()) {
        $taskCCListOptions[$db->f("clientContactEmail")] = stripslashes($db->f("clientContactName"));
      }

      // Get all the project people for this tasks project
      $q = sprintf("SELECT emailAddress, firstName, surname 
                     FROM projectPerson LEFT JOIN person on projectPerson.personID = person.personID 
                    WHERE projectPerson.projectID = %d",$projectID);
      $db->query($q);
      while ($db->next_record()) {
        $taskCCListOptions[$db->f("emailAddress")] = stripslashes($db->f("firstName")." ".$db->f("surname"));
      }
      

    }



    if (is_array($taskCCListOptions)) {
      foreach ($taskCCListOptions as $email => $name) {
        if ($email) {
          $str = trim(htmlentities($name." <".$email.">"));
          $options[urlencode(base64_encode(serialize(array("name"=>sprintf("%s",$name),"email"=>$email))))] = $str;
        }
      }
    }
    $str = "<select name=\"taskCCList[]\" size=\"5\" multiple=\"true\"  style=\"width:300px\">".get_select_options($options,$taskCCList)."</select>";
    return $str;
  }




  // Set template values to provide options for edit selects
  function set_option_tpl_values() {
    global $TPL, $timeSheetID, $current_user, $isMessage;

    $projectID = $this->get_value("projectID");
    $db = new db_alloc;

    // TaskType Options
    $taskType = new taskType;
    $TPL["taskTypeOptions"] = $taskType->get_dropdown_options("taskTypeID","taskTypeName",$this->get_value("taskTypeID"));

    // Project Options - Select all projects 
    $query = sprintf("SELECT * FROM project WHERE projectStatus = 'current' ORDER BY projectName");
    $db->query($query);
    $TPL["projectOptions"] = get_option("None", "0", $projectID == 0)."\n";
    $TPL["projectOptions"].= get_options_from_db($db, "projectName", "projectID", $projectID,60);

    // TaskCommentTemplateOptions - Select all task comment templates
    $query = sprintf("SELECT * FROM taskCommentTemplate ORDER BY taskCommentTemplateName");
    $db->query($query);
    $TPL["taskCommentTemplateOptions"] = get_option("Comment Templates", "0")."\n";
    $TPL["taskCommentTemplateOptions"].= get_options_from_db($db, "taskCommentTemplateName", "taskCommentTemplateID",false);

    if ($timeSheetID) {
      $ts_query = "select personID from timeSheet where timeSheetID = ".$timeSheetID;
      $db->query($ts_query);
      $db->next_record();
      $owner = $db->f("personID");
    } else if ($this->get_value("personID")) {
      $owner = $this->get_value("personID");
    } else {
      $owner = $current_user->get_id();
    }

    $TPL["personOptions"] = get_option("None", "0", $owner == 0)."\n";
    $TPL["personOptions"].= get_select_options(person::get_username_list($owner), $owner);

    $percentCompleteOptions = array(0, 10, 20, 30, 40, 50, 60, 70, 80, 90, 100);
    $TPL["percentCompleteOptions"] = get_options_from_array($percentCompleteOptions, $this->get_value("percentComplete"), false);


    $priority = $this->get_value("priority") or $priority = 3;
    $TPL["priorityOptions"] = get_select_options(array(1=>"Priority 1", 2=>"Priority 2", 3=>"Priority 3", 4=>"Priority 4", 5=>"Priority 5"), $priority);

    // We're building these two with the <select> tags because they will be replaced by an AJAX created dropdown when
    // The projectID changes.
    $TPL["parentTaskOptions"] = $this->get_parent_task_select();
    $TPL["taskCCListOptions"] = $this->get_task_cc_list_select();
    

    $db->query(sprintf("SELECT fullName,emailAddress FROM taskCCList WHERE taskID = %d",$this->get_id()));
    while ($db->next_record()) {
      $str = trim(htmlentities($db->f("fullName")." <".$db->f("emailAddress").">"));
      $value = urlencode(base64_encode(serialize(array("name"=>sprintf("%s",$db->f("fullName")),"email"=>$db->f("emailAddress")))));
      $TPL["taskCCList_hidden"].= $commar.$str."<input type=\"hidden\" name=\"taskCCList[".$value."]\" value=\"".$db->f("emailAddress")."\">";
      $commar = "<br/>";
    }
    

    $timeUnit = new timeUnit;
    $TPL["task_timeEstimateUnitID_options"] = $timeUnit->get_dropdown_options("timeUnitID","timeUnitLabelA",$this->get_value("timeEstimateUnitID"),1);

  
    // The options for the email dropdown boxes
    #$TPL["task_createdBy"] and $creator_extra = " (".$TPL["task_createdBy"].")";
    #$TPL["person_username"] and $person_extra = " (".$TPL["person_username"].")";
    #$emailOptions[] = "Email Sending Options";
    #$TPL["task_createdBy_personID"] != $current_user->get_id() and $emailOptions["creator"] = "Email Task Creator".$creator_extra;
    #$TPL["person_username_personID"] != $current_user->get_id() and $emailOptions["assignee"] = "Email Task Assignee".$person_extra;
    #$emailOptions["isManager"] = "Email Project Managers";
    #$emailOptions["canEditTasks"] = "Email Project Engineers";
    #$emailOptions["all"] = "Email Project Managers & Engineers";

    // Email dropdown options for the comment box
    #if ($current_user->get_id() == $this->get_value("creatorID")) {
      #$taskCommentEmailSelected = "assignee";
    #}   
    #if ($current_user->get_id() == $this->get_value("personID")) {
      #$taskCommentEmailSelected = "creator";
    #}   
    #$TPL["taskCommentEmailOptions"] = get_options_from_array($emailOptions,$taskCommentEmailSelected);



    // If we're viewing the printer friendly view
    global $view;
    if ($view == "printer") {
      // Parent Task label
      $t = new task;
      $t->set_id($this->get_value("parentTaskID"));
      $t->select();
      $TPL["parentTask"] = $t->get_display_value();

      // Task Type label
      $tt = new taskType;
      $tt->set_id($this->get_value("taskTypeID"));
      $tt->select();
      $TPL["taskType"] = $tt->get_display_value();

      // Priority
      $TPL["priority"] = $this->get_value("priority");

      // Assignee label
      $p = new person;
      $p->set_id($this->get_value("personID"));
      $p->select();
      $TPL["person"] = $p->get_display_value();
  
      // Project label
      $p = new project;
      $p->set_id($this->get_value("projectID"));
      $p->select();
      $TPL["projectName"] = $p->get_display_value();
    }

  }


  function get_email_recipients($options=array()) {
    static $people;
    $recipients = array();

    // Load up all people into array
    if (!$people) { 
      $db = new db_alloc;
      $db->query("SELECT personID, username, firstName, surname, emailAddress FROM person");
      while($db->next_record()) {
        if ($db->f("firstName") && $db->f("surname")) {
          $db->Record["fullName"] = $db->f("firstName")." ".$db->f("surname");
        } else {
          $db->Record["fullName"] = $db->f("username");
        }
        $people[$db->f("personID")] = $db->Record;
      }
    }


    foreach ($options as $selected_option) {

      // Determine recipient/s 

      if ($selected_option == "CCList") {
        $db = new db_alloc;
        $q = sprintf("SELECT * FROM taskCCList WHERE taskID = %d",$this->get_id()); 
        $db->query($q);
        while($db->next_record()) {
          $recipients[] = $db->Record;
        }
      } else if ($selected_option == "creator") {
        $recipients[] = $people[$this->get_value("creatorID")];
      } else if ($selected_option == "assignee") {
        $recipients[] = $people[$this->get_value("personID")];
      } else if ($selected_option == "isManager" || $selected_option == "canEditTasks" || $selected_option == "all") {
        $q = sprintf("SELECT personID,projectPersonRoleHandle 
                        FROM projectPerson 
                   LEFT JOIN projectPersonRole ON projectPersonRole.projectPersonRoleID = projectPerson.projectPersonRoleID 
                       WHERE projectID = %d", $this->get_value("projectID"));
        if ($selected_option != "all") {
          $q .=  sprintf(" AND projectPersonRole.projectPersonRoleHandle = '%s'",$selected_option);
        }

        $db->query($q);
        while ($db->next_record()) {
          $recipients[] = $people[$db->f("personID")];
        }
      }
    }
    return $recipients;
  }


  function send_emails($selected_option, $object, $extra="") {
    global $current_user;
    $recipients = $this->get_email_recipients($selected_option);

    $extra or $extra = "Task";

    $subject = $extra.": ".$this->get_id()." ".$this->get_value("taskName");
    $p = new project;
    $p->set_id($this->get_value("projectID"));
    $p->select();

    if (get_class($object) == "task") {
      $body = "Project: ".stripslashes($p->get_value("projectName"));
      $body.= "\nTask: ".stripslashes($this->get_value("taskName"));
      $body.= "\nhttp://alloc/project/task.php?taskID=".$this->get_id();
      $body.= "\n\n".stripslashes(wordwrap($this->get_value("taskDescription")));
    }

    foreach ($recipients as $recipient) {
      if ($current_user->get_id() != $recipient["personID"] && $object->send_email($recipient, $subject, $body)) {
        $successful_recipients.= $commar.$recipient["fullName"];
        $commar = ", ";
      }
    }
    return $successful_recipients;
  }

  function send_email($recipient, $subject, $body) {
    global $current_user;

    // New email object wrapper takes care of logging etc.
    $email = new alloc_email;
    $email->set_from($current_user->get_id());

    // REMOVE ME!!
    $email->ignore_no_email_urls = true;

    $message = "\n".wordwrap($body);

    // Convert plain old recipient address alla@cyber.com.au to Alex Lance <alla@cyber.com.au>
    if ($recipient["firstName"] && $recipient["surname"] && $recipient["emailAddress"]) {
      $recipient["emailAddress"] = $recipient["firstName"]." ".$recipient["surname"]." <".$recipient["emailAddress"].">";
    } else if ($recipient["fullName"] && $recipient["emailAddress"]) {
      $recipient["emailAddress"] = $recipient["fullName"]." <".$recipient["emailAddress"].">";
    }

    if ($recipient["emailAddress"]) {
      return $email->send($recipient["emailAddress"], $subject, $message);
    }
  }


  // Get the date the task is forecast to be completed given an actual start 
  // date and percent complete
  function get_forecast_completion() {
    $date_actual_start = $this->get_value("dateActualStart");
    $percent_complete = $this->get_value("percentComplete");

    if (!($date_actual_start and $percent_complete)) {
      // Can't calculate forecast date without date_actual_start and % complete
      return 0;
    }

    $date_actual_start = get_date_stamp($date_actual_start);
    $time_spent = mktime() - $date_actual_start;
    $time_per_percent = $time_spent / $percent_complete;
    $percent_left = 100 - $percent_complete;
    $time_left = $percent_left * $time_per_percent;
    $date_forecast_completion = mktime() + $time_left;
    return $date_forecast_completion;
  }

// function get_status() {{{
  function get_status($format = "html", $type = "standard") {
    $today = date("Y-m-d");
    define("UNKNOWN", 0);
    define("NOT_STARTED", 1);
    define("STARTED", 2);
    define("COMPLETED", 3);

    $date_target_start = $this->get_value("dateTargetStart");
    $date_target_completion = $this->get_value("dateTargetCompletion");
    $date_actual_start = $this->get_value("dateActualStart");
    $date_actual_completion = $this->get_value("dateActualCompletion");

    // First figure out where we should be with this task
    if ($date_target_completion != "" && $date_target_completion <= $today) {
      $target = COMPLETED;
    } else if ($date_target_start != "" && $date_target_start <= $today) {
      $target = STARTED;
    } else if ($date_target_start) {
      $target = NOT_STARTED;
    } else {
      $target = UNKNOWN;
    }

    // Now figure out where we are
    if ($date_actual_completion) {
      $actual = COMPLETED;
    } else if ($date_actual_start) {
      $actual = STARTED;
    } else {
      $actual = NOT_STARTED;
    }

    // Now compare the target and the actual and provide the results
    if ($actual == COMPLETED) {
      if ($type != "brief") {
        $status = "Completed on ".$date_actual_completion;
      } else {
        $status = "Completed";
      }
    } else if ($actual == STARTED) {
      $date_forecast_completion = $this->get_forecast_completion();
      #$percent_complete = $this->get_value("percentComplete");

      $status = "Started ".$date_actual_start.", ";

      #if ($type != "brief") {
      #  if ($percent_complete == "") {
      #    $status.= "% complete not set, ";
      #  } else {
      #    $status.= "$percent_complete% complete, ";
      #  }
      #}

      if ($date_target_completion != "") {
        $status.= "Target completion $date_target_completion ";
      } else {
    
      }

      if ($type != "brief") {
        if ($date_forecast_completion == 0) {
          $status.= "forecast completion date not available";
        } else {
          $status.= "forecast completion date of	".date("Y-m-d", $date_forecast_completion);
        }
      }

      if ($target == COMPLETED) {
        if ($type == "brief") {
          $status = "Overdue for completion on ".$date_target_completion;
        } else {
          $status = "Overdue for completion - $status";
        }
        if ($format == "html") {
          $status = "<strong class=\"overdue\">$status</strong>";
        }
      } else if ($date_target_completion && date("Y-m-d", $date_forecast_completion) > $date_target_completion) {
        $status = "Behind target - $status";
        if ($format == "html") {
          $status = "<strong class=\"behind-target\">$status</strong>";
        }
      }

    // New one
    } else if ($actual == NOT_STARTED && $target == UNKNOWN) {
      if ($target_completion_date) {
        $status = "Not started, due to be completed by $target_completion_date, no target start date";
      } else {
        $status = "Not started, no targets";
      }
    } else if ($actual == NOT_STARTED && $target == NOT_STARTED) {
      $status = "Due to start on ".$date_target_start;
      if ($date_target_completion) {
        $status.= " and to be completed by ".$date_target_completion;
      } else {
        $status.= ", no target completion date";
      }
    } else if ($actual == NOT_STARTED && $target == STARTED) {
      $status = "Overdue to start on ".$date_target_start;
      if ($format == "html") {
        $status = "<strong class=\"behind-target\">$status</strong>";
      }
    } else if ($actual == NOT_STARTED && $target == COMPLETED) {
      $status = "Overdue to start and be completed by $date_target_completion";
      if ($format == "html") {
        $status = "<strong class=\"overdue\">$status</strong>";
      }
    } else {
      $status = "Unexpected target/actual combination: $target/$actual";
    }

    // $status .= " ($target/$actual)";
    return $status;
  }
// }}}

/*
  function get_parent() {
    $parent = new task;
    $parent->set_id($this->get_value("parentTaskID"));
    if ($parent->select()) {
      return $parent;
    } else {
      return;
    }
  }
  function get_children($existing_filter = "") {
    if ($existing_filter == "") {
      $filter = new task_filter();
    } else {
      $filter = $existing_filter;
    }

    $filter->set_element("parent_task", $this);
    $list = new task_list($filter);
    $children = $list->get_entity_array();
    return $children;
  }
*/


/*
  function recalculate() {
    $children = $this->get_children();

    if (count($children) > 0) {
      // Calculate time estimate as a total of childrens' time estimates
      $total_time = 0;
      reset($children);
      while (list(, $child) = each($children)) {
        $total_time += $child->get_value("timeEstimate");
      }

      // Calculate percent complete as a weighted average of childrens' % complete
      $percent_complete = 0;
      reset($children);
      while (list(, $child) = each($children)) {
        $percent_complete += $child->get_value("percentComplete") * $child->get_value("timeEstimate");
      }

      if ($total_time != 0) {
        $percent_complete = $percent_complete / $total_time;
        $percent_complete = ceil($percent_complete);
        $this->set_value("timeEstimate", $total_time);
        $this->set_value("percentComplete", $percent_complete);
      }
    }
  }
*/

  function get_task_link() {
    $rtn = "<a href=\"".$this->get_url()."\">";
    $rtn.= $this->get_task_name();
    $rtn.= "</a>";
    return $rtn;
  }

  function get_task_name() {
    if ($this->get_value("taskTypeID") == TT_PHASE) {
      $rtn = "<strong>".stripslashes($this->get_value("taskName"))."</strong>";
    } else {
      substr($this->get_value("taskName"),0,140) != $this->get_value("taskName") and $dotdotdot = "...";
      $rtn = substr(stripslashes($this->get_value("taskName")),0,140).$dotdotdot;
    }
    return $rtn;
  }

/*
  function get_summary($options = "", $include_children = true, $child_filter = "", $format = "html", $indent = 0, $user_id = "") {
    global $default_task_options, $current_user, $TPL;
    if ($options == "")
      $options = $default_task_options;
    if ($child_filter == "") {
      $child_filter = new task_filter;
    }
  
    $show_links = $options["show_links"] && $this->have_perm(PERM_PROJECT_READ_TASK_DETAIL);

    if (isset($options["status_type"])) {
      $status_type = $options["status_type"];
    } else {
      $status_type = "standard";
    }

// if ($format == "html") {{{
    if ($format == "html") {
      // HTML format

      if (!$options["skip_indent"]) {
        while ($i <= $indent) {
          $indent_space.= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
          $i++;
        }
      }

      $rtn.= "<tr>\n";
      $options["nobr_taskName"] and $colspan=" colspan=\"2\"";
      $rtn.= "  <td valign=\"top\"".$colspan.">";
      $options["nobr_taskName"] and $rtn .= "<nobr>";
      $rtn.= $indent_space;

      if ($show_links) {
        $rtn.= $this->get_task_link();
      } else {
        $rtn.= $this->get_task_name();
      }

      if ($options["show_new_children_links"] && $this->get_value("taskTypeID") == TT_PHASE) {
        $rtn.= "&nbsp;&nbsp;<a href=\"".$TPL["url_alloc_task"]."projectID=".$this->get_value("projectID");
        $rtn.= "&parentTaskID=".$this->get_id()."\">New Subtask</a>";
      }

      $options["nobr_taskName"] and $rtn .= "</nobr>";
      
 
      if ($show_links && $options["show_details"] && $this->get_value("taskDescription")) {
        $d = $this->get_value("taskDescription");
        $d = preg_replace("/\s+/"," ",$d);
        $d = nl2br(trim($d));
        $d = str_replace("&nbsp;"," ",$d);
        $d = preg_replace("/\s+/"," ",$d);
        $d = wordwrap($d,150,"<br />");
        $d = str_replace("<br />","<br />&nbsp;".$indent_space,$d);
        $d = preg_replace("/<br \/>$/","",$d);
        $rtn.="<br />&nbsp;".$indent_space.$d;
      }
      $rtn.= "</td>";

      if ($options["show_person"]) {
        $rtn.= "  <td valign=\"top\" width=\"10%\">";
        $person = $this->get_foreign_object("person");
        $rtn.= $person->get_value("username");
        $rtn.= "  </td>";
      }

      if ($options["show_project"]) {
        $rtn.= "  <td valign=\"top\">";
        $project = $this->get_foreign_object("project");
        $rtn.= $project->get_value("projectName");
        $rtn.= "  </td>";
      }

      if ($options["show_project_short"]) {
        $rtn.= "  <td valign=\"top\">";
        $project = $this->get_foreign_object("project");
        $n = $project->get_value("projectShortName") or $n = $project->get_value("projectName");
        $rtn.= $n;
        $rtn.= "  </td>";
      }


      if ($options["show_priorities"]) {
        $rtn.= "  <td valign=\"top\">";
        $rtn.= $this->get_value("priority");
        $rtn.= "  </td>";

        // The projectPriority field will only be present if we loaded from a query that 
        // joined the task table with the project table
        $rtn.= "  <td valign=\"top\">";
        $rtn.= $this->get_row_value("projectPriority");
        $rtn.= "  </td>";

        // The daysUntilDue field will only be present if we loaded from a query that 
        // calculates this field
        $rtn.= "  <td valign=\"top\" align=\"center\">";
        $rtn.= $this->get_row_value("daysUntilDue");
        $rtn.= "  </td>";

        // The priorityFactor field will only be present if we loaded from a query that 
        // calculates this field
        $rtn.= "  <td valign=\"top\" align=\"center\">";
        $rtn.= number_format($this->get_row_value("priorityFactor"), 0);
        $rtn.= "  </td>";
      }

      if ($status_type != "none") {
        $rtn.= "  <td valign=\"top\">";
        $rtn.= $this->get_status($format, $status_type)."\n";
        $rtn.= "  </td>";
      }
      $rtn.= "\n</tr>\n";
      // end

// }}}
// else text format {{{
    } else {
      // Text format

      if ($options["show_project"]) {
        $project = $this->get_foreign_object("project");
        $rtn.= "\n";
        $rtn.= $project->get_value("projectName");
        $rtn.= " -> ";
      }

      if ($options["show_project_short"]) {
        $project = $this->get_foreign_object("project");
        $rtn.= "\n";
        $n = $project->get_value("projectShortName") or $n = $project->get_value("projectName");
        $rtn.= $n;
        $rtn.= " -> ";
      }


      $rtn.= $this->get_value("taskName");

      if ($options["show_person"]) {
        $person = $this->get_foreign_object("person");
        $rtn.= " (".$person->get_value("username").")";
      }

      $rtn.= "\n";
      $rtn.= $this->get_status($format, $status_type)."\n";

      if ($show_links) {
        $rtn.= $this->get_url();
      }
      $rtn.= "\n";


    }
// }}}

    if ($include_children) {
      $rtn.= $this->get_children_summary($options, $include_children, $child_filter, $format, $indent + 1);
    }

    return $rtn;
  }


  function get_children_summary($options = "", $include_grandchildren = true, $child_filter = "", $format = "html", $indent = 0, $user_id = "") {
    $rtn = "";
    $children = $this->get_children($child_filter);
    if (count($children)) {
      reset($children);
      while (list(, $child) = each($children)) {
        $rtn.= $child->get_summary($options, $include_grandchildren, $child_filter, $format, $indent);
      }
    }
    return $rtn;
  }
*/

  function get_url() {
    global $sess;
    $url = SCRIPT_PATH."project/task.php?taskID=".$this->get_id();
    $url = $sess->email_url($url);
    return $url;
  }

  // The definitive method of getting a list of tasks
  function get_task_list($_FORM) {

    
    // Join them up with commars and add a restrictive sql clause subset
    if (is_array($_FORM["projectIDs"]) && count($_FORM["projectIDs"])) {
      $_FORM["projectIDs"] = "project.projectID IN (".implode(",",$_FORM["projectIDs"]).")";
    } else {
      $_FORM["projectIDs"] = "1";
    }

    // Task level filtering
    if ($_FORM["taskStatus"]) {

      $taskStatusFilter = array("completed"=>"(task.dateActualCompletion IS NOT NULL AND task.dateActualCompletion != '')"
                               ,"not_completed"=>"(task.dateActualCompletion IS NULL OR task.dateActualCompletion = '')"
                               ,"in_progress"=>"((task.dateActualCompletion IS NULL OR task.dateActualCompletion = '') AND (task.dateActualStart IS NOT NULL AND task.dateActualStart != ''))"
                               ,"overdue"=>"((task.dateActualCompletion IS NULL OR task.dateActualCompletion = '') 
                                             AND 
                                             (task.dateTargetCompletion IS NOT NULL AND task.dateTargetCompletion != '' AND '".date("Y-m-d")."' > task.dateTargetCompletion))"
                               );
      $filter[] = $taskStatusFilter[$_FORM["taskStatus"]];
    }

    if (count($_FORM["taskTypeID"]==1) && !$_FORM["taskTypeID"][0]) {
      $_FORM["taskTypeID"] = "";
    }

    if (is_array($_FORM["taskTypeID"]) && count($_FORM["taskTypeID"])) {
      $filter[] = "(taskTypeID in (".implode(",",$_FORM["taskTypeID"])."))";

    } else if ($_FORM["taskTypeID"]) {
      $filter[] = sprintf("(taskTypeID = %d)",$_FORM["taskTypeID"]);
    }

    if ($_FORM["personID"]) {
      $filter[] = sprintf("(personID = %d)",$_FORM["personID"]);
    }

    if ($_FORM["limit"]) {
      $limit = sprintf("limit %d",$_FORM["limit"]);
    }



    if ($_FORM["showDates"]) {
      $_FORM["showDate1"] = true;
      $_FORM["showDate2"] = true;
      $_FORM["showDate3"] = true;
      $_FORM["showDate4"] = true;
    }

    $_FORM["people_cache"] = get_cached_table("person");
    $_FORM["timeUnit_cache"] = get_cached_table("timeUnit");

    // A header row

    if ($_FORM["showHeader"]) {

      $summary.= "\n<tr>";
      $_FORM["taskView"] == "prioritised" && $_FORM["showProject"]
                             and $summary.= "\n<td>&nbsp;</td>";
      $summary.= "\n<td>&nbsp;</td>";
      $_FORM["showPriority"] and $summary.= "\n<td class=\"col\"><b><nobr>Priority</nobr></b></td>"; 
      $_FORM["showPriority"] and $summary.= "\n<td class=\"col\"><b><nobr>Task Pri</nobr></b></td>"; 
      $_FORM["showPriority"] and $summary.= "\n<td class=\"col\"><b><nobr>Proj Pri</nobr></b></td>"; 
      $_FORM["showStatus"]   and $summary.= "\n<td class=\"col\"><b><nobr>Status</nobr></b></td>"; 
      $_FORM["showCreator"]  and $summary.= "\n<td class=\"col\"><b><nobr>Task Creator</nobr></b></td>";
      $_FORM["showAssigned"] and $summary.= "\n<td class=\"col\"><b><nobr>Assigned To</nobr></b></td>";
      $_FORM["showTimes"]    and $summary.= "\n<td class=\"col\"><b><nobr>Estimate</nobr></b></td>";
      $_FORM["showTimes"]    and $summary.= "\n<td class=\"col\"><b><nobr>Actual</nobr></b></td>";
      $_FORM["showDate1"]    and $summary.= "\n<td class=\"col\"><b><nobr>Targ Start</nobr></b></td>";
      $_FORM["showDate2"]    and $summary.= "\n<td class=\"col\"><b><nobr>Targ Compl</nobr></b></td>";
      $_FORM["showDate3"]    and $summary.= "\n<td class=\"col\"><b><nobr>Act Start</nobr></b></td>";
      $_FORM["showDate4"]    and $summary.= "\n<td class=\"col\"><b><nobr>Act Compl</nobr></b></td>";
      $_FORM["showPercent"]  and $summary.= "\n<td class=\"col\"><b><nobr>%</nobr></b></td>";
      $summary.="\n</tr>";
    }


    if ($_FORM["taskView"] == "byProject") {


      $q = "SELECT projectID, projectName, clientID, projectPriority FROM project WHERE ".$_FORM["projectIDs"]. " ORDER BY projectName";
      $db = new db_alloc;
      $db->query($q);
      
      while ($db->next_record()) {
        
        $project = new project;
        $project->read_db_record($db);
        $tasks = $project->get_task_children(0,$filter,$_FORM["padding"]);

        if (count($tasks)) {
          $print = true;

          $_FORM["showProject"] and $summary.= "\n<tr>";
          $_FORM["showProject"] and $summary.= "\n  <td class=\"tasks\" colspan=\"21\">";
          $_FORM["showProject"] and $summary.= "\n    <strong><a href=\"".$project->get_url()."\">".$project->get_value("projectName")."</a></strong>&nbsp;&nbsp;".$project->get_navigation_links();
          $_FORM["showProject"] and $summary.= "\n  </td>";
          $_FORM["showProject"] and $summary.= "\n</tr>";

          foreach ($tasks as $task) {
            $task["projectPriority"] = $db->f("projectPriority");
            $summary.= task::get_task_list_tr($task,$_FORM);
          }
          $summary.= "<td class=\"col\" colspan=\"21\">&nbsp;</td>";
        }
      }

    } else if ($_FORM["taskView"] == "prioritised") {
          
      if (is_array($filter) && count($filter)) {
        $f = " AND ".implode(" AND ",$filter);
      }
          
      $q = "SELECT task.*, projectName, projectShortName, clientID, projectPriority, 
                   IF(task.dateTargetCompletion IS NULL, \"-\",
                     TO_DAYS(task.dateTargetCompletion) - TO_DAYS(NOW())) as daysUntilDue,
                     priority * POWER(projectPriority, 2) * 
                       IF(task.dateTargetCompletion IS NULL, 
                         8,
                         ATAN(
                           (
                             TO_DAYS(task.dateTargetCompletion) - TO_DAYS(NOW())
                           ) / 20
                         ) / 3.14 * 8 + 4
                       ) / 10 as priorityFactor
             FROM task LEFT JOIN project on task.projectID = project.projectID WHERE ".$_FORM["projectIDs"].$f." ORDER BY priorityFactor ".$limit;
      $db = new db_alloc;
      $db->query($q);
      while ($task = $db->next_record()) {
        $print = true;
        $task["project_name"] = $task["projectShortName"]  or  $task["project_name"] = $task["projectName"];
        $t = new task;
        $t->read_db_record($db);
        $task["taskLink"] = $t->get_task_link();
        $task["taskStatus"] = $t->get_status();
        $summary.= task::get_task_list_tr($task,$_FORM);
      }
    } 

    if ($print) {
      return "<table border=\"0\" cellspacing=\"0\" cellpadding=\"3\" width=\"100%\">".$summary."</table>";
    } else {
      return "<table align=\"center\"><tr><td colspan=\"10\" align=\"center\"><b>No Tasks Found</b></td></tr></table>";
    } 
  }

  function get_task_list_tr($task,$_FORM) {

    $people_cache = $_FORM["people_cache"];
    $timeUnit_cache = $_FORM["timeUnit_cache"];

    $estime = $task["timeEstimate"]; $task["timeEstimateUnitID"] and $estime.= " ".$timeUnit_cache[$task["timeEstimateUnitID"]]["timeUnitLabelA"];
    $actual = task::get_time_billed($task["taskID"]); 

                                  $summary[] = "<tr>";
    $_FORM["taskView"] == "prioritised" && $_FORM["showProject"]
                              and $summary[] = "  <td class=\"col\">".$task["project_name"]."&nbsp;</td>";
                                  $summary[] = "  <td class=\"col\" style=\"padding-left:".($task["padding"]*15+3)."\">".$task["taskLink"]."</td>";
    $_FORM["showPriority"]    and $summary[] = "  <td class=\"col\">".sprintf("%0.2f",$task["priorityFactor"])."&nbsp;</td>"; 
    $_FORM["showPriority"]    and $summary[] = "  <td class=\"col\">".sprintf("%d",$task["priority"])."&nbsp;</td>"; 
    $_FORM["showPriority"]    and $summary[] = "  <td class=\"col\">".sprintf("%d",$task["projectPriority"])."&nbsp;</td>"; 
    $_FORM["showStatus"]      and $summary[] = "  <td class=\"col\">".$task["taskStatus"]."&nbsp;</td>"; 
    $_FORM["showCreator"]     and $summary[] = "  <td class=\"col\">".$people_cache[$task["creatorID"]]["name"]."&nbsp;</td>";
    $_FORM["showAssigned"]    and $summary[] = "  <td class=\"col\">".$people_cache[$task["personID"]]["name"]."&nbsp;</td>";
    $_FORM["showTimes"]       and $summary[] = "  <td class=\"col\"><nobr>".$estime."&nbsp;</nobr></td>";
    $_FORM["showTimes"]       and $summary[] = "  <td class=\"col\"><nobr>".$actual."&nbsp;</nobr></td>";
    $_FORM["showDate1"]       and $summary[] = "  <td class=\"col\"><nobr>".$task["dateTargetStart"]."&nbsp;</nobr></td>";
    $_FORM["showDate2"]       and $summary[] = "  <td class=\"col\"><nobr>".$task["dateTargetCompletion"]."&nbsp;</nobr></td>";
    $_FORM["showDate3"]       and $summary[] = "  <td class=\"col\"><nobr>".$task["dateActualStart"]."&nbsp;</nobr></td>";
    $_FORM["showDate4"]       and $summary[] = "  <td class=\"col\"><nobr>".$task["dateActualCompletion"]."&nbsp;</nobr></td>";
    $_FORM["showPercent"]     and $summary[] = "  <td class=\"col\"><nobr>".sprintf("%d",$task["percentComplete"])."%&nbsp;</nobr></td>";
                                  $summary[] = "</tr>";

    if ($_FORM["showDescription"] && $task["taskDescription"]) {
                                  $summary[] = "<tr>";
       $_FORM["taskView"] == "prioritised" && $_FORM["showProject"]
                              and $summary[] = "  <td class=\"col\">&nbsp;</td>";
                                  $summary[] = "  <td style=\"padding-left:".($task["padding"]*15+4)."\" colspan=\"21\" class=\"col\">".$task["taskDescription"]."</td>";
                                  $summary[] = "</tr>";
    }

    $summary = "\n".implode("\n",$summary);
    return $summary;
  }
 
  function get_children_taskIDs($taskID) {
    $q = sprintf("SELECT taskID,taskTypeID FROM task WHERE parentTaskID = %d",$taskID);
    $db = new db_alloc;
    $db->query($q);

    while($db->next_record()) {
      $rtn[] = $db->f("taskID");
      if ($db->f("taskTypeID") == TT_PHASE) {
        $rtn = array_merge($rtn, task::get_children_taskIDs($db->f("taskID")));
      }
    }
    return $rtn;
  }

  function get_time_billed($taskID="") {

    if (is_object($this) && !$taskID) {
      $taskID = $this->get_id();
    }


    if ($taskID) {
      $db = new db_alloc;

      $taskIDs = task::get_children_taskIDs($taskID);
      $taskIDs[] = $taskID;
      $taskIDs = implode(",",$taskIDs);

      // Get tally from timeSheetItem table
      $q = sprintf("SELECT sum(timeSheetItemDuration) as duration,timeSheetItemDurationUnitID
                      FROM timeSheetItem
                     WHERE taskID IN (%s)
                  GROUP BY timeSheetItemDurationUnitID
                  ORDER BY timeSheetItemDurationUnitID DESC"
                  ,$taskIDs);
      $db->query($q);
      while ($db->next_record()) {
        $actual_tallys[$db->f("timeSheetItemDurationUnitID")] += $db->f("duration");
      }
      $actual_tallys or $actual_tallys = array();

      $timeUnit = new timeUnit;
      $units = $timeUnit->get_assoc_array("timeUnitID","timeUnitLabelA");

      foreach ($actual_tallys as $unit => $tally) {
        $rtn .= $br.sprintf("%0.2f",$tally)." ".$units[$unit];
        $br = ", ";
      }
      $rtn or $rtn = "0.00";
      return $rtn;
    }
  }




}


?>
