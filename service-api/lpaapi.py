import requests

apiRoot = "http://localhost:7001"
s = requests.Session()

def getTokenByAuthenticating(username = "seeded_test_user@digital.justice.gov.uk", password = "Pass1234"):
    # authenticate using the suppled creds, and get the resulting token
    credentials = {"username":username,"password":password}
    authPath = f'{apiRoot}/v2/authenticate'
    r = requests.get(authPath, data=credentials)

    print(r.json())
    token = r.json()['token']
    print("token :")
    return token

def getIdOfAuthenticatedUser(token):
    # given a token, get the ID of the related user
    tokenhdr = {"authToken": token}
    print(tokenhdr)
    # note we have to call the authenticate endpoint a second time, with the token, to get the userId, which is a little odd
    authPath = f'{apiRoot}/v2/authenticate'
    r = s.get(authPath, data=tokenhdr)
    print(r.json())
    userId = r.json()['userId']
    print("userId :")
    return userId

def getUserDetails():
    token = getTokenByAuthenticating()
    userId = getIdOfAuthenticatedUser(token)
    userPath = f'{apiRoot}/v2/user/{userId}'
    tokenhdr = {"Token": token}
    r = s.get(userPath, headers=tokenhdr)
    print(r.json())
    #import pdb; pdb.set_trace()
