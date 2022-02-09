from sqlalchemy import *
import os

postgresUrl = 'postgresql+psycopg2://{}:{}@{}/{}'.format(
        os.getenv('OPG_LPA_POSTGRES_USERNAME') , 
        os.getenv('OPG_LPA_POSTGRES_PASSWORD') , 
        os.getenv('OPG_LPA_POSTGRES_HOSTNAME') , 
        os.getenv('OPG_LPA_POSTGRES_NAME'))
      
engine = create_engine(postgresUrl)
feedbackuser = os.getenv('OPG_LPA_POSTGRES_FEEDBACK_USERNAME')

def feedback_user_exists():
    sQL = f"SELECT 1 FROM pg_roles WHERE ROLNAME = '{feedbackuser}'"
    rawConn = engine.raw_connection()
    cur = rawConn.cursor()
    cur.execute(sQL)
    userCount = cur.rowcount 
    cur.close()
    return userCount > 0

def create_feedback_user():
    print(f"Creating {feedbackuser} user.")
    sQL = f"CREATE USER {feedbackuser} WITH PASSWORD '{os.getenv('OPG_LPA_POSTGRES_FEEDBACK_PASSWORD')}'"
    engine.execute(sQL)

def grant_privileges():
    sQL = f"GRANT ALL PRIVILEGES ON TABLE \"perf_feedback\" TO {feedbackuser};"
    engine.execute(sQL)

conn = engine.connect()
inspector = inspect(conn)
if not inspector.has_table('perf_feedback'):
    print('Cannot find perf_feedback table in db. Table needs to exist in order to grant privileges to user. Exiting ....')
    exit()
else:
    print('Found perf_feedback table')

if feedback_user_exists():
    print('feedback user already exists.')
else:
    create_feedback_user()
    grant_privileges()
