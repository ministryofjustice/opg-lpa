FROM python:3.14.9-alpine3.23@sha256:a3d4eff2e4a3de12d9709b3e80cee788109d7292955972a83f5fff81b06c2e95

COPY --from=ghcr.io/astral-sh/uv@sha256:9f130421e930bfbdc563c00740466603f8723158e90297d07957c2294640262b /uv /uvx /bin/

WORKDIR /
COPY app /app
COPY tests /tests
COPY pyproject.toml uv.lock ./
RUN uv export --locked -o requirements.txt \
    && python -m pip install --no-cache-dir -r /requirements.txt

ENTRYPOINT ["python", "-m", "unittest", "discover", "-s", "/tests", "-p", "*_tests.py"]
