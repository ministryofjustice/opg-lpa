from sqlalchemy import create_engine
from sqlalchemy.orm import sessionmaker


class Database:

    # example connstr for postgres:
    # postgresql://scott:tiger@localhost:5432/mydatabase
    def __init__(self, connstr):
        engine = create_engine(connstr)
        self.session = sessionmaker(bind=engine)()

    def query(self, *args, **kwargs):
        return self.session.query(*args, **kwargs)

    def add(self, obj):
        self.session.add(obj)
        return self.session.commit()

    def delete(self, modelClass, id):
        obj = self.session.query(modelClass).filter(modelClass.id==id).first()
        self.session.delete(obj)
        return self.session.commit()
