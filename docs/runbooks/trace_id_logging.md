# Trace logging in MaLPA

This is an overview of how we log the progress of a request through the MaLPA stack.

We use the incoming `X-Amzn-Trace-Id` header as the key which ties together log entries, as explained below. This header is added to incoming requests which hit the AWS load balancers that sit in front of the *-web containers.

Using the value of this header as a key enables us to trace requests from the load balancers all the way through to back-end services and out again.

## front-ssl/admin-ssl

These containers only exist in dev. They mock the load balancers which pass traffic on to the front-web/admin-web components in live.

Each is an nginx proxy (based on docker-ssl-proxy) modified to proxy requests with additional mock `X-Amzn-Trace-Id` header to the *-web containers.

## front-web/admin-web

nginx proxies which receive traffic from front-ssl/admin-ssl respectively and forward it to the front-end application (front-app/admin-app). Incoming requests are logged in an access log.

For non-static resources, log entries look like this:

```
{"trace_id": "Root=1-1608072733.633-691-fromssl", "time_local": "15/Dec/2020:22:52:13 +0000", "timestamp_msec": "1608072733.830", "remote_addr": "192.168.48.10", "real_ip": "192.168.48.1", "real_forwarded_for": "192.168.48.1", "real_forwarded_proto": "https", "request_id": "", "remote_user": "", "request_time": 0.196, "request_uri": "/login", "status": 200, "request": "GET /login HTTP/1.0", "request_method": "GET", "http_referrer": "https://localhost:7002/home", "http_user_agent": "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.88 Safari/537.36", "bytes_sent": 9011, "http_host": "localhost", "sent_http_location": "", "server_name": "_", "server_port": "80", "upstream_addr": "192.168.48.11:9000", "upstream_response_length": "8624", "upstream_response_time": "0.196", "upstream_status": "200" }
```

This format is modelled after the JSON nginx log format used by Sirius.

For static resources, access logging uses combined log format with the addition of the Amazon trace ID at the start of the line:

```
Root=1-1608114378.099-152-fromssl - 192.168.48.14 - - [16/Dec/2020:10:26:18 +0000] "GET /assets/1608114377/v2/css/images/gov.uk_logotype_crown.png?0.19.2 HTTP/1.0" 200 780 "https://localhost:7002/assets/1608114377/v2/css/govuk-template.min.css" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.88 Safari/537.36"
```

(Note that these examples show a mocked-out Amazon trace header, as provided by the *-ssl containers.)

The `X-Amzn-Trace-Id` header is forwarded with requests proxied to front-app/admin-app but renamed as `X-Trace-Id`.
