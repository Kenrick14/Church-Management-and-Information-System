INSERT INTO service_types (name, is_active, sort_order)
VALUES ('Sunday Morning Service', true, 1),
    ('Sunday Evening Service', true, 2),
    ('Midweek Service', true, 3),
    ('Communion Service', true, 4) ON CONFLICT (name) DO NOTHING;
-- 'Leader' is required for the led_ministry_ids() function (Ministry
-- Leader RLS scoping) to ever match anything. 'Minister' is required
-- for the check_is_minister() trigger on vestry_hours. Without these
-- two exact names, both features silently behave as if no one
-- qualifies — not an error, just quietly never true.
INSERT INTO ministry_roles (name, is_active)
VALUES ('Leader', true),
    ('Assistant Leader', true),
    ('Minister', true),
    ('Member', true) ON CONFLICT (name) DO NOTHING;