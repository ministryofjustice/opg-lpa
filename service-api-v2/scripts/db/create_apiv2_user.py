from sqlalchemy import *
import os
pgConnStringTemplate = 'postgresql+psycopg2://{username}:{password}@{hostname}/{dbname}'
pgConnectionString = pgConnStringTemplate.format(username = os.getenv('OPG_LPA_POSTGRES_USERNAME') , password = os.getenv('OPG_LPA_POSTGRES_PASSWORD') , hostname = os.getenv('OPG_LPA_POSTGRES_HOSTNAME') , dbname = os.getenv('OPG_LPA_POSTGRES_NAME'))
      
engine = create_engine(pgConnectionString)
v2user = os.getenv('OPG_LPA_POSTGRES_APIV2_USERNAME')

def apiv2_user_exists():
    sQL = "SELECT 1 FROM pg_roles WHERE ROLNAME = '{user}'".format(user = v2user)
    rawConn = engine.raw_connection()
    cur = rawConn.cursor()
    cur.execute(sQL)
    userCount = cur.rowcount 
    cur.close()
    return userCount > 0

def create_apiv2_user():
    print("Creating {user} user.".format(user = v2user))
    sQL = "CREATE USER {user} WITH PASSWORD '{password}'".format(user = v2user, password = os.getenv('OPG_LPA_POSTGRES_APIV2_PASSWORD'))
    engine.execute(sQL)

def grant_privileges():
    sQL = "GRANT ALL PRIVILEGES ON TABLE \"perf_feedback\" TO {user};".format(user = v2user)
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
