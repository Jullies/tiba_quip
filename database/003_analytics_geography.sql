-- Applied idempotently by tools/setup.php after checking the existing table.
ALTER TABLE analytics_sessions ADD COLUMN country char(2) NULL AFTER os;
CREATE INDEX country_started ON analytics_sessions(country,started_at);
