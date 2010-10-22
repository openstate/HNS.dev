DROP TABLE parties_functions;
DROP TABLE organizations_tags;
DROP TABLE documents_tags;
DROP TABLE persons_tags;
DROP TABLE authors;
DROP TABLE wiz_resume;
DROP TABLE tags;
DROP TABLE petitions;
DROP TABLE org_orientation;
DROP TABLE persons_functions;
DROP TABLE functions;
DROP TABLE developers;
DROP TABLE parties;
DROP TABLE citations;
DROP TABLE documents;
DROP TABLE persons;
DROP TABLE organizations;

CREATE TABLE organizations(
  id SERIAL,
  name CHARACTER VARYING(50) NOT NULL,
  type INTEGER NOT NULL,
  area INTEGER,
  description CHARACTER VARYING(250),
  orientation INTEGER,
  child INTEGER,
  mother INTEGER
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
  bio CHARACTER VARYING(50),
  place_birth CHARACTER VARYING(50),
  origin_mom INTEGER,
  origin_dad INTEGER,
  marital_status INTEGER
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
  submitter_person INTEGER
);

CREATE TABLE citations(
  id SERIAL,
  document INTEGER NOT NULL,
  person INTEGER,
  organization INTEGER,
  citation CHARACTER VARYING(250) NOT NULL
);

CREATE TABLE parties(
  id SERIAL,
  organization INTEGER NOT NULL,
  orientation INTEGER,
  name CHARACTER VARYING(50) NOT NULL
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
  created TIMESTAMP,
  email CHARACTER VARYING(50) NOT NULL
);

CREATE TABLE function_types(
  id SERIAL,
  name CHARACTER VARYING(25) NOT NULL
);

CREATE TABLE functions(
  id SERIAL NOT NULL,
  name CHARACTER VARYING(25) NOT NULL,
  created TIMESTAMP DEFAULT now(),
  type INTEGER NOT NULL DEFAULT 1
);

CREATE TABLE persons_functions(
  id SERIAL,
  function INTEGER NOT NULL,
  person INTEGER NOT NULL,
  start DATE NOT NULL,
  end DATE
);

CREATE TABLE petitions(
  id SERIAL,
  document INTEGER,
  petitioner INTEGER NOT NULL,
  organization INTEGER NOT NULL,
  status INTEGER
);

CREATE TABLE tags(
  id SERIAL,
  name CHARACTER VARYING(50) NOT NULL,
  created TIMESTAMP NOT NULL DEFAULT now(),
  updated TIMESTAMP
);

CREATE TABLE wiz_resume(
  person INTEGER NOT NULL,
  ordinal_position INTEGER NOT NULL,
  year_from INTEGER,
  year_to INTEGER,
  header CHARACTER VARYING(255),
  content TEXT,
  category CHARACTER VARYING(25),
  location CHARACTER VARYING(255)
);

CREATE TABLE authors(
  id SERIAL,
  document INTEGER NOT NULL,
  person INTEGER NOT NULL,
  order INTEGER NOT NULL DEFAULT 1
);

CREATE TABLE persons_tags(
  id SERIAL,
  person INTEGER NOT NULL,
  tag INTEGER NOT NULL,
  created TIMESTAMP NOT NULL DEFAULT now()
);

CREATE TABLE documents_tags(
  id SERIAL,
  document INTEGER NOT NULL,
  tag INTEGER NOT NULL,
  created TIMESTAMP NOT NULL DEFAULT now()
);

CREATE TABLE organizations_tags(
  id SERIAL,
  organization INTEGER NOT NULL,
  tag INTEGER NOT NULL,
  created TIMESTAMP NOT NULL DEFAULT now()
);

CREATE TABLE parties_functions(
  id SERIAL,
  party INTEGER NOT NULL,
  function INTEGER NOT NULL,
  start DATE NOT NULL,
  end DATE
);


ALTER TABLE organizations ADD PRIMARY KEY (id);
ALTER TABLE organizations ADD CONSTRAINT FK_organizations_0 FOREIGN KEY (type) REFERENCES org_types (id);
ALTER TABLE organizations ADD CONSTRAINT FK_organizations_1 FOREIGN KEY (orientation) REFERENCES org_orientation (id);
ALTER TABLE organizations ADD CONSTRAINT FK_organizations_2 FOREIGN KEY (area) REFERENCES sys_regions (id);

ALTER TABLE persons ADD PRIMARY KEY (id);
ALTER TABLE persons ADD CONSTRAINT FK_persons_0 FOREIGN KEY (origin_mom) REFERENCES sys_countries (id);
ALTER TABLE persons ADD CONSTRAINT FK_persons_1 FOREIGN KEY (origin_dad) REFERENCES sys_countries (id);
ALTER TABLE persons ADD CONSTRAINT FK_persons_2 FOREIGN KEY (nationality) REFERENCES sys_nationalities (id);

ALTER TABLE documents ADD PRIMARY KEY (id);
ALTER TABLE documents ADD CONSTRAINT FK_documents_0 FOREIGN KEY (type) REFERENCES doc_types (id);
ALTER TABLE documents ADD CONSTRAINT FK_documents_1 FOREIGN KEY (submitter_organization) REFERENCES organizations (id);
ALTER TABLE documents ADD CONSTRAINT FK_documents_2 FOREIGN KEY (region) REFERENCES sys_regions (id);
ALTER TABLE documents ADD CONSTRAINT FK_documents_3 FOREIGN KEY (submitter_person) REFERENCES persons (id);

ALTER TABLE citations ADD PRIMARY KEY (id);
ALTER TABLE citations ADD CONSTRAINT FK_citations_0 FOREIGN KEY (person) REFERENCES persons (id);
ALTER TABLE citations ADD CONSTRAINT FK_citations_1 FOREIGN KEY (document) REFERENCES documents (id);

ALTER TABLE parties ADD PRIMARY KEY (id);
ALTER TABLE parties ADD CONSTRAINT FK_parties_0 FOREIGN KEY (organization) REFERENCES organizations (id);
ALTER TABLE parties ADD CONSTRAINT FK_parties_1 FOREIGN KEY (orientation) REFERENCES org_orientation (id);

ALTER TABLE developers ADD PRIMARY KEY (id);
ALTER TABLE developers ADD CONSTRAINT FK_developers_0 FOREIGN KEY (nationality) REFERENCES sys_nationalities (id);
ALTER TABLE developers ADD CONSTRAINT FK_developers_1 FOREIGN KEY (party) REFERENCES parties (id);

ALTER TABLE function_types ADD PRIMARY KEY (id);

ALTER TABLE functions ADD PRIMARY KEY (id);
ALTER TABLE functions ADD CONSTRAINT FK_functions_0 FOREIGN KEY (type) REFERENCES function_types (id);

ALTER TABLE persons_functions ADD PRIMARY KEY (function, person, start);
ALTER TABLE persons_functions ADD CONSTRAINT FK_persons_functions_0 FOREIGN KEY (person) REFERENCES persons (id);
ALTER TABLE persons_functions ADD CONSTRAINT FK_persons_functions_1 FOREIGN KEY (function) REFERENCES functions (id);

ALTER TABLE petitions ADD PRIMARY KEY (id);
ALTER TABLE petitions ADD CONSTRAINT FK_petitions_0 FOREIGN KEY (document) REFERENCES documents (id);
ALTER TABLE petitions ADD CONSTRAINT FK_petitions_1 FOREIGN KEY (organization) REFERENCES organizations (id);
ALTER TABLE petitions ADD CONSTRAINT FK_petitions_2 FOREIGN KEY (petitioner) REFERENCES persons (id);

ALTER TABLE tags ADD PRIMARY KEY (id);

ALTER TABLE wiz_resume ADD PRIMARY KEY (person, ordinal_position);
ALTER TABLE wiz_resume ADD CONSTRAINT FK_wiz_resume_0 FOREIGN KEY (person) REFERENCES persons (id);

ALTER TABLE authors ADD PRIMARY KEY (document, person);
ALTER TABLE authors ADD CONSTRAINT FK_authors_0 FOREIGN KEY (person) REFERENCES persons (id);
ALTER TABLE authors ADD CONSTRAINT FK_authors_1 FOREIGN KEY (document) REFERENCES documents (id);
ALTER TABLE authors ADD CONSTRAINT unique_authors_order UNIQUE (order, document_id);

ALTER TABLE persons_tags ADD PRIMARY KEY (person, tag, created);
ALTER TABLE persons_tags ADD CONSTRAINT FK_persons_tags_0 FOREIGN KEY (tag) REFERENCES tags (id);
ALTER TABLE persons_tags ADD CONSTRAINT FK_persons_tags_1 FOREIGN KEY (person) REFERENCES persons (id);

ALTER TABLE documents_tags ADD PRIMARY KEY (document, tag, created);
ALTER TABLE documents_tags ADD CONSTRAINT FK_documents_tags_0 FOREIGN KEY (tag) REFERENCES tags (id);
ALTER TABLE documents_tags ADD CONSTRAINT FK_documents_tags_1 FOREIGN KEY (document) REFERENCES documents (id);

ALTER TABLE organizations_tags ADD PRIMARY KEY (organization, tag, created);
ALTER TABLE organizations_tags ADD CONSTRAINT FK_organizations_tags_0 FOREIGN KEY (tag) REFERENCES tags (id);
ALTER TABLE organizations_tags ADD CONSTRAINT FK_organizations_tags_1 FOREIGN KEY (organization) REFERENCES organizations (id);

ALTER TABLE parties_functions ADD PRIMARY KEY (party, function, start);
ALTER TABLE parties_functions ADD CONSTRAINT FK_parties_functions_0 FOREIGN KEY (function) REFERENCES functions (id);
ALTER TABLE parties_functions ADD CONSTRAINT FK_parties_functions_1 FOREIGN KEY (party) REFERENCES parties (id);

