<?php
namespace BCCHR\PermaBanUsers;

use ExternalModules\AbstractExternalModule;

class PermaBanUsers extends AbstractExternalModule
{
    /**
     * Fetch usernames listed in the PermaBan project
     */
    public function getPermaBannedUsers()
    {
        $pid = $this->getSystemSetting('perma_ban_project_id');
        $field = 'username'; // unique identifier 
        if (!$pid || !$field) {
            error_log("PermaBanUsers: Missing required settings (perma_ban_project_id or username_field).");
            return [];
        }
        // Get the first form name and completion field
        $metadata = \REDCap::getDataDictionary($pid, 'array');
        $firstField = array_key_first($metadata);
        $instrument = $metadata[$firstField]['form_name'];
        $completionField = $instrument . '_complete';

        $fieldsToPull = [$field, $completionField];

        $data = \REDCap::getData($pid, 'array', null, $fieldsToPull);
        $usernames = [];

        foreach ($data as $record) {
            foreach ($record as $event_id => $fields) {
                if (!empty($fields[$field]) && $fields[$completionField] == '2') {
                    $usernames[] = strtolower(trim($fields[$field]));
                }
            }
        }
        return array_unique($usernames);
    }

    /**
     * Hook into User Rights page and modify the user list
     */
    public function redcap_user_rights_page($project_id)
    {
        $banned = $this->getPermaBannedUsers();
        echo "<script>console.log('[PermaBanUsers] Banned users: " . json_encode($banned) . "');</script>";
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                const bannedUsers = " . json_encode($banned) . ";
                const select = document.querySelector('select[name=\"unsuspenduser\"]');
                if (!select) return;
                [...select.options].forEach(option => {
                    if (bannedUsers.includes(option.value.toLowerCase())) {
                        console.log('Hiding permanently banned user:', option.value);
                        option.remove();
                    }
                });
            });
        </script>";
    }
}