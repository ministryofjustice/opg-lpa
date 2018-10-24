import os
import pprint
from pymongo import MongoClient
import psycopg2
from datetime import datetime
import json
from urllib.parse import quote_plus


class Encoder(json.JSONEncoder):
    def default(self, obj):
        if isinstance(obj, datetime):

            # Example: 1983-02-01T00:00:00.000Z
            year = obj.strftime('%Y').zfill(4)              # Zero pad the year.
            time = obj.strftime('-%m-%dT%H:%M:%S.%f')[:-3]  # We only want milliseconds, so drop the last 3 chars
            return year + time + 'Z'                        # Manually add the timezone

        else:
            return json.JSONEncoder.default(self, obj)


# Setup Postgres
url = "dbname='"+os.environ['OPG_LPA_POSTGRES_NAME']\
      + "' user='"+os.environ['OPG_LPA_POSTGRES_USERNAME']\
      + "' host='"+os.environ['OPG_LPA_POSTGRES_HOSTNAME']\
      + "' password='"+os.environ['OPG_LPA_POSTGRES_PASSWORD']+"'"

postgres = psycopg2.connect(url)
cursor = postgres.cursor()

# Cache allowed user IDs
valid_user_ids = set()

cursor.execute("SELECT id FROM users WHERE identity IS NOT NULL")
rows = cursor.fetchall()
for row in rows:
    valid_user_ids.add(row[0])

# Setup MongoDB
url = 'mongodb://opglpa-api:%s@mongodb-01,mongodb-02,mongodb-03/opglpa-api' % quote_plus(os.environ['OPG_LPA_API_MONGODB_PASSWORD'])
url = url + '?ssl=true&ssl_cert_reqs=CERT_NONE'

client = MongoClient(url)
db = client['opglpa-api']

collection = db['lpa']

# Return each LPA
for lpa in collection.find():

    # Return the matching LPA from Postgres
    cursor.execute("SELECT * FROM applications WHERE id = %s", (lpa['_id'],))
    row = cursor.fetchone()

    # If there's a user in Mongo, but not in Postgres, check if that's valid
    if 'user' in lpa and row[1] != lpa['user']:
        if lpa['user'] not in valid_user_ids:
            # print("User has been deleted; skipping")
            continue
        else:
            print('Missing Postgres data for live user')
            exit(1)

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

            # Convert any actor lists from dictionary to lists, then sort them by id.
            if key == 'document' and isinstance(lpa[key]['primaryAttorneys'], dict):
                lpa[key]['primaryAttorneys'] = list(lpa[key]['primaryAttorneys'].values())
                lpa[key]['primaryAttorneys'] = sorted(lpa[key]['primaryAttorneys'], key=lambda k: k['id'])

            if key == 'document' and isinstance(lpa[key]['replacementAttorneys'], dict):
                lpa[key]['replacementAttorneys'] = list(lpa[key]['replacementAttorneys'].values())
                lpa[key]['replacementAttorneys'] = sorted(lpa[key]['replacementAttorneys'], key=lambda k: k['id'])

            if key == 'document' and isinstance(lpa[key]['peopleToNotify'], dict):
                lpa[key]['peopleToNotify'] = list(lpa[key]['peopleToNotify'].values())
                lpa[key]['peopleToNotify'] = sorted(lpa[key]['peopleToNotify'], key=lambda k: k['id'])

            # ---

            # Sort actor lists by id
            if index == 11 and isinstance(row[index]['primaryAttorneys'], list):
                row[index]['primaryAttorneys'] = sorted(row[index]['primaryAttorneys'], key=lambda k: k['id'])

            if index == 11 and isinstance(row[index]['replacementAttorneys'], list):
                row[index]['replacementAttorneys'] = sorted(row[index]['replacementAttorneys'], key=lambda k: k['id'])

            if index == 11 and isinstance(row[index]['peopleToNotify'], list):
                row[index]['peopleToNotify'] = sorted(row[index]['peopleToNotify'], key=lambda k: k['id'])

            # Convert both data sets to JSON
            a = json.dumps(row[index], sort_keys=True, cls=Encoder)
            b = json.dumps(lpa[key], sort_keys=True, cls=Encoder)

            # If all is well, the JSON should exactly match.
            if a != b:
                print(a)
                print(b)
                print(key + " miss-match")
                exit(1)

# Managed to get here without exit(1)ing.
print('All went well!')

# -----------------------------
# Postgres index -> column map

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
