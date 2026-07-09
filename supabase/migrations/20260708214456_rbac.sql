-- =====================================================================
-- CMIS Migration: Role-based access control
-- =====================================================================
-- Replaces the earlier blanket "any active staff" policies with real
-- per-role rules:
--   Administrator    — full access everywhere, manages users
--   Pastor/Clergy    — view + edit membership, view attendance, reports
--   Ministry Leader  — view/update ONLY their own ministry's records
--                       and attendance counts
--   Clerk/Secretary  — enter members + event data, standard reports
-- =====================================================================
-- ---------------------------------------------------------------------
-- 0. Remove the old blanket policies + helper (superseded)
-- ---------------------------------------------------------------------
DROP POLICY IF EXISTS "Active staff can manage members" ON members;
DROP POLICY IF EXISTS "Active staff can manage next_of_kin" ON next_of_kin;
DROP POLICY IF EXISTS "Active staff can manage ministries" ON ministries;
DROP POLICY IF EXISTS "Active staff can manage ministry_members" ON ministry_members;
DROP POLICY IF EXISTS "Active staff can manage ministry_roles" ON ministry_roles;
DROP POLICY IF EXISTS "Active staff can manage service_types" ON service_types;
DROP POLICY IF EXISTS "Active staff can manage events" ON events;
DROP POLICY IF EXISTS "Active staff can manage event_members" ON event_members;
DROP POLICY IF EXISTS "Active staff can manage event_subjects" ON event_subjects;
DROP POLICY IF EXISTS "Active staff can manage sunday_school_attendance" ON sunday_school_attendance;
DROP POLICY IF EXISTS "Active staff can manage vestry_hours" ON vestry_hours;
DROP POLICY IF EXISTS "Active staff can manage headcount_attendance" ON headcount_attendance;
DROP POLICY IF EXISTS "Active staff can manage users" ON users;
DROP FUNCTION IF EXISTS is_active_staff();
-- ---------------------------------------------------------------------
-- 1. Helper functions
-- ---------------------------------------------------------------------
-- All SECURITY DEFINER + fixed search_path so they bypass RLS on
-- `users`/`ministry_members` internally (avoids recursive RLS checks)
-- and can't be tricked via search_path hijacking.
-- Returns the caller's role, or NULL if they have no active users row
-- (inactive/unknown users pass none of the role checks below).
CREATE OR REPLACE FUNCTION current_user_role() RETURNS user_role_enum AS $$
SELECT role
FROM public.users
WHERE uid = auth.uid()
    AND status = 'active';
$$ LANGUAGE sql STABLE SECURITY DEFINER
SET search_path = public;
-- The member record tied to the logged-in staff account, if any.
CREATE OR REPLACE FUNCTION current_user_mem_id() RETURNS UUID AS $$
SELECT mem_id
FROM public.users
WHERE uid = auth.uid()
    AND status = 'active';
$$ LANGUAGE sql STABLE SECURITY DEFINER
SET search_path = public;
-- Ministries this user leads (linked via ministry_members with role 'Leader').
CREATE OR REPLACE FUNCTION led_ministry_ids() RETURNS SETOF UUID AS $$
SELECT mm.min_id
FROM ministry_members mm
    JOIN ministry_roles mr ON mr.role_id = mm.role_id
WHERE mm.mem_id = current_user_mem_id()
    AND mr.name = 'Leader';
$$ LANGUAGE sql STABLE SECURITY DEFINER
SET search_path = public;
-- ---------------------------------------------------------------------
-- 2. users — Admin manages all; everyone can see their own row
-- ---------------------------------------------------------------------
CREATE POLICY "Admin manages users" ON users FOR ALL USING (current_user_role() = 'admin') WITH CHECK (current_user_role() = 'admin');
-- (self-view policy from the previous migration is untouched/still active)
-- ---------------------------------------------------------------------
-- 3. members / next_of_kin — Admin full; Pastor/Clergy view+edit;
--    Clerk/Secretary view+enter+edit
-- ---------------------------------------------------------------------
CREATE POLICY "Admin full access members" ON members FOR ALL USING (current_user_role() = 'admin') WITH CHECK (current_user_role() = 'admin');
CREATE POLICY "Pastor clergy view members" ON members FOR
SELECT USING (current_user_role() IN ('pastor', 'clergy'));
CREATE POLICY "Pastor clergy edit members" ON members FOR
UPDATE USING (current_user_role() IN ('pastor', 'clergy')) WITH CHECK (current_user_role() IN ('pastor', 'clergy'));
CREATE POLICY "Clerk view members" ON members FOR
SELECT USING (current_user_role() IN ('clerk', 'secretary'));
CREATE POLICY "Clerk enter members" ON members FOR
INSERT WITH CHECK (current_user_role() IN ('clerk', 'secretary'));
CREATE POLICY "Clerk edit members" ON members FOR
UPDATE USING (current_user_role() IN ('clerk', 'secretary')) WITH CHECK (current_user_role() IN ('clerk', 'secretary'));
CREATE POLICY "Admin full access next_of_kin" ON next_of_kin FOR ALL USING (current_user_role() = 'admin') WITH CHECK (current_user_role() = 'admin');
CREATE POLICY "Pastor clergy view next_of_kin" ON next_of_kin FOR
SELECT USING (current_user_role() IN ('pastor', 'clergy'));
CREATE POLICY "Pastor clergy edit next_of_kin" ON next_of_kin FOR
UPDATE USING (current_user_role() IN ('pastor', 'clergy')) WITH CHECK (current_user_role() IN ('pastor', 'clergy'));
CREATE POLICY "Clerk view next_of_kin" ON next_of_kin FOR
SELECT USING (current_user_role() IN ('clerk', 'secretary'));
CREATE POLICY "Clerk enter next_of_kin" ON next_of_kin FOR
INSERT WITH CHECK (current_user_role() IN ('clerk', 'secretary'));
CREATE POLICY "Clerk edit next_of_kin" ON next_of_kin FOR
UPDATE USING (current_user_role() IN ('clerk', 'secretary')) WITH CHECK (current_user_role() IN ('clerk', 'secretary'));
-- ---------------------------------------------------------------------
-- 4. ministries — Admin full; Ministry Leader sees only their own;
--    Pastor/Clergy + Clerk/Secretary get read access (dropdowns/reports)
-- ---------------------------------------------------------------------
CREATE POLICY "Admin full access ministries" ON ministries FOR ALL USING (current_user_role() = 'admin') WITH CHECK (current_user_role() = 'admin');
CREATE POLICY "Leader views own ministries" ON ministries FOR
SELECT USING (
        min_id IN (
            SELECT led_ministry_ids()
        )
    );
CREATE POLICY "Pastor clerk view ministries" ON ministries FOR
SELECT USING (
        current_user_role() IN ('pastor', 'clergy', 'clerk', 'secretary')
    );
-- ---------------------------------------------------------------------
-- 5. ministry_members — Admin full; Leader views own roster only;
--    Clerk/Secretary can enter/edit as part of member data entry
-- ---------------------------------------------------------------------
CREATE POLICY "Admin full access ministry_members" ON ministry_members FOR ALL USING (current_user_role() = 'admin') WITH CHECK (current_user_role() = 'admin');
CREATE POLICY "Leader views own ministry roster" ON ministry_members FOR
SELECT USING (
        min_id IN (
            SELECT led_ministry_ids()
        )
    );
CREATE POLICY "Clerk view ministry_members" ON ministry_members FOR
SELECT USING (current_user_role() IN ('clerk', 'secretary'));
CREATE POLICY "Clerk enter ministry_members" ON ministry_members FOR
INSERT WITH CHECK (current_user_role() IN ('clerk', 'secretary'));
CREATE POLICY "Clerk edit ministry_members" ON ministry_members FOR
UPDATE USING (current_user_role() IN ('clerk', 'secretary')) WITH CHECK (current_user_role() IN ('clerk', 'secretary'));
-- ---------------------------------------------------------------------
-- 6. Lookup tables — Admin manages; any active staff can read
-- ---------------------------------------------------------------------
CREATE POLICY "Admin full access ministry_roles" ON ministry_roles FOR ALL USING (current_user_role() = 'admin') WITH CHECK (current_user_role() = 'admin');
CREATE POLICY "Staff view ministry_roles" ON ministry_roles FOR
SELECT USING (current_user_role() IS NOT NULL);
CREATE POLICY "Admin full access service_types" ON service_types FOR ALL USING (current_user_role() = 'admin') WITH CHECK (current_user_role() = 'admin');
CREATE POLICY "Staff view service_types" ON service_types FOR
SELECT USING (current_user_role() IS NOT NULL);
-- ---------------------------------------------------------------------
-- 7. events — Admin full; Clerk enters; Pastor/Clergy view;
--    Ministry Leader views only their own ministry's meetings
-- ---------------------------------------------------------------------
CREATE POLICY "Admin full access events" ON events FOR ALL USING (current_user_role() = 'admin') WITH CHECK (current_user_role() = 'admin');
CREATE POLICY "Clerk view events" ON events FOR
SELECT USING (current_user_role() IN ('clerk', 'secretary'));
CREATE POLICY "Clerk enter events" ON events FOR
INSERT WITH CHECK (current_user_role() IN ('clerk', 'secretary'));
CREATE POLICY "Clerk edit events" ON events FOR
UPDATE USING (current_user_role() IN ('clerk', 'secretary')) WITH CHECK (current_user_role() IN ('clerk', 'secretary'));
CREATE POLICY "Pastor clergy view events" ON events FOR
SELECT USING (current_user_role() IN ('pastor', 'clergy'));
CREATE POLICY "Leader views own ministry events" ON events FOR
SELECT USING (
        min_id IN (
            SELECT led_ministry_ids()
        )
    );
-- ---------------------------------------------------------------------
-- 8. event_members / event_subjects — Admin full; Clerk enters
--    (part of "event data"); Pastor/Clergy view
-- ---------------------------------------------------------------------
CREATE POLICY "Admin full access event_members" ON event_members FOR ALL USING (current_user_role() = 'admin') WITH CHECK (current_user_role() = 'admin');
CREATE POLICY "Clerk view event_members" ON event_members FOR
SELECT USING (current_user_role() IN ('clerk', 'secretary'));
CREATE POLICY "Clerk enter event_members" ON event_members FOR
INSERT WITH CHECK (current_user_role() IN ('clerk', 'secretary'));
CREATE POLICY "Clerk edit event_members" ON event_members FOR
UPDATE USING (current_user_role() IN ('clerk', 'secretary')) WITH CHECK (current_user_role() IN ('clerk', 'secretary'));
CREATE POLICY "Pastor clergy view event_members" ON event_members FOR
SELECT USING (current_user_role() IN ('pastor', 'clergy'));
CREATE POLICY "Admin full access event_subjects" ON event_subjects FOR ALL USING (current_user_role() = 'admin') WITH CHECK (current_user_role() = 'admin');
CREATE POLICY "Clerk view event_subjects" ON event_subjects FOR
SELECT USING (current_user_role() IN ('clerk', 'secretary'));
CREATE POLICY "Clerk enter event_subjects" ON event_subjects FOR
INSERT WITH CHECK (current_user_role() IN ('clerk', 'secretary'));
CREATE POLICY "Clerk edit event_subjects" ON event_subjects FOR
UPDATE USING (current_user_role() IN ('clerk', 'secretary')) WITH CHECK (current_user_role() IN ('clerk', 'secretary'));
CREATE POLICY "Pastor clergy view event_subjects" ON event_subjects FOR
SELECT USING (current_user_role() IN ('pastor', 'clergy'));
-- ---------------------------------------------------------------------
-- 9. sunday_school_attendance — Admin full; Pastor/Clergy + Clerk view
-- ---------------------------------------------------------------------
CREATE POLICY "Admin full access sunday_school_attendance" ON sunday_school_attendance FOR ALL USING (current_user_role() = 'admin') WITH CHECK (current_user_role() = 'admin');
CREATE POLICY "Pastor clergy view sunday_school_attendance" ON sunday_school_attendance FOR
SELECT USING (current_user_role() IN ('pastor', 'clergy'));
CREATE POLICY "Clerk view sunday_school_attendance" ON sunday_school_attendance FOR
SELECT USING (current_user_role() IN ('clerk', 'secretary'));
-- ---------------------------------------------------------------------
-- 10. vestry_hours — Admin full; Pastor/Clergy view all, edit own;
--     Clerk views (reports)
-- ---------------------------------------------------------------------
CREATE POLICY "Admin full access vestry_hours" ON vestry_hours FOR ALL USING (current_user_role() = 'admin') WITH CHECK (current_user_role() = 'admin');
CREATE POLICY "Pastor clergy view vestry_hours" ON vestry_hours FOR
SELECT USING (current_user_role() IN ('pastor', 'clergy'));
CREATE POLICY "Pastor clergy enter own vestry_hours" ON vestry_hours FOR
INSERT WITH CHECK (
        current_user_role() IN ('pastor', 'clergy')
        AND mem_id = current_user_mem_id()
    );
CREATE POLICY "Pastor clergy edit own vestry_hours" ON vestry_hours FOR
UPDATE USING (
        current_user_role() IN ('pastor', 'clergy')
        AND mem_id = current_user_mem_id()
    ) WITH CHECK (
        current_user_role() IN ('pastor', 'clergy')
        AND mem_id = current_user_mem_id()
    );
CREATE POLICY "Clerk view vestry_hours" ON vestry_hours FOR
SELECT USING (current_user_role() IN ('clerk', 'secretary'));
-- ---------------------------------------------------------------------
-- 11. headcount_attendance — Admin full; Ministry Leader manages ONLY
--     their own ministry's meeting counts; Pastor/Clergy + Clerk view
-- ---------------------------------------------------------------------
CREATE POLICY "Admin full access headcount_attendance" ON headcount_attendance FOR ALL USING (current_user_role() = 'admin') WITH CHECK (current_user_role() = 'admin');
CREATE POLICY "Pastor clergy view headcount_attendance" ON headcount_attendance FOR
SELECT USING (current_user_role() IN ('pastor', 'clergy'));
CREATE POLICY "Clerk view headcount_attendance" ON headcount_attendance FOR
SELECT USING (current_user_role() IN ('clerk', 'secretary'));
-- Ministry Leader: scoped to headcount rows whose event belongs to a
-- ministry they lead (church_service events have min_id = NULL, so
-- those are automatically excluded here).
CREATE POLICY "Leader views own ministry headcount" ON headcount_attendance FOR
SELECT USING (
        EXISTS (
            SELECT 1
            FROM events e
            WHERE e.event_id = headcount_attendance.event_id
                AND e.min_id IN (
                    SELECT led_ministry_ids()
                )
        )
    );
CREATE POLICY "Leader enters own ministry headcount" ON headcount_attendance FOR
INSERT WITH CHECK (
        EXISTS (
            SELECT 1
            FROM events e
            WHERE e.event_id = headcount_attendance.event_id
                AND e.min_id IN (
                    SELECT led_ministry_ids()
                )
        )
    );
CREATE POLICY "Leader updates own ministry headcount" ON headcount_attendance FOR
UPDATE USING (
        EXISTS (
            SELECT 1
            FROM events e
            WHERE e.event_id = headcount_attendance.event_id
                AND e.min_id IN (
                    SELECT led_ministry_ids()
                )
        )
    ) WITH CHECK (
        EXISTS (
            SELECT 1
            FROM events e
            WHERE e.event_id = headcount_attendance.event_id
                AND e.min_id IN (
                    SELECT led_ministry_ids()
                )
        )
    );