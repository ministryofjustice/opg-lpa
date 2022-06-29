from os import environ as env


pg_username = env.get("OPG_LPA_POSTGRES_USERNAME", None)
pg_password = env.get("OPG_LPA_POSTGRES_PASSWORD", None)
pg_hostname = env.get("OPG_LPA_POSTGRES_HOSTNAME", None)
pg_port = env.get("OPG_LPA_POSTGRES_PORT", None)
pg_database = env.get("OPG_LPA_POSTGRES_NAME", None)

postgres_conn_str = (
    f"postgresql://{pg_username}:{pg_password}@{pg_hostname}:{pg_port}/{pg_database}"
)

CONFIG = {
    "db_conn_str": postgres_conn_str,
}
