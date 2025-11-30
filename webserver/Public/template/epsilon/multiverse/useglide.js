new Glide(".glide", {
  type: "carousel",
  focusAt: "center",
  autoplay: 2000,
  perView: 3,
  breakpoints: {
    1024: {
      perView: 2
    },
    600: {
      perView: 1
    }
  }
}).mount()


new Glide(".swiper", {
  type: "carousel",
  focusAt: "center",
  autoplay: 5000,
  perView: 1,
}).mount()



