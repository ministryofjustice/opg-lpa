from sqlalchemy import *
import os

postgresUrl = 'postgresql+psycopg2://{}:{}@{}/{}'.format(
        os.getenv('OPG_LPA_POSTGRES_USERNAME') , 
        os.getenv('OPG_LPA_POSTGRES_PASSWORD') , 
        os.getenv('OPG_LPA_POSTGRES_HOSTNAME') , 
        os.getenv('OPG_LPA_POSTGRES_NAME'))
      
engine = create_engine(postgresUrl)
v2user = os.getenv('OPG_LPA_POSTGRES_APIV2_USERNAME')

def apiv2_user_exists():
    sQL = "SELECT 1 FROM pg_roles WHERE ROLNAME = '{}'".format(v2user)
    rawConn = engine.raw_connection()
    cur = rawConn.cursor()
    cur.execute(sQL)
    userCount = cur.rowcount 
    cur.close()
    return userCount > 0

def create_apiv2_user():
    print("Creating {} user.".format(v2user))
    sQL = "CREATE USER {} WITH PASSWORD '{}'".format(v2user, os.getenv('OPG_LPA_POSTGRES_APIV2_PASSWORD'))
    engine.execute(sQL)

def grant_privileges():
    sQL = "GRANT ALL PRIVILEGES ON TABLE \"perf_feedback\" TO {};".format(v2user)
    engine.execute(sQL)

conn = engine.connect()
inspector = inspect(conn)
if not inspector.has_table('perf_feedback'):
    print('Cannot find perf_feedback table in db. Table needs to exist in order to grant privileges to user. Exiting ....')
    exit()
else:
    print('Found perf_feedback table')

if apiv2_user_exists():
    print('apiv2 user already exists.')
else:
    create_apiv2_user()
    grant_privileges()
