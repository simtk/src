
CREATE SEQUENCE plugin_datashare_pk_seq;

CREATE TABLE plugin_datashare (
    study_id integer DEFAULT nextval(('plugin_datashare_pk_seq'::text)::regclass) NOT NULL,
    group_id integer NOT NULL,
	title varchar(255),
    description text,
    logo text,
    license text,
    is_private integer DEFAULT 0 NOT NULL,
	date_created integer,
	token integer,
	active integer,
  template_id integer DEFAULT 1 NOT NULL
);

ALTER plugin_datashare_pk_seq OWNED BY plugin_datashare.study_id;

ALTER TABLE ONLY plugin_datashare ADD CONSTRAINT datashare_pkey PRIMARY KEY (study_id);
