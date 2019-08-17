import json
from service import handler as handler
with open('test.json') as f:
    event = json.load(f)
print("start of handler test...")
handler(event, 'context')
print("end of handler test.")
