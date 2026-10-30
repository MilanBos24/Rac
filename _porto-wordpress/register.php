<?php
// index.php – formulář pro přihlášení
session_start();
if (isset($_SESSION['user'])) {
    header("Location: home.php");
    exit();
}
?>
<!doctype html><html lang="cs" class="fixed">
<head>

<!-- Basic -->
<meta charset="UTF-8">
<meta name="keywords" content="HTML5 Admin Template" />
<meta name="description" content="Porto Admin - Responsive HTML5 Template">
<meta name="author" content="okler.net">
<!-- Mobile Metas -->
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<!-- Web Fonts -->		<link href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700,800|Shadows+Into+Light" rel="stylesheet" type="text/css">
<!-- Vendor CSS -->
<link rel="stylesheet" href="vendor/bootstrap/css/bootstrap.css" />		
<link rel="stylesheet" href="vendor/animate/animate.compat.css">		
<link rel="stylesheet" href="vendor/font-awesome/css/all.min.css" />		
<link rel="stylesheet" href="vendor/boxicons/css/boxicons.min.css" />		
<link rel="stylesheet" href="vendor/magnific-popup/magnific-popup.css" />		
<link rel="stylesheet" href="vendor/bootstrap-datepicker/css/bootstrap-datepicker3.css" />
<!-- Specific Page Vendor CSS -->
<!-- Theme CSS -->
<link rel="stylesheet" href="css/theme.css" />
<!-- Theme Custom CSS -->
<link rel="stylesheet" href="css/custom.css">
<!-- Head Libs -->
<script src="vendor/modernizr/modernizr.js"></script>
<script src="master/style-switcher/style.switcher.localstorage.js"></script>
</head>
<body>
<!-- start: page -->
<section class="body-sign">
<div class="center-sign">
<a href="/" class="logo float-start"><img src="img/logo-dark.webp" height="70" alt="Mil24 Admin" /></a>
<div class="panel card-sign">
<div class="card-title-sign mt-3 text-end">
<h2 class="title text-uppercase font-weight-bold m-0"><i class="bx bx-user-circle me-1 text-6 position-relative top-5"></i> Přihlášení</h2>
</div>
<div class="card-body">
<form action="skripty/register_process.php" method="post">

<div class="form-group mb-3"><label for="email">Email:</label><input type="email" name="email" class="form-control form-control-lg" required /></div>
<div class="form-group mb-3"><label for="username">Uživatelské jméno:</label><input type="text" name="username" class="form-control form-control-lg" required /></div>

<div class="row">
<div class="col-sm-6"><div class="form-group mb-3"><label for="password">Heslo:</label><input type="password" name="password" class="form-control form-control-lg" required /></div></div>
<div class="col-sm-6"><div class="form-group mb-3"><label for="confirm_password">Potvrďte heslo:</label><input type="password" name="confirm_password"  class="form-control form-control-lg" required /></div>
</div>


<div class="row">

<div class="col-sm-12 text-end"><button type="submit" class="btn btn-primary mt-2">Registrovat se</button></div></div>

</form>

</div></div>
<p class="text-center text-muted mt-3 mb-3">&copy; Copyright 2025. All Rights Reserved.</p>
</div>
</section>
<!-- end: page -->
<!-- Vendor -->
<script src="vendor/jquery/jquery.js"></script>		
<script src="vendor/jquery-browser-mobile/jquery.browser.mobile.js"></script>		
<script src="vendor/jquery-cookie/jquery.cookie.js"></script>		
<script src="master/style-switcher/style.switcher.js"></script>		
<script src="vendor/popper/umd/popper.min.js"></script>		
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>		
<script src="vendor/bootstrap-datepicker/js/bootstrap-datepicker.js"></script>		
<script src="vendor/common/common.js"></script>		
<script src="vendor/nanoscroller/nanoscroller.js"></script>		
<script src="vendor/magnific-popup/jquery.magnific-popup.js"></script>		
<script src="vendor/jquery-placeholder/jquery.placeholder.js"></script>
<!-- Specific Page Vendor -->
<!-- Theme Base, Components and Settings -->
<script src="js/theme.js"></script>
<!-- Theme Custom -->
<script src="js/custom.js"></script>
<!-- Theme Initialization Files -->
<script src="js/theme.init.js"></script>
<!-- Analytics to Track Preview Website -->

</body>
</html>

