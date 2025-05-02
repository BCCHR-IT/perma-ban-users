<?php
require_once dirname(__FILE__) . '/../../redcap_connect.php';

use ExternalModules\ExternalModules;

$module = ExternalModules::getModuleInstance('perma_ban_users');
echo "<script>console.log('Loaded manage.php');</script>";


if (!defined('SUPER_USER') || !SUPER_USER) {
    exit("<div style='color: red;'>Access Denied: Only Super Admins can access this page.</div>");
}

$pid = $module->getSystemSetting('perma_ban_project_id');
$field = 'username'; // unique identifier

function getNextRecordId($pid) {
    $data = \REDCap::getData($pid, 'array', null, ['record_id']);
    $record_ids = array_map('intval', array_keys($data));
    return $record_ids ? (max($record_ids) + 1) : 1;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = strtolower(trim($_POST['username']));
    if (!empty($username)) {
        $record_id = getNextRecordId($pid);
        // $result = \REDCap::saveData($pid, 'array', [
        //     $record_id => [
        //         'record_id' => $record_id,
        //         $field => $username
        //     ]
        // ]);
        // if (!empty($result['errors'])) {
        //     $errors = is_array($result['errors']) ? implode(', ', $result['errors']) : $result['errors'];
        //     echo "<div style='color: red;'>Failed to add user: $errors</div>";
        // } else {
        //     echo "<div style='color: green;'>User <b>$username</b> added to the ban list as Record ID $record_id.</div>";
        // }
        $data = [
            [
                'record_id' => $record_id,
                $field => $username
            ]
        ];
        \REDCap::saveData($pid, 'json', json_encode($data));
    } else {
        echo "<div style='color: red;'>Username cannot be empty.</div>";
    }
}
?>

<h3>Permanently Ban a User</h3>
<form method="POST" style="margin-bottom: 20px;">
    <label><strong>REDCap Username:</strong></label><br>
    <input type="text" name="username" placeholder="e.g., testuser1" required style="padding: 5px; width: 250px;">
    <button type="submit" style="margin-left: 10px; padding: 5px;">Add to Ban List</button>
</form>

<hr>
<h4>Currently Banned Users:</h4>
<ul>
<?php
foreach ($module->getPermaBannedUsers() as $u) {
    echo "<li>" . htmlspecialchars($u) . "</li>";
}
?>
</ul>