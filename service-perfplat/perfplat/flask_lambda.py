# -*- coding: utf-8 -*-
# Copyright 2016 Matt Martz
# All Rights Reserved.
#
#    Licensed under the Apache License, Version 2.0 (the "License"); you may
#    not use this file except in compliance with the License. You may obtain
#    a copy of the License at
#
#         http://www.apache.org/licenses/LICENSE-2.0
#
#    Unless required by applicable law or agreed to in writing, software
#    distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
#    WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
#    License for the specific language governing permissions and limitations
#    under the License.

import sys

try:
    from urllib import urlencode
except ImportError:
    from urllib.parse import urlencode

from flask import Flask

try:
    from cStringIO import StringIO
except ImportError:
    try:
        from StringIO import StringIO
    except ImportError:
        from io import StringIO

from werkzeug.wrappers import BaseRequest


__version__ = '0.0.4'


def make_environ(event):
    environ = {}
    print('resource:', event['resource'], 'path:', event['path'])
    # key might be there but set to None
    headers = event.get('headers', {}) or {}
    for hdr_name, hdr_value in headers.items():
        hdr_name = hdr_name.replace('-', '_').upper()
        if hdr_name in ['CONTENT_TYPE', 'CONTENT_LENGTH']:
            environ[hdr_name] = hdr_value
            continue

        http_hdr_name = 'HTTP_{}'.format(hdr_name)
        environ[http_hdr_name] = hdr_value

    qs = event['queryStringParameters']

    environ['REQUEST_METHOD'] = event['httpMethod']
    environ['PATH_INFO'] = event['path']
    environ['QUERY_STRING'] = urlencode(qs) if qs else ''
    environ['REMOTE_ADDR'] = event['requestContext']['identity']['sourceIp']
    environ['HOST'] = '{}:{}'.format(
        environ.get('HTTP_HOST', ''),
        environ.get('HTTP_X_FORWARDED_PORT', ''),
    )
    environ['SCRIPT_NAME'] = ''
    environ['SERVER_NAME'] = 'SERVER_NAME'

    environ['SERVER_PORT'] = environ['HTTP_X_FORWARDED_PORT']
    environ['SERVER_PROTOCOL'] = 'HTTP/1.1'

    environ['CONTENT_LENGTH'] = str(
        len(event['body']) if event['body'] else ''
    )

    environ['wsgi.url_scheme'] = environ['HTTP_X_FORWARDED_PROTO']
    environ['wsgi.input'] = StringIO(event['body'] or '')
    environ['wsgi.version'] = (1, 0)
    environ['wsgi.errors'] = sys.stderr
    environ['wsgi.multithread'] = False
    environ['wsgi.run_once'] = True
    environ['wsgi.multiprocess'] = False

    BaseRequest(environ)

    return environ


class LambdaResponse(object):

    def __init__(self):
        self.status = None
        self.response_headers = None

    def start_response(self, status, response_headers, exc_info=None):
        self.status = int(status[:3])
        self.response_headers = dict(response_headers)


class FlaskLambda(Flask):

    def __call__(self, event, context):
        try:
            if 'httpMethod' not in event:
                print('call as flask app')
                # In this "context" `event` is `environ` and
                # `context` is `start_response`, meaning the request didn't
                # occur via API Gateway and Lambda
                return super(FlaskLambda, self).__call__(event, context)

            print('call as aws lambda')
            response = LambdaResponse()

            body = next(self.wsgi_app(
                make_environ(event),
                response.start_response
            ))

            return {
                'statusCode': response.status,
                'headers': response.response_headers,
                'body': body.decode('utf-8')
            }

        except Exception as e:
            print('unexpected error', e)
            return {
                'statusCode': 500,
                'headers': {},
                'body': 'internal server error'
            }

