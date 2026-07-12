<?php

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
        'select' => 'mem_id,first_name,last_name,status,parish,telephone,email,date_joined,avatar_path',
        'order'  => 'date_joined.desc',
    ]);

    if (!$result['ok'] || !is_array($result['data'])) {
        return null;
    }

    foreach ($result['data'] as &$member) {
        $member['avatar_url'] = !empty($member['avatar_path'])
            ? supabase_public_url('profile-photos', $member['avatar_path'])
            : null;
    }
    unset($member);

    return $result['data'];
}

/**
 * @return array|null  The member row (with a nested 'next_of_kin'
 *                      object), or null if not found / the call failed.
 */
function fetch_member_detail(string $memId): ?array
{
    $result = supabase_rest('GET', 'members', [
        'select' => '*,next_of_kin(nk_id,first_name,last_name,relation,address_1,address_2,parish,telephone,email)',
        'mem_id' => 'eq.' . $memId,
    ]);

    if (!$result['ok'] || empty($result['data'][0])) {
        return null;
    }

    $member = $result['data'][0];
    $member['avatar_url'] = !empty($member['avatar_path'])
        ? supabase_public_url('profile-photos', $member['avatar_path'])
        : null;

    return $member;
}
