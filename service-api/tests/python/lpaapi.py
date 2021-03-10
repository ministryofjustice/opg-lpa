import requests

apiRoot = "http://localhost:7001"
s = requests.Session()

def authenticate(username = "seeded_test_user@digital.justice.gov.uk", password = "Pass1234"):
    # authenticate using the suppled creds, returning the resulting token and the userId
    credentials = {"username":username,"password":password}
    authPath = f'{apiRoot}/v2/authenticate'
    r = requests.get(authPath, data=credentials)
    token = r.json()['token']
    userId = r.json()['userId']
    return {"Token": token}, userId

def makeNewLpa():
    token, userId = authenticate()
    applicationPath = f'{apiRoot}/v2/user/{userId}/applications'
    emptyData = []
    r = s.post(applicationPath, headers=token, data=emptyData)
    id = r.json()['id']
    #print(f'lpa Id : {id}')
    return id

def deleteLpa(lpaId):
    token, userId = authenticate()
    lpaPath = f'{apiRoot}/v2/user/{userId}/applications/{lpaId}'
    emptyData = []
    r = s.delete(lpaPath, headers=token)
    print(r)

def setLpaType(lpaId, lpaType = 'health-and-welfare'):
    token, userId = authenticate()
    lpatype = {"type": lpaType}
    typePath = f'{apiRoot}/v2/user/{userId}/applications/{lpaId}/type'
    r = s.put(typePath, headers=token, data=lpatype)

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

def addPrimaryAttorney(lpaId):
    token, userId = authenticate()
    primaryAttorneyPath = f'{apiRoot}/v2/user/{userId}/applications/{lpaId}/primary-attorneys'
    primaryAttorneyDetails = {"name":{"title":"Mrs","first":"Amy","last":"Wheeler"},"dob":{"date":"1988-10-22T00:00:00.000000+0000"},"id":None,"address":{"address1":"Brickhill Cottage","address2":"Birch Cross","address3":"Marchington, Uttoxeter, Staffordshire","postcode":"ST14 8NX"},"email":{"address":"opglpademo+AmyWheeler@gmail.com"},"type":"human"}
    #primaryAttorneyDetails = {"name":{"title":"Mr","first":"David","last":"Wheeler"},"dob":{"date":"1972-03-12T00:00:00.000000+0000"},"id":None,"address":{"address1":"Brickhill Cottage","address2":"Birch Cross","address3":"Marchington, Uttoxeter, Staffordshire","postcode":"ST14 8NX"},"email":{"address":"opglpademo+DavidWheeler@gmail.com"},"type":"human"}
    r = s.post(primaryAttorneyPath, headers=token, json=primaryAttorneyDetails)
    print(r.json())

def deletePrimaryAttorney(lpaId, index = 1):
    token, userId = authenticate()
    primaryAttorneyPath = f'{apiRoot}/v2/user/{userId}/applications/{lpaId}/primary-attorneys/{index}'
    r = s.delete(primaryAttorneyPath, headers=token)
    print(r)

def addReplacementAttorney(lpaId):
    token, userId = authenticate()
    replacementAttorneyPath = f'{apiRoot}/v2/user/{userId}/applications/{lpaId}/replacement-attorneys'
    replacementAttorneyDetails = {"name":{"title":"Ms","first":"Isobel","last":"Ward"},"dob":{"date":"1937-02-01T00:00:00.000000+0000"},"id":None,"address":{"address1":"2 Westview","address2":"Staplehay","address3":"Trull, Taunton, Somerset","postcode":"TA3 7HF"},"email":None,"type":"human"}
    r = s.post(replacementAttorneyPath, headers=token, json=replacementAttorneyDetails)
    print(r)
    print(r.content)

def deleteReplacementAttorney(lpaId, index = 1):
    token, userId = authenticate()
    replacementAttorneyPath = f'{apiRoot}/v2/user/{userId}/applications/{lpaId}/replacement-attorneys/{index}'
    r = s.delete(replacementAttorneyPath, headers=token)
    print(r)

def setCertificateProvider(lpaId):
    token, userId = authenticate()
    certProviderPath = f'{apiRoot}/v2/user/{userId}/applications/{lpaId}/certificate-provider'
    certProvider = {"name":{"title":"Mr","first":"Reece","last":"Richards"},"address":{"address1":"11 Brookside","address2":"Cholsey","address3":"Wallingford, Oxfordshire","postcode":"OX10 9NN"}}
    r = s.put(certProviderPath, headers=token, json=certProvider)
    #import pdb; pdb.set_trace()
    print(r)
    print(r.content)

