const swiperThumbs = {};

const strToObj = (str) => {
  var obj = {};
  if (str && typeof str === "string") {
    var objStr = str.match(/\{(.)+\}/g);
    eval("obj =" + objStr);
  }
  return obj; // вернем преобразованный из строки объект
};

const addArrSlider = (svg) => {
  if (svg) {
    return `<div class='swiper-button swiper-button-prev svg'>${svg}</div><div class='swiper-button swiper-button-next svg'>${svg}</div>`;
  } else {
    return "<div class='swiper-button swiper-button-prev'></div><div class='swiper-button swiper-button-next'></div>";
  }
};
swiperInstances = [];
const createSlider = (el, key) => {
  let options = el.getAttribute("data-options");

  // добавим swiper-wrapper
  const innerSlider = el.innerHTML;
  el.innerHTML = '<div class="swiper-wrapper"></div>';
  el.querySelector(".swiper-wrapper").insertAdjacentHTML(
    "beforeend",
    innerSlider
  );

  // обернем swiper в элемент sliderWrap (для того что бы навигацию можно было выносить за обертку самого swiper)
  const wrapSlider = el.outerHTML;
  const parentEl = el.parentElement;
  el.outerHTML = '<div class="sliderWrap"></div>';
  parentEl
    .querySelector(".sliderWrap")
    .insertAdjacentHTML("beforeend", wrapSlider);
  el = parentEl.querySelector(".swiper");

  if (options) {
    options = strToObj(options.replace(/\s/g, ""));
  } else {
    options = {};
  }

  // если на swiper установлен класс grid то выведем кол-во слайдов и отступы из grid
  // но только в том случае, если сюда уже не были переданы параметры из чанка
  if (el.classList.contains("grid")) {
    const col = getComputedStyle(el).getPropertyValue("--col");
    const gap = getComputedStyle(el).getPropertyValue("row-gap");

    if (!options.slidesPerView) {
      options.slidesPerView = col ? Number(col.replace(/\D/g, "")) : 1;
    }

    if (!options.spaceBetween) {
      options.spaceBetween = gap ? Number(gap.replace(/\D/g, "")) : 10;
    }

    // swiper так адекватно работает только на 8 версии (и то с оговорками)
    if (!options.loop) {
      options.loop = true;
    }
  }

  // доработка swiper 8
  const slides = el.querySelectorAll('.swiper-slide');
  if (slides.length < options.slidesPerView || slides.length == 1 && !options.slidesPerView) {
    options.loop = false;
  }

  // пагинация
  if (el.classList.contains("pager")) {
    el.insertAdjacentHTML("beforeend", "<div class='swiper-pagination'></div>");

    // если параметры не пришли из чанка
    if (!options.pagination) {
      options.pagination = {
        el: ".swiper-pagination",
        type: "bullets",
        clickable: true,
      };
    }
  }

  // стрелки
  if (el.classList.contains("arrow")) {
    const svg = el.getAttribute("data-svg") ? el.getAttribute("data-svg") : "";
    el.insertAdjacentHTML("beforeend", addArrSlider(svg));

    // если параметры не пришли из чанка
    if (!options.navigation) {
      options.navigation = {
        nextEl: ".swiper-button-next",
        prevEl: ".swiper-button-prev",
      };
    }
  }
  options.observer = true;
  
    
  const swiper = new Swiper(el, options ? options : {}); // инициализируем каждый слайдер

  // thumb (добавим элементы thumb в объекты swiperThumbsOf и swiperThumbsOn, присвоим уникальные ключи)
  if (el.getAttribute("data-thumbs-of")) {
    const thumbId = el.getAttribute("data-thumbs-of");
    if (typeof swiperThumbs[thumbId] != "object") {
      swiperThumbs[thumbId] = {};
    }
    swiperThumbs[thumbId]["thumbOf"] = swiper;
  }
  if (el.getAttribute("data-thumbs-on")) {
    const thumbId = el.getAttribute("data-thumbs-on");
    if (typeof swiperThumbs[thumbId] != "object") {
      swiperThumbs[thumbId] = {};
    }
    swiperThumbs[thumbId]["thumbOn"] = swiper;
  }

  el.style.opacity = 1; // когда слайдер готов, показываем его
  swiperInstances.push(swiper);
};

// создадим слайдеры при первой загрузке страницы
document.querySelectorAll(".swiper").forEach((el, key) => {
  createSlider(el, key);
});


// пересоздадим слайдеры в элементе, который был перезагружен аяксом
document.addEventListener("htmx:afterSwap", (e) => {
  e.target.querySelectorAll(".swiper").forEach((el, key) => {
    createSlider(el, key);
  });
});

// thumbs (синхранизируем все swiper thumbs между собой)
if (Object.entries(swiperThumbs).length !== 0) {
  for (let k in swiperThumbs) {
    if (swiperThumbs[k]["thumbOn"]) {
      swiperThumbs[k]["thumbOf"].thumbs.swiper = swiperThumbs[k]["thumbOn"];
      swiperThumbs[k]["thumbOf"].thumbs.init();
    }
  }
}



function updateSwiperParams() {
  document.querySelectorAll(".swiper").forEach((el, key) => { 
    let options = el.getAttribute("data-options"); 
    if (options) {
        options = strToObj(options.replace(/\s/g, ""));
    } else {
        options = {};
    }    
    if (el.classList.contains("grid")) {
        const col = getComputedStyle(el).getPropertyValue("--col");
        const gap = getComputedStyle(el).getPropertyValue("row-gap");  
        const newSlidesPerView = col ? Number(col.replace(/\D/g, "")) : 1;
        const newSpaceBetween = gap ? Number(gap.replace(/\D/g, "")) : 10; 
        if (!options.slidesPerView) {
            el.swiper.params.slidesPerView = newSlidesPerView;
        }
        if (!options.spaceBetween) {
            options.spaceBetween = newSpaceBetween;
        }            
        el.swiper.update();
    }
  });
}
// Обработчик изменения размера окна
window.addEventListener('resize', updateSwiperParams)

