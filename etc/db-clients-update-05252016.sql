BEGIN;
ALTER TABLE app.student_guardians DROP COLUMN student_id;
ALTER TABLE app.student_guardians DROP COLUMN relationship;
ALTER TABLE app.student_guardians RENAME TO guardians;
ALTER TABLE app.student_guardians_guardian_id_seq RENAME TO guardians_guardian_id_seq;
COMMIT;

BEGIN;
ALTER TABLE app.employees ADD COLUMN login_id integer;
ALTER TABLE app.guardians ADD CONSTRAINT "U_id_number" UNIQUE(id_number );
ALTER TABLE app.guardians ALTER COLUMN id_number SET NOT NULL;
  

/*
ALTER TABLE app.guardians DROP CONSTRAINT "FK_student_guardian_student";
ALTER TABLE app.guardians ADD CONSTRAINT "FK_student_guardian_student" FOREIGN KEY (student_id)
      REFERENCES app.students (student_id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;
*/

CREATE TABLE app.student_guardians
(
  student_guardian_id serial NOT NULL,
  student_id integer NOT NULL,
  guardian_id integer NOT NULL,
  creation_date timestamp without time zone NOT NULL DEFAULT now(),
  created_by integer,
  relationship character varying,
  active boolean NOT NULL DEFAULT true,
  modified_date timestamp without time zone,
  modified_by integer,
  CONSTRAINT "PK_student_guardian_id" PRIMARY KEY (student_guardian_id ),
  CONSTRAINT "FK_student_guardian_student" FOREIGN KEY (student_id)
      REFERENCES app.students (student_id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT "FK_student_guardian_guardian" FOREIGN KEY (guardian_id)
      REFERENCES app.guardians (guardian_id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);

COMMIT;
