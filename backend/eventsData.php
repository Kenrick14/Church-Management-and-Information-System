<?php
function fetch_events(): ?array
{
    $result = supabase_rest('GET', 'events', [
        'select' => '*,'
            . 'ministries(name),'
            . 'service_types(name),'
            . 'event_subjects(subject_role,members(mem_id,first_name,last_name))',
        'date'   => 'gte.' . date('Y-m-d'),
        'order'  => 'date.asc',
    ]);

    if (!$result['ok'] || !is_array($result['data'])) {
        return null;
    }

    return $result['data'];
}

/**
 * One event's full detail (for the future View/Edit modals) — same
 * embed shape as fetch_events(), just filtered to one row.
 */
function fetch_event_detail(string $eventId): ?array
{
    $result = supabase_rest('GET', 'events', [
        'select'   => '*,'
            . 'ministries(name),'
            . 'service_types(name),'
            . 'event_subjects(event_subject_id,subject_role,members(mem_id,first_name,last_name))',
        'event_id' => 'eq.' . $eventId,
    ]);

    if (!$result['ok'] || empty($result['data'][0])) {
        return null;
    }

    return $result['data'][0];
}

/** Lightweight lookup list for the "Ministry" dropdown. */
function fetch_ministries_lookup(): array
{
    $result = supabase_rest('GET', 'ministries', [
        'select' => 'min_id,name',
        'order'  => 'name.asc',
    ]);

    return ($result['ok'] && is_array($result['data'])) ? $result['data'] : [];
}

/** Lightweight lookup list for the "Service Type" dropdown. */
function fetch_service_types_lookup(): array
{
    $result = supabase_rest('GET', 'service_types', [
        'select'    => 'service_type_id,name',
        'is_active' => 'eq.true',
        'order'     => 'sort_order.asc',
    ]);

    return ($result['ok'] && is_array($result['data'])) ? $result['data'] : [];
}

/** Lightweight lookup list for member-picker dropdowns (life events). */
function fetch_members_lookup(): array
{
    $result = supabase_rest('GET', 'members', [
        'select' => 'mem_id,first_name,last_name',
        'order'  => 'first_name.asc',
    ]);

    return ($result['ok'] && is_array($result['data'])) ? $result['data'] : [];
}
