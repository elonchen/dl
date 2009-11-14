<?php
require_once("include/pages.php");
$act = "glist";
pageHeader();
?>

<form action="<?php echo currentPage(); ?>" method="post">
  <ul>

<?php
if(isset($_REQUEST["purge"]) && !empty($_REQUEST["sel"]))
{
  // purge immediately
  echo "<li id=\"error_message\"><table><tr><td class=\"label\">Purged:</td>";

  $first = true;
  foreach($_REQUEST["sel"] as $id)
  {
    $sql = "SELECT * FROM grant WHERE id = " . $db->quote($id);
    $DATA = $db->query($sql)->fetch();
    if($DATA === false) continue;

    // check for permissions
    if(!$auth["admin"] && $DATA["user_id"] != $auth["id"])
      continue;

    // actually purge the grant
    if($first) $first = false;
    else echo "<tr><td></td>";
    echo "<td>" . htmlentities(grantStr($DATA)) . "</td></tr>";
    grantPurge($DATA, false);
  }

  echo "</table></li>";
}

// list active grants
$sql = "SELECT g.*, u.name AS user FROM grant g"
  . " LEFT JOIN user u ON u.id = g.user_id";
if(!$auth["admin"]) $sql .= " WHERE user_id = " . $auth["id"];

foreach($db->query($sql) as $DATA)
{
  echo "<li class=\"fileinfo\">";

  // name
  echo "<span><input class=\"element checkbox\" type=\"checkbox\" name=\"sel[]\" value=\"" . $DATA['id'] . "\"/>";
  echo "<label class=\"choice\"><a href=\"" . grantUrl($DATA) . "\">" .
    htmlentities($DATA["id"]) . "</a>";
  if($DATA["cmt"]) echo ' ' . htmlentities($DATA["cmt"]);
  echo "</label></span>";

  // parameters
  echo "<div class=\"fileinfo\"><table>";
  echo "<tr><th>Date: </th><td> " . date("d/m/Y", $DATA["time"]) . "</td></tr>";
  if($DATA["user_id"] != $auth["id"])
    echo "<tr><th>User: </th><td>" . htmlentities($DATA["user"]) . "</td></tr>";
  if(isset($DATA['pass_md5']))
    echo "<tr><th>Password: </th><td>" . str_repeat("&bull;", 5) . "</td>";

  // expire
  echo "<tr><th>Expiry: </th><td>";
  if($DATA["grant_expire"])
    echo "In " . humanTime($DATA["grant_expire"] - time());
  else
    echo "<strong>never</strong>";
  echo "</td></tr>";

  // notify
  if($DATA["notify_email"])
  {
    echo "<tr><th>Notify: </th><td>";
    $first = true;
    foreach(getEMailAddrs($DATA['notify_email']) as $email)
    {
      if($first) $first = false;
      else echo ", ";
      echo "<a href=\"mailto:" . htmlentities($email) . "\">" .
	htmlentities($email) . "</a>";
    }
    echo "</td></tr>";
  }

  echo "</table></div></li>";
}

?>

    <li class="buttons">
      <input type="reset" value="Reload" onclick="document.location.reload();"/>
      <input type="reset" value="Reset"/>
      <input type="submit" name="purge" value="Purge selected"/>
    </li>
  </ul>
</form>

<p>Total archive size: <?php echo humanSize($totalSize); ?></p>

<?php
pageFooter();
?>