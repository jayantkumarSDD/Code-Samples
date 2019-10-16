/**
 * File     - popup.js
 * Author   - Kuldeep
 * Purpose  - this file will handle the behaviour
 * historical search.  
 * 
 *  */


function disableButton() {
    //$('#btn-visit-profile').attr('disabled', true);
    $('#btn-scan-profile').attr('disabled', true);
    $('#btn-history-profile').attr('disabled', true);

    //$('#btn-stop-search').attr('disabled', false);
}
function enableButton() {
    $('#btn-visit-profile').attr('disabled', false);
    $('#btn-scan-profile').attr('disabled', false);
    $('#btn-history-profile').attr('disabled', false);
    //$('#btn-download-profile').attr('disabled', false);
    //$('#btn-clear-data').attr('disabled', false);
    //$('#btn-stop-search').attr('disabled', true);
}
function stopProcess() {
    chrome.runtime.sendMessage({cmd: 'isCurrentTabIsLinkedIn'}, function (tab) {
        if (!tab) {
            disableButton();
        } else {
            enableButton();
        }
    });
    //$('#btn-stop-search').attr('disabled', true);
    chrome.runtime.sendMessage({cmd: 'stopProcess', type: 'scan'}, function (res) {
        $('.profile-recorded span').html(res.dayWiseVisit.scannedProfile);
        $('.today-visit span').html(res.dayWiseVisit.visitedProfile);
    });
}
function checkStatus() {
    
    chrome.runtime.sendMessage({cmd: 'getLatestUpdates'}, function (res) {
        
        if (res.user_id) {
            if (!res.userLoggedin) {
                $('#logoutBtn').click();
            } else {
                $('.profile-recorded span').html(typeof res.dayWiseVisit == 'undefined' ? 0 : res.dayWiseVisit.scannedProfile);
                $('.today-visit span').html(typeof res.dayWiseVisit == 'undefined' ? 0 : res.dayWiseVisit.visitedProfile);
                    
                    $('#allocated_scans').html(res.profile_limit.scanLimit);
                    $('#allocated_visits').html(res.profile_limit.visitLimit);
                if (res.processMode) {
                    disableButton();
                } else {
                    enableButton();
                }
            }
        }else{
            $('#user-navigation').hide();
            $('body').removeClass('main-panel');
            $('#login-panel').show();
            disableButton();
        }
    });
}

$(document).ready(function () {
    //resetData();

    chrome.storage.local.get('isInProgress', function (d) {
        if (typeof d.isInProgress != 'undefined') {
            if (!d.isInProgress.status) {
                stopProcess();
            }
        }
    });

    //$('#btn-download-profile').attr('disabled', true);
    //$('#btn-clear-data').attr('disabled', true);
    $('#btn-visit-profile').attr('disabled', true);
    
    


    chrome.storage.local.get('user_details', function (obj) {
        if (typeof obj.user_details == 'undefined') {
            $('#user-navigation').hide();
            $('body').removeClass('main-panel');
            $('#login-panel').show();
            disableButton();
        } else {
            if (obj.user_details.status) {
                $('#user-navigation').show();
                $('body').addClass('main-panel');
                $('#login-panel').hide();
                
            } else {
                $('#user-navigation').hide();
                $('body').removeClass('main-panel');
                $('#login-panel').show();
                disableButton();
            }
        }
        checkStatus();
    });
    $('#signUp').click(function () {
        window.location.href = webservice.register;
        chrome.tabs.create({
            url: webservice.register
        });
    });
    $('#btn-view-download').click(function () {
        window.close();
        chrome.tabs.create({url: chrome.runtime.getURL('Views/view.html')});
    });
    $('#settingBtn').click(function () {
        window.close();
        chrome.tabs.create({url: chrome.runtime.getURL('Views/dashboard.html')});
    });
    $('#btn-visit-profile').click(function () {
        window.close();
        //return;
        chrome.runtime.sendMessage({cmd: 'visitProfile', type: 'visit'}, function (res) {

        });
    });
    $('#btn-clear-data').click(function () {
        window.close();
        //if(!confirm('You will lost all data which you have collected today?'))return;
        chrome.runtime.sendMessage({cmd: 'clearData'}, function (res) {

        });
    });
    $('#btn-history-profile').click(function () {
        chrome.runtime.sendMessage({cmd: 'getHistoricalResult'}, function (res) {
            window.close();
            if (res.lastPage == 0 && res.profileIndex == 0) {
            } else {
                $('#btn-stop-search').attr('disabled', false);
                chrome.storage.local.set({isInProgress: {status: true}});
            }

        });
    });
    $('#openLinkedinPage').click(function () {
        chrome.runtime.sendMessage({cmd: 'openLinkedinPage'}, function (res) {});
    });
    $('#signIn').click(function () {
        $('#login-form').submit();
    });
    $('#btn-scan-profile').click(function () {
        window.close();
        disableButton();
        //$('#btn-stop-search').attr('disabled', false);
        chrome.runtime.sendMessage({cmd: 'startProcess', type: 'scan'}, function (res) {
            console.log(res);
        });
    });
    $('#btn-stop-search').click(function () {
        window.close();
        stopProcess();

    });
    $('#logoutBtn').click(function () {

        chrome.runtime.sendMessage({cmd: 'logout'}, function () {
            $('#login-email-txt').val('');
            $('#login-password-txt').val('');
            $('#login-form #errorMsg').hide().html('');
            $('#login-panel').show();
            $('#user-navigation').hide();
            $('.profile-recorded span').html(0);
            $('.today-visit span').html(0);
            $('body').removeClass('main-panel');
        });

    });
    $('#btn-download-profile').click(function () {
        window.close();
        //return;
        chrome.runtime.sendMessage({cmd: 'downloadCSVFile'}, function () {

        })
    });
    $('#login-form').submit(function () {
        var msg = "";
        var data = {
            email: $('#login-email-txt').val(),
            password: $('#login-password-txt').val()
        };
        if (data.email == "") {
            msg = 'Email is required!';
        } else if (data.password == "") {
            msg = 'Password is required!';
        }
        if (msg != "") {
            $('#login-form #errorMsg').show().html(msg);
            setTimeout(function () {
                $('#login-form #errorMsg').hide().html('');
            }, 3000)
            return false;
        }
        $('#login-form #errorMsg').show().html("Please wait...");
        chrome.runtime.sendMessage({cmd: 'userLogin', data: data}, function (res) {
            var user = {status: false};
            $('#login-form #errorMsg').show().html(res.msg);
            if (res.status == 200) {
                user.status = true;
                user.info = res.data;
                $('#login-panel').hide();
                $('#user-navigation').show();
                enableButton();
                $('body').addClass('main-panel');
                checkStatus();
            }
            chrome.storage.local.set({'user_details': user});
        });
        return false;
    });
    chrome.runtime.sendMessage({cmd: 'isLinkedInPageAvailable'}, function (response) {
        chrome.runtime.sendMessage({cmd: 'isCurrentTabIsLinkedIn'}, function (yes) {
            if (yes) {
                $('#navigation-panel').show();
                $('#linkedin-page-link').hide();
            } else {
                $('#navigation-panel').hide();
                $('#linkedin-page-link').show();
            }
        });
//        if (response.length) {
//            $('#navigation-panel').show();
//            $('#linkedin-page-link').hide();
//        } else {
//            $('#navigation-panel').hide();
//            $('#linkedin-page-link').show();
//        }
    });




    $('#signup-form').submit(function () {
        var errorMsg = "";
        $('#signup-form #errorMsg').html('').hide();
        $('#signup-form .form-input').each(function (k, elem) {
            var value = $(elem).val();
            var name = $(elem).attr('placeholder');
            if (value == "") {
                errorMsg = name + " is required!";
                return false;
            }
            if ($(elem).attr('id') == "email") {
                if (!validateEmail($(elem).val())) {
                    errorMsg = name + " isn't valid!";
                    return false;
                }
            }
            if ($(elem).attr('id') == "password") {
                if (($(elem).val()).length < 5) {
                    errorMsg = "Password should be minimum 5 characters!";
                    return false;
                }
            }
            if ($(elem).attr('id') == "cpassword") {
                if ($(elem).val() != $('#password').val()) {
                    errorMsg = "Password not match!";
                    return false;
                }
            }
        });
        if (errorMsg != "") {
            $('#signup-form #errorMsg').show().html(errorMsg);
            setTimeout(function () {
                $('#signup-form #errorMsg').hide(500)
            }, 3000);
            return false;
        }
        $('#signup-form #errorMsg').show().html('Please wait...');
        var data = {
            firstName: $('#firstName').val(),
            lastName: $('#lastName').val(),
            email: $('#email').val(),
            password: $('#password').val()
        };

        chrome.runtime.sendMessage({'userSignUp': true, data: data}, function (res) {
            $('#signup-form #errorMsg').html(res.msg);
            if (res.status == 200) {
                notify('SignUp Success', {body: 'Your account successfully created, We have sent an verification email. Please verify your account'});
                setTimeout(function () {
                    $('#signup-form').hide(500);
                }, 4000);
            }
            setTimeout(function () {
                $('#signup-form #errorMsg').hide(500);
            }, 4000);
        });
        return false;
    });

});
function validateEmail(email) {
    var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(String(email).toLowerCase());
}