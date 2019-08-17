import json
import sys
sys.path.insert(0, './lambda')
from service import lambda_handler as lambda_handler
with open('test.json') as f:
    event = json.load(f)
print("start of handler test...")
lambda_handler(event, 'context')
print("end of handler test.")
