-- ---------------------------------------------------------------------
-- 1. is_active_staff() helper
-- ---------------------------------------------------------------------
-- SECURITY DEFINER so this bypasses RLS on `users` itself when it runs
-- (otherwise checking "is this user staff" would require reading
-- `users`, which is itself RLS-protected — recursive). It only ever
-- checks the calling user's own row, so this is safe.
CREATE OR REPLACE FUNCTION is_active_staff() RETURNS BOOLEAN AS $$
SELECT EXISTS (
        SELECT 1
        FROM public.users
        WHERE uid = auth.uid()
            AND status = 'active'
    );
$$ LANGUAGE sql STABLE SECURITY DEFINER
SET search_path = public;
-- ---------------------------------------------------------------------
-- 2. Enable RLS + policy per core table
-- ---------------------------------------------------------------------
ALTER TABLE members ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Active staff can manage members" ON members FOR ALL USING (is_active_staff()) WITH CHECK (is_active_staff());
ALTER TABLE next_of_kin ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Active staff can manage next_of_kin" ON next_of_kin FOR ALL USING (is_active_staff()) WITH CHECK (is_active_staff());
ALTER TABLE ministries ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Active staff can manage ministries" ON ministries FOR ALL USING (is_active_staff()) WITH CHECK (is_active_staff());
ALTER TABLE ministry_members ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Active staff can manage ministry_members" ON ministry_members FOR ALL USING (is_active_staff()) WITH CHECK (is_active_staff());
ALTER TABLE ministry_roles ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Active staff can manage ministry_roles" ON ministry_roles FOR ALL USING (is_active_staff()) WITH CHECK (is_active_staff());
ALTER TABLE service_types ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Active staff can manage service_types" ON service_types FOR ALL USING (is_active_staff()) WITH CHECK (is_active_staff());
ALTER TABLE events ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Active staff can manage events" ON events FOR ALL USING (is_active_staff()) WITH CHECK (is_active_staff());
ALTER TABLE event_members ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Active staff can manage event_members" ON event_members FOR ALL USING (is_active_staff()) WITH CHECK (is_active_staff());
ALTER TABLE event_subjects ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Active staff can manage event_subjects" ON event_subjects FOR ALL USING (is_active_staff()) WITH CHECK (is_active_staff());
ALTER TABLE sunday_school_attendance ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Active staff can manage sunday_school_attendance" ON sunday_school_attendance FOR ALL USING (is_active_staff()) WITH CHECK (is_active_staff());
ALTER TABLE vestry_hours ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Active staff can manage vestry_hours" ON vestry_hours FOR ALL USING (is_active_staff()) WITH CHECK (is_active_staff());
ALTER TABLE headcount_attendance ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Active staff can manage headcount_attendance" ON headcount_attendance FOR ALL USING (is_active_staff()) WITH CHECK (is_active_staff());
-- users: everyone can read their own row (needed at login to check
-- role/status); active staff can manage all rows (e.g. admin screens).
ALTER TABLE users ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Users can view their own row" ON users FOR
SELECT USING (auth.uid() = uid);
CREATE POLICY "Active staff can manage users" ON users FOR ALL USING (is_active_staff()) WITH CHECK (is_active_staff());
-- ---------------------------------------------------------------------
-- 3. Auto-provision public.users when a Supabase Auth account is created
-- ---------------------------------------------------------------------
-- Staff accounts get created in Supabase Auth (dashboard, or the Admin
-- API from a privileged PHP script using the service_role key — never
-- exposed to the browser). This trigger creates the matching
-- `public.users` row automatically. Default role is 'clerk' and
-- status 'active' — an admin should adjust the role afterward for
-- anyone who isn't actually a clerk.
CREATE OR REPLACE FUNCTION handle_new_auth_user() RETURNS TRIGGER AS $$ BEGIN
INSERT INTO public.users (uid, username, role, status)
VALUES (NEW.id, NEW.email, 'clerk', 'active') ON CONFLICT (uid) DO NOTHING;
RETURN NEW;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER
SET search_path = public;
CREATE TRIGGER trg_handle_new_auth_user
AFTER
INSERT ON auth.users FOR EACH ROW EXECUTE FUNCTION handle_new_auth_user();