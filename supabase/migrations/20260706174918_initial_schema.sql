CREATE EXTENSION IF NOT EXISTS "pgcrypto";
CREATE TYPE parish_enum AS ENUM (
    'Kingston',
    'St. Andrew',
    'St. Thomas',
    'Portland',
    'St. Mary',
    'St. Ann',
    'Trelawny',
    'St. James',
    'Hanover',
    'Westmoreland',
    'St. Elizabeth',
    'Manchester',
    'Clarendon',
    'St. Catherine'
);
CREATE TYPE member_status_enum AS ENUM (
    'active',
    'inactive',
    'minor',
    'visitor',
    'deceased'
);
CREATE TYPE user_role_enum AS ENUM (
    'admin',
    'pastor',
    'clergy',
    'clerk',
    'ministry leader',
    'secretary'
);
CREATE TYPE user_status_enum AS ENUM ('active', 'inactive');
CREATE TYPE event_type_enum AS ENUM (
    'church_service',
    'ministry_meeting',
    'sunday_school',
    'vestry',
    'wedding',
    'funeral',
    'baptism',
    'anniversary',
    'birthday'
);
CREATE TYPE age_group_enum AS ENUM (
    'ages_3_under',
    'ages_4_to_8',
    'ages_9_to_11',
    'ages_12_above'
);
CREATE TABLE service_types (
    service_type_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name TEXT NOT NULL UNIQUE,
    -- e.g. 'Sunday AM', 'Sunday PM', 'Midweek'
    is_active BOOLEAN NOT NULL DEFAULT true,
    sort_order INT
);
CREATE TABLE ministry_roles (
    role_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name TEXT NOT NULL UNIQUE,
    is_active BOOLEAN NOT NULL DEFAULT true
);
CREATE TABLE next_of_kin (
    nk_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    first_name TEXT NOT NULL,
    last_name TEXT NOT NULL,
    address_1 TEXT,
    address_2 TEXT,
    parish parish_enum,
    relation TEXT NOT NULL,
    telephone TEXT,
    email TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE TABLE members (
    mem_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    first_name TEXT NOT NULL,
    mid_init TEXT,
    last_name TEXT NOT NULL,
    dob DATE,
    gender TEXT,
    address_1 TEXT,
    address_2 TEXT,
    parish parish_enum,
    telephone TEXT,
    email TEXT,
    status member_status_enum NOT NULL DEFAULT 'active',
    date_joined DATE,
    passing_date DATE,
    nk_id UUID REFERENCES next_of_kin(nk_id) ON DELETE
    SET NULL,
        avatar_path TEXT,
        -- storage object path, e.g. 'members/<mem_id>.jpg'
        created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
        updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
        CONSTRAINT chk_passing_date_requires_deceased CHECK (
            passing_date IS NULL
            OR status = 'deceased'
        )
);
CREATE INDEX idx_members_status ON members(status);
CREATE INDEX idx_members_nk_id ON members(nk_id);
-- ---------------------------------------------------------------------
-- 5. users
-- ---------------------------------------------------------------------
-- Recommended: back this with Supabase Auth rather than a custom
-- password column. `uid` becomes a 1:1 FK to auth.users(id), and
-- Supabase handles hashing/sessions/resets for you. `mem_id` is
-- optional, for staff who are also recorded as members (pastor,
-- clerk, etc.) so you don't duplicate their name/contact info.
CREATE TABLE users (
    uid UUID PRIMARY KEY REFERENCES auth.users(id) ON DELETE CASCADE,
    mem_id UUID REFERENCES members(mem_id) ON DELETE
    SET NULL,
        username TEXT NOT NULL UNIQUE,
        role user_role_enum NOT NULL,
        status user_status_enum NOT NULL DEFAULT 'active',
        avatar_path TEXT,
        -- storage object path, e.g. 'users/<uid>.jpg'
        created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
        updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE INDEX idx_users_mem_id ON users(mem_id);
CREATE TABLE ministries (
    min_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name TEXT NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE TABLE ministry_members (
    ministry_member_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    mem_id UUID NOT NULL REFERENCES members(mem_id) ON DELETE CASCADE,
    min_id UUID NOT NULL REFERENCES ministries(min_id) ON DELETE CASCADE,
    role_id UUID REFERENCES ministry_roles(role_id),
    date_joined DATE NOT NULL DEFAULT CURRENT_DATE,
    UNIQUE (mem_id, min_id)
);
CREATE INDEX idx_ministry_members_mem ON ministry_members(mem_id);
CREATE INDEX idx_ministry_members_min ON ministry_members(min_id);
CREATE TABLE events (
    event_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    event_type event_type_enum NOT NULL,
    min_id UUID REFERENCES ministries(min_id) ON DELETE CASCADE,
    -- only for ministry_meeting
    service_type_id UUID REFERENCES service_types(service_type_id),
    -- only for church_service
    date DATE NOT NULL,
    description TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    CONSTRAINT chk_event_context CHECK (
        (
            event_type = 'ministry_meeting'
            AND min_id IS NOT NULL
            AND service_type_id IS NULL
        )
        OR (
            event_type = 'church_service'
            AND service_type_id IS NOT NULL
            AND min_id IS NULL
        )
        OR (
            event_type IN (
                'sunday_school',
                'vestry',
                'wedding',
                'funeral',
                'baptism',
                'anniversary',
                'birthday'
            )
            AND min_id IS NULL
            AND service_type_id IS NULL
        )
    )
);
CREATE INDEX idx_events_type_date ON events(event_type, date);
CREATE TABLE event_subjects (
    event_subject_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    event_id UUID NOT NULL REFERENCES events(event_id) ON DELETE CASCADE,
    mem_id UUID NOT NULL REFERENCES members(mem_id) ON DELETE CASCADE,
    subject_role TEXT NOT NULL,
    -- e.g. 'bride', 'groom', 'baptized', 'celebrant', 'deceased', 'spouse'
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    UNIQUE (event_id, mem_id)
);
CREATE INDEX idx_event_subjects_event ON event_subjects(event_id);
CREATE INDEX idx_event_subjects_member ON event_subjects(mem_id);
CREATE TABLE event_members (
    event_member_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    mem_id UUID NOT NULL REFERENCES members(mem_id) ON DELETE CASCADE,
    event_id UUID NOT NULL REFERENCES events(event_id) ON DELETE CASCADE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    UNIQUE (mem_id, event_id)
);
CREATE INDEX idx_event_members_event ON event_members(event_id);
CREATE INDEX idx_event_members_mem ON event_members(mem_id);
CREATE OR REPLACE FUNCTION sync_funeral_event_to_member() RETURNS TRIGGER AS $$
DECLARE v_event_type event_type_enum;
v_event_date DATE;
BEGIN
SELECT event_type,
    date INTO v_event_type,
    v_event_date
FROM events
WHERE event_id = NEW.event_id;
IF v_event_type = 'funeral' THEN
UPDATE members
SET status = 'deceased',
    passing_date = v_event_date
WHERE mem_id = NEW.mem_id;
END IF;
RETURN NEW;
END;
$$ LANGUAGE plpgsql;
CREATE TRIGGER trg_sync_funeral_event
AFTER
INSERT ON event_subjects FOR EACH ROW EXECUTE FUNCTION sync_funeral_event_to_member();
CREATE TABLE sunday_school_attendance (
    ss_attendance_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    event_id UUID NOT NULL REFERENCES events(event_id) ON DELETE CASCADE,
    mem_id UUID NOT NULL REFERENCES members(mem_id) ON DELETE CASCADE,
    age_group age_group_enum NOT NULL,
    recorded_by UUID REFERENCES users(uid),
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    UNIQUE (event_id, mem_id)
);
CREATE INDEX idx_ss_attendance_event ON sunday_school_attendance(event_id);
CREATE INDEX idx_ss_attendance_member ON sunday_school_attendance(mem_id);
CREATE TABLE vestry_hours (
    vestry_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    mem_id UUID NOT NULL REFERENCES members(mem_id) ON DELETE CASCADE,
    event_id UUID REFERENCES events(event_id) ON DELETE
    SET NULL,
        date DATE NOT NULL,
        hours_logged NUMERIC(5, 2) NOT NULL CHECK (hours_logged >= 0),
        duties TEXT,
        recorded_by UUID REFERENCES users(uid),
        created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE INDEX idx_vestry_hours_member ON vestry_hours(mem_id);
CREATE INDEX idx_vestry_hours_date ON vestry_hours(date);
CREATE OR REPLACE FUNCTION check_is_minister() RETURNS TRIGGER AS $$ BEGIN IF NOT EXISTS (
        SELECT 1
        FROM ministry_members mm
            JOIN ministry_roles mr ON mr.role_id = mm.role_id
        WHERE mm.mem_id = NEW.mem_id
            AND mr.name = 'Minister'
    ) THEN RAISE EXCEPTION 'mem_id % is not recorded as a Minister',
    NEW.mem_id;
END IF;
RETURN NEW;
END;
$$ LANGUAGE plpgsql;
CREATE TRIGGER trg_check_is_minister BEFORE
INSERT
    OR
UPDATE ON vestry_hours FOR EACH ROW EXECUTE FUNCTION check_is_minister();
CREATE TABLE headcount_attendance (
    headcount_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    event_id UUID NOT NULL UNIQUE REFERENCES events(event_id) ON DELETE CASCADE,
    attendee_count INT NOT NULL CHECK (attendee_count >= 0),
    recorded_by UUID REFERENCES users(uid),
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE OR REPLACE VIEW upcoming_birthdays AS
SELECT mem_id,
    first_name,
    last_name,
    dob,
    EXTRACT(
        YEAR
        FROM age(dob)
    ) AS turning_age,
    make_date(
        EXTRACT(
            YEAR
            FROM CURRENT_DATE
        )::INT,
        EXTRACT(
            MONTH
            FROM dob
        )::INT,
        EXTRACT(
            DAY
            FROM dob
        )::INT
    ) AS this_years_birthday
FROM members
WHERE status != 'deceased'
    AND dob IS NOT NULL
ORDER BY EXTRACT(
        MONTH
        FROM dob
    ),
    EXTRACT(
        DAY
        FROM dob
    );
-- Example: birthdays in the next 14 days
-- SELECT * FROM upcoming_birthdays
-- WHERE this_years_birthday BETWEEN CURRENT_DATE AND CURRENT_DATE + INTERVAL '14 days';
CREATE OR REPLACE FUNCTION set_updated_at() RETURNS TRIGGER AS $$ BEGIN NEW.updated_at = now();
RETURN NEW;
END;
$$ LANGUAGE plpgsql;
CREATE TRIGGER trg_members_updated_at BEFORE
UPDATE ON members FOR EACH ROW EXECUTE FUNCTION set_updated_at();
CREATE TRIGGER trg_users_updated_at BEFORE
UPDATE ON users FOR EACH ROW EXECUTE FUNCTION set_updated_at();
INSERT INTO storage.buckets (id, name, public)
VALUES ('profile-photos', 'profile-photos', true) ON CONFLICT (id) DO NOTHING;
CREATE POLICY "Public read access to profile photos" ON storage.objects FOR
SELECT USING (bucket_id = 'profile-photos');
CREATE POLICY "Staff can upload member photos" ON storage.objects FOR
INSERT TO authenticated WITH CHECK (
        bucket_id = 'profile-photos'
        AND (storage.foldername(name)) [1] = 'members'
        AND EXISTS (
            SELECT 1
            FROM users
            WHERE users.uid = auth.uid()
                AND users.status = 'active'
        )
    );
CREATE POLICY "Staff can update member photos" ON storage.objects FOR
UPDATE TO authenticated USING (
        bucket_id = 'profile-photos'
        AND (storage.foldername(name)) [1] = 'members'
        AND EXISTS (
            SELECT 1
            FROM users
            WHERE users.uid = auth.uid()
                AND users.status = 'active'
        )
    );
CREATE POLICY "Staff can delete member photos" ON storage.objects FOR DELETE TO authenticated USING (
    bucket_id = 'profile-photos'
    AND (storage.foldername(name)) [1] = 'members'
    AND EXISTS (
        SELECT 1
        FROM users
        WHERE users.uid = auth.uid()
            AND users.status = 'active'
    )
);
-- Staff can upload/replace their own avatar (folder name must match
-- their own uid): users/<uid>.jpg
CREATE POLICY "Users can upload their own avatar" ON storage.objects FOR
INSERT TO authenticated WITH CHECK (
        bucket_id = 'profile-photos'
        AND (storage.foldername(name)) [1] = 'users'
        AND (storage.foldername(name)) [2] = auth.uid()::text
    );
CREATE POLICY "Users can update their own avatar" ON storage.objects FOR
UPDATE TO authenticated USING (
        bucket_id = 'profile-photos'
        AND (storage.foldername(name)) [1] = 'users'
        AND (storage.foldername(name)) [2] = auth.uid()::text
    );
CREATE POLICY "Users can delete their own avatar" ON storage.objects FOR DELETE TO authenticated USING (
    bucket_id = 'profile-photos'
    AND (storage.foldername(name)) [1] = 'users'
    AND (storage.foldername(name)) [2] = auth.uid()::text
);
-- Recommended app-side convention when uploading:
--   members: `members/${mem_id}.jpg`  -> save that path into members.avatar_path
--   users:   `users/${uid}.jpg`       -> save that path into users.avatar_path
-- To render: supabase.storage.from('profile-photos').getPublicUrl(avatar_path)