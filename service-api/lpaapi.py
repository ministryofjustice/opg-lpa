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
    print(f'token : {token}')
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
    print(f'userId : {userId}')
    return userId

def getUserDetails():
    token = getTokenByAuthenticating()
    userId = getIdOfAuthenticatedUser(token)
    userPath = f'{apiRoot}/v2/user/{userId}'
    tokenhdr = {"Token": token}
    r = s.get(userPath, headers=tokenhdr)
    print(r.json())

def getApplications():
    token = getTokenByAuthenticating()
    userId = getIdOfAuthenticatedUser(token)
    applicationPath = f'{apiRoot}/v2/user/{userId}/applications'
    tokenhdr = {"Token": token}
    r = s.get(applicationPath, headers=tokenhdr)
    print(r.json())
    return r.json()

def makeNewLpa():
    token = getTokenByAuthenticating()
    userId = getIdOfAuthenticatedUser(token)
    applicationPath = f'{apiRoot}/v2/user/{userId}/applications'
    tokenhdr = {"Token": token}
    emptyData = []
    r = s.post(applicationPath, headers=tokenhdr, data=emptyData)
    print(r.json())
    id = r.json()['id']
    print(f'lpa Id : {id}')
    return id

def setLpaType(lpaId, lpaType = 'health-and-welfare'):
    #r = s.put(applicationPath, headers=tokenhdr)
    token = getTokenByAuthenticating()
    userId = getIdOfAuthenticatedUser(token)
    lpatype = {"type":"health-and-welfare"}
    typePath = f'{apiRoot}/v2/user/{userId}/applications/{lpaId}/type'
    tokenhdr = {"Token": token}
    r = s.put(typePath, headers=tokenhdr, data=lpatype)
    print(r.json())
