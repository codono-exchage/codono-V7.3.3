// theme -
let themeHandlers = {};

const stateObj = {};

const update = {};

const handler = {
  set(target, prop, value) {
    // Check for change and perform actions
    if (target[prop] !== value) {
      target[prop] = value;
    }
    return true; // Allow the change to proceed
  },
};

const proxy = new Proxy(stateObj, handler);

(function () {
  const KEY = "theme";

  themeHandlers.getTheme = function () {
    if (typeof localStorage !== "undefined") return localStorage.getItem(KEY);
  };

  themeHandlers.setTheme = function (value) {
    // STORE THE DARK MODE ON THE HTML AS class
    // document.documentElement.dataset.mode = value
    proxy.themeVal = value;
    return localStorage.setItem(KEY, value);
  };

  themeHandlers.clearTheme = function () {
    return localStorage.removeItem(KEY);
  };

  themeHandlers.detectColorScheme = function () {
    proxy.themeVal = "light";
    return "light";
  };

  themeHandlers.setDefaultTheme = function () {
    // check system esisting theme
    if (!getTheme()) {
      document.documentElement.classList.add(detectColorScheme());
      return setTheme(detectColorScheme());
    }
    document.documentElement.classList.add(getTheme());
    return setTheme(getTheme());
  };

  const { getTheme, setTheme, clearTheme, detectColorScheme, setDefaultTheme } =
    themeHandlers;
  // GLOBAL
  function toggleThemeMode() {
    if (getTheme() === "dark") {
      setTheme("light");
      document.documentElement.classList.remove("dark");
      document.documentElement.classList.add("light");
    } else if (getTheme() === "light") {
      setTheme("dark");
      document.documentElement.classList.remove("light");
      document.documentElement.classList.add("dark");
    } else {
      clearTheme();
      setDefaultTheme();
    }

    window.scrollTo({
      top: window.scrollY - 1,
      behavior: "smooth",
    });
  }

  document
    .querySelectorAll(".mobile_navbar_theme")
    .forEach((el) => el.addEventListener("click", toggleThemeMode));

  window.onload = setDefaultTheme();
})();

// set class on scrollll
proxy.scroll = false;

// LOGO
const lightLogoScroll = document.querySelectorAll(".lightLogoScroll");
const darkLogoScroll = document.querySelectorAll(".darkLogoScroll");

// caret
const caret_scroll = document.querySelectorAll(".caret_scroll");
const navtextScroll = document.querySelector("#navtextScroll");

// side bar
const sidebar = document.querySelector("#sidebar");

const top_dropdown_scroll = document.querySelectorAll(".top_dropdown_scroll");
const top_dd_hover_scroll = document.querySelectorAll(".top_dd_hover_scroll");
const scrollTopDDiCON = document.querySelectorAll(".scrollTopDDiCON");

lightLogoScroll.forEach((el) => {
  el.style.cssText = "display: none";
});

if (window.scrollY > 50) {
  proxy.scroll = true;
   if(proxy.themeVal === "light"){
	   setTheme("dark")
   }
}
const handleScrollToggle = () => {
  if (window.scrollY > 50) {
    proxy.scroll = true;

  } else {
    proxy.scroll = false;

  }

  if (window.scrollY > 200 && proxy.themeVal === "light") {
    // switch theme where light = true, dark == false
    proxy.theme = true;


    lightLogoScroll.forEach((el) => {
      el.style.cssText = "display: block";
    });
    darkLogoScroll.forEach((el) => {
      el.style.cssText = "display: none";
    });

    caret_scroll.forEach((el) => {
      el.classList.remove("text-white");
      el.classList.add("text-dark");
    });

    sidebar.classList.remove("bg-dark");
    sidebar.classList.add("bg-white");

    top_dropdown_scroll.forEach((el) => {
      el.classList.remove("bg-[#272729]");
      el.classList.add("bg-white", 'text-dark');
    });

    top_dd_hover_scroll.forEach((el) => {
      el.classList.remove("hover:bg-dropdownHover");
      el.classList.add("hover:bg-[#272729]"); 
    });

    scrollTopDDiCON.forEach((el) => {
      el.classList.remove("text-subTitleColor"); 
      el.classList.add( "text-subTitleColor"); 
    });
  } else {
    proxy.theme = false;

    lightLogoScroll.forEach((el) => {
      el.style.cssText = "display: none";
    });
    darkLogoScroll.forEach((el) => {
      el.style.cssText = "display: block";
    });

    caret_scroll.forEach((el) => {
      el.classList.remove("text-dark");
      el.classList.add("text-white");
    });

    sidebar.classList.remove("bg-white");
    sidebar.classList.add("bg-dark");

    top_dropdown_scroll.forEach((el) => {
      el.classList.remove("bg-white", 'text-dark');
      el.classList.add("bg-[#272729]");
    });

    top_dd_hover_scroll.forEach((el) => {
      el.classList.remove("hover:bg-dropdownHover");
      el.classList.remove("hover:bg-[#272729]"); 
    });

    scrollTopDDiCON.forEach((el) => {
      el.classList.remove("bg-[#f5f5f5]", "text-subTitleColor");
      el.classList.add("text-subTitleColor", "bg-[#3a3a3c]"); 
    });
  }
};

window.addEventListener("scroll", handleScrollToggle);

// ACCORDION
const accordion = Array.from(document.querySelectorAll(".accordion"));
const accContent = accordion.map((el) => el.querySelector("ul"));
accordion.forEach((el) =>
  el.querySelector("h3").addEventListener("click", (el) => {
    // close all before opening any
    accContent.forEach((el) => el.classList.add("hidden"));
    el.target.closest("h3").nextElementSibling.classList.toggle("hidden");
  })
);

const sidebar_accordion = Array.from(
  document.querySelectorAll(".sidebar-accordion")
);
const sidebar_accordion_menu = sidebar_accordion.map((el) =>
  el.querySelector(".sidebar-accordion-menu")
);
sidebar_accordion.forEach((el) => {
  el.querySelector(".sidebar-accordion-menu")
  .classList.toggle("hidden");
})
sidebar_accordion.forEach((el) =>
  el.addEventListener("click", (el) => {
    el.target
      .closest(".sidebar-accordion")
      .querySelector(".sidebar-accordion-menu")
      .classList.toggle("hidden");
  })
);

// countdown
// get doms
const getDaysId_top = document.querySelector(
  "#flip-card-days > div > span > .flip-card__top"
);
const getDaysId_bottom_dataset = document.querySelector(
  "#flip-card-days > div > span > .flip-card__bottom"
);

const getHoursId_top = document.querySelector(
  "#flip-card-hours > div > span > .flip-card__top"
);
const getHoursId_bottom_dataset = document.querySelector(
  "#flip-card-hours > div > span > .flip-card__bottom"
);

const getMinsId_top = document.querySelector(
  "#flip-card-minutes > div > span > .flip-card__top"
);
const getMinsId_bottom_dataset = document.querySelector(
  "#flip-card-minutes > div > span > .flip-card__bottom"
);

const getSecId_top = document.querySelector(
  "#flip-card-seconds > div > span > .flip-card__top"
);
const getSecId_bottom_dataset = document.querySelector(
  "#flip-card-seconds > div > span > .flip-card__bottom"
);

// Set the date we're counting down to
var countDownDate = new Date("Wed May 21 2024 10:56:49").getTime();

// Update the count down every 1 second
var x = setInterval(function () {
  // Get today's date and time
  var now = new Date().getTime();

  // Find the distance between now and the count down date
  var distance = countDownDate - now;

  // Time calculations for days, hours, minutes and seconds
  var days = Math.floor(distance / (1000 * 60 * 60 * 24));
  var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
  var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
  var seconds = Math.floor((distance % (1000 * 60)) / 1000);

  // Display the result in the element with id="demo"
  // document.getElementById("demo").innerHTML = days + "d " + hours + "h "
  // + minutes + "m " + seconds + "s ";

  getDaysId_top.textContent = days < 10 ? `0${days}` : days;
  getDaysId_bottom_dataset.dataset.value = days < 10 ? `0${days}` : days;

  getHoursId_top.textContent = hours < 10 ? `0${hours}` : hours;
  getHoursId_bottom_dataset.dataset.value = hours < 10 ? `0${hours}` : hours;

  getMinsId_top.textContent = minutes < 10 ? `0${minutes}` : minutes;
  getMinsId_bottom_dataset.dataset.value =
    minutes < 10 ? `0${minutes}` : minutes;

  getSecId_top.textContent = seconds < 10 ? `0${seconds}` : seconds;
  getSecId_bottom_dataset.dataset.value =
    seconds < 10 ? `0${seconds}` : seconds;

  // If the count down is finished, write some text
  if (distance < 0) {
    clearInterval(x);
    document.getElementById("demo").innerHTML = "EXPIRED";
  }
}, 1000);

