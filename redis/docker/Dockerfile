FROM redis:7.2.17-alpine3.22@sha256:2f66de68111e64c3927859f158f8e666066dae2b97d9f82770a6f37a5aa1c335

# exec so SIGKILL is respected and redis can shutdown gracefully
CMD [ "sh", "-c", "exec redis-server --requirepass \"$OPG_LPA_COMMON_REDIS_AUTH_TOKEN\"" ]
