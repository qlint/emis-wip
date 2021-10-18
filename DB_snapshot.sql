--
-- PostgreSQL database dump
--

-- Dumped from database version 12.1
-- Dumped by pg_dump version 12.1

-- Started on 2021-09-01 09:13:03

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
-- TOC entry 37 (class 2615 OID 114769)
-- Name: app; Type: SCHEMA; Schema: -; Owner: postgres
--

CREATE SCHEMA app;


ALTER SCHEMA app OWNER TO postgres;

--
-- TOC entry 2 (class 3079 OID 114770)
-- Name: tablefunc; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS tablefunc WITH SCHEMA public;


--
-- TOC entry 3811 (class 0 OID 0)
-- Dependencies: 2
-- Name: EXTENSION tablefunc; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION tablefunc IS 'functions that manipulate whole tables, including crosstab';


--
-- TOC entry 395 (class 1255 OID 114791)
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
-- TOC entry 396 (class 1255 OID 114792)
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

--
-- TOC entry 397 (class 1255 OID 114793)
-- Name: date_text_to_date(character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.date_text_to_date(str character varying) RETURNS text
    LANGUAGE plpgsql
    AS $$
begin
    return TO_CHAR(str :: DATE, 'dd/mm/yyyy');
exception
    when others then return str;
end $$;


ALTER FUNCTION public.date_text_to_date(str character varying) OWNER TO postgres;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- TOC entry 376 (class 1259 OID 141655)
-- Name: absenteeism; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.absenteeism (
    absentee_id integer NOT NULL,
    student_id integer NOT NULL,
    reason character varying,
    message character varying NOT NULL,
    start_date character varying,
    end_date character varying,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    modified_date timestamp without time zone,
    starting date,
    ending date
);


ALTER TABLE app.absenteeism OWNER TO postgres;

--
-- TOC entry 375 (class 1259 OID 141653)
-- Name: absenteeism_absentee_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.absenteeism_absentee_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.absenteeism_absentee_id_seq OWNER TO postgres;

--
-- TOC entry 3812 (class 0 OID 0)
-- Dependencies: 375
-- Name: absenteeism_absentee_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.absenteeism_absentee_id_seq OWNED BY app.absenteeism.absentee_id;


--
-- TOC entry 243 (class 1259 OID 114794)
-- Name: blog_post_statuses; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.blog_post_statuses (
    post_status_id integer NOT NULL,
    post_status character varying NOT NULL
);


ALTER TABLE app.blog_post_statuses OWNER TO postgres;

--
-- TOC entry 244 (class 1259 OID 114800)
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
-- TOC entry 3813 (class 0 OID 0)
-- Dependencies: 244
-- Name: blog_post_statuses_post_status_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.blog_post_statuses_post_status_id_seq OWNED BY app.blog_post_statuses.post_status_id;


--
-- TOC entry 245 (class 1259 OID 114802)
-- Name: blog_post_types; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.blog_post_types (
    post_type_id integer NOT NULL,
    post_type character varying NOT NULL
);


ALTER TABLE app.blog_post_types OWNER TO postgres;

--
-- TOC entry 246 (class 1259 OID 114808)
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
-- TOC entry 3814 (class 0 OID 0)
-- Dependencies: 246
-- Name: blog_post_types_post_type_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.blog_post_types_post_type_id_seq OWNED BY app.blog_post_types.post_type_id;


--
-- TOC entry 247 (class 1259 OID 114810)
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
-- TOC entry 248 (class 1259 OID 114817)
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
-- TOC entry 3815 (class 0 OID 0)
-- Dependencies: 248
-- Name: blog_posts_post_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.blog_posts_post_id_seq OWNED BY app.blog_posts.post_id;


--
-- TOC entry 249 (class 1259 OID 114819)
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
-- TOC entry 250 (class 1259 OID 114825)
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
-- TOC entry 3816 (class 0 OID 0)
-- Dependencies: 250
-- Name: blogs_blog_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.blogs_blog_id_seq OWNED BY app.blogs.blog_id;


--
-- TOC entry 251 (class 1259 OID 114827)
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
    destinations character varying,
    bus_description character varying,
    bus_capacity integer
);


ALTER TABLE app.buses OWNER TO postgres;

--
-- TOC entry 252 (class 1259 OID 114835)
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
-- TOC entry 3817 (class 0 OID 0)
-- Dependencies: 252
-- Name: buses_bus_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.buses_bus_id_seq OWNED BY app.buses.bus_id;


--
-- TOC entry 253 (class 1259 OID 114837)
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
-- TOC entry 254 (class 1259 OID 114845)
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
-- TOC entry 3818 (class 0 OID 0)
-- Dependencies: 254
-- Name: class_cats_class_cat_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.class_cats_class_cat_id_seq OWNED BY app.class_cats.class_cat_id;


--
-- TOC entry 255 (class 1259 OID 114847)
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
-- TOC entry 256 (class 1259 OID 114852)
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
-- TOC entry 3819 (class 0 OID 0)
-- Dependencies: 256
-- Name: class_subject_exams_class_sub_exam_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.class_subject_exams_class_sub_exam_id_seq OWNED BY app.class_subject_exams.class_sub_exam_id;


--
-- TOC entry 257 (class 1259 OID 114854)
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
-- TOC entry 258 (class 1259 OID 114859)
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
-- TOC entry 3820 (class 0 OID 0)
-- Dependencies: 258
-- Name: class_subjects_class_subject_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.class_subjects_class_subject_id_seq OWNED BY app.class_subjects.class_subject_id;


--
-- TOC entry 259 (class 1259 OID 114861)
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
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    subject_id integer
);


ALTER TABLE app.class_timetables OWNER TO postgres;

--
-- TOC entry 260 (class 1259 OID 114868)
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
-- TOC entry 3821 (class 0 OID 0)
-- Dependencies: 260
-- Name: class_timetables_class_timetable_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.class_timetables_class_timetable_id_seq OWNED BY app.class_timetables.class_timetable_id;


--
-- TOC entry 261 (class 1259 OID 114870)
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
-- TOC entry 262 (class 1259 OID 114878)
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
-- TOC entry 3822 (class 0 OID 0)
-- Dependencies: 262
-- Name: classes_class_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.classes_class_id_seq OWNED BY app.classes.class_id;


--
-- TOC entry 263 (class 1259 OID 114880)
-- Name: communication_attachments; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.communication_attachments (
    com_id integer,
    attachment_id integer NOT NULL,
    attachment character varying
);


ALTER TABLE app.communication_attachments OWNER TO postgres;

--
-- TOC entry 264 (class 1259 OID 114886)
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
-- TOC entry 3823 (class 0 OID 0)
-- Dependencies: 264
-- Name: communication_attachments_attachment_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.communication_attachments_attachment_id_seq OWNED BY app.communication_attachments.attachment_id;


--
-- TOC entry 265 (class 1259 OID 114888)
-- Name: communication_audience; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.communication_audience (
    audience_id integer NOT NULL,
    audience character varying NOT NULL,
    module character varying
);


ALTER TABLE app.communication_audience OWNER TO postgres;

--
-- TOC entry 266 (class 1259 OID 114894)
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
-- TOC entry 3824 (class 0 OID 0)
-- Dependencies: 266
-- Name: communication_audience_audience_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.communication_audience_audience_id_seq OWNED BY app.communication_audience.audience_id;


--
-- TOC entry 267 (class 1259 OID 114896)
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
-- TOC entry 268 (class 1259 OID 114904)
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
-- TOC entry 3825 (class 0 OID 0)
-- Dependencies: 268
-- Name: communication_emails_email_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.communication_emails_email_id_seq OWNED BY app.communication_emails.email_id;


--
-- TOC entry 269 (class 1259 OID 114906)
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
-- TOC entry 270 (class 1259 OID 114914)
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
-- TOC entry 3826 (class 0 OID 0)
-- Dependencies: 270
-- Name: communication_feedback_com_feedback_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.communication_feedback_com_feedback_id_seq OWNED BY app.communication_feedback.com_feedback_id;


--
-- TOC entry 271 (class 1259 OID 114916)
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
-- TOC entry 272 (class 1259 OID 114924)
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
-- TOC entry 3827 (class 0 OID 0)
-- Dependencies: 272
-- Name: communication_sms_sms_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.communication_sms_sms_id_seq OWNED BY app.communication_sms.sms_id;


--
-- TOC entry 273 (class 1259 OID 114926)
-- Name: communication_types; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.communication_types (
    com_type_id integer NOT NULL,
    com_type character varying NOT NULL
);


ALTER TABLE app.communication_types OWNER TO postgres;

--
-- TOC entry 274 (class 1259 OID 114932)
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
-- TOC entry 3828 (class 0 OID 0)
-- Dependencies: 274
-- Name: communication_types_com_type_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.communication_types_com_type_id_seq OWNED BY app.communication_types.com_type_id;


--
-- TOC entry 275 (class 1259 OID 114934)
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
    to_employee integer,
    seen_count integer,
    seen_by character varying
);


ALTER TABLE app.communications OWNER TO postgres;

--
-- TOC entry 276 (class 1259 OID 114942)
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
-- TOC entry 3829 (class 0 OID 0)
-- Dependencies: 276
-- Name: communications_com_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.communications_com_id_seq OWNED BY app.communications.com_id;


--
-- TOC entry 374 (class 1259 OID 118325)
-- Name: communications_failed_sms; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.communications_failed_sms (
    failed_sms_id integer NOT NULL,
    subscriber_name character varying,
    message_by character varying,
    message_text character varying,
    recipient_name character varying,
    phone_number character varying,
    message_date character varying
);


ALTER TABLE app.communications_failed_sms OWNER TO postgres;

--
-- TOC entry 373 (class 1259 OID 118323)
-- Name: communications_failed_sms_failed_sms_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.communications_failed_sms_failed_sms_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.communications_failed_sms_failed_sms_id_seq OWNER TO postgres;

--
-- TOC entry 3830 (class 0 OID 0)
-- Dependencies: 373
-- Name: communications_failed_sms_failed_sms_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.communications_failed_sms_failed_sms_id_seq OWNED BY app.communications_failed_sms.failed_sms_id;


--
-- TOC entry 277 (class 1259 OID 114944)
-- Name: countries; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.countries (
    countries_id integer NOT NULL,
    countries_name character varying(255) NOT NULL,
    countries_iso_code_2 character(2) NOT NULL,
    countries_iso_code_3 character(3) NOT NULL,
    address_format_id integer NOT NULL,
    currency_name character varying,
    currency_symbol character varying,
    curriculum character varying
);


ALTER TABLE app.countries OWNER TO postgres;

--
-- TOC entry 278 (class 1259 OID 114947)
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
-- TOC entry 3831 (class 0 OID 0)
-- Dependencies: 278
-- Name: countries_countries_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.countries_countries_id_seq OWNED BY app.countries.countries_id;


--
-- TOC entry 279 (class 1259 OID 114949)
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
    payment_id integer,
    in_quickbooks boolean DEFAULT false NOT NULL
);


ALTER TABLE app.credits OWNER TO postgres;

--
-- TOC entry 280 (class 1259 OID 114957)
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
-- TOC entry 3832 (class 0 OID 0)
-- Dependencies: 280
-- Name: credits_credit_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.credits_credit_id_seq OWNED BY app.credits.credit_id;


--
-- TOC entry 281 (class 1259 OID 114959)
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
-- TOC entry 282 (class 1259 OID 114966)
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
-- TOC entry 283 (class 1259 OID 114970)
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
-- TOC entry 284 (class 1259 OID 114978)
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
-- TOC entry 3833 (class 0 OID 0)
-- Dependencies: 284
-- Name: departments_dept_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.departments_dept_id_seq OWNED BY app.departments.dept_id;


--
-- TOC entry 380 (class 1259 OID 167610)
-- Name: disciplinary; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.disciplinary (
    disciplinary_id integer NOT NULL,
    student_id integer NOT NULL,
    emp_id integer NOT NULL,
    notes character varying NOT NULL,
    class_id integer,
    term_id integer,
    other_students character varying,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    modified_date timestamp without time zone
);


ALTER TABLE app.disciplinary OWNER TO postgres;

--
-- TOC entry 379 (class 1259 OID 167608)
-- Name: disciplinary_disciplinary_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.disciplinary_disciplinary_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.disciplinary_disciplinary_id_seq OWNER TO postgres;

--
-- TOC entry 3834 (class 0 OID 0)
-- Dependencies: 379
-- Name: disciplinary_disciplinary_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.disciplinary_disciplinary_id_seq OWNED BY app.disciplinary.disciplinary_id;


--
-- TOC entry 285 (class 1259 OID 114980)
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
-- TOC entry 286 (class 1259 OID 114988)
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
-- TOC entry 3835 (class 0 OID 0)
-- Dependencies: 286
-- Name: employee_cats_emp_cat_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.employee_cats_emp_cat_id_seq OWNED BY app.employee_cats.emp_cat_id;


--
-- TOC entry 287 (class 1259 OID 114990)
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
    telephone2 character varying,
    super_teacher boolean
);


ALTER TABLE app.employees OWNER TO postgres;

--
-- TOC entry 288 (class 1259 OID 114998)
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
-- TOC entry 3836 (class 0 OID 0)
-- Dependencies: 288
-- Name: employees_emp_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.employees_emp_id_seq OWNED BY app.employees.emp_id;


--
-- TOC entry 289 (class 1259 OID 115000)
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
-- TOC entry 290 (class 1259 OID 115004)
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
-- TOC entry 3837 (class 0 OID 0)
-- Dependencies: 290
-- Name: exam_marks_exam_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.exam_marks_exam_id_seq OWNED BY app.exam_marks.exam_id;


--
-- TOC entry 291 (class 1259 OID 115006)
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
-- TOC entry 292 (class 1259 OID 115014)
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
-- TOC entry 3838 (class 0 OID 0)
-- Dependencies: 292
-- Name: exam_types_exam_type_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.exam_types_exam_type_id_seq OWNED BY app.exam_types.exam_type_id;


--
-- TOC entry 293 (class 1259 OID 115016)
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
-- TOC entry 294 (class 1259 OID 115024)
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
-- TOC entry 3839 (class 0 OID 0)
-- Dependencies: 294
-- Name: fee_item_uniforms_uniform_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.fee_item_uniforms_uniform_id_seq OWNED BY app.fee_item_uniforms.uniform_id;


--
-- TOC entry 295 (class 1259 OID 115026)
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
    modified_by integer,
    in_quickbooks boolean DEFAULT false NOT NULL,
    year character varying
);


ALTER TABLE app.fee_items OWNER TO postgres;

--
-- TOC entry 296 (class 1259 OID 115038)
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
-- TOC entry 3840 (class 0 OID 0)
-- Dependencies: 296
-- Name: fee_items_fee_item_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.fee_items_fee_item_id_seq OWNED BY app.fee_items.fee_item_id;


--
-- TOC entry 383 (class 1259 OID 179689)
-- Name: forgot_pwd; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.forgot_pwd (
    usr_phone character varying NOT NULL,
    temp_pwd character varying NOT NULL,
    user_id integer NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE app.forgot_pwd OWNER TO postgres;

--
-- TOC entry 297 (class 1259 OID 115040)
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
-- TOC entry 298 (class 1259 OID 115046)
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
-- TOC entry 299 (class 1259 OID 115052)
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
-- TOC entry 3841 (class 0 OID 0)
-- Dependencies: 299
-- Name: grading2_grade2_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.grading2_grade2_id_seq OWNED BY app.grading2.grade2_id;


--
-- TOC entry 300 (class 1259 OID 115054)
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
-- TOC entry 3842 (class 0 OID 0)
-- Dependencies: 300
-- Name: grading_grade_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.grading_grade_id_seq OWNED BY app.grading.grade_id;


--
-- TOC entry 301 (class 1259 OID 115056)
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
-- TOC entry 302 (class 1259 OID 115064)
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
-- TOC entry 3843 (class 0 OID 0)
-- Dependencies: 302
-- Name: guardians_guardian_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.guardians_guardian_id_seq OWNED BY app.guardians.guardian_id;


--
-- TOC entry 303 (class 1259 OID 115066)
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
    modified_by integer,
    students character varying,
    seen_count integer,
    seen_by character varying
);


ALTER TABLE app.homework OWNER TO postgres;

--
-- TOC entry 304 (class 1259 OID 115073)
-- Name: homework_feedback; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.homework_feedback (
    homework_feedback_id integer NOT NULL,
    homework_id integer,
    title character varying,
    body character varying,
    message character varying,
    homework_attachment character varying,
    student_attachment character varying,
    assigned_date character varying,
    due_date character varying,
    added_by character varying,
    emp_id integer,
    subject_name character varying,
    subject_id integer,
    class_id integer,
    class_name character varying,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    student_id integer,
    guardian_id integer
);


ALTER TABLE app.homework_feedback OWNER TO postgres;

--
-- TOC entry 305 (class 1259 OID 115080)
-- Name: homework_feedback_homework_feedback_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.homework_feedback_homework_feedback_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.homework_feedback_homework_feedback_id_seq OWNER TO postgres;

--
-- TOC entry 3844 (class 0 OID 0)
-- Dependencies: 305
-- Name: homework_feedback_homework_feedback_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.homework_feedback_homework_feedback_id_seq OWNED BY app.homework_feedback.homework_feedback_id;


--
-- TOC entry 306 (class 1259 OID 115082)
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
-- TOC entry 3845 (class 0 OID 0)
-- Dependencies: 306
-- Name: homework_homework_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.homework_homework_id_seq OWNED BY app.homework.homework_id;


--
-- TOC entry 307 (class 1259 OID 115084)
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
-- TOC entry 3846 (class 0 OID 0)
-- Dependencies: 307
-- Name: COLUMN installment_options.payment_interval; Type: COMMENT; Schema: app; Owner: postgres
--

COMMENT ON COLUMN app.installment_options.payment_interval IS 'number of days';


--
-- TOC entry 308 (class 1259 OID 115091)
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
-- TOC entry 3847 (class 0 OID 0)
-- Dependencies: 308
-- Name: installment_options_installment_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.installment_options_installment_id_seq OWNED BY app.installment_options.installment_id;


--
-- TOC entry 309 (class 1259 OID 115093)
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
-- TOC entry 310 (class 1259 OID 115097)
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
-- TOC entry 311 (class 1259 OID 115101)
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
    modified_by integer,
    in_quickbooks boolean DEFAULT false NOT NULL
);


ALTER TABLE app.invoice_line_items OWNER TO postgres;

--
-- TOC entry 312 (class 1259 OID 115108)
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
-- TOC entry 3848 (class 0 OID 0)
-- Dependencies: 312
-- Name: invoice_line_items_inv_item_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.invoice_line_items_inv_item_id_seq OWNED BY app.invoice_line_items.inv_item_id;


--
-- TOC entry 313 (class 1259 OID 115110)
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
    custom_invoice_no character varying,
    in_quickbooks boolean DEFAULT false NOT NULL
);


ALTER TABLE app.invoices OWNER TO postgres;

--
-- TOC entry 314 (class 1259 OID 115120)
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
-- TOC entry 3849 (class 0 OID 0)
-- Dependencies: 314
-- Name: invoices_inv_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.invoices_inv_id_seq OWNED BY app.invoices.inv_id;


--
-- TOC entry 315 (class 1259 OID 115122)
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
-- TOC entry 316 (class 1259 OID 115128)
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
-- TOC entry 3850 (class 0 OID 0)
-- Dependencies: 316
-- Name: lowersch_reportcards_lowersch_reportcards_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.lowersch_reportcards_lowersch_reportcards_id_seq OWNED BY app.lowersch_reportcards.lowersch_reportcards_id;


--
-- TOC entry 317 (class 1259 OID 115130)
-- Name: medical_conditions; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.medical_conditions (
    condition_id integer NOT NULL,
    illness_condition character varying NOT NULL
);


ALTER TABLE app.medical_conditions OWNER TO postgres;

--
-- TOC entry 318 (class 1259 OID 115136)
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
-- TOC entry 3851 (class 0 OID 0)
-- Dependencies: 318
-- Name: medical_conditions_condition_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.medical_conditions_condition_id_seq OWNED BY app.medical_conditions.condition_id;


--
-- TOC entry 319 (class 1259 OID 115138)
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
-- TOC entry 320 (class 1259 OID 115142)
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
    modified_by integer,
    in_quickbooks boolean DEFAULT false NOT NULL
);


ALTER TABLE app.payment_inv_items OWNER TO postgres;

--
-- TOC entry 321 (class 1259 OID 115149)
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
-- TOC entry 3852 (class 0 OID 0)
-- Dependencies: 321
-- Name: payment_inv_items_payment_inv_item_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.payment_inv_items_payment_inv_item_id_seq OWNED BY app.payment_inv_items.payment_inv_item_id;


--
-- TOC entry 322 (class 1259 OID 115151)
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
-- TOC entry 323 (class 1259 OID 115158)
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
-- TOC entry 3853 (class 0 OID 0)
-- Dependencies: 323
-- Name: payment_replacement_items_payment_replace_item_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.payment_replacement_items_payment_replace_item_id_seq OWNED BY app.payment_replacement_items.payment_replace_item_id;


--
-- TOC entry 324 (class 1259 OID 115160)
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
    banking_date date,
    in_quickbooks boolean DEFAULT false NOT NULL
);


ALTER TABLE app.payments OWNER TO postgres;

--
-- TOC entry 3854 (class 0 OID 0)
-- Dependencies: 324
-- Name: COLUMN payments.payment_method; Type: COMMENT; Schema: app; Owner: postgres
--

COMMENT ON COLUMN app.payments.payment_method IS 'Cash or Cheque';


--
-- TOC entry 325 (class 1259 OID 115170)
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
-- TOC entry 3855 (class 0 OID 0)
-- Dependencies: 325
-- Name: payments_payment_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.payments_payment_id_seq OWNED BY app.payments.payment_id;


--
-- TOC entry 326 (class 1259 OID 115172)
-- Name: permissions; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.permissions (
    permission_id integer NOT NULL,
    user_id integer,
    emp_id integer,
    module text NOT NULL,
    parent_module text,
    all_access boolean,
    create_access boolean,
    edit_access boolean,
    delete_access boolean,
    view_access boolean,
    export_access boolean,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    modified_date timestamp without time zone,
    modified_by integer,
    active boolean DEFAULT true NOT NULL
);


ALTER TABLE app.permissions OWNER TO postgres;

--
-- TOC entry 327 (class 1259 OID 115180)
-- Name: permissions_permission_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.permissions_permission_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.permissions_permission_id_seq OWNER TO postgres;

--
-- TOC entry 3856 (class 0 OID 0)
-- Dependencies: 327
-- Name: permissions_permission_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.permissions_permission_id_seq OWNED BY app.permissions.permission_id;


--
-- TOC entry 328 (class 1259 OID 115182)
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
-- TOC entry 329 (class 1259 OID 115186)
-- Name: report_card_files; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.report_card_files (
    rptcard_file_id integer NOT NULL,
    student_id integer NOT NULL,
    term_id integer NOT NULL,
    file_name character varying NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    modified_date timestamp without time zone
);


ALTER TABLE app.report_card_files OWNER TO postgres;

--
-- TOC entry 330 (class 1259 OID 115193)
-- Name: report_card_files_rptcard_file_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.report_card_files_rptcard_file_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.report_card_files_rptcard_file_id_seq OWNER TO postgres;

--
-- TOC entry 3857 (class 0 OID 0)
-- Dependencies: 330
-- Name: report_card_files_rptcard_file_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.report_card_files_rptcard_file_id_seq OWNED BY app.report_card_files.rptcard_file_id;


--
-- TOC entry 331 (class 1259 OID 115195)
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
    published boolean DEFAULT false NOT NULL,
    class_teacher_comment text,
    head_teacher_comment text
);


ALTER TABLE app.report_cards OWNER TO postgres;

--
-- TOC entry 332 (class 1259 OID 115203)
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
-- TOC entry 3858 (class 0 OID 0)
-- Dependencies: 332
-- Name: report_cards_report_card_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.report_cards_report_card_id_seq OWNED BY app.report_cards.report_card_id;


--
-- TOC entry 333 (class 1259 OID 115205)
-- Name: reportcard_data; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.reportcard_data (
    reportcard_data_id integer NOT NULL,
    student_id integer NOT NULL,
    class_id integer NOT NULL,
    class_name character varying NOT NULL,
    term_id integer NOT NULL,
    term_name character varying NOT NULL,
    exam_type_id integer NOT NULL,
    exam_type character varying NOT NULL,
    subject_id integer NOT NULL,
    subject_name character varying NOT NULL,
    parent_subject_name character varying,
    mark integer,
    grade_weight integer,
    grade character varying,
    use_for_grading boolean,
    teacher_id integer,
    teacher_initials character varying,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    modified_date timestamp without time zone,
    modified_by integer
);


ALTER TABLE app.reportcard_data OWNER TO postgres;

--
-- TOC entry 334 (class 1259 OID 115212)
-- Name: reportcard_data_overalls; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.reportcard_data_overalls (
    reportcard_data_overall_id integer NOT NULL,
    reportcard_data_id integer,
    class_teacher_name character varying,
    class_teacher_comment character varying,
    non_exam_comments character varying,
    principal_comment character varying,
    next_term_date character varying,
    closing_date character varying,
    class_position character varying,
    stream_position character varying,
    overall_marks character varying,
    overall_grade character varying,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    modified_date timestamp without time zone,
    modified_by integer
);


ALTER TABLE app.reportcard_data_overalls OWNER TO postgres;

--
-- TOC entry 335 (class 1259 OID 115219)
-- Name: reportcard_data_overalls_reportcard_data_overall_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.reportcard_data_overalls_reportcard_data_overall_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.reportcard_data_overalls_reportcard_data_overall_id_seq OWNER TO postgres;

--
-- TOC entry 3859 (class 0 OID 0)
-- Dependencies: 335
-- Name: reportcard_data_overalls_reportcard_data_overall_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.reportcard_data_overalls_reportcard_data_overall_id_seq OWNED BY app.reportcard_data_overalls.reportcard_data_overall_id;


--
-- TOC entry 336 (class 1259 OID 115221)
-- Name: reportcard_data_reportcard_data_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.reportcard_data_reportcard_data_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.reportcard_data_reportcard_data_id_seq OWNER TO postgres;

--
-- TOC entry 3860 (class 0 OID 0)
-- Dependencies: 336
-- Name: reportcard_data_reportcard_data_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.reportcard_data_reportcard_data_id_seq OWNED BY app.reportcard_data.reportcard_data_id;


--
-- TOC entry 337 (class 1259 OID 115223)
-- Name: reportcard_data_subj_ovrlls; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.reportcard_data_subj_ovrlls (
    reportcard_data_subj_id integer NOT NULL,
    reportcard_data_id integer,
    subject_overall_mark integer,
    subject_overall_grade character varying,
    suject_remarks character varying,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    modified_date timestamp without time zone,
    modified_by integer
);


ALTER TABLE app.reportcard_data_subj_ovrlls OWNER TO postgres;

--
-- TOC entry 338 (class 1259 OID 115230)
-- Name: reportcard_data_subj_ovrlls_reportcard_data_subj_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.reportcard_data_subj_ovrlls_reportcard_data_subj_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.reportcard_data_subj_ovrlls_reportcard_data_subj_id_seq OWNER TO postgres;

--
-- TOC entry 3861 (class 0 OID 0)
-- Dependencies: 338
-- Name: reportcard_data_subj_ovrlls_reportcard_data_subj_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.reportcard_data_subj_ovrlls_reportcard_data_subj_id_seq OWNED BY app.reportcard_data_subj_ovrlls.reportcard_data_subj_id;


--
-- TOC entry 378 (class 1259 OID 142418)
-- Name: school_bnks; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.school_bnks (
    bnk_id integer NOT NULL,
    name character varying,
    branch character varying,
    acc_name character varying,
    acc_number character varying,
    active boolean DEFAULT true NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    modified_date timestamp without time zone
);


ALTER TABLE app.school_bnks OWNER TO postgres;

--
-- TOC entry 377 (class 1259 OID 142416)
-- Name: school_bnks_bnk_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.school_bnks_bnk_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.school_bnks_bnk_id_seq OWNER TO postgres;

--
-- TOC entry 3862 (class 0 OID 0)
-- Dependencies: 377
-- Name: school_bnks_bnk_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.school_bnks_bnk_id_seq OWNED BY app.school_bnks.bnk_id;


--
-- TOC entry 382 (class 1259 OID 171665)
-- Name: school_menu; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.school_menu (
    menu_id integer NOT NULL,
    day_name character varying,
    break_name character varying,
    meal character varying,
    active boolean DEFAULT true NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    modified_date timestamp without time zone
);


ALTER TABLE app.school_menu OWNER TO postgres;

--
-- TOC entry 381 (class 1259 OID 171663)
-- Name: school_menu_menu_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.school_menu_menu_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.school_menu_menu_id_seq OWNER TO postgres;

--
-- TOC entry 3863 (class 0 OID 0)
-- Dependencies: 381
-- Name: school_menu_menu_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.school_menu_menu_id_seq OWNED BY app.school_menu.menu_id;


--
-- TOC entry 339 (class 1259 OID 115232)
-- Name: school_resources; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.school_resources (
    resource_id integer NOT NULL,
    class_id integer,
    term_id integer,
    emp_id integer,
    resource_name character varying,
    resource_type character varying,
    file_name character varying,
    additional_text character varying,
    active boolean DEFAULT true NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    modified_date timestamp without time zone,
    vimeo_path character varying
);


ALTER TABLE app.school_resources OWNER TO postgres;

--
-- TOC entry 340 (class 1259 OID 115240)
-- Name: school_resources_resource_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE app.school_resources_resource_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.school_resources_resource_id_seq OWNER TO postgres;

--
-- TOC entry 3864 (class 0 OID 0)
-- Dependencies: 340
-- Name: school_resources_resource_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.school_resources_resource_id_seq OWNED BY app.school_resources.resource_id;


--
-- TOC entry 341 (class 1259 OID 115242)
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
-- TOC entry 342 (class 1259 OID 115250)
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
-- TOC entry 3865 (class 0 OID 0)
-- Dependencies: 342
-- Name: schoolbus_bus_trips_bus_trip_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.schoolbus_bus_trips_bus_trip_id_seq OWNED BY app.schoolbus_bus_trips.bus_trip_id;


--
-- TOC entry 343 (class 1259 OID 115252)
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
-- TOC entry 344 (class 1259 OID 115259)
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
-- TOC entry 3866 (class 0 OID 0)
-- Dependencies: 344
-- Name: schoolbus_history_schoolbus_history_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.schoolbus_history_schoolbus_history_id_seq OWNED BY app.schoolbus_history.schoolbus_history_id;


--
-- TOC entry 345 (class 1259 OID 115261)
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
-- TOC entry 346 (class 1259 OID 115269)
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
-- TOC entry 3867 (class 0 OID 0)
-- Dependencies: 346
-- Name: schoolbus_trips_schoolbus_trip_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.schoolbus_trips_schoolbus_trip_id_seq OWNED BY app.schoolbus_trips.schoolbus_trip_id;


--
-- TOC entry 347 (class 1259 OID 115271)
-- Name: settings; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.settings (
    name character varying NOT NULL,
    value character varying
);


ALTER TABLE app.settings OWNER TO postgres;

--
-- TOC entry 348 (class 1259 OID 115277)
-- Name: student_buses; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.student_buses (
    student_bus_id integer NOT NULL,
    student_id integer NOT NULL,
    bus_id integer NOT NULL
);


ALTER TABLE app.student_buses OWNER TO postgres;

--
-- TOC entry 349 (class 1259 OID 115280)
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
-- TOC entry 3868 (class 0 OID 0)
-- Dependencies: 349
-- Name: student_buses_student_bus_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.student_buses_student_bus_id_seq OWNED BY app.student_buses.student_bus_id;


--
-- TOC entry 350 (class 1259 OID 115282)
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
-- TOC entry 351 (class 1259 OID 115287)
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
-- TOC entry 3869 (class 0 OID 0)
-- Dependencies: 351
-- Name: student_class_history_class_history_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.student_class_history_class_history_id_seq OWNED BY app.student_class_history.class_history_id;


--
-- TOC entry 352 (class 1259 OID 115289)
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
    transfer_date date,
    in_quickbooks boolean DEFAULT false NOT NULL
);


ALTER TABLE app.students OWNER TO postgres;

--
-- TOC entry 353 (class 1259 OID 115308)
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
    use_for_grading boolean DEFAULT true NOT NULL,
    teacher_class_id integer
);


ALTER TABLE app.subjects OWNER TO postgres;

--
-- TOC entry 354 (class 1259 OID 115317)
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
-- TOC entry 355 (class 1259 OID 115322)
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
-- TOC entry 3870 (class 0 OID 0)
-- Dependencies: 355
-- Name: COLUMN student_fee_items.payment_method; Type: COMMENT; Schema: app; Owner: postgres
--

COMMENT ON COLUMN app.student_fee_items.payment_method IS 'This is an option from the Payment Options setting';


--
-- TOC entry 356 (class 1259 OID 115330)
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
-- TOC entry 3871 (class 0 OID 0)
-- Dependencies: 356
-- Name: student_fee_items_student_fee_item_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.student_fee_items_student_fee_item_id_seq OWNED BY app.student_fee_items.student_fee_item_id;


--
-- TOC entry 357 (class 1259 OID 115332)
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
-- TOC entry 358 (class 1259 OID 115340)
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
-- TOC entry 3872 (class 0 OID 0)
-- Dependencies: 358
-- Name: student_guardians_student_guardian_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.student_guardians_student_guardian_id_seq OWNED BY app.student_guardians.student_guardian_id;


--
-- TOC entry 359 (class 1259 OID 115342)
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
-- TOC entry 360 (class 1259 OID 115349)
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
-- TOC entry 3873 (class 0 OID 0)
-- Dependencies: 360
-- Name: student_medical_history_medical_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.student_medical_history_medical_id_seq OWNED BY app.student_medical_history.medical_id;


--
-- TOC entry 361 (class 1259 OID 115351)
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
-- TOC entry 3874 (class 0 OID 0)
-- Dependencies: 361
-- Name: students_student_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.students_student_id_seq OWNED BY app.students.student_id;


--
-- TOC entry 362 (class 1259 OID 115353)
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
-- TOC entry 3875 (class 0 OID 0)
-- Dependencies: 362
-- Name: subjects_subject_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.subjects_subject_id_seq OWNED BY app.subjects.subject_id;


--
-- TOC entry 363 (class 1259 OID 115355)
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
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    subject_id integer NOT NULL
);


ALTER TABLE app.teacher_timetables OWNER TO postgres;

--
-- TOC entry 364 (class 1259 OID 115362)
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
-- TOC entry 3876 (class 0 OID 0)
-- Dependencies: 364
-- Name: teacher_timetables_teacher_timetable_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.teacher_timetables_teacher_timetable_id_seq OWNED BY app.teacher_timetables.teacher_timetable_id;


--
-- TOC entry 365 (class 1259 OID 115364)
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
-- TOC entry 366 (class 1259 OID 115368)
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
-- TOC entry 3877 (class 0 OID 0)
-- Dependencies: 366
-- Name: terms_term_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.terms_term_id_seq OWNED BY app.terms.term_id;


--
-- TOC entry 367 (class 1259 OID 115370)
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
-- TOC entry 368 (class 1259 OID 115379)
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
-- TOC entry 3878 (class 0 OID 0)
-- Dependencies: 368
-- Name: transport_routes_transport_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.transport_routes_transport_id_seq OWNED BY app.transport_routes.transport_id;


--
-- TOC entry 369 (class 1259 OID 115381)
-- Name: user_permissions; Type: TABLE; Schema: app; Owner: postgres
--

CREATE TABLE app.user_permissions (
    perm_id integer NOT NULL,
    user_type character varying NOT NULL,
    permissions text NOT NULL
);


ALTER TABLE app.user_permissions OWNER TO postgres;

--
-- TOC entry 370 (class 1259 OID 115387)
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
-- TOC entry 3879 (class 0 OID 0)
-- Dependencies: 370
-- Name: user_permissions_perm_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.user_permissions_perm_id_seq OWNED BY app.user_permissions.perm_id;


--
-- TOC entry 371 (class 1259 OID 115389)
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
    modified_by integer,
    class_cat_limit integer
);


ALTER TABLE app.users OWNER TO postgres;

--
-- TOC entry 372 (class 1259 OID 115397)
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
-- TOC entry 3880 (class 0 OID 0)
-- Dependencies: 372
-- Name: user_user_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE app.user_user_id_seq OWNED BY app.users.user_id;


--
-- TOC entry 3404 (class 2604 OID 141658)
-- Name: absenteeism absentee_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.absenteeism ALTER COLUMN absentee_id SET DEFAULT nextval('app.absenteeism_absentee_id_seq'::regclass);


--
-- TOC entry 3240 (class 2604 OID 116002)
-- Name: blog_post_statuses post_status_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.blog_post_statuses ALTER COLUMN post_status_id SET DEFAULT nextval('app.blog_post_statuses_post_status_id_seq'::regclass);


--
-- TOC entry 3241 (class 2604 OID 116003)
-- Name: blog_post_types post_type_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.blog_post_types ALTER COLUMN post_type_id SET DEFAULT nextval('app.blog_post_types_post_type_id_seq'::regclass);


--
-- TOC entry 3243 (class 2604 OID 116004)
-- Name: blog_posts post_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.blog_posts ALTER COLUMN post_id SET DEFAULT nextval('app.blog_posts_post_id_seq'::regclass);


--
-- TOC entry 3244 (class 2604 OID 116005)
-- Name: blogs blog_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.blogs ALTER COLUMN blog_id SET DEFAULT nextval('app.blogs_blog_id_seq'::regclass);


--
-- TOC entry 3247 (class 2604 OID 116006)
-- Name: buses bus_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.buses ALTER COLUMN bus_id SET DEFAULT nextval('app.buses_bus_id_seq'::regclass);


--
-- TOC entry 3250 (class 2604 OID 116007)
-- Name: class_cats class_cat_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.class_cats ALTER COLUMN class_cat_id SET DEFAULT nextval('app.class_cats_class_cat_id_seq'::regclass);


--
-- TOC entry 3253 (class 2604 OID 116008)
-- Name: class_subject_exams class_sub_exam_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.class_subject_exams ALTER COLUMN class_sub_exam_id SET DEFAULT nextval('app.class_subject_exams_class_sub_exam_id_seq'::regclass);


--
-- TOC entry 3256 (class 2604 OID 116009)
-- Name: class_subjects class_subject_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.class_subjects ALTER COLUMN class_subject_id SET DEFAULT nextval('app.class_subjects_class_subject_id_seq'::regclass);


--
-- TOC entry 3258 (class 2604 OID 116010)
-- Name: class_timetables class_timetable_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.class_timetables ALTER COLUMN class_timetable_id SET DEFAULT nextval('app.class_timetables_class_timetable_id_seq'::regclass);


--
-- TOC entry 3261 (class 2604 OID 116011)
-- Name: classes class_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.classes ALTER COLUMN class_id SET DEFAULT nextval('app.classes_class_id_seq'::regclass);


--
-- TOC entry 3262 (class 2604 OID 116012)
-- Name: communication_attachments attachment_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communication_attachments ALTER COLUMN attachment_id SET DEFAULT nextval('app.communication_attachments_attachment_id_seq'::regclass);


--
-- TOC entry 3263 (class 2604 OID 116013)
-- Name: communication_audience audience_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communication_audience ALTER COLUMN audience_id SET DEFAULT nextval('app.communication_audience_audience_id_seq'::regclass);


--
-- TOC entry 3266 (class 2604 OID 116014)
-- Name: communication_emails email_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communication_emails ALTER COLUMN email_id SET DEFAULT nextval('app.communication_emails_email_id_seq'::regclass);


--
-- TOC entry 3269 (class 2604 OID 116015)
-- Name: communication_feedback com_feedback_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communication_feedback ALTER COLUMN com_feedback_id SET DEFAULT nextval('app.communication_feedback_com_feedback_id_seq'::regclass);


--
-- TOC entry 3272 (class 2604 OID 116016)
-- Name: communication_sms sms_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communication_sms ALTER COLUMN sms_id SET DEFAULT nextval('app.communication_sms_sms_id_seq'::regclass);


--
-- TOC entry 3273 (class 2604 OID 116017)
-- Name: communication_types com_type_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communication_types ALTER COLUMN com_type_id SET DEFAULT nextval('app.communication_types_com_type_id_seq'::regclass);


--
-- TOC entry 3276 (class 2604 OID 116018)
-- Name: communications com_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communications ALTER COLUMN com_id SET DEFAULT nextval('app.communications_com_id_seq'::regclass);


--
-- TOC entry 3403 (class 2604 OID 118328)
-- Name: communications_failed_sms failed_sms_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communications_failed_sms ALTER COLUMN failed_sms_id SET DEFAULT nextval('app.communications_failed_sms_failed_sms_id_seq'::regclass);


--
-- TOC entry 3277 (class 2604 OID 116019)
-- Name: countries countries_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.countries ALTER COLUMN countries_id SET DEFAULT nextval('app.countries_countries_id_seq'::regclass);


--
-- TOC entry 3280 (class 2604 OID 116020)
-- Name: credits credit_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.credits ALTER COLUMN credit_id SET DEFAULT nextval('app.credits_credit_id_seq'::regclass);


--
-- TOC entry 3286 (class 2604 OID 116021)
-- Name: departments dept_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.departments ALTER COLUMN dept_id SET DEFAULT nextval('app.departments_dept_id_seq'::regclass);


--
-- TOC entry 3409 (class 2604 OID 167613)
-- Name: disciplinary disciplinary_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.disciplinary ALTER COLUMN disciplinary_id SET DEFAULT nextval('app.disciplinary_disciplinary_id_seq'::regclass);


--
-- TOC entry 3289 (class 2604 OID 116022)
-- Name: employee_cats emp_cat_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.employee_cats ALTER COLUMN emp_cat_id SET DEFAULT nextval('app.employee_cats_emp_cat_id_seq'::regclass);


--
-- TOC entry 3292 (class 2604 OID 116023)
-- Name: employees emp_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.employees ALTER COLUMN emp_id SET DEFAULT nextval('app.employees_emp_id_seq'::regclass);


--
-- TOC entry 3294 (class 2604 OID 116024)
-- Name: exam_marks exam_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.exam_marks ALTER COLUMN exam_id SET DEFAULT nextval('app.exam_marks_exam_id_seq'::regclass);


--
-- TOC entry 3297 (class 2604 OID 116025)
-- Name: exam_types exam_type_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.exam_types ALTER COLUMN exam_type_id SET DEFAULT nextval('app.exam_types_exam_type_id_seq'::regclass);


--
-- TOC entry 3300 (class 2604 OID 116026)
-- Name: fee_item_uniforms uniform_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.fee_item_uniforms ALTER COLUMN uniform_id SET DEFAULT nextval('app.fee_item_uniforms_uniform_id_seq'::regclass);


--
-- TOC entry 3307 (class 2604 OID 116027)
-- Name: fee_items fee_item_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.fee_items ALTER COLUMN fee_item_id SET DEFAULT nextval('app.fee_items_fee_item_id_seq'::regclass);


--
-- TOC entry 3308 (class 2604 OID 116028)
-- Name: grading grade_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.grading ALTER COLUMN grade_id SET DEFAULT nextval('app.grading_grade_id_seq'::regclass);


--
-- TOC entry 3309 (class 2604 OID 116029)
-- Name: grading2 grade2_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.grading2 ALTER COLUMN grade2_id SET DEFAULT nextval('app.grading2_grade2_id_seq'::regclass);


--
-- TOC entry 3312 (class 2604 OID 116030)
-- Name: guardians guardian_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.guardians ALTER COLUMN guardian_id SET DEFAULT nextval('app.guardians_guardian_id_seq'::regclass);


--
-- TOC entry 3314 (class 2604 OID 116031)
-- Name: homework homework_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.homework ALTER COLUMN homework_id SET DEFAULT nextval('app.homework_homework_id_seq'::regclass);


--
-- TOC entry 3316 (class 2604 OID 116032)
-- Name: homework_feedback homework_feedback_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.homework_feedback ALTER COLUMN homework_feedback_id SET DEFAULT nextval('app.homework_feedback_homework_feedback_id_seq'::regclass);


--
-- TOC entry 3318 (class 2604 OID 116033)
-- Name: installment_options installment_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.installment_options ALTER COLUMN installment_id SET DEFAULT nextval('app.installment_options_installment_id_seq'::regclass);


--
-- TOC entry 3320 (class 2604 OID 116034)
-- Name: invoice_line_items inv_item_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.invoice_line_items ALTER COLUMN inv_item_id SET DEFAULT nextval('app.invoice_line_items_inv_item_id_seq'::regclass);


--
-- TOC entry 3326 (class 2604 OID 116035)
-- Name: invoices inv_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.invoices ALTER COLUMN inv_id SET DEFAULT nextval('app.invoices_inv_id_seq'::regclass);


--
-- TOC entry 3327 (class 2604 OID 116036)
-- Name: lowersch_reportcards lowersch_reportcards_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.lowersch_reportcards ALTER COLUMN lowersch_reportcards_id SET DEFAULT nextval('app.lowersch_reportcards_lowersch_reportcards_id_seq'::regclass);


--
-- TOC entry 3328 (class 2604 OID 116037)
-- Name: medical_conditions condition_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.medical_conditions ALTER COLUMN condition_id SET DEFAULT nextval('app.medical_conditions_condition_id_seq'::regclass);


--
-- TOC entry 3330 (class 2604 OID 116038)
-- Name: payment_inv_items payment_inv_item_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.payment_inv_items ALTER COLUMN payment_inv_item_id SET DEFAULT nextval('app.payment_inv_items_payment_inv_item_id_seq'::regclass);


--
-- TOC entry 3333 (class 2604 OID 116039)
-- Name: payment_replacement_items payment_replace_item_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.payment_replacement_items ALTER COLUMN payment_replace_item_id SET DEFAULT nextval('app.payment_replacement_items_payment_replace_item_id_seq'::regclass);


--
-- TOC entry 3338 (class 2604 OID 116040)
-- Name: payments payment_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.payments ALTER COLUMN payment_id SET DEFAULT nextval('app.payments_payment_id_seq'::regclass);


--
-- TOC entry 3341 (class 2604 OID 116041)
-- Name: permissions permission_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.permissions ALTER COLUMN permission_id SET DEFAULT nextval('app.permissions_permission_id_seq'::regclass);


--
-- TOC entry 3343 (class 2604 OID 116042)
-- Name: report_card_files rptcard_file_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.report_card_files ALTER COLUMN rptcard_file_id SET DEFAULT nextval('app.report_card_files_rptcard_file_id_seq'::regclass);


--
-- TOC entry 3346 (class 2604 OID 116043)
-- Name: report_cards report_card_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.report_cards ALTER COLUMN report_card_id SET DEFAULT nextval('app.report_cards_report_card_id_seq'::regclass);


--
-- TOC entry 3348 (class 2604 OID 116044)
-- Name: reportcard_data reportcard_data_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.reportcard_data ALTER COLUMN reportcard_data_id SET DEFAULT nextval('app.reportcard_data_reportcard_data_id_seq'::regclass);


--
-- TOC entry 3350 (class 2604 OID 116045)
-- Name: reportcard_data_overalls reportcard_data_overall_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.reportcard_data_overalls ALTER COLUMN reportcard_data_overall_id SET DEFAULT nextval('app.reportcard_data_overalls_reportcard_data_overall_id_seq'::regclass);


--
-- TOC entry 3352 (class 2604 OID 116046)
-- Name: reportcard_data_subj_ovrlls reportcard_data_subj_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.reportcard_data_subj_ovrlls ALTER COLUMN reportcard_data_subj_id SET DEFAULT nextval('app.reportcard_data_subj_ovrlls_reportcard_data_subj_id_seq'::regclass);


--
-- TOC entry 3406 (class 2604 OID 142421)
-- Name: school_bnks bnk_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.school_bnks ALTER COLUMN bnk_id SET DEFAULT nextval('app.school_bnks_bnk_id_seq'::regclass);


--
-- TOC entry 3411 (class 2604 OID 171668)
-- Name: school_menu menu_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.school_menu ALTER COLUMN menu_id SET DEFAULT nextval('app.school_menu_menu_id_seq'::regclass);


--
-- TOC entry 3355 (class 2604 OID 116047)
-- Name: school_resources resource_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.school_resources ALTER COLUMN resource_id SET DEFAULT nextval('app.school_resources_resource_id_seq'::regclass);


--
-- TOC entry 3358 (class 2604 OID 116048)
-- Name: schoolbus_bus_trips bus_trip_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.schoolbus_bus_trips ALTER COLUMN bus_trip_id SET DEFAULT nextval('app.schoolbus_bus_trips_bus_trip_id_seq'::regclass);


--
-- TOC entry 3360 (class 2604 OID 116049)
-- Name: schoolbus_history schoolbus_history_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.schoolbus_history ALTER COLUMN schoolbus_history_id SET DEFAULT nextval('app.schoolbus_history_schoolbus_history_id_seq'::regclass);


--
-- TOC entry 3363 (class 2604 OID 116050)
-- Name: schoolbus_trips schoolbus_trip_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.schoolbus_trips ALTER COLUMN schoolbus_trip_id SET DEFAULT nextval('app.schoolbus_trips_schoolbus_trip_id_seq'::regclass);


--
-- TOC entry 3364 (class 2604 OID 116051)
-- Name: student_buses student_bus_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.student_buses ALTER COLUMN student_bus_id SET DEFAULT nextval('app.student_buses_student_bus_id_seq'::regclass);


--
-- TOC entry 3367 (class 2604 OID 116052)
-- Name: student_class_history class_history_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.student_class_history ALTER COLUMN class_history_id SET DEFAULT nextval('app.student_class_history_class_history_id_seq'::regclass);


--
-- TOC entry 3388 (class 2604 OID 116053)
-- Name: student_fee_items student_fee_item_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.student_fee_items ALTER COLUMN student_fee_item_id SET DEFAULT nextval('app.student_fee_items_student_fee_item_id_seq'::regclass);


--
-- TOC entry 3391 (class 2604 OID 116054)
-- Name: student_guardians student_guardian_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.student_guardians ALTER COLUMN student_guardian_id SET DEFAULT nextval('app.student_guardians_student_guardian_id_seq'::regclass);


--
-- TOC entry 3393 (class 2604 OID 116055)
-- Name: student_medical_history medical_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.student_medical_history ALTER COLUMN medical_id SET DEFAULT nextval('app.student_medical_history_medical_id_seq'::regclass);


--
-- TOC entry 3381 (class 2604 OID 116056)
-- Name: students student_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.students ALTER COLUMN student_id SET DEFAULT nextval('app.students_student_id_seq'::regclass);


--
-- TOC entry 3385 (class 2604 OID 116057)
-- Name: subjects subject_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.subjects ALTER COLUMN subject_id SET DEFAULT nextval('app.subjects_subject_id_seq'::regclass);


--
-- TOC entry 3395 (class 2604 OID 116058)
-- Name: teacher_timetables teacher_timetable_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.teacher_timetables ALTER COLUMN teacher_timetable_id SET DEFAULT nextval('app.teacher_timetables_teacher_timetable_id_seq'::regclass);


--
-- TOC entry 3283 (class 2604 OID 116059)
-- Name: terms term_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.terms ALTER COLUMN term_id SET DEFAULT nextval('app.terms_term_id_seq'::regclass);


--
-- TOC entry 3398 (class 2604 OID 116060)
-- Name: transport_routes transport_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.transport_routes ALTER COLUMN transport_id SET DEFAULT nextval('app.transport_routes_transport_id_seq'::regclass);


--
-- TOC entry 3399 (class 2604 OID 116061)
-- Name: user_permissions perm_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.user_permissions ALTER COLUMN perm_id SET DEFAULT nextval('app.user_permissions_perm_id_seq'::regclass);


--
-- TOC entry 3402 (class 2604 OID 116062)
-- Name: users user_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.users ALTER COLUMN user_id SET DEFAULT nextval('app.user_user_id_seq'::regclass);


--
-- TOC entry 3422 (class 2606 OID 115461)
-- Name: blogs FK_blog_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.blogs
    ADD CONSTRAINT "FK_blog_id" PRIMARY KEY (blog_id);


--
-- TOC entry 3505 (class 2606 OID 115463)
-- Name: homework FK_homework_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.homework
    ADD CONSTRAINT "FK_homework_id" PRIMARY KEY (homework_id);


--
-- TOC entry 3515 (class 2606 OID 115465)
-- Name: lowersch_reportcards FK_lowersch_reportcards_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.lowersch_reportcards
    ADD CONSTRAINT "FK_lowersch_reportcards_id" PRIMARY KEY (lowersch_reportcards_id);


--
-- TOC entry 3532 (class 2606 OID 115467)
-- Name: report_cards FK_report_card_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.report_cards
    ADD CONSTRAINT "FK_report_card_id" PRIMARY KEY (report_card_id);


--
-- TOC entry 3446 (class 2606 OID 115469)
-- Name: communication_audience PK_audience_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communication_audience
    ADD CONSTRAINT "PK_audience_id" PRIMARY KEY (audience_id);


--
-- TOC entry 3588 (class 2606 OID 142428)
-- Name: school_bnks PK_bnk_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.school_bnks
    ADD CONSTRAINT "PK_bnk_id" PRIMARY KEY (bnk_id);


--
-- TOC entry 3424 (class 2606 OID 115471)
-- Name: buses PK_bus_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.buses
    ADD CONSTRAINT "PK_bus_id" PRIMARY KEY (bus_id);


--
-- TOC entry 3544 (class 2606 OID 115473)
-- Name: schoolbus_bus_trips PK_bus_trip_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.schoolbus_bus_trips
    ADD CONSTRAINT "PK_bus_trip_id" PRIMARY KEY (bus_trip_id);


--
-- TOC entry 3428 (class 2606 OID 115475)
-- Name: class_cats PK_class_cat_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.class_cats
    ADD CONSTRAINT "PK_class_cat_id" PRIMARY KEY (class_cat_id);


--
-- TOC entry 3556 (class 2606 OID 115477)
-- Name: student_class_history PK_class_history_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.student_class_history
    ADD CONSTRAINT "PK_class_history_id" PRIMARY KEY (class_history_id);


--
-- TOC entry 3441 (class 2606 OID 115479)
-- Name: classes PK_class_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.classes
    ADD CONSTRAINT "PK_class_id" PRIMARY KEY (class_id);


--
-- TOC entry 3435 (class 2606 OID 115481)
-- Name: class_subjects PK_class_subject; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.class_subjects
    ADD CONSTRAINT "PK_class_subject" PRIMARY KEY (class_subject_id);


--
-- TOC entry 3431 (class 2606 OID 115483)
-- Name: class_subject_exams PK_class_subject_exam; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.class_subject_exams
    ADD CONSTRAINT "PK_class_subject_exam" PRIMARY KEY (class_sub_exam_id);


--
-- TOC entry 3439 (class 2606 OID 115485)
-- Name: class_timetables PK_class_timetable_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.class_timetables
    ADD CONSTRAINT "PK_class_timetable_id" PRIMARY KEY (class_timetable_id);


--
-- TOC entry 3450 (class 2606 OID 115487)
-- Name: communication_feedback PK_com_feedback_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communication_feedback
    ADD CONSTRAINT "PK_com_feedback_id" PRIMARY KEY (com_feedback_id);


--
-- TOC entry 3456 (class 2606 OID 115489)
-- Name: communications PK_com_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communications
    ADD CONSTRAINT "PK_com_id" PRIMARY KEY (com_id);


--
-- TOC entry 3454 (class 2606 OID 115491)
-- Name: communication_types PK_com_type_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communication_types
    ADD CONSTRAINT "PK_com_type_id" PRIMARY KEY (com_type_id);


--
-- TOC entry 3517 (class 2606 OID 115493)
-- Name: medical_conditions PK_condition_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.medical_conditions
    ADD CONSTRAINT "PK_condition_id" PRIMARY KEY (condition_id);


--
-- TOC entry 3460 (class 2606 OID 115495)
-- Name: credits PK_credit_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.credits
    ADD CONSTRAINT "PK_credit_id" PRIMARY KEY (credit_id);


--
-- TOC entry 3466 (class 2606 OID 115497)
-- Name: departments PK_dept_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.departments
    ADD CONSTRAINT "PK_dept_id" PRIMARY KEY (dept_id);


--
-- TOC entry 3448 (class 2606 OID 115499)
-- Name: communication_emails PK_email_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communication_emails
    ADD CONSTRAINT "PK_email_id" PRIMARY KEY (email_id);


--
-- TOC entry 3470 (class 2606 OID 115501)
-- Name: employee_cats PK_emp_cat_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.employee_cats
    ADD CONSTRAINT "PK_emp_cat_id" PRIMARY KEY (emp_cat_id);


--
-- TOC entry 3474 (class 2606 OID 115503)
-- Name: employees PK_emp_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.employees
    ADD CONSTRAINT "PK_emp_id" PRIMARY KEY (emp_id);


--
-- TOC entry 3478 (class 2606 OID 115505)
-- Name: exam_marks PK_exam_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.exam_marks
    ADD CONSTRAINT "PK_exam_id" PRIMARY KEY (exam_id);


--
-- TOC entry 3483 (class 2606 OID 115507)
-- Name: exam_types PK_exam_type; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.exam_types
    ADD CONSTRAINT "PK_exam_type" PRIMARY KEY (exam_type_id);


--
-- TOC entry 3491 (class 2606 OID 115509)
-- Name: fee_items PK_fee_item_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.fee_items
    ADD CONSTRAINT "PK_fee_item_id" PRIMARY KEY (fee_item_id);


--
-- TOC entry 3497 (class 2606 OID 115511)
-- Name: grading2 PK_grade2_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.grading2
    ADD CONSTRAINT "PK_grade2_id" PRIMARY KEY (grade2_id);


--
-- TOC entry 3493 (class 2606 OID 115513)
-- Name: grading PK_grade_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.grading
    ADD CONSTRAINT "PK_grade_id" PRIMARY KEY (grade_id);


--
-- TOC entry 3501 (class 2606 OID 115515)
-- Name: guardians PK_guardian_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.guardians
    ADD CONSTRAINT "PK_guardian_id" PRIMARY KEY (guardian_id);


--
-- TOC entry 3507 (class 2606 OID 115517)
-- Name: homework_feedback PK_homework_feedback_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.homework_feedback
    ADD CONSTRAINT "PK_homework_feedback_id" PRIMARY KEY (homework_feedback_id);


--
-- TOC entry 3509 (class 2606 OID 115519)
-- Name: installment_options PK_installment_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.installment_options
    ADD CONSTRAINT "PK_installment_id" PRIMARY KEY (installment_id);


--
-- TOC entry 3513 (class 2606 OID 115521)
-- Name: invoices PK_inv_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.invoices
    ADD CONSTRAINT "PK_inv_id" PRIMARY KEY (inv_id);


--
-- TOC entry 3511 (class 2606 OID 115523)
-- Name: invoice_line_items PK_inv_item_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.invoice_line_items
    ADD CONSTRAINT "PK_inv_item_id" PRIMARY KEY (inv_item_id);


--
-- TOC entry 3572 (class 2606 OID 115525)
-- Name: student_medical_history PK_medical_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.student_medical_history
    ADD CONSTRAINT "PK_medical_id" PRIMARY KEY (medical_id);


--
-- TOC entry 3590 (class 2606 OID 171675)
-- Name: school_menu PK_menu_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.school_menu
    ADD CONSTRAINT "PK_menu_id" PRIMARY KEY (menu_id);


--
-- TOC entry 3523 (class 2606 OID 115527)
-- Name: payments PK_payment_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.payments
    ADD CONSTRAINT "PK_payment_id" PRIMARY KEY (payment_id);


--
-- TOC entry 3519 (class 2606 OID 115529)
-- Name: payment_inv_items PK_payment_inv_item_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.payment_inv_items
    ADD CONSTRAINT "PK_payment_inv_item_id" PRIMARY KEY (payment_inv_item_id);


--
-- TOC entry 3521 (class 2606 OID 115531)
-- Name: payment_replacement_items PK_payment_replace_item_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.payment_replacement_items
    ADD CONSTRAINT "PK_payment_replace_item_id" PRIMARY KEY (payment_replace_item_id);


--
-- TOC entry 3580 (class 2606 OID 115533)
-- Name: user_permissions PK_perm_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.user_permissions
    ADD CONSTRAINT "PK_perm_id" PRIMARY KEY (perm_id);


--
-- TOC entry 3525 (class 2606 OID 115535)
-- Name: permissions PK_permission_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.permissions
    ADD CONSTRAINT "PK_permission_id" PRIMARY KEY (permission_id);


--
-- TOC entry 3420 (class 2606 OID 115537)
-- Name: blog_posts PK_post_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.blog_posts
    ADD CONSTRAINT "PK_post_id" PRIMARY KEY (post_id);


--
-- TOC entry 3416 (class 2606 OID 115539)
-- Name: blog_post_statuses PK_post_status_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.blog_post_statuses
    ADD CONSTRAINT "PK_post_status_id" PRIMARY KEY (post_status_id);


--
-- TOC entry 3418 (class 2606 OID 115541)
-- Name: blog_post_types PK_post_type_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.blog_post_types
    ADD CONSTRAINT "PK_post_type_id" PRIMARY KEY (post_type_id);


--
-- TOC entry 3534 (class 2606 OID 115543)
-- Name: reportcard_data PK_reportcard_data_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.reportcard_data
    ADD CONSTRAINT "PK_reportcard_data_id" PRIMARY KEY (reportcard_data_id);


--
-- TOC entry 3538 (class 2606 OID 115545)
-- Name: reportcard_data_overalls PK_reportcard_data_overall_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.reportcard_data_overalls
    ADD CONSTRAINT "PK_reportcard_data_overall_id" PRIMARY KEY (reportcard_data_overall_id);


--
-- TOC entry 3540 (class 2606 OID 115547)
-- Name: reportcard_data_subj_ovrlls PK_reportcard_data_subj_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.reportcard_data_subj_ovrlls
    ADD CONSTRAINT "PK_reportcard_data_subj_id" PRIMARY KEY (reportcard_data_subj_id);


--
-- TOC entry 3542 (class 2606 OID 115549)
-- Name: school_resources PK_resource_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.school_resources
    ADD CONSTRAINT "PK_resource_id" PRIMARY KEY (resource_id);


--
-- TOC entry 3527 (class 2606 OID 115551)
-- Name: report_card_files PK_rptcard_file_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.report_card_files
    ADD CONSTRAINT "PK_rptcard_file_id" PRIMARY KEY (rptcard_file_id);


--
-- TOC entry 3546 (class 2606 OID 115553)
-- Name: schoolbus_history PK_schoolbus_history_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.schoolbus_history
    ADD CONSTRAINT "PK_schoolbus_history_id" PRIMARY KEY (schoolbus_history_id);


--
-- TOC entry 3548 (class 2606 OID 115555)
-- Name: schoolbus_trips PK_schoolbus_trip_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.schoolbus_trips
    ADD CONSTRAINT "PK_schoolbus_trip_id" PRIMARY KEY (schoolbus_trip_id);


--
-- TOC entry 3550 (class 2606 OID 115557)
-- Name: settings PK_setting_name; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.settings
    ADD CONSTRAINT "PK_setting_name" PRIMARY KEY (name);


--
-- TOC entry 3452 (class 2606 OID 115559)
-- Name: communication_sms PK_sms_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communication_sms
    ADD CONSTRAINT "PK_sms_id" PRIMARY KEY (sms_id);


--
-- TOC entry 3552 (class 2606 OID 115561)
-- Name: student_buses PK_student_bus_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.student_buses
    ADD CONSTRAINT "PK_student_bus_id" PRIMARY KEY (student_bus_id);


--
-- TOC entry 3566 (class 2606 OID 115563)
-- Name: student_fee_items PK_student_fee_item; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.student_fee_items
    ADD CONSTRAINT "PK_student_fee_item" PRIMARY KEY (student_fee_item_id);


--
-- TOC entry 3570 (class 2606 OID 115565)
-- Name: student_guardians PK_student_guardian_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.student_guardians
    ADD CONSTRAINT "PK_student_guardian_id" PRIMARY KEY (student_guardian_id);


--
-- TOC entry 3558 (class 2606 OID 115567)
-- Name: students PK_student_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.students
    ADD CONSTRAINT "PK_student_id" PRIMARY KEY (student_id);


--
-- TOC entry 3562 (class 2606 OID 115569)
-- Name: subjects PK_subject_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.subjects
    ADD CONSTRAINT "PK_subject_id" PRIMARY KEY (subject_id);


--
-- TOC entry 3574 (class 2606 OID 115571)
-- Name: teacher_timetables PK_teacher_timetable_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.teacher_timetables
    ADD CONSTRAINT "PK_teacher_timetable_id" PRIMARY KEY (teacher_timetable_id);


--
-- TOC entry 3462 (class 2606 OID 115573)
-- Name: terms PK_term_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.terms
    ADD CONSTRAINT "PK_term_id" PRIMARY KEY (term_id);


--
-- TOC entry 3576 (class 2606 OID 115575)
-- Name: transport_routes PK_transport_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.transport_routes
    ADD CONSTRAINT "PK_transport_id" PRIMARY KEY (transport_id);


--
-- TOC entry 3487 (class 2606 OID 115577)
-- Name: fee_item_uniforms PK_uniform_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.fee_item_uniforms
    ADD CONSTRAINT "PK_uniform_id" PRIMARY KEY (uniform_id);


--
-- TOC entry 3582 (class 2606 OID 115579)
-- Name: users PK_user_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.users
    ADD CONSTRAINT "PK_user_id" PRIMARY KEY (user_id);


--
-- TOC entry 3592 (class 2606 OID 179697)
-- Name: forgot_pwd PK_usr_phone; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.forgot_pwd
    ADD CONSTRAINT "PK_usr_phone" PRIMARY KEY (usr_phone);


--
-- TOC entry 3472 (class 2606 OID 115581)
-- Name: employee_cats U_active_emp_cat; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.employee_cats
    ADD CONSTRAINT "U_active_emp_cat" UNIQUE (emp_cat_name, active);


--
-- TOC entry 3560 (class 2606 OID 115583)
-- Name: students U_admission_number; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.students
    ADD CONSTRAINT "U_admission_number" UNIQUE (admission_number);


--
-- TOC entry 3426 (class 2606 OID 115585)
-- Name: buses U_bus_registration; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.buses
    ADD CONSTRAINT "U_bus_registration" UNIQUE (bus_registration);


--
-- TOC entry 3437 (class 2606 OID 115587)
-- Name: class_subjects U_class_subject; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.class_subjects
    ADD CONSTRAINT "U_class_subject" UNIQUE (class_id, subject_id);


--
-- TOC entry 3468 (class 2606 OID 115589)
-- Name: departments U_dept_name; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.departments
    ADD CONSTRAINT "U_dept_name" UNIQUE (dept_name);


--
-- TOC entry 3476 (class 2606 OID 115591)
-- Name: employees U_emp_number; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.employees
    ADD CONSTRAINT "U_emp_number" UNIQUE (emp_number);


--
-- TOC entry 3485 (class 2606 OID 115593)
-- Name: exam_types U_exam_type_per_category; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.exam_types
    ADD CONSTRAINT "U_exam_type_per_category" UNIQUE (exam_type, class_cat_id);


--
-- TOC entry 3503 (class 2606 OID 115595)
-- Name: guardians U_id_number; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.guardians
    ADD CONSTRAINT "U_id_number" UNIQUE (id_number);


--
-- TOC entry 3578 (class 2606 OID 115597)
-- Name: transport_routes U_route; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.transport_routes
    ADD CONSTRAINT "U_route" UNIQUE (route);


--
-- TOC entry 3480 (class 2606 OID 115599)
-- Name: exam_marks U_student_exam_mark; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.exam_marks
    ADD CONSTRAINT "U_student_exam_mark" UNIQUE (student_id, class_sub_exam_id, term_id);


--
-- TOC entry 3568 (class 2606 OID 115601)
-- Name: student_fee_items U_student_fee_item; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.student_fee_items
    ADD CONSTRAINT "U_student_fee_item" UNIQUE (student_id, fee_item_id);


--
-- TOC entry 3554 (class 2606 OID 115603)
-- Name: student_buses U_student_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.student_buses
    ADD CONSTRAINT "U_student_id" UNIQUE (student_id);


--
-- TOC entry 3536 (class 2606 OID 115605)
-- Name: reportcard_data U_student_subject_exam_mark; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.reportcard_data
    ADD CONSTRAINT "U_student_subject_exam_mark" UNIQUE (student_id, exam_type_id, subject_id, term_id);


--
-- TOC entry 3564 (class 2606 OID 115607)
-- Name: subjects U_subject_by_class_cat; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.subjects
    ADD CONSTRAINT "U_subject_by_class_cat" UNIQUE (subject_name, class_cat_id);


--
-- TOC entry 3433 (class 2606 OID 115609)
-- Name: class_subject_exams U_subject_exam; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.class_subject_exams
    ADD CONSTRAINT "U_subject_exam" UNIQUE (class_subject_id, exam_type_id);


--
-- TOC entry 3464 (class 2606 OID 115611)
-- Name: terms U_term; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.terms
    ADD CONSTRAINT "U_term" UNIQUE (start_date, end_date);


--
-- TOC entry 3489 (class 2606 OID 115613)
-- Name: fee_item_uniforms U_uniform; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.fee_item_uniforms
    ADD CONSTRAINT "U_uniform" UNIQUE (uniform);


--
-- TOC entry 3584 (class 2606 OID 115615)
-- Name: users U_username; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.users
    ADD CONSTRAINT "U_username" UNIQUE (username);


--
-- TOC entry 3444 (class 2606 OID 115617)
-- Name: communication_attachments attachment_id; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communication_attachments
    ADD CONSTRAINT attachment_id PRIMARY KEY (attachment_id);


--
-- TOC entry 3586 (class 2606 OID 118333)
-- Name: communications_failed_sms communications_failed_sms_pkey; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communications_failed_sms
    ADD CONSTRAINT communications_failed_sms_pkey PRIMARY KEY (failed_sms_id);


--
-- TOC entry 3530 (class 2606 OID 115619)
-- Name: report_card_files constraint_name; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.report_card_files
    ADD CONSTRAINT constraint_name UNIQUE (file_name);


--
-- TOC entry 3458 (class 2606 OID 115621)
-- Name: countries countries_pk; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.countries
    ADD CONSTRAINT countries_pk PRIMARY KEY (countries_id);


--
-- TOC entry 3499 (class 2606 OID 115623)
-- Name: grading2 grading_unique_grade2_contraint; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.grading2
    ADD CONSTRAINT grading_unique_grade2_contraint UNIQUE (grade2);


--
-- TOC entry 3495 (class 2606 OID 115625)
-- Name: grading grading_unique_grade_contraint; Type: CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.grading
    ADD CONSTRAINT grading_unique_grade_contraint UNIQUE (grade);


--
-- TOC entry 3429 (class 1259 OID 115626)
-- Name: U_active_class_cat; Type: INDEX; Schema: app; Owner: postgres
--

CREATE UNIQUE INDEX "U_active_class_cat" ON app.class_cats USING btree (class_cat_name) WHERE (active IS TRUE);


--
-- TOC entry 3442 (class 1259 OID 115627)
-- Name: U_active_class_name; Type: INDEX; Schema: app; Owner: postgres
--

CREATE UNIQUE INDEX "U_active_class_name" ON app.classes USING btree (class_name, class_cat_id) WHERE (active IS TRUE);


--
-- TOC entry 3528 (class 1259 OID 115628)
-- Name: U_file_name; Type: INDEX; Schema: app; Owner: postgres
--

CREATE UNIQUE INDEX "U_file_name" ON app.report_card_files USING btree (file_name, rptcard_file_id);


--
-- TOC entry 3481 (class 1259 OID 115629)
-- Name: app_exam_marks_index; Type: INDEX; Schema: app; Owner: postgres
--

CREATE INDEX app_exam_marks_index ON app.exam_marks USING btree (class_sub_exam_id, term_id);


--
-- TOC entry 3800 (class 2618 OID 115096)
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
-- TOC entry 3801 (class 2618 OID 115100)
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
-- TOC entry 3614 (class 2606 OID 115632)
-- Name: communications FK_audience_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communications
    ADD CONSTRAINT "FK_audience_id" FOREIGN KEY (audience_id) REFERENCES app.communication_audience(audience_id);


--
-- TOC entry 3597 (class 2606 OID 115637)
-- Name: blogs FK_blog_class; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.blogs
    ADD CONSTRAINT "FK_blog_class" FOREIGN KEY (class_id) REFERENCES app.classes(class_id);


--
-- TOC entry 3598 (class 2606 OID 115642)
-- Name: blogs FK_blog_teacher; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.blogs
    ADD CONSTRAINT "FK_blog_teacher" FOREIGN KEY (teacher_id) REFERENCES app.employees(emp_id);


--
-- TOC entry 3655 (class 2606 OID 115647)
-- Name: student_buses FK_bus_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.student_buses
    ADD CONSTRAINT "FK_bus_id" FOREIGN KEY (bus_id) REFERENCES app.buses(bus_id);


--
-- TOC entry 3606 (class 2606 OID 115652)
-- Name: classes FK_class_cat_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.classes
    ADD CONSTRAINT "FK_class_cat_id" FOREIGN KEY (class_cat_id) REFERENCES app.class_cats(class_cat_id);


--
-- TOC entry 3661 (class 2606 OID 115657)
-- Name: subjects FK_class_cat_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.subjects
    ADD CONSTRAINT "FK_class_cat_id" FOREIGN KEY (class_cat_id) REFERENCES app.class_cats(class_cat_id);


--
-- TOC entry 3657 (class 2606 OID 115662)
-- Name: student_class_history FK_class_history_class; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.student_class_history
    ADD CONSTRAINT "FK_class_history_class" FOREIGN KEY (class_id) REFERENCES app.classes(class_id);


--
-- TOC entry 3658 (class 2606 OID 115667)
-- Name: student_class_history FK_class_history_student; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.student_class_history
    ADD CONSTRAINT "FK_class_history_student" FOREIGN KEY (student_id) REFERENCES app.students(student_id);


--
-- TOC entry 3601 (class 2606 OID 115672)
-- Name: class_subject_exams FK_class_subect_exam_type; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.class_subject_exams
    ADD CONSTRAINT "FK_class_subect_exam_type" FOREIGN KEY (exam_type_id) REFERENCES app.exam_types(exam_type_id);


--
-- TOC entry 3602 (class 2606 OID 115677)
-- Name: class_subject_exams FK_class_subject; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.class_subject_exams
    ADD CONSTRAINT "FK_class_subject" FOREIGN KEY (class_subject_id) REFERENCES app.class_subjects(class_subject_id);


--
-- TOC entry 3603 (class 2606 OID 115682)
-- Name: class_subjects FK_class_subject_class; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.class_subjects
    ADD CONSTRAINT "FK_class_subject_class" FOREIGN KEY (class_id) REFERENCES app.classes(class_id);


--
-- TOC entry 3625 (class 2606 OID 115687)
-- Name: exam_marks FK_class_subject_exam; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.exam_marks
    ADD CONSTRAINT "FK_class_subject_exam" FOREIGN KEY (class_sub_exam_id) REFERENCES app.class_subject_exams(class_sub_exam_id);


--
-- TOC entry 3604 (class 2606 OID 115692)
-- Name: class_subjects FK_class_subject_subject; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.class_subjects
    ADD CONSTRAINT "FK_class_subject_subject" FOREIGN KEY (subject_id) REFERENCES app.subjects(subject_id);


--
-- TOC entry 3607 (class 2606 OID 115697)
-- Name: classes FK_class_teacher; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.classes
    ADD CONSTRAINT "FK_class_teacher" FOREIGN KEY (teacher_id) REFERENCES app.employees(emp_id);


--
-- TOC entry 3610 (class 2606 OID 115702)
-- Name: communication_feedback FK_com_class_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communication_feedback
    ADD CONSTRAINT "FK_com_class_id" FOREIGN KEY (class_id) REFERENCES app.classes(class_id);


--
-- TOC entry 3615 (class 2606 OID 115707)
-- Name: communications FK_com_class_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communications
    ADD CONSTRAINT "FK_com_class_id" FOREIGN KEY (class_id) REFERENCES app.classes(class_id);


--
-- TOC entry 3611 (class 2606 OID 115712)
-- Name: communication_feedback FK_com_guardian_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communication_feedback
    ADD CONSTRAINT "FK_com_guardian_id" FOREIGN KEY (guardian_id) REFERENCES app.guardians(guardian_id);


--
-- TOC entry 3616 (class 2606 OID 115717)
-- Name: communications FK_com_guardian_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communications
    ADD CONSTRAINT "FK_com_guardian_id" FOREIGN KEY (guardian_id) REFERENCES app.guardians(guardian_id);


--
-- TOC entry 3617 (class 2606 OID 115722)
-- Name: communications FK_com_message_from; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communications
    ADD CONSTRAINT "FK_com_message_from" FOREIGN KEY (message_from) REFERENCES app.employees(emp_id);


--
-- TOC entry 3612 (class 2606 OID 115727)
-- Name: communication_feedback FK_com_student_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communication_feedback
    ADD CONSTRAINT "FK_com_student_id" FOREIGN KEY (student_id) REFERENCES app.students(student_id);


--
-- TOC entry 3618 (class 2606 OID 115732)
-- Name: communications FK_com_student_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communications
    ADD CONSTRAINT "FK_com_student_id" FOREIGN KEY (student_id) REFERENCES app.students(student_id);


--
-- TOC entry 3619 (class 2606 OID 115737)
-- Name: communications FK_com_type_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communications
    ADD CONSTRAINT "FK_com_type_id" FOREIGN KEY (com_type_id) REFERENCES app.communication_types(com_type_id);


--
-- TOC entry 3609 (class 2606 OID 115742)
-- Name: communication_emails FK_comm_email_comm; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communication_emails
    ADD CONSTRAINT "FK_comm_email_comm" FOREIGN KEY (com_id) REFERENCES app.communications(com_id);


--
-- TOC entry 3613 (class 2606 OID 115747)
-- Name: communication_sms FK_comm_sms_comm; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communication_sms
    ADD CONSTRAINT "FK_comm_sms_comm" FOREIGN KEY (com_id) REFERENCES app.communications(com_id);


--
-- TOC entry 3621 (class 2606 OID 115752)
-- Name: credits FK_credit_payment; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.credits
    ADD CONSTRAINT "FK_credit_payment" FOREIGN KEY (payment_id) REFERENCES app.payments(payment_id);


--
-- TOC entry 3622 (class 2606 OID 115757)
-- Name: credits FK_credit_student; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.credits
    ADD CONSTRAINT "FK_credit_student" FOREIGN KEY (student_id) REFERENCES app.students(student_id);


--
-- TOC entry 3620 (class 2606 OID 115762)
-- Name: communications FK_email_post_status; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communications
    ADD CONSTRAINT "FK_email_post_status" FOREIGN KEY (post_status_id) REFERENCES app.blog_post_statuses(post_status_id);


--
-- TOC entry 3623 (class 2606 OID 115767)
-- Name: employees FK_emp_cat_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.employees
    ADD CONSTRAINT "FK_emp_cat_id" FOREIGN KEY (emp_cat_id) REFERENCES app.employee_cats(emp_cat_id);


--
-- TOC entry 3624 (class 2606 OID 115772)
-- Name: employees FK_emp_dept_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.employees
    ADD CONSTRAINT "FK_emp_dept_id" FOREIGN KEY (dept_id) REFERENCES app.departments(dept_id);


--
-- TOC entry 3640 (class 2606 OID 115777)
-- Name: permissions FK_emp_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.permissions
    ADD CONSTRAINT "FK_emp_id" FOREIGN KEY (emp_id) REFERENCES app.employees(emp_id);


--
-- TOC entry 3626 (class 2606 OID 115782)
-- Name: exam_marks FK_exam_student; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.exam_marks
    ADD CONSTRAINT "FK_exam_student" FOREIGN KEY (student_id) REFERENCES app.students(student_id);


--
-- TOC entry 3627 (class 2606 OID 115787)
-- Name: exam_marks FK_exam_term; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.exam_marks
    ADD CONSTRAINT "FK_exam_term" FOREIGN KEY (term_id) REFERENCES app.terms(term_id);


--
-- TOC entry 3628 (class 2606 OID 115792)
-- Name: exam_types FK_exam_type_class_cat; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.exam_types
    ADD CONSTRAINT "FK_exam_type_class_cat" FOREIGN KEY (class_cat_id) REFERENCES app.class_cats(class_cat_id);


--
-- TOC entry 3647 (class 2606 OID 115797)
-- Name: reportcard_data FK_exam_type_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.reportcard_data
    ADD CONSTRAINT "FK_exam_type_id" FOREIGN KEY (exam_type_id) REFERENCES app.exam_types(exam_type_id);


--
-- TOC entry 3629 (class 2606 OID 115802)
-- Name: homework FK_homework_class_subject; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.homework
    ADD CONSTRAINT "FK_homework_class_subject" FOREIGN KEY (class_subject_id) REFERENCES app.class_subjects(class_subject_id);


--
-- TOC entry 3630 (class 2606 OID 115807)
-- Name: homework FK_homework_post_status; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.homework
    ADD CONSTRAINT "FK_homework_post_status" FOREIGN KEY (post_status_id) REFERENCES app.blog_post_statuses(post_status_id);


--
-- TOC entry 3659 (class 2606 OID 115812)
-- Name: students FK_installment_option; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.students
    ADD CONSTRAINT "FK_installment_option" FOREIGN KEY (installment_option_id) REFERENCES app.installment_options(installment_id);


--
-- TOC entry 3631 (class 2606 OID 115817)
-- Name: invoice_line_items FK_inv_item_fee_item; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.invoice_line_items
    ADD CONSTRAINT "FK_inv_item_fee_item" FOREIGN KEY (student_fee_item_id) REFERENCES app.student_fee_items(student_fee_item_id);


--
-- TOC entry 3632 (class 2606 OID 115822)
-- Name: invoice_line_items FK_inv_item_invoice; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.invoice_line_items
    ADD CONSTRAINT "FK_inv_item_invoice" FOREIGN KEY (inv_id) REFERENCES app.invoices(inv_id);


--
-- TOC entry 3633 (class 2606 OID 115827)
-- Name: invoices FK_invoice_student; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.invoices
    ADD CONSTRAINT "FK_invoice_student" FOREIGN KEY (student_id) REFERENCES app.students(student_id);


--
-- TOC entry 3634 (class 2606 OID 115832)
-- Name: payment_inv_items FK_payment_fee_item_payment; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.payment_inv_items
    ADD CONSTRAINT "FK_payment_fee_item_payment" FOREIGN KEY (payment_id) REFERENCES app.payments(payment_id);


--
-- TOC entry 3635 (class 2606 OID 115837)
-- Name: payment_inv_items FK_payment_inv; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.payment_inv_items
    ADD CONSTRAINT "FK_payment_inv" FOREIGN KEY (inv_id) REFERENCES app.invoices(inv_id);


--
-- TOC entry 3636 (class 2606 OID 115842)
-- Name: payment_inv_items FK_payment_inv_item; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.payment_inv_items
    ADD CONSTRAINT "FK_payment_inv_item" FOREIGN KEY (inv_item_id) REFERENCES app.invoice_line_items(inv_item_id);


--
-- TOC entry 3637 (class 2606 OID 115847)
-- Name: payment_replacement_items FK_payment_item; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.payment_replacement_items
    ADD CONSTRAINT "FK_payment_item" FOREIGN KEY (student_fee_item_id) REFERENCES app.student_fee_items(student_fee_item_id);


--
-- TOC entry 3638 (class 2606 OID 115852)
-- Name: payment_replacement_items FK_payment_replace_item_payment; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.payment_replacement_items
    ADD CONSTRAINT "FK_payment_replace_item_payment" FOREIGN KEY (payment_id) REFERENCES app.payments(payment_id);


--
-- TOC entry 3639 (class 2606 OID 115857)
-- Name: payments FK_payments_student; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.payments
    ADD CONSTRAINT "FK_payments_student" FOREIGN KEY (student_id) REFERENCES app.students(student_id);


--
-- TOC entry 3593 (class 2606 OID 115862)
-- Name: blog_posts FK_post_blog; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.blog_posts
    ADD CONSTRAINT "FK_post_blog" FOREIGN KEY (blog_id) REFERENCES app.blogs(blog_id);


--
-- TOC entry 3594 (class 2606 OID 115867)
-- Name: blog_posts FK_post_status; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.blog_posts
    ADD CONSTRAINT "FK_post_status" FOREIGN KEY (post_status_id) REFERENCES app.blog_post_statuses(post_status_id);


--
-- TOC entry 3595 (class 2606 OID 115872)
-- Name: blog_posts FK_post_type; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.blog_posts
    ADD CONSTRAINT "FK_post_type" FOREIGN KEY (post_type_id) REFERENCES app.blog_post_types(post_type_id);


--
-- TOC entry 3596 (class 2606 OID 115877)
-- Name: blog_posts FK_posted_by; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.blog_posts
    ADD CONSTRAINT "FK_posted_by" FOREIGN KEY (created_by) REFERENCES app.employees(emp_id);


--
-- TOC entry 3644 (class 2606 OID 115882)
-- Name: report_cards FK_report_class; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.report_cards
    ADD CONSTRAINT "FK_report_class" FOREIGN KEY (class_id) REFERENCES app.classes(class_id);


--
-- TOC entry 3645 (class 2606 OID 115887)
-- Name: report_cards FK_report_student; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.report_cards
    ADD CONSTRAINT "FK_report_student" FOREIGN KEY (student_id) REFERENCES app.students(student_id);


--
-- TOC entry 3646 (class 2606 OID 115892)
-- Name: report_cards FK_report_term; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.report_cards
    ADD CONSTRAINT "FK_report_term" FOREIGN KEY (term_id) REFERENCES app.terms(term_id);


--
-- TOC entry 3652 (class 2606 OID 115897)
-- Name: reportcard_data_subj_ovrlls FK_reportcard_data_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.reportcard_data_subj_ovrlls
    ADD CONSTRAINT "FK_reportcard_data_id" FOREIGN KEY (reportcard_data_id) REFERENCES app.reportcard_data(reportcard_data_id);


--
-- TOC entry 3651 (class 2606 OID 115902)
-- Name: reportcard_data_overalls FK_reportcard_data_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.reportcard_data_overalls
    ADD CONSTRAINT "FK_reportcard_data_id" FOREIGN KEY (reportcard_data_id) REFERENCES app.reportcard_data(reportcard_data_id);


--
-- TOC entry 3663 (class 2606 OID 115907)
-- Name: student_fee_items FK_student_fee_items; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.student_fee_items
    ADD CONSTRAINT "FK_student_fee_items" FOREIGN KEY (fee_item_id) REFERENCES app.fee_items(fee_item_id);


--
-- TOC entry 3664 (class 2606 OID 115912)
-- Name: student_fee_items FK_student_fee_items_student; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.student_fee_items
    ADD CONSTRAINT "FK_student_fee_items_student" FOREIGN KEY (student_id) REFERENCES app.students(student_id);


--
-- TOC entry 3665 (class 2606 OID 115917)
-- Name: student_guardians FK_student_guardian_guardian; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.student_guardians
    ADD CONSTRAINT "FK_student_guardian_guardian" FOREIGN KEY (guardian_id) REFERENCES app.guardians(guardian_id);


--
-- TOC entry 3666 (class 2606 OID 115922)
-- Name: student_guardians FK_student_guardian_student; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.student_guardians
    ADD CONSTRAINT "FK_student_guardian_student" FOREIGN KEY (student_id) REFERENCES app.students(student_id);


--
-- TOC entry 3667 (class 2606 OID 115927)
-- Name: student_medical_history FK_student_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.student_medical_history
    ADD CONSTRAINT "FK_student_id" FOREIGN KEY (student_id) REFERENCES app.students(student_id);


--
-- TOC entry 3656 (class 2606 OID 115932)
-- Name: student_buses FK_student_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.student_buses
    ADD CONSTRAINT "FK_student_id" FOREIGN KEY (student_id) REFERENCES app.students(student_id);


--
-- TOC entry 3642 (class 2606 OID 115937)
-- Name: report_card_files FK_student_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.report_card_files
    ADD CONSTRAINT "FK_student_id" FOREIGN KEY (student_id) REFERENCES app.students(student_id);


--
-- TOC entry 3648 (class 2606 OID 115942)
-- Name: reportcard_data FK_student_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.reportcard_data
    ADD CONSTRAINT "FK_student_id" FOREIGN KEY (student_id) REFERENCES app.students(student_id);


--
-- TOC entry 3660 (class 2606 OID 115947)
-- Name: students FK_student_route; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.students
    ADD CONSTRAINT "FK_student_route" FOREIGN KEY (transport_route_id) REFERENCES app.transport_routes(transport_id);


--
-- TOC entry 3649 (class 2606 OID 115952)
-- Name: reportcard_data FK_subject_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.reportcard_data
    ADD CONSTRAINT "FK_subject_id" FOREIGN KEY (subject_id) REFERENCES app.subjects(subject_id);


--
-- TOC entry 3662 (class 2606 OID 115957)
-- Name: subjects FK_subject_teacher; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.subjects
    ADD CONSTRAINT "FK_subject_teacher" FOREIGN KEY (teacher_id) REFERENCES app.employees(emp_id);


--
-- TOC entry 3643 (class 2606 OID 115962)
-- Name: report_card_files FK_term_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.report_card_files
    ADD CONSTRAINT "FK_term_id" FOREIGN KEY (term_id) REFERENCES app.terms(term_id);


--
-- TOC entry 3650 (class 2606 OID 115967)
-- Name: reportcard_data FK_term_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.reportcard_data
    ADD CONSTRAINT "FK_term_id" FOREIGN KEY (term_id) REFERENCES app.terms(term_id);


--
-- TOC entry 3641 (class 2606 OID 115972)
-- Name: permissions FK_user_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.permissions
    ADD CONSTRAINT "FK_user_id" FOREIGN KEY (user_id) REFERENCES app.users(user_id);


--
-- TOC entry 3599 (class 2606 OID 115977)
-- Name: buses bus_driver; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.buses
    ADD CONSTRAINT bus_driver FOREIGN KEY (bus_driver) REFERENCES app.employees(emp_id) MATCH FULL;


--
-- TOC entry 3600 (class 2606 OID 115982)
-- Name: buses bus_guide; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.buses
    ADD CONSTRAINT bus_guide FOREIGN KEY (bus_guide) REFERENCES app.employees(emp_id) MATCH FULL;


--
-- TOC entry 3653 (class 2606 OID 115987)
-- Name: schoolbus_bus_trips bus_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.schoolbus_bus_trips
    ADD CONSTRAINT bus_id FOREIGN KEY (bus_id) REFERENCES app.buses(bus_id) MATCH FULL;


--
-- TOC entry 3671 (class 2606 OID 167623)
-- Name: disciplinary class_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.disciplinary
    ADD CONSTRAINT class_id FOREIGN KEY (class_id) REFERENCES app.classes(class_id) MATCH FULL;


--
-- TOC entry 3608 (class 2606 OID 115992)
-- Name: communication_attachments com_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.communication_attachments
    ADD CONSTRAINT com_id FOREIGN KEY (com_id) REFERENCES app.communications(com_id) MATCH FULL;


--
-- TOC entry 3605 (class 2606 OID 136885)
-- Name: class_timetables fk_class_timetable_subject; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.class_timetables
    ADD CONSTRAINT fk_class_timetable_subject FOREIGN KEY (subject_id) REFERENCES app.subjects(subject_id);


--
-- TOC entry 3668 (class 2606 OID 140928)
-- Name: teacher_timetables fk_teacher_timetable_subject; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.teacher_timetables
    ADD CONSTRAINT fk_teacher_timetable_subject FOREIGN KEY (subject_id) REFERENCES app.subjects(subject_id);


--
-- TOC entry 3654 (class 2606 OID 115997)
-- Name: schoolbus_bus_trips schoolbus_trip_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.schoolbus_bus_trips
    ADD CONSTRAINT schoolbus_trip_id FOREIGN KEY (schoolbus_trip_id) REFERENCES app.schoolbus_trips(schoolbus_trip_id) MATCH FULL;


--
-- TOC entry 3669 (class 2606 OID 141663)
-- Name: absenteeism student_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.absenteeism
    ADD CONSTRAINT student_id FOREIGN KEY (student_id) REFERENCES app.students(student_id) MATCH FULL;


--
-- TOC entry 3670 (class 2606 OID 167618)
-- Name: disciplinary student_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.disciplinary
    ADD CONSTRAINT student_id FOREIGN KEY (student_id) REFERENCES app.students(student_id) MATCH FULL;


--
-- TOC entry 3672 (class 2606 OID 167628)
-- Name: disciplinary term_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY app.disciplinary
    ADD CONSTRAINT term_id FOREIGN KEY (term_id) REFERENCES app.terms(term_id) MATCH FULL;


-- Completed on 2021-09-01 09:13:03

--
-- PostgreSQL database dump complete
--

