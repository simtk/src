
CREATE SEQUENCE plugin_api_pk_seq;

CREATE TABLE plugin_api (
    api_id integer DEFAULT nextval(('plugin_api_pk_seq'::text)::regclass) NOT NULL,
    api_key varchar(255) NOT NULL,
    status integer DEFAULT 1
);

ALTER plugin_api_pk_seq OWNED BY plugin_api.api_id;

ALTER TABLE ONLY plugin_api ADD CONSTRAINT api_pkey PRIMARY KEY (api_id);
