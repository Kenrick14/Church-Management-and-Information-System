<?php
// vestryHoursData.php — data layer for the Vestry Hours tab.

/**
 * All logged vestry hours, most recent first. RLS decides what each
 * viewer actually sees: Pastor/Clergy and Clerk/Secretary see
 * everyone's (view-only for reporting), Admin sees and manages all.
 */
function fetch_vestry_hours(): ?array
{
    $result = supabase_rest('GET', 'vestry_hours', [
        'select' => '*,members(mem_id,first_name,last_name)',
        'order'  => 'date.desc',
    ]);

    if (!$result['ok'] || !is_array($result['data'])) {
        return null;
    }

    return $result['data'];
}

/**
 * Members who hold the 'Minister' ministry role — the only people the
 * check_is_minister trigger will actually allow a vestry_hours row
 * for. Filtering the picker to just these avoids offering choices
 * that would be silently rejected at save time.
 */
function fetch_ministers_lookup(): array
{
    // ministry_roles must be embedded (with !inner) for a filter on
    // its column to actually restrict which ministry_members rows
    // come back — referencing an unembedded table in a filter is
    // silently ignored by PostgREST, not an error, which would have
    // made this quietly return everyone instead of just Ministers.
    $result = supabase_rest('GET', 'ministry_members', [
        'select'              => 'members(mem_id,first_name,last_name),ministry_roles!inner(name)',
        'ministry_roles.name' => 'eq.Minister',
    ]);

    if (!$result['ok'] || !is_array($result['data'])) {
        return [];
    }

    // Dedupe — someone could theoretically hold the Minister role in
    // more than one ministry, which would otherwise list them twice.
    $seen = [];
    $ministers = [];
    foreach ($result['data'] as $row) {
        $member = $row['members'] ?? null;
        if ($member === null || isset($seen[$member['mem_id']])) {
            continue;
        }
        $seen[$member['mem_id']] = true;
        $ministers[] = $member;
    }

    usort($ministers, fn($a, $b) => strcmp($a['first_name'], $b['first_name']));

    return $ministers;
}
