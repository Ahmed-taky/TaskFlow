-- ============================================================
--  Canonical schema (drop + recreate)
--  Run manually against an empty/new database:
--    psql -h $DB_HOST -p $DB_PORT -U $DB_USER -d $DB_NAME -f Schema.sql
-- ============================================================

\c mydb

-- ---- Enum types ------------------------------------------------
CREATE TYPE task_status   AS ENUM ('PENDING', 'IN_PROGRESS', 'COMPLETED');
CREATE TYPE project_type  AS ENUM ('SYSTEM', 'NORMAL');

-- ---- users -----------------------------------------------------
CREATE TABLE users (
    id         SERIAL PRIMARY KEY,
    name       VARCHAR(100)  NOT NULL,
    email      VARCHAR(255)  UNIQUE NOT NULL,
    password   VARCHAR(255)  NOT NULL,
    created_at TIMESTAMP     DEFAULT NOW()
);

-- ---- projects --------------------------------------------------
CREATE TABLE projects (
    id          SERIAL PRIMARY KEY,
    name        VARCHAR(100)  NOT NULL,
    type        project_type NOT NULL DEFAULT 'NORMAL',
    goal        TEXT    ,
    reflection  TEXT    ,
    due_date    DATE,
    user_id     INTEGER       NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    start_date  DATE,
    created_at  TIMESTAMP     DEFAULT NOW(),
    updated_at  TIMESTAMP     DEFAULT NOW()
);

-- One SYSTEM project (Inbox) per user
CREATE UNIQUE INDEX one_system_project_per_user
    ON projects(user_id)
    WHERE type = 'SYSTEM';

-- ---- tasks -----------------------------------------------------
-- Ownership is indirect: Task -> Project -> User (no user_id here)
CREATE TABLE tasks (
    id          SERIAL PRIMARY KEY,
    title       VARCHAR(100)  NOT NULL,
    description VARCHAR(255),
    status      task_status   NOT NULL DEFAULT 'PENDING',
    project_id  INTEGER       NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    created_at  TIMESTAMP     DEFAULT NOW(),
    updated_at  TIMESTAMP     DEFAULT NOW(),
    completed_at TIMESTAMP
);

-- ---- updated_at trigger ----------------------------------------
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER set_projects_updated_at
    BEFORE UPDATE ON projects
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER set_tasks_updated_at
    BEFORE UPDATE ON tasks
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();


-- ============================================================
--  Reference: ALTERs for migrating an existing database.
--  Run manually via psql — each block is one conceptual step.
-- ============================================================
--
-- 1. Create the project_type enum
--    CREATE TYPE project_type AS ENUM ('SYSTEM', 'NORMAL');
--
-- 2. Add the type column to existing projects
--    ALTER TABLE projects ADD COLUMN type project_type NOT NULL DEFAULT 'NORMAL';
--
-- 3. Rename due → due_date
--    ALTER TABLE projects RENAME COLUMN due TO due_date;
--
-- 4. Unique index: one SYSTEM project per user
--    CREATE UNIQUE INDEX one_system_project_per_user
--        ON projects(user_id) WHERE type = 'SYSTEM';
--
-- 5. tasks: drop old FK, make project_id NOT NULL, re-add with CASCADE
--    ALTER TABLE tasks DROP CONSTRAINT tasks_project_id_fkey;
--    ALTER TABLE tasks ALTER COLUMN project_id SET NOT NULL;
--    ALTER TABLE tasks
--        ADD CONSTRAINT tasks_project_id_fkey
--        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE;
--
-- 6. Remove user_id from tasks
--    ALTER TABLE tasks DROP COLUMN user_id;
