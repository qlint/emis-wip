--
-- PostgreSQL database dump
--

-- Dumped from database version 12.1
-- Dumped by pg_dump version 12.1

-- Started on 2020-10-21 01:30:08

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- TOC entry 3333 (class 0 OID 78492)
-- Dependencies: 311
-- Data for Name: installment_options; Type: TABLE DATA; Schema: app; Owner: postgres
--

INSERT INTO app.installment_options (installment_id, payment_plan_name, active, num_payments, payment_interval, payment_interval2) VALUES (1, 'Per Term', true, 3, 4, 'month');
INSERT INTO app.installment_options (installment_id, payment_plan_name, active, num_payments, payment_interval, payment_interval2) VALUES (2, 'Per Month', true, 12, 1, 'month');


--
-- TOC entry 3342 (class 0 OID 0)
-- Dependencies: 312
-- Name: installment_options_installment_id_seq; Type: SEQUENCE SET; Schema: app; Owner: postgres
--

SELECT pg_catalog.setval('app.installment_options_installment_id_seq', 2, true);


-- Completed on 2020-10-21 01:30:09

--
-- PostgreSQL database dump complete
--

