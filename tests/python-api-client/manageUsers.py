import argparse
import json

from lpaapi import createAndActivateUser, deleteUser, searchUser


parser = argparse.ArgumentParser(description="Manage users")
parser.add_argument("action", nargs=1, choices=["getOrCreate", "deleteIfExists"])
parser.add_argument("username", nargs=1)
parser.add_argument("password", nargs=1)
args = parser.parse_args()

action = args.action[0]
username = args.username[0]
password = args.password[0]

existing_user = searchUser(username, password)

if action == "getOrCreate":
    if existing_user is None:
        created = createAndActivateUser(username, password)
        if created["success"]:
            result = {
                "success": True,
                "result": "user created",
                "details": {"user_id": created["user_id"], "username": username},
            }
        else:
            result = {
                "success": False,
                "result": "user could not be created",
                "details": None,
            }
    else:
        result = {
            "success": True,
            "result": "user already exists",
            "details": {"user_id": existing_user["userId"], "username": username},
        }
elif action == "deleteIfExists":
    if existing_user is not None:
        deleted = deleteUser(existing_user["userId"], username, password)
        if deleted is None:
            result = {
                "success": False,
                "result": "unable to delete user",
                "details": None,
            }
        else:
            result = {"success": True, "result": "user deleted", "details": deleted}
    else:
        result = {"success": True, "result": "no user to delete", "details": None}

print(json.dumps(result))
