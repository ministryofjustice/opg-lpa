SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET client_min_messages = warning;
SET row_security = off;

INSERT INTO public.feedback (received, message) VALUES ('2023-05-12 12:43:56+00', '{"agent": "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.93 Safari/537.36", "email": "", "phone": "", "rating": "neither-satisfied-or-dissatisfied", "details": "test-no-email-no-number", "fromPage": "/home"}');
INSERT INTO public.feedback (received, message) VALUES ('2023-05-12 12:44:14+00', '{"agent": "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.93 Safari/537.36", "email": "test@test.com", "phone": "", "rating": "neither-satisfied-or-dissatisfied", "details": "test-no-num", "fromPage": "/feedback-thanks"}');
INSERT INTO public.feedback (received, message) VALUES ('2023-05-12 12:44:36+00', '{"agent": "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.93 Safari/537.36", "email": "test@test.com", "phone": "01234567891", "rating": "dissatisfied", "details": "test", "fromPage": "/feedback-thanks"}');
INSERT INTO public.feedback (received, message) VALUES (
    '2023-11-28 17:23:23+00',
    '{"agent": "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.93 Safari/537.36",
      "email": "longwindeduser@test.com",
      "phone": "11234567899",
      "rating": "satisfied",
      "details": "IhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattimeIhadagreattime",
      "fromPage": "/about-you"}'
);
INSERT INTO public.feedback (received, message) VALUES ('2023-12-02 14:22:12+00', '{"agent": "<script>alert(\"hello agent\");</script>Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.93 Safari/537.36", "email": "<script>alert(\"hello email\");</script>test@test.com", "phone": "<script>alert(\"hello phone\");</script>01234567891", "rating": "<script>alert(\"hello rating\");</script>dissatisfied", "details": "<script>alert(\"hello details\");</script>\"great service\" test", "fromPage": "<script>alert(\"hello fromPage\");</script>/feedback-thanks"}');
