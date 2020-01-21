--
-- PostgreSQL database dump
--

-- Dumped from database version 12.1
-- Dumped by pg_dump version 12.1

-- Started on 2020-01-21 13:16:17

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
-- TOC entry 2994 (class 1262 OID 73484)
-- Name: eduweb_mis; Type: DATABASE; Schema: -; Owner: postgres
--

CREATE DATABASE eduweb_mis WITH TEMPLATE = template0 ENCODING = 'UTF8' LC_COLLATE = 'English_United States.1252' LC_CTYPE = 'English_United States.1252';


ALTER DATABASE eduweb_mis OWNER TO postgres;

\connect eduweb_mis

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
-- TOC entry 2 (class 3079 OID 79470)
-- Name: dblink; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS dblink WITH SCHEMA public;


--
-- TOC entry 2995 (class 0 OID 0)
-- Dependencies: 2
-- Name: EXTENSION dblink; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION dblink IS 'connect to other PostgreSQL databases from within a database';


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- TOC entry 204 (class 1259 OID 79516)
-- Name: clients; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.clients (
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
-- TOC entry 205 (class 1259 OID 79523)
-- Name: clients_client_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.clients_client_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.clients_client_id_seq OWNER TO postgres;

--
-- TOC entry 2996 (class 0 OID 0)
-- Dependencies: 205
-- Name: clients_client_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.clients_client_id_seq OWNED BY public.clients.client_id;


--
-- TOC entry 206 (class 1259 OID 79525)
-- Name: college_students; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.college_students (
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
-- TOC entry 207 (class 1259 OID 79533)
-- Name: college_students_college_student_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.college_students_college_student_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.college_students_college_student_id_seq OWNER TO postgres;

--
-- TOC entry 2997 (class 0 OID 0)
-- Dependencies: 207
-- Name: college_students_college_student_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.college_students_college_student_id_seq OWNED BY public.college_students.college_student_id;


--
-- TOC entry 208 (class 1259 OID 79535)
-- Name: college_students_school; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.college_students_school (
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
-- TOC entry 209 (class 1259 OID 79542)
-- Name: college_students_school_css_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.college_students_school_css_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.college_students_school_css_id_seq OWNER TO postgres;

--
-- TOC entry 2998 (class 0 OID 0)
-- Dependencies: 209
-- Name: college_students_school_css_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.college_students_school_css_id_seq OWNED BY public.college_students_school.css_id;


--
-- TOC entry 210 (class 1259 OID 79544)
-- Name: forgot_password; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.forgot_password (
    usr_name character varying NOT NULL,
    temp_pwd character varying NOT NULL,
    parent_id integer NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.forgot_password OWNER TO postgres;

--
-- TOC entry 211 (class 1259 OID 79551)
-- Name: notifications; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.notifications (
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
-- TOC entry 2999 (class 0 OID 0)
-- Dependencies: 211
-- Name: COLUMN notifications.device_user_ids; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.notifications.device_user_ids IS 'up to 2000 device ids';


--
-- TOC entry 212 (class 1259 OID 79559)
-- Name: notifications_notification_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.notifications_notification_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.notifications_notification_id_seq OWNER TO postgres;

--
-- TOC entry 3000 (class 0 OID 0)
-- Dependencies: 212
-- Name: notifications_notification_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.notifications_notification_id_seq OWNED BY public.notifications.notification_id;


--
-- TOC entry 213 (class 1259 OID 79561)
-- Name: parent_students; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.parent_students (
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
-- TOC entry 214 (class 1259 OID 79568)
-- Name: parent_students_parent_student_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.parent_students_parent_student_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.parent_students_parent_student_id_seq OWNER TO postgres;

--
-- TOC entry 3001 (class 0 OID 0)
-- Dependencies: 214
-- Name: parent_students_parent_student_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.parent_students_parent_student_id_seq OWNED BY public.parent_students.parent_student_id;


--
-- TOC entry 215 (class 1259 OID 79570)
-- Name: parents; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.parents (
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
-- TOC entry 216 (class 1259 OID 79578)
-- Name: parents_parent_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.parents_parent_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.parents_parent_id_seq OWNER TO postgres;

--
-- TOC entry 3002 (class 0 OID 0)
-- Dependencies: 216
-- Name: parents_parent_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.parents_parent_id_seq OWNED BY public.parents.parent_id;


--
-- TOC entry 217 (class 1259 OID 79580)
-- Name: registration_codes; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.registration_codes (
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
-- TOC entry 218 (class 1259 OID 79587)
-- Name: staff; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.staff (
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
-- TOC entry 219 (class 1259 OID 79595)
-- Name: staff_staff_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.staff_staff_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.staff_staff_id_seq OWNER TO postgres;

--
-- TOC entry 3003 (class 0 OID 0)
-- Dependencies: 219
-- Name: staff_staff_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.staff_staff_id_seq OWNED BY public.staff.staff_id;


--
-- TOC entry 220 (class 1259 OID 79597)
-- Name: traffic; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.traffic (
    traffic_id integer NOT NULL,
    school character varying NOT NULL,
    module character varying,
    creation_date timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.traffic OWNER TO postgres;

--
-- TOC entry 221 (class 1259 OID 79604)
-- Name: traffic_summary; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.traffic_summary (
    traffic_summary_id integer NOT NULL,
    week_number integer NOT NULL,
    module_traffic integer NOT NULL,
    module character varying,
    school character varying,
    creation_date timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.traffic_summary OWNER TO postgres;

--
-- TOC entry 222 (class 1259 OID 79611)
-- Name: traffic_summary_traffic_summary_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.traffic_summary_traffic_summary_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.traffic_summary_traffic_summary_id_seq OWNER TO postgres;

--
-- TOC entry 3004 (class 0 OID 0)
-- Dependencies: 222
-- Name: traffic_summary_traffic_summary_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.traffic_summary_traffic_summary_id_seq OWNED BY public.traffic_summary.traffic_summary_id;


--
-- TOC entry 223 (class 1259 OID 79613)
-- Name: traffic_traffic_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.traffic_traffic_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.traffic_traffic_id_seq OWNER TO postgres;

--
-- TOC entry 3005 (class 0 OID 0)
-- Dependencies: 223
-- Name: traffic_traffic_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.traffic_traffic_id_seq OWNED BY public.traffic.traffic_id;


--
-- TOC entry 2802 (class 2604 OID 79615)
-- Name: clients client_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.clients ALTER COLUMN client_id SET DEFAULT nextval('public.clients_client_id_seq'::regclass);


--
-- TOC entry 2805 (class 2604 OID 79616)
-- Name: college_students college_student_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.college_students ALTER COLUMN college_student_id SET DEFAULT nextval('public.college_students_college_student_id_seq'::regclass);


--
-- TOC entry 2807 (class 2604 OID 79617)
-- Name: college_students_school css_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.college_students_school ALTER COLUMN css_id SET DEFAULT nextval('public.college_students_school_css_id_seq'::regclass);


--
-- TOC entry 2811 (class 2604 OID 79618)
-- Name: notifications notification_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.notifications ALTER COLUMN notification_id SET DEFAULT nextval('public.notifications_notification_id_seq'::regclass);


--
-- TOC entry 2813 (class 2604 OID 79619)
-- Name: parent_students parent_student_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.parent_students ALTER COLUMN parent_student_id SET DEFAULT nextval('public.parent_students_parent_student_id_seq'::regclass);


--
-- TOC entry 2816 (class 2604 OID 79620)
-- Name: parents parent_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.parents ALTER COLUMN parent_id SET DEFAULT nextval('public.parents_parent_id_seq'::regclass);


--
-- TOC entry 2820 (class 2604 OID 79621)
-- Name: staff staff_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.staff ALTER COLUMN staff_id SET DEFAULT nextval('public.staff_staff_id_seq'::regclass);


--
-- TOC entry 2822 (class 2604 OID 79622)
-- Name: traffic traffic_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.traffic ALTER COLUMN traffic_id SET DEFAULT nextval('public.traffic_traffic_id_seq'::regclass);


--
-- TOC entry 2824 (class 2604 OID 79623)
-- Name: traffic_summary traffic_summary_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.traffic_summary ALTER COLUMN traffic_summary_id SET DEFAULT nextval('public.traffic_summary_traffic_summary_id_seq'::regclass);


--
-- TOC entry 2826 (class 2606 OID 79852)
-- Name: clients PK_clients_client_id; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.clients
    ADD CONSTRAINT "PK_clients_client_id" PRIMARY KEY (client_id);


--
-- TOC entry 2828 (class 2606 OID 79854)
-- Name: college_students PK_college_student_id; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.college_students
    ADD CONSTRAINT "PK_college_student_id" PRIMARY KEY (college_student_id);


--
-- TOC entry 2838 (class 2606 OID 79856)
-- Name: notifications PK_notification_id; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT "PK_notification_id" PRIMARY KEY (notification_id);


--
-- TOC entry 2842 (class 2606 OID 79858)
-- Name: parents PK_parents_parent_id; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.parents
    ADD CONSTRAINT "PK_parents_parent_id" PRIMARY KEY (parent_id);


--
-- TOC entry 2850 (class 2606 OID 79860)
-- Name: staff PK_staff_staff_id; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.staff
    ADD CONSTRAINT "PK_staff_staff_id" PRIMARY KEY (staff_id);


--
-- TOC entry 2848 (class 2606 OID 79862)
-- Name: registration_codes PK_telephone; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.registration_codes
    ADD CONSTRAINT "PK_telephone" PRIMARY KEY (telephone);


--
-- TOC entry 2860 (class 2606 OID 79864)
-- Name: traffic_summary PK_traffic_summary_id; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.traffic_summary
    ADD CONSTRAINT "PK_traffic_summary_id" PRIMARY KEY (traffic_summary_id);


--
-- TOC entry 2836 (class 2606 OID 79866)
-- Name: forgot_password PK_usr_name; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.forgot_password
    ADD CONSTRAINT "PK_usr_name" PRIMARY KEY (usr_name);


--
-- TOC entry 2844 (class 2606 OID 79868)
-- Name: parents U_id_number; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.parents
    ADD CONSTRAINT "U_id_number" UNIQUE (id_number);


--
-- TOC entry 2830 (class 2606 OID 79870)
-- Name: college_students U_student_id_number; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.college_students
    ADD CONSTRAINT "U_student_id_number" UNIQUE (student_id_number);


--
-- TOC entry 2832 (class 2606 OID 79872)
-- Name: college_students U_student_username; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.college_students
    ADD CONSTRAINT "U_student_username" UNIQUE (student_username);


--
-- TOC entry 2852 (class 2606 OID 79874)
-- Name: staff U_telephone; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.staff
    ADD CONSTRAINT "U_telephone" UNIQUE (telephone);


--
-- TOC entry 2846 (class 2606 OID 79876)
-- Name: parents U_username; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.parents
    ADD CONSTRAINT "U_username" UNIQUE (username);


--
-- TOC entry 2854 (class 2606 OID 79878)
-- Name: staff U_usernm; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.staff
    ADD CONSTRAINT "U_usernm" UNIQUE (usernm);


--
-- TOC entry 2834 (class 2606 OID 79880)
-- Name: college_students_school college_students_css_id; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.college_students_school
    ADD CONSTRAINT college_students_css_id PRIMARY KEY (css_id);


--
-- TOC entry 2856 (class 2606 OID 79882)
-- Name: staff id_number; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.staff
    ADD CONSTRAINT id_number UNIQUE (id_number);


--
-- TOC entry 2840 (class 2606 OID 79884)
-- Name: parent_students parent_students_parent_student_id; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.parent_students
    ADD CONSTRAINT parent_students_parent_student_id PRIMARY KEY (parent_student_id);


--
-- TOC entry 2858 (class 2606 OID 79886)
-- Name: traffic traffic_id; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.traffic
    ADD CONSTRAINT traffic_id PRIMARY KEY (traffic_id);


--
-- TOC entry 2861 (class 2606 OID 79887)
-- Name: college_students_school FK_college_students_student; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.college_students_school
    ADD CONSTRAINT "FK_college_students_student" FOREIGN KEY (college_student_id) REFERENCES public.college_students(college_student_id);


--
-- TOC entry 2862 (class 2606 OID 79892)
-- Name: parent_students FK_parent_students_parent; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.parent_students
    ADD CONSTRAINT "FK_parent_students_parent" FOREIGN KEY (parent_id) REFERENCES public.parents(parent_id);


-- Completed on 2020-01-21 13:16:22

--
-- PostgreSQL database dump complete
--

