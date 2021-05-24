import requests
import sys

apiRoot = "http://localhost:7001"
s = requests.Session()

def putToAPI(lpaId, jsonData, pathSuffix = ''):
    token, userId = authenticate()
    pathTemplate = '{apiRoot}/v2/user/{userId}/applications/{lpaId}/{pathSuffix}'
    fullPath = pathTemplate.format(apiRoot = apiRoot, userId = userId, lpaId = lpaId, pathSuffix = pathSuffix)
    print("Putting ",file=sys.stderr)
    print(jsonData,file=sys.stderr)
    r = s.put(fullPath, headers=token, json=jsonData)
    print(r,file=sys.stderr)
    print(r.json(),file=sys.stderr)

def postToAPI(lpaId, jsonData, pathSuffix = ''):
    token, userId = authenticate()
    pathTemplate = '{apiRoot}/v2/user/{userId}/applications/{lpaId}/{pathSuffix}'
    fullPath = pathTemplate.format(apiRoot = apiRoot, userId = userId, lpaId = lpaId, pathSuffix = pathSuffix)
    print("Posting ",file=sys.stderr)
    print(jsonData,file=sys.stderr)
    r = s.post(fullPath, headers=token, json=jsonData)
    print(r,file=sys.stderr)
    print(r.json(),file=sys.stderr)

def patchViaAPI(lpaId, jsonData):
    token, userId = authenticate()
    pathTemplate = '{apiRoot}/v2/user/{userId}/applications/{lpaId}'
    fullPath = pathTemplate.format(apiRoot = apiRoot, userId = userId, lpaId = lpaId)
    print("Patching ",file=sys.stderr)
    print(jsonData,file=sys.stderr)
    r = s.patch(fullPath, headers=token, json=jsonData)
    print(r,file=sys.stderr)
    print(r.json(),file=sys.stderr)

def deleteViaAPI(lpaId, jsonData, pathSuffix = ''):
    token, userId = authenticate()
    pathTemplate = '{apiRoot}/v2/user/{userId}/applications/{lpaId}/{pathSuffix}'
    fullPath = pathTemplate.format(apiRoot = apiRoot, userId = userId, lpaId = lpaId, pathSuffix = pathSuffix)
    r = s.delete(fullPath, headers=token)
    print(r)

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
    lpaTypeJson = {"type": lpaType}
    putToAPI(lpaId, lpaTypeJson, 'type')

def setDonor(lpaId):
    donorDetails = {"name":{"title":"Mrs","first":"Nancy","last":"Garrison"},"otherNames":"","address":{"address1":"Bank End Farm House","address2":"Undercliff Drive","address3":"Ventnor, Isle of Wight","postcode":"PO38 1UL"},"dob":{"date":"1988-10-22T00:00:00.000000+0000"},"email":{"address":"opglpademo+NancyGarrison@gmail.com"},"canSign":False}
    putToAPI(lpaId, donorDetails, 'donor')

def setPrimaryAttorneyDecisions(lpaId, lpaType = 'health-and-welfare'):
    # "when"  is when LPA starts (PF only) 
    # canSustainLife is for life-sustaining treatment (HW only) 
    # "how" , is how attorneys makes decisions, can be set to jointly-attorney-severally if there is more than 1 attorney but there isn't yet at this stage
    if lpaType == 'health-and-welfare' :
        primaryAttorneyDecisionDetails = {"canSustainLife":True,"how":None,"when":None,"howDetails":None}
    else:
        primaryAttorneyDecisionDetails = {"canSustainLife":None,"how":None,"when":"now","howDetails":None}
    putToAPI(lpaId, primaryAttorneyDecisionDetails, 'primary-attorney-decisions')

def setPrimaryAttorneyDecisionsMultipleAttorneys(lpaId, lpaType = 'health-and-welfare'):
    # canSustainLife is for life-sustaining treatment (HW only) 
    # "when"  is when LPA starts (PF only) 
    # "how" , is how attorneys makes decisions, can be set to jointly-attorney-severally if there is more than 1 attorney
    if lpaType == 'health-and-welfare' :
        primaryAttorneyDecisionDetails = {"canSustainLife":True,"how":"jointly-attorney-severally","when":None,"howDetails":None}
    else:
        primaryAttorneyDecisionDetails = {"canSustainLife":None,"how":"jointly-attorney-severally","when":"now","howDetails":None}
    putToAPI(lpaId, primaryAttorneyDecisionDetails, 'primary-attorney-decisions')

def setReplacementAttorneyDecisions(lpaId, lpaType = 'health-and-welfare'):
    # "how" , is how replacement attorneys makes decisions, can be set to jointly-attorney-severally if there is more than 1 replacement attorney
    # "when"  is when replacement attorneys step in, can be first or last
    if lpaType == 'health-and-welfare' :
        replacementAttorneyDecisionDetails = {"canSustainLife":True,"how":None,"when":None,"howDetails":None}
    else:
        replacementAttorneyDecisionDetails = {"whenDetails":None,"how":None,"when":"first","howDetails":None}
    putToAPI(lpaId, replacementAttorneyDecisionDetails, 'replacement-attorney-decisions')

def setReplacementAttorneyDecisionsMultipleAttorneys(lpaId, lpaType = 'health-and-welfare'):
    # canSustainLife is for life-sustaining treatment (HW only) 
    # "when"  is when LPA starts (PF only) 
    # "how" , is how attorneys makes decisions, can be set to jointly-attorney-severally if there is more than 1 attorney
    if lpaType == 'health-and-welfare' :
        replacementAttorneyDecisionDetails = {"canSustainLife":True,"how":"jointly-attorney-severally","when":"last","howDetails":None}
    else:
        replacementAttorneyDecisionDetails = {"whenDetails":None,"how":"jointly-attorney-severally","when":"last","howDetails":None}
    putToAPI(lpaId, replacementAttorneyDecisionDetails, 'replacement-attorney-decisions')

def addPrimaryAttorney(lpaId):
    primaryAttorneyDetails = {"name":{"title":"Mrs","first":"Amy","last":"Wheeler"},"dob":{"date":"1988-10-22T00:00:00.000000+0000"},"id":None,"address":{"address1":"Brickhill Cottage","address2":"Birch Cross","address3":"Marchington, Uttoxeter, Staffordshire","postcode":"ST14 8NX"},"email":{"address":"opglpademo+AmyWheeler@gmail.com"},"type":"human"}
    postToAPI(lpaId, primaryAttorneyDetails, 'primary-attorneys')

def addSecondPrimaryAttorney(lpaId, lpaType = 'health-and-welfare'):
    if lpaType == 'health-and-welfare' :
        primaryAttorneyDetails = {"name":{"title":"Mr","first":"David","last":"Wheeler"},"dob":{"date":"1972-03-12T00:00:00.000000+0000"},"id":None,"address":{"address1":"Brickhill Cottage","address2":"Birch Cross","address3":"Marchington, Uttoxeter, Staffordshire","postcode":"ST14 8NX"},"email":{"address":"opglpademo+DavidWheeler@gmail.com"},"type":"human"}
    else:
        primaryAttorneyDetails = {"name":"Standard Trust","number":"678437685","id":None,"address":{"address1":"1 Laburnum Place","address2":"Sketty","address3":"Swansea, Abertawe","postcode":"SA2 8HT"},"email":{"address":"opglpademo+trustcorp@gmail.com"},"type":"trust"}
    postToAPI(lpaId, primaryAttorneyDetails, 'primary-attorneys')

def deletePrimaryAttorney(lpaId, index = 1):
    token, userId = authenticate()
    primaryAttorneyPath = f'{apiRoot}/v2/user/{userId}/applications/{lpaId}/primary-attorneys/{index}'
    r = s.delete(primaryAttorneyPath, headers=token)
    print(r)

def addReplacementAttorney(lpaId):
    replacementAttorneyDetails = {"name":{"title":"Ms","first":"Isobel","last":"Ward"},"dob":{"date":"1937-02-01T00:00:00.000000+0000"},"id":None,"address":{"address1":"2 Westview","address2":"Staplehay","address3":"Trull, Taunton, Somerset","postcode":"TA3 7HF"},"email":None,"type":"human"}
    postToAPI(lpaId, replacementAttorneyDetails, 'replacement-attorneys')

def addSecondReplacementAttorney(lpaId):
    replacementAttorneyDetails = {"name":{"title":"Mr","first":"Ewan","last":"Adams"},"dob":{"date":"1972-03-12T00:00:00.000000+0000"},"id":None,"address":{"address1":"2 Westview","address2":"Staplehay","address3":"Trull, Taunton, Somerset","postcode":"TA3 7HF"},"email":None,"type":"human"}
    postToAPI(lpaId, replacementAttorneyDetails, 'replacement-attorneys')

def deleteReplacementAttorney(lpaId, index = 1):
    token, userId = authenticate()
    replacementAttorneyPath = f'{apiRoot}/v2/user/{userId}/applications/{lpaId}/replacement-attorneys/{index}'
    r = s.delete(replacementAttorneyPath, headers=token)
    print(r)

def setReplacementAttorneysConfirmed(lpaId):
    token, userId = authenticate()
    metadata = {"metadata":{"replacement-attorneys-confirmed":True}}
    patchViaAPI(lpaId, metadata)

def setCertificateProvider(lpaId):
    certProvider = {"name":{"title":"Mr","first":"Reece","last":"Richards"},"address":{"address1":"11 Brookside","address2":"Cholsey","address3":"Wallingford, Oxfordshire","postcode":"OX10 9NN"}}
    putToAPI(lpaId, certProvider, 'certificate-provider')

def addPersonToNotify(lpaId):
    personToNotify = {"id":None,"name":{"title":"Sir","first":"Anthony","last":"Webb"},"address":{"address1":"Brickhill Cottage","address2":"Birch Cross","address3":"Marchington, Uttoxeter, Staffordshire","postcode":"BS18 6PL"}}
    postToAPI(lpaId, personToNotify, 'notified-people')

def addCorrespondent(lpaId):
    correspondent = {"who":"donor","name":{"title":"Mrs","first":"Nancy","last":"Garrison"},"company":None,"address":{"address1":"Bank End Farm House","address2":"Undercliff Drive","address3":"Ventnor, Isle of Wight","postcode":"PO38 1UL"},"email":{"address":"opglpademo+NancyGarrison@gmail.com"},"phone":None,"contactByPost":False,"contactInWelsh":False,"contactDetailsEnteredManually":None}
    putToAPI(lpaId, correspondent, 'correspondent')

def addWhoAreYou(lpaId):
    whoAreYou = {"who":"donor","qualifier":None}
    putToAPI(lpaId, whoAreYou, 'who-are-you')

def setRepeatApplication(lpaId):
    metadata = {"metadata":{"replacement-attorneys-confirmed":True,"repeat-application-confirmed":True}}
    patchViaAPI(lpaId, metadata)

def setRepeatCaseNumber(lpaId):
    repeatCaseNumber = {"repeatCaseNumber":"12345678"}
    putToAPI(lpaId, repeatCaseNumber, 'repeat-case-number')

def setPayment(lpaId, amount = 82, method = None, reducedFeeReceivesBenefits = None, reducedFeeLowIncome = None, reducedFeeUniversalCredit = None):
    # default is normal fee, without cheque, used by HW test
    payment = {"method":method,"email":None,"amount":amount,"reference":None,"gatewayReference":None,"date":None,"reducedFeeReceivesBenefits":reducedFeeReceivesBenefits,"reducedFeeAwardedDamages":None,"reducedFeeLowIncome":reducedFeeLowIncome,"reducedFeeUniversalCredit":reducedFeeUniversalCredit}
    putToAPI(lpaId, payment, 'payment')

def setPaymentNormalFeeWithCheque(lpaId):
    setPayment(lpaId, method = "cheque")

def setPaymentLowIncomeClaimingReduction(lpaId):
    setPayment(lpaId, amount = 20.5, reducedFeeReceivesBenefits = False, reducedFeeLowIncome = True)

def setPaymentLowIncomeNotClaimingReduction(lpaId):
    setPayment(lpaId, amount = 41 )

def setInstruction(lpaId, instruction = "Lorem Ipsum"):
    instructionJson = {"instruction":instruction}
    putToAPI(lpaId, instructionJson, 'instruction')

def setPreference(lpaId, preference = "Neque porro quisquam"):
    preferenceJson = {"preference":preference}
    putToAPI(lpaId, preferenceJson, 'preference')

def setWhoIsRegistering(lpaId, who = "donor"):
    whoIsRegisteringJson = {"whoIsRegistering":who}
    putToAPI(lpaId, whoIsRegisteringJson, 'who-is-registering')

def getPdf1(lpaId):
    token, userId = authenticate()
    pdf1Path = f'{apiRoot}/v2/user/{userId}/applications/{lpaId}/pdfs/lp1'
    r = s.get(pdf1Path, headers=token )
    print(r)
    print(r.content)
