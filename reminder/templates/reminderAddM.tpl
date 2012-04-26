{page::header()}
{page::toolbar()}

<form action="{$url_alloc_reminderAdd}" method="post">
<table class="box">
    <tr>
      <th colspan="4">{$reminder_title}</th>
    </tr>
    <tr>
      <td>Date:</td>
      <td>
        {page::calendar("reminder_date",$reminder_date)}
      </td>
      <td>Time:</td>
      <td>
        <select name="reminder_hour">{$reminder_hours}</select>
        <select name="reminder_minute">{$reminder_minutes}</select>
        <select name="reminder_meridian">{$reminder_meridians}</select>
      </td>
    </tr>
    <tr>
      <td>Recurring:</td>
      <td>
        <input type="checkbox" name="reminder_recuring" {$reminder_recuring}>Yes, every
        <input type="text" size="4" name="reminder_recuring_value" value="{$reminder_recuring_value}">
        <select name="reminder_recuring_interval">{$reminder_recuring_intervals}</select>
      </td>
      <td>Advanced Notice:</td>
      <td>
        <input type="checkbox" name="reminder_advnotice" {$reminder_advnotice}>Yes
        <input type="text" size="4" name="reminder_advnotice_value" value="{$reminder_advnotice_value}">
        <select name="reminder_advnotice_interval">{$reminder_advnotice_intervals}</select>
        in advance
      </td>
    </tr>
    <tr>
      <td>Recipient:</td>
      <td>
        <select name="reminder_recipient">
          {$reminder_recipients}
        </select>
        {page::help("reminder_recipient")}
      </td>
      <td>Reminder Active</td>
      <td>
        <input type="checkbox" value="1" name="reminderActive" {$reminderActive and print "checked"}>
      </td>
    </tr>
    <tr>
      <td>Subject:</td>
      <td colspan="3">
        <input name="reminder_subject" type="text" size="60" value="{$reminder_default_subject}">
      </td>
    </tr>
    <tr>
      <td valign="top">Content:</td>
      <td colspan="2">{page::textarea("reminder_content",$reminder_default_content,array("height"=>"medium"))}</td>
    </tr>
    <tr>
      <td colspan="4" align="center">{$reminder_buttons}&nbsp;&nbsp;&nbsp;{$reminder_goto_parent}</td>
    </tr>
  </table>

  <input type="hidden" name="parentType" value="{$parentType}">
  <input type="hidden" name="parentID" value="{$parentID}">
  <input type="hidden" name="returnToParent" value="{$returnToParent}">
  <input type="hidden" name="step" value="4">
  <input type="hidden" name="reminderTime" value="{$reminderTime}">
  <input type="hidden" name="personID" value="{$personID}">


  <input type="hidden" name="sessID" value="{$sessID}">
  </form>

{page::footer()}
