CREATE TABLE usr_users (
    id serial NOT NULL,    
    "password" character varying(40) NOT NULL,
    email character varying(100) NOT NULL,
    created timestamp without time zone DEFAULT now() NOT NULL,
    updated timestamp without time zone DEFAULT now() NOT NULL,
    deleted timestamp without time zone,
    admin smallint DEFAULT 0 NOT NULL,
    accepte_user smallint DEFAULT 0 NOT NULL,
);

ALTER TABLE ONLY usr_users
    ADD CONSTRAINT usr_users_email_key UNIQUE (email);
    
ALTER TABLE ONLY usr_users
    ADD CONSTRAINT usr_users_pkey PRIMARY KEY (id);
    
    
CREATE TABLE usr_password_requests (
    id bigserial NOT NULL,
    user_id bigint NOT NULL,
    hash character varying(40) NOT NULL,
    created timestamp without time zone DEFAULT now() NOT NULL,
    updated timestamp without time zone DEFAULT now() NOT NULL
);

ALTER TABLE ONLY usr_password_requests
    ADD CONSTRAINT usr_password_requests_pkey PRIMARY KEY (id);
    
ALTER TABLE ONLY usr_password_requests
    ADD CONSTRAINT usr_password_requests_user_id_key UNIQUE (user_id);
    
ALTER TABLE ONLY usr_password_requests
    ADD CONSTRAINT usr_password_requests_user_id_fkey FOREIGN KEY (user_id) REFERENCES usr_users(id) ON UPDATE CASCADE ON DELETE CASCADE;

CREATE TABLE usr_activations (
    id serial NOT NULL,
    user_id integer NOT NULL,
    hash character varying(40) NOT NULL,
    created timestamp without time zone DEFAULT now() NOT NULL
);

ALTER TABLE ONLY usr_activations
    ADD CONSTRAINT usr_activations_hash_key UNIQUE (hash);
    
ALTER TABLE ONLY usr_activations
    ADD CONSTRAINT usr_activations_pkey PRIMARY KEY (id);
    
ALTER TABLE ONLY usr_activations
    ADD CONSTRAINT usr_activations_user_id_fkey FOREIGN KEY (user_id) REFERENCES usr_users(id) ON UPDATE CASCADE ON DELETE CASCADE;

