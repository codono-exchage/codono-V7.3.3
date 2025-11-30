/*
*   https://www.jqueryscript.net/time-clock/Customizable-Dropdown-Birthday-Picker-Plugin-with-jQuery.html
*
* */
$(function ($) {
	var month = {
			"number": ["01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12"],
			"short": ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
			"long": ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"]
		},
		today = new Date(),
		todayYear = today.getFullYear(),
		todayMonth = today.getMonth() + 1,
		todayDay = today.getDate();
	calculateAge = function (birthday) { // birthday is a date
		var today = new Date();
		var birthDate = new Date(birthday);
		var age = today.getFullYear() - birthDate.getFullYear();
		var m = today.getMonth() - birthDate.getMonth();
		if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
			age--;
		}
		return age;
	};
	updateTheBirthDayValue = function (options, $selector, selectedYear, selectedMonth, selectedDay) {
		if ((selectedYear * selectedMonth * selectedDay) != 0) {
			if (selectedMonth < 10) selectedMonth = "0" + selectedMonth;
			if (selectedDay < 10) selectedDay = "0" + selectedDay;
			hiddenDate = selectedYear + "-" + selectedMonth + "-" + selectedDay;
			$selector.val(hiddenDate);
			if (options.callback) {
				options.callback(hiddenDate);
			}
		}
	};
	generateBirthdayPicker = function ($input, options) {
		var $container = $("<div/>").addClass("birthdatepicker_container");
		var $year = $("<select/>").addClass("birthdatepicker_year").addClass($input.attr("class"));
		var $month = $("<select/>").addClass("birthdatepicker_month").addClass($input.attr("class"));
		var $day = $("<select/>").addClass("birthdatepicker_day").addClass($input.attr("class"));
		$input.attr("type", "hidden").addClass("birthdatepicker_value");
		// Add the option placeholders if specified
		if (options.placeholder) {
			$("<option value='0'>" + translate_year + "</option>").appendTo($year);
			$("<option value='0'>" + translate_month + "</option>").appendTo($month);
			$("<option value='0'>" + translate_day + "</option>").appendTo($day);
		}
		if (options.dateFormat == "bigEndian") {
			$container.append($year).append($month).append($day);
		} else if (options.dateFormat == "littleEndian") {
			$container.append($day).append($month).append($year);
		} else {
			$container.append($month).append($day).append($year);
		}
		//var age = calculateAge(options.defaultDate);
		var lastBirthDay = new Date();
		lastBirthDay.setFullYear(todayYear - (options.maxAge + 1));
		lastBirthDay.setDate(lastBirthDay.getDate() - 1);
		//calculate the year to add to the select options.
		var yearBegin = todayYear - options.minAge;
		var yearEnd = lastBirthDay.getFullYear();
		if (options.maxYear != todayYear && options.maxYear > todayYear) {
			yearBegin = options.maxYear;
			yearEnd = yearEnd + (options.maxYear - todayYear)
		}
		for (var i = yearBegin; i >= yearEnd; i--) {
			$("<option/>").val(i).text(i).appendTo($year);
		}
		for (var i = 0; i <= 11; i++) {
			$("<option/>").val(i + 1).text(month[options.monthFormat][i]).appendTo($month);
		}
		for (var i = 1; i <= 31; i++) {
			var number = (i < 10) ? "0" + i : i;
			$("<option/>").val(i).text(number).appendTo($day);
		}
		$input.before($container);
		$container.append($input);
		// Set the default date if given
		if (options.defaultDate) {
			if ($.type(options.defaultDate) !== "date") {
				/*
				 * There is no concept of a pure date in js, only absolute timestamps.
				 * A call to `new Date(value)` with a `value` of a string will attempt
				 * to parse a datetime from that string into an absolute and localised
				 * timestamp. Depending on the client locale this can result in the
				 * defaultDate being misrepresented. To counter for this we add the
				 * locale timezone offset.
				 */
				var date = new Date(options.defaultDate);
				if(!options.ignoreTimeZone)
					date.setSeconds(date.getSeconds() + (date.getTimezoneOffset() * 60));
			} else {
				var date = options.defaultDate;
			}
			if(date.getFullYear()>yearBegin||date.getFullYear()<yearEnd){
				date.setFullYear(yearBegin);
			}
			$year.val(date.getFullYear());
			$month.val(date.getMonth() + 1);
			$day.val(date.getDate());
			updateTheBirthDayValue(options, $input, date.getFullYear(), date.getMonth() + 1, date.getDate());
		} else {
			updateTheBirthDayValue(options, $input, $year.val(), $month.val(), $day.val());
		}
		$container.on('change', function () {
			$birthday = $(this).find('.birthdatepicker_value');
			// currently selected values
			selectedYear = parseInt($year.val(), 10),
				selectedMonth = parseInt($month.val(), 10),
				selectedDay = parseInt($day.val(), 10);
			//rebuild the index for the month.
			var currentMaxMonth = $month.children(":last").val();
			if (selectedYear > todayYear) {
				if (currentMaxMonth > todayMonth) {
					while (currentMaxMonth > todayMonth) {
						$month.children(":last").remove();
						currentMaxMonth--;
					}
				}
			} else {
				while (currentMaxMonth < 12) {
					$("<option/>").val(parseInt(currentMaxMonth) + 1).text(month[options.monthFormat][currentMaxMonth]).appendTo($month);
					currentMaxMonth++;
				}
			}
			var currentMaxDate = $day.children(":last").val();
			// number of days in currently selected year/month
			var actMaxDay = (new Date(selectedYear, selectedMonth, 0)).getDate();
			if (currentMaxDate > actMaxDay) {
				while (currentMaxDate > actMaxDay) {
					$day.children(":last").remove();
					currentMaxDate--;
				}
			} else if (currentMaxDate < actMaxDay) {
				while (currentMaxDate < actMaxDay) {
					var dateIndex = parseInt(currentMaxDate) + 1;
					var number = (dateIndex < 10) ? "0" + dateIndex : dateIndex;
					$("<option/>").val(dateIndex).text(number).appendTo($day);
					currentMaxDate++;
				}
			}
			// update the hidden date
			updateTheBirthDayValue(options, $birthday, selectedYear, selectedMonth, selectedDay);
		});
	}
	$.fn.birthdayPicker = function (options) {
		return this.each(function () {
			var settings = $.extend($.fn.birthdayPicker.defaults, options);
			if ($(this).attr("data-min-age"))
				settings.minAge = $(this).data("min-age");
			if ($(this).attr("data-max-age"))
				settings.maxAge = $(this).data("max-age");
			if ($(this).attr("data-ignore-time-zone"))
				settings.ignoreTimeZone = $(this).data("ignore-time-zone");
			if ($(this).attr("placeholder"))
				settings.placeholder = $(this).attr("placeholder");
			if ($(this).val())
				settings.defaultDate = $(this).val();
			generateBirthdayPicker($(this), settings);
		});
	};
	$.fn.birthdayPicker.defaults = {
		"maxAge": 100,
		"minAge": 0,
		"maxYear": todayYear,
		"dateFormat": "littleEndian",
		"placeholder": "",
		"monthFormat": "number",
		"defaultDate": false,
		"ignoreTimeZone": true,
		'callback': false
	}
}(jQuery))

