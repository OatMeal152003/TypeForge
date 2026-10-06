-- TypeForge migration 004: arcade-era achievements (all fire from existing criteria types).
INSERT IGNORE INTO achievements (code, title, description, icon, criteria_type, criteria_value, points, is_active) VALUES
('SPEED_DEMON', 'Speed Demon', 'Reach 80 words per minute in any test.', 'fa-fire', 'wpm_reach', 80.00, 60, 1),
('MARATHON', 'Marathon', 'Complete 100 typing tests.', 'fa-road', 'tests_count', 100.00, 80, 1),
('PERFECTIONIST', 'Perfectionist', 'Finish any test with 100% accuracy.', 'fa-gem', 'accuracy_reach', 100.00, 70, 1),
('STREAK_7', 'Week Warrior', 'Type on 7 consecutive days.', 'fa-calendar-check', 'streak_days', 7.00, 90, 1);
