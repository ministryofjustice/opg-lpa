from datetime import datetime, timedelta


class User:

    def __init__(self, user_id, username, dob=None):
        """
        :param: datetime; date of birth
        """
        self.user_id = user_id
        self.username = username

        if dob is None:
            dob = datetime.now() - timedelta(days=365*30)
        self.dob = dob

    def build_details(self):
        """
        Build user details dict suitable to populate user details record in db.
        """
        now_as_str = datetime.now().isoformat(timespec='microseconds')

        return {
            "id": self.user_id,
            "createdAt": now_as_str,
            "updatedAt": now_as_str,
            "name": {
                "title": "Dr",
                "first": "Philbertaaa",
                "last": "Knowledgewarrior"
            },
            "address": {
                "address1": "9212 URPLOT MAGEE AVENUE",
                "address2": "BRUMMAGEN",
                "address3": "",
                "postcode": "B28 5ZZ"
            },
            "dob": {
                "date": self.dob.isoformat(timespec='microseconds')
            },
            "email": {
                "address": self.username
            }
        }
