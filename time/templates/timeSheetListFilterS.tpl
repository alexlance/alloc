      <form action="{$url_alloc_timeSheetList}" method="post">
      <table class="filter" align="center">
        <tr>
          <td>Project</td>
          <td>User Name</td>
          <td>Date From</td>
          <td>Status</td>
        </tr>
        <tr>
          <td>
            <select name="projectID">
              <option value=""> </option>
              {$show_project_options}
            </select>
          </td>
          <td>
            <select name="personID">
              <option value="">
              {$show_userID_options}
            </select>
          </td>
          <td>{get_calendar("dateFrom",$TPL["dateFrom"])}</td>
          <td>
            <select name="status">
              <option value=""> </option>
              {$show_status_options}
            </select>
          </td>
          <td>&nbsp;&nbsp;<input type="submit" name="applyFilter" value="Filter"></td>
        </tr>
      </table>
      </form>

