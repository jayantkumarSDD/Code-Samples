/**
 * File         - content_script.js
 * Author       - Malik Khan
 * Purpose      - Handle the extension working in the website
 * Script type  - Content Script
 */
//-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue","Fira Sans",Ubuntu,Oxygen,"Oxygen Sans",Cantarell,"Droid Sans","Apple Color Emoji","Segoe UI Emoji","Segoe UI Emoji","Segoe UI Symbol","Lucida Grande",Helvetica,Arial,sans-serif
const MIN_TIME_TO_NEXT = 1; // 11 scan min time
const MAX_TIME_TO_NEXT = 2; // 17 scan max time
const FLYING_SPEED = 1000; // scan animate fying speed
const RATOTE_SPEED = 5; //scan animate rotate speed
// min max interval for visiting
const VISIT_MIN_TIME_TO_NEXT = 3; // 5
const VISIT_MAX_TIME_TO_NEXT = 15; // 15
const SKIPPED_TIMEOUT = 1000; // Skip Profile Timeout 
const LINKEDIN_DOMAIN_URL = 'https://www.linkedin.com/';
const LINKEDIN_PROFILE_PAGE = LINKEDIN_DOMAIN_URL + 'in/';
const SALES_PROFILE_LINK = LINKEDIN_DOMAIN_URL + 'sales/people/';
const RECRUITER_PROFILE_LINK = LINKEDIN_DOMAIN_URL + 'recruiter/profile/';
const COMPANY_PROFILE_LINK = LINKEDIN_DOMAIN_URL + 'voyager/api/organization/companies/';
var START_FROM = 0;
var LIMIT = 0;
var SCAN_LIMIT = 0;
var VISIT_LIMIT = 0;
const LIST_OUT_OF_NETWORK = [`fora da rede`, `ネットワーク外`, `人脉圈外`, `en dehors du réseau`, `fuera de la red`, `out of network`, `außerhalb des Netzwerks`];
var MODE = 'scan';
var HISTORICAL_PAGE = 0;
var isSalesNav = false;
var PROCESS_ACTIVE = false;
var pageData = {
    account_type: "Basic"
};
var messageTemplate = "Hi {name}, What are you doing now?";
var JSESSIONID_REGEX = new RegExp('JSESSIONID=["]*(.*?)["]*;');// It will be use as a CSRF Token
/* Set Account type of user*/
function initAccountType() {
    if (location.href.indexOf(LINKEDIN_DOMAIN_URL + 'sales') >= 0) {
        pageData.account_type = 'Sales';
    } else if (location.href.indexOf(LINKEDIN_DOMAIN_URL + 'recruiter') >= 0) {
        pageData.account_type = 'Recruiter';
    } else {
        pageData.account_type = 'Basic';
    }
}
function getFetchData(html) {
    var data = new Object();
    html = $(html);
    data.skills = [], data.experience = [], data.also_viewed = [];
    data.recommendations = "";
    data.num_connections = "";
    if (pageData.account_type == "Sales") {
        html.find('#profile-skills ul.profile-skills__list li').each(function () {
            data.skills.push(cleanStr($(this).find('.profile-skills__skill-name').text()));
        });
        var conns = html.find('.profile-topcard__connections-data').text();
        conns = cleanStr(escapeHtml(conns));
        if (conns) {
            data.num_connections = conns.match(/\d+/);
            if (data.num_connections >= 500) {
                data.num_connections = data.num_connections + "+";
            }
        }
        var experienceList = html.find('#profile-experience ul.profile-experience__position-list li dl');
        experienceList.each(function () {
            var position = cleanStr($(this).find('.profile-position__title').text());
            var duration = cleanStr(escapeHtml($(this).find('.profile-position__duration').text()));
            if (duration && duration != "" && typeof duration != 'undefined') {
                duration = duration.replace("Employment Duration", "");
            }
            var company = cleanStr($(this).find('.profile-position__secondary-title span a').text());
            var exp = cleanStr(($(this).find('p.profile-position__dates-employed').text()).replace('Dates Employed', ''));
            data.experience.push({
                start: exp,
                duration: cleanStr(duration),
                title: position,
                organization: [{
                        name: company
                    }]
            });
        });
        data.recommendations = 0;
        html.find('.profile-recommendations .artdeco-scrolling-container artdeco-tab').each(function () {
            var val = $(this).text();
            val = val.match(/\d+/);
            if (typeof val != 'undefined')
                data.recommendations += parseInt(val[0]);
        });
    } else if (pageData.account_type == "Basic") {
        html.find('section.pv-profile-section .pv-skill-category-entity__name a span').each(function () {
            data.skills.push(cleanStr($(this).text()));
        });
        var conns = html.find('.pv-top-card-v2-section__link--connections .pv-top-card-v2-section__connections').text();
        conns = cleanStr(escapeHtml(conns));
        if (conns) {
            data.num_connections = conns.match(/\d+/);
            if (data.num_connections >= 500) {
                data.num_connections = data.num_connections + "+";
            }
        }

        html.find('li.pv-browsemap-section__member-container').each(function () {
            var that = $(this);
            var name = that.find('h3.actor-name-with-distance .actor-name').text();
            var headline = that.find('p.browsemap-headline').text();
            var url = that.find('a').attr('href');
            data.also_viewed.push({
                name: cleanStr(name),
                headline: cleanStr(headline),
                url: LINKEDIN_DOMAIN_URL + "in" + url
            });
        });
        data.recommendations = 0;
        html.find('section .pv-recommendations-section .artdeco-scrolling-container artdeco-tab').each(function () {
            var val = $(this).text();
            val = val.match(/\d+/);
            if (typeof val != 'undefined')
                data.recommendations += parseInt(val[0]);
        });
        var educationList = html.find('#experience-section ul div.pv-entity__position-group-pager li.pv-profile-section');
    } else if (pageData.account_type == "Recruiter") {
        html.find('#profile-skills ul li').each(function () {
            data.skills.push(cleanStr($(this).html()));
        });
        var conns = html.find('.connection-info .connections-badge').text();
        conns = cleanStr(escapeHtml(conns));
        if (conns) {
            data.num_connections = conns.match(/\d+/);
            if (data.num_connections >= 500) {
                data.num_connections = data.num_connections + "+";
            }
        }
    }
    return data;
}

$(document).ready(function () {
    var basic = 'https://www.linkedin.com/in/cassandra-pope-3b186563/';
    var recruiter = 'https://www.linkedin.com/recruiter/profile/193579016,Txs6,CAP';
    var sales = 'https://www.linkedin.com/sales/people/ACoAABXehLsBG6XTWBzgcqOHeFAw5lc7MYRiGho,';//,websiteUrl,url
    setProfileLimit(function () {
        chrome.storage.local.get('pageReload', function (page) {
            //console.log(page);
            if (typeof page.pageReload == 'undefined') {
                chrome.storage.local.set({pageReload: false});
            } else {
                if (page.pageReload) {
                    chrome.storage.local.get('profile_data', function (d) {
                        if (typeof d.profile_data !== 'undefined') {
                            scrollDown("", function () {
                                PROCESS_ACTIVE = true;
                                chrome.storage.local.set({isInProgress: {status: PROCESS_ACTIVE}});
                                MODE = d.profile_data.history.lastOperation;
                                if (MODE == 'visit') {
                                    LIMIT = VISIT_LIMIT;
                                    START_FROM = d.profile_data.dayWiseVisit.visitedProfile;
                                } else if (MODE == 'scan') {
                                    LIMIT = SCAN_LIMIT;
                                    START_FROM = d.profile_data.dayWiseVisit.scannedProfile;
                                }
                                chrome.storage.local.set({pageReload: false});
                                setTimeout(function () {
                                    startScanning(parseInt(d.profile_data.history.profileIndex));
                                }, 1000);

                            });
                        }
                    });

                }
            }
        });
    });
    initAccountType();
});
/* Set the scan and visit limit*/
function setProfileLimit(callback) {
    chrome.storage.local.get('profile_limit', function (d) {
        if (typeof d.profile_limit == 'undefined') {
            SCAN_LIMIT = 1500, VISIT_LIMIT = 500;
        } else {
            SCAN_LIMIT = d.profile_limit.scanLimit;
            VISIT_LIMIT = d.profile_limit.visitLimit;
        }
        if (MODE == 'visit') {
            LIMIT = VISIT_LIMIT;
        } else if (MODE == 'scan') {
            LIMIT = SCAN_LIMIT;
        }
        callback();
    });
}
chrome.runtime.onMessage.addListener(function (message, sender, sendResponse) {

    switch (message.cmd) {
        case 'startProcess':
            startProcess(sendResponse);
            break;
        case 'stopProcess':
            stopProcess(sendResponse);
            break;
        case 'showNotification':
            showNotification(message);
            break;
        case 'startHistoricalSearch':
            startHistoricalSearch(message, sendResponse);
            break;
        case 'startProfileVisiting':
            startProfileVisiting(sendResponse);
            break;
        case 'showPopup':
            showPopup(message, sendResponse);
            break;
    }
    return true;
});
/* Confirm/Alert/Tips Dialog box*/
function showPopup(options, callback) {
    if (options.type == "confirm") {
        $.confirm("<center>" + options.msg + "</center>", function (a) {
            callback(a);
        });
    } else if (options.type == "alert") {
        $.alert("<center>" + options.msg + "</center>", function (a) {
            callback(a);
        });
    } else if (options.type == "tips") {
        $.tips(options.msg, 5000);
    }
}

/* Profile Visiting Start From Here*/
function startProfileVisiting(callback) {
    PROCESS_ACTIVE = true;
    MODE = 'visit';
    START_FROM = 0;
    LIMIT = VISIT_LIMIT;
    chrome.storage.local.set({isInProgress: {status: PROCESS_ACTIVE}});
    chrome.storage.local.get('profile_data', function (d) {
        START_FROM = d.profile_data.dayWiseVisit.visitedProfile;
        startScanning(0);
    });

}

/* Show Notification Using Toastr */
function showNotification(options) {
    if (options.type == "error") {
        $.toast().error(options.msg, options.title, {timeOut: 5000});
    } else if (options.type == "info") {
        $.toast().info(options.msg, options.title, {timeOut: 5000});
    } else if (options.type == "success") {
        $.toast().success(options.msg, options.title, {timeOut: 5000});
    } else if (options.type == "warning") {
        $.toast().warning(options.msg, options.title, {timeOut: 5000});
    }
}

/* Remove begainig and last Spaces from a string*/
function cleanStr(str) {
    if (typeof str !== 'undefined') {
        if (str.length)
            str = str.trim();
        str = str
                .replace(/&amp;/g, '&')
                .replace(/&quot;/g, '"')
                .replace(/&#39;/g, "'")
                .replace(/&lt;/g, '<')
                .replace(/&gt;/g, '>');
    } else
        str = "";
    return str;
}
/* Fetch User Information from list*/
function getUserInformation(objStr, callback) {
    var linkedInId, name, entityUrn, designation, profilePicture, locality, companyName, phoneNumber, website, skills, certification, education, experience, source;
    var STROBJ = $(objStr);
    var postedData = {
        linkedinId: "",
        entityUrn: "",
        scrapMode: MODE,
        source: pageData.account_type,
    };
    try {
        if (pageData.account_type == "Basic") {
            var regPersonName = objStr.match(/<span.*class=".*name.*actor-name.*?">(.+?)<\/span>/mi);
            var regPersonId = objStr.match(/href="\/in\/(.+)\/"/i);
            if (regPersonId == null) {
                callback(false);
                return;
            }
            if (regPersonId && typeof regPersonId[1] !== 'undefined')
                linkedInId = regPersonId[1];
            postedData.url = regPersonId[0].match(/href="\/(.+?)"/i)[1];
            postedData.full_name = escapeHtml(regPersonName[1]);
            postedData.headline = escapeHtml(STROBJ.find('a.search-result__result-link').next('p').html());
            if (postedData.headline.indexOf(' at ') > 0) {
                postedData.current_company_name = (postedData.headline.split(' at ')[1]).trim();
            }
            var regPersonPosition2 = objStr.match(/(.+?) at/i);
            var regPersonCompany3 = objStr.match(/at (.+)/i);
            var regPersonLocality = objStr.match(/<p.*class="subline-level-2.*search-result__truncate">\s*([\s\S]+?)\s*<\/p>/mi);
            if (regPersonLocality && typeof regPersonLocality[1] !== 'undefined')
                postedData.locality = escapeHtml(regPersonLocality[1]);
            var regPersonDescription = objStr.match(/<p.*class="subline-level-1.*search-result__truncate">\s*([\s\S]+?)\s*<\/p>/mi);
            var imageObj = STROBJ.find('.search-result__image-wrapper .presence-entity__image');
            profilePicture = imageObj.css('background-image');
            profilePicture = /^url\((['"]?)(.*)\1\)$/.exec(profilePicture);
            postedData.image_url = (profilePicture && profilePicture != null) ? profilePicture[2] : ""; // If matched, retrieve url, otherwise ""
            companyName = "";
            var degree = /<span class="dist-value">(.+?)<\/span>/mi.exec(objStr);
            if (degree) {
                postedData.degree = degree[1];
            }
            if (regPersonCompany3 !== null)
                if (typeof regPersonCompany3[1] !== 'undefined') {
                    companyName = regPersonCompany3[1];
                }
            designation = regPersonPosition2 ? regPersonPosition2[1] : ((regPersonDescription) ? regPersonDescription[1] : "");
            source = "Basic";
            postedData.linkedinId = cleanStr(linkedInId);
        } else if (pageData.account_type == "Sales") {
            var regSalesPersonSearchLink = objStr.match(/sales\/people\/(.*?),/i);
            if (regSalesPersonSearchLink) {
                entityUrn = regSalesPersonSearchLink[1];
                postedData.linkedinId = postedData.entityUrn = cleanStr(entityUrn);
            }
            postedData.url = "sales/people/" + postedData.linkedinId + ",";
            var companyUrl = STROBJ.find('.result-lockup__position-company a').attr('href');
            if (companyUrl && companyUrl != "" && typeof companyUrl != 'undefined') {
                postedData.company_linkedin_url = LINKEDIN_DOMAIN_URL + companyUrl.slice(1);
            }
            var degree = /<span class="a11y-text">(.+?)<\/span>/mi.exec(objStr);
            if (degree) {
                postedData.degree = degree[1];
            }
            var obj = STROBJ.find('.result-lockup__position-company span');
            if (obj.length) {
                postedData.current_company_name = cleanStr(escapeHtml($(obj[0]).text()));
            }
            obj = STROBJ.find('.result-lockup__highlight-keyword span');
            if (obj.length) {
                postedData.headline = cleanStr(escapeHtml($(obj[0]).text()));
            }
            if (typeof postedData.current_company_name != 'undefined' && postedData.current_company_name != "") {
                postedData.headline += postedData.headline + " at " + postedData.current_company_name;
            }
            postedData.locality = cleanStr(escapeHtml(STROBJ.find('li.result-lockup__misc-item').html()));
            name = STROBJ.find(".result-lockup__name a").text();
            postedData.full_name = postedData.given_name = postedData.family_name = cleanStr(escapeHtml(name));
            var regPersonImage = /class="lazy-image .*loaded".*?alt="(.+?)" src="(.+?)"/mi.exec(objStr);
            if (regPersonImage && typeof regPersonImage[2] != 'undefined')
                postedData.image_url = regPersonImage[2];
            var duration = STROBJ.find('dd.result-lockup__highlight-keyword').next('dd').children();
            if (duration.length) {

                postedData.experience = [];
                duration.each(function () {
                    var workduration = cleanStr(escapeHtml($(this).html()));
                    if (workduration.indexOf('month')) {
                        postedData.experience.push({
                            duration: workduration,
                            organization: [
                                {
                                    name: postedData.industry
                                }
                            ]
                        });
                    }
                });
            }

        } else if (pageData.account_type == 'Recruiter') {
            postedData.image_url = $(objStr).find('img.profile-img').attr('src');
            name = /class="search-result-profile-link" title=".*?">(.+?)<\/a>/i.exec(objStr);
            if (name && name[1] != null) {
                postedData.full_name = cleanStr(name[1]);
            }
            var linkedInId = /<a href="\/recruiter\/profile\/(\d+?),(.+)"/i.exec(objStr);
            var remID = (linkedInId[2].split('?')[0]).split(',');
            postedData.linkedinId = cleanStr(linkedInId[1]);// + "," + remID[0] + "," + remID[1];
            var profileAuthToken = remID[0];
            var profileAuthType = remID[1];
            postedData.url = "recruiter/profile/" + postedData.linkedinId + "," + profileAuthToken + "," + profileAuthType;
            var cPosLi = /<dd class="row-content curr-positions">([\s\S]*?)<\/dd>/i.exec(objStr);
            var cPos = /<li data-index="(\d+)">([\s\S]*?)<span class="date-range"/i.exec(cPosLi);
            if (cPos == null) {
                cPos = /<li data-index="(\d+)">([\s\S]*?)<\/li>/gi.exec(cPosLi);
            }
            if (cPos) {
                var profile = escapeHtml(cPos[2]).match(/(.+?) at (.*)/i);
                postedData.headline = escapeHtml(cPos[2]);
                postedData.current_company_name = profile && typeof profile[2] !== 'undefined' ? profile[2] : "";
            }
            //callXHROnLinkedIn(LINKEDIN_DOMAIN_URL + 'recruiter/api/profile/430216346,aYyd,CAP/profile/', [], function (resp) {
            //console.log(resp);
            //});
            postedData.locality = $($(objStr).find('.location span')[0]).html();
            postedData.degree = $(objStr).find('.degree-icon.badge__seperator').html();

            var pastPos = /<dd class="row-content past-positions">([\s\S]*?)<\/dd>/i.exec(objStr);
            if (pastPos) {
                var pastPositions = [];
                $(pastPos[1]).find('li').each(function (k, elem) {
                    pastPositions.push(cleanStr(escapeHtml($(this).html())));
                });
                //postedData.pastPositions = pastPositions.join('|| ');
            }


        }
    } catch (e) {
        console.log(e);
    }
    postedData.degree = escapeHtml(postedData.degree);
    postedData.url = LINKEDIN_DOMAIN_URL + postedData.url;
    //console.log(postedData);return;
    callback(postedData);
}
function escapeHtml(str) {
    if (str != "" && typeof str != 'undefined') {
        str = str.replace(/(<([^>]+)>)/ig, " ");
    }
    return str.replace(/\s\s+/g, ' ')
            .replace(/&amp;/g, '&')
            .replace(/&quot;/g, '"')
            .replace(/&#39;/g, "'")
            .replace(/&lt;/g, '<')
            .replace(/&gt;/g, '>');
}


/* Start Scanning*/
function startProcess(sendResponse) {
    PROCESS_ACTIVE = true;
    START_FROM = 0;
    LIMIT = SCAN_LIMIT;
    MODE = 'scan';
    chrome.storage.local.set({isInProgress: {status: PROCESS_ACTIVE}});
    chrome.storage.local.get('profile_data', function (d) {
        START_FROM = d.profile_data.dayWiseVisit.scannedProfile;
        startScanning(0);
    });

}
/* Stop Scanning*/
function stopProcess(sendResponse) {
    $('li.search-result.search-result__occluded-item').removeClass('linkedinActiveList');
    $(".search-results .search-results__result-list li.search-results__result-item").removeClass('linkedinActiveList');
    $("#search-results li.search-result").removeClass('linkedinActiveList');
    PROCESS_ACTIVE = false;
    setTimeout(function () {
        $('#toaster-container').html("");
    }, 1000);
    setTimeout(function () {
        var tstr = $.toast().warning('Process has stopped!', 'Stop');
    }, 1200);


    $('#iframe').hide();
    chrome.storage.local.set({isInProgress: {status: false}});
    chrome.storage.local.get('profile_data', function (d) {
        sendResponse(d.profile_data);
    });
}

var TOOL = {
    setTrigger: function (elem, callback) {
        var t = elem;//$(elem);
        window.setTimeout(function () {
            t.focus(), t.val("First Name"), t.focus();
            var _ = new Event("input", {
                bubbles: !0,
                cancelable: !0
            });
            t[0].dispatchEvent(_);
            var keyboardEvent = document.createEvent("KeyboardEvent");
            var initMethod = typeof keyboardEvent.initKeyboardEvent !== 'undefined' ? "initKeyboardEvent" : "initKeyEvent";
            var e = jQuery.Event("keydown");
            e.which = 77; // m code value
            e.altKey = true; // Alt key pressed
            t.trigger(e).blur().focus();
            callback();
        });
    },
    sendMessage: function (scrapeInfo, duplicateTemplate, html) {
        setTimeout(function () {
            html.find('button.pv-s-profile-actions--message').click();
            html.find('.msg-form__placeholder').removeClass('visible').addClass('hidden');

            setTimeout(function () {
                html.find('.msg-form__contenteditable, .msg-overlay-conversation-bubble--is-active').attr('data-artdeco-is-focused', true);
                html.find('.msg-form__footer button.msg-form__send-button').removeAttr('disabled');
                duplicateTemplate = duplicateTemplate.replace('%name%', scrapeInfo.full_name);
                html.find('.msg-form__message-texteditor .msg-form__contenteditable p').html(duplicateTemplate);
                TOOL.setTrigger(html.find('.msg-form__contenteditable'), function () {
                    html.find('.msg-form__footer button.msg-form__send-button').click();
                });
            }, 1000);
        }, 2000);
    },
    sendEndorsement: function (number, elements) {
        if (elements.length) {
            var count = 0;
            elements.each(function () {
                var type = $(this).find('button.pv-skill-entity__featured-endorse-button-shared li-icon').attr('type');
                if (type == 'plus-icon') {
                    $(this).find('button.pv-skill-entity__featured-endorse-button-shared').click();
                }
                count++;
                if (count == number)
                    return false;
            });
        }
    }
};
/* Profile Preview on a iframe tag*/
function profilePreview(url, scrapeInfo, callback) {
    $('#iframe').remove();
    $('<iframe />', {id: 'iframe', isds: true, scrolling: 'no', class: 'profile_frame'}).appendTo($("body"));
    $("#iframe").load(function ()
    {
        try {


            setTimeout(function () {
                if (scrapeInfo.degree && scrapeInfo.degree.indexOf('1') != -1) {
                    chrome.storage.local.get('profile_data', function (d) {
                        if (d.profile_data) {
                            if (!d.profile_data.messageTemplate.skipSend) {
                                var template = d.profile_data.messageTemplate.template;
                                TOOL.sendMessage(scrapeInfo, template, $("#iframe").contents().children());
                            }
                        }
                    });
                }
                $("#iframe").contents().children().animate({scrollTop: 7500}, 4000).promise().then(function () {
                    $("#iframe").contents().children().find('.pv-profile-section__card-action-bar.pv-skills-section__additional-skills').click();
                    $("#iframe").contents().children().find('.profile-section__expansion-button').click();


                    var seeMore = $("#iframe").contents().children().find('.pv-profile-section__see-more-inline');
                    seeMore.each(function () {
                        if ($(this).attr('data-control-name')) {
                        } else {
                            seeMore.click();
                        }
                    });
                    if (scrapeInfo.degree && scrapeInfo.degree.indexOf('1') != -1) {
                        chrome.storage.local.get('profile_data', function (d) {
                            if (d.profile_data) {
                                if (!d.profile_data.endorseSetting.skipEndorse) {
                                    var noOfEndorse = d.profile_data.endorseSetting.noOfEndorse;
                                    TOOL.sendEndorsement(noOfEndorse, $("#iframe").contents().children().find('.pv-skill-endorsedSkill-entity'));
                                }
                            }
                        });
                    }
                    $("#iframe").contents().children().animate({scrollTop: 0}, 2000).promise().then(function () {
                        if (typeof $('#iframe')[0] == 'undefined') {
                            callback("<html><body></body></html>");
                        } else {
                            if (PROCESS_ACTIVE) {
                                callback($('#iframe')[0].contentDocument.getElementsByTagName('html')[0].outerHTML);
                            } else {
                                callback("<html><body></body></html>");
                            }

                        }
                    });
                });
            }, 2000);
        } catch (e) {
            console.log(e)
            callback("<html><body></body></html>");
        }
    });

    $("#iframe").attr("src", url).find('head');
}
// find the page number from the last history
function gotoTargetPage(targetPage, callback) {
    var RANDOM_TIMER = randomInRange(2, 4);
    setTimeout(function () {
        scrollDown("", function () {
            var paging = $('.page-list li');
            var firstElem = $(paging).first();
            var lastElem = $(paging).last();
            var firstNumber, lastNumber;
            firstNumber = firstElem.hasClass('active') ? firstElem.html() : firstElem.find('button').html();
            lastNumber = lastElem.hasClass('active') ? lastElem.html() : lastElem.find('button').html();
            if (targetPage >= firstNumber && targetPage <= lastNumber) {
                paging.each(function () {
                    if ($(this).hasClass('active') && $(this).html() == targetPage) {
                        callback();
                        return false;
                    } else {
                        var btn = $(this).find('button');
                        if (btn.html() == targetPage) {
                            btn.click();
                            callback();
                            return false;
                        }
                    }
                });
            } else if (targetPage < firstNumber) {
                paging.first().find('button').click();
                gotoTargetPage(targetPage, callback);
            } else {
                paging.last().find('button').click();
                gotoTargetPage(targetPage, callback);
            }
        });
    }, RANDOM_TIMER);
}
/* Historical Search */
function startHistoricalSearch(cmd, callback) {
    chrome.storage.local.set({pageReload: true});
    window.location.href = cmd.history.pageUrl;
}


function checkUserSettings(settings, userInfo, callback) {
    var obj = {};
    obj.hasCondition = false;
    //console.log(userInfo.image_url);
    if (settings.skipNoImage) {
        if (userInfo.image_url == "" || userInfo.image_url == undefined || userInfo.image_url.indexOf('https://static.licdn.com/sc/h/7st6vm28lv5le4b37jrjaw2n3') > -1 || !(userInfo.image_url.indexOf('http') > -1)) {
            obj.msg = " has no profile picture!";
            obj.hasCondition = true;
            callback(obj);
            return false;
        }
    }
    if (settings.skip2ndConn) {
        if (userInfo.degree != "" && (userInfo.degree.indexOf('2') > -1)) {
            obj.msg = " has 2nd Degree Connection!";
            obj.hasCondition = true;
            callback(obj);
            return false;
        }
    }
    if (settings.skip1stConn) {
        if (userInfo.degree != "" && (userInfo.degree.indexOf('1') > -1)) {
            obj.msg = " has 1st Degree Connection!";
            obj.hasCondition = true;
            callback(obj);
            return false;
        }
    }
    if (settings.skip3rdConn) {
        if (userInfo.degree != "" && userInfo.degree != undefined && (userInfo.degree.indexOf('3') > -1)) {
            obj.msg = " has 3rd Degree Connection!";
            obj.hasCondition = true;
            callback(obj);
            return false;
        }
    }
    if (settings.skipOutOfNetwork) {
        if ($.inArray(userInfo.degree, LIST_OUT_OF_NETWORK) > -1) {
            obj.msg = " is out of network";
            obj.hasCondition = true;
            callback(obj);
            return false;
        }
    }
    callback(obj);

}

/* Checks is user already fetched*/
function isUserAlreadyScanned(scrapProfile, callback) {
    chrome.storage.local.get('profile_data', function (d) {
        if (d.profile_data) {
            checkUserSettings(d.profile_data.settings, scrapProfile, function (checks) {
                if (checks.hasCondition) {
                    callback(false, checks);
                } else {
                    var data = {
                        scrapMode: MODE,
                        linkedinId: scrapProfile.linkedinId
                    };
                    chrome.runtime.sendMessage({cmd: 'checkIfAlreadyExists', ajaxData: data}, function (response) {

                        if (response.notFound) {
                            scrapProfile.lastStatus = response.lastStatus;
                            callback(true, {msg: 'not_' + MODE});
                        } else {
                            callback(false, {msg: MODE == 'visit' ? ' already visited!' : 'already scanned!'});
                        }
                    });
                }
            });
        }
    });
}
function getUser(callback) {
    chrome.storage.local.get('user_id', function (d) {
        if (d.user_id) {
            callback(d.user_id);
        } else {
            callback(false);
        }
    });
}
/* Update user data priodically*/
function updateHistory(data, callback) {
    getUser(function (user_id) {
        if (user_id) {
            chrome.runtime.sendMessage({
                cmd: 'callAPI',
                ajaxData:
                        {
                            url: webservice.updateHistory + "/" + user_id,
                            data: data
                        }
            }, function (response) {
                callback(response);
            });
        }
    });

}
/* Store Scrab profile in to database*/
function storeScrapProfile(scrapUserInfo, callback) {
    getUser(function (user_id) {
        chrome.runtime.sendMessage({
            cmd: 'callAPI',
            ajaxData:
                    {
                        url: webservice.addUserProfile + "/" + user_id,
                        data: {scrapUserInfo: scrapUserInfo}
                    }
        }, function (response) {
            if (response.status == 200) {
                var user = {};
                user.status = true;
                user.info = response.data;
                chrome.storage.local.set({'user_details': user});
            }
            callback(response);
        });
    });

}
/* Move to next Scan*/
function startNextScan(index, RANDOM_TIMER) {

    if (PROCESS_ACTIVE) {
        var seconds = RANDOM_TIMER / 1000;
        var tstr = $.toast().info("Pausing for... " + seconds, 'Pause', {timeOut: RANDOM_TIMER});
        tstr = tstr.getObject();
        var intrl = setInterval(function () {
            seconds--;
            tstr.find('.toaster-message').html("Pausing for... " + seconds);
            if (seconds <= 0 || !PROCESS_ACTIVE) {
                tstr.remove();
                clearInterval(intrl);
            }
        }, 1000);

        setTimeout(function () {
            startScanning(index);
        }, RANDOM_TIMER);
    }
}

function rotate(clone) {
    var degree = 0;
    setInterval(function () {
        clone.css({WebkitTransform: 'scale(0.85,0.75) rotate(' + degree + 'deg)'});
        clone.css({'-moz-transform': 'scale(0.85,0.75) rotate(' + degree + 'deg)'});
        degree += RATOTE_SPEED;
    }, 10);

}
/* Move profile information using animate*/
function moveAnimate(target) {
    setTimeout(function () {
        var clone = target.clone();
        clone.css({
            position: "absolute",
            'z-index': '99999',
            transform: 'scale(0.9,0.8)',
            'border-top': 'none',
            'width': '20%',
            'height': '20%',
            'overflow': 'hidden'
        });
        clone.removeClass('linkedinActiveList');
        if (pageData.account_type == 'Recruiter') {
            clone.css({
                transform: 'scale(0.8,0.7)',
            });
            clone.attr('id', 'search-results');
        }
        clone.offset(target.offset());
        $("body").append(clone);
        rotate(clone);

        clone.animate({'top': target.offset().top - 150, 'left': target.width() + 300}, FLYING_SPEED, function () {
            $(this).remove();
        });
    }, 100);
}



/* Scanning Start from here*/
function startScanning(index) {

    if (!PROCESS_ACTIVE) {
        $('li.search-result.search-result__occluded-item').removeClass('linkedinActiveList');
        return;
    }
    var RANDOM_TIMER = 1000;
    var pageNo = getUrlParameter('page');
    HISTORICAL_PAGE = pageNo ? pageNo : 0;
    if (MODE == 'scan')
        RANDOM_TIMER = randomInRange(MIN_TIME_TO_NEXT, MAX_TIME_TO_NEXT);
    else if (MODE == 'visit')
        RANDOM_TIMER = randomInRange(VISIT_MIN_TIME_TO_NEXT, VISIT_MAX_TIME_TO_NEXT);
    $('.search-results__total').append('<div />');
    var cont_div = $("li.search-result");
    if (pageData.account_type == 'Sales') {
        cont_div = $("#results-list > li");
    } else if (pageData.account_type == 'Recruiter') {
        cont_div = $("#search-results > li.search-result");
    }
    if (cont_div.length == 0) {
        cont_div = $(".search-results__list > li");
    }
    if (cont_div.length == 0 && pageData.account_type == 'Sales') {
        cont_div = $(".search-results .search-results__result-list li.search-results__result-item");
    }
    if (cont_div.length == 0) {
        showNotification({
            type: "error",
            'msg': "Start your query by customizing your search criteria here!",
            title: "List Not Found!"
        });
    }
    if (index < cont_div.length) {
        if ($(cont_div[index]).find('.search-paywall').length || $(cont_div[index]).find('.search-result__profile-blur').length) {
            startScanning(++index);
        } else {
            cont_div.removeClass('linkedinActiveList');
            $(cont_div[index]).addClass('linkedinActiveList');

            var cont_li = $(cont_div[index]).attr('id');
            if (typeof cont_li == 'undefined') {
                var id = randomInRange(100000000, 9999999999999);
                $(cont_div[index]).attr('id', id);
                cont_li = id;
            }
            scrollDown('#' + cont_li);
            if (START_FROM >= LIMIT) {
                PROCESS_ACTIVE = false;
                cont_div.removeClass('linkedinActiveList');
                chrome.storage.local.set({isInProgress: {status: PROCESS_ACTIVE}});
                showNotification({msg: "Daily limit quota consumed.", type: 'error', title: "Sorry!"});
                setTimeout(function () {
                    $(cont_div).removeClass('linkedinActiveList');
                    $('#iframe').remove();
                }, 1000);

            } else {
                try {
                    if (PROCESS_ACTIVE) {
                        getUserInformation($(cont_div[index]).html(), function (scrapUserInfo) {
                            if (!scrapUserInfo) {
                                startNextScan(++index, SKIPPED_TIMEOUT);
                                return false;
                            }


                            isUserAlreadyScanned(scrapUserInfo, function (hasNoConfict, reason) {
                                var toastMsg = {msg: "Scanning to", title: "Scaning"};
                                if (MODE == 'visit') {
                                    toastMsg.msg = "Visiting to";
                                    toastMsg.title = "Visiting";
                                }
                                if (hasNoConfict) {
                                    var currentProfileToastr = {};
                                    currentProfileToastr = $.toast();
                                    if (MODE == 'visit') {
                                        $.toast().success("Profile Got...", "Found", {timeOut: 2000, onHide: function () {
                                                currentProfileToastr.success(toastMsg.msg + " " + scrapUserInfo.full_name + "...", toastMsg.title, {static: true});
                                            }});
                                    } else
                                        currentProfileToastr.success(toastMsg.msg + " " + scrapUserInfo.full_name + "...", toastMsg.title, {static: false, timeOut: RANDOM_TIMER - 500});

                                    if (MODE == 'visit') {
                                        var url = "";
                                        if (pageData.account_type == 'Basic') {
                                            url = LINKEDIN_PROFILE_PAGE + scrapUserInfo.linkedinId;
                                            //moveAnimate($(cont_div[index]).find('.search-result__info'));
                                        } else if (pageData.account_type == 'Sales') {
                                            url = SALES_PROFILE_LINK + scrapUserInfo.linkedinId + ",";
                                            //moveAnimate($(cont_div[index]).find('.result-lockup__entity'));
                                        } else if (pageData.account_type == 'Recruiter') {
                                            url = RECRUITER_PROFILE_LINK + scrapUserInfo.url.replace(LINKEDIN_DOMAIN_URL + "recruiter/profile/", "");
                                            //moveAnimate($(cont_div[index]));
                                        }

                                        profilePreview(url, scrapUserInfo, function (data) {

                                            var body = /<body.*?>([\s\S]*?)<\/body>/i.exec(data);
                                            var scrapData = getFetchData(body[0]);

                                            scrapUserInfo.also_viewed = scrapData.also_viewed
                                            scrapUserInfo.recommendations = scrapData.recommendations;
                                            scrapUserInfo.num_connections = scrapData.num_connections;
                                            scrapUserInfo.skills = scrapData.skills;
                                            scrapUserInfo.experience = scrapData.experience;
                                            if (pageData.account_type !== 'Recruiter') {
                                                if(currentProfileToastr)
                                                currentProfileToastr.remove();
                                                visitProfile(scrapUserInfo.linkedinId, function (attr) {
                                                    scrapUserInfo = $.extend(scrapUserInfo, attr);
                                                    if (pageData.account_type == 'Basic') {
                                                        scrapUserInfo.entityUrn = attr.entityUrn;
                                                    }
                                                    storeFetchedData(scrapUserInfo, index, function (resp) {
                                                        
                                                        startNextScan(++index, RANDOM_TIMER);
                                                    });
                                                });
                                            } else {
                                                //console.log(data);
                                                var ul = /<ul class="topcard-footer-actions">(.*?)<\/ul>/gi.exec(data);
                                                var href = $(ul[0]).children('.public-profile').html();
                                                if ($(href).attr('href')) {
                                                    var ids = $(href).attr('href').split('/');
                                                    var uid = ids[ids.length - 1];
                                                    visitProfile(uid, function (attr) {
                                                        scrapUserInfo = $.extend(scrapUserInfo, attr);
                                                        if (pageData.account_type == 'Basic') {
                                                            scrapUserInfo.entityUrn = attr.entityUrn;
                                                        }
                                                        storeFetchedData(scrapUserInfo, index, function (resp) {
                                                            currentProfileToastr.remove();
                                                            startNextScan(++index, RANDOM_TIMER);
                                                        });
                                                    });
                                                } else {
                                                    storeFetchedData(scrapUserInfo, index, function (resp) {
                                                        currentProfileToastr.remove();
                                                        startNextScan(++index, RANDOM_TIMER);
                                                    });
                                                }
                                            }

                                        });

                                    } else {
                                        if (pageData.account_type == "Basic")
                                            moveAnimate($(cont_div[index]).find('.search-result__info'));
                                        else if (pageData.account_type == "Sales")
                                            moveAnimate($(cont_div[index]).find('.result-lockup__entity'));
                                        else if (pageData.account_type == 'Recruiter') {
                                            moveAnimate($(cont_div[index]));
                                        }
                                        storeFetchedData(scrapUserInfo, index, function (resp) {
                                            //currentProfileToastr.remove();
                                            startNextScan(++index, RANDOM_TIMER);
                                        });
                                    }
                                } else {
                                    //console.log(reason);
                                    $.toast().warning(escapeHtml(scrapUserInfo.full_name) + " " + reason.msg, 'Skipped', {timeOut: SKIPPED_TIMEOUT - 500});
                                    startNextScan(++index, SKIPPED_TIMEOUT);
                                }
                            });
                        });
                    }
                } catch (e) {
                    console.log(e);
                    startNextScan(++index, SKIPPED_TIMEOUT);
                }
            }
        }
        //profilePreview(LINKEDIN_PROFILE_PAGE+info.linkedinId,function(data){});
    } else {
        gotoNextPage();
    }
}
/* Store information on server*/
function storeFetchedData(scrapUserInfo, index, callback) {
    updateHistory({lastOperation: MODE, pageUrl: location.href, lastPage: HISTORICAL_PAGE, profileIndex: index}, function (res) {

    });
    storeScrapProfile(JSON.stringify(scrapUserInfo), function (storeResponse) {
        if (storeResponse.status == 200) {
            START_FROM++;
            chrome.storage.local.get('Profiles', function (collection) {
                if (typeof collection.Profiles == 'undefined') {
                    chrome.storage.local.set({'Profiles': []});
                }
                //scrapUserInfo._id = (storeResponse.data.userProfiles[storeResponse.data.userProfiles.length - 1])._id;
                collection.Profiles.push({lastStatus: scrapUserInfo.lastStatus, linkedinId: scrapUserInfo.linkedinId, scrapMode: scrapUserInfo.scrapMode});
                chrome.storage.local.set({'Profiles': collection.Profiles});
            });
        }
        callback(storeResponse);
    });
}

/* Goto next page on linkedin*/
function gotoNextPage() {

    var found = false;
    setTimeout(function () {
        if ($(".next,.artdeco-pagination__button--next").length > 0) {
            if (pageData.account_type == 'Recruiter') {
                found = true;
                $(".page-link li-icon").trigger('click');
            } else {
                if (!$(".next,.artdeco-pagination__button--next").is(':disabled')) {
                    $(".next,.artdeco-pagination__button--next").trigger('click');
                    found = true;
                }
            }
        } else if ($(".next a").length > 0) {
            $(".next a").trigger('click');
            alert(3);
            found = true;
        } else if (pageData.account_type == 'Sales' && $(".next-pagination").length > 0) {
            $(".next-pagination")[0].click();
            found = true;
        } else if (pageData.account_type == 'Sales' && $(".search-results__pagination-next-button").length > 0) {

            if (!$(".search-results__pagination-next-button").prop('disabled')) {
                $(".search-results__pagination-next-button").click();
                found = true;
            }
        }
        if (found) {
            setTimeout(function () {
                startScanning(0);
            }, 3000);
        } else
            stopProcess(function () {});
    }, 2000);
}
/* Scroll Body to down*/
function scrollDown(div, callback) {
    var scrollValue = typeof div == 'undefined' || div == "" ? 3500 : (pageData.account_type == 'Basic' ? $(div).offset().top - 100 : $(div).offset().top - 150);
    $('html,body').animate({scrollTop: scrollValue}, 800).promise().then(callback);
}

function randIn(min, max) {
    if (max == null) {
        max = min;
        min = 0;
    }
    return (min + Math.floor(Math.random() * (max - min + 1)));
}
function randomInRange(min, max) {
    return randIn(min, max) * 1000;
}
/* Find Page Number from URL*/
function getUrlParameter(name) {
    name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
    var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
    var results = regex.exec(location.search);
    return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
}

/* Call Download CSV*/
function downloadCSV() {
    chrome.runtime.sendMessage({cmd: 'downloadCSVFile'}, function (response) {

    });
}
/* Hit Linkedin API */
function callXHROnLinkedIn(url, headers, callback, is_async) {
    var async = !is_async ? true : false;
    $.ajax({
        url: url,
        async: false,
        beforeSend: function (req) {
            var csrf_token = document.cookie.match(JSESSIONID_REGEX)[1];
            req.setRequestHeader('csrf-token', csrf_token);
            if (headers && headers.length > 0) {
                headers.forEach(function (h) {
                    req.setRequestHeader(h.key, h.val);
                });
            }
        },
        xhrFields: {
            withCredentials: true
        },
        type: 'GET',
        success: function (data) {
            if (typeof callback == 'function')
                callback(data);
        },
        error: function (xhr) {
            // console.log("XHR Failed for "+url);
            if (typeof callback == 'function')
                callback();
        }
    });
}

function visitProfile(entityUrn, callback) {
    if (entityUrn) {
        var attrs = {
            experience: [],
            publications: [],

        };
        callXHROnLinkedIn(LINKEDIN_DOMAIN_URL + 'voyager/api/identity/profiles/' + entityUrn + '/profileView', [], function (resp) {
            if (resp && resp.profile) {
                var profile = resp.profile;
                attrs.firstName = profile.firstName;
                attrs.lastName = profile.lastName;
                attrs.full_name = attrs.firstName + " " + attrs.lastName;
                attrs.entityUrn = profile.entityUrn.replace(/urn:li:fs_profile:/, '');
                attrs.headline = profile.headline;
                attrs.industry = profile.industryName;
                if (profile.miniProfile.picture) {
                    var vectorImg = profile.miniProfile.picture['com.linkedin.common.VectorImage'];
                    if (vectorImg.artifacts && vectorImg.artifacts.length > 0) {
                        attrs.image_url = vectorImg['rootUrl'] + '' + vectorImg.artifacts.splice(-1)[0].fileIdentifyingUrlPathSegment;
                    }
                }
                attrs.locality = profile.locationName;
                if (profile.location.basicLocation.countryCode)
                    attrs.country_code = profile.location.basicLocation.countryCode;
                if (resp.publicationView.elements.length) {
                    $(resp.publicationView.elements).each(function (k, v) {
                        var info = {authors: []};
                        info.url = v.url;
                        info.title = v.name;
                        info.publisher = v.publisher;
                        info.summary = "";
                        if (v.authors.length) {
                            for (var i = 0; i < v.authors.length; i++) {
                                var authorInfo = {};
                                if (typeof v.authors[i].member !== 'undefined') {
                                    if (typeof v.authors[i].member.url !== 'undefined')
                                        authorInfo.url = v.authors[i].member.url;
                                    if (typeof v.authors[i].member.firstName !== 'undefined')
                                        authorInfo.full_name = v.authors[i].member.firstName;
                                    if (typeof v.authors[i].member.lastName !== 'undefined')
                                        authorInfo.full_name += " " + v.authors[i].member.lastName;
                                }
                                info.authors.push(authorInfo);
                            }
                        }
                        attrs.publications.push(info);
                    });
                }
                if (resp.positionView.elements.length) {
                    $(resp.positionView.elements).each(function (k, v) {
                        var start = "", end = "";
                        if (typeof v.timePeriod != 'undefined') {
                            if (typeof v.timePeriod.startDate !== 'undefined') {
                                if (typeof v.timePeriod.startDate.month !== 'undefined') {
                                    start = v.timePeriod.startDate.month;
                                    if (typeof v.timePeriod.startDate.year !== 'undefined') {
                                        start += "-" + v.timePeriod.startDate.year;
                                    }
                                }
                            }

                            if (typeof v.timePeriod.endDate !== 'undefined') {
                                if (typeof v.timePeriod.endDate.month !== 'undefined') {
                                    end = v.timePeriod.endDate.month;
                                    if (typeof v.timePeriod.endDate.year !== 'undefined') {
                                        end += "-" + v.timePeriod.endDate.year;
                                    }
                                }
                            }

                        }

                        attrs.experience.push({
                            "start": start,
                            "title": v.title,
                            "end": end,
                            company_name: v.companyName
                        });
                    });
                    callXHROnLinkedIn(LINKEDIN_DOMAIN_URL + 'voyager/api/identity/profiles/' + entityUrn + '/profileContactInfo', [], function (res) {
                        if (res && res.emailAddress) {
                            attrs.primary_email = res.emailAddress || "";
                        }
                        if (res && res.phoneNumbers) {
                            attrs.phone_number = res.phoneNumbers.map(x => x.number).toString() || "";
                        }
                        var companyCode = resp.positionView.elements[0];
                        if (companyCode.companyUrn) {
                            companyCode = companyCode.companyUrn.replace('urn:li:fs_miniCompany:', '');
                            callXHROnLinkedIn(COMPANY_PROFILE_LINK + companyCode, [], function (resp) {
                                if (resp) {
                                    attrs.current_company_website = resp.companyPageUrl;
                                    attrs.company_linkedin_url = resp.url;
                                    attrs.current_company_specialties = (resp.specialities).join(", ");
                                    attrs.current_company_size = resp.staffCount + " employees";
                                    attrs.current_company_name = resp.name;
                                    attrs.current_company_industry = (resp.industries && resp.industries.length) ? (resp.industries).join(', ') : "";
                                }
                                callback(attrs);
                                return;

                            }, true);
                        } else {
                            callback(attrs);
                            return;
                        }
                    }, true);
                } else
                    callback(attrs);
                return;
            } else {

            }
        }, true);
    } else
        callback(false);
}

window.onbeforeunload = function (e) {
    if (PROCESS_ACTIVE)
        return true;
}