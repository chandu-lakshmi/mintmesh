<!doctype html>
<html lang="en" ng-app="myApp" ng-cloak>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Forgot Password</title>
        <link rel="stylesheet" type="text/css" href="<?php echo url('/'); ?>/app/views/forgot-password/css/style.css" />
        <link rel="stylesheet" type="text/css" href="<?php echo url('/'); ?>/app/views/forgot-password/css/bootstrap.css" />
        <link rel="stylesheet" type="text/css" href="<?php echo url('/'); ?>/app/views/forgot-password/css/animate.min.css" />
        <link rel="stylesheet" type="text/css" href="<?php echo url('/'); ?>/app/views/forgot-password/css/angular-material.min.css" />

        <script type="text/javascript" src="<?php echo url('/'); ?>/app/views/forgot-password/js/jquery.js"></script>
        <script type="text/javascript" src="<?php echo url('/'); ?>/app/views/forgot-password/js/angular.js"></script>
        <script type="text/javascript" src="<?php echo url('/'); ?>/app/views/forgot-password/js/angular-material.min.js"></script>
        <script type="text/javascript" src="<?php echo url('/'); ?>/app/views/forgot-password/js/angular-animate.min.js"></script>
        <script type="text/javascript" src="<?php echo url('/'); ?>/app/views/forgot-password/js/angular-aria.min.js"></script>
        <script type="text/javascript" src="<?php echo url('/'); ?>/app/views/forgot-password/js/angular-messages.min.js"></script>
        <script type="text/javascript">
                    (function () {
                    "use strict";
                            angular.module('app.constants', [])
                            .constant('BASEURL', '<?php echo url('/'); ?>');
                    }());        </script>
    </head>
    <body>
        <div class="bg-image"  ng-controller="forgetPwdController as forgotCtrl">
            <div class="signin-signup">
                <section>
                    <div class="container">
                        <div class="row">
                            <div class="col-sm-5 col-xs-9 col-centered">
                                <div class="m_right">
                                    <!-- Forgot Password -->
                                    <div class="forgot-password-box slideInDown animated" ng-if="forgotCtrl.initStep">
                                        <div class="camp_logo">
                                            <img src="<?php echo url('/'); ?>/app/views/forgot-password/images/company_logo.svg" alt="logo">
                                        </div>
                                        <div class="forgot-password-content">
                                            <form role="form" name="re_enter_password_form" ng-submit="forgotCtrl.forgot_submit(re_enter_password_form.$valid)" novalidate>
                                                <div class="error-spacer" style="float:none">
                                                    <div class="has-error-msg text-center" style="float:none">
                                                        <span ng-if="forgotCtrl.backendError" style="font-size: 14px">{{forgotCtrl.backendMsg}}</span>
                                                    </div>
                                                </div>

                                                <div>
                                                    <md-input-container class="md-block">
                                                        <label for="new_password">New Password</label>
                                                        <input type="password" id="new_password" name="new_password" ng-model="forgotCtrl.forgotPassword.new_password" required ng-pattern="/^(?=.*[a-zA-Z0-9]).{6,}$/" ng-class="{'has-error-border':re_enter_password_form.new_password.$invalid && forgotCtrl.forgot_show_error}">
                                                        <div ng-if="re_enter_password_form.new_password.$error.required && forgotCtrl.forgot_show_error" class="has-error-msg">Please Enter Password.</div>
                                                        <div ng-if="re_enter_password_form.new_password.$error.pattern && forgotCtrl.forgot_show_error" class="has-error-msg">Please Enter Atleast 6 characters.</div>
                                                    </md-input-container>
                                                    <md-input-container class="md-block">
                                                        <label for="re_password">Confirm New Password</label>
                                                        <input type="password" id="re_password" name="re_password" ng-model="forgotCtrl.forgotPassword.re_password" pw-match="new_password" ng-class="{'has-error-border': re_enter_password_form.re_password.$invalid && forgotCtrl.forgot_show_error}" required>
                                                        <div ng-if="re_enter_password_form.re_password.$error.required && forgotCtrl.forgot_show_error" class="has-error-msg">Please Enter Confirm Password.</div>
                                                        <div ng-if="re_enter_password_form.re_password.$dirty && re_enter_password_form.re_password.$error.pwmatch && forgotCtrl.forgot_show_error && !re_enter_password_form.re_password.$error.required" class="has-error-msg">Password Doesn't Match.</div>
                                                    </md-input-container>
                                                    <div class="text-center"><button class="btn btn-lg btn-style btn-loader">Reset Password<img src="<?php echo url('/'); ?>/app/views/forgot-password/images/butt_loader.gif"  width="30px" style="margin-top: -2px;" alt="loader" ng-if="forgotCtrl.load_cond_reset"></button></div>
<!--                                                    <div class="back-to-home text-center"><span ng-click="forgotCtrl.forgot_cancel()">Cancel</span></div>-->
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                    <!-- Forgot Password Success -->
                                    <div class="verification-box zoomIn animated" ng-if="!forgotCtrl.initStep">
                                        <div class="verification-box-content text-center">
                                            <img src="<?php echo url('/'); ?>/app/views/forgot-password/images/tickmark.svg" alt="tick">
                                            <p class="text" ng-bind="forgotCtrl.backendMsg"></p>
<!--                                            <button class="btn btn-style" style="width:40%" ng-click="forgetPwdCtrl.signin()">OK</button>-->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
        <script type="text/javascript" src="<?php echo url('/'); ?>/app/views/forgot-password/js/home.js"></script>
    </body>
</html>

