<!DOCTYPE html>
<html lang="en">

  <head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Analyses by Eduweb</title>

    <!-- Bootstrap core CSS -->
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="css/4-col-portfolio.css" rel="stylesheet">

  </head>

  <body>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
      <div class="container">
        <a class="navbar-brand" href="#">Analyses by Eduweb</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarResponsive">
          <ul class="navbar-nav ml-auto">
            <li class="nav-item active">
              <a class="nav-link" href="<?php echo htmlspecialchars("https://".array_shift((explode('.', $_SERVER['HTTP_HOST']))).".eduweb.co.ke"); ?>">Home
                <span class="sr-only">(current)</span>
              </a>
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <!-- Page Content -->
    <div class="container">

      <!-- Page Heading -->
      <h2 class="my-4">Analyses for
        <small><?php echo htmlspecialchars("https://".array_shift((explode('.', $_SERVER['HTTP_HOST']))).".eduweb.co.ke"); ?></small>
      </h2>

      <div class="row">
        <!-- PROJECT ONE -->
        <div class="col-lg-3 col-md-4 col-sm-6 portfolio-item">
          <div class="card h-100">
            <a href="class-analysis.php">
              <img class="card-img-top" src="img/class-analysis.png" alt="Class Based Exam Analysis">
            </a>
            <div class="card-body">
              <h4 class="card-title">
                <a class="btn btn-primary" href="class-analysis.php">Class Analysis</a>
              </h4>
              <p class="card-text">This is the student's performance in the student's respective class. Ranks students according to the last exam that particular class did.</p>
            </div>
          </div>
        </div>
        <!-- PROJECT TWO -->
        <div class="col-lg-3 col-md-4 col-sm-6 portfolio-item">
          <div class="card h-100">
            <a href="stream-analysis.php">
              <img class="card-img-top" src="img/stream-analysis.png" alt="Stream Based Exam Analysis">
            </a>
            <div class="card-body">
              <h4 class="card-title">
                <a class="btn btn-primary" href="stream-analysis.php">Stream Analysis</a>
              </h4>
              <p class="card-text">This is the student's performance in the entire stream. Ranks students by the number of exams done and number of exam types done.</p>
            </div>
          </div>
        </div>
        <!-- PROJECT THREE -->
        <div class="col-lg-3 col-md-4 col-sm-6 portfolio-item">
          <div class="card h-100">
            <a href="#">
              <img class="card-img-top" src="img/grade-analysis.png" alt="Grade Attainment Analysis">
            </a>
            <div class="card-body">
              <h4 class="card-title">
                <a class="btn btn-primary" href="#">Grade Attainment</a>
              </h4>
              <p class="card-text">An analysis of the number of students in the class who attained each grade / marks</p>
            </div>
          </div>
        </div>
        <!-- PROJECT FOUR -->
        <div class="col-lg-3 col-md-4 col-sm-6 portfolio-item">
          <div class="card h-100">
            <a href="#">
              <img class="card-img-top" src="img/mean-analysis.png" alt="Examinations Mean Analysis">
            </a>
            <div class="card-body">
              <h4 class="card-title">
                <a  class="btn btn-primary" href="#">Mean Analysis</a>
              </h4>
              <p class="card-text">Mean marks attainment comparison for the respective classes for each subject done by the class.</p>
            </div>
          </div>
        </div>
        <!-- PROJECT FIVE -->
        <div class="col-lg-3 col-md-4 col-sm-6 portfolio-item">
          <div class="card h-100">
            <a href="financial-analysis.php">
              <img class="card-img-top" src="img/balances-analysis.png" alt="Opening Balances Financial Analysis">
            </a>
            <div class="card-body">
              <h4 class="card-title">
                <a  class="btn btn-primary" href="financial-analysis.php">Opening Balances</a>
              </h4>
              <p class="card-text">An overview of the school's opening balances, amounts paid and amounts owed.</p>
            </div>
          </div>
        </div>
        <!-- PROJECT SIX -->
        <div class="col-lg-3 col-md-4 col-sm-6 portfolio-item">
          <div class="card h-100">
            <a href="#">
              <img class="card-img-top" src="img/parents-and-students.jpg" alt="Student Fee Items Analysis">
            </a>
            <div class="card-body">
              <h4 class="card-title">
                <a  class="btn btn-primary" href="student-parents.php">Student Parent Data</a>
              </h4>
              <p class="card-text">An overview of all students and their parents details.</p>
            </div>
          </div>
        </div>
        <!-- PROJECT SEVEN -->
        <div class="col-lg-3 col-md-4 col-sm-6 portfolio-item">
          <div class="card h-100">
            <a href="#"><img class="card-img-top" src="img/app.jpg" alt=""></a>
            <div class="card-body">
              <h4 class="card-title">
                <a class="btn btn-primary" href="parent-data.php">Parent Credentials</a>
              </h4>
              <p class="card-text">App credentials with student, relationship and class.</p>
            </div>
          </div>
        </div>
        <!-- PROJECT EIGHT -->
        <!-- <div class="col-lg-3 col-md-4 col-sm-6 portfolio-item">
          <div class="card h-100">
            <a href="#"><img class="card-img-top" src="http://placehold.it/700x400" alt=""></a>
            <div class="card-body">
              <h4 class="card-title">
                <a class="btn btn-primary" href="#">Project Eight</a>
              </h4>
              <p class="card-text">Lorem ipsum dolor sit amet, consectetur adipisicing elit. Eius adipisci dicta dignissimos neque animi ea, veritatis, provident hic consequatur ut esse! Commodi ea consequatur accusantium, beatae qui deserunt tenetur ipsa.</p>
            </div>
          </div>
        </div> -->
      </div>
      <!-- /.row -->

      <!-- Pagination -->
      <ul class="pagination justify-content-center">
        <li class="page-item">
          <a class="page-link" href="#" aria-label="Previous">
            <span aria-hidden="true">&laquo;</span>
            <span class="sr-only">Previous</span>
          </a>
        </li>
        <li class="page-item">
          <a class="page-link" href="#">1</a>
        </li>
        <li class="page-item">
          <a class="page-link" href="#" aria-label="Next">
            <span aria-hidden="true">&raquo;</span>
            <span class="sr-only">Next</span>
          </a>
        </li>
      </ul>

    </div>
    <!-- /.container -->

    <!-- Footer -->
    <footer class="py-5 bg-dark">
      <div class="container">
        <p class="m-0 text-center text-white"><small>&copy; Eduweb <script type="text/javascript">document.write((new Date()).getFullYear())</script></small></p>
      </div>
      <!-- /.container -->
    </footer>

    <!-- Bootstrap core JavaScript -->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

  </body>

</html>
