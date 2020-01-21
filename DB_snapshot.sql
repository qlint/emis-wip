--
-- PostgreSQL database dump
--

-- Dumped from database version 12.1
-- Dumped by pg_dump version 12.1

-- Started on 2020-01-21 13:13:23

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
-- TOC entry 3652 (class 1262 OID 73483)
-- Name: eduweb_kingsinternational; Type: DATABASE; Schema: -; Owner: postgres
--

CREATE DATABASE eduweb_kingsinternational WITH TEMPLATE = template0 ENCODING = 'UTF8' LC_COLLATE = 'English_United States.1252' LC_CTYPE = 'English_United States.1252';


ALTER DATABASE eduweb_kingsinternational OWNER TO postgres;

\connect eduweb_kingsinternational

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
-- TOC entry 9 (class 2615 OID 78142)
-- Name: app; Type: SCHEMA; Schema: -; Owner: postgres
--

CREATE SCHEMA app;


ALTER SCHEMA app OWNER TO postgres;

--
-- TOC entry 3 (class 3079 OID 78143)
-- Name: dblink; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS dblink WITH SCHEMA public;


--
-- TOC entry 3653 (class 0 OID 0)
-- Dependencies: 3
-- Name: EXTENSION dblink; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION dblink IS 'connect to other PostgreSQL databases from within a database';


--
-- TOC entry 2 (class 3079 OID 78189)
-- Name: tablefunc; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS tablefunc WITH SCHEMA public;


--
-- TOC entry 3654 (class 0 OID 0)
-- Dependencies: 2
-- Name: EXTENSION tablefunc; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION tablefunc IS 'functions that manipulate whole tables, including crosstab';


--
-- TOC entry 389 (class 1255 OID 78210)
-- Name: colpivot(character varying, character varying, character varying[], character varying[], character varying, character varying); Type: FUNCTION; Schema: app; Owner: postgres
--

CREATE FUNCTION app.colpivot(out_table character varying, in_query character varying, key_cols character varying[], class_cols character varying[], value_e character varying, col_order character varying) RETURNS void
    LANGUAGE plpgsql
    AS $$
    declare
        in_table varchar;
        col varchar;
        ali varchar;
        on_e varchar;
        i integer;
        rec record;
        query varchar;
        -- This is actually an array of arrays but postgres does not support an array of arrays type so we flatten it.
        -- We could theoretically use the matrix feature but it's extremly cancerogenous and we would have to involve
        -- custom aggrigates. For most intents and purposes postgres does not have a multi-dimensional array type.
        clsc_cols text[] := array[]::text[];
        n_clsc_cols integer;
        n_class_cols integer;
    begin
        in_table := quote_ident('__' || out_table || '_in');
        execute ('create temp table ' || in_table || ' on commit drop as ' || in_query);
        -- get ordered unique columns (column combinations)
        query := 'select array[';
        i := 0;
        foreach col in array class_cols loop
            if i > 0 then
                query := query || ', ';
            end if;
            query := query || 'quote_literal(' || quote_ident(col) || ')';
            i := i + 1;
        end loop;
        query := query || '] x from ' || in_table;
        for j in 1..2 loop
            if j = 1 then
                query := query || ' group by ';
            else
                query := query || ' order by ';
                if col_order is not null then
                    query := query || col_order || ' ';
                    exit;
                end if;
            end if;
            i := 0;
            foreach col in array class_cols loop
                if i > 0 then
                    query := query || ', ';
                end if;
                query := query || quote_ident(col);
                i := i + 1;
            end loop;
        end loop;
        -- raise notice '%', query;
        for rec in
            execute query
        loop
            clsc_cols := array_cat(clsc_cols, rec.x);
        end loop;
        n_class_cols := array_length(class_cols, 1);
        n_clsc_cols := array_length(clsc_cols, 1) / n_class_cols;
        -- build target query
        query := 'select ';
        i := 0;
        foreach col in array key_cols loop
            if i > 0 then
                query := query || ', ';
            end if;
            query := query || '_key.' || quote_ident(col) || ' ';
            i := i + 1;
        end loop;
        for j in 1..n_clsc_cols loop
            query := query || ', ';
            col := '';
            for k in 1..n_class_cols loop
                if k > 1 then
                    col := col || ', ';
                end if;
                col := col || clsc_cols[(j - 1) * n_class_cols + k];
            end loop;
            ali := '_clsc_' || j::text;
            query := query || '(' || replace(value_e, '#', ali) || ')' || ' as ' || quote_ident(col) || ' ';
        end loop;
        query := query || ' from (select distinct ';
        i := 0;
        foreach col in array key_cols loop
            if i > 0 then
                query := query || ', ';
            end if;
            query := query || quote_ident(col) || ' ';
            i := i + 1;
        end loop;
        query := query || ' from ' || in_table || ') _key ';
        for j in 1..n_clsc_cols loop
            ali := '_clsc_' || j::text;
            on_e := '';
            i := 0;
            foreach col in array key_cols loop
                if i > 0 then
                    on_e := on_e || ' and ';
                end if;
                on_e := on_e || ali || '.' || quote_ident(col) || ' = _key.' || quote_ident(col) || ' ';
                i := i + 1;
            end loop;
            for k in 1..n_class_cols loop
                on_e := on_e || ' and ';
                on_e := on_e || ali || '.' || quote_ident(class_cols[k]) || ' = ' || clsc_cols[(j - 1) * n_class_cols + k];
            end loop;
            query := query || 'left join ' || in_table || ' as ' || ali || ' on ' || on_e || ' ';
        end loop;
        -- raise notice '%', query;
        execute ('create temp table ' || quote_ident(out_table) || ' on commit drop as ' || query);
        -- cleanup temporary in_table before we return
        execute ('drop table ' || in_table)
        return;
    end;
$$;


ALTER FUNCTION app.colpivot(out_table character varying, in_query character varying, key_cols character varying[], class_cols character varying[], value_e character varying, col_order character varying) OWNER TO postgres;

--
-- TOC entry 390 (class 1255 OID 78211)
-- Name: set_invoice_term(); Type: FUNCTION; Schema: app; Owner: postgres
--

CREATE FUNCTION app.set_invoice_term() RETURNS boolean
    LANGUAGE plpgsql
    AS $$
declare
	_result record;
begin

for _result in 
		select inv_id, (select term_id from app.terms where due_date between start_date and end_date) as term_id
		from app.invoices
	loop
		update app.invoices set term_id = _result.term_id where inv_id = _result.inv_id;
	end loop;
	return true;
end;
$$;


ALTER FUNCTION app.set_invoice_term() OWNER TO postgres;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- TOC entry 209 (class 1259 OID 78212)
-- Name: blog_post_statuses; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.blog_post_statuses (
    post_status_id integer NOT NULL,
    post_status character varying NOT NULL
);


ALTER TABLE app.blog_post_statuses OWNER TO postgres;

--
-- TOC entry 210 (class 1259 OID 78218)
-- Name: blog_post_statuses_post_status_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.blog_post_statuses_post_status_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.blog_post_statuses_post_status_id_seq OWNER TO postgres;

--
-- TOC entry 3655 (class 0 OID 0)
-- Dependencies: 210
-- Name: blog_post_statuses_post_status_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.blog_post_statuses_post_status_id_seq OWNED BY app.blog_post_statuses.post_status_id;


--
-- TOC entry 211 (class 1259 OID 78220)
-- Name: blog_post_types; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.blog_post_types (
    post_type_id integer NOT NULL,
    post_type character varying NOT NULL
);


ALTER TABLE app.blog_post_types OWNER TO postgres;

--
-- TOC entry 212 (class 1259 OID 78226)
-- Name: blog_post_types_post_type_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.blog_post_types_post_type_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.blog_post_types_post_type_id_seq OWNER TO postgres;

--
-- TOC entry 3656 (class 0 OID 0)
-- Dependencies: 212
-- Name: blog_post_types_post_type_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.blog_post_types_post_type_id_seq OWNED BY app.blog_post_types.post_type_id;


--
-- TOC entry 213 (class 1259 OID 78228)
-- Name: blog_posts; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.blog_posts (
    post_id integer NOT NULL,
    blog_id integer NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    post_type_id integer,
    body text,
    title character varying NOT NULL,
    post_status_id integer NOT NULL,
    feature_image character varying,
    modified_date timestamp without time zone,
    modified_by integer
);


ALTER TABLE app.blog_posts OWNER TO postgres;

--
-- TOC entry 214 (class 1259 OID 78235)
-- Name: blog_posts_post_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.blog_posts_post_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.blog_posts_post_id_seq OWNER TO postgres;

--
-- TOC entry 3657 (class 0 OID 0)
-- Dependencies: 214
-- Name: blog_posts_post_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.blog_posts_post_id_seq OWNED BY app.blog_posts.post_id;


--
-- TOC entry 215 (class 1259 OID 78237)
-- Name: blogs; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.blogs (
    blog_id integer NOT NULL,
    teacher_id integer NOT NULL,
    class_id integer NOT NULL,
    blog_name character varying
);


ALTER TABLE app.blogs OWNER TO postgres;

--
-- TOC entry 216 (class 1259 OID 78243)
-- Name: blogs_blog_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.blogs_blog_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.blogs_blog_id_seq OWNER TO postgres;

--
-- TOC entry 3658 (class 0 OID 0)
-- Dependencies: 216
-- Name: blogs_blog_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.blogs_blog_id_seq OWNED BY app.blogs.blog_id;


--
-- TOC entry 217 (class 1259 OID 78245)
-- Name: buses; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.buses (
    bus_id integer NOT NULL,
    bus_type character varying NOT NULL,
    bus_registration character varying NOT NULL,
    bus_driver integer,
    bus_guide integer,
    active boolean DEFAULT true NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    modified_date timestamp without time zone,
    destinations character varying
);


ALTER TABLE app.buses OWNER TO postgres;

--
-- TOC entry 218 (class 1259 OID 78253)
-- Name: buses_bus_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.buses_bus_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.buses_bus_id_seq OWNER TO postgres;

--
-- TOC entry 3659 (class 0 OID 0)
-- Dependencies: 218
-- Name: buses_bus_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.buses_bus_id_seq OWNED BY app.buses.bus_id;


--
-- TOC entry 219 (class 1259 OID 78255)
-- Name: class_cats; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.class_cats (
    class_cat_id integer NOT NULL,
    class_cat_name character varying NOT NULL,
    active boolean DEFAULT true NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    modified_date timestamp without time zone,
    modified_by integer,
    entity_id integer
);


ALTER TABLE app.class_cats OWNER TO postgres;

--
-- TOC entry 220 (class 1259 OID 78263)
-- Name: class_cats_class_cat_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.class_cats_class_cat_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.class_cats_class_cat_id_seq OWNER TO postgres;

--
-- TOC entry 3660 (class 0 OID 0)
-- Dependencies: 220
-- Name: class_cats_class_cat_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.class_cats_class_cat_id_seq OWNED BY app.class_cats.class_cat_id;


--
-- TOC entry 221 (class 1259 OID 78265)
-- Name: class_subject_exams; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.class_subject_exams (
    class_sub_exam_id integer NOT NULL,
    class_subject_id integer NOT NULL,
    exam_type_id integer NOT NULL,
    grade_weight integer,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    modified_date timestamp without time zone,
    modified_by integer,
    active boolean DEFAULT true NOT NULL
);


ALTER TABLE app.class_subject_exams OWNER TO postgres;

--
-- TOC entry 222 (class 1259 OID 78270)
-- Name: class_subject_exams_class_sub_exam_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.class_subject_exams_class_sub_exam_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.class_subject_exams_class_sub_exam_id_seq OWNER TO postgres;

--
-- TOC entry 3661 (class 0 OID 0)
-- Dependencies: 222
-- Name: class_subject_exams_class_sub_exam_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.class_subject_exams_class_sub_exam_id_seq OWNED BY app.class_subject_exams.class_sub_exam_id;


--
-- TOC entry 223 (class 1259 OID 78272)
-- Name: class_subjects; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.class_subjects (
    class_subject_id integer NOT NULL,
    class_id integer NOT NULL,
    subject_id integer NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    modified_date timestamp without time zone,
    modified_by integer,
    active boolean DEFAULT true NOT NULL
);


ALTER TABLE app.class_subjects OWNER TO postgres;

--
-- TOC entry 224 (class 1259 OID 78277)
-- Name: class_subjects_class_subject_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.class_subjects_class_subject_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.class_subjects_class_subject_id_seq OWNER TO postgres;

--
-- TOC entry 3662 (class 0 OID 0)
-- Dependencies: 224
-- Name: class_subjects_class_subject_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.class_subjects_class_subject_id_seq OWNED BY app.class_subjects.class_subject_id;


--
-- TOC entry 225 (class 1259 OID 78279)
-- Name: class_timetables; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.class_timetables (
    class_timetable_id integer NOT NULL,
    class_id integer NOT NULL,
    term_id integer NOT NULL,
    subject_name character varying NOT NULL,
    year character varying,
    month character varying,
    day character varying,
    start_hour character varying NOT NULL,
    start_minutes character varying NOT NULL,
    end_hour character varying NOT NULL,
    end_minutes character varying NOT NULL,
    color character varying,
    creation_date timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE app.class_timetables OWNER TO postgres;

--
-- TOC entry 226 (class 1259 OID 78286)
-- Name: class_timetables_class_timetable_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.class_timetables_class_timetable_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.class_timetables_class_timetable_id_seq OWNER TO postgres;

--
-- TOC entry 3663 (class 0 OID 0)
-- Dependencies: 226
-- Name: class_timetables_class_timetable_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.class_timetables_class_timetable_id_seq OWNED BY app.class_timetables.class_timetable_id;


--
-- TOC entry 227 (class 1259 OID 78288)
-- Name: classes; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.classes (
    class_id integer NOT NULL,
    class_name character varying NOT NULL,
    class_cat_id integer NOT NULL,
    teacher_id integer,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    active boolean DEFAULT true NOT NULL,
    modified_date timestamp without time zone,
    modified_by integer,
    sort_order integer,
    report_card_type character varying
);


ALTER TABLE app.classes OWNER TO postgres;

--
-- TOC entry 228 (class 1259 OID 78296)
-- Name: classes_class_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.classes_class_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.classes_class_id_seq OWNER TO postgres;

--
-- TOC entry 3664 (class 0 OID 0)
-- Dependencies: 228
-- Name: classes_class_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.classes_class_id_seq OWNED BY app.classes.class_id;


--
-- TOC entry 229 (class 1259 OID 78298)
-- Name: communication_attachments; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.communication_attachments (
    com_id integer,
    attachment_id integer NOT NULL,
    attachment character varying
);


ALTER TABLE app.communication_attachments OWNER TO postgres;

--
-- TOC entry 230 (class 1259 OID 78304)
-- Name: communication_attachments_attachment_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.communication_attachments_attachment_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.communication_attachments_attachment_id_seq OWNER TO postgres;

--
-- TOC entry 3665 (class 0 OID 0)
-- Dependencies: 230
-- Name: communication_attachments_attachment_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.communication_attachments_attachment_id_seq OWNED BY app.communication_attachments.attachment_id;


--
-- TOC entry 231 (class 1259 OID 78306)
-- Name: communication_audience; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.communication_audience (
    audience_id integer NOT NULL,
    audience character varying NOT NULL,
    module character varying
);


ALTER TABLE app.communication_audience OWNER TO postgres;

--
-- TOC entry 232 (class 1259 OID 78312)
-- Name: communication_audience_audience_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.communication_audience_audience_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.communication_audience_audience_id_seq OWNER TO postgres;

--
-- TOC entry 3666 (class 0 OID 0)
-- Dependencies: 232
-- Name: communication_audience_audience_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.communication_audience_audience_id_seq OWNED BY app.communication_audience.audience_id;


--
-- TOC entry 233 (class 1259 OID 78314)
-- Name: communication_emails; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.communication_emails (
    email_id integer NOT NULL,
    com_id integer NOT NULL,
    email_address character varying NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    send_date timestamp without time zone,
    forwarded boolean DEFAULT false NOT NULL
);


ALTER TABLE app.communication_emails OWNER TO postgres;

--
-- TOC entry 234 (class 1259 OID 78322)
-- Name: communication_emails_email_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.communication_emails_email_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.communication_emails_email_id_seq OWNER TO postgres;

--
-- TOC entry 3667 (class 0 OID 0)
-- Dependencies: 234
-- Name: communication_emails_email_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.communication_emails_email_id_seq OWNED BY app.communication_emails.email_id;


--
-- TOC entry 235 (class 1259 OID 78324)
-- Name: communication_feedback; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.communication_feedback (
    com_feedback_id integer NOT NULL,
    opened boolean DEFAULT false NOT NULL,
    subject character varying NOT NULL,
    message character varying NOT NULL,
    message_from character varying,
    student_id integer,
    guardian_id integer,
    class_id integer,
    creation_date timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE app.communication_feedback OWNER TO postgres;

--
-- TOC entry 236 (class 1259 OID 78332)
-- Name: communication_feedback_com_feedback_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.communication_feedback_com_feedback_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.communication_feedback_com_feedback_id_seq OWNER TO postgres;

--
-- TOC entry 3668 (class 0 OID 0)
-- Dependencies: 236
-- Name: communication_feedback_com_feedback_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.communication_feedback_com_feedback_id_seq OWNED BY app.communication_feedback.com_feedback_id;


--
-- TOC entry 237 (class 1259 OID 78334)
-- Name: communication_sms; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.communication_sms (
    sms_id integer NOT NULL,
    com_id integer NOT NULL,
    sim_number numeric NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    send_date timestamp without time zone,
    forwarded boolean DEFAULT false NOT NULL,
    first_name text,
    last_name text
);


ALTER TABLE app.communication_sms OWNER TO postgres;

--
-- TOC entry 238 (class 1259 OID 78342)
-- Name: communication_sms_sms_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.communication_sms_sms_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.communication_sms_sms_id_seq OWNER TO postgres;

--
-- TOC entry 3669 (class 0 OID 0)
-- Dependencies: 238
-- Name: communication_sms_sms_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.communication_sms_sms_id_seq OWNED BY app.communication_sms.sms_id;


--
-- TOC entry 239 (class 1259 OID 78344)
-- Name: communication_types; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.communication_types (
    com_type_id integer NOT NULL,
    com_type character varying NOT NULL
);


ALTER TABLE app.communication_types OWNER TO postgres;

--
-- TOC entry 240 (class 1259 OID 78350)
-- Name: communication_types_com_type_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.communication_types_com_type_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.communication_types_com_type_id_seq OWNER TO postgres;

--
-- TOC entry 3670 (class 0 OID 0)
-- Dependencies: 240
-- Name: communication_types_com_type_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.communication_types_com_type_id_seq OWNED BY app.communication_types.com_type_id;


--
-- TOC entry 241 (class 1259 OID 78352)
-- Name: communications; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.communications (
    com_id integer NOT NULL,
    com_date date,
    audience_id integer NOT NULL,
    com_type_id integer NOT NULL,
    post_status_id integer,
    subject character varying,
    message character varying NOT NULL,
    attachment character varying,
    message_from integer NOT NULL,
    student_id integer,
    guardian_id integer,
    class_id integer,
    send_as_email boolean,
    send_as_sms boolean,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    reply_to character varying,
    sent boolean DEFAULT false NOT NULL,
    sent_date timestamp without time zone,
    modified_date timestamp without time zone,
    modified_by integer,
    route integer,
    activity character varying,
    guardians character varying,
    students character varying,
    dept_id integer,
    emp_cat_id integer,
    student_type character varying,
    house_name character varying,
    committee_name character varying,
    to_employee integer
);


ALTER TABLE app.communications OWNER TO postgres;

--
-- TOC entry 242 (class 1259 OID 78360)
-- Name: communications_com_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.communications_com_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.communications_com_id_seq OWNER TO postgres;

--
-- TOC entry 3671 (class 0 OID 0)
-- Dependencies: 242
-- Name: communications_com_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.communications_com_id_seq OWNED BY app.communications.com_id;


--
-- TOC entry 243 (class 1259 OID 78362)
-- Name: countries; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.countries (
    countries_id integer NOT NULL,
    countries_name character varying(255) NOT NULL,
    countries_iso_code_2 character(2) NOT NULL,
    countries_iso_code_3 character(3) NOT NULL,
    address_format_id integer NOT NULL
);


ALTER TABLE app.countries OWNER TO postgres;

--
-- TOC entry 244 (class 1259 OID 78365)
-- Name: countries_countries_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.countries_countries_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.countries_countries_id_seq OWNER TO postgres;

--
-- TOC entry 3672 (class 0 OID 0)
-- Dependencies: 244
-- Name: countries_countries_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.countries_countries_id_seq OWNED BY app.countries.countries_id;


--
-- TOC entry 245 (class 1259 OID 78367)
-- Name: credits; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.credits (
    credit_id integer NOT NULL,
    student_id integer NOT NULL,
    amount numeric NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    modified_date timestamp without time zone,
    modified_by integer,
    amount_applied numeric DEFAULT 0 NOT NULL,
    payment_id integer
);


ALTER TABLE app.credits OWNER TO postgres;

--
-- TOC entry 246 (class 1259 OID 78375)
-- Name: credits_credit_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.credits_credit_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.credits_credit_id_seq OWNER TO postgres;

--
-- TOC entry 3673 (class 0 OID 0)
-- Dependencies: 246
-- Name: credits_credit_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.credits_credit_id_seq OWNED BY app.credits.credit_id;


--
-- TOC entry 247 (class 1259 OID 78377)
-- Name: terms; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.terms (
    term_id integer NOT NULL,
    term_name character varying NOT NULL,
    start_date date NOT NULL,
    end_date date NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    term_number integer
);


ALTER TABLE app.terms OWNER TO postgres;

--
-- TOC entry 248 (class 1259 OID 78384)
-- Name: current_term; Type: VIEW; Schema: app; Owner: postgres
--

CREATE VIEW app.current_term AS
 SELECT terms.term_id,
    terms.term_name,
    terms.start_date,
    terms.end_date,
    terms.creation_date,
    terms.created_by,
    terms.term_number
   FROM app.terms
  WHERE ((now())::date > terms.start_date)
  ORDER BY terms.start_date DESC
 LIMIT 1;


ALTER TABLE app.current_term OWNER TO postgres;

--
-- TOC entry 249 (class 1259 OID 78388)
-- Name: departments; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.departments (
    dept_id integer NOT NULL,
    dept_name character varying NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    active boolean DEFAULT true NOT NULL,
    category character varying,
    modified_date timestamp without time zone,
    modified_by integer
);


ALTER TABLE app.departments OWNER TO postgres;

--
-- TOC entry 250 (class 1259 OID 78396)
-- Name: departments_dept_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.departments_dept_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.departments_dept_id_seq OWNER TO postgres;

--
-- TOC entry 3674 (class 0 OID 0)
-- Dependencies: 250
-- Name: departments_dept_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.departments_dept_id_seq OWNED BY app.departments.dept_id;


--
-- TOC entry 251 (class 1259 OID 78398)
-- Name: employee_cats; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.employee_cats (
    emp_cat_id integer NOT NULL,
    emp_cat_name character varying NOT NULL,
    active boolean DEFAULT true NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    modified_date timestamp without time zone,
    modified_by integer
);


ALTER TABLE app.employee_cats OWNER TO postgres;

--
-- TOC entry 252 (class 1259 OID 78406)
-- Name: employee_cats_emp_cat_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.employee_cats_emp_cat_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.employee_cats_emp_cat_id_seq OWNER TO postgres;

--
-- TOC entry 3675 (class 0 OID 0)
-- Dependencies: 252
-- Name: employee_cats_emp_cat_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.employee_cats_emp_cat_id_seq OWNED BY app.employee_cats.emp_cat_id;


--
-- TOC entry 253 (class 1259 OID 78408)
-- Name: employees; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.employees (
    emp_id integer NOT NULL,
    emp_cat_id integer NOT NULL,
    dept_id integer NOT NULL,
    emp_number character varying,
    id_number character varying,
    gender character(1),
    first_name character varying NOT NULL,
    middle_name character varying,
    last_name character varying NOT NULL,
    initials character varying,
    country character varying,
    active boolean DEFAULT true NOT NULL,
    telephone character varying,
    email character varying,
    joined_date date,
    job_title character varying,
    qualifications character varying,
    experience character varying,
    additional_info character varying,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    emp_image character varying,
    next_of_kin_name character varying,
    next_of_kin_telephone character varying,
    next_of_kin_email character varying,
    modified_date timestamp without time zone,
    modified_by integer,
    login_id integer,
    dob character varying,
    house character varying,
    committee character varying,
    telephone2 character varying
);


ALTER TABLE app.employees OWNER TO postgres;

--
-- TOC entry 254 (class 1259 OID 78416)
-- Name: employees_emp_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.employees_emp_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.employees_emp_id_seq OWNER TO postgres;

--
-- TOC entry 3676 (class 0 OID 0)
-- Dependencies: 254
-- Name: employees_emp_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.employees_emp_id_seq OWNED BY app.employees.emp_id;


--
-- TOC entry 255 (class 1259 OID 78418)
-- Name: exam_marks; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.exam_marks (
    exam_id integer NOT NULL,
    student_id integer NOT NULL,
    class_sub_exam_id integer NOT NULL,
    term_id integer NOT NULL,
    mark integer,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    modified_date timestamp without time zone,
    modified_by integer
);


ALTER TABLE app.exam_marks OWNER TO postgres;

--
-- TOC entry 256 (class 1259 OID 78422)
-- Name: exam_marks_exam_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.exam_marks_exam_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.exam_marks_exam_id_seq OWNER TO postgres;

--
-- TOC entry 3677 (class 0 OID 0)
-- Dependencies: 256
-- Name: exam_marks_exam_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.exam_marks_exam_id_seq OWNED BY app.exam_marks.exam_id;


--
-- TOC entry 257 (class 1259 OID 78424)
-- Name: exam_types; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.exam_types (
    exam_type_id integer NOT NULL,
    exam_type character varying NOT NULL,
    class_cat_id integer NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    modified_date timestamp without time zone,
    modified_by integer,
    sort_order integer,
    is_special_exam boolean DEFAULT false
);


ALTER TABLE app.exam_types OWNER TO postgres;

--
-- TOC entry 258 (class 1259 OID 78432)
-- Name: exam_types_exam_type_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.exam_types_exam_type_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.exam_types_exam_type_id_seq OWNER TO postgres;

--
-- TOC entry 3678 (class 0 OID 0)
-- Dependencies: 258
-- Name: exam_types_exam_type_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.exam_types_exam_type_id_seq OWNED BY app.exam_types.exam_type_id;


--
-- TOC entry 259 (class 1259 OID 78434)
-- Name: fee_item_uniforms; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.fee_item_uniforms (
    uniform_id integer NOT NULL,
    uniform character varying NOT NULL,
    amount numeric NOT NULL,
    active boolean DEFAULT true NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    modified_date timestamp without time zone,
    modified_by integer
);


ALTER TABLE app.fee_item_uniforms OWNER TO postgres;

--
-- TOC entry 260 (class 1259 OID 78442)
-- Name: fee_item_uniforms_uniform_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.fee_item_uniforms_uniform_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.fee_item_uniforms_uniform_id_seq OWNER TO postgres;

--
-- TOC entry 3679 (class 0 OID 0)
-- Dependencies: 260
-- Name: fee_item_uniforms_uniform_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.fee_item_uniforms_uniform_id_seq OWNED BY app.fee_item_uniforms.uniform_id;


--
-- TOC entry 261 (class 1259 OID 78444)
-- Name: fee_items; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.fee_items (
    fee_item_id integer NOT NULL,
    fee_item character varying NOT NULL,
    default_amount double precision,
    frequency character varying NOT NULL,
    active boolean DEFAULT true NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    class_cats_restriction integer[],
    optional boolean DEFAULT false,
    new_student_only boolean DEFAULT false,
    replaceable boolean DEFAULT false NOT NULL,
    modified_date timestamp without time zone,
    modified_by integer
);


ALTER TABLE app.fee_items OWNER TO postgres;

--
-- TOC entry 262 (class 1259 OID 78455)
-- Name: fee_items_fee_item_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.fee_items_fee_item_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.fee_items_fee_item_id_seq OWNER TO postgres;

--
-- TOC entry 3680 (class 0 OID 0)
-- Dependencies: 262
-- Name: fee_items_fee_item_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.fee_items_fee_item_id_seq OWNED BY app.fee_items.fee_item_id;


--
-- TOC entry 263 (class 1259 OID 78457)
-- Name: grading; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.grading (
    grade_id integer NOT NULL,
    grade character varying NOT NULL,
    min_mark integer NOT NULL,
    max_mark integer NOT NULL,
    comment character varying,
    kiswahili_comment character varying,
    principal_comment character varying
);


ALTER TABLE app.grading OWNER TO postgres;

--
-- TOC entry 264 (class 1259 OID 78463)
-- Name: grading2; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.grading2 (
    grade2_id integer NOT NULL,
    grade2 character varying NOT NULL,
    min_mark integer NOT NULL,
    max_mark integer NOT NULL,
    comment character varying,
    kiswahili_comment character varying
);


ALTER TABLE app.grading2 OWNER TO postgres;

--
-- TOC entry 265 (class 1259 OID 78469)
-- Name: grading2_grade2_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.grading2_grade2_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.grading2_grade2_id_seq OWNER TO postgres;

--
-- TOC entry 3681 (class 0 OID 0)
-- Dependencies: 265
-- Name: grading2_grade2_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.grading2_grade2_id_seq OWNED BY app.grading2.grade2_id;


--
-- TOC entry 266 (class 1259 OID 78471)
-- Name: grading_grade_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.grading_grade_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.grading_grade_id_seq OWNER TO postgres;

--
-- TOC entry 3682 (class 0 OID 0)
-- Dependencies: 266
-- Name: grading_grade_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.grading_grade_id_seq OWNED BY app.grading.grade_id;


--
-- TOC entry 267 (class 1259 OID 78473)
-- Name: guardians; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.guardians (
    guardian_id integer NOT NULL,
    first_name character varying NOT NULL,
    middle_name character varying,
    last_name character varying NOT NULL,
    id_number character varying NOT NULL,
    telephone character varying,
    email character varying,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    active boolean DEFAULT true NOT NULL,
    occupation character varying,
    address character varying,
    title character varying,
    marital_status character varying,
    work_email character varying,
    employer character varying,
    employer_address character varying,
    work_phone character varying,
    modified_date timestamp without time zone,
    modified_by integer,
    telephone2 character varying
);


ALTER TABLE app.guardians OWNER TO postgres;

--
-- TOC entry 268 (class 1259 OID 78481)
-- Name: guardians_guardian_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.guardians_guardian_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.guardians_guardian_id_seq OWNER TO postgres;

--
-- TOC entry 3683 (class 0 OID 0)
-- Dependencies: 268
-- Name: guardians_guardian_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.guardians_guardian_id_seq OWNED BY app.guardians.guardian_id;


--
-- TOC entry 269 (class 1259 OID 78483)
-- Name: homework; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.homework (
    homework_id integer NOT NULL,
    class_subject_id integer NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    due_date timestamp without time zone,
    assigned_date timestamp without time zone,
    body text,
    title character varying NOT NULL,
    post_status_id integer NOT NULL,
    attachment character varying,
    modified_date timestamp without time zone,
    modified_by integer
);


ALTER TABLE app.homework OWNER TO postgres;

--
-- TOC entry 270 (class 1259 OID 78490)
-- Name: homework_homework_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.homework_homework_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.homework_homework_id_seq OWNER TO postgres;

--
-- TOC entry 3684 (class 0 OID 0)
-- Dependencies: 270
-- Name: homework_homework_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.homework_homework_id_seq OWNED BY app.homework.homework_id;


--
-- TOC entry 271 (class 1259 OID 78492)
-- Name: installment_options; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.installment_options (
    installment_id integer NOT NULL,
    payment_plan_name character varying NOT NULL,
    active boolean DEFAULT true NOT NULL,
    num_payments integer,
    payment_interval integer,
    payment_interval2 character varying
);


ALTER TABLE app.installment_options OWNER TO postgres;

--
-- TOC entry 3685 (class 0 OID 0)
-- Dependencies: 271
-- Name: COLUMN installment_options.payment_interval; Type: COMMENT; Schema: app; Owner: postgres
--

COMMENT ON COLUMN app.installment_options.payment_interval IS 'number of days';


--
-- TOC entry 272 (class 1259 OID 78499)
-- Name: installment_options_installment_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.installment_options_installment_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.installment_options_installment_id_seq OWNER TO postgres;

--
-- TOC entry 3686 (class 0 OID 0)
-- Dependencies: 272
-- Name: installment_options_installment_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.installment_options_installment_id_seq OWNED BY app.installment_options.installment_id;


--
-- TOC entry 273 (class 1259 OID 78501)
-- Name: invoice_balances; Type: VIEW; Schema: app; Owner: postgres
--

CREATE VIEW app.invoice_balances AS
SELECT
    NULL::integer AS student_id,
    NULL::integer AS inv_id,
    NULL::date AS inv_date,
    NULL::numeric AS total_due,
    NULL::numeric AS total_paid,
    NULL::numeric AS balance,
    NULL::date AS due_date,
    NULL::boolean AS past_due,
    NULL::boolean AS canceled;


ALTER TABLE app.invoice_balances OWNER TO postgres;

--
-- TOC entry 274 (class 1259 OID 78505)
-- Name: invoice_balances2; Type: VIEW; Schema: app; Owner: postgres
--

CREATE VIEW app.invoice_balances2 AS
SELECT
    NULL::integer AS student_id,
    NULL::integer AS inv_id,
    NULL::date AS inv_date,
    NULL::numeric AS total_due,
    NULL::numeric AS total_paid,
    NULL::numeric AS balance,
    NULL::date AS due_date,
    NULL::boolean AS past_due,
    NULL::boolean AS canceled,
    NULL::integer AS term_id;


ALTER TABLE app.invoice_balances2 OWNER TO postgres;

--
-- TOC entry 275 (class 1259 OID 78509)
-- Name: invoice_line_items; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.invoice_line_items (
    inv_item_id integer NOT NULL,
    inv_id integer NOT NULL,
    student_fee_item_id integer NOT NULL,
    amount numeric NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    modified_date timestamp without time zone,
    modified_by integer
);


ALTER TABLE app.invoice_line_items OWNER TO postgres;

--
-- TOC entry 276 (class 1259 OID 78516)
-- Name: invoice_line_items_inv_item_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.invoice_line_items_inv_item_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.invoice_line_items_inv_item_id_seq OWNER TO postgres;

--
-- TOC entry 3687 (class 0 OID 0)
-- Dependencies: 276
-- Name: invoice_line_items_inv_item_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.invoice_line_items_inv_item_id_seq OWNED BY app.invoice_line_items.inv_item_id;


--
-- TOC entry 277 (class 1259 OID 78518)
-- Name: invoices; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.invoices (
    inv_id integer NOT NULL,
    student_id integer NOT NULL,
    inv_date date NOT NULL,
    total_amount numeric NOT NULL,
    due_date date NOT NULL,
    paid_in_full boolean DEFAULT false NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    modified_date timestamp without time zone,
    modified_by integer,
    canceled boolean DEFAULT false NOT NULL,
    term_id integer,
    custom_invoice_no character varying
);


ALTER TABLE app.invoices OWNER TO postgres;

--
-- TOC entry 278 (class 1259 OID 78527)
-- Name: invoices_inv_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.invoices_inv_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.invoices_inv_id_seq OWNER TO postgres;

--
-- TOC entry 3688 (class 0 OID 0)
-- Dependencies: 278
-- Name: invoices_inv_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.invoices_inv_id_seq OWNED BY app.invoices.inv_id;


--
-- TOC entry 279 (class 1259 OID 78529)
-- Name: lowersch_reportcards; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.lowersch_reportcards (
    lowersch_reportcards_id integer NOT NULL,
    student_id integer NOT NULL,
    term_id integer NOT NULL,
    file_name character varying
);


ALTER TABLE app.lowersch_reportcards OWNER TO postgres;

--
-- TOC entry 280 (class 1259 OID 78535)
-- Name: lowersch_reportcards_lowersch_reportcards_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.lowersch_reportcards_lowersch_reportcards_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.lowersch_reportcards_lowersch_reportcards_id_seq OWNER TO postgres;

--
-- TOC entry 3689 (class 0 OID 0)
-- Dependencies: 280
-- Name: lowersch_reportcards_lowersch_reportcards_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.lowersch_reportcards_lowersch_reportcards_id_seq OWNED BY app.lowersch_reportcards.lowersch_reportcards_id;


--
-- TOC entry 281 (class 1259 OID 78537)
-- Name: medical_conditions; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.medical_conditions (
    condition_id integer NOT NULL,
    illness_condition character varying NOT NULL
);


ALTER TABLE app.medical_conditions OWNER TO postgres;

--
-- TOC entry 282 (class 1259 OID 78543)
-- Name: medical_conditions_condition_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.medical_conditions_condition_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.medical_conditions_condition_id_seq OWNER TO postgres;

--
-- TOC entry 3690 (class 0 OID 0)
-- Dependencies: 282
-- Name: medical_conditions_condition_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.medical_conditions_condition_id_seq OWNED BY app.medical_conditions.condition_id;


--
-- TOC entry 283 (class 1259 OID 78545)
-- Name: next_term; Type: VIEW; Schema: app; Owner: postgres
--

CREATE VIEW app.next_term AS
 SELECT terms.term_id,
    terms.term_name,
    terms.start_date,
    terms.end_date,
    terms.creation_date,
    terms.created_by,
    terms.term_number
   FROM app.terms
  WHERE ((now())::date < terms.start_date)
  ORDER BY terms.start_date
 LIMIT 1;


ALTER TABLE app.next_term OWNER TO postgres;

--
-- TOC entry 284 (class 1259 OID 78549)
-- Name: payment_inv_items; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.payment_inv_items (
    payment_inv_item_id integer NOT NULL,
    payment_id integer NOT NULL,
    inv_id integer NOT NULL,
    inv_item_id integer,
    amount numeric NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    modified_date timestamp without time zone,
    modified_by integer
);


ALTER TABLE app.payment_inv_items OWNER TO postgres;

--
-- TOC entry 285 (class 1259 OID 78556)
-- Name: payment_inv_items_payment_inv_item_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.payment_inv_items_payment_inv_item_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.payment_inv_items_payment_inv_item_id_seq OWNER TO postgres;

--
-- TOC entry 3691 (class 0 OID 0)
-- Dependencies: 285
-- Name: payment_inv_items_payment_inv_item_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.payment_inv_items_payment_inv_item_id_seq OWNED BY app.payment_inv_items.payment_inv_item_id;


--
-- TOC entry 286 (class 1259 OID 78558)
-- Name: payment_replacement_items; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.payment_replacement_items (
    payment_replace_item_id integer NOT NULL,
    payment_id integer NOT NULL,
    student_fee_item_id integer,
    amount numeric NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    modified_date timestamp without time zone,
    modified_by integer
);


ALTER TABLE app.payment_replacement_items OWNER TO postgres;

--
-- TOC entry 287 (class 1259 OID 78565)
-- Name: payment_replacement_items_payment_replace_item_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.payment_replacement_items_payment_replace_item_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.payment_replacement_items_payment_replace_item_id_seq OWNER TO postgres;

--
-- TOC entry 3692 (class 0 OID 0)
-- Dependencies: 287
-- Name: payment_replacement_items_payment_replace_item_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.payment_replacement_items_payment_replace_item_id_seq OWNED BY app.payment_replacement_items.payment_replace_item_id;


--
-- TOC entry 288 (class 1259 OID 78567)
-- Name: payments; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.payments (
    payment_id integer NOT NULL,
    student_id integer NOT NULL,
    payment_date date NOT NULL,
    amount numeric NOT NULL,
    payment_method character varying NOT NULL,
    slip_cheque_no character varying,
    replacement_payment boolean DEFAULT false NOT NULL,
    reversed boolean DEFAULT false NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    reversed_date timestamp without time zone,
    reversed_by integer,
    inv_id integer,
    modified_date timestamp without time zone,
    modified_by integer,
    custom_receipt_no character varying,
    payment_bank character varying,
    banking_date date
);


ALTER TABLE app.payments OWNER TO postgres;

--
-- TOC entry 3693 (class 0 OID 0)
-- Dependencies: 288
-- Name: COLUMN payments.payment_method; Type: COMMENT; Schema: app; Owner: postgres
--

COMMENT ON COLUMN app.payments.payment_method IS 'Cash or Cheque';


--
-- TOC entry 289 (class 1259 OID 78576)
-- Name: payments_payment_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.payments_payment_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.payments_payment_id_seq OWNER TO postgres;

--
-- TOC entry 3694 (class 0 OID 0)
-- Dependencies: 289
-- Name: payments_payment_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.payments_payment_id_seq OWNED BY app.payments.payment_id;


--
-- TOC entry 290 (class 1259 OID 78578)
-- Name: previous_term; Type: VIEW; Schema: app; Owner: postgres
--

CREATE VIEW app.previous_term AS
 SELECT terms.term_id,
    terms.term_name,
    terms.start_date,
    terms.end_date,
    terms.creation_date,
    terms.created_by,
    terms.term_number
   FROM app.terms
  WHERE (terms.start_date < ( SELECT current_term.start_date
           FROM app.current_term))
  ORDER BY terms.start_date DESC
 LIMIT 1;


ALTER TABLE app.previous_term OWNER TO postgres;

--
-- TOC entry 291 (class 1259 OID 78582)
-- Name: report_cards; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.report_cards (
    report_card_id integer NOT NULL,
    student_id integer,
    term_id integer,
    class_id integer,
    report_data text,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    modified_date timestamp without time zone,
    modified_by integer,
    report_card_type character varying NOT NULL,
    teacher_id integer,
    published boolean DEFAULT false NOT NULL
);


ALTER TABLE app.report_cards OWNER TO postgres;

--
-- TOC entry 292 (class 1259 OID 78590)
-- Name: report_cards_report_card_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.report_cards_report_card_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.report_cards_report_card_id_seq OWNER TO postgres;

--
-- TOC entry 3695 (class 0 OID 0)
-- Dependencies: 292
-- Name: report_cards_report_card_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.report_cards_report_card_id_seq OWNED BY app.report_cards.report_card_id;


--
-- TOC entry 293 (class 1259 OID 78592)
-- Name: schoolbus_bus_trips; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.schoolbus_bus_trips (
    bus_trip_id integer NOT NULL,
    schoolbus_trip_id integer,
    bus_id integer,
    class_cats character varying,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    modified_date timestamp without time zone DEFAULT now()
);


ALTER TABLE app.schoolbus_bus_trips OWNER TO postgres;

--
-- TOC entry 294 (class 1259 OID 78600)
-- Name: schoolbus_bus_trips_bus_trip_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.schoolbus_bus_trips_bus_trip_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.schoolbus_bus_trips_bus_trip_id_seq OWNER TO postgres;

--
-- TOC entry 3696 (class 0 OID 0)
-- Dependencies: 294
-- Name: schoolbus_bus_trips_bus_trip_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.schoolbus_bus_trips_bus_trip_id_seq OWNED BY app.schoolbus_bus_trips.bus_trip_id;


--
-- TOC entry 295 (class 1259 OID 78602)
-- Name: schoolbus_history; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.schoolbus_history (
    schoolbus_history_id integer NOT NULL,
    bus_id integer,
    bus_type character varying NOT NULL,
    bus_registration character varying NOT NULL,
    route_id integer,
    bus_driver integer,
    bus_guide integer,
    gps character varying,
    gps_time character varying,
    gps_order integer,
    activity character varying,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    student_id integer
);


ALTER TABLE app.schoolbus_history OWNER TO postgres;

--
-- TOC entry 296 (class 1259 OID 78609)
-- Name: schoolbus_history_schoolbus_history_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.schoolbus_history_schoolbus_history_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.schoolbus_history_schoolbus_history_id_seq OWNER TO postgres;

--
-- TOC entry 3697 (class 0 OID 0)
-- Dependencies: 296
-- Name: schoolbus_history_schoolbus_history_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.schoolbus_history_schoolbus_history_id_seq OWNED BY app.schoolbus_history.schoolbus_history_id;


--
-- TOC entry 297 (class 1259 OID 78611)
-- Name: schoolbus_trips; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.schoolbus_trips (
    schoolbus_trip_id integer NOT NULL,
    trip_name character varying,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    modified_date timestamp without time zone DEFAULT now(),
    class_cats character varying
);


ALTER TABLE app.schoolbus_trips OWNER TO postgres;

--
-- TOC entry 298 (class 1259 OID 78619)
-- Name: schoolbus_trips_schoolbus_trip_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.schoolbus_trips_schoolbus_trip_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.schoolbus_trips_schoolbus_trip_id_seq OWNER TO postgres;

--
-- TOC entry 3698 (class 0 OID 0)
-- Dependencies: 298
-- Name: schoolbus_trips_schoolbus_trip_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.schoolbus_trips_schoolbus_trip_id_seq OWNED BY app.schoolbus_trips.schoolbus_trip_id;


--
-- TOC entry 299 (class 1259 OID 78621)
-- Name: settings; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.settings (
    name character varying NOT NULL,
    value character varying
);


ALTER TABLE app.settings OWNER TO postgres;

--
-- TOC entry 300 (class 1259 OID 78627)
-- Name: student_buses; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.student_buses (
    student_bus_id integer NOT NULL,
    student_id integer NOT NULL,
    bus_id integer NOT NULL
);


ALTER TABLE app.student_buses OWNER TO postgres;

--
-- TOC entry 301 (class 1259 OID 78630)
-- Name: student_buses_student_bus_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.student_buses_student_bus_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.student_buses_student_bus_id_seq OWNER TO postgres;

--
-- TOC entry 3699 (class 0 OID 0)
-- Dependencies: 301
-- Name: student_buses_student_bus_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.student_buses_student_bus_id_seq OWNED BY app.student_buses.student_bus_id;


--
-- TOC entry 302 (class 1259 OID 78632)
-- Name: student_class_history; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.student_class_history (
    class_history_id integer NOT NULL,
    student_id integer NOT NULL,
    class_id integer NOT NULL,
    start_date timestamp without time zone DEFAULT now() NOT NULL,
    end_date date,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer
);


ALTER TABLE app.student_class_history OWNER TO postgres;

--
-- TOC entry 303 (class 1259 OID 78637)
-- Name: student_class_history_class_history_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.student_class_history_class_history_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.student_class_history_class_history_id_seq OWNER TO postgres;

--
-- TOC entry 3700 (class 0 OID 0)
-- Dependencies: 303
-- Name: student_class_history_class_history_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.student_class_history_class_history_id_seq OWNED BY app.student_class_history.class_history_id;


--
-- TOC entry 304 (class 1259 OID 78639)
-- Name: students; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.students (
    student_id integer NOT NULL,
    admission_number character varying,
    gender character(1),
    first_name character varying NOT NULL,
    middle_name character varying,
    last_name character varying NOT NULL,
    student_category character varying DEFAULT 'Regular'::character varying NOT NULL,
    nationality character varying,
    student_image character varying,
    active boolean DEFAULT true NOT NULL,
    current_class integer NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    payment_method character varying DEFAULT 'Annually'::character varying NOT NULL,
    admission_date date,
    marial_status_parents character varying,
    adopted boolean DEFAULT false NOT NULL,
    adopted_age character varying,
    marital_separation_age character varying,
    adoption_aware boolean DEFAULT false NOT NULL,
    medical_conditions boolean DEFAULT false NOT NULL,
    hospitalized boolean DEFAULT false NOT NULL,
    current_medical_treatment boolean DEFAULT false NOT NULL,
    hospitalized_description character varying,
    current_medical_treatment_description character varying,
    comments character varying,
    other_medical_conditions boolean DEFAULT false NOT NULL,
    other_medical_conditions_description character varying,
    emergency_name character varying,
    emergency_relationship character varying,
    emergency_telephone character varying,
    dob character varying,
    pick_up_drop_off_individual character varying,
    modified_date timestamp without time zone,
    modified_by integer,
    installment_option_id integer,
    new_student boolean DEFAULT false NOT NULL,
    transport_route_id integer,
    student_type character varying DEFAULT 'Day Scholar'::character varying,
    emergency_telephone_2 integer,
    pick_up_drop_off_individual_phone character varying,
    pick_up_drop_off_individual_img character varying,
    nemis character varying,
    house character varying,
    club character varying,
    movement character varying,
    destination character varying,
    trip_ids character varying,
    transfer_date date
);


ALTER TABLE app.students OWNER TO postgres;

--
-- TOC entry 305 (class 1259 OID 78657)
-- Name: subjects; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.subjects (
    subject_id integer NOT NULL,
    subject_name character varying NOT NULL,
    class_cat_id integer NOT NULL,
    teacher_id integer,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    active boolean DEFAULT true NOT NULL,
    modified_date timestamp without time zone,
    modified_by integer,
    sort_order integer,
    parent_subject_id integer,
    use_for_grading boolean DEFAULT true NOT NULL
);


ALTER TABLE app.subjects OWNER TO postgres;

--
-- TOC entry 306 (class 1259 OID 78666)
-- Name: student_exam_marks; Type: VIEW; Schema: app; Owner: postgres
--

CREATE VIEW app.student_exam_marks AS
 SELECT students.student_id,
    (((((students.first_name)::text || ' '::text) || (COALESCE(students.middle_name, ''::character varying))::text) || ' '::text) || (students.last_name)::text) AS student_name,
    exam_marks.term_id,
    class_subjects.class_id,
    class_subject_exams.exam_type_id,
    exam_types.exam_type,
    subjects.subject_name,
    exam_marks.mark,
    class_subject_exams.class_sub_exam_id,
    class_subject_exams.grade_weight
   FROM ((((((app.class_subjects
     JOIN app.class_subject_exams USING (class_subject_id))
     JOIN app.exam_types USING (exam_type_id))
     JOIN app.subjects USING (subject_id))
     JOIN app.classes USING (class_id))
     JOIN app.students ON ((classes.class_id = students.current_class)))
     LEFT JOIN app.exam_marks ON (((students.student_id = exam_marks.student_id) AND (class_subject_exams.class_sub_exam_id = exam_marks.class_sub_exam_id))));


ALTER TABLE app.student_exam_marks OWNER TO postgres;

--
-- TOC entry 307 (class 1259 OID 78671)
-- Name: student_fee_items; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.student_fee_items (
    student_fee_item_id integer NOT NULL,
    student_id integer NOT NULL,
    fee_item_id integer NOT NULL,
    amount numeric NOT NULL,
    payment_method character varying NOT NULL,
    active boolean DEFAULT true NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    modified_date timestamp without time zone,
    modified_by integer
);


ALTER TABLE app.student_fee_items OWNER TO postgres;

--
-- TOC entry 3701 (class 0 OID 0)
-- Dependencies: 307
-- Name: COLUMN student_fee_items.payment_method; Type: COMMENT; Schema: app; Owner: postgres
--

COMMENT ON COLUMN app.student_fee_items.payment_method IS 'This is an option from the Payment Options setting';


--
-- TOC entry 308 (class 1259 OID 78679)
-- Name: student_fee_items_student_fee_item_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.student_fee_items_student_fee_item_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.student_fee_items_student_fee_item_id_seq OWNER TO postgres;

--
-- TOC entry 3702 (class 0 OID 0)
-- Dependencies: 308
-- Name: student_fee_items_student_fee_item_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.student_fee_items_student_fee_item_id_seq OWNED BY app.student_fee_items.student_fee_item_id;


--
-- TOC entry 309 (class 1259 OID 78681)
-- Name: student_guardians; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.student_guardians (
    student_guardian_id integer NOT NULL,
    student_id integer NOT NULL,
    guardian_id integer NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    active boolean DEFAULT true NOT NULL,
    modified_date timestamp without time zone,
    modified_by integer,
    relationship character varying
);


ALTER TABLE app.student_guardians OWNER TO postgres;

--
-- TOC entry 310 (class 1259 OID 78689)
-- Name: student_guardians_student_guardian_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.student_guardians_student_guardian_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.student_guardians_student_guardian_id_seq OWNER TO postgres;

--
-- TOC entry 3703 (class 0 OID 0)
-- Dependencies: 310
-- Name: student_guardians_student_guardian_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.student_guardians_student_guardian_id_seq OWNED BY app.student_guardians.student_guardian_id;


--
-- TOC entry 311 (class 1259 OID 78691)
-- Name: student_medical_history; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.student_medical_history (
    medical_id integer NOT NULL,
    student_id integer,
    illness_condition character varying,
    age character varying,
    comments character varying,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    modified_date timestamp without time zone,
    modified_by integer
);


ALTER TABLE app.student_medical_history OWNER TO postgres;

--
-- TOC entry 312 (class 1259 OID 78698)
-- Name: student_medical_history_medical_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.student_medical_history_medical_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.student_medical_history_medical_id_seq OWNER TO postgres;

--
-- TOC entry 3704 (class 0 OID 0)
-- Dependencies: 312
-- Name: student_medical_history_medical_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.student_medical_history_medical_id_seq OWNED BY app.student_medical_history.medical_id;


--
-- TOC entry 313 (class 1259 OID 78700)
-- Name: students_student_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.students_student_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.students_student_id_seq OWNER TO postgres;

--
-- TOC entry 3705 (class 0 OID 0)
-- Dependencies: 313
-- Name: students_student_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.students_student_id_seq OWNED BY app.students.student_id;


--
-- TOC entry 314 (class 1259 OID 78702)
-- Name: subjects_subject_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.subjects_subject_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.subjects_subject_id_seq OWNER TO postgres;

--
-- TOC entry 3706 (class 0 OID 0)
-- Dependencies: 314
-- Name: subjects_subject_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.subjects_subject_id_seq OWNED BY app.subjects.subject_id;


--
-- TOC entry 315 (class 1259 OID 78704)
-- Name: teacher_timetables; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.teacher_timetables (
    teacher_timetable_id integer NOT NULL,
    teacher_id integer NOT NULL,
    class_id integer NOT NULL,
    term_id integer NOT NULL,
    subject_name character varying NOT NULL,
    year character varying,
    month character varying,
    day character varying,
    start_hour character varying NOT NULL,
    start_minutes character varying NOT NULL,
    end_hour character varying NOT NULL,
    end_minutes character varying NOT NULL,
    color character varying,
    creation_date timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE app.teacher_timetables OWNER TO postgres;

--
-- TOC entry 316 (class 1259 OID 78711)
-- Name: teacher_timetables_teacher_timetable_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.teacher_timetables_teacher_timetable_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.teacher_timetables_teacher_timetable_id_seq OWNER TO postgres;

--
-- TOC entry 3707 (class 0 OID 0)
-- Dependencies: 316
-- Name: teacher_timetables_teacher_timetable_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.teacher_timetables_teacher_timetable_id_seq OWNED BY app.teacher_timetables.teacher_timetable_id;


--
-- TOC entry 317 (class 1259 OID 78713)
-- Name: term_after_next; Type: VIEW; Schema: app; Owner: postgres
--

CREATE VIEW app.term_after_next AS
 SELECT terms.term_id,
    terms.term_name,
    terms.start_date,
    terms.end_date,
    terms.creation_date,
    terms.created_by,
    terms.term_number
   FROM app.terms
  WHERE ((now())::date < terms.start_date)
  ORDER BY terms.start_date
 OFFSET 1
 LIMIT 1;


ALTER TABLE app.term_after_next OWNER TO postgres;

--
-- TOC entry 318 (class 1259 OID 78717)
-- Name: terms_term_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.terms_term_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.terms_term_id_seq OWNER TO postgres;

--
-- TOC entry 3708 (class 0 OID 0)
-- Dependencies: 318
-- Name: terms_term_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.terms_term_id_seq OWNED BY app.terms.term_id;


--
-- TOC entry 319 (class 1259 OID 78719)
-- Name: transport_routes; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.transport_routes (
    transport_id integer NOT NULL,
    route character varying NOT NULL,
    amount numeric NOT NULL,
    active boolean DEFAULT true NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    modified_date timestamp without time zone,
    modified_by integer
);


ALTER TABLE app.transport_routes OWNER TO postgres;

--
-- TOC entry 320 (class 1259 OID 78727)
-- Name: transport_routes_transport_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.transport_routes_transport_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.transport_routes_transport_id_seq OWNER TO postgres;

--
-- TOC entry 3709 (class 0 OID 0)
-- Dependencies: 320
-- Name: transport_routes_transport_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.transport_routes_transport_id_seq OWNED BY app.transport_routes.transport_id;


--
-- TOC entry 321 (class 1259 OID 78729)
-- Name: user_permissions; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.user_permissions (
    perm_id integer NOT NULL,
    user_type character varying NOT NULL,
    permissions text NOT NULL
);


ALTER TABLE app.user_permissions OWNER TO postgres;

--
-- TOC entry 322 (class 1259 OID 78735)
-- Name: user_permissions_perm_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.user_permissions_perm_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.user_permissions_perm_id_seq OWNER TO postgres;

--
-- TOC entry 3710 (class 0 OID 0)
-- Dependencies: 322
-- Name: user_permissions_perm_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.user_permissions_perm_id_seq OWNED BY app.user_permissions.perm_id;


--
-- TOC entry 323 (class 1259 OID 78737)
-- Name: users; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.users (
    user_id integer NOT NULL,
    username character varying NOT NULL,
    password character varying NOT NULL,
    active boolean DEFAULT true NOT NULL,
    first_name character varying NOT NULL,
    middle_name character varying,
    last_name character varying NOT NULL,
    email character varying,
    user_type character varying NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    modified_date timestamp without time zone,
    modified_by integer
);


ALTER TABLE app.users OWNER TO postgres;

--
-- TOC entry 324 (class 1259 OID 78745)
-- Name: user_user_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.user_user_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.user_user_id_seq OWNER TO postgres;

--
-- TOC entry 3711 (class 0 OID 0)
-- Dependencies: 324
-- Name: user_user_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.user_user_id_seq OWNED BY app.users.user_id;


--
-- TOC entry 3160 (class 2604 OID 78747)
-- Name: blog_post_statuses post_status_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.blog_post_statuses ALTER COLUMN post_status_id SET DEFAULT nextval('app.blog_post_statuses_post_status_id_seq'::regclass);


--
-- TOC entry 3161 (class 2604 OID 78748)
-- Name: blog_post_types post_type_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.blog_post_types ALTER COLUMN post_type_id SET DEFAULT nextval('app.blog_post_types_post_type_id_seq'::regclass);


--
-- TOC entry 3163 (class 2604 OID 78749)
-- Name: blog_posts post_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.blog_posts ALTER COLUMN post_id SET DEFAULT nextval('app.blog_posts_post_id_seq'::regclass);


--
-- TOC entry 3164 (class 2604 OID 78750)
-- Name: blogs blog_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.blogs ALTER COLUMN blog_id SET DEFAULT nextval('app.blogs_blog_id_seq'::regclass);


--
-- TOC entry 3167 (class 2604 OID 78751)
-- Name: buses bus_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.buses ALTER COLUMN bus_id SET DEFAULT nextval('app.buses_bus_id_seq'::regclass);


--
-- TOC entry 3170 (class 2604 OID 78752)
-- Name: class_cats class_cat_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.class_cats ALTER COLUMN class_cat_id SET DEFAULT nextval('app.class_cats_class_cat_id_seq'::regclass);


--
-- TOC entry 3173 (class 2604 OID 78753)
-- Name: class_subject_exams class_sub_exam_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.class_subject_exams ALTER COLUMN class_sub_exam_id SET DEFAULT nextval('app.class_subject_exams_class_sub_exam_id_seq'::regclass);


--
-- TOC entry 3176 (class 2604 OID 78754)
-- Name: class_subjects class_subject_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.class_subjects ALTER COLUMN class_subject_id SET DEFAULT nextval('app.class_subjects_class_subject_id_seq'::regclass);


--
-- TOC entry 3178 (class 2604 OID 78755)
-- Name: class_timetables class_timetable_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.class_timetables ALTER COLUMN class_timetable_id SET DEFAULT nextval('app.class_timetables_class_timetable_id_seq'::regclass);


--
-- TOC entry 3181 (class 2604 OID 78756)
-- Name: classes class_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.classes ALTER COLUMN class_id SET DEFAULT nextval('app.classes_class_id_seq'::regclass);


--
-- TOC entry 3182 (class 2604 OID 78757)
-- Name: communication_attachments attachment_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communication_attachments ALTER COLUMN attachment_id SET DEFAULT nextval('app.communication_attachments_attachment_id_seq'::regclass);


--
-- TOC entry 3183 (class 2604 OID 78758)
-- Name: communication_audience audience_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communication_audience ALTER COLUMN audience_id SET DEFAULT nextval('app.communication_audience_audience_id_seq'::regclass);


--
-- TOC entry 3186 (class 2604 OID 78759)
-- Name: communication_emails email_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communication_emails ALTER COLUMN email_id SET DEFAULT nextval('app.communication_emails_email_id_seq'::regclass);


--
-- TOC entry 3189 (class 2604 OID 78760)
-- Name: communication_feedback com_feedback_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communication_feedback ALTER COLUMN com_feedback_id SET DEFAULT nextval('app.communication_feedback_com_feedback_id_seq'::regclass);


--
-- TOC entry 3192 (class 2604 OID 78761)
-- Name: communication_sms sms_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communication_sms ALTER COLUMN sms_id SET DEFAULT nextval('app.communication_sms_sms_id_seq'::regclass);


--
-- TOC entry 3193 (class 2604 OID 78762)
-- Name: communication_types com_type_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communication_types ALTER COLUMN com_type_id SET DEFAULT nextval('app.communication_types_com_type_id_seq'::regclass);


--
-- TOC entry 3196 (class 2604 OID 78763)
-- Name: communications com_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communications ALTER COLUMN com_id SET DEFAULT nextval('app.communications_com_id_seq'::regclass);


--
-- TOC entry 3197 (class 2604 OID 78764)
-- Name: countries countries_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.countries ALTER COLUMN countries_id SET DEFAULT nextval('app.countries_countries_id_seq'::regclass);


--
-- TOC entry 3200 (class 2604 OID 78765)
-- Name: credits credit_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.credits ALTER COLUMN credit_id SET DEFAULT nextval('app.credits_credit_id_seq'::regclass);


--
-- TOC entry 3205 (class 2604 OID 78766)
-- Name: departments dept_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.departments ALTER COLUMN dept_id SET DEFAULT nextval('app.departments_dept_id_seq'::regclass);


--
-- TOC entry 3208 (class 2604 OID 78767)
-- Name: employee_cats emp_cat_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.employee_cats ALTER COLUMN emp_cat_id SET DEFAULT nextval('app.employee_cats_emp_cat_id_seq'::regclass);


--
-- TOC entry 3211 (class 2604 OID 78768)
-- Name: employees emp_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.employees ALTER COLUMN emp_id SET DEFAULT nextval('app.employees_emp_id_seq'::regclass);


--
-- TOC entry 3213 (class 2604 OID 78769)
-- Name: exam_marks exam_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.exam_marks ALTER COLUMN exam_id SET DEFAULT nextval('app.exam_marks_exam_id_seq'::regclass);


--
-- TOC entry 3216 (class 2604 OID 78770)
-- Name: exam_types exam_type_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.exam_types ALTER COLUMN exam_type_id SET DEFAULT nextval('app.exam_types_exam_type_id_seq'::regclass);


--
-- TOC entry 3219 (class 2604 OID 78771)
-- Name: fee_item_uniforms uniform_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.fee_item_uniforms ALTER COLUMN uniform_id SET DEFAULT nextval('app.fee_item_uniforms_uniform_id_seq'::regclass);


--
-- TOC entry 3225 (class 2604 OID 78772)
-- Name: fee_items fee_item_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.fee_items ALTER COLUMN fee_item_id SET DEFAULT nextval('app.fee_items_fee_item_id_seq'::regclass);


--
-- TOC entry 3226 (class 2604 OID 78773)
-- Name: grading grade_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.grading ALTER COLUMN grade_id SET DEFAULT nextval('app.grading_grade_id_seq'::regclass);


--
-- TOC entry 3227 (class 2604 OID 78774)
-- Name: grading2 grade2_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.grading2 ALTER COLUMN grade2_id SET DEFAULT nextval('app.grading2_grade2_id_seq'::regclass);


--
-- TOC entry 3230 (class 2604 OID 78775)
-- Name: guardians guardian_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.guardians ALTER COLUMN guardian_id SET DEFAULT nextval('app.guardians_guardian_id_seq'::regclass);


--
-- TOC entry 3232 (class 2604 OID 78776)
-- Name: homework homework_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.homework ALTER COLUMN homework_id SET DEFAULT nextval('app.homework_homework_id_seq'::regclass);


--
-- TOC entry 3234 (class 2604 OID 78777)
-- Name: installment_options installment_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.installment_options ALTER COLUMN installment_id SET DEFAULT nextval('app.installment_options_installment_id_seq'::regclass);


--
-- TOC entry 3236 (class 2604 OID 78778)
-- Name: invoice_line_items inv_item_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.invoice_line_items ALTER COLUMN inv_item_id SET DEFAULT nextval('app.invoice_line_items_inv_item_id_seq'::regclass);


--
-- TOC entry 3240 (class 2604 OID 78779)
-- Name: invoices inv_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.invoices ALTER COLUMN inv_id SET DEFAULT nextval('app.invoices_inv_id_seq'::regclass);


--
-- TOC entry 3241 (class 2604 OID 78780)
-- Name: lowersch_reportcards lowersch_reportcards_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.lowersch_reportcards ALTER COLUMN lowersch_reportcards_id SET DEFAULT nextval('app.lowersch_reportcards_lowersch_reportcards_id_seq'::regclass);


--
-- TOC entry 3242 (class 2604 OID 78781)
-- Name: medical_conditions condition_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.medical_conditions ALTER COLUMN condition_id SET DEFAULT nextval('app.medical_conditions_condition_id_seq'::regclass);


--
-- TOC entry 3244 (class 2604 OID 78782)
-- Name: payment_inv_items payment_inv_item_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.payment_inv_items ALTER COLUMN payment_inv_item_id SET DEFAULT nextval('app.payment_inv_items_payment_inv_item_id_seq'::regclass);


--
-- TOC entry 3246 (class 2604 OID 78783)
-- Name: payment_replacement_items payment_replace_item_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.payment_replacement_items ALTER COLUMN payment_replace_item_id SET DEFAULT nextval('app.payment_replacement_items_payment_replace_item_id_seq'::regclass);


--
-- TOC entry 3250 (class 2604 OID 78784)
-- Name: payments payment_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.payments ALTER COLUMN payment_id SET DEFAULT nextval('app.payments_payment_id_seq'::regclass);


--
-- TOC entry 3253 (class 2604 OID 78785)
-- Name: report_cards report_card_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.report_cards ALTER COLUMN report_card_id SET DEFAULT nextval('app.report_cards_report_card_id_seq'::regclass);


--
-- TOC entry 3256 (class 2604 OID 78786)
-- Name: schoolbus_bus_trips bus_trip_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.schoolbus_bus_trips ALTER COLUMN bus_trip_id SET DEFAULT nextval('app.schoolbus_bus_trips_bus_trip_id_seq'::regclass);


--
-- TOC entry 3258 (class 2604 OID 78787)
-- Name: schoolbus_history schoolbus_history_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.schoolbus_history ALTER COLUMN schoolbus_history_id SET DEFAULT nextval('app.schoolbus_history_schoolbus_history_id_seq'::regclass);


--
-- TOC entry 3261 (class 2604 OID 78788)
-- Name: schoolbus_trips schoolbus_trip_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.schoolbus_trips ALTER COLUMN schoolbus_trip_id SET DEFAULT nextval('app.schoolbus_trips_schoolbus_trip_id_seq'::regclass);


--
-- TOC entry 3262 (class 2604 OID 78789)
-- Name: student_buses student_bus_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.student_buses ALTER COLUMN student_bus_id SET DEFAULT nextval('app.student_buses_student_bus_id_seq'::regclass);


--
-- TOC entry 3265 (class 2604 OID 78790)
-- Name: student_class_history class_history_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.student_class_history ALTER COLUMN class_history_id SET DEFAULT nextval('app.student_class_history_class_history_id_seq'::regclass);


--
-- TOC entry 3285 (class 2604 OID 78791)
-- Name: student_fee_items student_fee_item_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.student_fee_items ALTER COLUMN student_fee_item_id SET DEFAULT nextval('app.student_fee_items_student_fee_item_id_seq'::regclass);


--
-- TOC entry 3288 (class 2604 OID 78792)
-- Name: student_guardians student_guardian_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.student_guardians ALTER COLUMN student_guardian_id SET DEFAULT nextval('app.student_guardians_student_guardian_id_seq'::regclass);


--
-- TOC entry 3290 (class 2604 OID 78793)
-- Name: student_medical_history medical_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.student_medical_history ALTER COLUMN medical_id SET DEFAULT nextval('app.student_medical_history_medical_id_seq'::regclass);


--
-- TOC entry 3278 (class 2604 OID 78794)
-- Name: students student_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.students ALTER COLUMN student_id SET DEFAULT nextval('app.students_student_id_seq'::regclass);


--
-- TOC entry 3282 (class 2604 OID 78795)
-- Name: subjects subject_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.subjects ALTER COLUMN subject_id SET DEFAULT nextval('app.subjects_subject_id_seq'::regclass);


--
-- TOC entry 3292 (class 2604 OID 78796)
-- Name: teacher_timetables teacher_timetable_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.teacher_timetables ALTER COLUMN teacher_timetable_id SET DEFAULT nextval('app.teacher_timetables_teacher_timetable_id_seq'::regclass);


--
-- TOC entry 3202 (class 2604 OID 78797)
-- Name: terms term_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.terms ALTER COLUMN term_id SET DEFAULT nextval('app.terms_term_id_seq'::regclass);


--
-- TOC entry 3295 (class 2604 OID 78798)
-- Name: transport_routes transport_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.transport_routes ALTER COLUMN transport_id SET DEFAULT nextval('app.transport_routes_transport_id_seq'::regclass);


--
-- TOC entry 3296 (class 2604 OID 78799)
-- Name: user_permissions perm_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.user_permissions ALTER COLUMN perm_id SET DEFAULT nextval('app.user_permissions_perm_id_seq'::regclass);


--
-- TOC entry 3299 (class 2604 OID 78800)
-- Name: users user_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.users ALTER COLUMN user_id SET DEFAULT nextval('app.user_user_id_seq'::regclass);


--
-- TOC entry 3307 (class 2606 OID 78943)
-- Name: blogs FK_blog_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.blogs
    ADD CONSTRAINT "FK_blog_id" PRIMARY KEY (blog_id);


--
-- TOC entry 3389 (class 2606 OID 78945)
-- Name: homework FK_homework_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.homework
    ADD CONSTRAINT "FK_homework_id" PRIMARY KEY (homework_id);


--
-- TOC entry 3397 (class 2606 OID 78947)
-- Name: lowersch_reportcards FK_lowersch_reportcards_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.lowersch_reportcards
    ADD CONSTRAINT "FK_lowersch_reportcards_id" PRIMARY KEY (lowersch_reportcards_id);


--
-- TOC entry 3407 (class 2606 OID 78949)
-- Name: report_cards FK_report_card_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.report_cards
    ADD CONSTRAINT "FK_report_card_id" PRIMARY KEY (report_card_id);


--
-- TOC entry 3331 (class 2606 OID 78951)
-- Name: communication_audience PK_audience_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communication_audience
    ADD CONSTRAINT "PK_audience_id" PRIMARY KEY (audience_id);


--
-- TOC entry 3309 (class 2606 OID 78953)
-- Name: buses PK_bus_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.buses
    ADD CONSTRAINT "PK_bus_id" PRIMARY KEY (bus_id);


--
-- TOC entry 3409 (class 2606 OID 78955)
-- Name: schoolbus_bus_trips PK_bus_trip_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.schoolbus_bus_trips
    ADD CONSTRAINT "PK_bus_trip_id" PRIMARY KEY (bus_trip_id);


--
-- TOC entry 3313 (class 2606 OID 78957)
-- Name: class_cats PK_class_cat_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.class_cats
    ADD CONSTRAINT "PK_class_cat_id" PRIMARY KEY (class_cat_id);


--
-- TOC entry 3421 (class 2606 OID 78959)
-- Name: student_class_history PK_class_history_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.student_class_history
    ADD CONSTRAINT "PK_class_history_id" PRIMARY KEY (class_history_id);


--
-- TOC entry 3326 (class 2606 OID 78961)
-- Name: classes PK_class_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.classes
    ADD CONSTRAINT "PK_class_id" PRIMARY KEY (class_id);


--
-- TOC entry 3320 (class 2606 OID 78963)
-- Name: class_subjects PK_class_subject; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.class_subjects
    ADD CONSTRAINT "PK_class_subject" PRIMARY KEY (class_subject_id);


--
-- TOC entry 3316 (class 2606 OID 78965)
-- Name: class_subject_exams PK_class_subject_exam; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.class_subject_exams
    ADD CONSTRAINT "PK_class_subject_exam" PRIMARY KEY (class_sub_exam_id);


--
-- TOC entry 3324 (class 2606 OID 78967)
-- Name: class_timetables PK_class_timetable_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.class_timetables
    ADD CONSTRAINT "PK_class_timetable_id" PRIMARY KEY (class_timetable_id);


--
-- TOC entry 3335 (class 2606 OID 78969)
-- Name: communication_feedback PK_com_feedback_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communication_feedback
    ADD CONSTRAINT "PK_com_feedback_id" PRIMARY KEY (com_feedback_id);


--
-- TOC entry 3341 (class 2606 OID 78971)
-- Name: communications PK_com_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communications
    ADD CONSTRAINT "PK_com_id" PRIMARY KEY (com_id);


--
-- TOC entry 3339 (class 2606 OID 78973)
-- Name: communication_types PK_com_type_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communication_types
    ADD CONSTRAINT "PK_com_type_id" PRIMARY KEY (com_type_id);


--
-- TOC entry 3399 (class 2606 OID 78975)
-- Name: medical_conditions PK_condition_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.medical_conditions
    ADD CONSTRAINT "PK_condition_id" PRIMARY KEY (condition_id);


--
-- TOC entry 3345 (class 2606 OID 78977)
-- Name: credits PK_credit_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.credits
    ADD CONSTRAINT "PK_credit_id" PRIMARY KEY (credit_id);


--
-- TOC entry 3351 (class 2606 OID 78979)
-- Name: departments PK_dept_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.departments
    ADD CONSTRAINT "PK_dept_id" PRIMARY KEY (dept_id);


--
-- TOC entry 3333 (class 2606 OID 78981)
-- Name: communication_emails PK_email_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communication_emails
    ADD CONSTRAINT "PK_email_id" PRIMARY KEY (email_id);


--
-- TOC entry 3355 (class 2606 OID 78983)
-- Name: employee_cats PK_emp_cat_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.employee_cats
    ADD CONSTRAINT "PK_emp_cat_id" PRIMARY KEY (emp_cat_id);


--
-- TOC entry 3359 (class 2606 OID 78985)
-- Name: employees PK_emp_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.employees
    ADD CONSTRAINT "PK_emp_id" PRIMARY KEY (emp_id);


--
-- TOC entry 3363 (class 2606 OID 78987)
-- Name: exam_marks PK_exam_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.exam_marks
    ADD CONSTRAINT "PK_exam_id" PRIMARY KEY (exam_id);


--
-- TOC entry 3367 (class 2606 OID 78989)
-- Name: exam_types PK_exam_type; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.exam_types
    ADD CONSTRAINT "PK_exam_type" PRIMARY KEY (exam_type_id);


--
-- TOC entry 3375 (class 2606 OID 78991)
-- Name: fee_items PK_fee_item_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.fee_items
    ADD CONSTRAINT "PK_fee_item_id" PRIMARY KEY (fee_item_id);


--
-- TOC entry 3381 (class 2606 OID 78993)
-- Name: grading2 PK_grade2_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.grading2
    ADD CONSTRAINT "PK_grade2_id" PRIMARY KEY (grade2_id);


--
-- TOC entry 3377 (class 2606 OID 78995)
-- Name: grading PK_grade_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.grading
    ADD CONSTRAINT "PK_grade_id" PRIMARY KEY (grade_id);


--
-- TOC entry 3385 (class 2606 OID 78997)
-- Name: guardians PK_guardian_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.guardians
    ADD CONSTRAINT "PK_guardian_id" PRIMARY KEY (guardian_id);


--
-- TOC entry 3391 (class 2606 OID 78999)
-- Name: installment_options PK_installment_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.installment_options
    ADD CONSTRAINT "PK_installment_id" PRIMARY KEY (installment_id);


--
-- TOC entry 3395 (class 2606 OID 79001)
-- Name: invoices PK_inv_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.invoices
    ADD CONSTRAINT "PK_inv_id" PRIMARY KEY (inv_id);


--
-- TOC entry 3393 (class 2606 OID 79003)
-- Name: invoice_line_items PK_inv_item_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.invoice_line_items
    ADD CONSTRAINT "PK_inv_item_id" PRIMARY KEY (inv_item_id);


--
-- TOC entry 3437 (class 2606 OID 79005)
-- Name: student_medical_history PK_medical_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.student_medical_history
    ADD CONSTRAINT "PK_medical_id" PRIMARY KEY (medical_id);


--
-- TOC entry 3405 (class 2606 OID 79007)
-- Name: payments PK_payment_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.payments
    ADD CONSTRAINT "PK_payment_id" PRIMARY KEY (payment_id);


--
-- TOC entry 3401 (class 2606 OID 79009)
-- Name: payment_inv_items PK_payment_inv_item_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.payment_inv_items
    ADD CONSTRAINT "PK_payment_inv_item_id" PRIMARY KEY (payment_inv_item_id);


--
-- TOC entry 3403 (class 2606 OID 79011)
-- Name: payment_replacement_items PK_payment_replace_item_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.payment_replacement_items
    ADD CONSTRAINT "PK_payment_replace_item_id" PRIMARY KEY (payment_replace_item_id);


--
-- TOC entry 3445 (class 2606 OID 79013)
-- Name: user_permissions PK_perm_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.user_permissions
    ADD CONSTRAINT "PK_perm_id" PRIMARY KEY (perm_id);


--
-- TOC entry 3305 (class 2606 OID 79015)
-- Name: blog_posts PK_post_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.blog_posts
    ADD CONSTRAINT "PK_post_id" PRIMARY KEY (post_id);


--
-- TOC entry 3301 (class 2606 OID 79017)
-- Name: blog_post_statuses PK_post_status_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.blog_post_statuses
    ADD CONSTRAINT "PK_post_status_id" PRIMARY KEY (post_status_id);


--
-- TOC entry 3303 (class 2606 OID 79019)
-- Name: blog_post_types PK_post_type_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.blog_post_types
    ADD CONSTRAINT "PK_post_type_id" PRIMARY KEY (post_type_id);


--
-- TOC entry 3411 (class 2606 OID 79021)
-- Name: schoolbus_history PK_schoolbus_history_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.schoolbus_history
    ADD CONSTRAINT "PK_schoolbus_history_id" PRIMARY KEY (schoolbus_history_id);


--
-- TOC entry 3413 (class 2606 OID 79023)
-- Name: schoolbus_trips PK_schoolbus_trip_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.schoolbus_trips
    ADD CONSTRAINT "PK_schoolbus_trip_id" PRIMARY KEY (schoolbus_trip_id);


--
-- TOC entry 3415 (class 2606 OID 79025)
-- Name: settings PK_setting_name; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.settings
    ADD CONSTRAINT "PK_setting_name" PRIMARY KEY (name);


--
-- TOC entry 3337 (class 2606 OID 79027)
-- Name: communication_sms PK_sms_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communication_sms
    ADD CONSTRAINT "PK_sms_id" PRIMARY KEY (sms_id);


--
-- TOC entry 3417 (class 2606 OID 79029)
-- Name: student_buses PK_student_bus_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.student_buses
    ADD CONSTRAINT "PK_student_bus_id" PRIMARY KEY (student_bus_id);


--
-- TOC entry 3431 (class 2606 OID 79031)
-- Name: student_fee_items PK_student_fee_item; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.student_fee_items
    ADD CONSTRAINT "PK_student_fee_item" PRIMARY KEY (student_fee_item_id);


--
-- TOC entry 3435 (class 2606 OID 79033)
-- Name: student_guardians PK_student_guardian_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.student_guardians
    ADD CONSTRAINT "PK_student_guardian_id" PRIMARY KEY (student_guardian_id);


--
-- TOC entry 3423 (class 2606 OID 79035)
-- Name: students PK_student_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.students
    ADD CONSTRAINT "PK_student_id" PRIMARY KEY (student_id);


--
-- TOC entry 3427 (class 2606 OID 79037)
-- Name: subjects PK_subject_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.subjects
    ADD CONSTRAINT "PK_subject_id" PRIMARY KEY (subject_id);


--
-- TOC entry 3439 (class 2606 OID 79039)
-- Name: teacher_timetables PK_teacher_timetable_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.teacher_timetables
    ADD CONSTRAINT "PK_teacher_timetable_id" PRIMARY KEY (teacher_timetable_id);


--
-- TOC entry 3347 (class 2606 OID 79041)
-- Name: terms PK_term_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.terms
    ADD CONSTRAINT "PK_term_id" PRIMARY KEY (term_id);


--
-- TOC entry 3441 (class 2606 OID 79043)
-- Name: transport_routes PK_transport_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.transport_routes
    ADD CONSTRAINT "PK_transport_id" PRIMARY KEY (transport_id);


--
-- TOC entry 3371 (class 2606 OID 79045)
-- Name: fee_item_uniforms PK_uniform_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.fee_item_uniforms
    ADD CONSTRAINT "PK_uniform_id" PRIMARY KEY (uniform_id);


--
-- TOC entry 3447 (class 2606 OID 79047)
-- Name: users PK_user_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.users
    ADD CONSTRAINT "PK_user_id" PRIMARY KEY (user_id);


--
-- TOC entry 3357 (class 2606 OID 79049)
-- Name: employee_cats U_active_emp_cat; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.employee_cats
    ADD CONSTRAINT "U_active_emp_cat" UNIQUE (emp_cat_name, active);


--
-- TOC entry 3425 (class 2606 OID 79051)
-- Name: students U_admission_number; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.students
    ADD CONSTRAINT "U_admission_number" UNIQUE (admission_number);


--
-- TOC entry 3311 (class 2606 OID 79053)
-- Name: buses U_bus_registration; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.buses
    ADD CONSTRAINT "U_bus_registration" UNIQUE (bus_registration);


--
-- TOC entry 3322 (class 2606 OID 79055)
-- Name: class_subjects U_class_subject; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.class_subjects
    ADD CONSTRAINT "U_class_subject" UNIQUE (class_id, subject_id);


--
-- TOC entry 3353 (class 2606 OID 79057)
-- Name: departments U_dept_name; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.departments
    ADD CONSTRAINT "U_dept_name" UNIQUE (dept_name);


--
-- TOC entry 3361 (class 2606 OID 79059)
-- Name: employees U_emp_number; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.employees
    ADD CONSTRAINT "U_emp_number" UNIQUE (emp_number);


--
-- TOC entry 3369 (class 2606 OID 79061)
-- Name: exam_types U_exam_type_per_category; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.exam_types
    ADD CONSTRAINT "U_exam_type_per_category" UNIQUE (exam_type, class_cat_id);


--
-- TOC entry 3387 (class 2606 OID 79063)
-- Name: guardians U_id_number; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.guardians
    ADD CONSTRAINT "U_id_number" UNIQUE (id_number);


--
-- TOC entry 3443 (class 2606 OID 79065)
-- Name: transport_routes U_route; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.transport_routes
    ADD CONSTRAINT "U_route" UNIQUE (route);


--
-- TOC entry 3365 (class 2606 OID 79067)
-- Name: exam_marks U_student_exam_mark; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.exam_marks
    ADD CONSTRAINT "U_student_exam_mark" UNIQUE (student_id, class_sub_exam_id, term_id);


--
-- TOC entry 3433 (class 2606 OID 79069)
-- Name: student_fee_items U_student_fee_item; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.student_fee_items
    ADD CONSTRAINT "U_student_fee_item" UNIQUE (student_id, fee_item_id);


--
-- TOC entry 3419 (class 2606 OID 79071)
-- Name: student_buses U_student_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.student_buses
    ADD CONSTRAINT "U_student_id" UNIQUE (student_id);


--
-- TOC entry 3429 (class 2606 OID 79073)
-- Name: subjects U_subject_by_class_cat; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.subjects
    ADD CONSTRAINT "U_subject_by_class_cat" UNIQUE (subject_name, class_cat_id);


--
-- TOC entry 3318 (class 2606 OID 79075)
-- Name: class_subject_exams U_subject_exam; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.class_subject_exams
    ADD CONSTRAINT "U_subject_exam" UNIQUE (class_subject_id, exam_type_id);


--
-- TOC entry 3349 (class 2606 OID 79077)
-- Name: terms U_term; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.terms
    ADD CONSTRAINT "U_term" UNIQUE (start_date, end_date);


--
-- TOC entry 3373 (class 2606 OID 79079)
-- Name: fee_item_uniforms U_uniform; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.fee_item_uniforms
    ADD CONSTRAINT "U_uniform" UNIQUE (uniform);


--
-- TOC entry 3449 (class 2606 OID 79081)
-- Name: users U_username; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.users
    ADD CONSTRAINT "U_username" UNIQUE (username);


--
-- TOC entry 3329 (class 2606 OID 79083)
-- Name: communication_attachments attachment_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communication_attachments
    ADD CONSTRAINT attachment_id PRIMARY KEY (attachment_id);


--
-- TOC entry 3343 (class 2606 OID 79085)
-- Name: countries countries_pk; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.countries
    ADD CONSTRAINT countries_pk PRIMARY KEY (countries_id);


--
-- TOC entry 3383 (class 2606 OID 79087)
-- Name: grading2 grading_unique_grade2_contraint; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.grading2
    ADD CONSTRAINT grading_unique_grade2_contraint UNIQUE (grade2);


--
-- TOC entry 3379 (class 2606 OID 79089)
-- Name: grading grading_unique_grade_contraint; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.grading
    ADD CONSTRAINT grading_unique_grade_contraint UNIQUE (grade);


--
-- TOC entry 3314 (class 1259 OID 79090)
-- Name: U_active_class_cat; Type: INDEX; Schema: app; Owner: postgres
--

CREATE UNIQUE INDEX "U_active_class_cat" ON app.class_cats USING btree (class_cat_name) WHERE (active IS TRUE);


--
-- TOC entry 3327 (class 1259 OID 79091)
-- Name: U_active_class_name; Type: INDEX; Schema: app; Owner: postgres
--

CREATE UNIQUE INDEX "U_active_class_name" ON app.classes USING btree (class_name, class_cat_id) WHERE (active IS TRUE);


--
-- TOC entry 3641 (class 2618 OID 78504)
-- Name: invoice_balances _RETURN; Type: RULE; Schema: app; Owner: postgres
--

CREATE OR REPLACE VIEW app.invoice_balances AS
 SELECT invoices.student_id,
    invoices.inv_id,
    invoices.inv_date,
    max(invoices.total_amount) AS total_due,
    COALESCE(sum(payments.amount), (0)::numeric) AS total_paid,
    (COALESCE(sum(payments.amount), (0)::numeric) - max(invoices.total_amount)) AS balance,
    invoices.due_date,
        CASE
            WHEN ((invoices.due_date < (now())::date) AND ((COALESCE(sum(payments.amount), (0)::numeric) - max(invoices.total_amount)) < (0)::numeric)) THEN true
            ELSE false
        END AS past_due,
    invoices.canceled
   FROM (app.invoices
     LEFT JOIN app.payments ON (((invoices.inv_id = payments.inv_id) AND (payments.reversed IS FALSE))))
  GROUP BY invoices.student_id, invoices.inv_id;


--
-- TOC entry 3642 (class 2618 OID 78508)
-- Name: invoice_balances2 _RETURN; Type: RULE; Schema: app; Owner: postgres
--

CREATE OR REPLACE VIEW app.invoice_balances2 AS
 SELECT invoices.student_id,
    invoices.inv_id,
    invoices.inv_date,
    max(invoices.total_amount) AS total_due,
    COALESCE(sum(payment_inv_items.amount), (0)::numeric) AS total_paid,
    (COALESCE(sum(payment_inv_items.amount), (0)::numeric) - max(invoices.total_amount)) AS balance,
    invoices.due_date,
        CASE
            WHEN ((invoices.due_date < (now())::date) AND ((COALESCE(sum(payment_inv_items.amount), (0)::numeric) - max(invoices.total_amount)) < (0)::numeric)) THEN true
            ELSE false
        END AS past_due,
    invoices.canceled,
    invoices.term_id
   FROM (app.invoices
     JOIN (app.invoice_line_items
     LEFT JOIN (app.payment_inv_items
     JOIN app.payments ON (((payment_inv_items.payment_id = payments.payment_id) AND (payments.reversed IS FALSE)))) ON ((invoice_line_items.inv_item_id = payment_inv_items.inv_item_id))) ON ((invoices.inv_id = invoice_line_items.inv_id)))
  GROUP BY invoices.student_id, invoices.inv_id;


--
-- TOC entry 3470 (class 2606 OID 79094)
-- Name: communications FK_audience_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communications
    ADD CONSTRAINT "FK_audience_id" FOREIGN KEY (audience_id) REFERENCES app.communication_audience(audience_id);


--
-- TOC entry 3454 (class 2606 OID 79099)
-- Name: blogs FK_blog_class; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.blogs
    ADD CONSTRAINT "FK_blog_class" FOREIGN KEY (class_id) REFERENCES app.classes(class_id);


--
-- TOC entry 3455 (class 2606 OID 79104)
-- Name: blogs FK_blog_teacher; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.blogs
    ADD CONSTRAINT "FK_blog_teacher" FOREIGN KEY (teacher_id) REFERENCES app.employees(emp_id);


--
-- TOC entry 3501 (class 2606 OID 79109)
-- Name: student_buses FK_bus_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.student_buses
    ADD CONSTRAINT "FK_bus_id" FOREIGN KEY (bus_id) REFERENCES app.buses(bus_id);


--
-- TOC entry 3462 (class 2606 OID 79114)
-- Name: classes FK_class_cat_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.classes
    ADD CONSTRAINT "FK_class_cat_id" FOREIGN KEY (class_cat_id) REFERENCES app.class_cats(class_cat_id);


--
-- TOC entry 3507 (class 2606 OID 79119)
-- Name: subjects FK_class_cat_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.subjects
    ADD CONSTRAINT "FK_class_cat_id" FOREIGN KEY (class_cat_id) REFERENCES app.class_cats(class_cat_id);


--
-- TOC entry 3503 (class 2606 OID 79124)
-- Name: student_class_history FK_class_history_class; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.student_class_history
    ADD CONSTRAINT "FK_class_history_class" FOREIGN KEY (class_id) REFERENCES app.classes(class_id);


--
-- TOC entry 3504 (class 2606 OID 79129)
-- Name: student_class_history FK_class_history_student; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.student_class_history
    ADD CONSTRAINT "FK_class_history_student" FOREIGN KEY (student_id) REFERENCES app.students(student_id);


--
-- TOC entry 3458 (class 2606 OID 79134)
-- Name: class_subject_exams FK_class_subect_exam_type; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.class_subject_exams
    ADD CONSTRAINT "FK_class_subect_exam_type" FOREIGN KEY (exam_type_id) REFERENCES app.exam_types(exam_type_id);


--
-- TOC entry 3459 (class 2606 OID 79139)
-- Name: class_subject_exams FK_class_subject; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.class_subject_exams
    ADD CONSTRAINT "FK_class_subject" FOREIGN KEY (class_subject_id) REFERENCES app.class_subjects(class_subject_id);


--
-- TOC entry 3460 (class 2606 OID 79144)
-- Name: class_subjects FK_class_subject_class; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.class_subjects
    ADD CONSTRAINT "FK_class_subject_class" FOREIGN KEY (class_id) REFERENCES app.classes(class_id);


--
-- TOC entry 3481 (class 2606 OID 79149)
-- Name: exam_marks FK_class_subject_exam; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.exam_marks
    ADD CONSTRAINT "FK_class_subject_exam" FOREIGN KEY (class_sub_exam_id) REFERENCES app.class_subject_exams(class_sub_exam_id);


--
-- TOC entry 3461 (class 2606 OID 79154)
-- Name: class_subjects FK_class_subject_subject; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.class_subjects
    ADD CONSTRAINT "FK_class_subject_subject" FOREIGN KEY (subject_id) REFERENCES app.subjects(subject_id);


--
-- TOC entry 3463 (class 2606 OID 79159)
-- Name: classes FK_class_teacher; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.classes
    ADD CONSTRAINT "FK_class_teacher" FOREIGN KEY (teacher_id) REFERENCES app.employees(emp_id);


--
-- TOC entry 3466 (class 2606 OID 79164)
-- Name: communication_feedback FK_com_class_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communication_feedback
    ADD CONSTRAINT "FK_com_class_id" FOREIGN KEY (class_id) REFERENCES app.classes(class_id);


--
-- TOC entry 3471 (class 2606 OID 79169)
-- Name: communications FK_com_class_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communications
    ADD CONSTRAINT "FK_com_class_id" FOREIGN KEY (class_id) REFERENCES app.classes(class_id);


--
-- TOC entry 3467 (class 2606 OID 79174)
-- Name: communication_feedback FK_com_guardian_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communication_feedback
    ADD CONSTRAINT "FK_com_guardian_id" FOREIGN KEY (guardian_id) REFERENCES app.guardians(guardian_id);


--
-- TOC entry 3472 (class 2606 OID 79179)
-- Name: communications FK_com_guardian_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communications
    ADD CONSTRAINT "FK_com_guardian_id" FOREIGN KEY (guardian_id) REFERENCES app.guardians(guardian_id);


--
-- TOC entry 3473 (class 2606 OID 79184)
-- Name: communications FK_com_message_from; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communications
    ADD CONSTRAINT "FK_com_message_from" FOREIGN KEY (message_from) REFERENCES app.employees(emp_id);


--
-- TOC entry 3468 (class 2606 OID 79189)
-- Name: communication_feedback FK_com_student_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communication_feedback
    ADD CONSTRAINT "FK_com_student_id" FOREIGN KEY (student_id) REFERENCES app.students(student_id);


--
-- TOC entry 3474 (class 2606 OID 79194)
-- Name: communications FK_com_student_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communications
    ADD CONSTRAINT "FK_com_student_id" FOREIGN KEY (student_id) REFERENCES app.students(student_id);


--
-- TOC entry 3475 (class 2606 OID 79199)
-- Name: communications FK_com_type_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communications
    ADD CONSTRAINT "FK_com_type_id" FOREIGN KEY (com_type_id) REFERENCES app.communication_types(com_type_id);


--
-- TOC entry 3465 (class 2606 OID 79204)
-- Name: communication_emails FK_comm_email_comm; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communication_emails
    ADD CONSTRAINT "FK_comm_email_comm" FOREIGN KEY (com_id) REFERENCES app.communications(com_id);


--
-- TOC entry 3469 (class 2606 OID 79209)
-- Name: communication_sms FK_comm_sms_comm; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communication_sms
    ADD CONSTRAINT "FK_comm_sms_comm" FOREIGN KEY (com_id) REFERENCES app.communications(com_id);


--
-- TOC entry 3477 (class 2606 OID 79214)
-- Name: credits FK_credit_payment; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.credits
    ADD CONSTRAINT "FK_credit_payment" FOREIGN KEY (payment_id) REFERENCES app.payments(payment_id);


--
-- TOC entry 3478 (class 2606 OID 79219)
-- Name: credits FK_credit_student; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.credits
    ADD CONSTRAINT "FK_credit_student" FOREIGN KEY (student_id) REFERENCES app.students(student_id);


--
-- TOC entry 3476 (class 2606 OID 79224)
-- Name: communications FK_email_post_status; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communications
    ADD CONSTRAINT "FK_email_post_status" FOREIGN KEY (post_status_id) REFERENCES app.blog_post_statuses(post_status_id);


--
-- TOC entry 3479 (class 2606 OID 79229)
-- Name: employees FK_emp_cat_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.employees
    ADD CONSTRAINT "FK_emp_cat_id" FOREIGN KEY (emp_cat_id) REFERENCES app.employee_cats(emp_cat_id);


--
-- TOC entry 3480 (class 2606 OID 79234)
-- Name: employees FK_emp_dept_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.employees
    ADD CONSTRAINT "FK_emp_dept_id" FOREIGN KEY (dept_id) REFERENCES app.departments(dept_id);


--
-- TOC entry 3482 (class 2606 OID 79239)
-- Name: exam_marks FK_exam_student; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.exam_marks
    ADD CONSTRAINT "FK_exam_student" FOREIGN KEY (student_id) REFERENCES app.students(student_id);


--
-- TOC entry 3483 (class 2606 OID 79244)
-- Name: exam_marks FK_exam_term; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.exam_marks
    ADD CONSTRAINT "FK_exam_term" FOREIGN KEY (term_id) REFERENCES app.terms(term_id);


--
-- TOC entry 3484 (class 2606 OID 79249)
-- Name: exam_types FK_exam_type_class_cat; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.exam_types
    ADD CONSTRAINT "FK_exam_type_class_cat" FOREIGN KEY (class_cat_id) REFERENCES app.class_cats(class_cat_id);


--
-- TOC entry 3485 (class 2606 OID 79254)
-- Name: homework FK_homework_class_subject; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.homework
    ADD CONSTRAINT "FK_homework_class_subject" FOREIGN KEY (class_subject_id) REFERENCES app.class_subjects(class_subject_id);


--
-- TOC entry 3486 (class 2606 OID 79259)
-- Name: homework FK_homework_post_status; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.homework
    ADD CONSTRAINT "FK_homework_post_status" FOREIGN KEY (post_status_id) REFERENCES app.blog_post_statuses(post_status_id);


--
-- TOC entry 3505 (class 2606 OID 79264)
-- Name: students FK_installment_option; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.students
    ADD CONSTRAINT "FK_installment_option" FOREIGN KEY (installment_option_id) REFERENCES app.installment_options(installment_id);


--
-- TOC entry 3487 (class 2606 OID 79269)
-- Name: invoice_line_items FK_inv_item_fee_item; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.invoice_line_items
    ADD CONSTRAINT "FK_inv_item_fee_item" FOREIGN KEY (student_fee_item_id) REFERENCES app.student_fee_items(student_fee_item_id);


--
-- TOC entry 3488 (class 2606 OID 79274)
-- Name: invoice_line_items FK_inv_item_invoice; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.invoice_line_items
    ADD CONSTRAINT "FK_inv_item_invoice" FOREIGN KEY (inv_id) REFERENCES app.invoices(inv_id);


--
-- TOC entry 3489 (class 2606 OID 79279)
-- Name: invoices FK_invoice_student; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.invoices
    ADD CONSTRAINT "FK_invoice_student" FOREIGN KEY (student_id) REFERENCES app.students(student_id);


--
-- TOC entry 3490 (class 2606 OID 79284)
-- Name: payment_inv_items FK_payment_fee_item_payment; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.payment_inv_items
    ADD CONSTRAINT "FK_payment_fee_item_payment" FOREIGN KEY (payment_id) REFERENCES app.payments(payment_id);


--
-- TOC entry 3491 (class 2606 OID 79289)
-- Name: payment_inv_items FK_payment_inv; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.payment_inv_items
    ADD CONSTRAINT "FK_payment_inv" FOREIGN KEY (inv_id) REFERENCES app.invoices(inv_id);


--
-- TOC entry 3492 (class 2606 OID 79294)
-- Name: payment_inv_items FK_payment_inv_item; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.payment_inv_items
    ADD CONSTRAINT "FK_payment_inv_item" FOREIGN KEY (inv_item_id) REFERENCES app.invoice_line_items(inv_item_id);


--
-- TOC entry 3493 (class 2606 OID 79299)
-- Name: payment_replacement_items FK_payment_item; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.payment_replacement_items
    ADD CONSTRAINT "FK_payment_item" FOREIGN KEY (student_fee_item_id) REFERENCES app.student_fee_items(student_fee_item_id);


--
-- TOC entry 3494 (class 2606 OID 79304)
-- Name: payment_replacement_items FK_payment_replace_item_payment; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.payment_replacement_items
    ADD CONSTRAINT "FK_payment_replace_item_payment" FOREIGN KEY (payment_id) REFERENCES app.payments(payment_id);


--
-- TOC entry 3495 (class 2606 OID 79309)
-- Name: payments FK_payments_student; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.payments
    ADD CONSTRAINT "FK_payments_student" FOREIGN KEY (student_id) REFERENCES app.students(student_id);


--
-- TOC entry 3450 (class 2606 OID 79314)
-- Name: blog_posts FK_post_blog; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.blog_posts
    ADD CONSTRAINT "FK_post_blog" FOREIGN KEY (blog_id) REFERENCES app.blogs(blog_id);


--
-- TOC entry 3451 (class 2606 OID 79319)
-- Name: blog_posts FK_post_status; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.blog_posts
    ADD CONSTRAINT "FK_post_status" FOREIGN KEY (post_status_id) REFERENCES app.blog_post_statuses(post_status_id);


--
-- TOC entry 3452 (class 2606 OID 79324)
-- Name: blog_posts FK_post_type; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.blog_posts
    ADD CONSTRAINT "FK_post_type" FOREIGN KEY (post_type_id) REFERENCES app.blog_post_types(post_type_id);


--
-- TOC entry 3453 (class 2606 OID 79329)
-- Name: blog_posts FK_posted_by; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.blog_posts
    ADD CONSTRAINT "FK_posted_by" FOREIGN KEY (created_by) REFERENCES app.employees(emp_id);


--
-- TOC entry 3496 (class 2606 OID 79334)
-- Name: report_cards FK_report_class; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.report_cards
    ADD CONSTRAINT "FK_report_class" FOREIGN KEY (class_id) REFERENCES app.classes(class_id);


--
-- TOC entry 3497 (class 2606 OID 79339)
-- Name: report_cards FK_report_student; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.report_cards
    ADD CONSTRAINT "FK_report_student" FOREIGN KEY (student_id) REFERENCES app.students(student_id);


--
-- TOC entry 3498 (class 2606 OID 79344)
-- Name: report_cards FK_report_term; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.report_cards
    ADD CONSTRAINT "FK_report_term" FOREIGN KEY (term_id) REFERENCES app.terms(term_id);


--
-- TOC entry 3509 (class 2606 OID 79349)
-- Name: student_fee_items FK_student_fee_items; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.student_fee_items
    ADD CONSTRAINT "FK_student_fee_items" FOREIGN KEY (fee_item_id) REFERENCES app.fee_items(fee_item_id);


--
-- TOC entry 3510 (class 2606 OID 79354)
-- Name: student_fee_items FK_student_fee_items_student; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.student_fee_items
    ADD CONSTRAINT "FK_student_fee_items_student" FOREIGN KEY (student_id) REFERENCES app.students(student_id);


--
-- TOC entry 3511 (class 2606 OID 79359)
-- Name: student_guardians FK_student_guardian_guardian; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.student_guardians
    ADD CONSTRAINT "FK_student_guardian_guardian" FOREIGN KEY (guardian_id) REFERENCES app.guardians(guardian_id);


--
-- TOC entry 3512 (class 2606 OID 79364)
-- Name: student_guardians FK_student_guardian_student; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.student_guardians
    ADD CONSTRAINT "FK_student_guardian_student" FOREIGN KEY (student_id) REFERENCES app.students(student_id);


--
-- TOC entry 3513 (class 2606 OID 79369)
-- Name: student_medical_history FK_student_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.student_medical_history
    ADD CONSTRAINT "FK_student_id" FOREIGN KEY (student_id) REFERENCES app.students(student_id);


--
-- TOC entry 3502 (class 2606 OID 79374)
-- Name: student_buses FK_student_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.student_buses
    ADD CONSTRAINT "FK_student_id" FOREIGN KEY (student_id) REFERENCES app.students(student_id);


--
-- TOC entry 3506 (class 2606 OID 79379)
-- Name: students FK_student_route; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.students
    ADD CONSTRAINT "FK_student_route" FOREIGN KEY (transport_route_id) REFERENCES app.transport_routes(transport_id);


--
-- TOC entry 3508 (class 2606 OID 79384)
-- Name: subjects FK_subject_teacher; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.subjects
    ADD CONSTRAINT "FK_subject_teacher" FOREIGN KEY (teacher_id) REFERENCES app.employees(emp_id);


--
-- TOC entry 3456 (class 2606 OID 79389)
-- Name: buses bus_driver; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.buses
    ADD CONSTRAINT bus_driver FOREIGN KEY (bus_driver) REFERENCES app.employees(emp_id) MATCH FULL;


--
-- TOC entry 3457 (class 2606 OID 79394)
-- Name: buses bus_guide; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.buses
    ADD CONSTRAINT bus_guide FOREIGN KEY (bus_guide) REFERENCES app.employees(emp_id) MATCH FULL;


--
-- TOC entry 3499 (class 2606 OID 79399)
-- Name: schoolbus_bus_trips bus_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.schoolbus_bus_trips
    ADD CONSTRAINT bus_id FOREIGN KEY (bus_id) REFERENCES app.buses(bus_id) MATCH FULL;


--
-- TOC entry 3464 (class 2606 OID 79404)
-- Name: communication_attachments com_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communication_attachments
    ADD CONSTRAINT com_id FOREIGN KEY (com_id) REFERENCES app.communications(com_id) MATCH FULL;


--
-- TOC entry 3500 (class 2606 OID 79409)
-- Name: schoolbus_bus_trips schoolbus_trip_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.schoolbus_bus_trips
    ADD CONSTRAINT schoolbus_trip_id FOREIGN KEY (schoolbus_trip_id) REFERENCES app.schoolbus_trips(schoolbus_trip_id) MATCH FULL;


-- Completed on 2020-01-21 13:13:26

--
-- PostgreSQL database dump complete
--

