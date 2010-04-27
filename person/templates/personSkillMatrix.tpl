{page::header()}
{page::toolbar()}
<table class="box">
  <tr>
    <th>Skill Matrix</th>
    <th class="right">
      <a class='magic toggleFilter' href=''>Show Filter</a>
      {if $current_user->have_perm(PERM_PERSON_READ_MANAGEMENT)}
        <a href="{$url_alloc_personSkillAdd}">Edit Skill Items</a>
      {/}
    </th>
  </tr> 
  <tr>
    <td colspan="2" align="center">
      <table>
        <tr>
          <td>{show_filter("templates/personSkillFilterS.tpl")}</td>
        </tr>
      </table>
    </td>
  </tr>
  <tr>
    <td colspan="2"><img src="../images/skill_senior.png" alt="S" align="absmiddle"> Senior,
      <img src="../images/skill_advanced.png" alt="A" align="absmiddle"> Advanced,
      <img src="../images/skill_intermediate.png" alt="I" align="absmiddle"> Intermediate,
      <img src="../images/skill_junior.png" alt="J" align="absmiddle"> Junior,
      <img src="../images/skill_novice.png" alt="N" align="absmiddle"> Novice
    </td>
  </tr>
  <tr>
    <td colspan="2">
      <table>
        {show_skill_expertise()}
      </table>
    </td>
  </tr>
</table>
{page::footer()}
