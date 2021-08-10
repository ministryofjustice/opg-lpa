import os
import socket
import threading
import time

import pytest
from flask import Flask

from perfplat import create_app


def get_open_port():
    """ Find free port on a local system """
    s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    s.bind(("", 0))
    port = s.getsockname()[1]
    s.close()
    return port


def wait_until(predicate, timeout=5, interval=0.05, *args, **kwargs):
    mustend = time.time() + timeout
    while time.time() < mustend:
        if predicate(*args, **kwargs):
            return True
        time.sleep(interval)
    return False


@pytest.fixture
def server():

    version = os.environ.get("API_VERSION")
    print(f"version: {version}")

    http_server = create_app(Flask)
    routes = [str(p) for p in http_server.url_map.iter_rules()]
    print(f"routes: {routes}")

    port = get_open_port()
    http_server.url = "http://localhost:{}/{}".format(port, version)
    print(f"http_server.url: {http_server.url}")

    def start():
        print("start server")
        http_server.run(port=port)

    p = threading.Thread(target=start)
    p.daemon = True
    p.start()

    def check():
        s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        try:
            s.connect(("localhost", port))
            return True
        except Exception:
            return False
        finally:
            s.close()

    rc = wait_until(check)
    assert rc, "failed to start service"

    yield http_server

    p.join(timeout=0.5)
