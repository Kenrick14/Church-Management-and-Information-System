<?php
// membersData.php — backend data layer for the Members page.
// No HTML output here at all; this file is `require`d by
// frontend/members.php, never hit directly by the browser.
//
// Assumes supabase_client.php has already been loaded (auth_guard.php,
// required before this file, takes care of that).

/**
 * Fetch members from Supabase via PostgREST.
 *
 * @return array|null  The member rows on success, or null if the
 *                      Supabase call failed / isn't configured yet —
 *                      the frontend falls back to demo data in that
 *                      case so the page never breaks during setup.
 */
function fetch_members(): ?array
{
    $result = supabase_rest('GET', 'members', [
        'select' => 'mem_id,first_name,last_name,status,parish,telephone,email,date_joined',
        'order'  => 'date_joined.desc',
    ]);

    if ($result['ok'] && is_array($result['data'])) {
        return $result['data'];
    }

    return null;
}
