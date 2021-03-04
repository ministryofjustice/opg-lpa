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
    tokenData = {"authToken": token}
    # note we have to call the authenticate endpoint a second time, with the token, to get the userId, which is a little odd
    authPath = f'{apiRoot}/v2/authenticate'
    r = s.get(authPath, data=tokenData)
    #print(r.json())
    userId = r.json()['userId']
    print(f'userId : {userId}')
    return userId

def getUserDetails():
    token = getTokenJsonByAuthenticating()
    tokenHdr = {"Token": token}
    userId = getIdOfAuthenticatedUser(token)
    userPath = f'{apiRoot}/v2/user/{userId}'
    r = s.get(userPath, headers=tokenHdr)
    print(r.json())

def getApplications():
    token = getTokenByAuthenticating()
    tokenHdr = {"Token": token}
    userId = getIdOfAuthenticatedUser(token)
    applicationPath = f'{apiRoot}/v2/user/{userId}/applications'
    r = s.get(applicationPath, headers=tokenHdr)
    print(r.json())
    return r.json()

def makeNewLpa():
    token = getTokenByAuthenticating()
    tokenHdr = {"Token": token}
    userId = getIdOfAuthenticatedUser(token)
    applicationPath = f'{apiRoot}/v2/user/{userId}/applications'
    emptyData = []
    r = s.post(applicationPath, headers=tokenHdr, data=emptyData)
    print(r.json())
    id = r.json()['id']
    print(f'lpa Id : {id}')
    return id

def setLpaType(lpaId, lpaType = 'health-and-welfare'):
    token = getTokenByAuthenticating()
    tokenHdr = {"Token": token}
    userId = getIdOfAuthenticatedUser(token)
    lpatype = {"type": lpaType}
    typePath = f'{apiRoot}/v2/user/{userId}/applications/{lpaId}/type'
    r = s.put(typePath, headers=tokenHdr, data=lpatype)
    print(r.json())

def setDonor(lpaId):
    token = getTokenByAuthenticating()
    tokenHdr = {"Token": token}
    userId = getIdOfAuthenticatedUser(token)
    donorDetails = '{"name":{"title":"Mrs","first":"Nancy","last":"Garrison"},"otherNames":"","address":{"address1":"Bank End Farm House","address2":"Undercliff Drive","address3":"Ventnor, Isle of Wight","postcode":"PO38 1UL"},"dob":{"date":"1988-10-22T00:00:00.000000+0000"},"email":{"address":"opglpademo+NancyGarrison@gmail.com"},"canSign":false}'
    donorPath = f'{apiRoot}/v2/user/{userId}/applications/{lpaId}/donor'
    r = s.put(donorPath, headers=tokenHdr, data=donorDetails)
    print(r.json())

def setPrimaryAttorneyDecisions(lpaId):
    token = getTokenByAuthenticating()
    tokenHdr = {"Token": token}
    userId = getIdOfAuthenticatedUser(token)
    primaryAttorneyDecisionPath = f'{apiRoot}/v2/user/{userId}/applications/{lpaId}/primary-attorney-decisions'
    primaryAttorneyDecisionDetails = '{"canSustainLife":true,"how":null,"when":null,"howDetails":null}'
    r = s.put(primaryAttorneyDecisionPath, headers=tokenHdr, data=primaryAttorneyDecisionDetails)
    print(r.json())

def setPrimaryAttorney(lpaId):
    token = getTokenByAuthenticating()
    tokenHdr = {"Token": token}
    userId = getIdOfAuthenticatedUser(token)
    primaryAttorneyPath = f'{apiRoot}/v2/user/{userId}/applications/{lpaId}/primary-attorneys'
    primaryAttorneyDetails = '{"name":{"title":"Mrs","first":"Amy","last":"Wheeler"},"dob":{"date":"1988-10-22T00:00:00.000000+0000"},"id":null,"address":{"address1":"Brickhill Cottage","address2":"Birch Cross","address3":"Marchington, Uttoxeter, Staffordshire","postcode":"ST14 8NX"},"email":{"address":"opglpademo+AmyWheeler@gmail.com"},"type":"human"}'
    r = s.post(primaryAttorneyPath, headers=tokenHdr, data=primaryAttorneyDetails)
    #import pdb; pdb.set_trace()
    print(r)
    print(r.content)
    #print(r.json())
