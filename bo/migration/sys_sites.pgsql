CREATE TABLE sys_sites (
    id serial NOT NULL,
    title character varying(255) NOT NULL,
    "domain" character varying(255) NOT NULL,
    locale character varying(5) NOT NULL,
    "template" character varying(255) NOT NULL
);

ALTER TABLE ONLY sys_sites
    ADD CONSTRAINT sys_sites_pkey PRIMARY KEY (id);

