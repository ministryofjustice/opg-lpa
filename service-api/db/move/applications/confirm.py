import os
import pprint
from pymongo import MongoClient
import psycopg2
from datetime import datetime
import json
import pytz
from bson.codec_options import CodecOptions


class Encoder(json.JSONEncoder):
    def default(self, obj):
        if isinstance(obj, datetime):
            # Example: 1983-02-01T00:00:00.000Z
            return obj.strftime('%Y-%m-%dT%H:%M:%S.000Z')
        elif isinstance(obj, float):
            print("here")
            exit(0)
            return int(obj)
        else:
            return json.JSONEncoder.default(self, obj)

# Setup Postgres
url = "dbname='"+os.environ['OPG_LPA_POSTGRES_NAME']\
      + "' user='"+os.environ['OPG_LPA_POSTGRES_USERNAME']\
      + "' host='"+os.environ['OPG_LPA_POSTGRES_HOSTNAME']\
      + "' password='"+os.environ['OPG_LPA_POSTGRES_PASSWORD']+"'"

postgres = psycopg2.connect(url)
cursor = postgres.cursor()


# Setup MongoDB
url = 'mongodb://opglpa-api:'+os.environ['OPG_LPA_API_MONGODB_PASSWORD']+'@mongodb-01,mongodb-02,mongodb-03/opglpa-api'
url = url + '?ssl=true&ssl_cert_reqs=CERT_NONE'

client = MongoClient(url)
db = client['opglpa-api']

collection = db['lpa']
collection = collection.with_options(codec_options=CodecOptions(tz_aware=True, tzinfo=pytz.timezone('UTC')))


# Return each LPA
#for lpa in collection.find({"_id": 59974222637}):
for lpa in collection.find():
    print("---------------------")

    #pprint.pprint(lpa)

    # Return the matching LPA from Postgres
    cursor.execute("SELECT * FROM applications WHERE id = %s", (lpa['_id'],))
    row = cursor.fetchone()

    #pprint.pprint(row)

    # If there's a user in Mongo, but not in Postgres, check if that's valid
    if 'user' in lpa and row[1] != lpa['user']:
        print("User miss-match; ensure delete was deliberate.")
        continue

    # Check all of the DateTimes
    dateTimes = {2: "updatedAt", 3: "startedAt", 4: "createdAt", 5: "completedAt", 6: "lockedAt"}

    for index, key in dateTimes.items():
        if key in lpa and type(row[index]) is datetime.date:
            row[index] = row[index].replace(tzinfo=None)
            if row[index] != lpa[key]:
                pprint.pprint(row[index])
                pprint.pprint(lpa[key])
                print(key+" miss-match")
                exit(1)
        elif key in lpa and type(row[index]) is None:
            pprint.pprint(row[index])
            pprint.pprint(lpa[key])
            print(key+" missing")
            exit(1)

    # Check the booleans, ints and strings
    bools = {7: "locked", 8: "whoAreYouAnswered", 9: 'seed', 10: 'repeatCaseNumber', 14: 'search'}
    for index, key in bools.items():
        if key in lpa and lpa[key] != row[index]:
            print(key+" miss-match")
            exit(1)

    # Check dictionaries
    dicts = {11: 'document', 12: 'payment', 13: 'metadata'}
    for index, key in dicts.items():
        if key in lpa and row[index] is not None:

            # Format numbers to match
            if isinstance(lpa[key], dict):
                for k, v in lpa[key].items():
                    if isinstance(v, float) and v.is_integer():
                        lpa[key][k] = int(v)

            #pprint.pprint(lpa[key])
            if key == 'document' and  isinstance(lpa[key]['primaryAttorneys'], dict):
                lpa[key]['primaryAttorneys'] = list(lpa[key]['primaryAttorneys'].values())

            if key == 'document' and  isinstance(lpa[key]['replacementAttorneys'], dict):
                lpa[key]['replacementAttorneys'] = list(lpa[key]['replacementAttorneys'].values())

            if key == 'document' and  isinstance(lpa[key]['peopleToNotify'], dict):
                lpa[key]['peopleToNotify'] = list(lpa[key]['peopleToNotify'].values())

            #pprint.pprint(lpa[key])
            a = json.dumps(row[index], sort_keys=True, cls=Encoder)
            b = json.dumps(lpa[key], sort_keys=True, cls=Encoder)
            #print(a)
            #print(b)
            if a != b:
                print(key + " miss-match")
                exit(1)

print('All went well!')


    # 0 id bigint PRIMARY KEY,
    # 1 user text,
    # 2 "updatedAt" timestamp with time zone NOT NULL,
    # 3 "startedAt" timestamp with time zone,
    # 4 "createdAt" timestamp with time zone,
    # 5 "completedAt" timestamp with time zone,
    # 6 "lockedAt" timestamp with time zone,
    # 7 locked boolean,
    # 8 "whoAreYouAnswered" boolean,
    # 9 seed bigint,
    # 10 "repeatCaseNumber" bigint,
    # 11 document jsonb,
    # 12 payment jsonb,
    # 13 metadata jsonb,
    # 14 search text
