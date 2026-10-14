"use strict";

function yaMapsOnLoad() {
  document.dispatchEvent(new CustomEvent("ya-maps:load"));
}

const YaMaps = {
  init() {
    document.querySelectorAll("[data-map]").forEach((el) => {
      try {
        const points = JSON.parse(el.dataset.map);
        points && this.mapInit(el, points);
        
      } catch (e) {
        alert("Яндекс карты: некорректный json в data-map");
      }
    });
  },

  loadScript() {
      
    if (!this.scriptLoaded) {
      this.scriptLoaded = true;
      let s = document.createElement("script");
      s.setAttribute(
        "src",
        "https://api-maps.yandex.ru/2.1/?lang=ru_RU&onload=yaMapsOnLoad"
      );
      
      document.body.appendChild(s);
    }
  },

  mapInit(el, points) {
    this.loadScript();
    el.classList.add("is-loading");
    

    document.addEventListener("ya-maps:load", () => {
      const clusterer = new ymaps.Clusterer();
      const myGeoObjects = [];

      const myMap = new ymaps.Map(el, {
        center: points[0].coords.split(","),
        zoom: el.dataset.mapZoom || 14,
        controls: ["zoomControl"],
      });

      for (let i of points) {
        // если есть иконка то добавим ее, иначе буждет добавлена стандартная иконка
        let iconObj;
        if (i.icon) {
          iconObj = {
            iconImageHref: `assets/mgr/${i.icon}`,
            iconLayout: "default#image",
            iconImageSize: [
              i.iconSize ? i.iconSize : 50,
              i.iconSize ? i.iconSize : 50,
            ],
            iconImageOffset: [
              i.iconSize ? -(i.iconSize / 2) : -25,
              i.iconSize ? -i.iconSize : -50,
            ],
          };
        } else {
          iconObj = {
            iconColor: "#" + (i.color ? i.color : "0000ff"),
          };
        }

        myGeoObjects.push(
          new ymaps.GeoObject(
            {
              geometry: { type: "Point", coordinates: i.coords.split(",") },
              properties: { balloonContent: i.text || "" },
            },
            iconObj
          )
        );
      }

      clusterer.add(myGeoObjects);
      myMap.geoObjects.add(clusterer);

      // myMap.behaviors.disable("scrollZoom");
      if ("ontouchstart" in document.documentElement) {
        myMap.behaviors.disable("drag");
      }
      points.length > 1 &&
        myMap.setBounds(myMap.geoObjects.getBounds(), { zoomMargin: 40 });

      if (el.dataset.mapOffset) {
        const position = myMap.getGlobalPixelCenter();
        if (window.matchMedia("(min-width: 992px)").matches) {
          myMap.setGlobalPixelCenter([
            position[0] - +el.dataset.mapOffset,
            position[1],
          ]);
        }
      }

      el.classList.remove("is-loading");
    });
  },
};

YaMaps.init();
