FROM stoplight/prism:4

COPY run.sh /app/

# get the Sirius API gateway swagger file, make run script executable
RUN wget https://raw.githubusercontent.com/ministryofjustice/opg-sirius-api-gateway/master/docs/swagger.v1.yaml && \
    mv swagger.v1.yaml /app/swagger.yaml && \
    chmod +x /app/run.sh

# starts the mock server on port 5000
ENTRYPOINT ["/app/run.sh"]
