const swiperThumbs = {};

const strToObj = (str) => {
  var obj = {};
  if (str && typeof str === "string") {
    var objStr = str.match(/\{(.)+\}/g);
    if (objStr) {
      try {
        eval("obj =" + objStr);
      } catch(e) {
        console.error('Error parsing options:', e);
      }
    }
  }
  return obj;
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
  // Пропускаем, если слайдер уже инициализирован
  if (el.swiper) {
    return;
  }
  
  let options = el.getAttribute("data-options");

  // Проверяем наличие swiper-wrapper
  let wrapper = el.querySelector('.swiper-wrapper');
  if (!wrapper) {
    const innerSlider = el.innerHTML;
    el.innerHTML = '<div class="swiper-wrapper"></div>';
    wrapper = el.querySelector(".swiper-wrapper");
    if (wrapper && innerSlider.trim()) {
      wrapper.insertAdjacentHTML("beforeend", innerSlider);
    }
  }

  // Обертка в sliderWrap только если родитель не sliderWrap
  const parentEl = el.parentElement;
  if (parentEl && !parentEl.classList.contains('sliderWrap')) {
    const wrapSlider = el.outerHTML;
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = wrapSlider;
    const newSlider = tempDiv.firstElementChild;
    
    const sliderWrap = document.createElement('div');
    sliderWrap.className = 'sliderWrap';
    parentEl.insertBefore(sliderWrap, el);
    sliderWrap.appendChild(newSlider);
    el.remove();
    el = newSlider;
  }

  if (options) {
    options = strToObj(options.replace(/\s/g, ""));
  } else {
    options = {};
  }

  // Настройки для grid
  if (el.classList.contains("grid")) {
    const col = getComputedStyle(el).getPropertyValue("--col");
    const gap = getComputedStyle(el).getPropertyValue("row-gap");

    if (!options.slidesPerView) {
      options.slidesPerView = col ? Number(col.replace(/\D/g, "")) : 1;
    }

    if (!options.spaceBetween) {
      options.spaceBetween = gap ? Number(gap.replace(/\D/g, "")) : 10;
    }

    if (!options.loop && options.slidesPerView > 1) {
      options.loop = true;
    }
  }

  // Отключаем loop если слайдов меньше чем slidesPerView
  const slides = el.querySelectorAll('.swiper-slide');
  if (slides.length < (options.slidesPerView || 1)) {
    options.loop = false;
  }

  // Пагинация
  if (el.classList.contains("pager") && !el.querySelector('.swiper-pagination')) {
    el.insertAdjacentHTML("beforeend", "<div class='swiper-pagination'></div>");

    if (!options.pagination) {
      options.pagination = {
        el: ".swiper-pagination",
        type: "bullets",
        clickable: true,
      };
    }
  }

  // Стрелки
  if (el.classList.contains("arrow") && !el.querySelector('.swiper-button-prev')) {
    const svg = el.getAttribute("data-svg") ? el.getAttribute("data-svg") : "";
    el.insertAdjacentHTML("beforeend", addArrSlider(svg));

    if (!options.navigation) {
      options.navigation = {
        nextEl: ".swiper-button-next",
        prevEl: ".swiper-button-prev",
      };
    }
  }
  
  options.observer = true;
  options.observeParents = true;
  
  const swiper = new Swiper(el, options);

  // Thumbs
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

  el.style.opacity = 1;
  swiperInstances.push(swiper);
};

// Инициализация слайдеров
const initSliders = (container = document) => {
  container.querySelectorAll(".swiper:not(.swiper-initialized)").forEach((el, key) => {
    createSlider(el, key);
  });
};

// При загрузке страницы
document.addEventListener("DOMContentLoaded", () => {
  initSliders();
});

// После AJAX обновления через htmx
document.addEventListener("htmx:afterSwap", (e) => {
  initSliders(e.target);
});

// После полной загрузки контента через htmx
document.addEventListener("htmx:afterSettle", (e) => {
  // Обновляем слайдеры в загруженном контенте
  const sliders = e.target.querySelectorAll(".swiper");
  sliders.forEach(slider => {
    if (slider.swiper) {
      slider.swiper.update();
    } else {
      createSlider(slider);
    }
  });
});

// Синхронизация thumbs
const initThumbs = () => {
  if (Object.entries(swiperThumbs).length !== 0) {
    for (let k in swiperThumbs) {
      if (swiperThumbs[k]["thumbOn"] && swiperThumbs[k]["thumbOf"]) {
        swiperThumbs[k]["thumbOf"].thumbs.swiper = swiperThumbs[k]["thumbOn"];
        swiperThumbs[k]["thumbOf"].thumbs.init();
      }
    }
  }
};

// Запускаем thumbs после всех инициализаций
setTimeout(initThumbs, 100);

function updateSwiperParams() {
  document.querySelectorAll(".swiper.swiper-initialized").forEach((el) => { 
    if (!el.swiper) return;
    
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
        el.swiper.params.spaceBetween = newSpaceBetween;
      }            
      el.swiper.update();
    }
  });
}

// Делаем updateSwiperParams доступной глобально
window.updateSwiperParams = updateSwiperParams;

// Обработчик изменения размера окна
let resizeTimeout;
window.addEventListener('resize', () => {
  clearTimeout(resizeTimeout);
  resizeTimeout = setTimeout(updateSwiperParams, 150);
});