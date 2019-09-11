--
-- PostgreSQL database dump
--

-- Dumped from database version 9.1.24
-- Dumped by pg_dump version 9.1.24
-- Started on 2019-09-11 08:56:42

SET statement_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;

--
-- TOC entry 2018 (class 1262 OID 43306)
-- Name: eduweb_mis; Type: DATABASE; Schema: -; Owner: postgres
--

CREATE DATABASE eduweb_mis WITH TEMPLATE = template0 ENCODING = 'UTF8' LC_COLLATE = 'English_United States.1252' LC_CTYPE = 'English_United States.1252';


ALTER DATABASE eduweb_mis OWNER TO postgres;

\connect eduweb_mis

SET statement_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;

--
-- TOC entry 1 (class 3079 OID 11639)
-- Name: plpgsql; Type: EXTENSION; Schema: -; Owner: 
--

CREATE EXTENSION IF NOT EXISTS plpgsql WITH SCHEMA pg_catalog;


--
-- TOC entry 2021 (class 0 OID 0)
-- Dependencies: 1
-- Name: EXTENSION plpgsql; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION plpgsql IS 'PL/pgSQL procedural language';


--
-- TOC entry 2 (class 3079 OID 43307)
-- Dependencies: 8
-- Name: dblink; Type: EXTENSION; Schema: -; Owner: 
--

CREATE EXTENSION IF NOT EXISTS dblink WITH SCHEMA public;


--
-- TOC entry 2022 (class 0 OID 0)
-- Dependencies: 2
-- Name: EXTENSION dblink; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION dblink IS 'connect to other PostgreSQL databases from within a database';


SET search_path = public, pg_catalog;

SET default_tablespace = '';

SET default_with_oids = false;

--
-- TOC entry 164 (class 1259 OID 43351)
-- Dependencies: 1854 8
-- Name: clients; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE clients (
    client_id integer NOT NULL,
    username character varying NOT NULL,
    password character varying NOT NULL,
    subdomain character varying NOT NULL,
    dbusername character varying NOT NULL,
    dbpassword character varying NOT NULL,
    active boolean DEFAULT true NOT NULL,
    first_name character varying NOT NULL,
    middle_name character varying,
    last_name character varying NOT NULL,
    email character varying NOT NULL,
    user_type character varying NOT NULL
);


ALTER TABLE public.clients OWNER TO postgres;

--
-- TOC entry 165 (class 1259 OID 43358)
-- Dependencies: 8 164
-- Name: clients_client_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE clients_client_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.clients_client_id_seq OWNER TO postgres;

--
-- TOC entry 2023 (class 0 OID 0)
-- Dependencies: 165
-- Name: clients_client_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE clients_client_id_seq OWNED BY clients.client_id;


--
-- TOC entry 177 (class 1259 OID 102865)
-- Dependencies: 1870 1871 8
-- Name: college_students; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE college_students (
    college_student_id integer NOT NULL,
    first_name character varying NOT NULL,
    middle_name character varying,
    last_name character varying NOT NULL,
    email character varying NOT NULL,
    student_id_number character varying NOT NULL,
    student_username character varying NOT NULL,
    password character varying NOT NULL,
    active boolean DEFAULT true NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    modified_date timestamp without time zone,
    device_user_id character varying,
    last_active timestamp with time zone
);


ALTER TABLE public.college_students OWNER TO postgres;

--
-- TOC entry 176 (class 1259 OID 102863)
-- Dependencies: 8 177
-- Name: college_students_college_student_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE college_students_college_student_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.college_students_college_student_id_seq OWNER TO postgres;

--
-- TOC entry 2024 (class 0 OID 0)
-- Dependencies: 176
-- Name: college_students_college_student_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE college_students_college_student_id_seq OWNED BY college_students.college_student_id;


--
-- TOC entry 179 (class 1259 OID 102882)
-- Dependencies: 1873 8
-- Name: college_students_school; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE college_students_school (
    css_id integer NOT NULL,
    college_student_id integer NOT NULL,
    student_id integer NOT NULL,
    subdomain character varying NOT NULL,
    dbusername character varying NOT NULL,
    dbpassword character varying NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    modified_date timestamp without time zone,
    modified_by integer
);


ALTER TABLE public.college_students_school OWNER TO postgres;

--
-- TOC entry 178 (class 1259 OID 102880)
-- Dependencies: 8 179
-- Name: college_students_school_css_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE college_students_school_css_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.college_students_school_css_id_seq OWNER TO postgres;

--
-- TOC entry 2025 (class 0 OID 0)
-- Dependencies: 178
-- Name: college_students_school_css_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE college_students_school_css_id_seq OWNED BY college_students_school.css_id;


--
-- TOC entry 166 (class 1259 OID 43360)
-- Dependencies: 1856 1857 8
-- Name: notifications; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE notifications (
    notification_id integer NOT NULL,
    subdomain character varying NOT NULL,
    device_user_ids character varying[],
    message character varying NOT NULL,
    sent boolean DEFAULT false NOT NULL,
    result boolean DEFAULT false NOT NULL,
    response character varying
);


ALTER TABLE public.notifications OWNER TO postgres;

--
-- TOC entry 2026 (class 0 OID 0)
-- Dependencies: 166
-- Name: COLUMN notifications.device_user_ids; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN notifications.device_user_ids IS 'up to 2000 device ids';


--
-- TOC entry 167 (class 1259 OID 43368)
-- Dependencies: 166 8
-- Name: notifications_notification_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE notifications_notification_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.notifications_notification_id_seq OWNER TO postgres;

--
-- TOC entry 2027 (class 0 OID 0)
-- Dependencies: 167
-- Name: notifications_notification_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE notifications_notification_id_seq OWNED BY notifications.notification_id;


--
-- TOC entry 168 (class 1259 OID 43370)
-- Dependencies: 1859 8
-- Name: parent_students; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE parent_students (
    parent_student_id integer NOT NULL,
    parent_id integer NOT NULL,
    guardian_id integer NOT NULL,
    student_id integer NOT NULL,
    subdomain character varying NOT NULL,
    dbusername character varying NOT NULL,
    dbpassword character varying NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    modified_date timestamp without time zone,
    modified_by integer
);


ALTER TABLE public.parent_students OWNER TO postgres;

--
-- TOC entry 169 (class 1259 OID 43377)
-- Dependencies: 168 8
-- Name: parent_students_parent_student_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE parent_students_parent_student_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.parent_students_parent_student_id_seq OWNER TO postgres;

--
-- TOC entry 2028 (class 0 OID 0)
-- Dependencies: 169
-- Name: parent_students_parent_student_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE parent_students_parent_student_id_seq OWNED BY parent_students.parent_student_id;


--
-- TOC entry 170 (class 1259 OID 43379)
-- Dependencies: 1861 1862 8
-- Name: parents; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE parents (
    parent_id integer NOT NULL,
    first_name character varying NOT NULL,
    middle_name character varying,
    last_name character varying NOT NULL,
    email character varying NOT NULL,
    id_number character varying NOT NULL,
    username character varying NOT NULL,
    password character varying NOT NULL,
    active boolean DEFAULT true NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    modified_date timestamp without time zone,
    device_user_id character varying,
    last_active timestamp with time zone
);


ALTER TABLE public.parents OWNER TO postgres;

--
-- TOC entry 171 (class 1259 OID 43387)
-- Dependencies: 8 170
-- Name: parents_parent_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE parents_parent_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.parents_parent_id_seq OWNER TO postgres;

--
-- TOC entry 2029 (class 0 OID 0)
-- Dependencies: 171
-- Name: parents_parent_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE parents_parent_id_seq OWNED BY parents.parent_id;


--
-- TOC entry 182 (class 1259 OID 140543)
-- Dependencies: 1876 8
-- Name: registration_codes; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE registration_codes (
    telephone character varying NOT NULL,
    code character varying,
    status boolean DEFAULT false NOT NULL,
    guardian_id integer,
    first_name character varying,
    middle_name character varying,
    last_name character varying,
    email character varying,
    id_number character varying,
    username character varying,
    student_ids character varying,
    subdomain character varying
);


ALTER TABLE public.registration_codes OWNER TO postgres;

--
-- TOC entry 172 (class 1259 OID 43389)
-- Dependencies: 1864 1865 8
-- Name: staff; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE staff (
    staff_id integer NOT NULL,
    first_name character varying NOT NULL,
    middle_name character varying,
    last_name character varying NOT NULL,
    telephone character varying,
    email character varying,
    emp_id integer,
    user_id integer NOT NULL,
    user_type character varying,
    subdomain character varying NOT NULL,
    usernm character varying NOT NULL,
    password character varying NOT NULL,
    active boolean DEFAULT true NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    modified_date timestamp without time zone,
    device_user_id character varying,
    last_active timestamp with time zone,
    id_number character varying
);


ALTER TABLE public.staff OWNER TO postgres;

--
-- TOC entry 173 (class 1259 OID 43397)
-- Dependencies: 8 172
-- Name: staff_staff_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE staff_staff_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.staff_staff_id_seq OWNER TO postgres;

--
-- TOC entry 2030 (class 0 OID 0)
-- Dependencies: 173
-- Name: staff_staff_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE staff_staff_id_seq OWNED BY staff.staff_id;


--
-- TOC entry 175 (class 1259 OID 101124)
-- Dependencies: 1868 8
-- Name: traffic; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE traffic (
    traffic_id integer NOT NULL,
    school character varying NOT NULL,
    module character varying,
    creation_date timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.traffic OWNER TO postgres;

--
-- TOC entry 181 (class 1259 OID 103179)
-- Dependencies: 1875 8
-- Name: traffic_summary; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE traffic_summary (
    traffic_summary_id integer NOT NULL,
    week_number integer NOT NULL,
    module_traffic integer NOT NULL,
    module character varying,
    school character varying,
    creation_date timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.traffic_summary OWNER TO postgres;

--
-- TOC entry 180 (class 1259 OID 103177)
-- Dependencies: 8 181
-- Name: traffic_summary_traffic_summary_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE traffic_summary_traffic_summary_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.traffic_summary_traffic_summary_id_seq OWNER TO postgres;

--
-- TOC entry 2031 (class 0 OID 0)
-- Dependencies: 180
-- Name: traffic_summary_traffic_summary_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE traffic_summary_traffic_summary_id_seq OWNED BY traffic_summary.traffic_summary_id;


--
-- TOC entry 174 (class 1259 OID 101122)
-- Dependencies: 8 175
-- Name: traffic_traffic_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE traffic_traffic_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.traffic_traffic_id_seq OWNER TO postgres;

--
-- TOC entry 2032 (class 0 OID 0)
-- Dependencies: 174
-- Name: traffic_traffic_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE traffic_traffic_id_seq OWNED BY traffic.traffic_id;


--
-- TOC entry 1855 (class 2604 OID 43399)
-- Dependencies: 165 164
-- Name: client_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY clients ALTER COLUMN client_id SET DEFAULT nextval('clients_client_id_seq'::regclass);


--
-- TOC entry 1869 (class 2604 OID 102868)
-- Dependencies: 176 177 177
-- Name: college_student_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY college_students ALTER COLUMN college_student_id SET DEFAULT nextval('college_students_college_student_id_seq'::regclass);


--
-- TOC entry 1872 (class 2604 OID 102885)
-- Dependencies: 179 178 179
-- Name: css_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY college_students_school ALTER COLUMN css_id SET DEFAULT nextval('college_students_school_css_id_seq'::regclass);


--
-- TOC entry 1858 (class 2604 OID 43400)
-- Dependencies: 167 166
-- Name: notification_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY notifications ALTER COLUMN notification_id SET DEFAULT nextval('notifications_notification_id_seq'::regclass);


--
-- TOC entry 1860 (class 2604 OID 43401)
-- Dependencies: 169 168
-- Name: parent_student_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY parent_students ALTER COLUMN parent_student_id SET DEFAULT nextval('parent_students_parent_student_id_seq'::regclass);


--
-- TOC entry 1863 (class 2604 OID 43402)
-- Dependencies: 171 170
-- Name: parent_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY parents ALTER COLUMN parent_id SET DEFAULT nextval('parents_parent_id_seq'::regclass);


--
-- TOC entry 1866 (class 2604 OID 43403)
-- Dependencies: 173 172
-- Name: staff_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY staff ALTER COLUMN staff_id SET DEFAULT nextval('staff_staff_id_seq'::regclass);


--
-- TOC entry 1867 (class 2604 OID 101127)
-- Dependencies: 175 174 175
-- Name: traffic_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY traffic ALTER COLUMN traffic_id SET DEFAULT nextval('traffic_traffic_id_seq'::regclass);


--
-- TOC entry 1874 (class 2604 OID 103182)
-- Dependencies: 180 181 181
-- Name: traffic_summary_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY traffic_summary ALTER COLUMN traffic_summary_id SET DEFAULT nextval('traffic_summary_traffic_summary_id_seq'::regclass);


--
-- TOC entry 1878 (class 2606 OID 43512)
-- Dependencies: 164 164 2015
-- Name: PK_clients_client_id; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY clients
    ADD CONSTRAINT "PK_clients_client_id" PRIMARY KEY (client_id);


--
-- TOC entry 1900 (class 2606 OID 102875)
-- Dependencies: 177 177 2015
-- Name: PK_college_student_id; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY college_students
    ADD CONSTRAINT "PK_college_student_id" PRIMARY KEY (college_student_id);


--
-- TOC entry 1880 (class 2606 OID 43514)
-- Dependencies: 166 166 2015
-- Name: PK_notification_id; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY notifications
    ADD CONSTRAINT "PK_notification_id" PRIMARY KEY (notification_id);


--
-- TOC entry 1884 (class 2606 OID 43516)
-- Dependencies: 170 170 2015
-- Name: PK_parents_parent_id; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY parents
    ADD CONSTRAINT "PK_parents_parent_id" PRIMARY KEY (parent_id);


--
-- TOC entry 1890 (class 2606 OID 43518)
-- Dependencies: 172 172 2015
-- Name: PK_staff_staff_id; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY staff
    ADD CONSTRAINT "PK_staff_staff_id" PRIMARY KEY (staff_id);


--
-- TOC entry 1910 (class 2606 OID 140550)
-- Dependencies: 182 182 2015
-- Name: PK_telephone; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY registration_codes
    ADD CONSTRAINT "PK_telephone" PRIMARY KEY (telephone);


--
-- TOC entry 1908 (class 2606 OID 103188)
-- Dependencies: 181 181 2015
-- Name: PK_traffic_summary_id; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY traffic_summary
    ADD CONSTRAINT "PK_traffic_summary_id" PRIMARY KEY (traffic_summary_id);


--
-- TOC entry 1886 (class 2606 OID 43520)
-- Dependencies: 170 170 2015
-- Name: U_id_number; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY parents
    ADD CONSTRAINT "U_id_number" UNIQUE (id_number);


--
-- TOC entry 1902 (class 2606 OID 102877)
-- Dependencies: 177 177 2015
-- Name: U_student_id_number; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY college_students
    ADD CONSTRAINT "U_student_id_number" UNIQUE (student_id_number);


--
-- TOC entry 1904 (class 2606 OID 102879)
-- Dependencies: 177 177 2015
-- Name: U_student_username; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY college_students
    ADD CONSTRAINT "U_student_username" UNIQUE (student_username);


--
-- TOC entry 1892 (class 2606 OID 43522)
-- Dependencies: 172 172 2015
-- Name: U_telephone; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY staff
    ADD CONSTRAINT "U_telephone" UNIQUE (telephone);


--
-- TOC entry 1888 (class 2606 OID 43524)
-- Dependencies: 170 170 2015
-- Name: U_username; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY parents
    ADD CONSTRAINT "U_username" UNIQUE (username);


--
-- TOC entry 1894 (class 2606 OID 43526)
-- Dependencies: 172 172 2015
-- Name: U_usernm; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY staff
    ADD CONSTRAINT "U_usernm" UNIQUE (usernm);


--
-- TOC entry 1906 (class 2606 OID 102891)
-- Dependencies: 179 179 2015
-- Name: college_students_css_id; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY college_students_school
    ADD CONSTRAINT college_students_css_id PRIMARY KEY (css_id);


--
-- TOC entry 1896 (class 2606 OID 96942)
-- Dependencies: 172 172 2015
-- Name: id_number; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY staff
    ADD CONSTRAINT id_number UNIQUE (id_number);


--
-- TOC entry 1882 (class 2606 OID 43528)
-- Dependencies: 168 168 2015
-- Name: parent_students_parent_student_id; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY parent_students
    ADD CONSTRAINT parent_students_parent_student_id PRIMARY KEY (parent_student_id);


--
-- TOC entry 1898 (class 2606 OID 102352)
-- Dependencies: 175 175 2015
-- Name: traffic_id; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY traffic
    ADD CONSTRAINT traffic_id PRIMARY KEY (traffic_id);


--
-- TOC entry 1912 (class 2606 OID 102892)
-- Dependencies: 179 177 1899 2015
-- Name: FK_college_students_student; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY college_students_school
    ADD CONSTRAINT "FK_college_students_student" FOREIGN KEY (college_student_id) REFERENCES college_students(college_student_id);


--
-- TOC entry 1911 (class 2606 OID 43529)
-- Dependencies: 170 1883 168 2015
-- Name: FK_parent_students_parent; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY parent_students
    ADD CONSTRAINT "FK_parent_students_parent" FOREIGN KEY (parent_id) REFERENCES parents(parent_id);


--
-- TOC entry 2020 (class 0 OID 0)
-- Dependencies: 8
-- Name: public; Type: ACL; Schema: -; Owner: postgres
--

REVOKE ALL ON SCHEMA public FROM PUBLIC;
REVOKE ALL ON SCHEMA public FROM postgres;
GRANT ALL ON SCHEMA public TO postgres;
GRANT ALL ON SCHEMA public TO PUBLIC;


-- Completed on 2019-09-11 08:56:42

--
-- PostgreSQL database dump complete
--

