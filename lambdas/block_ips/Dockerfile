FROM python:3.14.7-alpine3.23@sha256:218761489de417a6eb0808e264cbdd7043ec6659fe5a61898815e9848536541d

COPY --from=ghcr.io/astral-sh/uv@sha256:9f130421e930bfbdc563c00740466603f8723158e90297d07957c2294640262b /uv /uvx /bin/

WORKDIR /
COPY app /app
COPY tests /tests
COPY pyproject.toml uv.lock ./
RUN uv export --locked -o requirements.txt \
    && python -m pip install --no-cache-dir -r /requirements.txt

ENTRYPOINT ["python", "-m", "unittest", "discover", "-s", "/tests", "-p", "*_tests.py"]
