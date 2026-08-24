<?php

/**
 * Fetch sunday_school events up to and including today, most recent
 * first, with a count of who's currently marked present.
 */
function fetch_sunday_school_events(): ?array
{
    $result = supabase_rest('GET', 'events', [
        'select'     => 'event_id,date,description,sunday_school_attendance(mem_id)',
        'event_type' => 'eq.sunday_school',
        'date'       => 'lte.' . date('Y-m-d'),
        'order'      => 'date.desc',
    ]);

    if (!$result['ok'] || !is_array($result['data'])) {
        return null;
    }

    return $result['data'];
}

function fetch_children_lookup(): array
{
    $result = supabase_rest('GET', 'members', [
        'select' => 'mem_id,first_name,last_name,dob',
        'status' => 'eq.minor',
        'order'  => 'first_name.asc',
    ]);

    return ($result['ok'] && is_array($result['data'])) ? $result['data'] : [];
}

/** Existing attendance (just the mem_ids) for one sunday_school event — used to pre-check the roster when editing. */
function fetch_sunday_school_attendance_for_event(string $eventId): array
{
    $result = supabase_rest('GET', 'sunday_school_attendance', [
        'select'   => 'mem_id',
        'event_id' => 'eq.' . $eventId,
    ]);

    return ($result['ok'] && is_array($result['data'])) ? array_column($result['data'], 'mem_id') : [];
}
