
CREATE SEQUENCE plugin_publications_pk_seq;

CREATE TABLE plugin_publications (
    pub_id integer DEFAULT nextval(('plugin_publications_pk_seq'::text)::regclass) NOT NULL,
    group_id integer NOT NULL,
    publication text NOT NULL,
    publication_year integer NOT NULL,
    url text,
    is_primary integer DEFAULT 0 NOT NULL,
    abstract text DEFAULT ''::text NOT NULL
);

ALTER plugin_publications_pk_seq OWNED BY plugin_publications.pub_id;

ALTER TABLE ONLY plugin_publications ADD CONSTRAINT publications_pkey PRIMARY KEY (pub_id);


CREATE INDEX publications_group_id ON plugin_publications USING btree (group_id);


CREATE INDEX publications_year ON plugin_publications USING btree (publication_year);

