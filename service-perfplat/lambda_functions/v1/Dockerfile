FROM python:3.8-slim-buster
WORKDIR /var/www/lambda_functions/v1

# Install Python Dependencies

COPY requirements requirements
RUN cat ./requirements/*.txt | uniq | grep -v "#" > ./requirements/local-requirements.txt
RUN pip install -r ./requirements/local-requirements.txt

EXPOSE 4343

ENTRYPOINT ["python", "/var/www/lambda_functions/v1/functions/lpa_codes/app/lpa_make_datapatform_mock.py"]
