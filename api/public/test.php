<h1>Login Test</h1>
<form action="/login/" method="post">
	<input type="test" name="user_name" value="test">
	<input type="test" name="user_pwd" value="test">
	<button type="submit">Login</button>
</form>


<h1>Add Class Category</h1>
<form action="/addClassCategory/" method="post">
	<input type="test" name="class_cat_name" value="">
	<button type="submit">Add Class Category</button>
</form>

<h1>Add Class</h1>
<form action="/addClass/" method="post">
<input type="hidden" value="1" name="user_id">
	<label>Class Name
	<input type="test" name="class_name" value="">
	</label>


	<label>Class Category
	<select name="class_cat_id">
		<option value="1">Baby Class</option>
	</select>
	</label>

	<label>Teacher
	<select name="teacher_id">
		<option value="1">Test Teacher</option>
	</select>
	</label>

	<button type="submit">Add Class</button>
</form>