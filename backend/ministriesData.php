<?php
function fetch_ministries(): ?array
{
    $result = supabase_rest('GET', 'ministries', [
        'select' => '*,ministry_members(mem_id)',
        'order'  => 'name.asc',
    ]);

    if (!$result['ok'] || !is_array($result['data'])) {
        return null;
    }

    foreach ($result['data'] as &$ministry) {
        $ministry['member_count'] = count($ministry['ministry_members'] ?? []);
    }
    unset($ministry);

    return $result['data'];
}

function fetch_ministry_detail(string $minId): ?array
{
    $result = supabase_rest('GET', 'ministries', [
        'select'  => '*,ministry_members(ministry_member_id,role_id,date_joined,'
            . 'members(mem_id,first_name,last_name),'
            . 'ministry_roles(name))',
        'min_id'  => 'eq.' . $minId,
    ]);

    if (!$result['ok'] || empty($result['data'][0])) {
        return null;
    }

    $ministry = $result['data'][0];
    $ministry['member_count'] = count($ministry['ministry_members'] ?? []);

    return $ministry;
}

/** Lightweight lookup list for the roster "Role" dropdown. */
function fetch_ministry_roles_lookup(): array
{
    $result = supabase_rest('GET', 'ministry_roles', [
        'select'    => 'role_id,name',
        'is_active' => 'eq.true',
        'order'     => 'name.asc',
    ]);

    return ($result['ok'] && is_array($result['data'])) ? $result['data'] : [];
}
