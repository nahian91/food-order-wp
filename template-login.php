<?php

/*
Template Name: Login
*/

get_header();?>

<div class="breadcrumb-area bg-cover text-center text-light" style="background-image: url(<?php echo get_template_directory_uri();?>/assets/img/breadcumb.jpg);">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 col-md-12">
                <h1>Login</h1>
                <ul class="breadcrumb">
                    <li><a href="#"><i class="fas fa-home"></i> Home</a></li>
                    <li>Login</li>
                </ul>
            </div>
        </div>
    </div>
</div>

    <!-- Start Login 
    ============================================= -->
    <div class="login-area">
        <div class="container">
            <div class="login-items">
                <div class="row">
                    <div class="col-lg-6">
                        <div class="login-thumb">
                            <img src="<?php echo get_template_directory_uri();?>/assets/img/banner/7.jpg" alt="Image Not Found">
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="login-forms">
                            <h2>Welcome back</h2>
                            <p>
                                Enter your email and password to continue
                            </p>
                            <form action="#">
                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="form-group">
                                            <input class="form-control" placeholder="Email*" type="email">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="form-group">
                                            <input class="form-control" placeholder="Password*" type="text">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-12">
                                        <button type="submit">
                                            Login
                                        </button>
                                    </div>
                                </div>
                            </form>
                            <div class="login-alternative">
                                <p>
                                    Don't have any account? <a href="register.html">Register Now</a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Login -->
     
  <?php get_footer();?>