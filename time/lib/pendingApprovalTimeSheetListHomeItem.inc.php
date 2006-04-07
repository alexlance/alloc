<?php
class pendingApprovalTimeSheetListHomeItem extends home_item {
  function pendingApprovalTimeSheetListHomeItem() {
    home_item::home_item("pending_time_list", "Time Sheets Pending Approval", "time", "pendingApprovalTimeSheetListH.tpl", "narrow");
  }

  function show_pending_time_sheets($template_name) {
    global $current_user, $TPL;
    $db = get_pending_timesheet_db();

    while ($db->next_record()) {
      $timeSheet = new timeSheet;
      $timeSheet->read_db_record($db);
      $timeSheet->set_tpl_values();

      unset($date); 
      if ($timeSheet->get_value("status") == "manager") {
        $date = $timeSheet->get_value("dateSubmittedToManager");
      } else if ($timeSheet->get_value("status") == "admin") {
        $date = $timeSheet->get_value("dateSubmittedToAdmin");
      }

      unset($TPL["class"]);

      // older than 10 days 
      if ($date && strtotime($date) < (mktime()-864000)) {
        $TPL["class"] = " class=\"really_overdue\"";

      // older than 5 days
      } else if ($date && strtotime($date) < (mktime()-432000)) {
        $TPL["class"] = " class=\"overdue\"";
      } 
      
      $TPL["date"] = "<a href=\"".$TPL["url_alloc_timeSheet"]."timeSheetID=".$timeSheet->get_id()."\">".$date."</a>";
      
      $person = new person;
      $person->set_id($timeSheet->get_value("personID"));
      $person->select();
      $TPL["user"] = $person->get_username(1);

      $project = $timeSheet->get_foreign_object("project");
      if ($project->get_value("projectShortName")) {
        $TPL["projectName"] = $project->get_value("projectShortName");
      } else {
        $TPL["projectName"] = $project->get_value("projectName");
      }

      include_template($this->get_template_dir().$template_name, $this);
    }
  }
}

function get_pending_timesheet_db() {
/*

 -----------     -----------------     ---------------------
 | project |  <  | projectPerson |  <  | projectPersonRole |
 -----------     -----------------     ---------------------
    /\
 -------------
 | timeSheet |
 -------------
    /\
 -----------------
 | timeSheetItem |
 -----------------

*/


  global $current_user;
  $db = new db_alloc;
 
  $c = new config; 
  $timeSheetAdminEmailPersonID = $c->get_config_item("timeSheetAdminEmail");

  $query = sprintf("SELECT timeSheet.*, sum(timeSheetItem.timeSheetItemDuration * timeSheetItem.rate) as total_dollars 
                      FROM timeSheet, timeSheetItem 
                           LEFT JOIN project on project.projectID = timeSheet.projectID
                           LEFT JOIN projectPerson on project.projectID = projectPerson.projectID 
                           LEFT JOIN projectPersonRole on projectPerson.projectPersonRoleID = projectPersonRole.projectPersonRoleID
                     WHERE timeSheet.timeSheetID = timeSheetItem.timeSheetID
                       AND ((projectPerson.personID = %d AND projectPersonRole.projectPersonRoleHandle = 'timeSheetRecipient' AND timeSheet.status='manager') 
                             OR 
                            (%d = %d and timeSheet.status='admin'))
                  GROUP BY timeSheet.timeSheetID 
                  ORDER BY timeSheet.dateSubmittedToManager, timeSheet.dateSubmittedToAdmin"
                   , $current_user->get_id()
                   , $current_user->get_id()
                   , $timeSheetAdminEmailPersonID
    );

  $db->query($query);
  return $db;
}

function has_pending_timesheet() {
  $db = get_pending_timesheet_db();
  if ($db->next_record()) {
    return true;
  }
  return false;
}




?>
