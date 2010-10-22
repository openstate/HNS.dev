--- Sessions

CREATE TABLE sys_sessions (
    id character varying(32) NOT NULL,
    created timestamp without time zone DEFAULT now() NOT NULL,
    data bytea NOT NULL,
    modified timestamp without time zone DEFAULT now() NOT NULL
);
ALTER TABLE ONLY sys_sessions
    ADD CONSTRAINT sys_sessions_pkey PRIMARY KEY (id);