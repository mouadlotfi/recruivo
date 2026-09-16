-- POSTGRES_DB (recruivo_db) is created by the entrypoint. These two are the
-- demo dataset and the test suite's database; both are owned by the role the
-- entrypoint created so it can install trusted extensions such as citext.
--
-- The entrypoint runs this file with ON_ERROR_STOP=1, so a bare CREATE DATABASE
-- aborts the whole initialisation when the name already exists - which is the
-- case for any stack whose own POSTGRES_DB is the demo database - and a literal
-- OWNER must match POSTGRES_USER or it fails with "role does not exist". Hence
-- \gexec over a guarded CREATE.
SELECT format('CREATE DATABASE %I OWNER %I', 'recruivo_demo_db', current_user)
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'recruivo_demo_db')
\gexec

SELECT format('CREATE DATABASE %I OWNER %I', 'recruivo_test', current_user)
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'recruivo_test')
\gexec
