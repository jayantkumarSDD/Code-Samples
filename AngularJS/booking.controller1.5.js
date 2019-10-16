(function () {
    'use strict';
    angular
        .module('la-app')
        .controller('BookLessonInstanceController', BookLessonInstanceController);
    BookLessonInstanceController.$inject = ['CommonService', '$uibModalInstance', '$timeout', 'uiCalendarConfig', 'lessonData', 'SearchService', '$localStorage', 'toastr', '$state', 'TeacherService', '$uibModal', 'NgMap', 'Geocoder', 'StripeService', 'googleConstants', 'SocialService'];

    // Please note that $uibModalInstance represents a modal window (instance) dependency.
    function BookLessonInstanceController(CommonService, $uibModalInstance, $timeout, uiCalendarConfig, lessonData, SearchService, $localStorage, toastr, $state, TeacherService, $uibModal, NgMap, Geocoder, StripeService, googleConstants, SocialService) {
        var $ctrl = this;
        $ctrl.init = init;
        $ctrl.close = close;
        $ctrl.wizarddetail = lessonData;
        $ctrl.checkInterview = false;
        $ctrl.checkPackage = true;
        $ctrl.saveBookLessonInfo = saveBookLessonInfo;
        $ctrl.validateBooking = validateBooking;
        $ctrl.beforeRender = beforeRender;
        $ctrl.onTimeSet = onTimeSet;
        //$ctrl.timerange = [];//timerange;
        $ctrl.saveInterviewInfo = saveInterviewInfo;
        $ctrl.packages = [];
        $ctrl.getPackageDetail = getPackageDetail;
        $ctrl.lessonRange = lessonRange;
        $ctrl.stripeFee = 0.00;
        $ctrl.getLessonPackages = getLessonPackages;
        $ctrl.changeWeek = changeWeek;
        $ctrl.changeMonth = changeMonth;
        $ctrl.currentMonth = moment().format("MMM");
        $ctrl.currentWeek = moment().startOf('week').format("LL");
        $ctrl.getMonthsInYear = getMonthsInYear();
        $ctrl.monthlyWeeks = getWeeksInMonth(moment().year(), moment().month());
        $ctrl.currentMonthNo = _.filter($ctrl.monthList, { desc: $ctrl.currentMonth })[0].monthIndex;
        $ctrl.duration = lessonData.level.duration;
        $ctrl.minDuration = 1;
        $ctrl.subjectType = lessonData.type;
        $ctrl.resetInterview = resetInterview;
        $ctrl.resetBooking = resetBooking;
        $ctrl.timebuffer = getTimeBuffer();
        $ctrl.policy = {};
        $ctrl.validateInterview = validateInterview;
        $ctrl.placeChanged = placeChanged;
        $ctrl.geocode = geocode;
        $ctrl.removeLesson = removeLesson;

        // Plugins configuration

        // Google Maps Configuration
        //$ctrl.googleMapsUrl = 'https://maps.google.com/maps/api/js?libraries=places';
        $ctrl.googleMapsUrl = 'https://maps.google.com/maps/api/js?libraries=places&key=' + googleConstants.MAP_API_KEY;

        if (_.isUndefined($localStorage.currentUser)) {
            $ctrl.center = "1.280094, 103.850949";
        } else {
            if (_.isUndefined($localStorage.currentUser.location)) {
                $ctrl.center = "1.280094, 103.850949";
                if (!_.isUndefined($localStorage.lastLocation) && !_.isNull($localStorage.lastLocation)) {
                    $ctrl.center = $localStorage.lastLocation.center;
                    $ctrl.name = $localStorage.lastLocation.name;
                }
            } else {
                if (!_.isUndefined($localStorage.lastLocation) && !_.isNull($localStorage.lastLocation)) {
                    $ctrl.center = $localStorage.lastLocation.center;
                    $ctrl.name = $localStorage.lastLocation.name;
                } else {
                    var defaultlocation = $localStorage.currentUser.location;
                    if (defaultlocation.lat && defaultlocation.lng) {
                        $ctrl.center = defaultlocation.lat + ',' + defaultlocation.lng;
                        $ctrl.name = defaultlocation.name;
                        $ctrl.positions = [{ lat: defaultlocation.lat, lng: defaultlocation.lng }];
                    } else {
                        $ctrl.center = "1.280094, 103.850949";
                        if (!_.isUndefined($localStorage.lastLocation) && !_.isNull($localStorage.lastLocation)) {
                            $ctrl.center = $localStorage.lastLocation.center;
                            $ctrl.name = $localStorage.lastLocation.name;
                        }
                    }
                    $localStorage.lastLocation = { center: $ctrl.center, name: $ctrl.name };
                }
            }
        }
        $ctrl.oldCenter = $ctrl.center;
        $ctrl.oldName = $ctrl.name;
        var Week = _.filter($ctrl.monthlyWeeks, { weekStart: $ctrl.currentWeek });
        if (Week.length > 0) {
            $ctrl.currentWeekNo = _.filter($ctrl.monthlyWeeks, { weekStart: $ctrl.currentWeek })[0].weekIndex;
        } else {
            $ctrl.currentWeekNo = 0;
        }

        /* config calendar */
        $ctrl.uiConfig = {
            availableCalendar: {
                height: 450,
                editable: true,
                selectable: true,
                header: {
                    left: '',
                    center: '',
                    right: ''
                },
                dayOfMonthFormat: 'ddd DD/MM',
                scrollTime: '07:00:00',
                eventClick: eventOnClick,
                select: selectEvent,
                viewRender: changeView,
                defaultView: 'agendaWeek',
                loading: loading,
                nowIndicator: true,
                slotEventOverlap: false,
                eventOverlap: false
            }
        };
        $ctrl.lessonPopover = { templateUrl: 'lessonTemplate.html', placement: 'right' };
        // calendar events
        $ctrl.events = [];
        $ctrl.interviewinfo = [];
        $ctrl.lessoninfo = [];
        $ctrl.backgroundEvents = [];

        // Tutor's Availability
        $ctrl.businessEvents = {
            url: webservices.getbusinesshours + '/' + $ctrl.wizarddetail.tutorId,
            type: 'GET',
            data: { type: $ctrl.subjectType },
            success: function (result) {
                if (result.status === 200) {
                    _.forEach(result.data, function (v) {
                        $ctrl.backgroundEvents.push(_.extend({}, v, { rendering: 'background', color: '#acacac' }));
                    });
                    return $ctrl.backgroundEvents;
                } else {
                    toastr.warning('Unable to fetch events');
                }
            },
            error: function () {
                console.log('There was an error while fetching events!');
            }
        };

        // Tutor's Weekly Booked Slot
        $ctrl.bookinginfo = {
            url: webservices.gettutornonavailability + '/' + $ctrl.wizarddetail.tutorId,
            type: 'GET',
            data: {},
            success: function (result) {
                if (result.status === 200) {
                    var scheduleEvents = [];
                    _.forEach(result.data, function (o) {
                        scheduleEvents.push(_.extend({}, o, { 'start': moment(o.start).local().format(), 'end': moment(o.end).local().format(), 'editable': false, 'title': 'Busy' }));
                    });
                    $ctrl.scheduleEvents = angular.copy(scheduleEvents);
                    return scheduleEvents;
                } else {
                    toastr.warning('Unable to fetch events');
                }
            },
            error: function () {
                console.log('There was an error while fetching events!');
            }
        };

        $ctrl.businessSources = [$ctrl.businessEvents, $ctrl.bookinginfo];
        $ctrl.lessondesc = angular.copy($ctrl.lessoninfo);
        $ctrl.lessontime = [];
        $ctrl.totalLesson = 0;
        $ctrl.lessonLength = 0;
        $ctrl.totaltime = 0;
        // selectize
        init();

        /*
         * Initialisation
         */
        function init() {
            getStripeDetails();
            getBookLessonInfo();
            getLessonPackages();
            getTutorPoliciesInfo();
            geocode();
        }
        /*
         * @desc - Close
         */
        function close() {
            $uibModalInstance.close();
        }

        /*
         * @description - get lesson packages for booking
         */
        function getLessonPackages() {
            SearchService.getLessonPackages(function (result) {
                $ctrl.packages = result.data;
                var $package = $.grep($ctrl.packages, function (elem, i) { return elem.number_lesson == 4; });
                $package = $package.length ? $package[0] : $ctrl.packages[0];
                $ctrl.initialPackage = $package;
                //new changes for package
                $ctrl.selectedPackage = $package._id;
                $ctrl.totalLesson = $ctrl.initialPackage.number_lesson;
            });
        }

        /*
         * @description - This function is used to change the package detail
         */
        function getPackageDetail(package_detail, index) {
            $ctrl.initialPackage = package_detail;
            $ctrl.selectedPackage = package_detail._id;
            $ctrl.totalLesson = $ctrl.initialPackage.number_lesson;
            $ctrl.lessondesc = [];
        }

        /*
         * @description - get Book lesson info
         */
        function getBookLessonInfo() {
            var details = { tutor: $ctrl.wizarddetail.tutorId, subject: $ctrl.wizarddetail.subject.subjectId._id, level: $ctrl.wizarddetail.level.levelId._id };
            SearchService.getBookLessonInfo(details, function (result) {
                if (result.status === 200) {
                    $ctrl.wizards = result.data;
                    renderCalender('availableCalendar');
                } else {
                    console.log("errr" + result);
                }
            });
        }

        /*
         * get reschedule and cancellation policy of tutor
         */
        function getTutorPoliciesInfo() {
            var details = $ctrl.wizarddetail.tutorId;
            SearchService.getTutorPoliciesInfo(details, function (result) {
                if (result.status === 200) {
                    var policy = result.data;
                    var reschedule = {
                        refundPolicy: policy.policy_slug,
                        refundDuration: policy.reschedule.duration,
                        refundPenalty: policy.reschedule.penalty_value
                    };

                    var cancellation = {
                        refundPolicy: policy.policy_slug,
                        refundDuration: policy.cancellation.duration,
                        refundPenalty: policy.cancellation.penalty_value
                    };

                    $ctrl.policy = {
                        reschedule: reschedule,
                        cancellation: cancellation
                    };

                } else {
                    console.log("errr" + result);
                }
            });
        }


        /*
         * get time buffer for booking slot
         */
        function getTimeBuffer() {
            return (!_.isUndefined($ctrl.wizarddetail.timebuffer)) ? $ctrl.wizarddetail.timebuffer : otherConstants.la_timebuffer;
        }


        /*
         * This function return the no of lessons field as an array
         */
        function lessonRange(range) {
            return new Array(range);
        }

        /*
        * @desc - save booking lesson info
        */
        function saveBookLessonInfo(token) {
            var studentId = $localStorage.currentUser.id;
            var bookingamount = ($ctrl.totaltime * $ctrl.wizarddetail.level.rate);
            if ($ctrl.checkInterview) {
                var interviewschedule = {
                    "interviewstart": $ctrl.interviewstart,
                    "interviewend": $ctrl.interviewend,
                    "is_rescheduled": false,
                    "is_interview_done": false
                };
                var payment_info = { totalbookingamount: bookingamount, payment_status: 'Pending' };
            } else {
                var interviewschedule = {};
                var payment_info = { totalbookingamount: bookingamount, payment_status: 'Paid' };
            }

            if ($ctrl.checkPackage) {
                var bookingSlots = [];
                _.forEach($ctrl.lessondesc, function (key, index) {
                    bookingSlots.push({
                        activityname: 'Lesson ' + parseInt(index + 1),
                        title: $ctrl.lessondesc[index].title,
                        start: moment($ctrl.lessondesc[index].start).toISOString(),
                        end: moment($ctrl.lessondesc[index].end).toISOString(),
                        overlap: $ctrl.lessondesc[index].overlap,
                        allDay: $ctrl.lessondesc[index].allDay,
                        type: 'lesson',
                        lesson_duration: $ctrl.lessondesc[index].lessonduration,
                        lesson_price: $ctrl.lessondesc[index].lessonprice
                    });
                });
                var bookinginfo = bookingSlots;
            } else {
                var bookinginfo = {};
            }

            var details = {
                tutor_id: $ctrl.wizarddetail.tutorId,
                student_id: studentId,
                subject_id: $ctrl.wizarddetail.subject.subjectId._id,
                subject_type: $ctrl.wizarddetail.subject.subjectId.type,
                level_id: $ctrl.wizarddetail.level.levelId._id,
                package_id: $ctrl.selectedPackage,
                no_of_lessons: $ctrl.totalLesson,
                is_interview_require: $ctrl.checkInterview,
                interviewschedule: interviewschedule,
                booking_info: bookinginfo,
                payment_info: payment_info,
                reschedule_policy: $ctrl.policy.reschedule,
                cancellation_policy: $ctrl.policy.cancellation,
                token: token,
                location: $ctrl.address,
                stripeFee: $ctrl.stripeFee
            };

            SearchService.saveBookLessonInfo(details, function (result) {
                if (result.status === 200) {
                    toastr.success('Booking was successful.');
                    if ($ctrl.checkInterview) {
                        var bookingId = { booking_id: result.data };
                        TeacherService.createinterviewsession(bookingId, function (result) {
                            if (result.status === 200) {
                                close();
                                //$state.go('learner.home'); 
                                $state.go('learner.teachers');
                                toastr.success('Interview Session was created successfully.');
                            }
                        });
                    } else {
                        close();
                        $state.go('learner.home');
                    }
                } else {
                    toastr.warning(result.message);
                    console.log("errr" + result);
                }
            });
        }

        function validateBooking(textfrom) {
            if ($ctrl.lessonLength < $ctrl.totalLesson) {
                toastr.warning('Please Book all lessons on grid');
                return false;
            } else if (_.isUndefined($ctrl.place) && _.isUndefined(defaultlocation)) {
                toastr.warning('Please select your prefered location for Learning');
                return false;
            } else {

                if (!_.isUndefined($ctrl.place)) {
                    if (_.isUndefined($ctrl.place.lat) && _.isUndefined($ctrl.place.lng)) {
                        toastr.warning('Please select a proper location');
                        return false;
                    } else {
                        $ctrl.address = {
                            lat: $ctrl.place.lat,
                            lng: $ctrl.place.lng,
                            name: $ctrl.name
                        };
                    }
                } else {
                    $ctrl.address = {
                        lat: defaultlocation.lat,
                        lng: defaultlocation.lng,
                        name: defaultlocation.name
                    };
                }

                if ($localStorage.currentUser && $localStorage.loginstatus) {
                    if (textfrom === 'checkout') {
                        angular.element('#checkout').triggerHandler('click');
                    } else {
                        saveBookLessonInfo();
                    }
                    return true;
                } else {
                    toastr.info('Please login as Learner to Book a Lesson');
                    modalopen('learnerlogin.html', 'md-modal', 'md', '', textfrom);
                }

            }
        }

        function validateInterview() {
            if ($ctrl.checkInterview) {
                if (_.isUndefined($ctrl.interviewDate) || _.isUndefined($ctrl.interviewTime)) {
                    toastr.warning('Please input Interview details');
                    return false;
                } else {
                    return true;
                }
            } else {
                return true;
            }
        }

        // Calendar events
        function eventOnClick() {
            console.log('clicked');
        }

        /*
         * Function is fired when a new event is to be added
         */
        function selectEvent(start, end, jsEvent, view) {
            // Check for End date being equal to Start date of next day
            if (moment(end).isSame(moment(end).startOf('day')) === true) {
                end = end.subtract(1, 'seconds');
            }
            var calendar = 'availableCalendar';
            var duration = end.diff(start, 'minutes') / 60;
            console.log(duration);
            var validity = isValidEvent(start, end, calendar);
            var current = uiCalendarConfig.calendars[calendar].fullCalendar('getDate');
            var slotBuffer = $ctrl.timebuffer; // start buffer in minutes
            var startDuration = start.diff(current, 'minutes');

            var checkInterview = isInterviewBeforeLessons(start, end, calendar);

            if (checkInterview.length > 0) {
                toastr.info('Lessons cannot be booked before an interview. Please reset your interview or bookings.');
                uiCalendarConfig.calendars[calendar].fullCalendar("unselect");
                return false;
            }

            if (validity.length === 0) {
                toastr.info('Lessons cannot be booked on UnAvailable Slots');
                uiCalendarConfig.calendars[calendar].fullCalendar("unselect");
            } else {

                if (startDuration < slotBuffer) {
                    toastr.info('Please select slot not before ' + slotBuffer / 60 + ' hours from current time.');
                    uiCalendarConfig.calendars[calendar].fullCalendar("unselect");
                    return false;
                }

                else if (duration > $ctrl.duration) {
                    toastr.info('Duration of this event cannot be greater than ' + $ctrl.duration + ' hour');
                    uiCalendarConfig.calendars[calendar].fullCalendar("unselect");
                    return false;
                }
                else if (duration < $ctrl.minDuration) {
                    toastr.info('Duration of this event cannot be less than ' + $ctrl.minDuration + ' hour');
                    uiCalendarConfig.calendars[calendar].fullCalendar("unselect");
                    return false;
                }
                else {
                    var totalLesson = $ctrl.totalLesson;
                    var lessonLength = $ctrl.lessonLength;
                    if (moment().diff(start, 'hour') > 0) {
                        toastr.error('Lessons cannot be booked for past date!');
                        uiCalendarConfig.calendars[calendar].fullCalendar("unselect");
                    } else {
                        //check if event is of all day or specified time;
                        var allDayEvent = false;
                        if (start.format().indexOf('T') === -1) {
                            allDayEvent = true;
                        }

                        var lessonprice = duration * $ctrl.wizarddetail.level.rate;
                        var eventData = {
                            id: 'lesson_' + $ctrl.lessonLength,
                            title: 'Lesson',
                            start: start.format(),
                            end: end.format(),
                            allDay: allDayEvent,
                            overlap: false,
                            stick: true,
                            editable: false,
                            type: 'lesson',
                            lessonprice: lessonprice,
                            lessonduration: duration
                        };
                        if (lessonLength < totalLesson) {
                            var start = moment(eventData.start);
                            var end = moment(eventData.end);
                            var lessontime = end.diff(start, 'minutes') / 60;

                            // Check to see if event is not added before the previous lesson
                            var checkPastEvent = [];

                            _.forEach($ctrl.lessoninfo, function (v) {
                                if (end.isBefore(v.end) || end.isSame(v.end, 'minute')) {
                                    checkPastEvent.push(true);
                                }
                            });

                            if (checkPastEvent.length === 0) {
                                uiCalendarConfig.calendars[calendar].fullCalendar('renderEvent', eventData, true);
                                $ctrl.lessoninfo.push(eventData);
                                $ctrl.lessondesc = $ctrl.lessoninfo;
                                $ctrl.lessontime.push(lessontime);
                                $ctrl.totaltime = _.sum($ctrl.lessontime);
                                toastr.success('Lesson booked successfully.');
                                $ctrl.lessonLength = $ctrl.lessoninfo.length;
                                calculateStripeFees();
                                if ($ctrl.lessonLength >= $ctrl.initialPackage.number_lesson) {
                                    return;
                                }
                                $timeout(function () {
                                    var modalInstance = $uibModal.open({
                                        animation: true,
                                        ariaLabelledBy: 'modal-title',
                                        ariaDescribedBy: 'modal-body',
                                        templateUrl: '/app/layout/dialogs/autofill-popup.html',
                                        size: 'md',
                                        controller: 'AutofillSlotController',
                                        controllerAs: '$ctrl',
                                        windowClass: 'write-rvwmodal',
                                        backdrop: 'static',
                                        resolve: {
                                            lessonData: {
                                                initialPackage: $ctrl.initialPackage,
                                                lessondesc: $ctrl.lessondesc,
                                                currentEvent: eventData,
                                                backgroundEvents: $ctrl.backgroundEvents,
                                                scheduleEvents: $ctrl.scheduleEvents,
                                                tutorId : $ctrl.wizarddetail.tutorId
                                            }
                                        }
                                    });
                                    modalInstance.opened.then(function () { });
                                    modalInstance.result.then(function (events) {

                                        if (events) {
                                            events.forEach(function (evt) {
                                                
                                                let obj = Object.assign(evt, { id: 'lesson_' + ($ctrl.lessoninfo.length) });
                                                $ctrl.lessoninfo.push(obj);
                                                $ctrl.lessondesc = $ctrl.lessoninfo;
                                                $ctrl.lessontime.push(lessontime);
                                                $ctrl.totaltime = _.sum($ctrl.lessontime);
                                                calculateStripeFees();
                                                toastr.success('Lesson booked successfully.');
                                                $ctrl.lessonLength = $ctrl.lessoninfo.length;
                                                uiCalendarConfig.calendars[calendar].fullCalendar('renderEvent', obj, true);
                                                
                                            });
                                        }

                                    }, function () {

                                    });
                                }, 100);
                            } else {
                                toastr.warning('You are trying to book a lesson before the schedule of previous lesson.');
                                uiCalendarConfig.calendars[calendar].fullCalendar("unselect");
                            }

                        } else {
                            toastr.info('All lessons already booked!.');
                            uiCalendarConfig.calendars[calendar].fullCalendar("unselect");
                        }
                    }
                }
            }
        }
        function calculateStripeFees() {
            var amount = $ctrl.totaltime * $ctrl.wizarddetail.level.rate;
            $ctrl.stripeFee = 0.00;//CommonService.getStripeFee(amount);
        }
        /*
         * This function is used to remove booking events from the calendar
         */
        function resetBooking(calendar) {
            uiCalendarConfig.calendars[calendar].fullCalendar('clientEvents', function (event) {
                if (event.title === 'Lesson') {
                    uiCalendarConfig.calendars[calendar].fullCalendar('removeEvents', event._id);
                    $ctrl.lessoninfo = [];
                    $ctrl.lessondesc = angular.copy($ctrl.lessoninfo);
                    $ctrl.lessontime = [];
                    $ctrl.totaltime = 0;
                    $ctrl.lessonLength = 0;
                    $ctrl.center = $ctrl.oldCenter;
                    $ctrl.name = $ctrl.oldName;
                    calculateStripeFees();
                    geocode();
                }
            });
        }

        function removeLesson(calendar, index) {

            uiCalendarConfig.calendars[calendar].fullCalendar('clientEvents', function (event) {
                if (event._id === 'lesson_' + index) {
                    uiCalendarConfig.calendars[calendar].fullCalendar('removeEvents', event._id);
                    $ctrl.lessoninfo = _.reject($ctrl.lessoninfo, { id: event._id });
                    $ctrl.lessondesc = angular.copy($ctrl.lessoninfo);
                    _.pullAt($ctrl.lessontime, index);
                    $ctrl.totaltime = _.sum($ctrl.lessontime);
                    $ctrl.lessonLength = $ctrl.lessonLength - 1;
                    calculateStripeFees();
                    toastr.info('You have recently removed a lesson from your Booking schedule');
                }
            });
        }

        /*
         * @desc - render calendar
         */
        function renderCalender(calendar) {
            $timeout(function () {
                if (uiCalendarConfig.calendars[calendar]) {
                    uiCalendarConfig.calendars[calendar].fullCalendar('render');
                }
            }, 1000);
        }


        function changeView(view, element) {
            //            uiCalendarConfig.calendars['availableCalendar'].fullCalendar('removeEvents');
            //            uiCalendarConfig.calendars['availableCalendar'].fullCalendar('addEventSource',$ctrl.lessoninfo);
        }

        function changeWeek(calendar, startDate) {
            $timeout(function () {
                if (uiCalendarConfig.calendars[calendar]) {
                    uiCalendarConfig.calendars[calendar].fullCalendar('gotoDate', moment(new Date(startDate)));
                    $ctrl.currentWeekNo = _.filter($ctrl.monthlyWeeks, { weekStart: startDate })[0].weekIndex;
                }
            }, 200);
        }

        /*
         * Function to change calander view when month changes
         */
        function changeMonth(calendar, startDate) {
            var startdate = moment(new Date(startDate));
            $timeout(function () {
                if (uiCalendarConfig.calendars[calendar]) {
                    uiCalendarConfig.calendars[calendar].fullCalendar('gotoDate', startdate);
                    $ctrl.currentMonthNo = _.filter($ctrl.monthList, { desc: startdate.format("MMM") })[0].monthIndex;
                    $ctrl.monthlyWeeks = getWeeksInMonth(startdate.year(), startdate.month());
                    $ctrl.currentWeekNo = _.filter($ctrl.monthlyWeeks, { weekStart: startDate })[0].weekIndex;
                }
            }, 200);
        }

        /*
         * Function to check whether event is valid or not
         */
        function isValidEvent(start, end, calendar) {
            return uiCalendarConfig.calendars[calendar].fullCalendar('clientEvents', function (event) {
                if (event.rendering === "background") {
                    if (start.isAfter(event.start) || start.isSame(event.start, 'minute')) {
                        if (end.isBefore(event.end) || end.isSame(event.end, 'minute')) {
                            return true;
                        } else {
                            return false;
                        }
                    }
                }


            });
        }

        /*
         * Function to check whether interview is before Lessons
         */
        // && _.isUndefined(event.id) Don't remove this it will be used in future  
        function isInterviewBeforeLessons(start, end, calendar) {
            var interviewEvents = uiCalendarConfig.calendars[calendar].fullCalendar('clientEvents');
            var isInterview = [];

            _.forEach(interviewEvents, function (event) {
                if (event.title === 'Interview' && start.isBefore(event.start)) {
                    isInterview.push(event);
                } else {

                }
            });
            return isInterview;
        }

        /*
         * This function is used for a loader when events are being fetched or rendered
         */
        function loading(isLoading, view) {
            if (isLoading) {
                $('.loading-wrp').show();
            } else {
                $('.loading-wrp').hide();
            }
        }

        // Calendar Section ends here
        // Datetime picker functions

        // Interview Section start here
        /*
         * This function is rendered for datepicker
         */
        function beforeRender($view, $dates) {
            var min = moment().add(-1, $view).valueOf();
            for (var i = 0, len = $dates.length; i < len; i++) {
                $dates[i].selectable = ($dates[i].localDateValue() >= min);
            }
        }

        /*
         * This function is used to get the time availabilty fot Tutor Interview Schedule
         */
        function onTimeSet(newdate, olddate) {
            $ctrl.timerange = [];
            var currentDate = moment().toISOString();
            var currentDay = moment(currentDate).startOf('day').format('YYYY-MM-DD');
            var interviewDate = (_.isUndefined(newdate)) ? olddate : newdate;
            var interviewDay = moment(interviewDate).startOf('day').format('YYYY-MM-DD');
            interviewDate = moment(interviewDate).toISOString();
            var dayname = moment(interviewDate).weekday();
            var timeAvailability = [];

            _.forEach($ctrl.backgroundEvents, function (value) {
                if (dayname === value.dow[0]) {
                    timeAvailability.push(value); // In case multiple availabilities in a day
                }
            });

            // sort business hours on the basis of start time
            timeAvailability = _.sortBy(timeAvailability, [function (o) { return o.start; }]);

            if (!_.isEmpty(timeAvailability)) {

                var time = [];
                _.forEach(timeAvailability, function (value) {
                    var start = _.split(value.start, ':');
                    var end = _.split(value.end, ':');
                    var hours, mins;
                    var startHour = start[0];
                    var startMin = start[1];
                    var endHour = end[0];
                    var endMin = end[1];
                    var currentHour = moment(currentDate).startOf('hour').format('HH');
                    var currentMin = moment(currentDate).startOf('min').format('mm');

                    hours = _.range(startHour, endHour);
                    mins = [0, 15, 30, 45];

                    // To get max and min time of avaiability
                    var maxTime = moment(interviewDate).set({ 'hour': endHour, 'minute': endMin }).valueOf();
                    var minTime = moment(interviewDate).set({ 'hour': currentHour, 'minute': currentMin }).valueOf();

                    _.forEach(hours, function (value) {
                        _.forEach(mins, function (v) {
                            var hour = value + ":" + (_.padStart(v, 2, '0'));
                            var starttime = moment(interviewDate).set({ 'hour': value, 'minute': v });
                            var endtime = moment(starttime).add(1, 'hour');

                            // push to array till max end time only
                            if (currentDay === interviewDay) {
                                if (moment(starttime).valueOf() >= minTime && moment(endtime).valueOf() <= maxTime) {
                                    time.push({
                                        name: moment(starttime).format('hh:mm a') + ' - ' + moment(endtime).format('hh:mm a'),
                                        value: hour
                                    });
                                }
                            } else {
                                if (moment(endtime).valueOf() <= maxTime) {
                                    time.push({
                                        name: moment(starttime).format('hh:mm a') + ' - ' + moment(endtime).format('hh:mm a'),
                                        value: hour
                                    });
                                }
                            }

                        });
                    });
                    if (_.size(time) > 0) {
                        $ctrl.timerange = angular.copy(time);
                    } else {
                        toastr.warning('No Availability of Tutor on the selected day.');
                        return false;
                    }
                });
            } else {
                toastr.warning('No Availability of Tutor on the selected day.');
            }
        }

        /*
         * This function is used to add interview slots
         */
        function saveInterviewInfo() {
            if (_.isUndefined($ctrl.interviewDate) || _.isUndefined($ctrl.interviewTime)) {
                toastr.warning('Please select date and time for interview');
                return false;
            } else {
                var calendar = 'eventCalendar';
                var calendar2 = 'availableCalendar';
                var time = _.split($ctrl.interviewTime, ':', 2);
                var interviewStart = moment($ctrl.interviewDate).hours(time[0]).minutes(time[1]);
                var interviewEnd = moment(interviewStart).add(1, "hour");

                var interviewValidity = isInterviewValid(interviewStart, interviewEnd, calendar);
                if (interviewValidity.length > 0) {
                    toastr.info('There is already a booked slot between the time selected.');
                    return false;
                } else {
                    var interviewLength = $ctrl.interviewinfo.length;

                    var eventData = {
                        title: 'Interview',
                        start: interviewStart.format(),
                        end: interviewEnd.format(),
                        allDay: false,
                        overlap: false,
                        stick: true,
                        editable: false,
                        type: 'interview'
                    };
                    var title = "You’re booking: " + moment(interviewStart).format('DD MMM YYYY, dddd');
                    var text = '(' + moment(interviewStart).format('hh:mma') + ' to ' + moment(interviewEnd).format('hh:mma') + ').' + ' Confirm ?';

                    if (interviewLength < 1) {
                        swal({
                            title: title,
                            text: text,
                            showCancelButton: true,
                            confirmButtonColor: "#48b0e4",
                            confirmButtonText: "Confirm",
                            cancelButtonText: "Cancel",
                            cancelButtonColor: "#ed4858",
                            closeOnConfirm: false
                        }, function (isConfirm) {
                            if (isConfirm) {
                                swal({
                                    title: "",
                                    text: "Pending tutor\'s acceptance",
                                    confirmButtonText: "Sent",
                                    confirmButtonColor: "#3ab557"
                                }, function () {
                                    $timeout(function () {
                                        uiCalendarConfig.calendars[calendar].fullCalendar('renderEvent', eventData, true);
                                        uiCalendarConfig.calendars[calendar2].fullCalendar('renderEvent', eventData, true);
                                        $ctrl.interviewinfo.push(eventData);
                                        $ctrl.interviewstart = interviewStart.toISOString();
                                        $ctrl.interviewend = interviewEnd.toISOString();
                                        toastr.success('Interview booked successfully.');
                                    }, 200);
                                });
                            }
                        });
                    } else {
                        toastr.error('Interview is already booked.');
                    }
                }
            }
        }

        /*
         * Function to check whether interview event slot added is available or not
         */
        function isInterviewValid(start, end, calendar) {
            return uiCalendarConfig.calendars[calendar].fullCalendar('clientEvents', function (event) {
                if (Math.round(event.start) / 1000 < Math.round(end) / 1000 && Math.round(event.end) > Math.round(start)) {
                    return true;
                } else {
                    return false;
                }
            });
        }

        /*
         * This function is used to reset the Interview Slots
         */
        function resetInterview(calendar) {
            var calendar2 = 'availableCalendar';
            if (uiCalendarConfig.calendars[calendar]) {
                uiCalendarConfig.calendars[calendar].fullCalendar('clientEvents', function (event) {
                    if (event.title === 'Interview') {
                        uiCalendarConfig.calendars[calendar].fullCalendar('removeEvents', event._id);
                        $ctrl.interviewinfo = [];
                    }
                });
                uiCalendarConfig.calendars[calendar2].fullCalendar('clientEvents', function (event) {
                    if (event.title === 'Interview') {
                        uiCalendarConfig.calendars[calendar2].fullCalendar('removeEvents', event._id);
                    }
                });
                // Reset Interview date and time 
                $ctrl.interviewDate = "";
                $ctrl.interviewTime = "";
                $ctrl.timerange = [];
            }
        }


        // private functions
        /*
         * Function to get objects of weeks in a particular month
         */
        function getWeeksInMonth(year, month) {
            var monthStart = moment().year(year).month(month).date(1);
            var monthEnd = moment().year(year).month(month).endOf('month');
            var numDaysInMonth = moment().year(year).month(month).endOf('month').date();

            //calculate weeks in given month
            var weeks = Math.ceil((numDaysInMonth + monthStart.day()) / 7);
            var weekRange = [];
            var weekStart = moment().year(year).month(month).date(1);
            var i = 0, j = 1;

            while (i < weeks) {
                var weekEnd = moment(weekStart);

                if (weekEnd.endOf('week').date() <= numDaysInMonth && weekEnd.month() === month) {
                    weekEnd = weekEnd.endOf('week').format('LL');
                } else {
                    weekEnd = moment(monthEnd);
                    weekEnd = weekEnd.format('LL');
                }

                weekRange.push({
                    'weekStart': weekStart.format('LL'),
                    'weekEnd': weekEnd,
                    'weekNo': 'Week' + ' ' + j,
                    'weekIndex': i
                });
                weekStart = weekStart.weekday(7);
                i++;
                j++;
            }
            return weekRange;
        }

        function getMonthsInYear() {
            var months = moment.monthsShort();
            $ctrl.monthList = [];
            _.forEach(months, function (value, key) {
                var start = moment().month(value).startOf('month').format("LL");
                $ctrl.monthList.push({ start: start, desc: value, monthIndex: key });
            });
        }

        // modal open
        function modalopen(template, windowClass, size, invitationId, textfrom) {
            var udataobj = { 'invitationId': invitationId };
            var timer = $timeout(function () {
                var modalInstance = $uibModal.open({
                    animation: true,
                    ariaLabelledBy: 'modal-title',
                    ariaDescribedBy: 'modal-body',
                    templateUrl: '/app/layout/dialogs/' + template,
                    size: size,
                    controller: 'ModalInstanceController',
                    controllerAs: '$ctrl',
                    windowClass: windowClass,
                    resolve: {
                        uData: udataobj,
                        socialConstants: SocialService.getSocialConstantDetails()
                    }
                });

                modalInstance.opened.then(function () {
                    $timeout.cancel(timer);
                    delete $localStorage.learnermodalcheck;
                });
                modalInstance.result.then(function (response) {
                    if (!_.isUndefined(response) && response === 'success') {
                        if (textfrom === 'checkout') {
                            angular.element('#checkout').triggerHandler('click');
                        } else {
                            saveBookLessonInfo();
                        }
                    } else if ($localStorage.learnermodalcheck) {
                        whichmodalopen();
                    }
                }, function () {

                });
            }, 400);
        }

        // modal type
        function whichmodalopen() {
            if ($localStorage.learnermodalcheck && !$localStorage.loginstatus) {
                if ($localStorage.learnermodalcheck.type === 1) {
                    modalopen('learnerlogin.html', 'md-modal', 'md');
                }
                if ($localStorage.learnermodalcheck.type === 2) {
                    modalopen('learnersignup.html', 'tutormodal learnerModal', 'lg');
                }
                if ($localStorage.learnermodalcheck.type === 3) {
                    modalopen('forgotpassword.html', 'md-modal', 'md');
                }
            }
        }

        // Placement of map
        function placeChanged() {
            $ctrl.place = this.getPlace();
            console.log($ctrl.place);
            if (!_.isUndefined($ctrl.place.geometry)) {
                $ctrl.map.setCenter($ctrl.place.geometry.location);
            } else {
                toastr.info('Can\'t find the location');
            }
            return false;
        }

        //        NgMap.getMap().then(function (map) {
        //            //$ctrl.map = map;
        //            $ctrl.map = NgMap.initMap("map");
        //        });

        $timeout(function () {
            NgMap.getMap({ id: 'my_map' }).then(function (response) {
                google.maps.event.trigger(response, 'resize');
            });
        }, 500);

        function geocode() {
            var geocodingPromise = Geocoder.geocodeAddress($ctrl.name);
            geocodingPromise.then(
                function (result) {
                    $ctrl.place = result;
                    $ctrl.center = result.lat + ',' + result.lng;
                    $ctrl.positions = [{ lat: result.lat, lng: result.lng }];
                    $ctrl.geocodingResult =
                        '(lat, lng) ' + result.lat + ', ' + result.lng +
                        ' (address: \'' + result.formattedAddress + '\')';
                    $localStorage.lastLocation = { center: $ctrl.center, name: $ctrl.name };
                },
                function (err) {
                    $ctrl.geocodingResult = err.message;
                });
        }

        // get Stripe Details
        function getStripeDetails() {
            StripeService.getStripeDetails(function (result) {
                if (result.status === 200) {
                    $ctrl.stripe = {
                        pk: result.data.pk,
                        url: stripeConstants.baseurl
                    };
                } else {
                    console.log('error');
                }
            });
        }

    }

})();




(function () {
    'use strict';
    angular
        .module('la-app')
        .controller('RenewPackageController', RenewPackageController);

    RenewPackageController.$inject = ['CalendarService','CommonService', '$uibModal', '$uibModalInstance', '$timeout', 'uiCalendarConfig', 'lessonData', 'SearchService', '$localStorage', 'toastr', '$state', 'NgMap', 'Geocoder', 'StripeService', 'googleConstants'];

    // Please note that $uibModalInstance represents a modal window (instance) dependency.
    function RenewPackageController(CalendarService,CommonService, $uibModal, $uibModalInstance, $timeout, uiCalendarConfig, lessonData, SearchService, $localStorage, toastr, $state, NgMap, Geocoder, StripeService, googleConstants) {
        var $ctrl = this;
        $ctrl.init = init;
        $ctrl.close = close;
        $ctrl.stripeFee = 0.00;
        $ctrl.wizarddetail = lessonData;
        $ctrl.checkPackage = true;
        $ctrl.validateBooking = validateBooking;
        $ctrl.packages = [];
        $ctrl.minDuration = 1;
        $ctrl.getPackageDetail = getPackageDetail;
        $ctrl.lessonRange = lessonRange;
        $ctrl.getLessonPackages = getLessonPackages;
        $ctrl.renewBookLessonInfo = renewBookLessonInfo;
        $ctrl.changeWeek = changeWeek;
        $ctrl.changeMonth = changeMonth;
        $ctrl.currentMonth = moment().format("MMM");
        $ctrl.currentWeek = moment().startOf('week').format("LL");
        $ctrl.getMonthsInYear = getMonthsInYear();
        $ctrl.monthlyWeeks = getWeeksInMonth(moment().year(), moment().month());
        $ctrl.currentMonthNo = _.filter($ctrl.monthList, { desc: $ctrl.currentMonth })[0].monthIndex;
        $ctrl.duration = lessonData.level.duration;
        $ctrl.subjectType = lessonData.type;

        $ctrl.resetBooking = resetBooking;
        $ctrl.timebuffer = getTimeBuffer();
        $ctrl.placeChanged = placeChanged;
        $ctrl.geocode = geocode;


        $ctrl.policy = {};

        // Plugins configuration

        $ctrl.googleMapsUrl = 'https://maps.google.com/maps/api/js?libraries=places&key=' + googleConstants.MAP_API_KEY;

        if (_.isUndefined($localStorage.currentUser)) {
            $ctrl.center = "1.280094, 103.850949";
            if (!_.isUndefined($localStorage.lastLocation) && !_.isNull($localStorage.lastLocation)) {
                $ctrl.center = $localStorage.lastLocation.center;
                $ctrl.name = $localStorage.lastLocation.name;
            }
        } else {
            if (_.isUndefined($localStorage.currentUser.location)) {
                $ctrl.center = "1.280094, 103.850949";
                if (!_.isUndefined($localStorage.lastLocation) && !_.isNull($localStorage.lastLocation)) {
                    $ctrl.center = $localStorage.lastLocation.center;
                    $ctrl.name = $localStorage.lastLocation.name;
                }
            } else {
                var defaultlocation = $localStorage.currentUser.location;
                if (defaultlocation.lat && defaultlocation.lng) {
                    $ctrl.center = defaultlocation.lat + ',' + defaultlocation.lng;
                    $ctrl.name = defaultlocation.name;
                    $ctrl.positions = [{ lat: defaultlocation.lat, lng: defaultlocation.lng }];
                } else {
                    $ctrl.center = "1.280094, 103.850949";
                    if (!_.isUndefined($localStorage.lastLocation) && !_.isNull($localStorage.lastLocation)) {
                        $ctrl.center = $localStorage.lastLocation.center;
                        $ctrl.name = $localStorage.lastLocation.name;
                    }
                }
                $localStorage.lastLocation = { center: $ctrl.center, name: $ctrl.name };
            }
        }
        $ctrl.oldCenter = $ctrl.center;
        $ctrl.oldName = $ctrl.name;
        var Week = _.filter($ctrl.monthlyWeeks, { weekStart: $ctrl.currentWeek });
        if (Week.length > 0) {
            $ctrl.currentWeekNo = _.filter($ctrl.monthlyWeeks, { weekStart: $ctrl.currentWeek })[0].weekIndex;
        } else {
            $ctrl.currentWeekNo = 0;
        }

        /* config calendar */
        $ctrl.uiConfig = {
            availableCalendar: {
                height: 450,
                dayOfMonthFormat: 'ddd DD/MM',
                editable: true,
                selectable: true,
                header: {
                    left: '',
                    center: '',
                    right: ''
                },
                scrollTime: '07:00:00',
                eventClick: eventOnClick,
                select: selectEvent,
                viewRender: changeView,
                defaultView: 'agendaWeek',
                loading: loading,
                nowIndicator: true
            }
        };
        $ctrl.lessonPopover = { templateUrl: 'lessonTemplate.html', placement: 'right' };
        // calendar events
        $ctrl.events = [];
        $ctrl.interviewinfo = [];
        $ctrl.lessoninfo = [];
        $ctrl.backgroundEvents = [];

        // Tutor's Availability
        $ctrl.businessEvents = {
            url: webservices.getbusinesshours + '/' + $ctrl.wizarddetail.tutorId,
            type: 'GET',
            data: { type: $ctrl.subjectType },
            success: function (result) {
                if (result.status === 200) {
                    _.forEach(result.data, function (v) {
                        $ctrl.backgroundEvents.push(_.extend({}, v, { rendering: 'background', color: '#acacac' }));
                    });
                    return $ctrl.backgroundEvents;
                } else {
                    toastr.warning('Unable to fetch events');
                }
            },
            error: function () {
                console.log('There was an error while fetching events!');
            }
        };

        // Tutor's Weekly Booked Slot
        $ctrl.bookinginfo = {
            url: webservices.gettutornonavailability + '/' + $ctrl.wizarddetail.tutorId,
            type: 'GET',
            data: {},
            success: function (result) {
                if (result.status === 200) {
                    var scheduleEvents = [];
                    _.forEach(result.data, function (o) {
                        scheduleEvents.push(_.extend({}, o, { 'start': moment(o.start).local().format(), 'end': moment(o.end).local().format(), 'editable': false, 'title': 'Busy' }));
                    });
                    return scheduleEvents;
                } else {
                    toastr.warning('Unable to fetch events');
                }
            },
            error: function () {
                console.log('There was an error while fetching events!');
            }
        };

        $ctrl.businessSources = [$ctrl.businessEvents, $ctrl.bookinginfo];
        $ctrl.bookingSources = [$ctrl.bookinginfo];
        $ctrl.lessondesc = angular.copy($ctrl.lessoninfo);
        $ctrl.lessontime = [];
        $ctrl.totalLesson = 0;
        $ctrl.lessonLength = 0;
        $ctrl.totaltime = 0;

        init();

        /*
         * Initialisation
         */
        function init() {
            getStripeDetails();
            getBookLessonInfo();
            getLessonPackages();
            getTutorPoliciesInfo();
            geocode();
        }

        /*
         * @desc - Close
         */
        function close(message) {
            $uibModalInstance.close(message);
        }
        function calculateStripeFees() {
            var amount = $ctrl.totaltime * $ctrl.wizarddetail.level.rate;
            $ctrl.stripeFee = 0.00;//CommonService.getStripeFee(amount);
        }
        /*
         * @description - get lesson packages for booking
         */
        function getLessonPackages() {
            SearchService.getLessonPackages(function (result) {
                $ctrl.packages = result.data;
                $ctrl.initialPackage = $ctrl.packages[0];

                $ctrl.selectedPackage = $ctrl.packages[0]._id;
                $ctrl.totalLesson = $ctrl.initialPackage.number_lesson;
            });
        }

        /*
         * @description - This function is used to change the package detail
         */
        function getPackageDetail(package_detail, index) {
            $ctrl.initialPackage = package_detail;
            $ctrl.selectedPackage = package_detail._id;
            $ctrl.totalLesson = $ctrl.initialPackage.number_lesson;
            $ctrl.lessondesc = [];
        }

        /*
         * @description - get Book lesson info
         */
        function getBookLessonInfo() {
            var details = { tutor: $ctrl.wizarddetail.tutorId, subject: $ctrl.wizarddetail.subject._id, level: $ctrl.wizarddetail.level._id };
            SearchService.getBookLessonInfo(details, function (result) {
                if (result.status === 200) {
                    $ctrl.wizards = result.data;
                    renderCalender('availableCalendar');
                } else {
                    console.log("errr" + result);
                }
            });
        }

        /*
         * get reschedule and cancellation policy of tutor
         */
        function getTutorPoliciesInfo() {
            var details = $ctrl.wizarddetail.tutorId;
            SearchService.getTutorPoliciesInfo(details, function (result) {
                if (result.status === 200) {
                    var policy = result.data;
                    var reschedule = {
                        refundPolicy: policy.policy_slug,
                        refundDuration: policy.reschedule.duration,
                        refundPenalty: policy.reschedule.penalty_value
                    };

                    var cancellation = {
                        refundPolicy: policy.policy_slug,
                        refundDuration: policy.cancellation.duration,
                        refundPenalty: policy.cancellation.penalty_value
                    };

                    $ctrl.policy = {
                        reschedule: reschedule,
                        cancellation: cancellation
                    };

                } else {
                    console.log("errr" + result);
                }
            });
        }


        /*
         * get time buffer for booking slot
         */
        function getTimeBuffer() {
            return (!_.isUndefined($ctrl.wizarddetail.timebuffer)) ? $ctrl.wizarddetail.timebuffer : otherConstants.la_timebuffer;
        }

        /*
         * This function return the no of lessons field as an array
         */
        function lessonRange(range) {
            return new Array(range);
        }

        // @desc - renew booking lesson info
        function renewBookLessonInfo(token) {
            var studentId = $localStorage.currentUser.id;
            var bookingamount = $ctrl.totaltime * $ctrl.wizarddetail.level.rate;
            var interviewschedule = {};
            var payment_info = { totalbookingamount: bookingamount, payment_status: 'Paid' };


            if ($ctrl.checkPackage) {
                var bookingSlots = [];
                _.forEach($ctrl.lessondesc, function (key, index) {
                    bookingSlots.push({
                        activityname: 'Lesson ' + parseInt(index + 1),
                        title: $ctrl.lessondesc[index].title,
                        start: moment($ctrl.lessondesc[index].start).toISOString(),
                        end: moment($ctrl.lessondesc[index].end).toISOString(),
                        overlap: $ctrl.lessondesc[index].overlap,
                        allDay: $ctrl.lessondesc[index].allDay,
                        type: 'lesson',
                        lesson_duration: $ctrl.lessondesc[index].lessonduration,
                        lesson_price: $ctrl.lessondesc[index].lessonprice
                    });
                });
                var bookinginfo = bookingSlots;
            } else {
                var bookinginfo = {};
            }

            var details = {
                tutor_id: $ctrl.wizarddetail.tutorId,
                student_id: studentId,
                subject_id: $ctrl.wizarddetail.subject._id,
                subject_type: $ctrl.wizarddetail.subject.type,
                level_id: $ctrl.wizarddetail.level.levelId,
                package_id: $ctrl.selectedPackage,
                no_of_lessons: $ctrl.totalLesson,
                is_interview_require: false,
                interviewschedule: interviewschedule,
                booking_info: bookinginfo,
                payment_info: payment_info,
                reschedule_policy: $ctrl.policy.reschedule,
                cancellation_policy: $ctrl.policy.cancellation,
                token: token,
                stripeFee: $ctrl.stripeFee,
                location: $ctrl.address,
                prev_booking_ref: $ctrl.wizarddetail.info.reference
            };

            SearchService.saveBookLessonInfo(details, function (result) {
                if (result.status === 200) {
                    toastr.success('Booking was successful.');
                    close('success');
                    $state.reload();
                } else {
                    toastr.warning(result.message);
                    console.log("errr" + result);
                }
            });
        }


        function validateBooking(textfrom) {
            if ($ctrl.lessonLength < $ctrl.totalLesson) {
                toastr.warning('Please Book all lessons on grid');
                return false;
            } else if (_.isUndefined($ctrl.place) && _.isUndefined(defaultlocation)) {
                toastr.warning('Please select your prefered location for Learning');
                return false;
            } else {

                if (!_.isUndefined($ctrl.place)) {
                    if (_.isUndefined($ctrl.place.lat) && _.isUndefined($ctrl.place.lng)) {
                        toastr.warning('Please select a proper location');
                        return false;
                    } else {
                        $ctrl.address = {
                            lat: $ctrl.place.lat,
                            lng: $ctrl.place.lng,
                            name: $ctrl.name
                        };
                    }
                } else {
                    $ctrl.address = {
                        lat: defaultlocation.lat,
                        lng: defaultlocation.lng,
                        name: defaultlocation.name
                    };
                }

                if (textfrom === 'checkout') {
                    angular.element('#checkout').triggerHandler('click');
                }
                return true;
            }
        }

        // Calendar events
        function eventOnClick() {
            console.log('clicked');
        }

        /*
         * Function is fired when a new event is to be added
         */
        function selectEvent(start, end, jsEvent, view) {
            var calendar = 'availableCalendar';
            var duration = end.diff(start, 'minutes') / 60;
            var validity = isValidEvent(start, end, calendar);
            var current = uiCalendarConfig.calendars[calendar].fullCalendar('getDate');
            var slotBuffer = $ctrl.timebuffer; // start buffer in minutes
            var startDuration = start.diff(current, 'minutes');

            if (validity.length === 0) {
                toastr.info('Lessons cannot be booked on UnAvailable Slots');
                uiCalendarConfig.calendars[calendar].fullCalendar("unselect");
            } else {

                if (startDuration < slotBuffer) {
                    toastr.info('Please select slot not before ' + slotBuffer / 60 + ' hours from current time.');
                    uiCalendarConfig.calendars[calendar].fullCalendar("unselect");
                    return false;
                }

                else if (duration > $ctrl.duration) {
                    toastr.info('Duration of this event cannot be greater than ' + $ctrl.duration + ' hour');
                    uiCalendarConfig.calendars[calendar].fullCalendar("unselect");
                    return false;
                }
                else if (duration < $ctrl.minDuration) {
                    toastr.info('Duration of this event cannot be less than ' + $ctrl.minDuration + ' hour');
                    uiCalendarConfig.calendars[calendar].fullCalendar("unselect");
                    return false;
                }
                else {
                    var totalLesson = $ctrl.totalLesson;
                    var lessonLength = $ctrl.lessonLength;
                    if (moment().diff(start, 'hour') > 0) {
                        toastr.error('Lessons cannot be booked for past date!');
                        uiCalendarConfig.calendars[calendar].fullCalendar("unselect");
                    } else {

                        //check if event is of all day or specified time;
                        var allDayEvent = false;
                        if (start.format().indexOf('T') === -1) {
                            allDayEvent = true;
                        }

                        var lessonprice = duration * $ctrl.wizarddetail.level.rate;
                        var eventData = {
                            title: 'Lesson',
                            start: start.format(),
                            end: end.format(),
                            allDay: allDayEvent,
                            overlap: false,
                            stick: true,
                            editable: false,
                            type: 'lesson',
                            lessonprice: lessonprice,
                            lessonduration: duration
                        };

                        if (lessonLength < totalLesson) {
                            var start = moment(eventData.start);
                            var end = moment(eventData.end);
                            var lessontime = end.diff(start, 'minutes') / 60;

                            // Check to see if event is not added before the previous lesson
                            var checkPastEvent = [];

                            _.forEach($ctrl.lessoninfo, function (v) {
                                if (end.isBefore(v.end) || end.isSame(v.end, 'minute')) {
                                    checkPastEvent.push(true);
                                }
                            });

                            if (checkPastEvent.length === 0) {
                                uiCalendarConfig.calendars[calendar].fullCalendar('renderEvent', eventData, true);
                                $ctrl.lessoninfo.push(eventData);
                                $ctrl.lessondesc = $ctrl.lessoninfo;
                                $ctrl.lessontime.push(lessontime);
                                $ctrl.totaltime = _.sum($ctrl.lessontime);
                                toastr.success('Lesson booked successfully.');
                                $ctrl.lessonLength = $ctrl.lessoninfo.length;
                                calculateStripeFees();
                                if ($ctrl.lessonLength >= $ctrl.initialPackage.number_lesson) {
                                    return;
                                }
                                $timeout(function () {
                                    var modalInstance = $uibModal.open({
                                        animation: true,
                                        ariaLabelledBy: 'modal-title',
                                        ariaDescribedBy: 'modal-body',
                                        templateUrl: '/app/layout/dialogs/autofill-popup.html',
                                        size: 'md',
                                        controller: 'AutofillSlotController',
                                        controllerAs: '$ctrl',
                                        windowClass: 'write-rvwmodal',
                                        backdrop: 'static',
                                        resolve: {
                                            lessonData: {
                                                initialPackage: $ctrl.initialPackage,
                                                lessondesc: $ctrl.lessondesc,
                                                currentEvent: eventData,
                                                backgroundEvents: $ctrl.backgroundEvents,
                                                scheduleEvents: $ctrl.scheduleEvents,
                                                tutorId : $ctrl.wizarddetail.tutorId
                                            }
                                        }
                                    });
                                    modalInstance.opened.then(function () { });
                                    modalInstance.result.then(function (events) {
                                        console.log(events);
                                        if (events) {
                                            events.forEach(function (evt) {
                                                
                                                let obj = Object.assign(evt, { id: 'lesson_' + ($ctrl.lessoninfo.length) });
                                                $ctrl.lessoninfo.push(obj);
                                                $ctrl.lessondesc = $ctrl.lessoninfo;
                                                $ctrl.lessontime.push(lessontime);
                                                $ctrl.totaltime = _.sum($ctrl.lessontime);
                                                calculateStripeFees();
                                                toastr.success('Lesson booked successfully.');
                                                $ctrl.lessonLength = $ctrl.lessoninfo.length;
                                                uiCalendarConfig.calendars[calendar].fullCalendar('renderEvent', obj, true);
                                                
                                            });
                                        }

                                    }, function () {

                                    });
                                }, 100);
                            } else {
                                toastr.warning('You are trying to book a lesson before the schedule of previous lesson.');
                                uiCalendarConfig.calendars[calendar].fullCalendar("unselect");
                            }

                        } else {
                            toastr.info('All lessons already booked!.');
                            uiCalendarConfig.calendars[calendar].fullCalendar("unselect");
                        }
                    }
                }


            }
        }
        /*
         * This function is used to remove booking events from the calendar
         */
        function resetBooking(calendar) {
            uiCalendarConfig.calendars[calendar].fullCalendar('clientEvents', function (event) {
                if (event.title === 'Lesson') {
                    uiCalendarConfig.calendars[calendar].fullCalendar('removeEvents', event._id);
                    $ctrl.lessoninfo = [];
                    $ctrl.lessondesc = angular.copy($ctrl.lessoninfo);
                    $ctrl.lessontime = [];
                    $ctrl.totaltime = 0;
                    $ctrl.lessonLength = 0;
                    $ctrl.center = $ctrl.oldCenter;
                    $ctrl.name = $ctrl.oldName;
                    calculateStripeFees();
                }
            });
        }

        /*
         * @desc - render calendar
         */
        function renderCalender(calendar) {
            $timeout(function () {
                if (uiCalendarConfig.calendars[calendar]) {
                    uiCalendarConfig.calendars[calendar].fullCalendar('render');
                }
            }, 1000);
        }


        function changeView(view, element) {
            //            uiCalendarConfig.calendars['availableCalendar'].fullCalendar('removeEvents');
            //            uiCalendarConfig.calendars['availableCalendar'].fullCalendar('addEventSource',$ctrl.lessoninfo);
        }

        function changeWeek(calendar, startDate) {
            $timeout(function () {
                if (uiCalendarConfig.calendars[calendar]) {
                    uiCalendarConfig.calendars[calendar].fullCalendar('gotoDate', moment(new Date(startDate)));
                    $ctrl.currentWeekNo = _.filter($ctrl.monthlyWeeks, { weekStart: startDate })[0].weekIndex;
                }
            }, 200);
        }

        /*
         * Function to change calander view when month changes
         */
        function changeMonth(calendar, startDate) {
            var startdate = moment(new Date(startDate));
            $timeout(function () {
                if (uiCalendarConfig.calendars[calendar]) {
                    uiCalendarConfig.calendars[calendar].fullCalendar('gotoDate', startdate);
                    $ctrl.currentMonthNo = _.filter($ctrl.monthList, { desc: startdate.format("MMM") })[0].monthIndex;
                    $ctrl.monthlyWeeks = getWeeksInMonth(startdate.year(), startdate.month());
                    $ctrl.currentWeekNo = _.filter($ctrl.monthlyWeeks, { weekStart: startDate })[0].weekIndex;
                }
            }, 200);
        }

        /*
         * Function to check whether event is valid or not
         */
        function isValidEvent(start, end, calendar) {
            return uiCalendarConfig.calendars[calendar].fullCalendar('clientEvents', function (event) {
                if (event.rendering === "background" && (start.isAfter(event.start) || start.isSame(event.start, 'minute'))
                    && (end.isBefore(event.end) || end.isSame(event.end, 'minute'))) {
                    return true;
                } else {
                    return false;
                }
            });
        }

        /*
         * This function is used for a loader when events are being fetched or rendered
         */
        function loading(isLoading, view) {
            if (isLoading) {
                $('.loading-wrp').show();
            } else {
                $('.loading-wrp').hide();
            }
        }

        // Calendar Section ends here

        // private functions
        /*
         * Function to get objects of weeks in a particular month
         */
        function getWeeksInMonth(year, month) {
            var monthStart = moment().year(year).month(month).date(1);
            var monthEnd = moment().year(year).month(month).endOf('month');
            var numDaysInMonth = moment().year(year).month(month).endOf('month').date();

            //calculate weeks in given month
            var weeks = Math.ceil((numDaysInMonth + monthStart.day()) / 7);
            var weekRange = [];
            var weekStart = moment().year(year).month(month).date(1);
            var i = 0, j = 1;

            while (i < weeks) {
                var weekEnd = moment(weekStart);

                if (weekEnd.endOf('week').date() <= numDaysInMonth && weekEnd.month() === month) {
                    weekEnd = weekEnd.endOf('week').format('LL');
                } else {
                    weekEnd = moment(monthEnd);
                    weekEnd = weekEnd.format('LL');
                }

                weekRange.push({
                    'weekStart': weekStart.format('LL'),
                    'weekEnd': weekEnd,
                    'weekNo': 'Week' + ' ' + j,
                    'weekIndex': i
                });
                weekStart = weekStart.weekday(7);
                i++;
                j++;
            }
            return weekRange;
        }

        function getMonthsInYear() {
            var months = moment.monthsShort();
            $ctrl.monthList = [];
            _.forEach(months, function (value, key) {
                var start = moment().month(value).startOf('month').format("LL");
                $ctrl.monthList.push({ start: start, desc: value, monthIndex: key });
            });
        }

        // Placement of map
        function placeChanged() {
            $ctrl.place = this.getPlace();
            if (!_.isUndefined($ctrl.place.geometry)) {
                $ctrl.map.setCenter($ctrl.place.geometry.location);
            } else {
                toastr.info('Can\'t find the location');
            }
            return false;
        }

        NgMap.getMap().then(function (map) {
            $ctrl.map = map;
        });

        function geocode() {
            var geocodingPromise = Geocoder.geocodeAddress($ctrl.name);
            geocodingPromise.then(
                function (result) {
                    $ctrl.place = result;
                    $ctrl.center = result.lat + ',' + result.lng;
                    $ctrl.positions = [{ lat: result.lat, lng: result.lng }];
                    $ctrl.geocodingResult =
                        '(lat, lng) ' + result.lat + ', ' + result.lng +
                        ' (address: \'' + result.formattedAddress + '\')';
                    $localStorage.lastLocation = { center: $ctrl.center, name: $ctrl.name };
                },
                function (err) {
                    $ctrl.geocodingResult = err.message;
                });
        }

        // get Stripe Details
        function getStripeDetails() {
            StripeService.getStripeDetails(function (result) {
                if (result.status === 200) {
                    $ctrl.stripe = {
                        pk: result.data.pk,
                        url: stripeConstants.baseurl
                    };
                } else {
                    console.log('error');
                }
            });
        }

    }


})();

// Book Interview (only) Controller
(function () {
    'use strict';
    angular
        .module('la-app')
        .controller('BookInterviewInstanceController', BookInterviewInstanceController);

    BookInterviewInstanceController.$inject = ['$uibModalInstance', '$timeout', 'uiCalendarConfig', 'lessonData', 'SearchService', '$localStorage', 'toastr', '$state', 'TeacherService', '$uibModal', 'SocialService'];

    // Please note that $uibModalInstance represents a modal window (instance) dependency.
    function BookInterviewInstanceController($uibModalInstance, $timeout, uiCalendarConfig, lessonData, SearchService, $localStorage, toastr, $state, TeacherService, $uibModal, SocialService) {
        var $ctrl = this;
        $ctrl.init = init;
        $ctrl.close = close;
        $ctrl.wizarddetail = lessonData;
        $ctrl.checkInterview = true;
        //$ctrl.saveBookLessonInfo = saveBookLessonInfo;
        $ctrl.beforeRender = beforeRender;
        $ctrl.onTimeSet = onTimeSet;
        $ctrl.saveInterviewInfo = saveInterviewInfo;

        $ctrl.currentMonth = moment().format("MMM");
        $ctrl.currentWeek = moment().startOf('week').format("LL");
        //        $ctrl.getMonthsInYear = getMonthsInYear();
        //        $ctrl.monthlyWeeks = getWeeksInMonth(moment().year(), moment().month());
        //        $ctrl.currentMonthNo = _.filter($ctrl.monthList, {desc: $ctrl.currentMonth})[0].monthIndex;
        $ctrl.duration = lessonData.level.duration;
        $ctrl.subjectType = lessonData.type;
        $ctrl.resetInterview = resetInterview;

        $ctrl.timebuffer = getTimeBuffer();
        $ctrl.policy = {};
        $ctrl.validateInterview = validateInterview;

        // Plugins configuration
        /* config calendar */
        $ctrl.uiConfig = {
            calendar: {
                dayOfMonthFormat: 'ddd DD/MM',
                height: 450,
                header: {
                    left: '',
                    center: '',
                    right: ''
                },
                timezone: 'local'
            }
        };

        // calendar events
        $ctrl.events = [];
        $ctrl.interviewinfo = [];
        $ctrl.lessoninfo = [];
        $ctrl.backgroundEvents = [];

        // Tutor's Availability
        $ctrl.businessEvents = {
            url: webservices.getbusinesshours + '/' + $ctrl.wizarddetail.tutorId,
            type: 'GET',
            data: { type: $ctrl.subjectType },
            success: function (result) {
                if (result.status === 200) {
                    _.forEach(result.data, function (v) {
                        $ctrl.backgroundEvents.push(_.extend({}, v, { rendering: 'background', color: '#acacac' }));
                    });
                    return $ctrl.backgroundEvents;
                } else {
                    toastr.warning('Unable to fetch events');
                }
            },
            error: function () {
                console.log('There was an error while fetching events!');
            }
        };

        // Tutor's Weekly Booked Slot
        $ctrl.bookinginfo = {
            url: webservices.gettutornonavailability + '/' + $ctrl.wizarddetail.tutorId,
            type: 'GET',
            data: {},
            success: function (result) {
                if (result.status === 200) {
                    var scheduleEvents = [];
                    _.forEach(result.data, function (o) {
                        scheduleEvents.push(_.extend({}, o, { 'start': moment(o.start).local().format(), 'end': moment(o.end).local().format(), 'editable': false, 'title': 'Busy' }));
                    });
                    $ctrl.scheduleEvents = angular.copy(scheduleEvents);
                    return scheduleEvents;
                } else {
                    toastr.warning('Unable to fetch events');
                }
            },
            error: function () {
                console.log('There was an error while fetching events!');
            }
        };

        $ctrl.businessSources = [$ctrl.businessEvents, $ctrl.bookinginfo];
        $ctrl.bookingSources = [$ctrl.bookinginfo];

        $ctrl.lessondesc = angular.copy($ctrl.lessoninfo);
        $ctrl.lessontime = [];
        $ctrl.totalLesson = 0;
        $ctrl.lessonLength = 0;
        $ctrl.totaltime = 0;
        // selectize
        $ctrl.interviewConfig = { maxItems: 1, labelField: 'name', valueField: 'value', searchField: ['name'] };
        init();

        /*
         * Initialisation
         */
        function init() {
            getBusinessHours();
        }

        /*
         * @desc - Close
         */
        function close() {
            $uibModalInstance.close();
        }

        /*
         * get Tutor Business Hours
         */
        function getBusinessHours() {
            var details = { tutor: $ctrl.wizarddetail.tutorId, type: $ctrl.subjectType };
            SearchService.getBusinessHours(details, function (result) {
                if (result.status === 200) {
                    _.forEach(result.data, function (v) {
                        $ctrl.backgroundEvents.push(_.extend({}, v, { rendering: 'background', color: '#acacac' }));
                    });
                } else {
                    console.log("errr" + result);
                }
            });
        }

        /*
         * get time buffer for booking slot
         */
        function getTimeBuffer() {
            return (!_.isUndefined($ctrl.wizarddetail.timebuffer)) ? $ctrl.wizarddetail.timebuffer : otherConstants.la_timebuffer;
        }

        function validateInterview() {
            if ($ctrl.checkInterview) {
                if (_.isUndefined($ctrl.interviewDate) || _.isUndefined($ctrl.interviewTime)) {
                    toastr.warning('Please input Interview details');
                    return false;
                } else {
                    return true;
                }
            } else {
                return true;
            }
        }

        /*
         * This function is rendered for datepicker
         */
        function beforeRender($view, $dates) {
            var min = moment().add(-1, $view).valueOf();
            for (var i = 0, len = $dates.length; i < len; i++) {
                $dates[i].selectable = ($dates[i].localDateValue() >= min);
            }
        }

        /*
         * This function is used to get the time availabilty fot Tutor Interview Schedule
         */
        function onTimeSet(newdate, olddate) {
            $ctrl.timerange = [];
            var currentDate = moment().toISOString();
            var currentDay = moment(currentDate).startOf('day').format('YYYY-MM-DD');
            var interviewDate = (_.isUndefined(newdate)) ? olddate : newdate;
            var interviewDay = moment(interviewDate).startOf('day').format('YYYY-MM-DD');
            interviewDate = moment(interviewDate).toISOString();
            var dayname = moment(interviewDate).weekday();
            var timeAvailability = [];

            _.forEach($ctrl.backgroundEvents, function (value) {
                if (dayname === value.dow[0]) {
                    timeAvailability.push(value); // In case multiple availabilities in a day
                }
            });

            // sort business hours on the basis of start time
            timeAvailability = _.sortBy(timeAvailability, [function (o) { return o.start; }]);

            if (!_.isEmpty(timeAvailability)) {

                var time = [];
                _.forEach(timeAvailability, function (value) {
                    var start = _.split(value.start, ':');
                    var end = _.split(value.end, ':');
                    var hours, mins;
                    var startHour = start[0];
                    var startMin = start[1];
                    var endHour = end[0];
                    var endMin = end[1];
                    var currentHour = moment(currentDate).startOf('hour').format('HH');
                    var currentMin = moment(currentDate).startOf('min').format('mm');

                    hours = _.range(startHour, endHour);
                    mins = [0, 15, 30, 45];

                    // To get max and min time of avaiability
                    var maxTime = moment(interviewDate).set({ 'hour': endHour, 'minute': endMin }).valueOf();
                    var minTime = moment(interviewDate).set({ 'hour': currentHour, 'minute': currentMin }).valueOf();

                    _.forEach(hours, function (value) {
                        _.forEach(mins, function (v) {
                            var hour = value + ":" + (_.padStart(v, 2, '0'));
                            var starttime = moment(interviewDate).set({ 'hour': value, 'minute': v });
                            var endtime = moment(starttime).add(1, 'hour');

                            // push to array till max end time only
                            if (currentDay === interviewDay) {
                                if (moment(starttime).valueOf() >= minTime && moment(endtime).valueOf() <= maxTime) {
                                    time.push({
                                        name: moment(starttime).format('hh:mm a') + ' - ' + moment(endtime).format('hh:mm a'),
                                        value: hour
                                    });
                                }
                            } else {
                                if (moment(endtime).valueOf() <= maxTime) {
                                    time.push({
                                        name: moment(starttime).format('hh:mm a') + ' - ' + moment(endtime).format('hh:mm a'),
                                        value: hour
                                    });
                                }
                            }

                        });
                    });
                    if (_.size(time) > 0) {
                        $ctrl.timerange = angular.copy(time);
                    } else {
                        toastr.warning('No Availability of Tutor on the selected day.');
                        return false;
                    }
                });
            } else {
                toastr.warning('No Availability of Tutor on the selected day.');
            }
        }

        /*
         * This function is used to add interview slots
         */
        function saveInterviewInfo() {
            if ($localStorage.currentUser && $localStorage.loginstatus) {
                if (_.isUndefined($ctrl.interviewDate) || _.isUndefined($ctrl.interviewTime)) {
                    toastr.warning('Please select date and time for interview');
                    return false;
                } else {
                    var calendar = 'eventCalendar';
                    var time = _.split($ctrl.interviewTime, ':', 2);
                    var interviewStart = moment($ctrl.interviewDate).hours(time[0]).minutes(time[1]);
                    var interviewEnd = moment(interviewStart).add(1, "hour");
                    var interviewValidity = isInterviewValid(interviewStart, interviewEnd, calendar);
                    if (interviewValidity.length > 0) {
                        toastr.info('There is already a booked slot between the time selected.');
                        return false;
                    } else {
                        $ctrl.interviewstart = interviewStart.toISOString();
                        $ctrl.interviewend = interviewEnd.toISOString();

                        var studentId = $localStorage.currentUser.id;
                        var bookingamount = 0;
                        var interviewschedule = {
                            "interviewstart": $ctrl.interviewstart,
                            "interviewend": $ctrl.interviewend,
                            "is_rescheduled": false,
                            "is_interview_done": false
                        };
                        var payment_info = { totalbookingamount: bookingamount, payment_status: 'Pending' };
                        var bookinginfo = {};

                        var details = {
                            tutor_id: $ctrl.wizarddetail.tutorId,
                            student_id: studentId,
                            subject_id: $ctrl.wizarddetail.subject.subjectId._id,
                            subject_type: $ctrl.wizarddetail.subject.subjectId.type,
                            level_id: $ctrl.wizarddetail.level.levelId._id,
                            is_interview_require: $ctrl.checkInterview,
                            interviewschedule: interviewschedule,
                            booking_info: bookinginfo,
                            payment_info: payment_info
                        };

                        SearchService.saveInterviewInfo(details, function (result) {
                            if (result.status === 200) {
                                if ($ctrl.checkInterview) {
                                    var bookingId = { booking_id: result.data };
                                    TeacherService.createinterviewsession(bookingId, function (result) {
                                        if (result.status === 200) {
                                            close();
                                            $state.go('learner.teachers');
                                            toastr.success('Interview was booked successfully.');
                                        }
                                    });
                                } else {
                                    close();
                                    $state.go('learner.home');
                                }
                            } else {
                                toastr.warning(result.message);
                                console.log("errr" + result);
                            }
                        });

                        //                    var interviewLength = $ctrl.interviewinfo.length;
                        //
                        //                    var eventData = {
                        //                        title: 'Interview',
                        //                        start: interviewStart.format(),
                        //                        end: interviewEnd.format(),
                        //                        allDay: false,
                        //                        overlap: false,
                        //                        stick: true,
                        //                        editable: false,
                        //                        type: 'interview'
                        //                    };
                        //                    var title = "You’re booking: " + moment(interviewStart).format('DD MMM YYYY, dddd');
                        //                    var text = '('+ moment(interviewStart).format('hh:mma') + ' to ' + moment(interviewEnd).format('hh:mma') +').'+ ' Confirm ?';
                        //                    
                        //                    if (interviewLength < 1) {
                        //                        swal({
                        //                            title: title,
                        //                            text: text,
                        //                            showCancelButton: true,
                        //                            confirmButtonColor: "#48b0e4",
                        //                            confirmButtonText: "Confirm",
                        //                            cancelButtonText: "Cancel",
                        //                            cancelButtonColor: "#ed4858",
                        //                            closeOnConfirm: false
                        //                        }, function (isConfirm) {
                        //                            if (isConfirm) {
                        //                                swal({
                        //                                    title: "",
                        //                                    text: "Pending tutor\'s acceptance",
                        //                                    confirmButtonText: "Sent",
                        //                                    confirmButtonColor: "#3ab557" 
                        //                                }, function(){
                        //                                    $timeout(function(){
                        //                                        uiCalendarConfig.calendars[calendar].fullCalendar('renderEvent', eventData, true);
                        //                                        $ctrl.interviewinfo.push(eventData);
                        //                                        $ctrl.interviewstart = interviewStart.toISOString();
                        //                                        $ctrl.interviewend = interviewEnd.toISOString();
                        //                                        toastr.success('Interview booked successfully.');
                        //                                    }, 200);
                        //                                });
                        //                            }
                        //                        });    
                        //                    } else {
                        //                        toastr.error('Interview is already booked.');
                        //                    }
                    }
                }

            } else {
                toastr.info('Please login as Learner to Book an Interview');
                modalopen('learnerlogin.html', 'md-modal', 'md', '');
            }

        }

        /*
         * Function to check whether interview event slot added is available or not
         */
        function isInterviewValid(start, end, calendar) {
            return uiCalendarConfig.calendars[calendar].fullCalendar('clientEvents', function (event) {
                if (Math.round(event.start) / 1000 < Math.round(end) / 1000 && Math.round(event.end) > Math.round(start)) {
                    return true;
                } else {
                    return false;
                }
            });
        }

        /*
         * This function is used to reset the Interview Slots
         */
        function resetInterview(calendar) {

            if (uiCalendarConfig.calendars[calendar]) {
                uiCalendarConfig.calendars[calendar].fullCalendar('clientEvents', function (event) {
                    if (event.title === 'Interview') {
                        uiCalendarConfig.calendars[calendar].fullCalendar('removeEvents', event._id);
                        $ctrl.interviewinfo = [];
                    }
                });
                // Reset Interview date and time 
                $ctrl.interviewDate = "";
                $ctrl.interviewTime = "";
                $ctrl.timerange = [];
            }
        }


        // private functions
        /*
         * Function to get objects of weeks in a particular month
         */
        //        function getWeeksInMonth(year, month) {
        //            var monthStart = moment().year(year).month(month).date(1);
        //            var monthEnd = moment().year(year).month(month).endOf('month');
        //            var numDaysInMonth = moment().year(year).month(month).endOf('month').date();
        //
        //            //calculate weeks in given month
        //            var weeks = Math.ceil((numDaysInMonth + monthStart.day()) / 7);
        //            var weekRange = [];
        //            var weekStart = moment().year(year).month(month).date(1);
        //            var i = 0, j = 1;
        //
        //            while (i < weeks) {
        //                var weekEnd = moment(weekStart);
        //
        //                if (weekEnd.endOf('week').date() <= numDaysInMonth && weekEnd.month() === month) {
        //                    weekEnd = weekEnd.endOf('week').format('LL');
        //                } else {
        //                    weekEnd = moment(monthEnd);
        //                    weekEnd = weekEnd.format('LL');
        //                }
        //
        //                weekRange.push({
        //                    'weekStart': weekStart.format('LL'),
        //                    'weekEnd': weekEnd,
        //                    'weekNo': 'Week' + ' ' + j,
        //                    'weekIndex': i
        //                });
        //                weekStart = weekStart.weekday(7);
        //                i++;
        //                j++;
        //            }
        //            return weekRange;
        //        }
        //
        //        function getMonthsInYear() {
        //            var months = moment.monthsShort();
        //            $ctrl.monthList = [];
        //            _.forEach(months, function (value, key) {
        //                var start = moment().month(value).startOf('month').format("LL");
        //                $ctrl.monthList.push({start: start, desc: value, monthIndex: key});
        //            });
        //        }

        // modal open
        function modalopen(template, windowClass, size, invitationId) {
            var udataobj = { 'invitationId': invitationId };
            var timer = $timeout(function () {
                var modalInstance = $uibModal.open({
                    animation: true,
                    ariaLabelledBy: 'modal-title',
                    ariaDescribedBy: 'modal-body',
                    templateUrl: '/app/layout/dialogs/' + template,
                    size: size,
                    controller: 'ModalInstanceController',
                    controllerAs: '$ctrl',
                    windowClass: windowClass,
                    resolve: {
                        uData: udataobj,
                        socialConstants: SocialService.getSocialConstantDetails()
                    }
                });

                modalInstance.opened.then(function () {
                    $timeout.cancel(timer);
                    delete $localStorage.learnermodalcheck;
                });
                modalInstance.result.then(function (response) {
                    if (!_.isUndefined(response) && response === 'success') {
                        saveInterviewInfo();
                    } else if ($localStorage.learnermodalcheck) {
                        whichmodalopen();
                    }
                }, function () {

                });
            }, 400);
        }

        // modal type
        function whichmodalopen() {
            if ($localStorage.learnermodalcheck && !$localStorage.loginstatus) {
                if ($localStorage.learnermodalcheck.type === 1) {
                    modalopen('learnerlogin.html', 'md-modal', 'md');
                }
                if ($localStorage.learnermodalcheck.type === 2) {
                    modalopen('learnersignup.html', 'tutormodal learnerModal', 'lg');
                }
                if ($localStorage.learnermodalcheck.type === 3) {
                    modalopen('forgotpassword.html', 'md-modal', 'md');
                }
            }
        }



    }

})();
(function () {
    'use strict';

    angular
        .module('la-app')
        .controller('AutofillSlotController', AutofillSlotController);
    AutofillSlotController.$inject = ['$localStorage','CalendarService', '$uibModalInstance', '$timeout', 'uiCalendarConfig', 'lessonData', 'SocialService'];
    function AutofillSlotController($localStorage,CalendarService,$uibModalInstance, $timeout, uiCalendarConfig, lessonData, SocialService) {
        var $ctrl = this;
        $ctrl.lessondesc = [];
        $ctrl.close = close;
        var studentId = $localStorage.currentUser.id;
        var tutorId = lessonData.tutorId;
        var postData = {
            student_id:studentId,
            tutor_id: tutorId,
            start:lessonData.currentEvent.start,
            end:lessonData.currentEvent.end
        };
        CalendarService.getAllBookedSlot(postData,function(data){
            var allEventsOnSameDay = data.data;
            console.log(allEventsOnSameDay);
            $ctrl.wizarddetail = lessonData;
            $ctrl.initialPackage = lessonData.initialPackage;
            $ctrl.lessonRange = lessonRange;
            $ctrl.selectedSlot = lessonData.currentEvent;
            let firstData = lessonData.currentEvent;
            let day = moment($ctrl.selectedSlot.start).format('ddd');
            $ctrl.params = lessonData.params;
            $ctrl.label = [];
            $ctrl.days = [];
            var days = [];
            var backgroundEvents = [];
            backgroundEvents = Object.assign(backgroundEvents, lessonData.backgroundEvents);
            var allDays = backgroundEvents.map(val => { return val.dow[0] });
            const allKeys = Object.keys(firstData);
            var timeFormat = 'HH:mm:ss';
            var noOfWeekToBeAdd = 0;
            
            for (let i = 0; i <= lessonData.initialPackage.number_lesson - lessonData.lessondesc.length; i++) {
                var startDateSlot = {},prevEventLength=0;
                let startDate = moment($ctrl.selectedSlot.start).add(noOfWeekToBeAdd, 'weeks');
                let endDate = moment($ctrl.selectedSlot.end).add(noOfWeekToBeAdd, 'weeks');
                var curStart = moment(moment(startDate).format(timeFormat),timeFormat);
                var curEnd = moment(moment(endDate).format(timeFormat),timeFormat);
                while(prevEventLength < allEventsOnSameDay.length){
                    var evt = allEventsOnSameDay[prevEventLength];
                    var evtStartDate = moment(evt.start).format('YYYY-MM-DD'); 
                    var evtStart = moment(moment(evt.start).format(timeFormat),timeFormat);
                    var evtEnd = moment(moment(evt.end).format(timeFormat),timeFormat);
                    //console.log('Date Is:',startDate.toISOString(),moment(evt.start).toISOString());
                    if(evtStartDate === startDate.format('YYYY-MM-DD')){
                        //console.log('Date Is Same:',evtStartDate);
                        if((curStart.isSameOrAfter(evtStart) && curStart.isSameOrBefore(evtEnd)) ){
                            if((curEnd.isAfter(evtStart)) )
                            {
                                noOfWeekToBeAdd = noOfWeekToBeAdd + 1;
                                startDate = moment(startDate).add(noOfWeekToBeAdd, 'weeks');
                                endDate = moment(endDate).add(noOfWeekToBeAdd, 'weeks');
                                noOfWeekToBeAdd = noOfWeekToBeAdd + 1;
                            }
                        }
                    }
                    prevEventLength++;
                }
                
                if ($.inArray(startDate.day(), allDays) != -1) {
                    allKeys.forEach(function (elem) {
                        startDateSlot[elem] = firstData[elem];
                    });
                    startDateSlot.start = startDate;//.format("YYYY-MM-DDTHH:mm:ss");
                    startDateSlot.end = endDate;//.format("YYYY-MM-DDTHH:mm:ss");
                    startDateSlot.id = 'lesson_' + (i + 1);
                    startDateSlot.day = startDate.format('ddd');
                    days.push(startDateSlot);
                    noOfWeekToBeAdd = noOfWeekToBeAdd + 1;
                }
    
            };
            $ctrl.days = days;
            for (let i = 0; i < $ctrl.days.length; i++) {
                if ($ctrl.days[i].day === day) {
                    $ctrl.days[i].start = $ctrl.selectedSlot.start;
                    $ctrl.days[i].end = $ctrl.selectedSlot.end;
                    $ctrl.days[i].default = true;
                    break;
                }
            }
        });
        $ctrl.saveTimeSlots = function () {
            let finalList = [];
            let checkedSlotes = $ctrl.days.filter(obj => obj.isChecked);
            checkedSlotes.forEach(function (elem, i) {
                let obj = Object.assign(elem, { id: "lesson_" + (i + 1) });
                finalList.push(obj);
            });
            $ctrl.finalList = finalList;
            $uibModalInstance.close($ctrl.finalList);
        };
        $ctrl.changeValue = function (elem, index) {
            let checkedSlotes = $ctrl.days.filter(obj => obj.isChecked);
            if ((checkedSlotes.length + lessonData.lessondesc.length) > lessonData.initialPackage.number_lesson) {
                elem.isChecked = false;
                alert('All lesson filled');
            }
        };
        function close() {
            $uibModalInstance.close(false);
        }
        function lessonRange(range) {
            return new Array(range);
        }
    };
})();
