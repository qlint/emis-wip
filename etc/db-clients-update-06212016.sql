ALTER TABLE app.classes DROP CONSTRAINT "U_class_name";

CREATE UNIQUE INDEX "U_active_class_name" ON app.classes (class_name, class_cat_id) WHERE active is true;


