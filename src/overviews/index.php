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
    <link href="css/1-col-portfolio.css" rel="stylesheet">

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
              <a class="nav-link" href="<?php echo htmlspecialchars("http://".array_shift((explode('.', $_SERVER['HTTP_HOST']))).".eduweb.co.ke"); ?>">Home
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
        <small><?php echo htmlspecialchars("http://".array_shift((explode('.', $_SERVER['HTTP_HOST']))).".eduweb.co.ke"); ?></small>
      </h2>

      <!-- Project One -->
      <div class="row">
        <div class="col-md-7">
          <a href="<?php echo htmlspecialchars("./".array_shift((explode('.', $_SERVER['HTTP_HOST'])))."-stream-analysis.php"); ?>">
            <img class="img-fluid rounded mb-3 mb-md-0" src="img/stream.jpg" alt="">
          </a>
        </div>
        <div class="col-md-5">
          <h3>Stream Analysis</h3>
          <p>This is the student's performance in the entire stream. Ranks students by the number of exams done and number of exam types done.
            This means if a student misses some papers or even an entire exam type,
            he/she will still be ranked according to the number of subjects the rest of the class did and according to the number of exam types
             the rest of the class has done.</p>
          <a class="btn btn-primary" href="<?php echo htmlspecialchars("./".array_shift((explode('.', $_SERVER['HTTP_HOST'])))."-stream-analysis.php"); ?>">View</a>
        </div>
      </div>
      <!-- /.row -->

      <hr>

      <!-- Project Two -->
      <div class="row">
        <div class="col-md-7">
          <a href="<?php echo htmlspecialchars("./".array_shift((explode('.', $_SERVER['HTTP_HOST'])))."-financial-analysis.php"); ?>">
            <img class="img-fluid rounded mb-3 mb-md-0" src="img/financials.jpg" alt="">
          </a>
        </div>
        <div class="col-md-5">
          <h3>Financial Analysis</h3>
          <p>This is the student's financial analysis. Includes the fee items for the student, the amounts he/she paid for each fee item and  balances if any.
          Also includes the combined amounts due, amounts paid and balances if any.</p>
          <a class="btn btn-primary" href="<?php echo htmlspecialchars("./".array_shift((explode('.', $_SERVER['HTTP_HOST'])))."-financial-analysis.php"); ?>">View</a>
        </div>
      </div>
      <!-- /.row -->

      <hr>

      <!-- Project Three -->
      <div class="row">
        <div class="col-md-7">
          <a href="<?php echo htmlspecialchars("./".array_shift((explode('.', $_SERVER['HTTP_HOST'])))."-performance-analysis.php"); ?>">
            <img class="img-fluid rounded mb-3 mb-md-0" src="img/mean_grade.jpg" alt="">
          </a>
        </div>
        <div class="col-md-5">
          <h3>Overall Performance Analysis</h3>
          <p>These are analyses for the overall performance of the class and the school. This is by analysing the overall class subject mean performance, the overall class mean performance against other classes as well as the
          overall school subject means. Also includes comparisons for student grade attainment in the different exam types. Note that the mean is derived from the number of students who actually sat for the paper. This is to avoid lower mean figures arising from students who missed out on a given paper(s).</p>
          <a class="btn btn-primary" href="<?php echo htmlspecialchars("./".array_shift((explode('.', $_SERVER['HTTP_HOST'])))."-performance-analysis.php"); ?>">View Project</a>
        </div>
      </div>
      <!-- /.row -->

      <hr>

      <!-- Project Four -->
      <!-- <div class="row">

        <div class="col-md-7">
          <a href="#">
            <img class="img-fluid rounded mb-3 mb-md-0" src="http://placehold.it/700x300" alt="">
          </a>
        </div>
        <div class="col-md-5">
          <h3>Project Four</h3>
          <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Explicabo, quidem, consectetur, officia rem officiis illum aliquam perspiciatis aspernatur quod modi hic nemo qui soluta aut eius fugit quam in suscipit?</p>
          <a class="btn btn-primary" href="#">View Project</a>
        </div>
      </div> -->
      <!-- /.row -->

      <hr>

      <!-- Pagination -->
      <ul class="pagination justify-content-center">
        <li class="page-item">
          <a class="page-link" aria-label="Previous"> <!-- removed href to disable link -->
            <span aria-hidden="true">&laquo;</span>
            <span class="sr-only">Previous</span>
          </a>
        </li>
        <li class="page-item">
          <a class="page-link" href="#">1</a>
        </li>
        <!-- <li class="page-item">
          <a class="page-link" href="#">2</a>
        </li>
        <li class="page-item">
          <a class="page-link" href="#">3</a>
        </li> -->
        <li class="page-item">
          <a class="page-link" aria-label="Next"> <!-- removed href to disable link -->
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
