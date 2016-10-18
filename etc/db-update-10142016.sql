ALTER TABLE app.subjects ADD COLUMN use_for_grading boolean NOT NULL DEFAULT true;
ALTER TABLE app.invoices ADD COLUMN term_id integer;