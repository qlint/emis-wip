--
-- PostgreSQL database dump
--

-- Dumped from database version 12.1
-- Dumped by pg_dump version 12.1

-- Started on 2020-10-21 01:14:14

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
-- TOC entry 3332 (class 0 OID 78212)
-- Dependencies: 249
-- Data for Name: blog_post_statuses; Type: TABLE DATA; Schema: app; Owner: postgres
--

INSERT INTO app.blog_post_statuses (post_status_id, post_status) VALUES (2, 'Draft');
INSERT INTO app.blog_post_statuses (post_status_id, post_status) VALUES (1, 'Published');


--
-- TOC entry 3340 (class 0 OID 0)
-- Dependencies: 250
-- Name: blog_post_statuses_post_status_id_seq; Type: SEQUENCE SET; Schema: app; Owner: postgres
--

SELECT pg_catalog.setval('app.blog_post_statuses_post_status_id_seq', 2, true);


-- Completed on 2020-10-21 01:14:14

--
-- PostgreSQL database dump complete
--

