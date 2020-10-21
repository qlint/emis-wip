--
-- PostgreSQL database dump
--

-- Dumped from database version 12.1
-- Dumped by pg_dump version 12.1

-- Started on 2020-10-21 01:21:26

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
-- TOC entry 3332 (class 0 OID 78306)
-- Dependencies: 271
-- Data for Name: communication_audience; Type: TABLE DATA; Schema: app; Owner: postgres
--

INSERT INTO app.communication_audience (audience_id, audience, module) VALUES (2, 'Class Specific', NULL);
INSERT INTO app.communication_audience (audience_id, audience, module) VALUES (3, 'All Staff', NULL);
INSERT INTO app.communication_audience (audience_id, audience, module) VALUES (4, 'All Teachers', NULL);
INSERT INTO app.communication_audience (audience_id, audience, module) VALUES (5, 'Parent', NULL);
INSERT INTO app.communication_audience (audience_id, audience, module) VALUES (6, 'Transport Route', NULL);
INSERT INTO app.communication_audience (audience_id, audience, module) VALUES (7, 'Student Activity', NULL);
INSERT INTO app.communication_audience (audience_id, audience, module) VALUES (1, 'All Parents', NULL);
INSERT INTO app.communication_audience (audience_id, audience, module) VALUES (8, 'House', NULL);
INSERT INTO app.communication_audience (audience_id, audience, module) VALUES (9, 'Committee', NULL);
INSERT INTO app.communication_audience (audience_id, audience, module) VALUES (10, 'Staff Category', NULL);
INSERT INTO app.communication_audience (audience_id, audience, module) VALUES (11, 'Staff Department', NULL);
INSERT INTO app.communication_audience (audience_id, audience, module) VALUES (12, 'Student Type', NULL);
INSERT INTO app.communication_audience (audience_id, audience, module) VALUES (13, 'Employee', NULL);
INSERT INTO app.communication_audience (audience_id, audience, module) VALUES (14, 'All Students In Neighborhood(s)', 'Transport');
INSERT INTO app.communication_audience (audience_id, audience, module) VALUES (15, 'All Students In A Trip', 'Transport');
INSERT INTO app.communication_audience (audience_id, audience, module) VALUES (16, 'All Students In Neighborhood(s) In Trip', 'Transport');
INSERT INTO app.communication_audience (audience_id, audience, module) VALUES (17, 'Class Students In Neighborhood(s)', 'Transport');
INSERT INTO app.communication_audience (audience_id, audience, module) VALUES (18, 'Class Students In A Trip', 'Transport');
INSERT INTO app.communication_audience (audience_id, audience, module) VALUES (19, 'Class Students In Neighborhood(s) In Trip', 'Transport');


--
-- TOC entry 3340 (class 0 OID 0)
-- Dependencies: 272
-- Name: communication_audience_audience_id_seq; Type: SEQUENCE SET; Schema: app; Owner: postgres
--

SELECT pg_catalog.setval('app.communication_audience_audience_id_seq', 29, true);


-- Completed on 2020-10-21 01:21:26

--
-- PostgreSQL database dump complete
--

