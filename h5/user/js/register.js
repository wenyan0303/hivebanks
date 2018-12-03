$(function () {
    //get invite
    var str = window.location.search.split("=")[1];
    if(str){
        var invite = window.atob(str);
        $(".emailInvitCode,.phoneInvitCode").val(invite);
    }

    //Whether to allow registration
    var type = 'us';
    RegisterSwitch(type, function (response) {
        if (response.errcode == '0') {
            var data = response.rows;
            if (data[0].option_name == 'user_lock' && data[0].is_open == '0') {
                $('.form_col').remove();
                $('.sec-row').html('<h2 style="color: #fff" class="i18n font-weight-400" name="unableRegister"></h2>');
                execI18n();
                return;
            }
        }
    }, function (response) {
        LayerFun(response.errcode);
        return;
    });
    GetImgCode();

    $('#phone_imgCode').click(function () {
        GetImgCode();
    });

    // Switch mailbox and mobile registration
    $('.registerToggle').click(function () {
        $(this).addClass('active').siblings().removeClass('active');
    });
    // Switch mailbox registration
    $('.emailRegister').click(function () {
        $('.phoneRegisterBox').fadeOut();
        $('.emailRegisterBox').fadeIn();
    });
    // Switch phone registration
    $('.phoneRegister').click(function () {
        $('.emailRegisterBox').fadeOut();
        $('.phoneRegisterBox').fadeIn();
        GetImgCode();
    });

    // Monitor mailbox registration input
    //emailInput
    $('.email').blur(function () {
        var email = $('.email').val();
        if (email.length <= 0) {//Is it empty?
            $('.email_tips').fadeIn('fast').siblings('span').fadeOut('fast');
        } else {
            $('.email_tips').fadeOut('fast');
        }
        if (!IsEmail(email)) {//Bad Mailbox Format
            $('.emailErrorTips').fadeIn('fast').siblings('span').fadeOut('fast');
        } else {
            $('.emailErrorTips').fadeOut('fast');
        }
    });

    //emailPassInput
    $('#emailPass').blur(function () {
        var emailPass = $('#emailPass').val();
        if (emailPass.length <= 0) {//Is it empty?
            $('.password_tips').fadeIn('fast').siblings('span').fadeOut('fast');
        } else if (emailPass.length < 8) {
            $('.errEmailPass_tips').fadeIn('fast').siblings('span').fadeOut('fast');
        } else {
            $('.password_tips').fadeOut('fast');
            $('.errEmailPass_tips').fadeOut('fast');
        }
    });

    //againEmailPasswordInput
    $('.againEmailPassword').blur(function () {
        var againEmailPassword = $('.againEmailPassword').val();
        if (againEmailPassword.length <= 0) {//Is it empty?
            $('.emailAgainPassword_tips').fadeIn('fast').siblings('span').fadeOut('fast');
        } else if (againEmailPassword != $('#emailPass').val()) {
            $('.emailSamePassword_tips').fadeIn('fast').siblings('span').fadeOut('fast');
        } else {
            $('.emailAgainPassword_tips').fadeOut('fast');
            $('.emailSamePassword_tips').fadeOut('fast');
        }
    });

    // ========email registration========
    var _email = '', emailList = '';
    $('.emailRegisterBtn').click(function () {
        var email = $('.email').val(),
            pass_word = $('.emailPassword').val(),
            againEmailPassword = $('.againEmailPassword').val(),
            pass_word_hash = hex_sha1(pass_word),
            invit_code = $('.emailInvitCode').val();

        if (email.length <= 0) {
            LayerFun('emailNotEmpty');
            $('.email_tips').fadeIn();
            return;
        }
        if (pass_word.length <= 0) {
            LayerFun('passNotEmpty');
            $('.password_tips').fadeIn();
            return;
        }
        if (againEmailPassword.length <= 0) {
            LayerFun('confirmPasswordNotEmpty');
            $('.emailAgainPassword_tips').fadeIn();
            return;
        }
        if (pass_word != againEmailPassword) {
            LayerFun('TwoPassword');
            $('.emailSamePassword_tips').fadeIn();
            return;
        }

        _email = email.split('@')[1];
        emailList = EmailList();

        var $this = $(this), btnText = $this.text();
        if (DisableClick($this)) return;
        EmailRegister(email, pass_word, pass_word_hash, invit_code, function (response) {
            ActiveClick($this, btnText);
            if (response.errcode == '0') {
                $('.email').val('');
                $('.emailPassword').val('');
                $('.againEmailPassword').val('');
                $('.emailInvitCode').val('');
                $('#registerSuccess').modal('show');//Registration successfully displayed prompt
            }
        }, function (response) {
            ActiveClick($this, btnText);
            if (response.errcode == '105') {
                $('.emailLoginTips').fadeIn('fast');
            }
            if (response.errcode == '121') {
                $('#alreadyRegister').modal('show');
            }
            GetImgCode();
            LayerFun(response.errcode);
            return;
        });
    });
    //Go to the mailbox to verify
    $('.goEmailBtn').click(function () {
        window.location.href = 'login.html';
        window.open(emailList[_email]);
    });

    //phoneInput
    //phone
    $('#phone').focus(function () {
        $('.phone_tips').fadeOut('fast');
        $('.phoneErrorTips').fadeOut('fast');
        $('.phoneLoginTips').fadeOut('fast');
    });
    $('#phone').blur(function () {
        var phone = $('#phone').val();
        if (phone.length <= 0) {
            $('.phone_tips').fadeIn('fast').siblings('span').fadeOut();
        } else if (isNaN(phone)) {
            $('.phoneErrorTips').fadeIn('fast').siblings('span').fadeOut();
        } else {
            $('.phone_tips').fadeOut('fast');
            $('.phoneErrorTips').fadeOut('fast');
        }
    });

    //phoneCfmCode-
    $('.phoneCfmCode').blur(function () {
        var phoneCfmCode = $('.phoneCfmCode').val();
        if (phoneCfmCode.length <= 0) {
            $('.phoneCode_tips').fadeIn('fast').siblings('span').fadeOut();
        } else {
            $('.phoneCode_tips').fadeOut('fast');
            $('.errPhoneCode_tips').fadeOut('fast');
        }
    });

    //phoneSmsCode-
    $('.phoneSmsCode').focus(function () {
        $('.phoneSmsCode_tips').fadeOut('fast');
        $('.phoneCode_expired').fadeOut('fast');
    });
    $('.phoneSmsCode').blur(function () {
        var phoneSmsCode = $('.phoneSmsCode').val();
        if (phoneSmsCode.length <= 0) {
            $('.phoneSmsCode_tips').fadeIn('fast').siblings('span').fadeOut();
        } else {
            $('.phoneSmsCode_tips').fadeOut('fast');
        }
    });

    //phonePassword
    $('#phonePass').blur(function () {
        var phonePass = $('#phonePass').val();
        if (phonePass.length <= 0) {
            $('.PhonePassword_tips').fadeIn('fast').siblings('span').fadeOut();
        } else if (phonePass.length < 8) {
            $('.errPhonePass_tips').fadeIn('fast').siblings('span').fadeOut();
        } else {
            $('.PhonePassword_tips').fadeOut('fast');
            $('.errPhonePass_tips').fadeOut('fast');
        }
    });

    //phoneAgainPassword
    $('.againPhonePassword').blur(function () {
        var againPhonePassword = $('.againPhonePassword').val();
        if (againPhonePassword.length <= 0) {
            $('.phoneAgainPassword_tips').fadeIn('fast').siblings('span').fadeOut();
        } else if (againPhonePassword != $('#phonePass').val()) {
            $('.phoneSamePassword_tips').fadeIn('fast').siblings('span').fadeOut();
        } else {
            $('.phoneAgainPassword_tips').fadeOut('fast');
            $('.phoneSamePassword_tips').fadeOut('fast');
        }
    });

    //Get phone verification code
    $('.phoneCodeBtn').click(function () {
        var bind_type = '1', $this = $(this), cfm_code = $('.phoneCfmCode').val();
        if ($(".phone").val().length <= 0) {
            $('.phone_tips').fadeIn().siblings('span').hide();
            LayerFun('phoneNotEmpty');
            return;
        }
        if (cfm_code.length <= 0) {
            LayerFun('codeNotEmpty');
            $('.phoneCode_tips').fadeIn();
            return;
        }
        setTime($this);
        GetPhoneCodeFun(bind_type, $this, cfm_code);
    });
    /**
     /* ========Register your phone========
     * Click to register to submit
     */
    $('.phoneRegisterBtn').click(function () {
        var country_code = $('.selected-dial-code').text().split("+")[1];
        // Get user input
        var cellphone = $('.phone').val(),
            sms_code = $('.phoneSmsCode').val(),
            phoneCfmCode = $('.phoneCfmCode').val(),
            pass_word = $('.phonePassword').val(),
            again_pass_word = $('.againPhonePassword').val(),
            pass_word_hash = hex_sha1(pass_word),
            invit_code = $('.phoneInvitCode').val();
        if (cellphone.length <= 0) {
            LayerFun('phoneNotEmpty');
            $('.phone_tips').fadeIn();
            return;
        }
        if (pass_word.length <= 0) {
            LayerFun('passNotEmpty');
            $('.PhonePassword_tips').fadeIn();
            return;
        }
        if (again_pass_word.length <= 0) {
            LayerFun('confirmPasswordNotEmpty');
            $('.phoneAgainPassword_tips').fadeIn();
            return;
        }
        if (phoneCfmCode.length <= 0) {
            LayerFun('codeNotEmpty');
            $('.phoneCode_tips').fadeIn();
            return;
        }
        if (pass_word != again_pass_word) {
            LayerFun('TwoPassword');
            $('.phoneSamePassword_tips').fadeIn();
            return;
        }
        if (sms_code.length <= 0) {
            LayerFun('codeNotEmpty');
            $('.phoneSmsCode_tips').fadeIn();
            return;
        }
        var $this = $(this), btnText = $(this).text();
        if (DisableClick($this)) return;
        PhoneRegister(country_code, cellphone, sms_code, pass_word, pass_word_hash, invit_code, function (response) {
            ActiveClick($this, btnText);
            if (response.errcode == '0') {
                $('.phone').val('');
                $('.phoneCfmCode').val('');
                $('.phonePassword').val('');
                $('.againPhonePassword').val('');
                $('.phoneInvitCode').val('');
                window.location.href = 'login.html?name=phone';
            }
        }, function (response) {
            ActiveClick($this, btnText);
            if (response.errcode == '100') {
                $('.phoneErrorTips').fadeIn('fast');
            }
            if (response.errcode == 105) {
                $('.phoneLoginTips').fadeIn('fast');
            }
            if (response.errcode == 111) {
                $('.phoneCode_expired').fadeIn('fast');
            }
            GetImgCode();
            LayerFun(response.errcode);
            return;
        });
    });
});








