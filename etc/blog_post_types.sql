--
-- PostgreSQL database dump
--

-- Dumped from database version 9.1.8
-- Dumped by pg_dump version 9.1.8
-- Started on 2016-05-30 13:38:49

SET statement_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;

SET search_path = app, pg_catalog;

--
-- TOC entry 2119 (class 0 OID 91254)
-- Dependencies: 254 2120
-- Data for Name: blog_post_types; Type: TABLE DATA; Schema: app; Owner: postgres
--

INSERT INTO blog_post_types VALUES (2, 'Event');
INSERT INTO blog_post_types VALUES (3, 'Reminder');
INSERT INTO blog_post_types VALUES (4, 'Important');
INSERT INTO blog_post_types VALUES (1, 'General');


--
-- TOC entry 2124 (class 0 OID 0)
-- Dependencies: 253
-- Name: blog_post_types_post_type_id_seq; Type: SEQUENCE SET; Schema: app; Owner: postgres
--

SELECT pg_catalog.setval('blog_post_types_post_type_id_seq', 4, true);


-- Completed on 2016-05-30 13:38:50

--
-- PostgreSQL database dump complete
--

