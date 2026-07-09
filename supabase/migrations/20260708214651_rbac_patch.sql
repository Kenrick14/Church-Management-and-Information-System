-- =====================================================================
-- CMIS Migration: RBAC patch
-- =====================================================================
-- 1. Clerk/Secretary can now enter headcount_attendance (previously
--    view-only), matching "enter event data" more literally.
-- 2. Clerk/Secretary gets DELETE alongside Admin on every table they
--    already have write access to.
-- 3. Ministry Leaders can view their own ministry's member roster
--    (not just the meeting headcount numbers).
-- =====================================================================
-- ---------------------------------------------------------------------
-- 1. Clerk/Secretary can enter headcount_attendance
-- ---------------------------------------------------------------------
CREATE POLICY "Clerk enters headcount_attendance" ON headcount_attendance FOR
INSERT WITH CHECK (current_user_role() IN ('clerk', 'secretary'));
CREATE POLICY "Clerk edits headcount_attendance" ON headcount_attendance FOR
UPDATE USING (current_user_role() IN ('clerk', 'secretary')) WITH CHECK (current_user_role() IN ('clerk', 'secretary'));
-- ---------------------------------------------------------------------
-- 2. Clerk/Secretary DELETE, matching every table they already write to
-- ---------------------------------------------------------------------
CREATE POLICY "Clerk deletes members" ON members FOR DELETE USING (current_user_role() IN ('clerk', 'secretary'));
CREATE POLICY "Clerk deletes next_of_kin" ON next_of_kin FOR DELETE USING (current_user_role() IN ('clerk', 'secretary'));
CREATE POLICY "Clerk deletes ministry_members" ON ministry_members FOR DELETE USING (current_user_role() IN ('clerk', 'secretary'));
CREATE POLICY "Clerk deletes events" ON events FOR DELETE USING (current_user_role() IN ('clerk', 'secretary'));
CREATE POLICY "Clerk deletes event_members" ON event_members FOR DELETE USING (current_user_role() IN ('clerk', 'secretary'));
CREATE POLICY "Clerk deletes event_subjects" ON event_subjects FOR DELETE USING (current_user_role() IN ('clerk', 'secretary'));
CREATE POLICY "Clerk deletes headcount_attendance" ON headcount_attendance FOR DELETE USING (current_user_role() IN ('clerk', 'secretary'));
-- ---------------------------------------------------------------------
-- 3. Ministry Leader can view their own ministry's member roster
-- ---------------------------------------------------------------------
-- Joins through ministry_members to find which members belong to a
-- ministry this user leads. This grants SELECT on `members` itself —
-- previously Ministry Leaders had no access to the members table at
-- all, only to ministry_members / events / headcount_attendance.
CREATE POLICY "Leader views own ministry roster members" ON members FOR
SELECT USING (
        mem_id IN (
            SELECT mm.mem_id
            FROM ministry_members mm
            WHERE mm.min_id IN (
                    SELECT led_ministry_ids()
                )
        )
    );