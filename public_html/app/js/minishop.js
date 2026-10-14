/** minishop */
const Ms = {
  cart: {
    init() {
      fetch("/api/ms/cart/get")
        .then((response) => response.json())
        .then((response) => {
          this._updateStore(response.data);
          this._addEvent(response);
        });
    },
    set(id, count = 1) {
      fetch(`/api/ms/cart/set?id=${id}&count=${count}`)
        .then((response) => response.json())
        .then((response) => {
          this._viewMessages(response);
          this._updateHTML(response);
          this._updateStore(response.data.data.cart);
          this._addEvent(response);
        });
    },
    getProduct(id) {
      const store = this._store;
      for (k in store) {
        if (store[k]["id"] == id) {
          return store[k];
        }
      }
    },
    getStore() {
      return this._store;
    },
    _store: undefined,
    _updateStore(elements) {
      if (Object.entries(elements).length !== 0) {
        this._store = elements;
      } else {
        this._store = undefined;
      }
    },
    _updateHTML(response) {
      for (let i of this._classes) {
        for (let el of document.querySelectorAll(i.class)) {
          el.innerHTML = response.data.data[i.key];
        }
      }
    },
    _addEvent(response) {
      document.dispatchEvent(
        new CustomEvent("ms:cart-update", { detail: response })
      );
    },
    _viewMessages(response) {
      if (response.data.message) {
        response.data.success
          ? miniShop2.Message.success(response.data.message)
          : miniShop2.Message.error(response.data.message);
      }
    },
    _classes: [
      { class: ".ms2_total_weight", key: "total_weight" },
      { class: ".ms2_total_count", key: "total_count" },
      { class: ".ms2_total_cost", key: "total_cost" },
      { class: ".ms2_total_discount", key: "total_discount" },
    ],
  },
};

Ms.cart.init(); // обновим state

/** добавляем или убираем .is-added карточке товара, убираем .is-loading */
document.addEventListener("ms:cart-update", (e) => {
  if (!e.detail.data.id) return;
  const items = document.querySelectorAll(
    `.js-ms-item[data-id="${e.detail.data.id}"]`
  );

  items.forEach((item) => {
    if (e.detail.data.action == "remove") {
      item.classList.remove("is-added");
    } else {
      item.classList.add("is-added");
    }
    const isLoading = item.querySelector(".is-loading");
    if (isLoading) {
      isLoading.classList.remove("is-loading");
    }
  });
});
