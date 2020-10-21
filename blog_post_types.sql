--
-- PostgreSQL database dump
--

-- Dumped from database version 12.1
-- Dumped by pg_dump version 12.1

-- Started on 2020-10-21 01:17:31

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
-- TOC entry 3332 (class 0 OID 78220)
-- Dependencies: 251
-- Data for Name: blog_post_types; Type: TABLE DATA; Schema: app; Owner: postgres
--

INSERT INTO app.blog_post_types (post_type_id, post_type) VALUES (1, 'General');
INSERT INTO app.blog_post_types (post_type_id, post_type) VALUES (2, 'Homework');


--
-- TOC entry 3340 (class 0 OID 0)
-- Dependencies: 252
-- Name: blog_post_types_post_type_id_seq; Type: SEQUENCE SET; Schema: app; Owner: postgres
--

SELECT pg_catalog.setval('app.blog_post_types_post_type_id_seq', 2, true);


-- Completed on 2020-10-21 01:17:31

--
-- PostgreSQL database dump complete
--

