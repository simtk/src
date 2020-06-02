--
-- PostgreSQL database dump
--

SET statement_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;

SET search_path = public, pg_catalog;

SET default_tablespace = '';

SET default_with_oids = false;

--
-- Name: news_bytes; Type: TABLE; Schema: public; Owner: gforge; Tablespace: 
--

CREATE TABLE plugin_simtk_news (
    id integer DEFAULT nextval(('plugin_simtk_news_pk_seq'::text)::regclass) NOT NULL,
    group_id integer DEFAULT 0 NOT NULL,
    submitted_by integer DEFAULT 0 NOT NULL,
    is_approved integer DEFAULT 0 NOT NULL,
    post_date integer DEFAULT 0 NOT NULL,
    forum_id integer DEFAULT 0 NOT NULL,
    summary text,
    details text,
    simtk_request_global boolean DEFAULT false NOT NULL,
    simtk_sidebar_display boolean DEFAULT true NOT NULL,
    simtk_image text,
    simtk_image_caption text,
    simtk_video text,
    simtk_image_width integer,
    simtk_image_height integer,
    simtk_make_diff boolean DEFAULT false NOT NULL,
    simtk_diff_is_approved integer DEFAULT 0 NOT NULL
);


ALTER TABLE public.plugin_simtk_news OWNER TO gforge;

--
-- Data for Name: news_bytes; Type: TABLE DATA; Schema: public; Owner: gforge
--


--
-- Name: news_bytes_pkey; Type: CONSTRAINT; Schema: public; Owner: gforge; Tablespace: 
--

ALTER TABLE ONLY plugin_simtk_news
    ADD CONSTRAINT plugin_simtk_news_pkey PRIMARY KEY (id);


--
-- Name: news_approved_date; Type: INDEX; Schema: public; Owner: gforge; Tablespace: 
--

CREATE INDEX simtk_news_approved_date ON plugin_simtk_news USING btree (is_approved, post_date);


--
-- Name: news_bytes_approved; Type: INDEX; Schema: public; Owner: gforge; Tablespace: 
--

CREATE INDEX simtk_news_approved ON plugin_simtk_news USING btree (is_approved);


--
-- Name: news_bytes_forum; Type: INDEX; Schema: public; Owner: gforge; Tablespace: 
--

-- CREATE INDEX news_bytes_forum ON news_bytes USING btree (forum_id);


--
-- Name: news_bytes_group; Type: INDEX; Schema: public; Owner: gforge; Tablespace: 
--

CREATE INDEX plugin_simtk_news_group ON plugin_simtk_news USING btree (group_id);


--
-- Name: news_group_date; Type: INDEX; Schema: public; Owner: gforge; Tablespace: 
--

CREATE INDEX simtk_news_group_date ON plugin_simtk_news USING btree (group_id, post_date);


--
-- Name: news_bytes_ts_update; Type: TRIGGER; Schema: public; Owner: gforge
--

CREATE TRIGGER plugin_simtk_news_ts_update AFTER INSERT OR DELETE OR UPDATE ON plugin_simtk_news FOR EACH ROW EXECUTE PROCEDURE update_vectors('plugin_simtk_news');


--
-- Name: news_bytes_group_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: gforge
--

ALTER TABLE ONLY plugin_simtk_news
    ADD CONSTRAINT plugin_simtk_news_group_id_fkey FOREIGN KEY (group_id) REFERENCES groups(group_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: news_bytes_submitted_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: gforge
--

ALTER TABLE ONLY plugin_simtk_news
    ADD CONSTRAINT plugin_simtk_news_submitted_by_fkey FOREIGN KEY (submitted_by) REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

