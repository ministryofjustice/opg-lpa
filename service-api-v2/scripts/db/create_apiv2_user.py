from sqlalchemy import *
import os
pgConnStringTemplate = 'postgresql+psycopg2://{username}:{password}@{hostname}/{dbname}'
pgConnectionString = pgConnStringTemplate.format(username = os.getenv('OPG_LPA_POSTGRES_USERNAME') , password = os.getenv('OPG_LPA_POSTGRES_PASSWORD') , hostname = os.getenv('OPG_LPA_POSTGRES_HOSTNAME') , dbname = os.getenv('OPG_LPA_POSTGRES_NAME'))
      
engine = create_engine(pgConnectionString)
metadata = MetaData()
conn = engine.connect()
inspector = inspect(conn)

def show_perf_feedback_table_details():
  columns = inspector.get_columns('perf_feedback')
  for column in columns:
      print(column["name"], column["type"])

def create_apiv2_user():
    # using OPG_LPA_POSTGRES_APIV2 blah, create the user if not already there, and grant privileges on perfplat table
    pass

#print(inspector.get_table_names())

if inspector.has_table('perf_feedback'):
  print('perf_feedback table already exists')
  print('perf_feedback table contains:')
  show_perf_feedback_table_details()
else:
  print('perf_feedback table not there ')

