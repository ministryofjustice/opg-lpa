import requests

apiRoot = "http://localhost:7001"
s = requests.Session()

def authenticate(username = "seeded_test_user@digital.justice.gov.uk", password = "Pass1234"):
    # authenticate using the suppled creds, returning the resulting token and the userId
    credentials = {"username":username,"password":password}
    authPath = f'{apiRoot}/v2/authenticate'
    r = requests.get(authPath, data=credentials)
    token = r.json()['token']
    print(f'token : {token}')
    userId = r.json()['userId']
    print(f'userId : {userId}')
    return {"Token": token}, userId

def getUserDetails():
    token, userId = getTokenJsonByAuthenticating()
    userPath = f'{apiRoot}/v2/user/{userId}'
    r = s.get(userPath, headers=token)
    print(r.json())

def getApplications():
    token, userId = authenticate()
    applicationPath = f'{apiRoot}/v2/user/{userId}/applications'
    r = s.get(applicationPath, headers=token)
    print(r.json())
    return r.json()

def makeNewLpa():
    token, userId = authenticate()
    applicationPath = f'{apiRoot}/v2/user/{userId}/applications'
    emptyData = []
    r = s.post(applicationPath, headers=token, data=emptyData)
    print(r.json())
    id = r.json()['id']
    print(f'lpa Id : {id}')
    return id

def setLpaType(lpaId, lpaType = 'health-and-welfare'):
    token, userId = authenticate()
    lpatype = {"type": lpaType}
    typePath = f'{apiRoot}/v2/user/{userId}/applications/{lpaId}/type'
    r = s.put(typePath, headers=token, data=lpatype)
    print(r.json())

def setDonor(lpaId):
    token, userId = authenticate()
    donorDetails = '{"name":{"title":"Mrs","first":"Nancy","last":"Garrison"},"otherNames":"","address":{"address1":"Bank End Farm House","address2":"Undercliff Drive","address3":"Ventnor, Isle of Wight","postcode":"PO38 1UL"},"dob":{"date":"1988-10-22T00:00:00.000000+0000"},"email":{"address":"opglpademo+NancyGarrison@gmail.com"},"canSign":false}'
    donorPath = f'{apiRoot}/v2/user/{userId}/applications/{lpaId}/donor'
    r = s.put(donorPath, headers=token, data=donorDetails)
    print(r.json())

def setPrimaryAttorneyDecisions(lpaId):
    token, userId = authenticate()
    primaryAttorneyDecisionPath = f'{apiRoot}/v2/user/{userId}/applications/{lpaId}/primary-attorney-decisions'
    primaryAttorneyDecisionDetails = '{"canSustainLife":true,"how":null,"when":null,"howDetails":null}'
    r = s.put(primaryAttorneyDecisionPath, headers=token, data=primaryAttorneyDecisionDetails)
    print(r.json())

def setPrimaryAttorney(lpaId):
    token, userId = authenticate()
    primaryAttorneyPath = f'{apiRoot}/v2/user/{userId}/applications/{lpaId}/primary-attorneys'
    primaryAttorneyDetails = {"name":{"title":"Mrs","first":"Amy","last":"Wheeler"},"dob":{"date":"1988-10-22T00:00:00.000000+0000"},"id":None,"address":{"address1":"Brickhill Cottage","address2":"Birch Cross","address3":"Marchington, Uttoxeter, Staffordshire","postcode":"ST14 8NX"},"email":{"address":"opglpademo+AmyWheeler@gmail.com"},"type":"human"}
    #primaryAttorneyDetails = {"name":{"title":"Mr","first":"David","last":"Wheeler"},"dob":{"date":"1972-03-12T00:00:00.000000+0000"},"id":None,"address":{"address1":"Brickhill Cottage","address2":"Birch Cross","address3":"Marchington, Uttoxeter, Staffordshire","postcode":"ST14 8NX"},"email":{"address":"opglpademo+DavidWheeler@gmail.com"},"type":"human"}
    r = s.post(primaryAttorneyPath, headers=token, json=primaryAttorneyDetails)
    print(r.json())

def setReplacementAttorney(lpaId):
    token, userId = authenticate()
    replacementAttorneyPath = f'{apiRoot}/v2/user/{userId}/applications/{lpaId}/replacement-attorneys'
    replacementAttorneyDetails = {"name":{"title":"Ms","first":"Isobel","last":"Ward"},"dob":{"date":"1937-02-01T00:00:00.000000+0000"},"id":None,"address":{"address1":"2 Westview","address2":"Staplehay","address3":"Trull, Taunton, Somerset","postcode":"TA3 7HF"},"email":None,"type":"human"}
    r = s.post(replacementAttorneyPath, headers=token, json=replacementAttorneyDetails)
    #import pdb; pdb.set_trace()
    print(r)
    print(r.content)
    #print(r.json())
