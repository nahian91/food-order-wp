<?php

/*
Template Name: Contact
*/

get_header();?>

<div class="breadcrumb-area bg-cover text-center text-light" style="background-image: url(<?php echo get_template_directory_uri();?>/assets/img/breadcumb.jpg);">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 col-md-12">
                <h1>Contact Us</h1>
                <ul class="breadcrumb">
                    <li><a href="#"><i class="fas fa-home"></i> Home</a></li>
                    <li>Contact Us</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="contact-style-one-area default-padding overflow-hidden">

        <div class="container">
            <div class="row align-center">
                <div class="col-lg-6">
                    <div class="contact-style-one-info">
                                <h2>Find Food Lounge</h2>
                                <p>
                                    Plan upon yet way get cold spot its week. Almost do am or limits hearts. Resolve parties but why she shewing.
                                </p>
                                <ul>
                                    <?php 
                                        $contact_info = get_field('contact_info', 'option');
                                        foreach ($contact_info as $info) {
                                            ?>
                                                <li class="wow fadeInUp" style="visibility: visible; animation-name: fadeInUp;">
                                        <div class="icon">
                                            <i class="<?php echo $info['contact_info_icon'];?>"></i>
                                        </div>
                                        <div class="content">
                                            <h5 class="title"><?php echo $info['contact_info_title'];?></h5>
                                            <a href=""><?php echo $info['contact_info_description'];?></a>
                                        </div>
                                    </li>
                                            <?php 
                                        }
                                    ?>
                                </ul>
                            </div>
                </div>
                <div class="col-lg-6">
                    <div class="contact-form-style-one">
                        <div class="heading text-center">
                            <h5 class="sub-title">Keep in touch</h5>
                            <h2 class="heading">Send us a Massage</h2>
                        </div>
                        <form action="<?php echo get_template_directory_uri();?>/assets/mail/contact.php" method="POST" class="contact-form contact-form">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <input class="form-control" id="name" name="name" placeholder="Name" type="text" style="background-image: url(&quot;data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAABHklEQVQ4EaVTO26DQBD1ohQWaS2lg9JybZ+AK7hNwx2oIoVf4UPQ0Lj1FdKktevIpel8AKNUkDcWMxpgSaIEaTVv3sx7uztiTdu2s/98DywOw3Dued4Who/M2aIx5lZV1aEsy0+qiwHELyi+Ytl0PQ69SxAxkWIA4RMRTdNsKE59juMcuZd6xIAFeZ6fGCdJ8kY4y7KAuTRNGd7jyEBXsdOPE3a0QGPsniOnnYMO67LgSQN9T41F2QGrQRRFCwyzoIF2qyBuKKbcOgPXdVeY9rMWgNsjf9ccYesJhk3f5dYT1HX9gR0LLQR30TnjkUEcx2uIuS4RnI+aj6sJR0AM8AaumPaM/rRehyWhXqbFAA9kh3/8/NvHxAYGAsZ/il8IalkCLBfNVAAAAABJRU5ErkJggg==&quot;); background-repeat: no-repeat; background-attachment: scroll; background-size: 16px 18px; background-position: 98% 50%; cursor: auto;">
                                        <span class="alert-error"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <input class="form-control" id="email" name="email" placeholder="Email*" type="email">
                                        <span class="alert-error"></span>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <input class="form-control" id="phone" name="phone" placeholder="Phone" type="text">
                                        <span class="alert-error"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="form-group comments">
                                        <textarea class="form-control" id="comments" name="comments" placeholder="Your Message *"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12">
                                    <button type="submit" name="submit" id="submit">
                                        <i class="fa fa-paper-plane"></i> Get in Touch
                                    </button>
                                </div>
                            </div>
                            <!-- Alert Message -->
                            <div class="col-lg-12 alert-notification">
                                <div id="message" class="alert-msg"></div>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

<?php get_footer();?>