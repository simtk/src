
CREATE SEQUENCE plugin_datashare_template_pk_seq;

CREATE TABLE plugin_datashare_template (
    template_id integer DEFAULT nextval(('plugin_datashare_template_pk_seq'::text)::regclass) NOT NULL,
    title varchar(255),
    display integer default 1,
    sequence integer
);

ALTER sequence plugin_datashare_template_pk_seq OWNED BY plugin_datashare_template.template_id;

