(function () {
    "use strict";
    angular.module('myApp', ['ngMaterial', 'ngMessages', 'app.constants'])
            .controller('forgetPwdController', function ($http, BASEURL) {

                var scope = this;
                this.baseUrl = BASEURL;
                this.initStep = true;
                this.forgot_submit = function (isValid) {

                    scope.forgot_show_error = false;

                    if (!isValid) {
                        scope.forgot_show_error = true;
                    }
                    else {
                        scope.load_cond_reset = true;
                       
                        var reset_params = $.param({
                            password: scope.forgotPassword.new_password,
                            password_confirmation: scope.forgotPassword.re_password,
                            code: window.location.search.split('code=')[1]
                        })
                        var resetPassword = $http({
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                            },
                            method: 'POST',
                            url: BASEURL + "/v1/user/reset_password",
                            data: reset_params
                        })

                        resetPassword.success(function (response) {
                            scope.load_cond_reset = false;
                            if (response.status_code == 200) {
                                scope.initStep = false;
                                scope.backendMsg = response.message.msg[0];
                            }
                            else if (response.status_code == 403) {
                                scope.backendError = true;
                                scope.backendMsg = response.message.msg[0];
                            }
                            else if (response.status_code == 400) {
                                $window.location = CONFIG.APP_DOMAIN + 'logout';
                            }
                        })
                        resetPassword.error(function (response) {
                            scope.load_cond_reset = false;
                            console.log(response)
                        })
                    }
                }

                // cancel button
                this.forgot_cancel = function () {
                    setTimeout(function () {
                        $state.go('home')
                    }, 500);
                }
            })

            .directive('pwMatch', function () {
                return {
                    require: 'ngModel',
                    link: function (scope, elem, attrs, ctrl) {
                        var rePassword = '#' + attrs.pwMatch;
                        elem.add(rePassword).on('keyup', function () {
                            scope.$apply(function () {
                                var v = elem.val() === $(rePassword).val();
                                // alert(v);
                                ctrl.$setValidity('pwmatch', v);
                            });
                        });
                    }
                }
            })

}());