CREATE TABLE sys_regions(
  id SERIAL,
  name CHARACTER VARYING(255) NOT NULL,
  level INTEGER NOT NULL,
  parent INTEGER,
  hidden SMALLINT NOT NULL
);

CREATE TABLE organizations(
  id SERIAL,
  name CHARACTER VARYING(50) NOT NULL,
  type INTEGER NOT NULL,
  area INTEGER,
  description CHARACTER VARYING(250),
  orientation INTEGER,
  child INTEGER,
  mother INTEGER,
  created TIMESTAMP NOT NULL,
  created_by INTEGER,
  updated TIMESTAMP,
  updated_by INTEGER,
  revision INTEGER
);

CREATE TABLE sys_countries(
  id SERIAL,
  name CHARACTER VARYING(75) NOT NULL,
  code CHARACTER(2) NOT NULL
);

CREATE TABLE sys_nationalities(
  id SERIAL,
  name CHARACTER VARYING(25) NOT NULL
);

CREATE TABLE persons(
  id SERIAL,
  initials CHARACTER VARYING(10),
  usualname CHARACTER VARYING(25) NOT NULL,
  lastname CHARACTER VARYING(25) NOT NULL,
  gender CHARACTER(1),
  date_birth DATE,
  nationality INTEGER,
  residency CHARACTER VARYING(50),
  picture CHARACTER VARYING(250),
  address CHARACTER VARYING(50),
  workphone CHARACTER VARYING(15),
  mobilephone CHARACTER VARYING(15),
  rights CHARACTER VARYING(50),
  website CHARACTER VARYING(50),
  blog CHARACTER VARYING(50),
  email CHARACTER VARYING(50),
  place_birth CHARACTER VARYING(50),
  origin_mom INTEGER,
  origin_dad INTEGER,
  marital_status INTEGER,
  created TIMESTAMP NOT NULL,
  created_by INTEGER,
  updated TIMESTAMP,
  updated_by INTEGER,
  revision INTEGER
);

CREATE TABLE doc_results(
  id SERIAL,
  name CHARACTER VARYING(25)
);

CREATE TABLE doc_categories(
  id SERIAL,
  name CHARACTER VARYING(25)
);

CREATE TABLE documents(
  id SERIAL,
  title CHARACTER VARYING(50) NOT NULL,
  source CHARACTER VARYING(250),
  content CHARACTER VARYING(40),
  timestamp TIMESTAMP NOT NULL,
  region INTEGER,
  vote_date DATE,
  summary CHARACTER VARYING(250),
  type INTEGER,
  result INTEGER,
  submitter_organization INTEGER,
  category INTEGER,
  submitter_person INTEGER,
  created TIMESTAMP NOT NULL,
  created_by INTEGER,
  updated TIMESTAMP,
  updated_by INTEGER,
  revision INTEGER
);

CREATE TABLE authors(
  id SERIAL,
  document INTEGER NOT NULL,
  person INTEGER NOT NULL,
  auth_order INTEGER NOT NULL,
  created TIMESTAMP DEFAULT now(),
  created_by INTEGER,
  updated TIMESTAMP,
  updated_by INTEGER,
  revision INTEGER
);

CREATE TABLE citations(
  id SERIAL,
  document INTEGER NOT NULL,
  person INTEGER,
  organization INTEGER,
  citation CHARACTER VARYING(250) NOT NULL,
  created TIMESTAMP NOT NULL,
  created_by INTEGER,
  updated TIMESTAMP,
  updated_by INTEGER,
  revision INTEGER
);

CREATE TABLE parties(
  id SERIAL,
  name CHARACTER VARYING(50) NOT NULL,
  organization INTEGER NOT NULL,
  orientation INTEGER,
  created TIMESTAMP NOT NULL,
  created_by INTEGER,
  updated TIMESTAMP,
  updated_by INTEGER,
  revision INTEGER
);

CREATE TABLE developers(
  id SERIAL,
  initials CHARACTER VARYING(10),
  usualname CHARACTER VARYING(25) NOT NULL,
  surname CHARACTER VARYING(50) NOT NULL,
  gender CHARACTER(1),
  date_birth DATE,
  nationality INTEGER,
  picture CHARACTER VARYING(250),
  party INTEGER,
  address CHARACTER VARYING(50),
  workphone CHARACTER VARYING(15),
  mobilephone CHARACTER VARYING(15),
  password CHARACTER VARYING(40) NOT NULL,
  email CHARACTER VARYING(50) NOT NULL,
  created TIMESTAMP NOT NULL,
  created_by INTEGER,
  updated TIMESTAMP,
  updated_by INTEGER,
  revision INTEGER
);

CREATE TABLE doc_types(
  id SERIAL,
  name CHARACTER VARYING(25) NOT NULL
);

CREATE TABLE function_types(
  id SERIAL,
  name CHARACTER VARYING(25) NOT NULL
);

CREATE TABLE functions(
  id SERIAL,
  name CHARACTER VARYING(25) NOT NULL,
  type INTEGER NOT NULL,
  created TIMESTAMP DEFAULT now(),
  created_by INTEGER,
  updated TIMESTAMP,
  updated_by INTEGER,
  revision INTEGER
);

CREATE TABLE marital_status(
  id SERIAL,
  name CHARACTER VARYING(25)
);

CREATE TABLE org_orientation(
  id SERIAL,
  name CHARACTER VARYING(25) NOT NULL
);

CREATE TABLE org_types(
  id SERIAL,
  name CHARACTER VARYING(25) NOT NULL
);

CREATE TABLE parties_functions(
  id SERIAL,
  party INTEGER NOT NULL,
  function INTEGER NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE,
  created TIMESTAMP DEFAULT now(),
  created_by INTEGER,
  updated TIMESTAMP,
  updated_by INTEGER,
  revision INTEGER
);

CREATE TABLE persons_functions(
  id SERIAL,
  function INTEGER NOT NULL,
  person INTEGER NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE,
  created TIMESTAMP NOT NULL,
  created_by INTEGER,
  updated TIMESTAMP,
  updated_by INTEGER,
  revision INTEGER
);

CREATE TABLE petitions_status(
  id SERIAL,
  name CHARACTER VARYING(25) NOT NULL
);

CREATE TABLE petitions(
  id SERIAL,
  document INTEGER,
  petitioner INTEGER NOT NULL,
  organization INTEGER NOT NULL,
  status INTEGER,
  created TIMESTAMP NOT NULL,
  created_by INTEGER,
  updated TIMESTAMP,
  updated_by INTEGER,
  revision INTEGER
);

CREATE TABLE resumes(
  id SERIAL,
  person INTEGER NOT NULL,
  ordinal_position INTEGER NOT NULL,
  year_from INTEGER,
  year_to INTEGER,
  header CHARACTER VARYING(255),
  content TEXT,
  category CHARACTER VARYING(25),
  location CHARACTER VARYING(255),
  created TIMESTAMP NOT NULL,
  created_by INTEGER,
  updated TIMESTAMP,
  updated_by INTEGER,
  revision INTEGER
);

CREATE TABLE sys_categories(
  id SERIAL,
  name CHARACTER VARYING(255) NOT NULL,
  description TEXT
);

CREATE TABLE sys_levels(
  id SERIAL,
  name CHARACTER VARYING(255) NOT NULL
);

CREATE TABLE sys_category_regions(
  category INTEGER NOT NULL,
  level INTEGER NOT NULL,
  description TEXT
);

CREATE TABLE sys_cities_postalcodes(
  id SERIAL,
  name CHARACTER VARYING(60),
  postalcode_min INTEGER NOT NULL,
  postalcode_max INTEGER NOT NULL
);

CREATE TABLE sys_postalcodes(
  id SERIAL,
  postalcode_min INTEGER NOT NULL,
  postalcode_max INTEGER,
  region INTEGER NOT NULL
);

CREATE TABLE tag_cascading_rules(
  id SERIAL,
  factor INTEGER NOT NULL,
  from_table CHARACTER VARYING NOT NULL,
  to_table CHARACTER VARYING NOT NULL,
  query CHARACTER VARYING NOT NULL
);

CREATE TABLE tags(
  id SERIAL,
  name CHARACTER VARYING NOT NULL,
  weight INTEGER NOT NULL,
  object_id BIGINT NOT NULL,
  object_table CHARACTER VARYING NOT NULL,
  cascaded_from BIGINT,
  originates_from BIGINT,
  created TIMESTAMP NOT NULL,
  created_by INTEGER NOT NULL
);


ALTER TABLE sys_regions ADD PRIMARY KEY (id);
ALTER TABLE sys_regions ADD CONSTRAINT FK_sys_regions_0 FOREIGN KEY (parent) REFERENCES sys_regions (id);
ALTER TABLE sys_regions ADD CONSTRAINT FK_sys_regions_1 FOREIGN KEY (level) REFERENCES sys_levels (id);

ALTER TABLE organizations ADD PRIMARY KEY (id);
ALTER TABLE organizations ADD CONSTRAINT FK_organizations_0 FOREIGN KEY (type) REFERENCES org_types (id);
ALTER TABLE organizations ADD CONSTRAINT FK_organizations_1 FOREIGN KEY (child) REFERENCES organizations (id);
ALTER TABLE organizations ADD CONSTRAINT FK_organizations_2 FOREIGN KEY (mother) REFERENCES organizations (id);
ALTER TABLE organizations ADD CONSTRAINT FK_organizations_3 FOREIGN KEY (orientation) REFERENCES org_orientation (id);
ALTER TABLE organizations ADD CONSTRAINT FK_organizations_4 FOREIGN KEY (area) REFERENCES sys_regions (id);

ALTER TABLE sys_countries ADD PRIMARY KEY (id);

ALTER TABLE sys_nationalities ADD PRIMARY KEY (id);

ALTER TABLE persons ADD PRIMARY KEY (id);
ALTER TABLE persons ADD CONSTRAINT FK_persons_0 FOREIGN KEY (marital_status) REFERENCES marital_status (id);
ALTER TABLE persons ADD CONSTRAINT FK_persons_1 FOREIGN KEY (origin_mom) REFERENCES sys_countries (id);
ALTER TABLE persons ADD CONSTRAINT FK_persons_2 FOREIGN KEY (nationality) REFERENCES sys_nationalities (id);
ALTER TABLE persons ADD CONSTRAINT FK_persons_3 FOREIGN KEY (origin_dad) REFERENCES sys_countries (id);

ALTER TABLE doc_results ADD PRIMARY KEY (id);
ALTER TABLE doc_results ADD CONSTRAINT IDX_doc_results_1 UNIQUE (name);

ALTER TABLE doc_categories ADD PRIMARY KEY (id);
ALTER TABLE doc_categories ADD CONSTRAINT IDX_doc_categories_1 UNIQUE (name);

ALTER TABLE documents ADD PRIMARY KEY (id);
ALTER TABLE documents ADD CONSTRAINT FK_documents_0 FOREIGN KEY (submitter_organization) REFERENCES organizations (id);
ALTER TABLE documents ADD CONSTRAINT FK_documents_1 FOREIGN KEY (region) REFERENCES sys_regions (id);
ALTER TABLE documents ADD CONSTRAINT FK_documents_2 FOREIGN KEY (submitter_person) REFERENCES persons (id);
ALTER TABLE documents ADD CONSTRAINT FK_documents_3 FOREIGN KEY (type) REFERENCES doc_types (id);
ALTER TABLE documents ADD CONSTRAINT FK_documents_4 FOREIGN KEY (result) REFERENCES doc_results (id);
ALTER TABLE documents ADD CONSTRAINT FK_documents_5 FOREIGN KEY (category) REFERENCES doc_categories (id);

ALTER TABLE authors ADD PRIMARY KEY (id);
ALTER TABLE authors ADD CONSTRAINT FK_authors_0 FOREIGN KEY (person) REFERENCES persons (id);
ALTER TABLE authors ADD CONSTRAINT FK_authors_1 FOREIGN KEY (document) REFERENCES documents (id);

ALTER TABLE citations ADD PRIMARY KEY (id);
ALTER TABLE citations ADD CONSTRAINT FK_citations_0 FOREIGN KEY (person) REFERENCES persons (id);
ALTER TABLE citations ADD CONSTRAINT FK_citations_1 FOREIGN KEY (document) REFERENCES documents (id);
ALTER TABLE citations ADD CONSTRAINT FK_citations_2 FOREIGN KEY (organization) REFERENCES organizations (id);

ALTER TABLE parties ADD PRIMARY KEY (id);
ALTER TABLE parties ADD CONSTRAINT FK_parties_0 FOREIGN KEY (organization) REFERENCES organizations (id);
ALTER TABLE parties ADD CONSTRAINT FK_parties_1 FOREIGN KEY (orientation) REFERENCES org_orientation (id);

ALTER TABLE developers ADD PRIMARY KEY (id);
ALTER TABLE developers ADD CONSTRAINT FK_developers_0 FOREIGN KEY (nationality) REFERENCES sys_nationalities (id);
ALTER TABLE developers ADD CONSTRAINT FK_developers_1 FOREIGN KEY (party) REFERENCES parties (id);

ALTER TABLE doc_types ADD PRIMARY KEY (id);

ALTER TABLE function_types ADD PRIMARY KEY (id);

ALTER TABLE functions ADD PRIMARY KEY (id);
ALTER TABLE functions ADD CONSTRAINT FK_functions_0 FOREIGN KEY (type) REFERENCES function_types (id);

ALTER TABLE marital_status ADD PRIMARY KEY (id);

ALTER TABLE org_orientation ADD PRIMARY KEY (id);

ALTER TABLE org_types ADD PRIMARY KEY (id);

ALTER TABLE parties_functions ADD PRIMARY KEY (id);
ALTER TABLE parties_functions ADD CONSTRAINT FK_parties_functions_0 FOREIGN KEY (function) REFERENCES functions (id);
ALTER TABLE parties_functions ADD CONSTRAINT FK_parties_functions_1 FOREIGN KEY (party) REFERENCES parties (id);

ALTER TABLE persons_functions ADD PRIMARY KEY (id);
ALTER TABLE persons_functions ADD CONSTRAINT FK_persons_functions_0 FOREIGN KEY (person) REFERENCES persons (id);
ALTER TABLE persons_functions ADD CONSTRAINT FK_persons_functions_1 FOREIGN KEY (function) REFERENCES functions (id);

ALTER TABLE petitions_status ADD PRIMARY KEY (id);
ALTER TABLE petitions_status ADD CONSTRAINT IDX_petitions_status_1 UNIQUE (name);

ALTER TABLE petitions ADD PRIMARY KEY (id);
ALTER TABLE petitions ADD CONSTRAINT FK_petitions_0 FOREIGN KEY (document) REFERENCES documents (id);
ALTER TABLE petitions ADD CONSTRAINT FK_petitions_1 FOREIGN KEY (organization) REFERENCES organizations (id);
ALTER TABLE petitions ADD CONSTRAINT FK_petitions_2 FOREIGN KEY (petitioner) REFERENCES persons (id);
ALTER TABLE petitions ADD CONSTRAINT FK_petitions_3 FOREIGN KEY (status) REFERENCES petitions_status (id);

ALTER TABLE resumes ADD PRIMARY KEY (id);
ALTER TABLE resumes ADD CONSTRAINT FK_resumes_0 FOREIGN KEY (person) REFERENCES persons (id);

ALTER TABLE sys_categories ADD PRIMARY KEY (id);

ALTER TABLE sys_levels ADD PRIMARY KEY (id);

ALTER TABLE sys_category_regions ADD PRIMARY KEY (category, level);
ALTER TABLE sys_category_regions ADD CONSTRAINT FK_sys_category_regions_0 FOREIGN KEY (level) REFERENCES sys_levels (id);
ALTER TABLE sys_category_regions ADD CONSTRAINT FK_sys_category_regions_1 FOREIGN KEY (category) REFERENCES sys_categories (id);

ALTER TABLE sys_cities_postalcodes ADD PRIMARY KEY (id);

ALTER TABLE sys_postalcodes ADD PRIMARY KEY (id);
ALTER TABLE sys_postalcodes ADD CONSTRAINT FK_sys_postalcodes_0 FOREIGN KEY (region) REFERENCES sys_regions (id);

ALTER TABLE tag_cascading_rules ADD PRIMARY KEY (id);

ALTER TABLE tags ADD PRIMARY KEY (id);
ALTER TABLE tags ADD CONSTRAINT FK_tags_0 FOREIGN KEY (originates_from) REFERENCES tags (id);
ALTER TABLE tags ADD CONSTRAINT FK_tags_1 FOREIGN KEY (created_by) REFERENCES developers (id);
ALTER TABLE tags ADD CONSTRAINT FK_tags_2 FOREIGN KEY (cascaded_from) REFERENCES tags (id);
CREATE INDEX tags_name ON tags (name);

