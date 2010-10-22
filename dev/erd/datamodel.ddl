DROP TABLE parties_functions;
DROP TABLE organizations_tags;
DROP TABLE documents_tags;
DROP TABLE persons_tags;
DROP TABLE authors;
DROP TABLE wiz_resume;
DROP TABLE tags;
DROP TABLE sys_postalcodes;
DROP TABLE sys_regions;
DROP TABLE sys_cities_postalcodes;
DROP TABLE sys_category_regions;
DROP TABLE sys_levels;
DROP TABLE sys_categories;
DROP TABLE petitions;
DROP TABLE parties;
DROP TABLE org_types;
DROP TABLE org_orientation;
DROP TABLE persons_functions;
DROP TABLE functions;
DROP TABLE doc_types;
DROP TABLE developers;
DROP TABLE citations;
DROP TABLE persons;
DROP TABLE sys_nationalities;
DROP TABLE sys_countries;
DROP TABLE documents;
DROP TABLE organizations;

/**********************************/
/* Table Name: organizations */
/**********************************/
CREATE TABLE organizations(
  id SERIAL,
  name VARCHAR(50) NOT NULL,
  type INTEGER NOT NULL,
  area INTEGER,
  description VARCHAR(250),
  orientation INTEGER,
  child INTEGER,
  mother INTEGER
);

/**********************************/
/* Table Name: documents */
/**********************************/
CREATE TABLE documents(
  id SERIAL,
  title VARCHAR(50) NOT NULL,
  source VARCHAR(250),
  content VARCHAR(40),
  timestamp TIMESTAMP NOT NULL,
  region INTEGER,
  vote_date DATE,
  summary VARCHAR(250),
  type INTEGER,
  result INTEGER,
  submitter INTEGER,
  category INTEGER
);

/**********************************/
/* Table Name: sys_countries */
/**********************************/
CREATE TABLE sys_countries(
  id SERIAL,
  name VARCHAR(75) NOT NULL,
  code CHARACTER(2) NOT NULL
);

/**********************************/
/* Table Name: sys_nationalities */
/**********************************/
CREATE TABLE sys_nationalities(
  id SERIAL,
  name VARCHAR(25) NOT NULL
);

/**********************************/
/* Table Name: persons */
/**********************************/
CREATE TABLE persons(
  id SERIAL,
  initials VARCHAR(10) NOT NULL,
  usualname VARCHAR(25) NOT NULL,
  lastname VARCHAR(25) NOT NULL,
  gender SMALLINT,
  date_birth DATE,
  nationality INTEGER,
  residency VARCHAR(50),
  picture VARCHAR(40),
  address VARCHAR(50),
  workphone VARCHAR(15),
  mobilephone VARCHAR(15),
  rights VARCHAR(50),
  website VARCHAR(50),
  blog VARCHAR(50),
  email VARCHAR(50),
  bio VARCHAR(50),
  place_birth VARCHAR(50),
  origin_mom INTEGER,
  origin_dad INTEGER,
  marital_status INTEGER
);

/**********************************/
/* Table Name: citations */
/**********************************/
CREATE TABLE citations(
  id SERIAL,
  document INTEGER NOT NULL,
  person INTEGER,
  organization INTEGER,
  citation VARCHAR(250) NOT NULL
);

/**********************************/
/* Table Name: developers */
/**********************************/
CREATE TABLE developers(
  id SERIAL,
  initials VARCHAR(10) NOT NULL,
  usualname VARCHAR(25) NOT NULL,
  surname VARCHAR(50) NOT NULL,
  gender SMALLINT,
  date_birth DATE,
  nationality INTEGER,
  picture VARCHAR(40),
  party INTEGER,
  address VARCHAR(50),
  workphone VARCHAR(15),
  mobilephone VARCHAR(15),
  password VARCHAR(40) NOT NULL,
  created TIMESTAMP,
  email VARCHAR(50) NOT NULL
);

/**********************************/
/* Table Name: doc_types */
/**********************************/
CREATE TABLE doc_types(
  id SERIAL,
  name VARCHAR(25) NOT NULL
);

/**********************************/
/* Table Name: functions */
/**********************************/
CREATE TABLE functions(
  id SERIAL NOT NULL,
  name VARCHAR(25) NOT NULL,
  created TIMESTAMP DEFAULT now()
);

/**********************************/
/* Table Name: persons_functions */
/**********************************/
CREATE TABLE persons_functions(
  function INTEGER NOT NULL,
  person INTEGER NOT NULL,
  start DATE NOT NULL,
  end DATE
);

/**********************************/
/* Table Name: org_orientation */
/**********************************/
CREATE TABLE org_orientation(
  id SERIAL,
  name VARCHAR(25) NOT NULL
);

/**********************************/
/* Table Name: org_types */
/**********************************/
CREATE TABLE org_types(
  id SERIAL,
  name VARCHAR(25) NOT NULL
);

/**********************************/
/* Table Name: parties */
/**********************************/
CREATE TABLE parties(
  id SERIAL,
  organization INTEGER NOT NULL,
  orientation INTEGER
);

/**********************************/
/* Table Name: petitions */
/**********************************/
CREATE TABLE petitions(
  id SERIAL,
  document INTEGER,
  petitioner INTEGER NOT NULL,
  organization INTEGER NOT NULL,
  status INTEGER
);

/**********************************/
/* Table Name: sys_categories */
/**********************************/
CREATE TABLE sys_categories(
  id SERIAL,
  name VARCHAR(255) NOT NULL,
  description TEXT
);

/**********************************/
/* Table Name: sys_levels */
/**********************************/
CREATE TABLE sys_levels(
  id SERIAL,
  name VARCHAR(255) NOT NULL
);

/**********************************/
/* Table Name: sys_category_regions */
/**********************************/
CREATE TABLE sys_category_regions(
  category INTEGER NOT NULL,
  level INTEGER NOT NULL,
  description TEXT
);

/**********************************/
/* Table Name: sys_cities_postalcodes */
/**********************************/
CREATE TABLE sys_cities_postalcodes(
  id SERIAL,
  name VARCHAR(60),
  postalcode_min INTEGER NOT NULL,
  postalcode_max INTEGER NOT NULL
);

/**********************************/
/* Table Name: sys_regions */
/**********************************/
CREATE TABLE sys_regions(
  id SERIAL,
  name VARCHAR(255) NOT NULL,
  level INTEGER NOT NULL,
  parent INTEGER,
  hidden SMALLINT NOT NULL
);

/**********************************/
/* Table Name: sys_postalcodes */
/**********************************/
CREATE TABLE sys_postalcodes(
  id SERIAL,
  postalcode_min INTEGER NOT NULL,
  postalcode_max INTEGER,
  region INTEGER NOT NULL
);

/**********************************/
/* Table Name: tags */
/**********************************/
CREATE TABLE tags(
  id SERIAL,
  name VARCHAR(50) NOT NULL,
  created TIMESTAMP NOT NULL DEFAULT now(),
  updated TIMESTAMP
);

/**********************************/
/* Table Name: wiz_resume */
/**********************************/
CREATE TABLE wiz_resume(
  person INTEGER NOT NULL,
  ordinal_position INTEGER NOT NULL,
  year_from INTEGER,
  year_to INTEGER,
  header VARCHAR(255),
  content TEXT,
  category VARCHAR(25),
  location VARCHAR(255)
);

/**********************************/
/* Table Name: authors */
/**********************************/
CREATE TABLE authors(
  document INTEGER NOT NULL,
  person INTEGER NOT NULL,
  order INTEGER NOT NULL DEFAULT 1
);

/**********************************/
/* Table Name: persons_tags */
/**********************************/
CREATE TABLE persons_tags(
  person INTEGER NOT NULL,
  tag INTEGER NOT NULL,
  created TIMESTAMP NOT NULL DEFAULT now()
);

/**********************************/
/* Table Name: documents_tags */
/**********************************/
CREATE TABLE documents_tags(
  document INTEGER NOT NULL,
  tag INTEGER NOT NULL,
  created TIMESTAMP NOT NULL DEFAULT now()
);

/**********************************/
/* Table Name: organizations_tags */
/**********************************/
CREATE TABLE organizations_tags(
  organization INTEGER NOT NULL,
  tag INTEGER NOT NULL,
  created TIMESTAMP NOT NULL DEFAULT now()
);

/**********************************/
/* Table Name: parties_functions */
/**********************************/
CREATE TABLE parties_functions(
  party INTEGER NOT NULL,
  function INTEGER NOT NULL,
  start DATE NOT NULL,
  end DATE
);


ALTER TABLE organizations ADD PRIMARY KEY (id);
ALTER TABLE organizations ADD CONSTRAINT FK_organizations_0 FOREIGN KEY (type) REFERENCES org_types (id);
ALTER TABLE organizations ADD CONSTRAINT FK_organizations_1 FOREIGN KEY (orientation) REFERENCES org_orientation (id);

ALTER TABLE documents ADD PRIMARY KEY (id);
ALTER TABLE documents ADD CONSTRAINT FK_documents_0 FOREIGN KEY (type) REFERENCES doc_types (id);
ALTER TABLE documents ADD CONSTRAINT FK_documents_1 FOREIGN KEY (submitter) REFERENCES organizations (id);

ALTER TABLE sys_countries ADD PRIMARY KEY (id);

ALTER TABLE sys_nationalities ADD PRIMARY KEY (id);

ALTER TABLE persons ADD PRIMARY KEY (id);
ALTER TABLE persons ADD CONSTRAINT FK_persons_0 FOREIGN KEY (origin_mom) REFERENCES sys_countries (id);
ALTER TABLE persons ADD CONSTRAINT FK_persons_1 FOREIGN KEY (origin_dad) REFERENCES sys_countries (id);
ALTER TABLE persons ADD CONSTRAINT FK_persons_2 FOREIGN KEY (nationality) REFERENCES sys_nationalities (id);

ALTER TABLE citations ADD PRIMARY KEY (id);
ALTER TABLE citations ADD CONSTRAINT FK_citations_0 FOREIGN KEY (person) REFERENCES persons (id);
ALTER TABLE citations ADD CONSTRAINT FK_citations_1 FOREIGN KEY (document) REFERENCES documents (id);

ALTER TABLE developers ADD PRIMARY KEY (id);

ALTER TABLE doc_types ADD PRIMARY KEY (id);

ALTER TABLE functions ADD PRIMARY KEY (id);

ALTER TABLE persons_functions ADD PRIMARY KEY (function, person, start);
ALTER TABLE persons_functions ADD CONSTRAINT FK_persons_functions_0 FOREIGN KEY (person) REFERENCES persons (id);
ALTER TABLE persons_functions ADD CONSTRAINT FK_persons_functions_1 FOREIGN KEY (function) REFERENCES functions (id);

ALTER TABLE org_orientation ADD PRIMARY KEY (id);

ALTER TABLE org_types ADD PRIMARY KEY (id);

ALTER TABLE parties ADD PRIMARY KEY (id);
ALTER TABLE parties ADD CONSTRAINT FK_parties_0 FOREIGN KEY (organization) REFERENCES organizations (id);
ALTER TABLE parties ADD CONSTRAINT FK_parties_1 FOREIGN KEY (orientation) REFERENCES org_orientation (id);

ALTER TABLE petitions ADD PRIMARY KEY (id);
ALTER TABLE petitions ADD CONSTRAINT FK_petitions_0 FOREIGN KEY (document) REFERENCES documents (id);
ALTER TABLE petitions ADD CONSTRAINT FK_petitions_1 FOREIGN KEY (organization) REFERENCES organizations (id);
ALTER TABLE petitions ADD CONSTRAINT FK_petitions_2 FOREIGN KEY (petitioner) REFERENCES persons (id);

ALTER TABLE sys_categories ADD PRIMARY KEY (id);

ALTER TABLE sys_levels ADD PRIMARY KEY (id);

ALTER TABLE sys_category_regions ADD PRIMARY KEY (category, level);
ALTER TABLE sys_category_regions ADD CONSTRAINT FK_sys_category_regions_0 FOREIGN KEY (level) REFERENCES sys_levels (id);
ALTER TABLE sys_category_regions ADD CONSTRAINT FK_sys_category_regions_1 FOREIGN KEY (category) REFERENCES sys_categories (id);

ALTER TABLE sys_cities_postalcodes ADD PRIMARY KEY (id);

ALTER TABLE sys_regions ADD PRIMARY KEY (id);
ALTER TABLE sys_regions ADD CONSTRAINT FK_sys_regions_0 FOREIGN KEY (parent) REFERENCES sys_regions (id);
ALTER TABLE sys_regions ADD CONSTRAINT FK_sys_regions_1 FOREIGN KEY (level) REFERENCES sys_levels (id);

ALTER TABLE sys_postalcodes ADD PRIMARY KEY (id);
ALTER TABLE sys_postalcodes ADD CONSTRAINT FK_sys_postalcodes_0 FOREIGN KEY (region) REFERENCES sys_regions (id);

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

