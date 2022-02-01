from sqlalchemy import *
import os
  #OPG_LPA_POSTGRES_HOSTNAME: "postgres"
# TODO env vars specified in docker-compose, for this, and those should also be in CI
pgConnectionString = 'postgresql+psycopg2://' + os.getenv('OPG_LPA_POSTGRES_USERNAME') + ':' + os.getenv('OPG_LPA_POSTGRES_PASSWORD') + '@localhost/' + os.getenv('OPG_LPA_POSTGRES_NAME')
      
engine = create_engine(pgConnectionString)
#engine = create_engine('postgresql+psycopg2://lpauser:lpapass@localhost/lpadb')
metadata = MetaData()
conn = engine.connect()
inspector = inspect(conn)

def create_perf_feedback_table():
    print('creating perf feedback table.')
    feedback = Table('perf_feedback', metadata,
        Column('id', Integer, primary_key=True),
        Column('rating', String(2), nullable=False), # up to 2-letter acronynm , VS = very satisfied , etc
        Column('comment', String(1200), nullable=False),
        Column('datetime', String(1200), nullable=False),
    )
    feedback.create(engine)

def show_perf_feedback_table_details():
  columns = inspector.get_columns('perf_feedback')
  for column in columns:
      print(column["name"], column["type"])

#print(inspector.get_table_names())

if inspector.has_table('perf_feedback'):
  print('perf_feedback already exists')
else:
  create_perf_feedback_table()

print('perf_feedback contains:')
show_perf_feedback_table_details()
