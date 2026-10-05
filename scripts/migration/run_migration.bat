@echo off
set PGPASSWORD=npg_YoD4CLZ2TQpw
set CONN=postgresql://neondb_owner:npg_YoD4CLZ2TQpw@ep-withered-wave-axase6mw-pooler.c-4.us-east-2.aws.neon.tech/neondb?sslmode=require&channel_binding=require

echo Step 1: Truncating all tables...
"C:\Program Files\PostgreSQL\18\bin\psql.exe" "%CONN%" -c "DO $$ DECLARE r RECORD; BEGIN FOR r IN SELECT tablename FROM pg_tables WHERE schemaname = 'public' LOOP EXECUTE 'TRUNCATE TABLE public.' || quote_ident(r.tablename) || ' CASCADE'; END LOOP; END $$;" > "F:\Research\ModPyPhp\psql_final2.log" 2>&1

echo Step 2: Disabling all triggers...
"C:\Program Files\PostgreSQL\18\bin\psql.exe" "%CONN%" -c "DO $$ DECLARE r RECORD; BEGIN FOR r IN SELECT tablename FROM pg_tables WHERE schemaname = 'public' LOOP EXECUTE 'ALTER TABLE public.' || quote_ident(r.tablename) || ' DISABLE TRIGGER ALL'; END LOOP; END $$;" >> "F:\Research\ModPyPhp\psql_final2.log" 2>&1

echo Step 3: Importing data...
"C:\Program Files\PostgreSQL\18\bin\psql.exe" "%CONN%" -v ON_ERROR_STOP=0 -f "F:\Research\ModPyPhp\modpyphp_clean.sql" >> "F:\Research\ModPyPhp\psql_final2.log" 2>&1

echo Step 4: Re-enabling all triggers...
"C:\Program Files\PostgreSQL\18\bin\psql.exe" "%CONN%" -c "DO $$ DECLARE r RECORD; BEGIN FOR r IN SELECT tablename FROM pg_tables WHERE schemaname = 'public' LOOP EXECUTE 'ALTER TABLE public.' || quote_ident(r.tablename) || ' ENABLE TRIGGER ALL'; END LOOP; END $$;" >> "F:\Research\ModPyPhp\psql_final2.log" 2>&1

echo Exit code: %ERRORLEVEL%
echo Done!
