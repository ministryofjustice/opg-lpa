import os


class Config(object):
    SECRET_KEY = os.environ.get("SECRET_KEY") or "5L18nw&y6a^1HfYPzedcpSwq*82jYH9R"
    SESSION_COOKIE_HTTPONLY = True
    SESSION_COOKIE_SECURE = True
