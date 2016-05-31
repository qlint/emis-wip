﻿CREATE TABLE app.blogs
(
   blog_id serial, 
   teacher_id integer NOT NULL, 
   class_id integer NOT NULL, 
   blog_name character varying NOT NULL,   
   CONSTRAINT "FK_blog_id" PRIMARY KEY (blog_id), 
   CONSTRAINT "FK_blog_teacher" FOREIGN KEY (teacher_id) REFERENCES app.employees (emp_id) ON UPDATE NO ACTION ON DELETE NO ACTION, 
   CONSTRAINT "FK_blog_class" FOREIGN KEY (class_id) REFERENCES app.classes (class_id) ON UPDATE NO ACTION ON DELETE NO ACTION
) 
WITH (
  OIDS = FALSE
)
;


CREATE TABLE app.blog_post_types
(
   post_type_id serial, 
   post_type character varying NOT NULL, 
   CONSTRAINT "PK_post_type_id" PRIMARY KEY (post_type_id)
) 
WITH (
  OIDS = FALSE
)
;

CREATE TABLE app.blog_posts
(
  post_id serial NOT NULL,
  blog_id integer NOT NULL,
  creation_date time without time zone NOT NULL DEFAULT now(),
  created_by integer,
  post_type_id integer,
  body text NOT NULL,
  title character varying NOT NULL,
  CONSTRAINT "PK_post_id" PRIMARY KEY (post_id ),
  CONSTRAINT "FK_post_blog" FOREIGN KEY (blog_id)
      REFERENCES app.blogs (blog_id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT "FK_post_type" FOREIGN KEY (post_type_id)
      REFERENCES app.blog_post_types (post_type_id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);


CREATE TABLE app.homework
(
  homework_id serial NOT NULL,
  teacher_id integer NOT NULL,
  class_id integer NOT NULL,
  homework_date date NOT NULL,
  description character varying NOT NULL,
  CONSTRAINT "FK_homework_id" PRIMARY KEY (homework_id ),
  CONSTRAINT "FK_blog_class" FOREIGN KEY (class_id)
      REFERENCES app.classes (class_id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT "FK_blog_teacher" FOREIGN KEY (teacher_id)
      REFERENCES app.employees (emp_id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);


CREATE TABLE app.communication_types
(
  com_type_id serial NOT NULL,
  com_type character varying NOT NULL,
  CONSTRAINT "PK_com_type_id" PRIMARY KEY (com_type_id )
)
WITH (
  OIDS=FALSE
);


DROP TABLE app.news;
CREATE TABLE app.communications
(
  com_id serial NOT NULL,
  com_date date,
  audience character varying NOT NULL,
  com_type_id integer NOT NULL,
  subject character varying NOT NULL,
  message character varying NOT NULL,
  attachment character varying NOT NULL,
  message_from integer NOT NULL,
  student_id integer,
  send_as_email boolean,
  post_to_parent_portal boolean,
  allow_reply boolean NOT NULL DEFAULT false,
  creation_date timestamp without time zone NOT NULL DEFAULT now(),
  created_by integer,
  active boolean NOT NULL DEFAULT true,
  CONSTRAINT "PK_com_id" PRIMARY KEY (com_id ),
  CONSTRAINT "FK_com_message_from" FOREIGN KEY (message_from)
      REFERENCES app.employees (emp_id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT "FK_com_student_id" FOREIGN KEY (student_id)
      REFERENCES app.students (student_id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT "FK_com_type_id" FOREIGN KEY (com_type_id)
      REFERENCES app.communication_types (com_type_id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);

CREATE TABLE app.communication_responses
(
  com_reply_id serial NOT NULL,
  com_id integer NOT NULL,
  com_from character varying NOT NULL,
  creation_date timestamp without time zone NOT NULL DEFAULT now(),
  message text NOT NULL,
  CONSTRAINT "PK_com_reply_id" PRIMARY KEY (com_reply_id ),
  CONSTRAINT "FK_com_reply_comm" FOREIGN KEY (com_id)
      REFERENCES app.communications (com_id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);


INSERT INTO blog_post_types VALUES (2, 'Event');
INSERT INTO blog_post_types VALUES (3, 'Reminder');
INSERT INTO blog_post_types VALUES (4, 'Important');
INSERT INTO blog_post_types VALUES (1, 'General');

SELECT pg_catalog.setval('blog_post_types_post_type_id_seq', 4, true);