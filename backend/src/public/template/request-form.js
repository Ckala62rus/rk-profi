(function () {
  var OPEN_CLASS = "is-open";
  var BODY_LOCK_CLASS = "popup-open";
  var BODY_BLUR_CLASS = "popup-blur-active";
  var INVALID_CLASS = "is-invalid";
  var DRAGOVER_CLASS = "is-dragover";
  var HAS_FILES_CLASS = "has-files";
  var PREVIEW_HOSTS = ["127.0.0.1", "localhost"];
  var PREVIEW_PORTS = ["8081"];
  var ENDPOINT = "/request-form-handler.php";
  var MAX_FILE_SIZE = 10 * 1024 * 1024;
  var FOCUSABLE_SELECTOR = [
    "a[href]",
    "button:not([disabled])",
    "textarea:not([disabled])",
    "input:not([disabled]):not([type='hidden'])",
    "select:not([disabled])",
    "[tabindex]:not([tabindex='-1'])"
  ].join(",");

  function createMarkup() {
    return (
      '<button class="request-fab btn btn--primary" type="button" aria-haspopup="dialog" aria-controls="request-modal">' +
        '<span class="request-fab__pulse" aria-hidden="true"></span>' +
        '<span class="request-fab__label">Отправить заявку</span>' +
      "</button>" +
      '<div class="request-modal" id="request-modal" aria-hidden="true">' +
        '<div class="request-modal__backdrop" data-request-close="true"></div>' +
        '<div class="request-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="request-modal-title">' +
          '<div class="request-modal__inner">' +
            '<div class="request-modal__top">' +
              '<div class="request-modal__head">' +
                '<p class="request-modal__eyebrow">Связь с производством</p>' +
                '<h2 class="request-modal__title" id="request-modal-title">Отправить заявку</h2>' +
              "</div>" +
              '<button class="request-modal__close btn btn--secondary btn--square" type="button" aria-label="Закрыть форму" data-request-close="true">×</button>' +
            "</div>" +
            '<form class="request-form" novalidate enctype="multipart/form-data">' +
              '<div class="request-form__grid">' +
                '<label class="request-form__field" data-field="name">' +
                  '<span class="request-form__label">Ваше имя</span>' +
                  '<input class="request-form__input" type="text" name="name" placeholder="Как к вам обращаться" required>' +
                  '<span class="request-form__field-error" data-error-for="name" aria-live="polite"></span>' +
                "</label>" +
                '<label class="request-form__field" data-field="phone">' +
                  '<span class="request-form__label">Телефон</span>' +
                  '<input class="request-form__input" type="tel" name="phone" placeholder="+7 900 000-00-00" required>' +
                  '<span class="request-form__field-error" data-error-for="phone" aria-live="polite"></span>' +
                "</label>" +
                '<label class="request-form__field request-form__field--email" data-field="email">' +
                  '<span class="request-form__label">Почта</span>' +
                  '<input class="request-form__input" type="email" name="email" placeholder="info@company.ru" required>' +
                  '<span class="request-form__field-error" data-error-for="email" aria-live="polite"></span>' +
                "</label>" +
                '<label class="request-form__field request-form__field--full" data-field="message">' +
                  '<span class="request-form__label">Описание заявки</span>' +
                  '<textarea class="request-form__textarea" name="message" placeholder="Что нужно изготовить, объем, сроки, особые требования" required></textarea>' +
                  '<span class="request-form__field-error" data-error-for="message" aria-live="polite"></span>' +
                "</label>" +
                '<div class="request-form__documents request-form__field--full">' +
                  '<div class="request-form__field request-form__field--file" data-field="attachment">' +
                    '<span class="request-form__label">Материалы к заявке</span>' +
                    '<div class="request-form__file" data-upload-zone="attachment">' +
                      '<input class="request-form__file-input" id="request-file" type="file" name="attachment[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.txt,.rtf,.odt,.ods,.odp,.odg,.odf,.jpg,.jpeg,.png,.webp,.zip,.rar,.7z,.dwg,.dxf">' +
                      '<div class="request-form__file-shell" role="button" tabindex="0" aria-labelledby="request-file-title request-file-hint">' +
                        '<div class="request-form__file-main">' +
                          '<div class="request-form__file-copy">' +
                            '<div class="request-form__file-title" id="request-file-title">Нажмите или перетащите файл</div>' +
                            '<div class="request-form__file-value" id="request-file-hint" data-file-hint="attachment">Документы, PDF и архивы до 10 МБ</div>' +
                          "</div>" +
                        "</div>" +
                        '<div class="request-form__file-list" data-file-list="attachment" aria-live="polite"></div>' +
                      "</div>" +
                      '<span class="request-form__field-error" data-error-for="attachment" aria-live="polite"></span>' +
                    "</div>" +
                  "</div>" +
                  '<div class="request-form__field request-form__field--file" data-field="company_card">' +
                    '<span class="request-form__label">Карточка предприятия</span>' +
                    '<div class="request-form__file" data-upload-zone="company_card">' +
                      '<input class="request-form__file-input" id="request-company-card" type="file" name="company_card[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.txt,.rtf,.odt,.ods,.odp,.odg,.odf,.jpg,.jpeg,.png,.webp,.zip,.rar,.7z">' +
                      '<div class="request-form__file-shell" role="button" tabindex="0" aria-labelledby="request-company-card-title request-company-card-hint">' +
                        '<div class="request-form__file-main">' +
                          '<div class="request-form__file-copy">' +
                            '<div class="request-form__file-title" id="request-company-card-title">Нажмите или перетащите файл</div>' +
                            '<div class="request-form__file-value" id="request-company-card-hint" data-file-hint="company_card">Документы и архивы до 10 МБ</div>' +
                          "</div>" +
                        "</div>" +
                        '<div class="request-form__file-list" data-file-list="company_card" aria-live="polite"></div>' +
                      "</div>" +
                      '<span class="request-form__field-error" data-error-for="company_card" aria-live="polite"></span>' +
                    "</div>" +
                  "</div>" +
                "</div>" +
              "</div>" +
              '<label class="request-form__honeypot" aria-hidden="true">Сайт<input type="text" name="company_site" tabindex="-1" autocomplete="off"></label>' +
              '<div class="request-form__footer">' +
                '<p class="request-form__status" data-status="true" aria-live="polite"></p>' +
                '<button class="request-form__submit btn btn--primary btn--lg" type="submit">Отправить заявку</button>' +
              "</div>" +
            "</form>" +
          "</div>" +
        "</div>" +
      "</div>" +
      '<div class="request-modal request-modal--success" id="request-success" aria-hidden="true">' +
        '<div class="request-modal__backdrop" data-success-close="true"></div>' +
        '<div class="request-modal__dialog request-modal__dialog--success" role="dialog" aria-modal="true" aria-labelledby="request-success-title">' +
          '<div class="request-modal__inner request-modal__inner--success">' +
            '<div class="request-success">' +
              '<p class="request-modal__eyebrow">Заявка отправлена</p>' +
              '<p class="request-modal__desc request-modal__desc--success" id="request-success-title">С вами свяжется менеджер.</p>' +
              '<button class="request-success__button btn btn--primary btn--lg" type="button" data-success-close="true">Хорошо</button>' +
            "</div>" +
          "</div>" +
        "</div>" +
      "</div>"
    );
  }

  function isPreviewMode() {
    if (window.location.protocol === "file:") {
      return true;
    }
    var host = window.location.hostname;
    var port = window.location.port || (window.location.protocol === "https:" ? "443" : "80");
    if (PREVIEW_HOSTS.indexOf(host) !== -1 && PREVIEW_PORTS.indexOf(port) !== -1) {
      return true;
    }
    /* Локальная копия без /request-form-handler.php (OSPanel, OpenServer, др.) */
    if (host === "localhost" || host === "127.0.0.1") {
      return true;
    }
    return false;
  }

  function getFocusable(container) {
    return Array.prototype.slice.call(container.querySelectorAll(FOCUSABLE_SELECTOR)).filter(function (element) {
      return !element.hasAttribute("hidden") && element.offsetParent !== null;
    });
  }

  function clearStatus(statusNode) {
    statusNode.textContent = "";
    statusNode.classList.remove("is-success", "is-error");
  }

  function setStatus(statusNode, text, type) {
    statusNode.textContent = text || "";
    statusNode.classList.remove("is-success", "is-error");

    if (type) {
      statusNode.classList.add(type === "success" ? "is-success" : "is-error");
    }
  }

  function getYandexMetrikaId() {
    if (window.YANDEX_METRIKA_ID) {
      return window.YANDEX_METRIKA_ID;
    }

    var inlineScript = Array.prototype.find.call(document.scripts, function (script) {
      return /ym\((['"])?\d+\1,\s*['"]init['"]/.test(script.textContent || "");
    });

    if (!inlineScript) {
      return null;
    }

    var match = (inlineScript.textContent || "").match(/ym\((['"])?(\d+)\1,\s*['"]init['"]/);
    return match ? match[2] : null;
  }

  function emitSuccessfulRequestEvent(payload) {
    var eventPayload = payload || {};
    var metrikaId = getYandexMetrikaId();

    window.dispatchEvent(new CustomEvent("requestFormSuccess", {
      detail: eventPayload
    }));

    if (Array.isArray(window.dataLayer)) {
      window.dataLayer.push({
        event: "request_form_success",
        form_name: "request_form",
        form_location: "floating_modal",
        contact_type: eventPayload.contactType || "",
        has_attachment: !!eventPayload.hasAttachment
      });
    }

    if (typeof window.ym === "function" && metrikaId) {
      try {
        window.ym(metrikaId, "reachGoal", "request_form_success", {
          contact_type: eventPayload.contactType || "",
          has_attachment: !!eventPayload.hasAttachment
        });
      } catch (error) {
        // Ignore metric runtime errors to avoid breaking the successful UX flow.
      }
    }
  }

  function createDataTransfer(files) {
    var transfer = new DataTransfer();
    files.forEach(function (file) {
      transfer.items.add(file);
    });
    return transfer;
  }

  function getInputFiles(input) {
    return Array.prototype.slice.call(input.files || []);
  }

  function setInputFiles(input, files) {
    input.files = createDataTransfer(files).files;
  }

  function mergeFiles(currentFiles, incomingFiles) {
    var fileMap = {};

    currentFiles.concat(incomingFiles).forEach(function (file) {
      var key = [file.name, file.size, file.lastModified, file.type].join("::");
      fileMap[key] = file;
    });

    return Object.keys(fileMap).map(function (key) {
      return fileMap[key];
    });
  }

  function formatFileSize(bytes) {
    if (!bytes) {
      return "0 КБ";
    }

    if (bytes >= 1024 * 1024) {
      return (bytes / (1024 * 1024)).toFixed(1).replace(".0", "") + " МБ";
    }

    return Math.max(1, Math.round(bytes / 1024)) + " КБ";
  }

  function renderFileList(slot) {
    var files = slot.files.slice();
    slot.list.innerHTML = "";
    slot.zone.classList.toggle(HAS_FILES_CLASS, files.length > 0);
    slot.hint.textContent = files.length
      ? ("Загружено файлов: " + files.length)
      : slot.emptyHint;

    if (!files.length) {
      return;
    }

    files.forEach(function (file, index) {
      var item = document.createElement("div");
      item.className = "request-form__file-chip";

      var state = document.createElement("span");
      state.className = "request-form__file-chip-state";
      state.setAttribute("aria-hidden", "true");

      var meta = document.createElement("div");
      meta.className = "request-form__file-chip-copy";

      var name = document.createElement("span");
      name.className = "request-form__file-chip-name";
      name.textContent = file.name;

      var size = document.createElement("span");
      size.className = "request-form__file-chip-size";
      size.textContent = formatFileSize(file.size);

      meta.appendChild(name);
      meta.appendChild(size);

      var remove = document.createElement("button");
      remove.type = "button";
      remove.className = "request-form__file-chip-remove";
      remove.textContent = "×";
      remove.setAttribute("aria-label", "Удалить " + file.name);
      remove.setAttribute("data-file-index", String(index));

      item.appendChild(state);
      item.appendChild(meta);
      item.appendChild(remove);
      slot.list.appendChild(item);
    });
  }

  function addFilesToInput(input, files) {
    setInputFiles(input, files);
  }

  function removeFileAtIndex(slot, index) {
    var files = slot.files.slice();
    files.splice(index, 1);
    slot.files = files;
    setInputFiles(slot.input, files);
  }

  function getValidationErrors(form) {
    var errors = {};
    var name = form.elements.name.value.trim();
    var phone = form.elements.phone.value.trim();
    var email = form.elements.email.value.trim();
    var message = form.elements.message.value.trim();
    var attachmentFiles = getInputFiles(form.querySelector("#request-file"));
    var companyCardFiles = getInputFiles(form.querySelector("#request-company-card"));

    function validateFiles(files, fieldName) {
      var oversized = files.some(function (file) {
        return file.size > MAX_FILE_SIZE;
      });

      if (oversized) {
        errors[fieldName] = "Файл слишком большой. Максимум 10 МБ на один файл.";
      }
    }

    if (name.length < 2) {
      errors.name = "Укажите имя.";
    }

    if ((phone.replace(/\D/g, "")).length < 10) {
      errors.phone = "Укажите телефон.";
    }

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      errors.email = "Укажите корректную почту.";
    }

    if (message.length < 10) {
      errors.message = "Добавьте короткое описание заявки.";
    }

    validateFiles(attachmentFiles, "attachment");
    validateFiles(companyCardFiles, "company_card");

    return errors;
  }

  function attachRequestForm() {
    /* Сохранённая страница shmz уже содержит .request-fab и модалку в HTML.
       Раньше скрипт выходил сразу и обработчики не вешались. */
    if (!document.querySelector(".request-fab")) {
      document.body.insertAdjacentHTML("beforeend", createMarkup());
    }

    var trigger = document.querySelector(".request-fab");
    var requestModal = document.querySelector("#request-modal");
    var successModal = document.querySelector("#request-success");
    if (!trigger || !requestModal || !successModal) {
      return;
    }
    var requestDialog = requestModal.querySelector(".request-modal__dialog");
    var successDialog = successModal.querySelector(".request-modal__dialog");
    var form = requestModal.querySelector(".request-form");
    if (!form) {
      return;
    }
    var statusNode = form.querySelector("[data-status='true']");
    var submitButton = form.querySelector(".request-form__submit");
    var lastActiveElement = null;
    var activeModal = null;
    var scrollLockDepth = 0;

    var fileSlots = {
      attachment: {
        key: "attachment",
        input: form.querySelector("#request-file"),
        zone: form.querySelector("[data-upload-zone='attachment']"),
        shell: form.querySelector("[data-upload-zone='attachment'] .request-form__file-shell"),
        list: form.querySelector("[data-file-list='attachment']"),
        hint: form.querySelector("[data-file-hint='attachment']"),
        emptyHint: "Документы, PDF и архивы до 10 МБ",
        files: []
      },
      company_card: {
        key: "company_card",
        input: form.querySelector("#request-company-card"),
        zone: form.querySelector("[data-upload-zone='company_card']"),
        shell: form.querySelector("[data-upload-zone='company_card'] .request-form__file-shell"),
        list: form.querySelector("[data-file-list='company_card']"),
        hint: form.querySelector("[data-file-hint='company_card']"),
        emptyHint: "Документы и архивы до 10 МБ",
        files: []
      }
    };

    function lockPageScroll() {
      var scrollbarOffset = window.innerWidth - document.documentElement.clientWidth;

      if (scrollLockDepth === 0) {
        document.body.style.setProperty("--request-scrollbar-offset", scrollbarOffset + "px");
        document.body.classList.add(BODY_LOCK_CLASS);
        document.body.classList.add(BODY_BLUR_CLASS);
      }

      scrollLockDepth += 1;
    }

    function unlockPageScroll() {
      scrollLockDepth = Math.max(0, scrollLockDepth - 1);

      if (scrollLockDepth === 0) {
        document.body.classList.remove(BODY_LOCK_CLASS);
        document.body.classList.remove(BODY_BLUR_CLASS);
        document.body.style.removeProperty("--request-scrollbar-offset");
      }
    }

    function openModal(modal, dialog, source, focusTarget) {
      lastActiveElement = source || document.activeElement;
      activeModal = modal;
      modal.classList.add(OPEN_CLASS);
      modal.setAttribute("aria-hidden", "false");
      lockPageScroll();

      window.setTimeout(function () {
        (focusTarget || dialog).focus();
      }, 20);
    }

    function closeModal(modal) {
      modal.classList.remove(OPEN_CLASS);
      modal.setAttribute("aria-hidden", "true");

      if (activeModal === modal) {
        activeModal = null;
      }

      if (!requestModal.classList.contains(OPEN_CLASS) && !successModal.classList.contains(OPEN_CLASS)) {
        unlockPageScroll();

        if (lastActiveElement && typeof lastActiveElement.focus === "function") {
          lastActiveElement.focus();
        }
      }
    }

    function openRequestModal(source) {
      openModal(requestModal, requestDialog, source, form.elements.name);
    }

    function openSuccessModal() {
      openModal(successModal, successDialog, lastActiveElement, successModal.querySelector(".request-success__button"));
    }

    function clearFieldError(fieldName) {
      var field = form.querySelector('[data-field="' + fieldName + '"]');
      var errorNode = form.querySelector('[data-error-for="' + fieldName + '"]');

      if (field) {
        field.classList.remove(INVALID_CLASS);
      }

      if (errorNode) {
        errorNode.textContent = "";
      }
    }

    function showFieldError(fieldName, message) {
      var field = form.querySelector('[data-field="' + fieldName + '"]');
      var errorNode = form.querySelector('[data-error-for="' + fieldName + '"]');

      if (field) {
        field.classList.add(INVALID_CLASS);
      }

      if (errorNode) {
        errorNode.textContent = message;
      }
    }

    function clearAllErrors() {
      ["name", "phone", "email", "message", "attachment", "company_card"].forEach(clearFieldError);
      clearStatus(statusNode);
    }

    function applyValidationErrors(errors) {
      clearAllErrors();

      Object.keys(errors).forEach(function (key) {
        showFieldError(key, errors[key]);
      });

      var firstKey = Object.keys(errors)[0];
      if (firstKey && form.elements[firstKey]) {
        form.elements[firstKey].focus();
      }
    }

    function rebuildAllFileSlots() {
      Object.keys(fileSlots).forEach(function (key) {
        renderFileList(fileSlots[key]);
      });
    }

    function handleSuccessfulSubmit() {
      var successPayload = {
        contactType: "phone_email",
        hasAttachment: getInputFiles(fileSlots.attachment.input).length > 0 ||
          getInputFiles(fileSlots.company_card.input).length > 0
      };

      form.reset();
      resetSlot(fileSlots.attachment);
      resetSlot(fileSlots.company_card);
      rebuildAllFileSlots();
      clearAllErrors();
      emitSuccessfulRequestEvent(successPayload);
      closeModal(requestModal);
      openSuccessModal();
    }

    function resetSlot(slot) {
      slot.files = [];
      setInputFiles(slot.input, []);
      slot.input.value = "";
    }

    function syncSlot(slot, incomingFiles) {
      slot.files = mergeFiles(slot.files, incomingFiles);
      addFilesToInput(slot.input, slot.files);
      renderFileList(slot);
      clearFieldError(slot.key);
      clearStatus(statusNode);
    }

    trigger.addEventListener("click", function () {
      openRequestModal(trigger);
    });

    document.addEventListener("click", function (event) {
      var openTrigger = event.target.closest("[data-open-request-form]");
      if (openTrigger) {
        event.preventDefault();
        openRequestModal(openTrigger);
      }
    });

    requestModal.addEventListener("click", function (event) {
      if (event.target.closest("[data-request-close='true']")) {
        closeModal(requestModal);
      }
    });

    successModal.addEventListener("click", function (event) {
      if (event.target.closest("[data-success-close='true']")) {
        closeModal(successModal);
      }
    });

    document.addEventListener("keydown", function (event) {
      if (!activeModal || !activeModal.classList.contains(OPEN_CLASS)) {
        return;
      }

      var activeDialog = activeModal.querySelector(".request-modal__dialog");

      if (event.key === "Escape") {
        event.preventDefault();
        closeModal(activeModal);
        return;
      }

      if (event.key === "Tab") {
        var focusable = getFocusable(activeDialog);
        if (!focusable.length) {
          return;
        }

        var first = focusable[0];
        var last = focusable[focusable.length - 1];

        if (event.shiftKey && document.activeElement === first) {
          event.preventDefault();
          last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
          event.preventDefault();
          first.focus();
        }
      }
    });

    ["name", "phone", "email", "message"].forEach(function (fieldName) {
      form.elements[fieldName].addEventListener("input", function () {
        clearFieldError(fieldName);
        clearStatus(statusNode);
      });
    });

    Object.keys(fileSlots).forEach(function (key) {
      var slot = fileSlots[key];

      slot.shell.addEventListener("click", function (event) {
        if (!event.target.closest(".request-form__file-chip-remove")) {
          slot.input.value = "";
          slot.input.click();
        }
      });

      slot.shell.addEventListener("keydown", function (event) {
        if (event.key === "Enter" || event.key === " ") {
          event.preventDefault();
          slot.input.value = "";
          slot.input.click();
        }
      });

      slot.input.addEventListener("change", function () {
        syncSlot(slot, getInputFiles(slot.input));
      });

      slot.list.addEventListener("click", function (event) {
        var removeButton = event.target.closest(".request-form__file-chip-remove");
        if (!removeButton) {
          return;
        }

        event.preventDefault();
        removeFileAtIndex(slot, Number(removeButton.getAttribute("data-file-index")));
        renderFileList(slot);
        clearFieldError(slot.key);
        clearStatus(statusNode);
      });

      ["dragenter", "dragover"].forEach(function (eventName) {
        slot.zone.addEventListener(eventName, function (event) {
          event.preventDefault();
          slot.zone.classList.add(DRAGOVER_CLASS);
        });
      });

      ["dragleave", "dragend", "drop"].forEach(function (eventName) {
        slot.zone.addEventListener(eventName, function (event) {
          if (eventName === "dragleave" && slot.zone.contains(event.relatedTarget)) {
            return;
          }

          event.preventDefault();
          slot.zone.classList.remove(DRAGOVER_CLASS);
        });
      });

      slot.zone.addEventListener("drop", function (event) {
        var droppedFiles = Array.prototype.slice.call((event.dataTransfer && event.dataTransfer.files) || []);
        if (!droppedFiles.length) {
          return;
        }

        syncSlot(slot, droppedFiles);
      });
    });

    rebuildAllFileSlots();

    form.addEventListener("submit", function (event) {
      event.preventDefault();

      var errors = getValidationErrors(form);
      if (Object.keys(errors).length) {
        applyValidationErrors(errors);
        return;
      }

      clearAllErrors();

      Object.keys(fileSlots).forEach(function (key) {
        addFilesToInput(fileSlots[key].input, fileSlots[key].files);
      });

      var formData = new FormData(form);
      submitButton.disabled = true;
      submitButton.textContent = "Отправляем...";

      if (isPreviewMode()) {
        window.setTimeout(function () {
          submitButton.disabled = false;
          submitButton.textContent = "Отправить заявку";
          handleSuccessfulSubmit();
        }, 300);
        return;
      }

      fetch(ENDPOINT, {
        method: "POST",
        body: formData,
        headers: {
          "X-Requested-With": "XMLHttpRequest"
        }
      })
        .then(function (response) {
          return response.json().then(function (payload) {
            return {
              ok: response.ok,
              payload: payload
            };
          });
        })
        .then(function (result) {
          if (!result.ok || !result.payload || !result.payload.ok) {
            throw new Error((result.payload && result.payload.message) || "Не удалось отправить заявку.");
          }

          handleSuccessfulSubmit();
        })
        .catch(function () {
          setStatus(statusNode, "Не удалось отправить заявку. Попробуйте еще раз.", "error");
        })
        .finally(function () {
          submitButton.disabled = false;
          submitButton.textContent = "Отправить заявку";
        });
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", attachRequestForm);
  } else {
    attachRequestForm();
  }
})();
