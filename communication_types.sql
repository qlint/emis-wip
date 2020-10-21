--
-- PostgreSQL database dump
--

-- Dumped from database version 12.1
-- Dumped by pg_dump version 12.1

-- Started on 2020-10-21 01:24:41

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
-- TOC entry 3332 (class 0 OID 78344)
-- Dependencies: 279
-- Data for Name: communication_types; Type: TABLE DATA; Schema: app; Owner: postgres
--

INSERT INTO app.communication_types (com_type_id, com_type) VALUES (1, 'General');
INSERT INTO app.communication_types (com_type_id, com_type) VALUES (2, 'Event');
INSERT INTO app.communication_types (com_type_id, com_type) VALUES (3, 'Important Notice');
INSERT INTO app.communication_types (com_type_id, com_type) VALUES (4, 'Reminder');
INSERT INTO app.communication_types (com_type_id, com_type) VALUES (5, 'Student Feedback');
INSERT INTO app.communication_types (com_type_id, com_type) VALUES (6, 'Gallery');


--
-- TOC entry 3340 (class 0 OID 0)
-- Dependencies: 280
-- Name: communication_types_com_type_id_seq; Type: SEQUENCE SET; Schema: app; Owner: postgres
--

SELECT pg_catalog.setval('app.communication_types_com_type_id_seq', 6, true);


-- Completed on 2020-10-21 01:24:41

--
-- PostgreSQL database dump complete
--

