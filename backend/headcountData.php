<?php
function fetch_headcount_events(): ?array
{
    $result = supabase_rest('GET', 'events', [
        'select'     => 'event_id,event_type,date,description,'
            . 'ministries(name),'
            . 'service_types(name),'
            . 'headcount_attendance(headcount_id,attendee_count)',
        'event_type' => 'in.(church_service,ministry_meeting)',
        'date'       => 'lte.' . date('Y-m-d'),
        'order'      => 'date.desc',
    ]);

    if (!$result['ok'] || !is_array($result['data'])) {
        return null;
    }

    return $result['data'];
}
