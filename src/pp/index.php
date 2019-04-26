<!DOCTYPE html>
<html lang="en">

  <head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Eduweb PP</title>

    <!-- Bootstrap core CSS -->
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="css/4-col-portfolio.css" rel="stylesheet">

  </head>

  <body>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
      <div class="container">
        <a class="navbar-brand" href="#">PP (Private Pages)</a>
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
      <h2 class="my-4">Administrative Pages for -
        <small><?php echo htmlspecialchars("http://".array_shift((explode('.', $_SERVER['HTTP_HOST']))).".eduweb.co.ke"); ?></small>. Do not share this link.
      </h2>

      <div class="row">
        <!-- PROJECT ONE -->
        <div class="col-lg-3 col-md-4 col-sm-6 portfolio-item">
          <div class="card h-100">
            <a href="">
              <img class="card-img-top" src="img/statsDash.jpg" alt="Guardian list">
            </a>
            <div class="card-body">
              <h4 class="card-title">
                <a class="btn btn-primary" href="dash/index.html">Eduweb Stats</a>
              </h4>
              <p class="card-text">Eduweb Dashboard for General Stats and Traction</p>
            </div>
          </div>
        </div>
        <!-- PROJECT TWO -->
        <div class="col-lg-3 col-md-4 col-sm-6 portfolio-item">
          <div class="card h-100">
            <a href="parentlist.php">
              <img class="card-img-top" src="img/parentlist.jpg" alt="Batch student/ guardian entry">
            </a>
            <div class="card-body">
              <h4 class="card-title">
                <a class="btn btn-primary" href="parentlist.php">Guardian List</a>
              </h4>
              <p class="card-text">List of guardians, their phone numbers, their students and their classes.</p>
            </div>
          </div>
        </div>
        <!-- PROJECT THREE -->
        <div class="col-lg-3 col-md-4 col-sm-6 portfolio-item">
          <div class="card h-100">
            <a href="#">
              <img class="card-img-top" src="img/studententry.jpg" alt="Students Entry">
            </a>
            <div class="card-body">
              <h4 class="card-title">
                <a class="btn btn-primary" href="#">Batch student entry</a>
              </h4>
              <p class="card-text">Batch student and guardian data entry. Requires prepared excel file upload.</p>
            </div>
          </div>
        </div>
        <!-- PROJECT FOUR -->
        <div class="col-lg-3 col-md-4 col-sm-6 portfolio-item">
          <div class="card h-100">
            <a href="#">
              <img class="card-img-top" src="img/parentlist.jpg" alt="Downloaded App">
            </a>
            <div class="card-body">
              <h4 class="card-title">
                <a  class="btn btn-primary" href="app-downloaded.php">Downloaded App List</a>
              </h4>
              <p class="card-text">This is the list of parents in the school who have downloaded the app.</p>
            </div>
          </div>
        </div>
        <!-- PROJECT FIVE -->
        <div class="col-lg-3 col-md-4 col-sm-6 portfolio-item">
          <div class="card h-100">
            <a href="financial-analysis.php">
              <img class="card-img-top" src="img/plain.jpg" alt="Opening Balances Financial Analysis">
            </a>
            <div class="card-body">
              <h4 class="card-title">
                <a  class="btn btn-primary" href="#">Future</a>
              </h4>
              <p class="card-text">Future use.</p>
            </div>
          </div>
        </div>
        <!-- PROJECT SIX -->
        <div class="col-lg-3 col-md-4 col-sm-6 portfolio-item">
          <div class="card h-100">
            <a href="#">
              <img class="card-img-top" src="img/plain.jpg" alt="Student Fee Items Analysis">
            </a>
            <div class="card-body">
              <h4 class="card-title">
                <a  class="btn btn-primary" href="#">Future</a>
              </h4>
              <p class="card-text">Future use.</p>
            </div>
          </div>
        </div>
        <!-- PROJECT SEVEN -->
        <!-- <div class="col-lg-3 col-md-4 col-sm-6 portfolio-item">
          <div class="card h-100">
            <a href="#"><img class="card-img-top" src="http://placehold.it/700x400" alt=""></a>
            <div class="card-body">
              <h4 class="card-title">
                <a class="btn btn-primary" href="#">Project Seven</a>
              </h4>
              <p class="card-text">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam viverra euismod odio, gravida pellentesque urna varius vitae.</p>
            </div>
          </div>
        </div> -->
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
