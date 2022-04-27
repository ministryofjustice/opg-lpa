//preprod
var rawBody = PD.inputRequest.rawBody;
var obj = JSON.parse(JSON.parse(unescape(rawBody)).Message);


var incident_key = obj[ 'Source ARN' ];
var event_type = PD.Trigger;

var normalized_event = {
  	 incident_key: incident_key,
     event_type: event_type,
     description: obj['Source ID'] + ": " + obj['Event Message'],
     details: obj
};
PD.emitGenericEvents([normalized_event]);
