from sqlalchemy import *
import os
pgConnStringTemplate = 'postgresql+psycopg2://{username}:{password}@{hostname}/{dbname}'
pgConnectionString = pgConnStringTemplate.format(username = os.getenv('OPG_LPA_POSTGRES_USERNAME') , password = os.getenv('OPG_LPA_POSTGRES_PASSWORD') , hostname = os.getenv('OPG_LPA_POSTGRES_HOSTNAME') , dbname = os.getenv('OPG_LPA_POSTGRES_NAME'))
      
engine = create_engine(pgConnectionString)
conn = engine.connect()
inspector = inspect(conn)

def remove_apiv2_user():
    sQL = "REVOKE ALL PRIVILEGES ON TABLE \"perf_feedback\" FROM v2user;"
    engine.execute(sQL)
    sQL = "DROP USER v2user"
    engine.execute(sQL)

def create_apiv2_user():
    # using OPG_LPA_POSTGRES_APIV2 blah, create the user if not already there
    #sQL = "DO $$ begin if not exists (select from pg_roles where rolname = 'v2user') then CREATE USER v2user WITH PASSWORD 'v2pass'; end if; end $$ ;"
    sQL = "CREATE USER v2user WITH PASSWORD 'v2pass'"
    engine.execute(sQL)

def grant_privileges():
    # grant privileges on perfplat table
    sQL = "GRANT ALL PRIVILEGES ON TABLE \"perf_feedback\" TO v2user;"
    engine.execute(sQL)

if not inspector.has_table('perf_feedback'):
  print('Cannot find perf_feedback table in db. Table needs to exist in order to grant privileges to user. Exiting ....')
  exit()
else:
  print('Found perf_feedback table')

remove_apiv2_user()
create_apiv2_user()
grant_privileges()
