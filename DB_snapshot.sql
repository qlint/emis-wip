--
-- PostgreSQL database dump
--

-- Dumped from database version 9.1.24
-- Dumped by pg_dump version 9.1.24
-- Started on 2019-08-02 08:45:28

SET statement_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;

--
-- TOC entry 2647 (class 1262 OID 43544)
-- Name: eduweb_lasalle; Type: DATABASE; Schema: -; Owner: postgres
--

CREATE DATABASE eduweb_lasalle WITH TEMPLATE = template0 ENCODING = 'UTF8' LC_COLLATE = 'English_United States.1252' LC_CTYPE = 'English_United States.1252';


ALTER DATABASE eduweb_lasalle OWNER TO postgres;

\connect eduweb_lasalle

SET statement_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;

--
-- TOC entry 8 (class 2615 OID 43545)
-- Name: app; Type: SCHEMA; Schema: -; Owner: postgres
--

CREATE SCHEMA app;


ALTER SCHEMA app OWNER TO postgres;

--
-- TOC entry 1 (class 3079 OID 11639)
-- Name: plpgsql; Type: EXTENSION; Schema: -; Owner: 
--

CREATE EXTENSION IF NOT EXISTS plpgsql WITH SCHEMA pg_catalog;


--
-- TOC entry 2650 (class 0 OID 0)
-- Dependencies: 1
-- Name: EXTENSION plpgsql; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION plpgsql IS 'PL/pgSQL procedural language';


--
-- TOC entry 2 (class 3079 OID 43546)
-- Dependencies: 9
-- Name: tablefunc; Type: EXTENSION; Schema: -; Owner: 
--

CREATE EXTENSION IF NOT EXISTS tablefunc WITH SCHEMA public;


--
-- TOC entry 2651 (class 0 OID 0)
-- Dependencies: 2
-- Name: EXTENSION tablefunc; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION tablefunc IS 'functions that manipulate whole tables, including crosstab';


SET search_path = app, pg_catalog;

--
-- TOC entry 341 (class 1255 OID 43567)
-- Dependencies: 935 8
-- Name: colpivot(character varying, character varying, character varying[], character varying[], character varying, character varying); Type: FUNCTION; Schema: app; Owner: postgres
--

CREATE FUNCTION colpivot(out_table character varying, in_query character varying, key_cols character varying[], class_cols character varying[], value_e character varying, col_order character varying) RETURNS void
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
-- TOC entry 342 (class 1255 OID 43568)
-- Dependencies: 935 8
-- Name: set_invoice_term(); Type: FUNCTION; Schema: app; Owner: postgres
--

CREATE FUNCTION set_invoice_term() RETURNS boolean
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

SET default_with_oids = false;

--
-- TOC entry 209 (class 1259 OID 43569)
-- Dependencies: 8
-- Name: blog_post_statuses; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE blog_post_statuses (
    post_status_id integer NOT NULL,
    post_status character varying NOT NULL
);


ALTER TABLE app.blog_post_statuses OWNER TO postgres;

--
-- TOC entry 210 (class 1259 OID 43575)
-- Dependencies: 209 8
-- Name: blog_post_statuses_post_status_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE blog_post_statuses_post_status_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.blog_post_statuses_post_status_id_seq OWNER TO postgres;

--
-- TOC entry 2652 (class 0 OID 0)
-- Dependencies: 210
-- Name: blog_post_statuses_post_status_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE blog_post_statuses_post_status_id_seq OWNED BY blog_post_statuses.post_status_id;


--
-- TOC entry 211 (class 1259 OID 43577)
-- Dependencies: 8
-- Name: blog_post_types; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE blog_post_types (
    post_type_id integer NOT NULL,
    post_type character varying NOT NULL
);


ALTER TABLE app.blog_post_types OWNER TO postgres;

--
-- TOC entry 212 (class 1259 OID 43583)
-- Dependencies: 8 211
-- Name: blog_post_types_post_type_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE blog_post_types_post_type_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.blog_post_types_post_type_id_seq OWNER TO postgres;

--
-- TOC entry 2653 (class 0 OID 0)
-- Dependencies: 212
-- Name: blog_post_types_post_type_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE blog_post_types_post_type_id_seq OWNED BY blog_post_types.post_type_id;


--
-- TOC entry 213 (class 1259 OID 43585)
-- Dependencies: 2196 8
-- Name: blog_posts; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE blog_posts (
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
-- TOC entry 214 (class 1259 OID 43592)
-- Dependencies: 8 213
-- Name: blog_posts_post_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE blog_posts_post_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.blog_posts_post_id_seq OWNER TO postgres;

--
-- TOC entry 2654 (class 0 OID 0)
-- Dependencies: 214
-- Name: blog_posts_post_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE blog_posts_post_id_seq OWNED BY blog_posts.post_id;


--
-- TOC entry 215 (class 1259 OID 43594)
-- Dependencies: 8
-- Name: blogs; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE blogs (
    blog_id integer NOT NULL,
    teacher_id integer NOT NULL,
    class_id integer NOT NULL,
    blog_name character varying
);


ALTER TABLE app.blogs OWNER TO postgres;

--
-- TOC entry 216 (class 1259 OID 43600)
-- Dependencies: 215 8
-- Name: blogs_blog_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE blogs_blog_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.blogs_blog_id_seq OWNER TO postgres;

--
-- TOC entry 2655 (class 0 OID 0)
-- Dependencies: 216
-- Name: blogs_blog_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE blogs_blog_id_seq OWNED BY blogs.blog_id;


--
-- TOC entry 312 (class 1259 OID 108105)
-- Dependencies: 2319 2320 8
-- Name: buses; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE buses (
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
-- TOC entry 311 (class 1259 OID 108103)
-- Dependencies: 8 312
-- Name: buses_bus_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE buses_bus_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.buses_bus_id_seq OWNER TO postgres;

--
-- TOC entry 2656 (class 0 OID 0)
-- Dependencies: 311
-- Name: buses_bus_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE buses_bus_id_seq OWNED BY buses.bus_id;


--
-- TOC entry 217 (class 1259 OID 43602)
-- Dependencies: 2199 2200 8
-- Name: class_cats; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE class_cats (
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
-- TOC entry 218 (class 1259 OID 43610)
-- Dependencies: 8 217
-- Name: class_cats_class_cat_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE class_cats_class_cat_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.class_cats_class_cat_id_seq OWNER TO postgres;

--
-- TOC entry 2657 (class 0 OID 0)
-- Dependencies: 218
-- Name: class_cats_class_cat_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE class_cats_class_cat_id_seq OWNED BY class_cats.class_cat_id;


--
-- TOC entry 219 (class 1259 OID 43612)
-- Dependencies: 2202 2203 8
-- Name: class_subject_exams; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE class_subject_exams (
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
-- TOC entry 220 (class 1259 OID 43617)
-- Dependencies: 219 8
-- Name: class_subject_exams_class_sub_exam_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE class_subject_exams_class_sub_exam_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.class_subject_exams_class_sub_exam_id_seq OWNER TO postgres;

--
-- TOC entry 2658 (class 0 OID 0)
-- Dependencies: 220
-- Name: class_subject_exams_class_sub_exam_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE class_subject_exams_class_sub_exam_id_seq OWNED BY class_subject_exams.class_sub_exam_id;


--
-- TOC entry 221 (class 1259 OID 43619)
-- Dependencies: 2205 2206 8
-- Name: class_subjects; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE class_subjects (
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
-- TOC entry 222 (class 1259 OID 43624)
-- Dependencies: 8 221
-- Name: class_subjects_class_subject_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE class_subjects_class_subject_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.class_subjects_class_subject_id_seq OWNER TO postgres;

--
-- TOC entry 2659 (class 0 OID 0)
-- Dependencies: 222
-- Name: class_subjects_class_subject_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE class_subjects_class_subject_id_seq OWNED BY class_subjects.class_subject_id;


--
-- TOC entry 223 (class 1259 OID 43626)
-- Dependencies: 2208 2209 8
-- Name: classes; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE classes (
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
-- TOC entry 224 (class 1259 OID 43634)
-- Dependencies: 8 223
-- Name: classes_class_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE classes_class_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.classes_class_id_seq OWNER TO postgres;

--
-- TOC entry 2660 (class 0 OID 0)
-- Dependencies: 224
-- Name: classes_class_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE classes_class_id_seq OWNED BY classes.class_id;


--
-- TOC entry 225 (class 1259 OID 43636)
-- Dependencies: 8
-- Name: communication_attachments; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE communication_attachments (
    com_id integer,
    attachment_id integer NOT NULL,
    attachment character varying
);


ALTER TABLE app.communication_attachments OWNER TO postgres;

--
-- TOC entry 226 (class 1259 OID 43642)
-- Dependencies: 225 8
-- Name: communication_attachments_attachment_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE communication_attachments_attachment_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.communication_attachments_attachment_id_seq OWNER TO postgres;

--
-- TOC entry 2661 (class 0 OID 0)
-- Dependencies: 226
-- Name: communication_attachments_attachment_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE communication_attachments_attachment_id_seq OWNED BY communication_attachments.attachment_id;


--
-- TOC entry 227 (class 1259 OID 43644)
-- Dependencies: 8
-- Name: communication_audience; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE communication_audience (
    audience_id integer NOT NULL,
    audience character varying NOT NULL
);


ALTER TABLE app.communication_audience OWNER TO postgres;

--
-- TOC entry 228 (class 1259 OID 43650)
-- Dependencies: 8 227
-- Name: communication_audience_audience_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE communication_audience_audience_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.communication_audience_audience_id_seq OWNER TO postgres;

--
-- TOC entry 2662 (class 0 OID 0)
-- Dependencies: 228
-- Name: communication_audience_audience_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE communication_audience_audience_id_seq OWNED BY communication_audience.audience_id;


--
-- TOC entry 229 (class 1259 OID 43652)
-- Dependencies: 2213 2214 8
-- Name: communication_emails; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE communication_emails (
    email_id integer NOT NULL,
    com_id integer NOT NULL,
    email_address character varying NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    send_date timestamp without time zone,
    forwarded boolean DEFAULT false NOT NULL
);


ALTER TABLE app.communication_emails OWNER TO postgres;

--
-- TOC entry 230 (class 1259 OID 43660)
-- Dependencies: 8 229
-- Name: communication_emails_email_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE communication_emails_email_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.communication_emails_email_id_seq OWNER TO postgres;

--
-- TOC entry 2663 (class 0 OID 0)
-- Dependencies: 230
-- Name: communication_emails_email_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE communication_emails_email_id_seq OWNED BY communication_emails.email_id;


--
-- TOC entry 231 (class 1259 OID 43662)
-- Dependencies: 2216 2217 8
-- Name: communication_feedback; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE communication_feedback (
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
-- TOC entry 232 (class 1259 OID 43670)
-- Dependencies: 231 8
-- Name: communication_feedback_com_feedback_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE communication_feedback_com_feedback_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.communication_feedback_com_feedback_id_seq OWNER TO postgres;

--
-- TOC entry 2664 (class 0 OID 0)
-- Dependencies: 232
-- Name: communication_feedback_com_feedback_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE communication_feedback_com_feedback_id_seq OWNED BY communication_feedback.com_feedback_id;


--
-- TOC entry 233 (class 1259 OID 43672)
-- Dependencies: 2219 2220 8
-- Name: communication_sms; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE communication_sms (
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
-- TOC entry 234 (class 1259 OID 43680)
-- Dependencies: 8 233
-- Name: communication_sms_sms_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE communication_sms_sms_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.communication_sms_sms_id_seq OWNER TO postgres;

--
-- TOC entry 2665 (class 0 OID 0)
-- Dependencies: 234
-- Name: communication_sms_sms_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE communication_sms_sms_id_seq OWNED BY communication_sms.sms_id;


--
-- TOC entry 235 (class 1259 OID 43682)
-- Dependencies: 8
-- Name: communication_types; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE communication_types (
    com_type_id integer NOT NULL,
    com_type character varying NOT NULL
);


ALTER TABLE app.communication_types OWNER TO postgres;

--
-- TOC entry 236 (class 1259 OID 43688)
-- Dependencies: 8 235
-- Name: communication_types_com_type_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE communication_types_com_type_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.communication_types_com_type_id_seq OWNER TO postgres;

--
-- TOC entry 2666 (class 0 OID 0)
-- Dependencies: 236
-- Name: communication_types_com_type_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE communication_types_com_type_id_seq OWNED BY communication_types.com_type_id;


--
-- TOC entry 237 (class 1259 OID 43690)
-- Dependencies: 2223 2224 8
-- Name: communications; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE communications (
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
-- TOC entry 238 (class 1259 OID 43698)
-- Dependencies: 8 237
-- Name: communications_com_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE communications_com_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.communications_com_id_seq OWNER TO postgres;

--
-- TOC entry 2667 (class 0 OID 0)
-- Dependencies: 238
-- Name: communications_com_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE communications_com_id_seq OWNED BY communications.com_id;


--
-- TOC entry 239 (class 1259 OID 43700)
-- Dependencies: 8
-- Name: countries; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE countries (
    countries_id integer NOT NULL,
    countries_name character varying(255) NOT NULL,
    countries_iso_code_2 character(2) NOT NULL,
    countries_iso_code_3 character(3) NOT NULL,
    address_format_id integer NOT NULL
);


ALTER TABLE app.countries OWNER TO postgres;

--
-- TOC entry 240 (class 1259 OID 43703)
-- Dependencies: 8 239
-- Name: countries_countries_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE countries_countries_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.countries_countries_id_seq OWNER TO postgres;

--
-- TOC entry 2668 (class 0 OID 0)
-- Dependencies: 240
-- Name: countries_countries_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE countries_countries_id_seq OWNED BY countries.countries_id;


--
-- TOC entry 241 (class 1259 OID 43705)
-- Dependencies: 2227 2228 8
-- Name: credits; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE credits (
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
-- TOC entry 242 (class 1259 OID 43713)
-- Dependencies: 8 241
-- Name: credits_credit_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE credits_credit_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.credits_credit_id_seq OWNER TO postgres;

--
-- TOC entry 2669 (class 0 OID 0)
-- Dependencies: 242
-- Name: credits_credit_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE credits_credit_id_seq OWNED BY credits.credit_id;


--
-- TOC entry 243 (class 1259 OID 43715)
-- Dependencies: 2230 8
-- Name: terms; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE terms (
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
-- TOC entry 244 (class 1259 OID 43722)
-- Dependencies: 2636 8
-- Name: current_term; Type: VIEW; Schema: app; Owner: postgres
--

CREATE VIEW current_term AS
    SELECT terms.term_id, terms.term_name, terms.start_date, terms.end_date, terms.creation_date, terms.created_by, terms.term_number FROM terms WHERE ((now())::date > terms.start_date) ORDER BY terms.start_date DESC LIMIT 1;


ALTER TABLE app.current_term OWNER TO postgres;

--
-- TOC entry 245 (class 1259 OID 43726)
-- Dependencies: 2232 2233 8
-- Name: departments; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE departments (
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
-- TOC entry 246 (class 1259 OID 43734)
-- Dependencies: 8 245
-- Name: departments_dept_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE departments_dept_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.departments_dept_id_seq OWNER TO postgres;

--
-- TOC entry 2670 (class 0 OID 0)
-- Dependencies: 246
-- Name: departments_dept_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE departments_dept_id_seq OWNED BY departments.dept_id;


--
-- TOC entry 247 (class 1259 OID 43736)
-- Dependencies: 2235 2236 8
-- Name: employee_cats; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE employee_cats (
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
-- TOC entry 248 (class 1259 OID 43744)
-- Dependencies: 247 8
-- Name: employee_cats_emp_cat_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE employee_cats_emp_cat_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.employee_cats_emp_cat_id_seq OWNER TO postgres;

--
-- TOC entry 2671 (class 0 OID 0)
-- Dependencies: 248
-- Name: employee_cats_emp_cat_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE employee_cats_emp_cat_id_seq OWNED BY employee_cats.emp_cat_id;


--
-- TOC entry 249 (class 1259 OID 43746)
-- Dependencies: 2238 2239 8
-- Name: employees; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE employees (
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
    committee character varying
);


ALTER TABLE app.employees OWNER TO postgres;

--
-- TOC entry 250 (class 1259 OID 43754)
-- Dependencies: 8 249
-- Name: employees_emp_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE employees_emp_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.employees_emp_id_seq OWNER TO postgres;

--
-- TOC entry 2672 (class 0 OID 0)
-- Dependencies: 250
-- Name: employees_emp_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE employees_emp_id_seq OWNED BY employees.emp_id;


--
-- TOC entry 251 (class 1259 OID 43756)
-- Dependencies: 2241 8
-- Name: exam_marks; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE exam_marks (
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
-- TOC entry 252 (class 1259 OID 43760)
-- Dependencies: 8 251
-- Name: exam_marks_exam_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE exam_marks_exam_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.exam_marks_exam_id_seq OWNER TO postgres;

--
-- TOC entry 2673 (class 0 OID 0)
-- Dependencies: 252
-- Name: exam_marks_exam_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE exam_marks_exam_id_seq OWNED BY exam_marks.exam_id;


--
-- TOC entry 253 (class 1259 OID 43762)
-- Dependencies: 2243 2245 8
-- Name: exam_types; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE exam_types (
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
-- TOC entry 254 (class 1259 OID 43769)
-- Dependencies: 8 253
-- Name: exam_types_exam_type_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE exam_types_exam_type_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.exam_types_exam_type_id_seq OWNER TO postgres;

--
-- TOC entry 2674 (class 0 OID 0)
-- Dependencies: 254
-- Name: exam_types_exam_type_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE exam_types_exam_type_id_seq OWNED BY exam_types.exam_type_id;


--
-- TOC entry 310 (class 1259 OID 86773)
-- Dependencies: 2316 2317 8
-- Name: fee_item_uniforms; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE fee_item_uniforms (
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
-- TOC entry 309 (class 1259 OID 86771)
-- Dependencies: 310 8
-- Name: fee_item_uniforms_uniform_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE fee_item_uniforms_uniform_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.fee_item_uniforms_uniform_id_seq OWNER TO postgres;

--
-- TOC entry 2675 (class 0 OID 0)
-- Dependencies: 309
-- Name: fee_item_uniforms_uniform_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE fee_item_uniforms_uniform_id_seq OWNED BY fee_item_uniforms.uniform_id;


--
-- TOC entry 255 (class 1259 OID 43771)
-- Dependencies: 2246 2247 2248 2249 2250 8
-- Name: fee_items; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE fee_items (
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
-- TOC entry 256 (class 1259 OID 43782)
-- Dependencies: 255 8
-- Name: fee_items_fee_item_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE fee_items_fee_item_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.fee_items_fee_item_id_seq OWNER TO postgres;

--
-- TOC entry 2676 (class 0 OID 0)
-- Dependencies: 256
-- Name: fee_items_fee_item_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE fee_items_fee_item_id_seq OWNED BY fee_items.fee_item_id;


--
-- TOC entry 257 (class 1259 OID 43784)
-- Dependencies: 8
-- Name: grading; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE grading (
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
-- TOC entry 258 (class 1259 OID 43790)
-- Dependencies: 8
-- Name: grading2; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE grading2 (
    grade2_id integer NOT NULL,
    grade2 character varying NOT NULL,
    min_mark integer NOT NULL,
    max_mark integer NOT NULL,
    comment character varying,
    kiswahili_comment character varying
);


ALTER TABLE app.grading2 OWNER TO postgres;

--
-- TOC entry 259 (class 1259 OID 43796)
-- Dependencies: 258 8
-- Name: grading2_grade2_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE grading2_grade2_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.grading2_grade2_id_seq OWNER TO postgres;

--
-- TOC entry 2677 (class 0 OID 0)
-- Dependencies: 259
-- Name: grading2_grade2_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE grading2_grade2_id_seq OWNED BY grading2.grade2_id;


--
-- TOC entry 260 (class 1259 OID 43798)
-- Dependencies: 8 257
-- Name: grading_grade_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE grading_grade_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.grading_grade_id_seq OWNER TO postgres;

--
-- TOC entry 2678 (class 0 OID 0)
-- Dependencies: 260
-- Name: grading_grade_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE grading_grade_id_seq OWNED BY grading.grade_id;


--
-- TOC entry 261 (class 1259 OID 43800)
-- Dependencies: 2254 2255 8
-- Name: guardians; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE guardians (
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
-- TOC entry 262 (class 1259 OID 43808)
-- Dependencies: 8 261
-- Name: guardians_guardian_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE guardians_guardian_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.guardians_guardian_id_seq OWNER TO postgres;

--
-- TOC entry 2679 (class 0 OID 0)
-- Dependencies: 262
-- Name: guardians_guardian_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE guardians_guardian_id_seq OWNED BY guardians.guardian_id;


--
-- TOC entry 263 (class 1259 OID 43810)
-- Dependencies: 2257 8
-- Name: homework; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE homework (
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
-- TOC entry 264 (class 1259 OID 43817)
-- Dependencies: 8 263
-- Name: homework_homework_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE homework_homework_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.homework_homework_id_seq OWNER TO postgres;

--
-- TOC entry 2680 (class 0 OID 0)
-- Dependencies: 264
-- Name: homework_homework_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE homework_homework_id_seq OWNED BY homework.homework_id;


--
-- TOC entry 265 (class 1259 OID 43819)
-- Dependencies: 2259 8
-- Name: installment_options; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE installment_options (
    installment_id integer NOT NULL,
    payment_plan_name character varying NOT NULL,
    active boolean DEFAULT true NOT NULL,
    num_payments integer,
    payment_interval integer,
    payment_interval2 character varying
);


ALTER TABLE app.installment_options OWNER TO postgres;

--
-- TOC entry 2681 (class 0 OID 0)
-- Dependencies: 265
-- Name: COLUMN installment_options.payment_interval; Type: COMMENT; Schema: app; Owner: postgres
--

COMMENT ON COLUMN installment_options.payment_interval IS 'number of days';


--
-- TOC entry 266 (class 1259 OID 43826)
-- Dependencies: 8 265
-- Name: installment_options_installment_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE installment_options_installment_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.installment_options_installment_id_seq OWNER TO postgres;

--
-- TOC entry 2682 (class 0 OID 0)
-- Dependencies: 266
-- Name: installment_options_installment_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE installment_options_installment_id_seq OWNED BY installment_options.installment_id;


--
-- TOC entry 269 (class 1259 OID 43848)
-- Dependencies: 8
-- Name: invoice_balances; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE invoice_balances (
    student_id integer,
    inv_id integer,
    inv_date date,
    total_due numeric,
    total_paid numeric,
    balance numeric,
    due_date date,
    past_due boolean,
    canceled boolean
);


ALTER TABLE app.invoice_balances OWNER TO postgres;

--
-- TOC entry 272 (class 1259 OID 43867)
-- Dependencies: 8
-- Name: invoice_balances2; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE invoice_balances2 (
    student_id integer,
    inv_id integer,
    inv_date date,
    total_due numeric,
    total_paid numeric,
    balance numeric,
    due_date date,
    past_due boolean,
    canceled boolean,
    term_id integer
);


ALTER TABLE app.invoice_balances2 OWNER TO postgres;

--
-- TOC entry 270 (class 1259 OID 43853)
-- Dependencies: 2269 8
-- Name: invoice_line_items; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE invoice_line_items (
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
-- TOC entry 273 (class 1259 OID 43872)
-- Dependencies: 8 270
-- Name: invoice_line_items_inv_item_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE invoice_line_items_inv_item_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.invoice_line_items_inv_item_id_seq OWNER TO postgres;

--
-- TOC entry 2683 (class 0 OID 0)
-- Dependencies: 273
-- Name: invoice_line_items_inv_item_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE invoice_line_items_inv_item_id_seq OWNED BY invoice_line_items.inv_item_id;


--
-- TOC entry 267 (class 1259 OID 43828)
-- Dependencies: 2261 2262 2263 8
-- Name: invoices; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE invoices (
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
-- TOC entry 274 (class 1259 OID 43874)
-- Dependencies: 8 267
-- Name: invoices_inv_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE invoices_inv_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.invoices_inv_id_seq OWNER TO postgres;

--
-- TOC entry 2684 (class 0 OID 0)
-- Dependencies: 274
-- Name: invoices_inv_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE invoices_inv_id_seq OWNED BY invoices.inv_id;


--
-- TOC entry 275 (class 1259 OID 43876)
-- Dependencies: 8
-- Name: lowersch_reportcards; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE lowersch_reportcards (
    lowersch_reportcards_id integer NOT NULL,
    student_id integer NOT NULL,
    term_id integer NOT NULL,
    file_name character varying
);


ALTER TABLE app.lowersch_reportcards OWNER TO postgres;

--
-- TOC entry 276 (class 1259 OID 43882)
-- Dependencies: 275 8
-- Name: lowersch_reportcards_lowersch_reportcards_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE lowersch_reportcards_lowersch_reportcards_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.lowersch_reportcards_lowersch_reportcards_id_seq OWNER TO postgres;

--
-- TOC entry 2685 (class 0 OID 0)
-- Dependencies: 276
-- Name: lowersch_reportcards_lowersch_reportcards_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE lowersch_reportcards_lowersch_reportcards_id_seq OWNED BY lowersch_reportcards.lowersch_reportcards_id;


--
-- TOC entry 277 (class 1259 OID 43884)
-- Dependencies: 8
-- Name: medical_conditions; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE medical_conditions (
    condition_id integer NOT NULL,
    illness_condition character varying NOT NULL
);


ALTER TABLE app.medical_conditions OWNER TO postgres;

--
-- TOC entry 278 (class 1259 OID 43890)
-- Dependencies: 8 277
-- Name: medical_conditions_condition_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE medical_conditions_condition_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.medical_conditions_condition_id_seq OWNER TO postgres;

--
-- TOC entry 2686 (class 0 OID 0)
-- Dependencies: 278
-- Name: medical_conditions_condition_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE medical_conditions_condition_id_seq OWNED BY medical_conditions.condition_id;


--
-- TOC entry 279 (class 1259 OID 43892)
-- Dependencies: 2639 8
-- Name: next_term; Type: VIEW; Schema: app; Owner: postgres
--

CREATE VIEW next_term AS
    SELECT terms.term_id, terms.term_name, terms.start_date, terms.end_date, terms.creation_date, terms.created_by, terms.term_number FROM terms WHERE ((now())::date < terms.start_date) ORDER BY terms.start_date LIMIT 1;


ALTER TABLE app.next_term OWNER TO postgres;

--
-- TOC entry 271 (class 1259 OID 43860)
-- Dependencies: 2271 8
-- Name: payment_inv_items; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE payment_inv_items (
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
-- TOC entry 280 (class 1259 OID 43896)
-- Dependencies: 8 271
-- Name: payment_inv_items_payment_inv_item_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE payment_inv_items_payment_inv_item_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.payment_inv_items_payment_inv_item_id_seq OWNER TO postgres;

--
-- TOC entry 2687 (class 0 OID 0)
-- Dependencies: 280
-- Name: payment_inv_items_payment_inv_item_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE payment_inv_items_payment_inv_item_id_seq OWNED BY payment_inv_items.payment_inv_item_id;


--
-- TOC entry 281 (class 1259 OID 43898)
-- Dependencies: 2275 8
-- Name: payment_replacement_items; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE payment_replacement_items (
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
-- TOC entry 282 (class 1259 OID 43905)
-- Dependencies: 8 281
-- Name: payment_replacement_items_payment_replace_item_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE payment_replacement_items_payment_replace_item_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.payment_replacement_items_payment_replace_item_id_seq OWNER TO postgres;

--
-- TOC entry 2688 (class 0 OID 0)
-- Dependencies: 282
-- Name: payment_replacement_items_payment_replace_item_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE payment_replacement_items_payment_replace_item_id_seq OWNED BY payment_replacement_items.payment_replace_item_id;


--
-- TOC entry 268 (class 1259 OID 43837)
-- Dependencies: 2265 2266 2267 8
-- Name: payments; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE payments (
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
    custom_receipt_no character varying
);


ALTER TABLE app.payments OWNER TO postgres;

--
-- TOC entry 2689 (class 0 OID 0)
-- Dependencies: 268
-- Name: COLUMN payments.payment_method; Type: COMMENT; Schema: app; Owner: postgres
--

COMMENT ON COLUMN payments.payment_method IS 'Cash or Cheque';


--
-- TOC entry 283 (class 1259 OID 43907)
-- Dependencies: 8 268
-- Name: payments_payment_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE payments_payment_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.payments_payment_id_seq OWNER TO postgres;

--
-- TOC entry 2690 (class 0 OID 0)
-- Dependencies: 283
-- Name: payments_payment_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE payments_payment_id_seq OWNED BY payments.payment_id;


--
-- TOC entry 284 (class 1259 OID 43909)
-- Dependencies: 2640 8
-- Name: previous_term; Type: VIEW; Schema: app; Owner: postgres
--

CREATE VIEW previous_term AS
    SELECT terms.term_id, terms.term_name, terms.start_date, terms.end_date, terms.creation_date, terms.created_by, terms.term_number FROM terms WHERE (terms.start_date < (SELECT current_term.start_date FROM current_term)) ORDER BY terms.start_date DESC LIMIT 1;


ALTER TABLE app.previous_term OWNER TO postgres;

--
-- TOC entry 285 (class 1259 OID 43913)
-- Dependencies: 2277 2278 8
-- Name: report_cards; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE report_cards (
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
-- TOC entry 286 (class 1259 OID 43921)
-- Dependencies: 8 285
-- Name: report_cards_report_card_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE report_cards_report_card_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.report_cards_report_card_id_seq OWNER TO postgres;

--
-- TOC entry 2691 (class 0 OID 0)
-- Dependencies: 286
-- Name: report_cards_report_card_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE report_cards_report_card_id_seq OWNED BY report_cards.report_card_id;


--
-- TOC entry 316 (class 1259 OID 109944)
-- Dependencies: 2323 8
-- Name: schoolbus_history; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE schoolbus_history (
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
-- TOC entry 315 (class 1259 OID 109942)
-- Dependencies: 8 316
-- Name: schoolbus_history_schoolbus_history_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE schoolbus_history_schoolbus_history_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.schoolbus_history_schoolbus_history_id_seq OWNER TO postgres;

--
-- TOC entry 2692 (class 0 OID 0)
-- Dependencies: 315
-- Name: schoolbus_history_schoolbus_history_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE schoolbus_history_schoolbus_history_id_seq OWNED BY schoolbus_history.schoolbus_history_id;


--
-- TOC entry 318 (class 1259 OID 114269)
-- Dependencies: 2324 2326 8
-- Name: schoolbus_trips; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE schoolbus_trips (
    schoolbus_trip_id integer NOT NULL,
    trip_name character varying,
    bus_id integer,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    modified_date timestamp without time zone DEFAULT now(),
    class_cats character varying
);


ALTER TABLE app.schoolbus_trips OWNER TO postgres;

--
-- TOC entry 317 (class 1259 OID 114267)
-- Dependencies: 8 318
-- Name: schoolbus_trips_schoolbus_trip_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE schoolbus_trips_schoolbus_trip_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.schoolbus_trips_schoolbus_trip_id_seq OWNER TO postgres;

--
-- TOC entry 2693 (class 0 OID 0)
-- Dependencies: 317
-- Name: schoolbus_trips_schoolbus_trip_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE schoolbus_trips_schoolbus_trip_id_seq OWNED BY schoolbus_trips.schoolbus_trip_id;


--
-- TOC entry 287 (class 1259 OID 43923)
-- Dependencies: 8
-- Name: settings; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE settings (
    name character varying NOT NULL,
    value character varying
);


ALTER TABLE app.settings OWNER TO postgres;

--
-- TOC entry 314 (class 1259 OID 109218)
-- Dependencies: 8
-- Name: student_buses; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE student_buses (
    student_bus_id integer NOT NULL,
    student_id integer NOT NULL,
    bus_id integer NOT NULL
);


ALTER TABLE app.student_buses OWNER TO postgres;

--
-- TOC entry 313 (class 1259 OID 109216)
-- Dependencies: 314 8
-- Name: student_buses_student_bus_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE student_buses_student_bus_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.student_buses_student_bus_id_seq OWNER TO postgres;

--
-- TOC entry 2694 (class 0 OID 0)
-- Dependencies: 313
-- Name: student_buses_student_bus_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE student_buses_student_bus_id_seq OWNED BY student_buses.student_bus_id;


--
-- TOC entry 288 (class 1259 OID 43929)
-- Dependencies: 2280 2281 8
-- Name: student_class_history; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE student_class_history (
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
-- TOC entry 289 (class 1259 OID 43934)
-- Dependencies: 8 288
-- Name: student_class_history_class_history_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE student_class_history_class_history_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.student_class_history_class_history_id_seq OWNER TO postgres;

--
-- TOC entry 2695 (class 0 OID 0)
-- Dependencies: 289
-- Name: student_class_history_class_history_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE student_class_history_class_history_id_seq OWNED BY student_class_history.class_history_id;


--
-- TOC entry 290 (class 1259 OID 43936)
-- Dependencies: 2283 2284 2285 2286 2287 2288 2289 2290 2291 2292 2293 2294 8
-- Name: students; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE students (
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
    trip_ids character varying
);


ALTER TABLE app.students OWNER TO postgres;

--
-- TOC entry 291 (class 1259 OID 43954)
-- Dependencies: 2296 2297 2298 8
-- Name: subjects; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE subjects (
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
-- TOC entry 292 (class 1259 OID 43963)
-- Dependencies: 2641 8
-- Name: student_exam_marks; Type: VIEW; Schema: app; Owner: postgres
--

CREATE VIEW student_exam_marks AS
    SELECT students.student_id, (((((students.first_name)::text || ' '::text) || (COALESCE(students.middle_name, ''::character varying))::text) || ' '::text) || (students.last_name)::text) AS student_name, exam_marks.term_id, class_subjects.class_id, class_subject_exams.exam_type_id, exam_types.exam_type, subjects.subject_name, exam_marks.mark, class_subject_exams.class_sub_exam_id, class_subject_exams.grade_weight FROM ((((((class_subjects JOIN class_subject_exams USING (class_subject_id)) JOIN exam_types USING (exam_type_id)) JOIN subjects USING (subject_id)) JOIN classes USING (class_id)) JOIN students ON ((classes.class_id = students.current_class))) LEFT JOIN exam_marks ON (((students.student_id = exam_marks.student_id) AND (class_subject_exams.class_sub_exam_id = exam_marks.class_sub_exam_id))));


ALTER TABLE app.student_exam_marks OWNER TO postgres;

--
-- TOC entry 293 (class 1259 OID 43968)
-- Dependencies: 2300 2301 8
-- Name: student_fee_items; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE student_fee_items (
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
-- TOC entry 2696 (class 0 OID 0)
-- Dependencies: 293
-- Name: COLUMN student_fee_items.payment_method; Type: COMMENT; Schema: app; Owner: postgres
--

COMMENT ON COLUMN student_fee_items.payment_method IS 'This is an option from the Payment Options setting';


--
-- TOC entry 294 (class 1259 OID 43976)
-- Dependencies: 8 293
-- Name: student_fee_items_student_fee_item_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE student_fee_items_student_fee_item_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.student_fee_items_student_fee_item_id_seq OWNER TO postgres;

--
-- TOC entry 2697 (class 0 OID 0)
-- Dependencies: 294
-- Name: student_fee_items_student_fee_item_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE student_fee_items_student_fee_item_id_seq OWNED BY student_fee_items.student_fee_item_id;


--
-- TOC entry 295 (class 1259 OID 43978)
-- Dependencies: 2303 2304 8
-- Name: student_guardians; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE student_guardians (
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
-- TOC entry 296 (class 1259 OID 43986)
-- Dependencies: 8 295
-- Name: student_guardians_student_guardian_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE student_guardians_student_guardian_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.student_guardians_student_guardian_id_seq OWNER TO postgres;

--
-- TOC entry 2698 (class 0 OID 0)
-- Dependencies: 296
-- Name: student_guardians_student_guardian_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE student_guardians_student_guardian_id_seq OWNED BY student_guardians.student_guardian_id;


--
-- TOC entry 297 (class 1259 OID 43988)
-- Dependencies: 2306 8
-- Name: student_medical_history; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE student_medical_history (
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
-- TOC entry 298 (class 1259 OID 43995)
-- Dependencies: 8 297
-- Name: student_medical_history_medical_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE student_medical_history_medical_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.student_medical_history_medical_id_seq OWNER TO postgres;

--
-- TOC entry 2699 (class 0 OID 0)
-- Dependencies: 298
-- Name: student_medical_history_medical_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE student_medical_history_medical_id_seq OWNED BY student_medical_history.medical_id;


--
-- TOC entry 299 (class 1259 OID 43997)
-- Dependencies: 290 8
-- Name: students_student_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE students_student_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.students_student_id_seq OWNER TO postgres;

--
-- TOC entry 2700 (class 0 OID 0)
-- Dependencies: 299
-- Name: students_student_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE students_student_id_seq OWNED BY students.student_id;


--
-- TOC entry 300 (class 1259 OID 43999)
-- Dependencies: 291 8
-- Name: subjects_subject_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE subjects_subject_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.subjects_subject_id_seq OWNER TO postgres;

--
-- TOC entry 2701 (class 0 OID 0)
-- Dependencies: 300
-- Name: subjects_subject_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE subjects_subject_id_seq OWNED BY subjects.subject_id;


--
-- TOC entry 301 (class 1259 OID 44001)
-- Dependencies: 2642 8
-- Name: term_after_next; Type: VIEW; Schema: app; Owner: postgres
--

CREATE VIEW term_after_next AS
    SELECT terms.term_id, terms.term_name, terms.start_date, terms.end_date, terms.creation_date, terms.created_by, terms.term_number FROM terms WHERE ((now())::date < terms.start_date) ORDER BY terms.start_date OFFSET 1 LIMIT 1;


ALTER TABLE app.term_after_next OWNER TO postgres;

--
-- TOC entry 302 (class 1259 OID 44005)
-- Dependencies: 243 8
-- Name: terms_term_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE terms_term_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.terms_term_id_seq OWNER TO postgres;

--
-- TOC entry 2702 (class 0 OID 0)
-- Dependencies: 302
-- Name: terms_term_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE terms_term_id_seq OWNED BY terms.term_id;


--
-- TOC entry 303 (class 1259 OID 44007)
-- Dependencies: 2308 2309 8
-- Name: transport_routes; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE transport_routes (
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
-- TOC entry 304 (class 1259 OID 44015)
-- Dependencies: 303 8
-- Name: transport_routes_transport_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE transport_routes_transport_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.transport_routes_transport_id_seq OWNER TO postgres;

--
-- TOC entry 2703 (class 0 OID 0)
-- Dependencies: 304
-- Name: transport_routes_transport_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE transport_routes_transport_id_seq OWNED BY transport_routes.transport_id;


--
-- TOC entry 305 (class 1259 OID 44017)
-- Dependencies: 8
-- Name: user_permissions; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE user_permissions (
    perm_id integer NOT NULL,
    user_type character varying NOT NULL,
    permissions text NOT NULL
);


ALTER TABLE app.user_permissions OWNER TO postgres;

--
-- TOC entry 306 (class 1259 OID 44023)
-- Dependencies: 305 8
-- Name: user_permissions_perm_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE user_permissions_perm_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.user_permissions_perm_id_seq OWNER TO postgres;

--
-- TOC entry 2704 (class 0 OID 0)
-- Dependencies: 306
-- Name: user_permissions_perm_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE user_permissions_perm_id_seq OWNED BY user_permissions.perm_id;


--
-- TOC entry 307 (class 1259 OID 44025)
-- Dependencies: 2312 2313 8
-- Name: users; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE users (
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
-- TOC entry 308 (class 1259 OID 44033)
-- Dependencies: 8 307
-- Name: user_user_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE user_user_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.user_user_id_seq OWNER TO postgres;

--
-- TOC entry 2705 (class 0 OID 0)
-- Dependencies: 308
-- Name: user_user_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE user_user_id_seq OWNED BY users.user_id;


--
-- TOC entry 2194 (class 2604 OID 44035)
-- Dependencies: 210 209
-- Name: post_status_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY blog_post_statuses ALTER COLUMN post_status_id SET DEFAULT nextval('blog_post_statuses_post_status_id_seq'::regclass);


--
-- TOC entry 2195 (class 2604 OID 44036)
-- Dependencies: 212 211
-- Name: post_type_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY blog_post_types ALTER COLUMN post_type_id SET DEFAULT nextval('blog_post_types_post_type_id_seq'::regclass);


--
-- TOC entry 2197 (class 2604 OID 44037)
-- Dependencies: 214 213
-- Name: post_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY blog_posts ALTER COLUMN post_id SET DEFAULT nextval('blog_posts_post_id_seq'::regclass);


--
-- TOC entry 2198 (class 2604 OID 44038)
-- Dependencies: 216 215
-- Name: blog_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY blogs ALTER COLUMN blog_id SET DEFAULT nextval('blogs_blog_id_seq'::regclass);


--
-- TOC entry 2318 (class 2604 OID 108108)
-- Dependencies: 311 312 312
-- Name: bus_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY buses ALTER COLUMN bus_id SET DEFAULT nextval('buses_bus_id_seq'::regclass);


--
-- TOC entry 2201 (class 2604 OID 44039)
-- Dependencies: 218 217
-- Name: class_cat_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY class_cats ALTER COLUMN class_cat_id SET DEFAULT nextval('class_cats_class_cat_id_seq'::regclass);


--
-- TOC entry 2204 (class 2604 OID 44040)
-- Dependencies: 220 219
-- Name: class_sub_exam_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY class_subject_exams ALTER COLUMN class_sub_exam_id SET DEFAULT nextval('class_subject_exams_class_sub_exam_id_seq'::regclass);


--
-- TOC entry 2207 (class 2604 OID 44041)
-- Dependencies: 222 221
-- Name: class_subject_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY class_subjects ALTER COLUMN class_subject_id SET DEFAULT nextval('class_subjects_class_subject_id_seq'::regclass);


--
-- TOC entry 2210 (class 2604 OID 44042)
-- Dependencies: 224 223
-- Name: class_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY classes ALTER COLUMN class_id SET DEFAULT nextval('classes_class_id_seq'::regclass);


--
-- TOC entry 2211 (class 2604 OID 44043)
-- Dependencies: 226 225
-- Name: attachment_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY communication_attachments ALTER COLUMN attachment_id SET DEFAULT nextval('communication_attachments_attachment_id_seq'::regclass);


--
-- TOC entry 2212 (class 2604 OID 44044)
-- Dependencies: 228 227
-- Name: audience_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY communication_audience ALTER COLUMN audience_id SET DEFAULT nextval('communication_audience_audience_id_seq'::regclass);


--
-- TOC entry 2215 (class 2604 OID 44045)
-- Dependencies: 230 229
-- Name: email_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY communication_emails ALTER COLUMN email_id SET DEFAULT nextval('communication_emails_email_id_seq'::regclass);


--
-- TOC entry 2218 (class 2604 OID 44046)
-- Dependencies: 232 231
-- Name: com_feedback_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY communication_feedback ALTER COLUMN com_feedback_id SET DEFAULT nextval('communication_feedback_com_feedback_id_seq'::regclass);


--
-- TOC entry 2221 (class 2604 OID 44047)
-- Dependencies: 234 233
-- Name: sms_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY communication_sms ALTER COLUMN sms_id SET DEFAULT nextval('communication_sms_sms_id_seq'::regclass);


--
-- TOC entry 2222 (class 2604 OID 44048)
-- Dependencies: 236 235
-- Name: com_type_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY communication_types ALTER COLUMN com_type_id SET DEFAULT nextval('communication_types_com_type_id_seq'::regclass);


--
-- TOC entry 2225 (class 2604 OID 44049)
-- Dependencies: 238 237
-- Name: com_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY communications ALTER COLUMN com_id SET DEFAULT nextval('communications_com_id_seq'::regclass);


--
-- TOC entry 2226 (class 2604 OID 44050)
-- Dependencies: 240 239
-- Name: countries_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY countries ALTER COLUMN countries_id SET DEFAULT nextval('countries_countries_id_seq'::regclass);


--
-- TOC entry 2229 (class 2604 OID 44051)
-- Dependencies: 242 241
-- Name: credit_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY credits ALTER COLUMN credit_id SET DEFAULT nextval('credits_credit_id_seq'::regclass);


--
-- TOC entry 2234 (class 2604 OID 44052)
-- Dependencies: 246 245
-- Name: dept_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY departments ALTER COLUMN dept_id SET DEFAULT nextval('departments_dept_id_seq'::regclass);


--
-- TOC entry 2237 (class 2604 OID 44053)
-- Dependencies: 248 247
-- Name: emp_cat_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY employee_cats ALTER COLUMN emp_cat_id SET DEFAULT nextval('employee_cats_emp_cat_id_seq'::regclass);


--
-- TOC entry 2240 (class 2604 OID 44054)
-- Dependencies: 250 249
-- Name: emp_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY employees ALTER COLUMN emp_id SET DEFAULT nextval('employees_emp_id_seq'::regclass);


--
-- TOC entry 2242 (class 2604 OID 44055)
-- Dependencies: 252 251
-- Name: exam_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY exam_marks ALTER COLUMN exam_id SET DEFAULT nextval('exam_marks_exam_id_seq'::regclass);


--
-- TOC entry 2244 (class 2604 OID 44056)
-- Dependencies: 254 253
-- Name: exam_type_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY exam_types ALTER COLUMN exam_type_id SET DEFAULT nextval('exam_types_exam_type_id_seq'::regclass);


--
-- TOC entry 2315 (class 2604 OID 86776)
-- Dependencies: 310 309 310
-- Name: uniform_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY fee_item_uniforms ALTER COLUMN uniform_id SET DEFAULT nextval('fee_item_uniforms_uniform_id_seq'::regclass);


--
-- TOC entry 2251 (class 2604 OID 44057)
-- Dependencies: 256 255
-- Name: fee_item_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY fee_items ALTER COLUMN fee_item_id SET DEFAULT nextval('fee_items_fee_item_id_seq'::regclass);


--
-- TOC entry 2252 (class 2604 OID 44058)
-- Dependencies: 260 257
-- Name: grade_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY grading ALTER COLUMN grade_id SET DEFAULT nextval('grading_grade_id_seq'::regclass);


--
-- TOC entry 2253 (class 2604 OID 44059)
-- Dependencies: 259 258
-- Name: grade2_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY grading2 ALTER COLUMN grade2_id SET DEFAULT nextval('grading2_grade2_id_seq'::regclass);


--
-- TOC entry 2256 (class 2604 OID 44060)
-- Dependencies: 262 261
-- Name: guardian_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY guardians ALTER COLUMN guardian_id SET DEFAULT nextval('guardians_guardian_id_seq'::regclass);


--
-- TOC entry 2258 (class 2604 OID 44061)
-- Dependencies: 264 263
-- Name: homework_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY homework ALTER COLUMN homework_id SET DEFAULT nextval('homework_homework_id_seq'::regclass);


--
-- TOC entry 2260 (class 2604 OID 44062)
-- Dependencies: 266 265
-- Name: installment_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY installment_options ALTER COLUMN installment_id SET DEFAULT nextval('installment_options_installment_id_seq'::regclass);


--
-- TOC entry 2270 (class 2604 OID 44063)
-- Dependencies: 273 270
-- Name: inv_item_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY invoice_line_items ALTER COLUMN inv_item_id SET DEFAULT nextval('invoice_line_items_inv_item_id_seq'::regclass);


--
-- TOC entry 2264 (class 2604 OID 44064)
-- Dependencies: 274 267
-- Name: inv_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY invoices ALTER COLUMN inv_id SET DEFAULT nextval('invoices_inv_id_seq'::regclass);


--
-- TOC entry 2273 (class 2604 OID 44065)
-- Dependencies: 276 275
-- Name: lowersch_reportcards_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY lowersch_reportcards ALTER COLUMN lowersch_reportcards_id SET DEFAULT nextval('lowersch_reportcards_lowersch_reportcards_id_seq'::regclass);


--
-- TOC entry 2274 (class 2604 OID 44066)
-- Dependencies: 278 277
-- Name: condition_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY medical_conditions ALTER COLUMN condition_id SET DEFAULT nextval('medical_conditions_condition_id_seq'::regclass);


--
-- TOC entry 2272 (class 2604 OID 44067)
-- Dependencies: 280 271
-- Name: payment_inv_item_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY payment_inv_items ALTER COLUMN payment_inv_item_id SET DEFAULT nextval('payment_inv_items_payment_inv_item_id_seq'::regclass);


--
-- TOC entry 2276 (class 2604 OID 44068)
-- Dependencies: 282 281
-- Name: payment_replace_item_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY payment_replacement_items ALTER COLUMN payment_replace_item_id SET DEFAULT nextval('payment_replacement_items_payment_replace_item_id_seq'::regclass);


--
-- TOC entry 2268 (class 2604 OID 44069)
-- Dependencies: 283 268
-- Name: payment_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY payments ALTER COLUMN payment_id SET DEFAULT nextval('payments_payment_id_seq'::regclass);


--
-- TOC entry 2279 (class 2604 OID 44070)
-- Dependencies: 286 285
-- Name: report_card_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY report_cards ALTER COLUMN report_card_id SET DEFAULT nextval('report_cards_report_card_id_seq'::regclass);


--
-- TOC entry 2322 (class 2604 OID 109947)
-- Dependencies: 315 316 316
-- Name: schoolbus_history_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY schoolbus_history ALTER COLUMN schoolbus_history_id SET DEFAULT nextval('schoolbus_history_schoolbus_history_id_seq'::regclass);


--
-- TOC entry 2325 (class 2604 OID 114272)
-- Dependencies: 318 317 318
-- Name: schoolbus_trip_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY schoolbus_trips ALTER COLUMN schoolbus_trip_id SET DEFAULT nextval('schoolbus_trips_schoolbus_trip_id_seq'::regclass);


--
-- TOC entry 2321 (class 2604 OID 109221)
-- Dependencies: 313 314 314
-- Name: student_bus_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY student_buses ALTER COLUMN student_bus_id SET DEFAULT nextval('student_buses_student_bus_id_seq'::regclass);


--
-- TOC entry 2282 (class 2604 OID 44071)
-- Dependencies: 289 288
-- Name: class_history_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY student_class_history ALTER COLUMN class_history_id SET DEFAULT nextval('student_class_history_class_history_id_seq'::regclass);


--
-- TOC entry 2302 (class 2604 OID 44072)
-- Dependencies: 294 293
-- Name: student_fee_item_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY student_fee_items ALTER COLUMN student_fee_item_id SET DEFAULT nextval('student_fee_items_student_fee_item_id_seq'::regclass);


--
-- TOC entry 2305 (class 2604 OID 44073)
-- Dependencies: 296 295
-- Name: student_guardian_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY student_guardians ALTER COLUMN student_guardian_id SET DEFAULT nextval('student_guardians_student_guardian_id_seq'::regclass);


--
-- TOC entry 2307 (class 2604 OID 44074)
-- Dependencies: 298 297
-- Name: medical_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY student_medical_history ALTER COLUMN medical_id SET DEFAULT nextval('student_medical_history_medical_id_seq'::regclass);


--
-- TOC entry 2295 (class 2604 OID 44075)
-- Dependencies: 299 290
-- Name: student_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY students ALTER COLUMN student_id SET DEFAULT nextval('students_student_id_seq'::regclass);


--
-- TOC entry 2299 (class 2604 OID 44076)
-- Dependencies: 300 291
-- Name: subject_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY subjects ALTER COLUMN subject_id SET DEFAULT nextval('subjects_subject_id_seq'::regclass);


--
-- TOC entry 2231 (class 2604 OID 44077)
-- Dependencies: 302 243
-- Name: term_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY terms ALTER COLUMN term_id SET DEFAULT nextval('terms_term_id_seq'::regclass);


--
-- TOC entry 2310 (class 2604 OID 44078)
-- Dependencies: 304 303
-- Name: transport_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY transport_routes ALTER COLUMN transport_id SET DEFAULT nextval('transport_routes_transport_id_seq'::regclass);


--
-- TOC entry 2311 (class 2604 OID 44079)
-- Dependencies: 306 305
-- Name: perm_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY user_permissions ALTER COLUMN perm_id SET DEFAULT nextval('user_permissions_perm_id_seq'::regclass);


--
-- TOC entry 2314 (class 2604 OID 44080)
-- Dependencies: 308 307
-- Name: user_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY users ALTER COLUMN user_id SET DEFAULT nextval('user_user_id_seq'::regclass);


--
-- TOC entry 2334 (class 2606 OID 44082)
-- Dependencies: 215 215 2644
-- Name: FK_blog_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY blogs
    ADD CONSTRAINT "FK_blog_id" PRIMARY KEY (blog_id);


--
-- TOC entry 2407 (class 2606 OID 44084)
-- Dependencies: 263 263 2644
-- Name: FK_homework_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY homework
    ADD CONSTRAINT "FK_homework_id" PRIMARY KEY (homework_id);


--
-- TOC entry 2419 (class 2606 OID 44086)
-- Dependencies: 275 275 2644
-- Name: FK_lowersch_reportcards_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY lowersch_reportcards
    ADD CONSTRAINT "FK_lowersch_reportcards_id" PRIMARY KEY (lowersch_reportcards_id);


--
-- TOC entry 2425 (class 2606 OID 44088)
-- Dependencies: 285 285 2644
-- Name: FK_report_card_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY report_cards
    ADD CONSTRAINT "FK_report_card_id" PRIMARY KEY (report_card_id);


--
-- TOC entry 2352 (class 2606 OID 44090)
-- Dependencies: 227 227 2644
-- Name: PK_audience_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY communication_audience
    ADD CONSTRAINT "PK_audience_id" PRIMARY KEY (audience_id);


--
-- TOC entry 2461 (class 2606 OID 108115)
-- Dependencies: 312 312 2644
-- Name: PK_bus_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY buses
    ADD CONSTRAINT "PK_bus_id" PRIMARY KEY (bus_id);


--
-- TOC entry 2336 (class 2606 OID 44092)
-- Dependencies: 217 217 2644
-- Name: PK_class_cat_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY class_cats
    ADD CONSTRAINT "PK_class_cat_id" PRIMARY KEY (class_cat_id);


--
-- TOC entry 2429 (class 2606 OID 44094)
-- Dependencies: 288 288 2644
-- Name: PK_class_history_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY student_class_history
    ADD CONSTRAINT "PK_class_history_id" PRIMARY KEY (class_history_id);


--
-- TOC entry 2347 (class 2606 OID 44096)
-- Dependencies: 223 223 2644
-- Name: PK_class_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY classes
    ADD CONSTRAINT "PK_class_id" PRIMARY KEY (class_id);


--
-- TOC entry 2343 (class 2606 OID 44098)
-- Dependencies: 221 221 2644
-- Name: PK_class_subject; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY class_subjects
    ADD CONSTRAINT "PK_class_subject" PRIMARY KEY (class_subject_id);


--
-- TOC entry 2339 (class 2606 OID 44100)
-- Dependencies: 219 219 2644
-- Name: PK_class_subject_exam; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY class_subject_exams
    ADD CONSTRAINT "PK_class_subject_exam" PRIMARY KEY (class_sub_exam_id);


--
-- TOC entry 2356 (class 2606 OID 44102)
-- Dependencies: 231 231 2644
-- Name: PK_com_feedback_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY communication_feedback
    ADD CONSTRAINT "PK_com_feedback_id" PRIMARY KEY (com_feedback_id);


--
-- TOC entry 2362 (class 2606 OID 44104)
-- Dependencies: 237 237 2644
-- Name: PK_com_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY communications
    ADD CONSTRAINT "PK_com_id" PRIMARY KEY (com_id);


--
-- TOC entry 2360 (class 2606 OID 44106)
-- Dependencies: 235 235 2644
-- Name: PK_com_type_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY communication_types
    ADD CONSTRAINT "PK_com_type_id" PRIMARY KEY (com_type_id);


--
-- TOC entry 2421 (class 2606 OID 44108)
-- Dependencies: 277 277 2644
-- Name: PK_condition_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY medical_conditions
    ADD CONSTRAINT "PK_condition_id" PRIMARY KEY (condition_id);


--
-- TOC entry 2366 (class 2606 OID 44110)
-- Dependencies: 241 241 2644
-- Name: PK_credit_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY credits
    ADD CONSTRAINT "PK_credit_id" PRIMARY KEY (credit_id);


--
-- TOC entry 2372 (class 2606 OID 44112)
-- Dependencies: 245 245 2644
-- Name: PK_dept_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY departments
    ADD CONSTRAINT "PK_dept_id" PRIMARY KEY (dept_id);


--
-- TOC entry 2354 (class 2606 OID 44114)
-- Dependencies: 229 229 2644
-- Name: PK_email_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY communication_emails
    ADD CONSTRAINT "PK_email_id" PRIMARY KEY (email_id);


--
-- TOC entry 2376 (class 2606 OID 44116)
-- Dependencies: 247 247 2644
-- Name: PK_emp_cat_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY employee_cats
    ADD CONSTRAINT "PK_emp_cat_id" PRIMARY KEY (emp_cat_id);


--
-- TOC entry 2380 (class 2606 OID 44118)
-- Dependencies: 249 249 2644
-- Name: PK_emp_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY employees
    ADD CONSTRAINT "PK_emp_id" PRIMARY KEY (emp_id);


--
-- TOC entry 2384 (class 2606 OID 44120)
-- Dependencies: 251 251 2644
-- Name: PK_exam_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY exam_marks
    ADD CONSTRAINT "PK_exam_id" PRIMARY KEY (exam_id);


--
-- TOC entry 2389 (class 2606 OID 44122)
-- Dependencies: 253 253 2644
-- Name: PK_exam_type; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY exam_types
    ADD CONSTRAINT "PK_exam_type" PRIMARY KEY (exam_type_id);


--
-- TOC entry 2393 (class 2606 OID 44124)
-- Dependencies: 255 255 2644
-- Name: PK_fee_item_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY fee_items
    ADD CONSTRAINT "PK_fee_item_id" PRIMARY KEY (fee_item_id);


--
-- TOC entry 2399 (class 2606 OID 44126)
-- Dependencies: 258 258 2644
-- Name: PK_grade2_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY grading2
    ADD CONSTRAINT "PK_grade2_id" PRIMARY KEY (grade2_id);


--
-- TOC entry 2395 (class 2606 OID 44128)
-- Dependencies: 257 257 2644
-- Name: PK_grade_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY grading
    ADD CONSTRAINT "PK_grade_id" PRIMARY KEY (grade_id);


--
-- TOC entry 2403 (class 2606 OID 44130)
-- Dependencies: 261 261 2644
-- Name: PK_guardian_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY guardians
    ADD CONSTRAINT "PK_guardian_id" PRIMARY KEY (guardian_id);


--
-- TOC entry 2409 (class 2606 OID 44132)
-- Dependencies: 265 265 2644
-- Name: PK_installment_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY installment_options
    ADD CONSTRAINT "PK_installment_id" PRIMARY KEY (installment_id);


--
-- TOC entry 2411 (class 2606 OID 43847)
-- Dependencies: 267 267 2644
-- Name: PK_inv_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY invoices
    ADD CONSTRAINT "PK_inv_id" PRIMARY KEY (inv_id);


--
-- TOC entry 2415 (class 2606 OID 44134)
-- Dependencies: 270 270 2644
-- Name: PK_inv_item_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY invoice_line_items
    ADD CONSTRAINT "PK_inv_item_id" PRIMARY KEY (inv_item_id);


--
-- TOC entry 2445 (class 2606 OID 44136)
-- Dependencies: 297 297 2644
-- Name: PK_medical_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY student_medical_history
    ADD CONSTRAINT "PK_medical_id" PRIMARY KEY (medical_id);


--
-- TOC entry 2413 (class 2606 OID 44138)
-- Dependencies: 268 268 2644
-- Name: PK_payment_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY payments
    ADD CONSTRAINT "PK_payment_id" PRIMARY KEY (payment_id);


--
-- TOC entry 2417 (class 2606 OID 44140)
-- Dependencies: 271 271 2644
-- Name: PK_payment_inv_item_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY payment_inv_items
    ADD CONSTRAINT "PK_payment_inv_item_id" PRIMARY KEY (payment_inv_item_id);


--
-- TOC entry 2423 (class 2606 OID 44142)
-- Dependencies: 281 281 2644
-- Name: PK_payment_replace_item_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY payment_replacement_items
    ADD CONSTRAINT "PK_payment_replace_item_id" PRIMARY KEY (payment_replace_item_id);


--
-- TOC entry 2451 (class 2606 OID 44144)
-- Dependencies: 305 305 2644
-- Name: PK_perm_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY user_permissions
    ADD CONSTRAINT "PK_perm_id" PRIMARY KEY (perm_id);


--
-- TOC entry 2332 (class 2606 OID 44146)
-- Dependencies: 213 213 2644
-- Name: PK_post_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY blog_posts
    ADD CONSTRAINT "PK_post_id" PRIMARY KEY (post_id);


--
-- TOC entry 2328 (class 2606 OID 44148)
-- Dependencies: 209 209 2644
-- Name: PK_post_status_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY blog_post_statuses
    ADD CONSTRAINT "PK_post_status_id" PRIMARY KEY (post_status_id);


--
-- TOC entry 2330 (class 2606 OID 44150)
-- Dependencies: 211 211 2644
-- Name: PK_post_type_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY blog_post_types
    ADD CONSTRAINT "PK_post_type_id" PRIMARY KEY (post_type_id);


--
-- TOC entry 2469 (class 2606 OID 109953)
-- Dependencies: 316 316 2644
-- Name: PK_schoolbus_history_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY schoolbus_history
    ADD CONSTRAINT "PK_schoolbus_history_id" PRIMARY KEY (schoolbus_history_id);


--
-- TOC entry 2471 (class 2606 OID 114279)
-- Dependencies: 318 318 2644
-- Name: PK_schoolbus_trip_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY schoolbus_trips
    ADD CONSTRAINT "PK_schoolbus_trip_id" PRIMARY KEY (schoolbus_trip_id);


--
-- TOC entry 2427 (class 2606 OID 44152)
-- Dependencies: 287 287 2644
-- Name: PK_setting_name; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY settings
    ADD CONSTRAINT "PK_setting_name" PRIMARY KEY (name);


--
-- TOC entry 2358 (class 2606 OID 44154)
-- Dependencies: 233 233 2644
-- Name: PK_sms_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY communication_sms
    ADD CONSTRAINT "PK_sms_id" PRIMARY KEY (sms_id);


--
-- TOC entry 2465 (class 2606 OID 109223)
-- Dependencies: 314 314 2644
-- Name: PK_student_bus_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY student_buses
    ADD CONSTRAINT "PK_student_bus_id" PRIMARY KEY (student_bus_id);


--
-- TOC entry 2439 (class 2606 OID 44156)
-- Dependencies: 293 293 2644
-- Name: PK_student_fee_item; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY student_fee_items
    ADD CONSTRAINT "PK_student_fee_item" PRIMARY KEY (student_fee_item_id);


--
-- TOC entry 2443 (class 2606 OID 44158)
-- Dependencies: 295 295 2644
-- Name: PK_student_guardian_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY student_guardians
    ADD CONSTRAINT "PK_student_guardian_id" PRIMARY KEY (student_guardian_id);


--
-- TOC entry 2431 (class 2606 OID 44160)
-- Dependencies: 290 290 2644
-- Name: PK_student_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY students
    ADD CONSTRAINT "PK_student_id" PRIMARY KEY (student_id);


--
-- TOC entry 2435 (class 2606 OID 44162)
-- Dependencies: 291 291 2644
-- Name: PK_subject_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY subjects
    ADD CONSTRAINT "PK_subject_id" PRIMARY KEY (subject_id);


--
-- TOC entry 2368 (class 2606 OID 44164)
-- Dependencies: 243 243 2644
-- Name: PK_term_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY terms
    ADD CONSTRAINT "PK_term_id" PRIMARY KEY (term_id);


--
-- TOC entry 2447 (class 2606 OID 44166)
-- Dependencies: 303 303 2644
-- Name: PK_transport_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY transport_routes
    ADD CONSTRAINT "PK_transport_id" PRIMARY KEY (transport_id);


--
-- TOC entry 2457 (class 2606 OID 86783)
-- Dependencies: 310 310 2644
-- Name: PK_uniform_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY fee_item_uniforms
    ADD CONSTRAINT "PK_uniform_id" PRIMARY KEY (uniform_id);


--
-- TOC entry 2453 (class 2606 OID 44168)
-- Dependencies: 307 307 2644
-- Name: PK_user_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY users
    ADD CONSTRAINT "PK_user_id" PRIMARY KEY (user_id);


--
-- TOC entry 2378 (class 2606 OID 44170)
-- Dependencies: 247 247 247 2644
-- Name: U_active_emp_cat; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY employee_cats
    ADD CONSTRAINT "U_active_emp_cat" UNIQUE (emp_cat_name, active);


--
-- TOC entry 2433 (class 2606 OID 44172)
-- Dependencies: 290 290 2644
-- Name: U_admission_number; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY students
    ADD CONSTRAINT "U_admission_number" UNIQUE (admission_number);


--
-- TOC entry 2463 (class 2606 OID 108117)
-- Dependencies: 312 312 2644
-- Name: U_bus_registration; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY buses
    ADD CONSTRAINT "U_bus_registration" UNIQUE (bus_registration);


--
-- TOC entry 2345 (class 2606 OID 44174)
-- Dependencies: 221 221 221 2644
-- Name: U_class_subject; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY class_subjects
    ADD CONSTRAINT "U_class_subject" UNIQUE (class_id, subject_id);


--
-- TOC entry 2374 (class 2606 OID 44176)
-- Dependencies: 245 245 2644
-- Name: U_dept_name; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY departments
    ADD CONSTRAINT "U_dept_name" UNIQUE (dept_name);


--
-- TOC entry 2382 (class 2606 OID 44178)
-- Dependencies: 249 249 2644
-- Name: U_emp_number; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY employees
    ADD CONSTRAINT "U_emp_number" UNIQUE (emp_number);


--
-- TOC entry 2391 (class 2606 OID 44180)
-- Dependencies: 253 253 253 2644
-- Name: U_exam_type_per_category; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY exam_types
    ADD CONSTRAINT "U_exam_type_per_category" UNIQUE (exam_type, class_cat_id);


--
-- TOC entry 2405 (class 2606 OID 44182)
-- Dependencies: 261 261 2644
-- Name: U_id_number; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY guardians
    ADD CONSTRAINT "U_id_number" UNIQUE (id_number);


--
-- TOC entry 2449 (class 2606 OID 44184)
-- Dependencies: 303 303 2644
-- Name: U_route; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY transport_routes
    ADD CONSTRAINT "U_route" UNIQUE (route);


--
-- TOC entry 2386 (class 2606 OID 44186)
-- Dependencies: 251 251 251 251 2644
-- Name: U_student_exam_mark; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY exam_marks
    ADD CONSTRAINT "U_student_exam_mark" UNIQUE (student_id, class_sub_exam_id, term_id);


--
-- TOC entry 2441 (class 2606 OID 44188)
-- Dependencies: 293 293 293 2644
-- Name: U_student_fee_item; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY student_fee_items
    ADD CONSTRAINT "U_student_fee_item" UNIQUE (student_id, fee_item_id);


--
-- TOC entry 2467 (class 2606 OID 109225)
-- Dependencies: 314 314 2644
-- Name: U_student_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY student_buses
    ADD CONSTRAINT "U_student_id" UNIQUE (student_id);


--
-- TOC entry 2437 (class 2606 OID 44190)
-- Dependencies: 291 291 291 2644
-- Name: U_subject_by_class_cat; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY subjects
    ADD CONSTRAINT "U_subject_by_class_cat" UNIQUE (subject_name, class_cat_id);


--
-- TOC entry 2341 (class 2606 OID 44192)
-- Dependencies: 219 219 219 2644
-- Name: U_subject_exam; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY class_subject_exams
    ADD CONSTRAINT "U_subject_exam" UNIQUE (class_subject_id, exam_type_id);


--
-- TOC entry 2370 (class 2606 OID 44194)
-- Dependencies: 243 243 243 2644
-- Name: U_term; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY terms
    ADD CONSTRAINT "U_term" UNIQUE (start_date, end_date);


--
-- TOC entry 2459 (class 2606 OID 86785)
-- Dependencies: 310 310 2644
-- Name: U_uniform; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY fee_item_uniforms
    ADD CONSTRAINT "U_uniform" UNIQUE (uniform);


--
-- TOC entry 2455 (class 2606 OID 44196)
-- Dependencies: 307 307 2644
-- Name: U_username; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY users
    ADD CONSTRAINT "U_username" UNIQUE (username);


--
-- TOC entry 2350 (class 2606 OID 44198)
-- Dependencies: 225 225 2644
-- Name: attachment_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY communication_attachments
    ADD CONSTRAINT attachment_id PRIMARY KEY (attachment_id);


--
-- TOC entry 2364 (class 2606 OID 44200)
-- Dependencies: 239 239 2644
-- Name: countries_pk; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY countries
    ADD CONSTRAINT countries_pk PRIMARY KEY (countries_id);


--
-- TOC entry 2401 (class 2606 OID 44202)
-- Dependencies: 258 258 2644
-- Name: grading_unique_grade2_contraint; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY grading2
    ADD CONSTRAINT grading_unique_grade2_contraint UNIQUE (grade2);


--
-- TOC entry 2397 (class 2606 OID 44204)
-- Dependencies: 257 257 2644
-- Name: grading_unique_grade_contraint; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY grading
    ADD CONSTRAINT grading_unique_grade_contraint UNIQUE (grade);


--
-- TOC entry 2337 (class 1259 OID 44205)
-- Dependencies: 217 217 2644
-- Name: U_active_class_cat; Type: INDEX; Schema: app; Owner: postgres; Tablespace: 
--

CREATE UNIQUE INDEX "U_active_class_cat" ON class_cats USING btree (class_cat_name) WHERE (active IS TRUE);


--
-- TOC entry 2348 (class 1259 OID 44206)
-- Dependencies: 223 223 223 2644
-- Name: U_active_class_name; Type: INDEX; Schema: app; Owner: postgres; Tablespace: 
--

CREATE UNIQUE INDEX "U_active_class_name" ON classes USING btree (class_name, class_cat_id) WHERE (active IS TRUE);


--
-- TOC entry 2387 (class 1259 OID 91191)
-- Dependencies: 251 251 2644
-- Name: app_exam_marks_index; Type: INDEX; Schema: app; Owner: postgres; Tablespace: 
--

CREATE INDEX app_exam_marks_index ON exam_marks USING btree (class_sub_exam_id, term_id);


--
-- TOC entry 2637 (class 2618 OID 43851)
-- Dependencies: 268 267 267 268 2411 267 267 267 267 268 269 2644
-- Name: _RETURN; Type: RULE; Schema: app; Owner: postgres
--

CREATE RULE "_RETURN" AS ON SELECT TO invoice_balances DO INSTEAD SELECT invoices.student_id, invoices.inv_id, invoices.inv_date, max(invoices.total_amount) AS total_due, COALESCE(sum(payments.amount), (0)::numeric) AS total_paid, (COALESCE(sum(payments.amount), (0)::numeric) - max(invoices.total_amount)) AS balance, invoices.due_date, CASE WHEN ((invoices.due_date < (now())::date) AND ((COALESCE(sum(payments.amount), (0)::numeric) - max(invoices.total_amount)) < (0)::numeric)) THEN true ELSE false END AS past_due, invoices.canceled FROM (invoices LEFT JOIN payments ON (((invoices.inv_id = payments.inv_id) AND (payments.reversed IS FALSE)))) GROUP BY invoices.student_id, invoices.inv_id;


--
-- TOC entry 2638 (class 2618 OID 43870)
-- Dependencies: 271 267 267 271 271 270 267 267 270 268 268 267 267 267 2411 272 2644
-- Name: _RETURN; Type: RULE; Schema: app; Owner: postgres
--

CREATE RULE "_RETURN" AS ON SELECT TO invoice_balances2 DO INSTEAD SELECT invoices.student_id, invoices.inv_id, invoices.inv_date, max(invoices.total_amount) AS total_due, COALESCE(sum(payment_inv_items.amount), (0)::numeric) AS total_paid, (COALESCE(sum(payment_inv_items.amount), (0)::numeric) - max(invoices.total_amount)) AS balance, invoices.due_date, CASE WHEN ((invoices.due_date < (now())::date) AND ((COALESCE(sum(payment_inv_items.amount), (0)::numeric) - max(invoices.total_amount)) < (0)::numeric)) THEN true ELSE false END AS past_due, invoices.canceled, invoices.term_id FROM (invoices JOIN (invoice_line_items LEFT JOIN (payment_inv_items JOIN payments ON (((payment_inv_items.payment_id = payments.payment_id) AND (payments.reversed IS FALSE)))) ON ((invoice_line_items.inv_item_id = payment_inv_items.inv_item_id))) ON ((invoices.inv_id = invoice_line_items.inv_id))) GROUP BY invoices.student_id, invoices.inv_id;


--
-- TOC entry 2490 (class 2606 OID 44207)
-- Dependencies: 2351 237 227 2644
-- Name: FK_audience_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY communications
    ADD CONSTRAINT "FK_audience_id" FOREIGN KEY (audience_id) REFERENCES communication_audience(audience_id);


--
-- TOC entry 2476 (class 2606 OID 44212)
-- Dependencies: 223 2346 215 2644
-- Name: FK_blog_class; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY blogs
    ADD CONSTRAINT "FK_blog_class" FOREIGN KEY (class_id) REFERENCES classes(class_id);


--
-- TOC entry 2477 (class 2606 OID 44217)
-- Dependencies: 249 215 2379 2644
-- Name: FK_blog_teacher; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY blogs
    ADD CONSTRAINT "FK_blog_teacher" FOREIGN KEY (teacher_id) REFERENCES employees(emp_id);


--
-- TOC entry 2532 (class 2606 OID 109226)
-- Dependencies: 314 2460 312 2644
-- Name: FK_bus_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY student_buses
    ADD CONSTRAINT "FK_bus_id" FOREIGN KEY (bus_id) REFERENCES buses(bus_id);


--
-- TOC entry 2482 (class 2606 OID 44222)
-- Dependencies: 223 217 2335 2644
-- Name: FK_class_cat_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY classes
    ADD CONSTRAINT "FK_class_cat_id" FOREIGN KEY (class_cat_id) REFERENCES class_cats(class_cat_id);


--
-- TOC entry 2523 (class 2606 OID 44227)
-- Dependencies: 291 217 2335 2644
-- Name: FK_class_cat_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY subjects
    ADD CONSTRAINT "FK_class_cat_id" FOREIGN KEY (class_cat_id) REFERENCES class_cats(class_cat_id);


--
-- TOC entry 2519 (class 2606 OID 44232)
-- Dependencies: 2346 288 223 2644
-- Name: FK_class_history_class; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY student_class_history
    ADD CONSTRAINT "FK_class_history_class" FOREIGN KEY (class_id) REFERENCES classes(class_id);


--
-- TOC entry 2520 (class 2606 OID 44237)
-- Dependencies: 290 288 2430 2644
-- Name: FK_class_history_student; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY student_class_history
    ADD CONSTRAINT "FK_class_history_student" FOREIGN KEY (student_id) REFERENCES students(student_id);


--
-- TOC entry 2478 (class 2606 OID 44242)
-- Dependencies: 253 219 2388 2644
-- Name: FK_class_subect_exam_type; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY class_subject_exams
    ADD CONSTRAINT "FK_class_subect_exam_type" FOREIGN KEY (exam_type_id) REFERENCES exam_types(exam_type_id);


--
-- TOC entry 2479 (class 2606 OID 44247)
-- Dependencies: 221 219 2342 2644
-- Name: FK_class_subject; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY class_subject_exams
    ADD CONSTRAINT "FK_class_subject" FOREIGN KEY (class_subject_id) REFERENCES class_subjects(class_subject_id);


--
-- TOC entry 2480 (class 2606 OID 44252)
-- Dependencies: 223 221 2346 2644
-- Name: FK_class_subject_class; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY class_subjects
    ADD CONSTRAINT "FK_class_subject_class" FOREIGN KEY (class_id) REFERENCES classes(class_id);


--
-- TOC entry 2501 (class 2606 OID 44257)
-- Dependencies: 219 251 2338 2644
-- Name: FK_class_subject_exam; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY exam_marks
    ADD CONSTRAINT "FK_class_subject_exam" FOREIGN KEY (class_sub_exam_id) REFERENCES class_subject_exams(class_sub_exam_id);


--
-- TOC entry 2481 (class 2606 OID 44262)
-- Dependencies: 291 221 2434 2644
-- Name: FK_class_subject_subject; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY class_subjects
    ADD CONSTRAINT "FK_class_subject_subject" FOREIGN KEY (subject_id) REFERENCES subjects(subject_id);


--
-- TOC entry 2483 (class 2606 OID 44267)
-- Dependencies: 223 2379 249 2644
-- Name: FK_class_teacher; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY classes
    ADD CONSTRAINT "FK_class_teacher" FOREIGN KEY (teacher_id) REFERENCES employees(emp_id);


--
-- TOC entry 2486 (class 2606 OID 44272)
-- Dependencies: 231 2346 223 2644
-- Name: FK_com_class_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY communication_feedback
    ADD CONSTRAINT "FK_com_class_id" FOREIGN KEY (class_id) REFERENCES classes(class_id);


--
-- TOC entry 2491 (class 2606 OID 44277)
-- Dependencies: 237 2346 223 2644
-- Name: FK_com_class_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY communications
    ADD CONSTRAINT "FK_com_class_id" FOREIGN KEY (class_id) REFERENCES classes(class_id);


--
-- TOC entry 2487 (class 2606 OID 44282)
-- Dependencies: 2402 231 261 2644
-- Name: FK_com_guardian_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY communication_feedback
    ADD CONSTRAINT "FK_com_guardian_id" FOREIGN KEY (guardian_id) REFERENCES guardians(guardian_id);


--
-- TOC entry 2492 (class 2606 OID 44287)
-- Dependencies: 237 2402 261 2644
-- Name: FK_com_guardian_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY communications
    ADD CONSTRAINT "FK_com_guardian_id" FOREIGN KEY (guardian_id) REFERENCES guardians(guardian_id);


--
-- TOC entry 2493 (class 2606 OID 44292)
-- Dependencies: 237 2379 249 2644
-- Name: FK_com_message_from; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY communications
    ADD CONSTRAINT "FK_com_message_from" FOREIGN KEY (message_from) REFERENCES employees(emp_id);


--
-- TOC entry 2488 (class 2606 OID 44297)
-- Dependencies: 290 231 2430 2644
-- Name: FK_com_student_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY communication_feedback
    ADD CONSTRAINT "FK_com_student_id" FOREIGN KEY (student_id) REFERENCES students(student_id);


--
-- TOC entry 2494 (class 2606 OID 44302)
-- Dependencies: 290 237 2430 2644
-- Name: FK_com_student_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY communications
    ADD CONSTRAINT "FK_com_student_id" FOREIGN KEY (student_id) REFERENCES students(student_id);


--
-- TOC entry 2495 (class 2606 OID 44307)
-- Dependencies: 235 237 2359 2644
-- Name: FK_com_type_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY communications
    ADD CONSTRAINT "FK_com_type_id" FOREIGN KEY (com_type_id) REFERENCES communication_types(com_type_id);


--
-- TOC entry 2485 (class 2606 OID 44312)
-- Dependencies: 2361 229 237 2644
-- Name: FK_comm_email_comm; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY communication_emails
    ADD CONSTRAINT "FK_comm_email_comm" FOREIGN KEY (com_id) REFERENCES communications(com_id);


--
-- TOC entry 2489 (class 2606 OID 44317)
-- Dependencies: 2361 237 233 2644
-- Name: FK_comm_sms_comm; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY communication_sms
    ADD CONSTRAINT "FK_comm_sms_comm" FOREIGN KEY (com_id) REFERENCES communications(com_id);


--
-- TOC entry 2497 (class 2606 OID 44322)
-- Dependencies: 241 268 2412 2644
-- Name: FK_credit_payment; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY credits
    ADD CONSTRAINT "FK_credit_payment" FOREIGN KEY (payment_id) REFERENCES payments(payment_id);


--
-- TOC entry 2498 (class 2606 OID 44327)
-- Dependencies: 241 290 2430 2644
-- Name: FK_credit_student; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY credits
    ADD CONSTRAINT "FK_credit_student" FOREIGN KEY (student_id) REFERENCES students(student_id);


--
-- TOC entry 2496 (class 2606 OID 44332)
-- Dependencies: 237 209 2327 2644
-- Name: FK_email_post_status; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY communications
    ADD CONSTRAINT "FK_email_post_status" FOREIGN KEY (post_status_id) REFERENCES blog_post_statuses(post_status_id);


--
-- TOC entry 2499 (class 2606 OID 44337)
-- Dependencies: 2375 247 249 2644
-- Name: FK_emp_cat_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY employees
    ADD CONSTRAINT "FK_emp_cat_id" FOREIGN KEY (emp_cat_id) REFERENCES employee_cats(emp_cat_id);


--
-- TOC entry 2500 (class 2606 OID 44342)
-- Dependencies: 2371 249 245 2644
-- Name: FK_emp_dept_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY employees
    ADD CONSTRAINT "FK_emp_dept_id" FOREIGN KEY (dept_id) REFERENCES departments(dept_id);


--
-- TOC entry 2502 (class 2606 OID 44347)
-- Dependencies: 2430 290 251 2644
-- Name: FK_exam_student; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY exam_marks
    ADD CONSTRAINT "FK_exam_student" FOREIGN KEY (student_id) REFERENCES students(student_id);


--
-- TOC entry 2503 (class 2606 OID 44352)
-- Dependencies: 251 2367 243 2644
-- Name: FK_exam_term; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY exam_marks
    ADD CONSTRAINT "FK_exam_term" FOREIGN KEY (term_id) REFERENCES terms(term_id);


--
-- TOC entry 2504 (class 2606 OID 44357)
-- Dependencies: 2335 253 217 2644
-- Name: FK_exam_type_class_cat; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY exam_types
    ADD CONSTRAINT "FK_exam_type_class_cat" FOREIGN KEY (class_cat_id) REFERENCES class_cats(class_cat_id);


--
-- TOC entry 2505 (class 2606 OID 44362)
-- Dependencies: 263 221 2342 2644
-- Name: FK_homework_class_subject; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY homework
    ADD CONSTRAINT "FK_homework_class_subject" FOREIGN KEY (class_subject_id) REFERENCES class_subjects(class_subject_id);


--
-- TOC entry 2506 (class 2606 OID 44367)
-- Dependencies: 263 2327 209 2644
-- Name: FK_homework_post_status; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY homework
    ADD CONSTRAINT "FK_homework_post_status" FOREIGN KEY (post_status_id) REFERENCES blog_post_statuses(post_status_id);


--
-- TOC entry 2521 (class 2606 OID 44372)
-- Dependencies: 290 2408 265 2644
-- Name: FK_installment_option; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY students
    ADD CONSTRAINT "FK_installment_option" FOREIGN KEY (installment_option_id) REFERENCES installment_options(installment_id);


--
-- TOC entry 2509 (class 2606 OID 44377)
-- Dependencies: 270 2438 293 2644
-- Name: FK_inv_item_fee_item; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY invoice_line_items
    ADD CONSTRAINT "FK_inv_item_fee_item" FOREIGN KEY (student_fee_item_id) REFERENCES student_fee_items(student_fee_item_id);


--
-- TOC entry 2510 (class 2606 OID 44382)
-- Dependencies: 2410 267 270 2644
-- Name: FK_inv_item_invoice; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY invoice_line_items
    ADD CONSTRAINT "FK_inv_item_invoice" FOREIGN KEY (inv_id) REFERENCES invoices(inv_id);


--
-- TOC entry 2507 (class 2606 OID 44387)
-- Dependencies: 2430 267 290 2644
-- Name: FK_invoice_student; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY invoices
    ADD CONSTRAINT "FK_invoice_student" FOREIGN KEY (student_id) REFERENCES students(student_id);


--
-- TOC entry 2511 (class 2606 OID 44392)
-- Dependencies: 271 2412 268 2644
-- Name: FK_payment_fee_item_payment; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY payment_inv_items
    ADD CONSTRAINT "FK_payment_fee_item_payment" FOREIGN KEY (payment_id) REFERENCES payments(payment_id);


--
-- TOC entry 2512 (class 2606 OID 44397)
-- Dependencies: 2410 267 271 2644
-- Name: FK_payment_inv; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY payment_inv_items
    ADD CONSTRAINT "FK_payment_inv" FOREIGN KEY (inv_id) REFERENCES invoices(inv_id);


--
-- TOC entry 2513 (class 2606 OID 44402)
-- Dependencies: 270 2414 271 2644
-- Name: FK_payment_inv_item; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY payment_inv_items
    ADD CONSTRAINT "FK_payment_inv_item" FOREIGN KEY (inv_item_id) REFERENCES invoice_line_items(inv_item_id);


--
-- TOC entry 2514 (class 2606 OID 44407)
-- Dependencies: 293 2438 281 2644
-- Name: FK_payment_item; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY payment_replacement_items
    ADD CONSTRAINT "FK_payment_item" FOREIGN KEY (student_fee_item_id) REFERENCES student_fee_items(student_fee_item_id);


--
-- TOC entry 2515 (class 2606 OID 44412)
-- Dependencies: 2412 281 268 2644
-- Name: FK_payment_replace_item_payment; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY payment_replacement_items
    ADD CONSTRAINT "FK_payment_replace_item_payment" FOREIGN KEY (payment_id) REFERENCES payments(payment_id);


--
-- TOC entry 2508 (class 2606 OID 44417)
-- Dependencies: 2430 290 268 2644
-- Name: FK_payments_student; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY payments
    ADD CONSTRAINT "FK_payments_student" FOREIGN KEY (student_id) REFERENCES students(student_id);


--
-- TOC entry 2472 (class 2606 OID 44422)
-- Dependencies: 213 2333 215 2644
-- Name: FK_post_blog; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY blog_posts
    ADD CONSTRAINT "FK_post_blog" FOREIGN KEY (blog_id) REFERENCES blogs(blog_id);


--
-- TOC entry 2473 (class 2606 OID 44427)
-- Dependencies: 209 2327 213 2644
-- Name: FK_post_status; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY blog_posts
    ADD CONSTRAINT "FK_post_status" FOREIGN KEY (post_status_id) REFERENCES blog_post_statuses(post_status_id);


--
-- TOC entry 2474 (class 2606 OID 44432)
-- Dependencies: 213 2329 211 2644
-- Name: FK_post_type; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY blog_posts
    ADD CONSTRAINT "FK_post_type" FOREIGN KEY (post_type_id) REFERENCES blog_post_types(post_type_id);


--
-- TOC entry 2475 (class 2606 OID 44437)
-- Dependencies: 213 2379 249 2644
-- Name: FK_posted_by; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY blog_posts
    ADD CONSTRAINT "FK_posted_by" FOREIGN KEY (created_by) REFERENCES employees(emp_id);


--
-- TOC entry 2516 (class 2606 OID 44442)
-- Dependencies: 223 285 2346 2644
-- Name: FK_report_class; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY report_cards
    ADD CONSTRAINT "FK_report_class" FOREIGN KEY (class_id) REFERENCES classes(class_id);


--
-- TOC entry 2517 (class 2606 OID 44447)
-- Dependencies: 285 2430 290 2644
-- Name: FK_report_student; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY report_cards
    ADD CONSTRAINT "FK_report_student" FOREIGN KEY (student_id) REFERENCES students(student_id);


--
-- TOC entry 2518 (class 2606 OID 44452)
-- Dependencies: 2367 285 243 2644
-- Name: FK_report_term; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY report_cards
    ADD CONSTRAINT "FK_report_term" FOREIGN KEY (term_id) REFERENCES terms(term_id);


--
-- TOC entry 2525 (class 2606 OID 44457)
-- Dependencies: 293 255 2392 2644
-- Name: FK_student_fee_items; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY student_fee_items
    ADD CONSTRAINT "FK_student_fee_items" FOREIGN KEY (fee_item_id) REFERENCES fee_items(fee_item_id);


--
-- TOC entry 2526 (class 2606 OID 44462)
-- Dependencies: 290 293 2430 2644
-- Name: FK_student_fee_items_student; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY student_fee_items
    ADD CONSTRAINT "FK_student_fee_items_student" FOREIGN KEY (student_id) REFERENCES students(student_id);


--
-- TOC entry 2527 (class 2606 OID 44467)
-- Dependencies: 2402 261 295 2644
-- Name: FK_student_guardian_guardian; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY student_guardians
    ADD CONSTRAINT "FK_student_guardian_guardian" FOREIGN KEY (guardian_id) REFERENCES guardians(guardian_id);


--
-- TOC entry 2528 (class 2606 OID 44472)
-- Dependencies: 2430 290 295 2644
-- Name: FK_student_guardian_student; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY student_guardians
    ADD CONSTRAINT "FK_student_guardian_student" FOREIGN KEY (student_id) REFERENCES students(student_id);


--
-- TOC entry 2529 (class 2606 OID 44477)
-- Dependencies: 290 297 2430 2644
-- Name: FK_student_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY student_medical_history
    ADD CONSTRAINT "FK_student_id" FOREIGN KEY (student_id) REFERENCES students(student_id);


--
-- TOC entry 2533 (class 2606 OID 109231)
-- Dependencies: 290 2430 314 2644
-- Name: FK_student_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY student_buses
    ADD CONSTRAINT "FK_student_id" FOREIGN KEY (student_id) REFERENCES students(student_id);


--
-- TOC entry 2522 (class 2606 OID 44482)
-- Dependencies: 303 290 2446 2644
-- Name: FK_student_route; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY students
    ADD CONSTRAINT "FK_student_route" FOREIGN KEY (transport_route_id) REFERENCES transport_routes(transport_id);


--
-- TOC entry 2524 (class 2606 OID 44487)
-- Dependencies: 2379 249 291 2644
-- Name: FK_subject_teacher; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY subjects
    ADD CONSTRAINT "FK_subject_teacher" FOREIGN KEY (teacher_id) REFERENCES employees(emp_id);


--
-- TOC entry 2530 (class 2606 OID 108123)
-- Dependencies: 312 249 2379 2644
-- Name: bus_driver; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY buses
    ADD CONSTRAINT bus_driver FOREIGN KEY (bus_driver) REFERENCES employees(emp_id) MATCH FULL;


--
-- TOC entry 2531 (class 2606 OID 108128)
-- Dependencies: 312 2379 249 2644
-- Name: bus_guide; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY buses
    ADD CONSTRAINT bus_guide FOREIGN KEY (bus_guide) REFERENCES employees(emp_id) MATCH FULL;


--
-- TOC entry 2534 (class 2606 OID 114280)
-- Dependencies: 2460 312 318 2644
-- Name: bus_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY schoolbus_trips
    ADD CONSTRAINT bus_id FOREIGN KEY (bus_id) REFERENCES buses(bus_id) MATCH FULL;


--
-- TOC entry 2484 (class 2606 OID 44492)
-- Dependencies: 225 2361 237 2644
-- Name: com_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY communication_attachments
    ADD CONSTRAINT com_id FOREIGN KEY (com_id) REFERENCES communications(com_id) MATCH FULL;


--
-- TOC entry 2649 (class 0 OID 0)
-- Dependencies: 9
-- Name: public; Type: ACL; Schema: -; Owner: postgres
--

REVOKE ALL ON SCHEMA public FROM PUBLIC;
REVOKE ALL ON SCHEMA public FROM postgres;
GRANT ALL ON SCHEMA public TO postgres;
GRANT ALL ON SCHEMA public TO PUBLIC;


-- Completed on 2019-08-02 08:45:29

--
-- PostgreSQL database dump complete
--

