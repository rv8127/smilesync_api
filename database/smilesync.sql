--
-- PostgreSQL database dump
--

\restrict 9Uc6PpW34dzgjWNkOWR64zazQazJkt2UGz3Y9hOHHc6bX8XBZD4gaxzMDqbRIdB

-- Dumped from database version 18.4
-- Dumped by pg_dump version 18.4

-- Started on 2026-10-10 18:43:04

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- TOC entry 6 (class 2615 OID 16961)
-- Name: smilesync_database; Type: SCHEMA; Schema: -; Owner: -
--

CREATE SCHEMA smilesync_database;


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- TOC entry 244 (class 1259 OID 16926)
-- Name: activity_log; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.activity_log (
    log_id integer NOT NULL,
    actor_type character varying(20) DEFAULT 'system_admin'::character varying NOT NULL,
    actor_id integer,
    actor_email character varying(255),
    action character varying(50) NOT NULL,
    target_type character varying(20),
    target_id integer,
    description text NOT NULL,
    created_at timestamp without time zone DEFAULT now() NOT NULL
);


--
-- TOC entry 243 (class 1259 OID 16925)
-- Name: activity_log_log_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.activity_log_log_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 5167 (class 0 OID 0)
-- Dependencies: 243
-- Name: activity_log_log_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.activity_log_log_id_seq OWNED BY public.activity_log.log_id;


--
-- TOC entry 242 (class 1259 OID 16895)
-- Name: announcement; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.announcement (
    announcement_id integer NOT NULL,
    clinic_id integer NOT NULL,
    title character varying(150) NOT NULL,
    message character varying(500),
    type character varying(20) DEFAULT 'info'::character varying,
    announcement_date date DEFAULT CURRENT_DATE
);


--
-- TOC entry 241 (class 1259 OID 16894)
-- Name: announcement_announcement_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.announcement_announcement_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 5168 (class 0 OID 0)
-- Dependencies: 241
-- Name: announcement_announcement_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.announcement_announcement_id_seq OWNED BY public.announcement.announcement_id;


--
-- TOC entry 236 (class 1259 OID 16814)
-- Name: appointment; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.appointment (
    appointment_id integer NOT NULL,
    clinic_id integer NOT NULL,
    patient_id integer NOT NULL,
    dentist_id integer NOT NULL,
    service_id integer NOT NULL,
    appointment_date date NOT NULL,
    appointment_time time without time zone NOT NULL,
    status character varying(20) DEFAULT 'pending'::character varying,
    notes character varying(255),
    schedule_id integer,
    source character varying(10) DEFAULT 'staff'::character varying NOT NULL,
    needs_staff_ack boolean DEFAULT false NOT NULL,
    previous_date date,
    previous_time time without time zone,
    CONSTRAINT appointment_source_check CHECK (((source)::text = ANY ((ARRAY['staff'::character varying, 'app'::character varying])::text[])))
);


--
-- TOC entry 235 (class 1259 OID 16813)
-- Name: appointment_appointment_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.appointment_appointment_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 5169 (class 0 OID 0)
-- Dependencies: 235
-- Name: appointment_appointment_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.appointment_appointment_id_seq OWNED BY public.appointment.appointment_id;


--
-- TOC entry 221 (class 1259 OID 16683)
-- Name: clinic; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.clinic (
    clinic_id integer NOT NULL,
    clinic_code character varying(50) NOT NULL,
    clinic_name character varying(150) NOT NULL,
    contact_email character varying(150),
    contact_number character varying(20),
    address character varying(255),
    opening_time time without time zone,
    closing_time time without time zone,
    status character varying(20) DEFAULT 'active'::character varying NOT NULL,
    admin_id integer NOT NULL,
    tier character varying(20) DEFAULT 'basic'::character varying NOT NULL,
    CONSTRAINT clinic_status_check CHECK (((status)::text = ANY ((ARRAY['active'::character varying, 'inactive'::character varying])::text[]))),
    CONSTRAINT clinic_tier_check CHECK (((tier)::text = ANY ((ARRAY['basic'::character varying, 'pro'::character varying])::text[])))
);


--
-- TOC entry 234 (class 1259 OID 16797)
-- Name: clinic_account; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.clinic_account (
    clinic_id integer NOT NULL,
    login_email character varying(150) NOT NULL,
    password_hash character varying(255) NOT NULL,
    account_status character varying(20) DEFAULT 'active'::character varying,
    reset_code_hash character varying(255),
    reset_code_expires_at timestamp without time zone
);


--
-- TOC entry 220 (class 1259 OID 16682)
-- Name: clinic_clinic_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.clinic_clinic_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 5170 (class 0 OID 0)
-- Dependencies: 220
-- Name: clinic_clinic_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.clinic_clinic_id_seq OWNED BY public.clinic.clinic_id;


--
-- TOC entry 240 (class 1259 OID 16880)
-- Name: clinic_closure; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.clinic_closure (
    clinic_closure_id integer NOT NULL,
    clinic_id integer NOT NULL,
    closure_date date NOT NULL,
    reason character varying(255)
);


--
-- TOC entry 239 (class 1259 OID 16879)
-- Name: clinic_closure_clinic_closure_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.clinic_closure_clinic_closure_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 5171 (class 0 OID 0)
-- Dependencies: 239
-- Name: clinic_closure_clinic_closure_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.clinic_closure_clinic_closure_id_seq OWNED BY public.clinic_closure.clinic_closure_id;


--
-- TOC entry 227 (class 1259 OID 16733)
-- Name: dentist; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.dentist (
    dentist_id integer NOT NULL,
    clinic_id integer NOT NULL,
    first_name character varying(100) NOT NULL,
    last_name character varying(100) NOT NULL,
    specialization character varying(150),
    status character varying(20) DEFAULT 'active'::character varying
);


--
-- TOC entry 226 (class 1259 OID 16732)
-- Name: dentist_dentist_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.dentist_dentist_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 5172 (class 0 OID 0)
-- Dependencies: 226
-- Name: dentist_dentist_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.dentist_dentist_id_seq OWNED BY public.dentist.dentist_id;


--
-- TOC entry 229 (class 1259 OID 16750)
-- Name: dentist_schedule; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.dentist_schedule (
    schedule_id integer CONSTRAINT dentist_schedule_dentist_schedule_id_not_null NOT NULL,
    dentist_id integer NOT NULL,
    day_of_week character varying(10) NOT NULL,
    start_time time without time zone,
    end_time time without time zone
);


--
-- TOC entry 228 (class 1259 OID 16749)
-- Name: dentist_schedule_dentist_schedule_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.dentist_schedule_dentist_schedule_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 5173 (class 0 OID 0)
-- Dependencies: 228
-- Name: dentist_schedule_dentist_schedule_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.dentist_schedule_dentist_schedule_id_seq OWNED BY public.dentist_schedule.schedule_id;


--
-- TOC entry 223 (class 1259 OID 16695)
-- Name: patient; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.patient (
    patient_id integer NOT NULL,
    clinic_id integer NOT NULL,
    first_name character varying(100) NOT NULL,
    last_name character varying(100) NOT NULL,
    contact_email character varying(150),
    contact_number character varying(20),
    address character varying(255),
    birthdate date,
    gender character varying(20),
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    is_archived boolean DEFAULT false NOT NULL
);


--
-- TOC entry 225 (class 1259 OID 16713)
-- Name: patient_account; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.patient_account (
    patient_account_id integer NOT NULL,
    patient_id integer NOT NULL,
    login_email character varying(150) NOT NULL,
    password_hash character varying(255) NOT NULL,
    account_status character varying(20) DEFAULT 'active'::character varying,
    reset_code_hash character varying(255),
    reset_code_expires_at timestamp without time zone
);


--
-- TOC entry 224 (class 1259 OID 16712)
-- Name: patient_account_patient_account_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.patient_account_patient_account_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 5174 (class 0 OID 0)
-- Dependencies: 224
-- Name: patient_account_patient_account_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.patient_account_patient_account_id_seq OWNED BY public.patient_account.patient_account_id;


--
-- TOC entry 222 (class 1259 OID 16694)
-- Name: patient_patient_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.patient_patient_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 5175 (class 0 OID 0)
-- Dependencies: 222
-- Name: patient_patient_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.patient_patient_id_seq OWNED BY public.patient.patient_id;


--
-- TOC entry 231 (class 1259 OID 16765)
-- Name: service; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.service (
    service_id integer NOT NULL,
    clinic_id integer NOT NULL,
    service_name character varying(150) NOT NULL,
    description character varying(255),
    duration_minutes integer,
    price numeric(10,2),
    status character varying(20) DEFAULT 'active'::character varying
);


--
-- TOC entry 230 (class 1259 OID 16764)
-- Name: service_service_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.service_service_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 5176 (class 0 OID 0)
-- Dependencies: 230
-- Name: service_service_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.service_service_id_seq OWNED BY public.service.service_id;


--
-- TOC entry 233 (class 1259 OID 16781)
-- Name: system_admin; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.system_admin (
    admin_id integer NOT NULL,
    first_name character varying(100) NOT NULL,
    last_name character varying(100) NOT NULL,
    login_email character varying(150) NOT NULL,
    password_hash character varying(255) NOT NULL,
    account_status character varying(20) DEFAULT 'active'::character varying,
    role character varying(20) DEFAULT 'support_admin'::character varying NOT NULL,
    last_login_at timestamp without time zone,
    reset_code_hash text,
    reset_code_expires_at timestamp without time zone,
    CONSTRAINT system_admin_role_check CHECK (((role)::text = ANY ((ARRAY['system_admin'::character varying, 'support_admin'::character varying])::text[])))
);


--
-- TOC entry 232 (class 1259 OID 16780)
-- Name: system_admin_admin_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.system_admin_admin_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 5177 (class 0 OID 0)
-- Dependencies: 232
-- Name: system_admin_admin_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.system_admin_admin_id_seq OWNED BY public.system_admin.admin_id;


--
-- TOC entry 246 (class 1259 OID 16967)
-- Name: system_announcement; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.system_announcement (
    system_announcement_id integer NOT NULL,
    title character varying(150) NOT NULL,
    message text,
    type character varying(30) DEFAULT 'info'::character varying NOT NULL,
    announcement_date date DEFAULT CURRENT_DATE NOT NULL,
    expires_on date,
    created_at timestamp without time zone DEFAULT now() NOT NULL
);


--
-- TOC entry 245 (class 1259 OID 16966)
-- Name: system_announcement_system_announcement_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.system_announcement_system_announcement_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 5178 (class 0 OID 0)
-- Dependencies: 245
-- Name: system_announcement_system_announcement_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.system_announcement_system_announcement_id_seq OWNED BY public.system_announcement.system_announcement_id;


--
-- TOC entry 238 (class 1259 OID 16851)
-- Name: treatment_record; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.treatment_record (
    treatment_record_id integer NOT NULL,
    patient_id integer NOT NULL,
    dentist_id integer NOT NULL,
    service_id integer NOT NULL,
    record_date date NOT NULL,
    notes character varying(500)
);


--
-- TOC entry 237 (class 1259 OID 16850)
-- Name: treatment_record_treatment_record_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.treatment_record_treatment_record_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 5179 (class 0 OID 0)
-- Dependencies: 237
-- Name: treatment_record_treatment_record_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.treatment_record_treatment_record_id_seq OWNED BY public.treatment_record.treatment_record_id;


--
-- TOC entry 4947 (class 2604 OID 16929)
-- Name: activity_log log_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.activity_log ALTER COLUMN log_id SET DEFAULT nextval('public.activity_log_log_id_seq'::regclass);


--
-- TOC entry 4944 (class 2604 OID 16898)
-- Name: announcement announcement_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.announcement ALTER COLUMN announcement_id SET DEFAULT nextval('public.announcement_announcement_id_seq'::regclass);


--
-- TOC entry 4938 (class 2604 OID 16817)
-- Name: appointment appointment_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.appointment ALTER COLUMN appointment_id SET DEFAULT nextval('public.appointment_appointment_id_seq'::regclass);


--
-- TOC entry 4921 (class 2604 OID 16686)
-- Name: clinic clinic_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.clinic ALTER COLUMN clinic_id SET DEFAULT nextval('public.clinic_clinic_id_seq'::regclass);


--
-- TOC entry 4943 (class 2604 OID 16883)
-- Name: clinic_closure clinic_closure_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.clinic_closure ALTER COLUMN clinic_closure_id SET DEFAULT nextval('public.clinic_closure_clinic_closure_id_seq'::regclass);


--
-- TOC entry 4929 (class 2604 OID 16736)
-- Name: dentist dentist_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.dentist ALTER COLUMN dentist_id SET DEFAULT nextval('public.dentist_dentist_id_seq'::regclass);


--
-- TOC entry 4931 (class 2604 OID 16753)
-- Name: dentist_schedule schedule_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.dentist_schedule ALTER COLUMN schedule_id SET DEFAULT nextval('public.dentist_schedule_dentist_schedule_id_seq'::regclass);


--
-- TOC entry 4924 (class 2604 OID 16698)
-- Name: patient patient_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.patient ALTER COLUMN patient_id SET DEFAULT nextval('public.patient_patient_id_seq'::regclass);


--
-- TOC entry 4927 (class 2604 OID 16716)
-- Name: patient_account patient_account_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.patient_account ALTER COLUMN patient_account_id SET DEFAULT nextval('public.patient_account_patient_account_id_seq'::regclass);


--
-- TOC entry 4932 (class 2604 OID 16768)
-- Name: service service_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.service ALTER COLUMN service_id SET DEFAULT nextval('public.service_service_id_seq'::regclass);


--
-- TOC entry 4934 (class 2604 OID 16784)
-- Name: system_admin admin_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.system_admin ALTER COLUMN admin_id SET DEFAULT nextval('public.system_admin_admin_id_seq'::regclass);


--
-- TOC entry 4950 (class 2604 OID 16970)
-- Name: system_announcement system_announcement_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.system_announcement ALTER COLUMN system_announcement_id SET DEFAULT nextval('public.system_announcement_system_announcement_id_seq'::regclass);


--
-- TOC entry 4942 (class 2604 OID 16854)
-- Name: treatment_record treatment_record_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.treatment_record ALTER COLUMN treatment_record_id SET DEFAULT nextval('public.treatment_record_treatment_record_id_seq'::regclass);


--
-- TOC entry 4993 (class 2606 OID 16940)
-- Name: activity_log activity_log_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.activity_log
    ADD CONSTRAINT activity_log_pkey PRIMARY KEY (log_id);


--
-- TOC entry 4991 (class 2606 OID 16907)
-- Name: announcement announcement_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.announcement
    ADD CONSTRAINT announcement_pkey PRIMARY KEY (announcement_id);


--
-- TOC entry 4985 (class 2606 OID 16827)
-- Name: appointment appointment_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.appointment
    ADD CONSTRAINT appointment_pkey PRIMARY KEY (appointment_id);


--
-- TOC entry 4981 (class 2606 OID 16807)
-- Name: clinic_account clinic_account_login_email_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.clinic_account
    ADD CONSTRAINT clinic_account_login_email_key UNIQUE (login_email);


--
-- TOC entry 4983 (class 2606 OID 16805)
-- Name: clinic_account clinic_account_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.clinic_account
    ADD CONSTRAINT clinic_account_pkey PRIMARY KEY (clinic_id);


--
-- TOC entry 4959 (class 2606 OID 16693)
-- Name: clinic clinic_clinic_code_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.clinic
    ADD CONSTRAINT clinic_clinic_code_key UNIQUE (clinic_code);


--
-- TOC entry 4989 (class 2606 OID 16888)
-- Name: clinic_closure clinic_closure_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.clinic_closure
    ADD CONSTRAINT clinic_closure_pkey PRIMARY KEY (clinic_closure_id);


--
-- TOC entry 4961 (class 2606 OID 16691)
-- Name: clinic clinic_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.clinic
    ADD CONSTRAINT clinic_pkey PRIMARY KEY (clinic_id);


--
-- TOC entry 4971 (class 2606 OID 16743)
-- Name: dentist dentist_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.dentist
    ADD CONSTRAINT dentist_pkey PRIMARY KEY (dentist_id);


--
-- TOC entry 4973 (class 2606 OID 16758)
-- Name: dentist_schedule dentist_schedule_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.dentist_schedule
    ADD CONSTRAINT dentist_schedule_pkey PRIMARY KEY (schedule_id);


--
-- TOC entry 4965 (class 2606 OID 16725)
-- Name: patient_account patient_account_login_email_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.patient_account
    ADD CONSTRAINT patient_account_login_email_key UNIQUE (login_email);


--
-- TOC entry 4967 (class 2606 OID 16919)
-- Name: patient_account patient_account_patient_id_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.patient_account
    ADD CONSTRAINT patient_account_patient_id_key UNIQUE (patient_id);


--
-- TOC entry 4969 (class 2606 OID 16723)
-- Name: patient_account patient_account_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.patient_account
    ADD CONSTRAINT patient_account_pkey PRIMARY KEY (patient_account_id);


--
-- TOC entry 4963 (class 2606 OID 16706)
-- Name: patient patient_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.patient
    ADD CONSTRAINT patient_pkey PRIMARY KEY (patient_id);


--
-- TOC entry 4975 (class 2606 OID 16774)
-- Name: service service_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.service
    ADD CONSTRAINT service_pkey PRIMARY KEY (service_id);


--
-- TOC entry 4977 (class 2606 OID 16796)
-- Name: system_admin system_admin_login_email_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.system_admin
    ADD CONSTRAINT system_admin_login_email_key UNIQUE (login_email);


--
-- TOC entry 4979 (class 2606 OID 16794)
-- Name: system_admin system_admin_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.system_admin
    ADD CONSTRAINT system_admin_pkey PRIMARY KEY (admin_id);


--
-- TOC entry 4997 (class 2606 OID 16982)
-- Name: system_announcement system_announcement_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.system_announcement
    ADD CONSTRAINT system_announcement_pkey PRIMARY KEY (system_announcement_id);


--
-- TOC entry 4987 (class 2606 OID 16863)
-- Name: treatment_record treatment_record_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.treatment_record
    ADD CONSTRAINT treatment_record_pkey PRIMARY KEY (treatment_record_id);


--
-- TOC entry 4994 (class 1259 OID 16941)
-- Name: idx_activity_log_created_at; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_activity_log_created_at ON public.activity_log USING btree (created_at DESC);


--
-- TOC entry 4995 (class 1259 OID 16942)
-- Name: idx_activity_log_target; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_activity_log_target ON public.activity_log USING btree (target_type, target_id);


--
-- TOC entry 5014 (class 2606 OID 16908)
-- Name: announcement announcement_clinic_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.announcement
    ADD CONSTRAINT announcement_clinic_id_fkey FOREIGN KEY (clinic_id) REFERENCES public.clinic(clinic_id);


--
-- TOC entry 5005 (class 2606 OID 16828)
-- Name: appointment appointment_clinic_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.appointment
    ADD CONSTRAINT appointment_clinic_id_fkey FOREIGN KEY (clinic_id) REFERENCES public.clinic(clinic_id);


--
-- TOC entry 5006 (class 2606 OID 16838)
-- Name: appointment appointment_dentist_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.appointment
    ADD CONSTRAINT appointment_dentist_id_fkey FOREIGN KEY (dentist_id) REFERENCES public.dentist(dentist_id);


--
-- TOC entry 5007 (class 2606 OID 16833)
-- Name: appointment appointment_patient_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.appointment
    ADD CONSTRAINT appointment_patient_id_fkey FOREIGN KEY (patient_id) REFERENCES public.patient(patient_id);


--
-- TOC entry 5008 (class 2606 OID 16843)
-- Name: appointment appointment_service_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.appointment
    ADD CONSTRAINT appointment_service_id_fkey FOREIGN KEY (service_id) REFERENCES public.service(service_id);


--
-- TOC entry 5004 (class 2606 OID 16808)
-- Name: clinic_account clinic_account_clinic_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.clinic_account
    ADD CONSTRAINT clinic_account_clinic_id_fkey FOREIGN KEY (clinic_id) REFERENCES public.clinic(clinic_id);


--
-- TOC entry 5013 (class 2606 OID 16889)
-- Name: clinic_closure clinic_closure_clinic_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.clinic_closure
    ADD CONSTRAINT clinic_closure_clinic_id_fkey FOREIGN KEY (clinic_id) REFERENCES public.clinic(clinic_id);


--
-- TOC entry 5001 (class 2606 OID 16744)
-- Name: dentist dentist_clinic_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.dentist
    ADD CONSTRAINT dentist_clinic_id_fkey FOREIGN KEY (clinic_id) REFERENCES public.clinic(clinic_id);


--
-- TOC entry 5002 (class 2606 OID 16759)
-- Name: dentist_schedule dentist_schedule_dentist_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.dentist_schedule
    ADD CONSTRAINT dentist_schedule_dentist_id_fkey FOREIGN KEY (dentist_id) REFERENCES public.dentist(dentist_id);


--
-- TOC entry 5009 (class 2606 OID 16913)
-- Name: appointment fk_appointment_schedule; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.appointment
    ADD CONSTRAINT fk_appointment_schedule FOREIGN KEY (schedule_id) REFERENCES public.dentist_schedule(schedule_id) ON DELETE SET NULL;


--
-- TOC entry 4998 (class 2606 OID 16948)
-- Name: clinic fk_clinic_admin; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.clinic
    ADD CONSTRAINT fk_clinic_admin FOREIGN KEY (admin_id) REFERENCES public.system_admin(admin_id);


--
-- TOC entry 5000 (class 2606 OID 16726)
-- Name: patient_account patient_account_patient_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.patient_account
    ADD CONSTRAINT patient_account_patient_id_fkey FOREIGN KEY (patient_id) REFERENCES public.patient(patient_id);


--
-- TOC entry 4999 (class 2606 OID 16707)
-- Name: patient patient_clinic_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.patient
    ADD CONSTRAINT patient_clinic_id_fkey FOREIGN KEY (clinic_id) REFERENCES public.clinic(clinic_id);


--
-- TOC entry 5003 (class 2606 OID 16775)
-- Name: service service_clinic_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.service
    ADD CONSTRAINT service_clinic_id_fkey FOREIGN KEY (clinic_id) REFERENCES public.clinic(clinic_id);


--
-- TOC entry 5010 (class 2606 OID 16869)
-- Name: treatment_record treatment_record_dentist_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.treatment_record
    ADD CONSTRAINT treatment_record_dentist_id_fkey FOREIGN KEY (dentist_id) REFERENCES public.dentist(dentist_id);


--
-- TOC entry 5011 (class 2606 OID 16864)
-- Name: treatment_record treatment_record_patient_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.treatment_record
    ADD CONSTRAINT treatment_record_patient_id_fkey FOREIGN KEY (patient_id) REFERENCES public.patient(patient_id);


--
-- TOC entry 5012 (class 2606 OID 16874)
-- Name: treatment_record treatment_record_service_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.treatment_record
    ADD CONSTRAINT treatment_record_service_id_fkey FOREIGN KEY (service_id) REFERENCES public.service(service_id);


-- Completed on 2026-10-10 18:43:04

--
-- PostgreSQL database dump complete
--

\unrestrict 9Uc6PpW34dzgjWNkOWR64zazQazJkt2UGz3Y9hOHHc6bX8XBZD4gaxzMDqbRIdB

