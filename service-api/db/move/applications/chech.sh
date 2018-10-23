#!/usr/bin/env bash

python3 -m venv migration
source migration/bin/activate

python -m pip install --upgrade pip

python -m pip install pymongo
python -m pip install psycopg2-binary
python -m pip install pytz
python -m pip install python-dateutil
