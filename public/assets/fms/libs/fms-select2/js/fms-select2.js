/**
 * FMS Select2 — Vanilla JavaScript replacement for Select2 (jQuery-free)
 * ---------------------------------------------------------------------
 * Drop-in replacement for Select2 4.x used by the FMS admin template.
 *
 * Public API (jQuery-style, so existing template code keeps working):
 *
 *   $('.my-select').select2();                 // init, returns jQuery-ish set
 *   $('.my-select').select2({ ...options });   // init with options
 *   $('.my-select').select2('destroy');        // teardown
 *   $('.my-select').select2('open'|'close'|'toggle');
 *   $('.my-select').select2('val', value);     // get/set value
 *   $('.my-select').val();
 *   $('.my-select').prop('disabled', true);
 *   $('.my-select').on('change', fn);
 *   $('.my-select').trigger('change');
 *
 * Or programmatically without a jQuery object:
 *
 *   const inst = FMSSelect2.init('#mySelect', { placeholder: '...' });
 *   inst.val('s-2');
 *   inst.destroy();
 *
 * Supported Select2 4.x options:
 *   placeholder, allowClear, multiple (inferred), disabled,
 *   minimumResultsForSearch, maximumSelectionLength, closeOnSelect,
 *   search, dir ('ltr'|'rtl'), templateResult, templateSelection,
 *   language.noResults, width, dropdownParent
 *
 * Events emitted on the original <select> element:
 *   'change' (native), 'fms-select2:open', 'fms-select2:close',
 *   'fms-select2:select', 'fms-select2:unselect', 'fms-select2:clear'
 *
 * @version 2.0.0
 * @license MIT
 */

(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        define([], factory);
    } else if (typeof module === 'object' && module.exports) {
        module.exports = factory();
    } else {
        root.FMSSelect2 = factory();
    }
}(typeof self !== 'undefined' ? self : this, function () {
    'use strict';

    var counter = 0;

    function assign(target) {
        for (var i = 1; i < arguments.length; i++) {
            var src = arguments[i];
            if (!src) continue;
            for (var k in src) {
                if (Object.prototype.hasOwnProperty.call(src, k)) {
                    target[k] = src[k];
                }
            }
        }
        return target;
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function toOptionLike(item) {
        if (item && item.nodeType === 1) return item;      // DOM <option>
        if (item && item.element) return item.element;     // sudah option-like
        var o = {};
        for (var k in item) {
            if (Object.prototype.hasOwnProperty.call(item, k)) o[k] = item[k];
        }
        o.value = item.id !== undefined ? String(item.id) : '';
        o.text = item.text != null ? String(item.text) : '';
        if (o.disabled === undefined) o.disabled = false;
        if (o.selected === undefined) o.selected = false;
        if (!o.dataset) o.dataset = {};
        if (!o.getAttribute) {
            o.getAttribute = function (key) {
                if (key === 'data-value') return item.id;
                return null;
            };
        }
        return o;
    }

    /* ------------------------------------------------------------------ */
    /*  Instance                                                          */
    /* ------------------------------------------------------------------ */

    function FMSSelect2(element, options) {
        if (!element || element.tagName !== 'SELECT') {
            throw new Error('FMSSelect2: element must be a <select>');
        }
        if (element._fmsSelect2) {
            return element._fmsSelect2; // already initialised
        }

        this.element = element;
        this.options = assign({
            placeholder: '',
            allowClear: false,
            multiple: !!element.multiple,
            search: true,
            minimumResultsForSearch: 0,
            maximumSelectionLength: 0,
            closeOnSelect: true,
            disabled: element.disabled,
            dir: element.getAttribute('dir') ||
                document.documentElement.getAttribute('dir') || 'ltr',
            width: '100%',
            dropdownParent: null,
            templateResult: null,
            templateSelection: null,
            minimumInputLength: 0,
            escapeMarkupFn: null,
            ajax: null,
            language: {
                noResults: function () { return 'No results found'; },
                searching: function () { return 'Searching…'; },
                inputTooShort: function (a) {
                    return 'Please enter ' + a.minimum + ' or more characters';
                }
            }
        }, options || {});

        // ---- Normalisasi alias gaya Select2 3.x -> opsi 4.x ----
        if (!this.options.templateResult && this.options.formatResult) {
            this.options.templateResult = this.options.formatResult;
        }
        if (!this.options.templateSelection && this.options.formatSelection) {
            this.options.templateSelection = this.options.formatSelection;
        }
        if (this.options.escapeMarkup && typeof this.options.escapeMarkup === 'function') {
            this.options.escapeMarkupFn = this.options.escapeMarkup;
        }
        if (this.options.ajax) {
            var aj = this.options.ajax;
            // quietMillis (3.x) -> delay (4.x)
            if (aj.delay == null && aj.quietMillis != null) aj.delay = aj.quietMillis;
            // results() (3.x) -> processResults() (4.x)
            if (!aj.processResults && typeof aj.results === 'function') {
                aj.processResults = aj.results;
            }
            // multiple di-infer dari elemen
            this.options.multiple = !!element.multiple;
        }

        this.multiple = this.options.multiple;
        this.isOpen = false;
        this.isDisabled = false;
        this.searchTerm = '';
        this.highlightedIndex = -1;
        this.selectedValues = [];
        this._listeners = [];
        this._uid = ++counter;

        // ---- state AJAX ----
        this.ajaxPage = 1;
        this.ajaxLoading = false;
        this.ajaxMore = false;
        this.ajaxCache = {};        // id -> {id, text, ...} agar chip tetap punya label
        this._ajaxTimer = null;
        this._ajaxXhr = null;

        this.init();
        element._fmsSelect2 = this;
    }

    FMSSelect2.prototype = {

        init: function () {
            this.element.style.display = 'none';
            if (this.element.getAttribute('dir')) {
                this.options.dir = this.element.getAttribute('dir');
            }
            this.buildContainer();
            this.buildDropdown();
            this.syncValues();
            this.bindEvents();
            if (this.options.disabled || this.element.disabled) {
                this.disable();
            }
            this.refreshPlaceholder();
            this.element.classList.add('fms-select2-hidden-accessible');
            return this;
        },

        /* -------------------- DOM construction -------------------- */

        buildContainer: function () {
            var self = this;

            this.container = document.createElement('div');
            this.container.className = 'fms-select2-container fms-select2-container--default';
            this.container.setAttribute('dir', this.options.dir === 'rtl' ? 'rtl' : 'ltr');
            this.container.id = 'fms-select2-' + this._uid;

            this.selection = document.createElement('div');
            this.selection.className = 'fms-select2-selection';
            this.selection.setAttribute('role', 'combobox');
            this.selection.setAttribute('aria-haspopup', 'listbox');
            this.selection.setAttribute('aria-expanded', 'false');
            this.selection.tabIndex = 0;

            if (this.multiple) {
                this.selection.classList.add('fms-select2-selection--multiple');

                this.selectionList = document.createElement('ul');
                this.selectionList.className = 'fms-select2-selection__rendered';

                this.searchInput = document.createElement('input');
                this.searchInput.type = 'text';
                this.searchInput.className = 'fms-select2-search__field';
                this.searchInput.setAttribute('autocomplete', 'off');
                this.searchInput.setAttribute('autocorrect', 'off');
                this.searchInput.setAttribute('autocapitalize', 'off');
                this.searchInput.setAttribute('spellcheck', 'false');

                this.selection.appendChild(this.selectionList);
                this.selection.appendChild(this.searchInput);

                // Panah dropdown pada mode multiple (meniru .select2-choice .select2-arrow)
                this.selectionArrow = document.createElement('span');
                this.selectionArrow.className = 'fms-select2-selection__arrow';
                this.selectionArrow.setAttribute('role', 'presentation');
                this.selectionArrow.innerHTML = '<b></b>';
                this.selection.appendChild(this.selectionArrow);
            } else {
                this.selection.classList.add('fms-select2-selection--single');

                this.selectionText = document.createElement('span');
                this.selectionText.className = 'fms-select2-selection__rendered';
                this.selectionText.id = 'fms-select2-rendered-' + this._uid;

                this.selectionArrow = document.createElement('span');
                this.selectionArrow.className = 'fms-select2-selection__arrow';
                this.selectionArrow.setAttribute('role', 'presentation');
                this.selectionArrow.innerHTML = '<b></b>';

                this.selection.appendChild(this.selectionText);
                this.selection.appendChild(this.selectionArrow);

                if (this.options.allowClear) {
                    this.clearButton = document.createElement('span');
                    this.clearButton.className = 'fms-select2-selection__clear';
                    this.clearButton.setAttribute('role', 'button');
                    this.clearButton.setAttribute('aria-label', 'Clear');
                    this.clearButton.innerHTML = '&times;';
                    this.clearButton.style.display = 'none';
                    this.selection.appendChild(this.clearButton);
                }
            }

            this.container.appendChild(this.selection);

            var parent = this.options.dropdownParent || this.element.parentNode;
            parent.insertBefore(this.container, this.element.nextSibling);

            if (this.options.width) {
                this.container.style.width =
                    (this.options.width === 'resolve' ? '' :
                        (typeof this.options.width === 'number'
                            ? this.options.width + 'px' : this.options.width));
            }
        },

        buildDropdown: function () {
            this.dropdown = document.createElement('div');
            this.dropdown.className =
                'fms-select2-dropdown fms-select2-dropdown--below';
            this.dropdown.setAttribute('dir', this.options.dir === 'rtl' ? 'rtl' : 'ltr');
            this.dropdown.style.display = 'none';

            // Search box inside dropdown (single-select or when multi search disabled)
            var needsDropdownSearch = this.options.search && !this.multiple &&
                (this.options.ajax ||
                    this.countSelectableOptions() > this.options.minimumResultsForSearch);

            if (needsDropdownSearch) {
                this.searchContainer = document.createElement('div');
                this.searchContainer.className = 'fms-select2-search fms-select2-search--dropdown';

                this.dropdownSearchInput = document.createElement('input');
                this.dropdownSearchInput.type = 'text';
                this.dropdownSearchInput.className = 'fms-select2-search__field';
                this.dropdownSearchInput.setAttribute('autocomplete', 'off');
                this.dropdownSearchInput.setAttribute('spellcheck', 'false');
                this.dropdownSearchInput.placeholder = 'Search…';

                this.searchContainer.appendChild(this.dropdownSearchInput);
                this.dropdown.appendChild(this.searchContainer);
            }

            this.results = document.createElement('ul');
            this.results.className = 'fms-select2-results__options';
            this.results.setAttribute('role', 'listbox');
            this.dropdown.appendChild(this.results);

            // Dropdown dipasang di dalam container agar border/box benar-benar
            // menyatu, kecuali pengguna menentukan dropdownParent sendiri.
            (this.options.dropdownParent || this.container).appendChild(this.dropdown);
        },

        countSelectableOptions: function () {
            var n = 0;
            for (var i = 0; i < this.element.options.length; i++) {
                if (!this.element.options[i].disabled) n++;
            }
            return n;
        },

        /* -------------------- value sync -------------------- */

        syncValues: function () {
            if (this.multiple) {
                this.selectedValues = [];
                for (var i = 0; i < this.element.options.length; i++) {
                    var o = this.element.options[i];
                    if (o.selected) this.selectedValues.push(o.value);
                }
                this.renderMultiSelection();
            } else {
                var opt = this.element.options[this.element.selectedIndex];
                if (opt && !opt.disabled && opt.value !== '') {
                    this.selectedValues = [opt.value];
                    this.updateSelectionText(opt.text, opt.value);
                } else {
                    this.selectedValues = [];
                    this.updateSelectionText('', '');
                }
            }
        },

        renderMultiSelection: function () {
            var self = this;
            this.selectionList.innerHTML = '';

            this.selectedValues.forEach(function (value) {
                var option = self.findOption(value);
                var optLike = option;
                if (!option) {
                    var cached = self.ajaxCache[String(value)];
                    if (cached) optLike = toOptionLike(cached);
                }
                if (!optLike) return;
                var label = optLike.text != null ? optLike.text : String(value);

                var li = document.createElement('li');
                li.className = 'fms-select2-selection__choice';
                li.setAttribute('title', label);

                var remove = document.createElement('span');
                remove.className = 'fms-select2-selection__choice__remove';
                remove.setAttribute('role', 'presentation');
                remove.setAttribute('aria-label', 'Remove');
                remove.innerHTML = '&times;';
                remove.setAttribute('data-value', value);

                var display = document.createElement('span');
                display.className = 'fms-select2-selection__choice__display';

                if (self.options.templateSelection) {
                    display.innerHTML = self.renderTemplate(
                        self.options.templateSelection, optLike
                    );
                } else {
                    display.textContent = label;
                }

                li.appendChild(remove);
                li.appendChild(display);
                self.selectionList.appendChild(li);
            });

            if (this.searchInput) {
                this.searchInput.style.display = this.isDisabled ? 'none' : '';
                this.searchInput.placeholder =
                    this.selectedValues.length === 0 ? (this.options.placeholder || '') : '';
            }
            this.refreshPlaceholder();
        },

        updateSelectionText: function (text, value) {
            if (!this.selectionText) return;
            var option = value !== undefined && value !== '' ? this.findOption(value) : null;
            var optLike = option;
            if (!option && value !== undefined && value !== '' && this.ajaxCache[String(value)]) {
                optLike = toOptionLike(this.ajaxCache[String(value)]);
            }

            if (optLike && this.options.templateSelection) {
                this.selectionText.innerHTML = this.renderTemplate(
                    this.options.templateSelection, optLike
                );
            } else if (optLike) {
                this.selectionText.textContent = optLike.text;
            } else {
                this.selectionText.innerHTML = '';
            }

            this.selectionText.classList.toggle(
                'fms-select2-selection__placeholder', !optLike
            );
            if (!optLike && this.options.placeholder) {
                this.selectionText.textContent = this.options.placeholder;
            }

            if (this.clearButton) {
                this.clearButton.style.display = optLike ? '' : 'none';
            }
            this.refreshPlaceholder();
        },

        refreshPlaceholder: function () {
            var hasValue = this.multiple
                ? this.selectedValues.length > 0
                : this.selectedValues.length > 0 &&
                    this.selectedValues[0] !== '';
            this.container.classList.toggle('fms-select2-container--placeholder', !hasValue);
        },

        /* -------------------- events -------------------- */

        bindEvents: function () {
            var self = this;

            this._on(this.selection, 'click', function (e) {
                if (self.isDisabled) return;
                if (e.target.closest &&
                    (e.target.closest('.fms-select2-selection__clear') ||
                        e.target.closest('.fms-select2-selection__choice__remove'))) {
                    return;
                }

                // Multiple tetap terbuka saat area/input diklik. Sebelumnya
                // click input setelah focus->open memanggil toggle() lalu menutup lagi.
                if (self.multiple) {
                    if (!self.isOpen) self.open();
                    if (self.searchInput && e.target !== self.selectionArrow) {
                        self.searchInput.focus();
                    }
                    return;
                }
                self.toggle();
            });

            this._on(this.selection, 'keydown', function (e) {
                if (self.isDisabled) return;
                if (self.multiple && e.target === self.searchInput) return;
                self.handleKeydown(e);
            });

            if (this.clearButton) {
                this._on(this.clearButton, 'click', function (e) {
                    e.stopPropagation();
                    self.clear();
                });
                this._on(this.clearButton, 'mousedown', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                });
            }

            if (this.selectionList) {
                this._on(this.selectionList, 'click', function (e) {
                    var rm = e.target.closest &&
                        e.target.closest('.fms-select2-selection__choice__remove');
                    if (rm) {
                        e.stopPropagation();
                        self.deselect(rm.getAttribute('data-value'));
                    }
                });
            }

            if (this.searchInput) {
                this._on(this.searchInput, 'input', function (e) {
                    self.searchTerm = e.target.value;
                    self.onSearchInput();
                });
                this._on(this.searchInput, 'keydown', function (e) {
                    self.handleKeydown(e);
                });
                this._on(this.searchInput, 'focus', function () {
                    if (!self.isOpen) self.open();
                });
            }

            if (this.dropdownSearchInput) {
                this._on(this.dropdownSearchInput, 'input', function (e) {
                    self.searchTerm = e.target.value;
                    self.onSearchInput();
                });
                this._on(this.dropdownSearchInput, 'keydown', function (e) {
                    self.handleKeydown(e);
                });
            }

            this._on(this.results, 'click', function (e) {
                var item = e.target.closest && e.target.closest('.fms-select2-results__option');
                if (item && !item.classList.contains('fms-select2-results__option--disabled')) {
                    self.select(item.getAttribute('data-value'));
                }
            });

            this._on(this.results, 'scroll', function () {
                self.onResultsScroll();
            });

            this._on(this.results, 'mouseover', function (e) {
                var item = e.target.closest && e.target.closest('.fms-select2-results__option');
                if (item && !item.classList.contains('fms-select2-results__option--disabled')) {
                    self.highlightItem(item);
                }
            });

            this._on(document, 'click', function (e) {
                if (self.isOpen &&
                    !self.container.contains(e.target) &&
                    !self.dropdown.contains(e.target)) {
                    self.close();
                }
            }, true);

            this._on(window, 'resize', function () {
                if (self.isOpen) self.positionDropdown();
            });
            this._on(window, 'scroll', function () {
                if (self.isOpen) self.positionDropdown();
            }, true);
        },

        _on: function (target, type, handler, capture) {
            target.addEventListener(type, handler, capture || false);
            this._listeners.push({ target: target, type: type, handler: handler, capture: capture || false });
        },

        unbindEvents: function () {
            this._listeners.forEach(function (l) {
                l.target.removeEventListener(l.type, l.handler, l.capture);
            });
            this._listeners = [];
        },

        handleKeydown: function (e) {
            switch (e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    if (!this.isOpen) this.open();
                    else this.highlightNext();
                    break;
                case 'ArrowUp':
                    e.preventDefault();
                    if (this.isOpen) this.highlightPrev();
                    break;
                case 'Enter':
                    e.preventDefault();
                    if (this.isOpen && this.highlightedIndex >= 0) {
                        var items = this.visibleItems();
                        if (items[this.highlightedIndex]) {
                            this.select(items[this.highlightedIndex].getAttribute('data-value'));
                        }
                    } else if (!this.isOpen) {
                        this.open();
                    }
                    break;
                case 'Escape':
                    e.preventDefault();
                    this.close();
                    break;
                case 'Tab':
                    if (this.isOpen) this.close();
                    break;
                case 'Backspace':
                    if (this.multiple && e.target.value === '' &&
                        this.selectedValues.length > 0) {
                        this.deselect(this.selectedValues[this.selectedValues.length - 1]);
                    }
                    break;
            }
        },

        /* -------------------- AJAX -------------------- */

        onSearchInput: function () {
            if (this.options.ajax) {
                var min = this.options.minimumInputLength || 0;
                if (this.searchTerm.length < min) {
                    this.renderLoadingOrMessage(
                        'inputTooShort',
                        { minimum: min, input: this.searchTerm.length }
                    );
                    this.results.innerHTML = '';
                    return;
                }
                this.ajaxPage = 1;
                this.ajaxMore = false;
                this.scheduleAjax();
            } else {
                this.filterResults();
            }
        },

        scheduleAjax: function () {
            var self = this;
            var delay = this.options.ajax.delay != null ? this.options.ajax.delay : 0;
            if (this._ajaxTimer) clearTimeout(this._ajaxTimer);
            this._ajaxTimer = setTimeout(function () {
                self.queryAjax();
            }, delay);
        },

        queryAjax: function () {
            var self = this;
            var aj = this.options.ajax;
            var term = this.searchTerm;

            this.ajaxLoading = true;
            this.setSearchActive(true);
            if (this.ajaxPage === 1) {
                this.renderLoadingOrMessage('searching');
            } else {
                // halaman berikutnya: jangan hapus daftar, tampilkan loading di bawah
                this.showAjaxLoadingRow();
            }

            var params = typeof aj.data === 'function'
                ? aj.data(term, this.ajaxPage) : (aj.data || {});

            // transport kustom (mis. mock internal) — tandatangan (options, done, fail)
            if (typeof aj.transport === 'function') {
                aj.transport(
                    { url: aj.url, data: params, type: aj.type, page: this.ajaxPage },
                    function (raw) { self.handleAjaxSuccess(raw); },
                    function (err) { self.handleAjaxError(err); }
                );
                return;
            }

            // default: fetch() (bisa di-override lewat ajax.transport)
            var url = aj.url;
            var type = (aj.type || 'GET').toUpperCase();
            var opts = { method: type, headers: {} };
            if (aj.dataType === 'JSON' || !aj.dataType) {
                opts.headers['Accept'] = 'application/json';
            }
            if (type === 'GET') {
                var qs = [];
                for (var k in params) {
                    if (Object.prototype.hasOwnProperty.call(params, k)) {
                        qs.push(encodeURIComponent(k) + '=' + encodeURIComponent(params[k]));
                    }
                }
                if (qs.length) url += (url.indexOf('?') === -1 ? '?' : '&') + qs.join('&');
            } else {
                opts.body = params;
            }
            if (aj.headers) {
                for (var h in aj.headers) { opts.headers[h] = aj.headers[h]; }
            }

            if (typeof fetch !== 'function') {
                return this.handleAjaxError(new Error('fetch tidak tersedia'));
            }
            fetch(url, opts)
                .then(function (r) { return aj.dataType === 'JSON' || !aj.dataType ? r.json() : r.text(); })
                .then(function (raw) { self.handleAjaxSuccess(raw); })
                .catch(function (err) { self.handleAjaxError(err); });
        },

        handleAjaxSuccess: function (raw) {
            var aj = this.options.ajax;
            var page = this.ajaxPage;
            var out = typeof aj.processResults === 'function'
                ? aj.processResults(raw, page) : raw;
            var results = (out && out.results) ? out.results : [];
            var more = !!(out && out.more);

            this.setSearchActive(false);

            if (page === 1) {
                this.ajaxItems = [];
                this.results.innerHTML = '';
            }
            if (!this.ajaxItems) this.ajaxItems = [];
            var self = this;
            results.forEach(function (item) {
                self.ajaxItems.push(item);
                self.ajaxCache[String(item.id)] = item;
            });
            this.ajaxMore = more;
            this.ajaxLoading = false;
            this.cacheSelectedLabels();

            this.renderAjaxResults();
            this.refreshPlaceholder();
        },

        handleAjaxError: function (err) {
            this.ajaxLoading = false;
            this.setSearchActive(false);
            var self = this;
            this.results.innerHTML = '';
            var li = document.createElement('li');
            li.className = 'fms-select2-results__message';
            li.textContent = 'Gagal memuat data' + (err && err.message ? ': ' + err.message : '');
            this.results.appendChild(li);
        },

        renderLoadingOrMessage: function (key, arg) {
            this.results.innerHTML = '';
            var li = document.createElement('li');
            li.className = 'fms-select2-results__message';
            if (key === 'searching') {
                li.classList.add('fms-select2-results__loading');
            }
            if (this.options.language && typeof this.options.language[key] === 'function') {
                li.textContent = this.options.language[key](arg || {});
            } else {
                li.textContent = key === 'searching' ? 'Searching…' : 'No results found';
            }
            this.results.appendChild(li);
        },

        // Spinner pada kolom pencarian (select2-spinner.gif) saat AJAX berjalan
        setSearchActive: function (on) {
            var inputs = [this.searchInput, this.dropdownSearchInput];
            for (var i = 0; i < inputs.length; i++) {
                if (inputs[i]) inputs[i].classList.toggle('fms-select2-active', !!on);
            }
        },

        // Baris "Memuat…" di dasar daftar (dipakai saat memuat halaman berikutnya)
        showAjaxLoadingRow: function () {
            if (!this.results) return;
            this.hideAjaxLoadingRow();
            var li = document.createElement('li');
            li.className = 'fms-select2-results__message fms-select2-results__loading';
            li.textContent = (this.options.language && typeof this.options.language.searching === 'function')
                ? this.options.language.searching() : 'Searching…';
            this.results.appendChild(li);
            this._ajaxLoadingRow = li;
            // hapus tombol "muat lebih banyak" agar tidak dobel
            var more = this.results.querySelector('.fms-select2-results__more');
            if (more) more.parentNode.removeChild(more);
        },

        hideAjaxLoadingRow: function () {
            if (this._ajaxLoadingRow && this._ajaxLoadingRow.parentNode) {
                this._ajaxLoadingRow.parentNode.removeChild(this._ajaxLoadingRow);
            }
            this._ajaxLoadingRow = null;
        },

        // Dipanggil saat dropdown di-scroll: muat halaman berikutnya bila mentok bawah
        onResultsScroll: function () {
            if (!this.options.ajax || this.ajaxLoading || !this.ajaxMore) return;
            var el = this.results;
            if (el.scrollTop + el.clientHeight >= el.scrollHeight - 24) {
                this.ajaxPage++;
                this.queryAjax();
            }
        },

        renderAjaxResults: function () {
            var self = this;
            this._ajaxLoadingRow = null;
            this.results.innerHTML = '';

            (this.ajaxItems || []).forEach(function (item) {
                var li = document.createElement('li');
                li.className = 'fms-select2-results__option fms-select2-results__option--selectable';
                li.setAttribute('data-value', item.id);
                li.setAttribute('role', 'option');

                if (self.options.templateResult) {
                    li.innerHTML = self.renderTemplate(
                        self.options.templateResult, toOptionLike(item)
                    );
                } else {
                    li.textContent = item.text;
                }

                if (self.selectedValues.indexOf(String(item.id)) !== -1) {
                    li.classList.add('fms-select2-results__option--selected');
                }
                self.results.appendChild(li);
            });

            if (!this.ajaxItems || !this.ajaxItems.length) {
                this.renderLoadingOrMessage('noResults');
                return;
            }

            if (this.ajaxMore) {
                var more = document.createElement('li');
                more.className = 'fms-select2-results__message fms-select2-results__more';
                more.textContent = 'Muat lebih banyak…';
                more.style.cursor = 'pointer';
                var self2 = this;
                more.addEventListener('click', function (e) {
                    e.stopPropagation();
                    self2.ajaxPage++;
                    self2.queryAjax();
                });
                this.results.appendChild(more);
            }
            this.highlightedIndex = -1;
        },

        // Pastikan label chip/selected tetap ada walau item belum dimuat dari server
        cacheSelectedLabels: function () {
            var self = this;
            this.selectedValues.forEach(function (val) {
                if (self.ajaxCache[val]) return;
                var opt = self.findOption(val);
                if (opt && opt.text) {
                    self.ajaxCache[val] = { id: val, text: opt.text, element: opt };
                } else {
                    self.ajaxCache[val] = { id: val, text: val };
                }
            });
        },

        /* -------------------- open / close -------------------- */

        toggle: function () {
            this.isOpen ? this.close() : this.open();
        },

        open: function () {
            if (this.isOpen || this.isDisabled) return;
            this.isOpen = true;
            this.container.classList.add('fms-select2-container--open');
            this.selection.setAttribute('aria-expanded', 'true');
            this.dropdown.style.display = '';
            this.selection.style.width = '100%';

            this.positionDropdown();

            if (this.options.ajax) {
                // muat halaman pertama (atau minimal-length check)
                var min = this.options.minimumInputLength || 0;
                if (this.searchTerm.length < min && this.ajaxItems && this.ajaxItems.length) {
                    this.renderAjaxResults();
                } else {
                    this.ajaxPage = 1;
                    this.queryAjax();
                }
            } else {
                this.populateResults();
                this.filterResults();
            }

            if (this.dropdownSearchInput) {
                var self = this;
                setTimeout(function () { self.dropdownSearchInput.focus(); }, 0);
            } else if (this.searchInput && !this.isDisabled) {
                var self2 = this;
                setTimeout(function () { self2.searchInput.focus(); }, 0);
            }
            this.triggerEvent('open');
        },

        close: function () {
            if (!this.isOpen) return;
            this.isOpen = false;
            this.container.classList.remove('fms-select2-container--open');
            this.selection.setAttribute('aria-expanded', 'false');
            this.dropdown.style.display = 'none';
            this.highlightedIndex = -1;
            this.searchTerm = '';
            if (this.dropdownSearchInput) this.dropdownSearchInput.value = '';
            if (this.searchInput) {
                this.searchInput.value = '';
                if (this.multiple) {
                    this.searchInput.placeholder =
                        this.selectedValues.length === 0 ? (this.options.placeholder || '') : '';
                }
            }
            this.populateResults();
            this.triggerEvent('close');
        },

        positionDropdown: function () {
            var rect = this.container.getBoundingClientRect();
            var useBodyCoordinates = !!this.options.dropdownParent &&
                this.options.dropdownParent !== this.container;
            var scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            var scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;

            this.dropdown.style.position = 'absolute';
            this.dropdown.style.width = rect.width + 'px';
            this.dropdown.style.left = useBodyCoordinates
                ? (rect.left + scrollLeft) + 'px' : '0px';

            var dropdownHeight = this.dropdown.offsetHeight;
            var viewport = window.innerHeight;
            var above = rect.bottom + dropdownHeight > viewport && rect.top > dropdownHeight;

            if (above) {
                this.dropdown.style.top = useBodyCoordinates
                    ? (rect.top + scrollTop - dropdownHeight) + 'px'
                    : (-dropdownHeight) + 'px';
                this.dropdown.classList.remove('fms-select2-dropdown--below');
                this.dropdown.classList.add('fms-select2-dropdown--above');
                this.container.classList.add('fms-select2-container--above');
            } else {
                this.dropdown.style.top = useBodyCoordinates
                    ? (rect.bottom + scrollTop) + 'px'
                    : '100%';
                this.dropdown.classList.remove('fms-select2-dropdown--above');
                this.dropdown.classList.add('fms-select2-dropdown--below');
                this.container.classList.remove('fms-select2-container--above');
            }
        },

        /* -------------------- results -------------------- */

        renderTemplate: function (fn, option) {
            // Teruskan field kustom (mis. nip, jabatan) selain field standar.
            var data = { id: option.value, text: option.text };
            if (option.nodeType !== 1) {
                for (var k in option) {
                    if (Object.prototype.hasOwnProperty.call(option, k)) data[k] = option[k];
                }
            }
            data.id = option.value !== undefined ? option.value : option.id;
            data.text = option.text;
            data.element = option;
            data.disabled = option.disabled;
            data.selected = option.selected;
            var out = fn(data);
            if (out == null) return this.applyEscape(escapeHtml(option.text));
            // Accept DOM nodes, jQuery objects and HTML strings alike.
            if (out.nodeType === 1) return out.outerHTML;
            if (typeof out.get === 'function') {           // jQuery object
                var el = out.get(0);
                if (el && el.nodeType === 1) return el.outerHTML;
                if (typeof out.html === 'function') return out.html();
                return out.text();
            }
            return this.applyEscape(out); // string (may contain markup)
        },

        applyEscape: function (markup) {
            if (typeof this.options.escapeMarkupFn === 'function') {
                return this.options.escapeMarkupFn(markup);
            }
            return markup;
        },

        populateResults: function () {
            var self = this;
            this.results.innerHTML = '';

            for (var i = 0; i < this.element.options.length; i++) {
                var option = this.element.options[i];
                if (option.disabled) continue;

                var li = document.createElement('li');
                li.className = 'fms-select2-results__option fms-select2-results__option--selectable';
                li.setAttribute('data-value', option.value);
                li.setAttribute('data-text', option.text.toLowerCase());
                li.setAttribute('role', 'option');

                if (self.options.templateResult) {
                    li.innerHTML = self.renderTemplate(self.options.templateResult, option);
                } else {
                    li.textContent = option.text;
                }

                if (self.selectedValues.indexOf(option.value) !== -1) {
                    li.classList.add('fms-select2-results__option--selected');
                    li.setAttribute('aria-selected', 'true');
                }
                self.results.appendChild(li);
            }
            this.refreshPlaceholder();
        },

        visibleItems: function () {
            var out = [];
            var items = this.results.querySelectorAll('.fms-select2-results__option');
            for (var i = 0; i < items.length; i++) {
                if (!items[i].classList.contains('fms-select2-results__option--hidden')) {
                    out.push(items[i]);
                }
            }
            return out;
        },

        filterResults: function () {
            var term = this.searchTerm.toLowerCase();
            var items = this.results.querySelectorAll('.fms-select2-results__option');
            var visible = 0;
            var atLimit = this.multiple && this.options.maximumSelectionLength > 0 &&
                this.selectedValues.length >= this.options.maximumSelectionLength;

            for (var i = 0; i < items.length; i++) {
                var item = items[i];
                var text = item.getAttribute('data-text') || '';
                var matches = !term || text.indexOf(term) !== -1;
                item.classList.toggle('fms-select2-results__option--hidden', !matches);
                if (matches) {
                    visible++;
                    if (atLimit && !item.classList.contains('fms-select2-results__option--selected')) {
                        item.classList.add('fms-select2-results__option--disabled');
                    } else if (!item.classList.contains('fms-select2-results__option--selected')) {
                        item.classList.remove('fms-select2-results__option--disabled');
                    }
                }
            }

            var noResults = this.results.querySelector('.fms-select2-results__message');
            if (visible === 0) {
                if (!noResults) {
                    noResults = document.createElement('li');
                    noResults.className = 'fms-select2-results__message';
                    noResults.textContent = this.options.language.noResults();
                    this.results.appendChild(noResults);
                }
            } else if (noResults) {
                noResults.parentNode.removeChild(noResults);
            }
            this.highlightedIndex = -1;
        },

        highlightItem: function (item) {
            var items = this.visibleItems();
            for (var i = 0; i < items.length; i++) {
                items[i].classList.toggle('fms-select2-results__option--highlighted', items[i] === item);
                if (items[i] === item) this.highlightedIndex = i;
            }
        },

        highlightNext: function () {
            var items = this.visibleItems();
            if (!items.length) return;
            this.clearHighlight();
            this.highlightedIndex = (this.highlightedIndex + 1) % items.length;
            items[this.highlightedIndex].classList.add('fms-select2-results__option--highlighted');
            items[this.highlightedIndex].scrollIntoView({ block: 'nearest' });
        },

        highlightPrev: function () {
            var items = this.visibleItems();
            if (!items.length) return;
            this.clearHighlight();
            this.highlightedIndex = this.highlightedIndex > 0
                ? this.highlightedIndex - 1 : items.length - 1;
            items[this.highlightedIndex].classList.add('fms-select2-results__option--highlighted');
            items[this.highlightedIndex].scrollIntoView({ block: 'nearest' });
        },

        clearHighlight: function () {
            var items = this.visibleItems();
            for (var i = 0; i < items.length; i++) {
                items[i].classList.remove('fms-select2-results__option--highlighted');
            }
        },

        findOption: function (value) {
            for (var i = 0; i < this.element.options.length; i++) {
                if (this.element.options[i].value === value) return this.element.options[i];
            }
            return null;
        },

        // Ambil label untuk sebuah nilai (dari <option> atau cache AJAX)
        labelFor: function (value) {
            var opt = this.findOption(value);
            if (opt) return opt.text;
            var cached = this.ajaxCache[String(value)];
            return cached ? cached.text : String(value);
        },

        // Buat <option> bila nilai hasil AJAX belum ada, agar form tetap mengirim
        ensureOption: function (value, text) {
            var existing = this.findOption(value);
            if (existing) {
                if (text != null && existing.text !== text) existing.text = text;
                return existing;
            }
            var opt = document.createElement('option');
            opt.value = value;
            opt.text = text != null ? text : value;
            this.element.appendChild(opt);
            return opt;
        },

        // Bersihkan <option> yang dibuat untuk item AJAX (bukan opsi asli halaman)
        pruneAjaxOptions: function () {
            if (!this.options.ajax) return;
            var self = this;
            var keep = this.selectedValues.map(String);
            Array.prototype.slice.call(this.element.options).forEach(function (opt) {
                if (opt.getAttribute('data-fms-ajax') === '1' && keep.indexOf(opt.value) === -1) {
                    self.element.removeChild(opt);
                }
            });
        },

        /* -------------------- select / deselect -------------------- */

        select: function (value) {
            if (this.isDisabled) return;
            value = String(value);
            if (this.multiple) {
                if (this.selectedValues.indexOf(value) === -1) {
                    if (this.options.maximumSelectionLength > 0 &&
                        this.selectedValues.length >= this.options.maximumSelectionLength) {
                        return;
                    }
                    if (this.options.ajax) {
                        var cached = this.ajaxCache[value];
                        var o = this.ensureOption(value, cached ? cached.text : value);
                        o.setAttribute('data-fms-ajax', '1');
                    }
                    this.selectedValues.push(value);
                    this.updateElement();
                    this.renderMultiSelection();
                    if (this.searchInput) {
                        this.searchInput.value = '';
                        this.searchInput.focus();
                    }
                }
            } else {
                this.selectedValues = [value];
                var option = this.findOption(value);
                if (!option && this.options.ajax) {
                    var c = this.ajaxCache[value];
                    option = this.ensureOption(value, c ? c.text : value);
                    option.setAttribute('data-fms-ajax', '1');
                }
                this.updateSelectionText(option ? option.text : '', value);
                this.updateElement();
                if (this.options.closeOnSelect) this.close();
            }
            if (this.options.ajax) {
                this.renderAjaxResults();
            } else {
                this.populateResults();
                this.filterResults();
            }
            this.triggerEvent('select', value);
            this.triggerEvent('change', value);
        },

        deselect: function (value) {
            if (!this.multiple) return;
            value = String(value);
            var idx = this.selectedValues.indexOf(value);
            if (idx === -1) return;
            this.selectedValues.splice(idx, 1);
            if (this.options.ajax) this.pruneAjaxOptions();
            this.updateElement();
            this.renderMultiSelection();
            if (this.options.ajax) {
                this.renderAjaxResults();
            } else {
                this.populateResults();
                this.filterResults();
            }
            this.triggerEvent('unselect', value);
            this.triggerEvent('change', value);
            if (this.searchInput) this.searchInput.focus();
        },

        clear: function () {
            this.selectedValues = [];
            if (this.options.ajax) this.pruneAjaxOptions();
            this.updateElement();

            if (this.multiple) {
                this.renderMultiSelection();
            } else {
                this.updateSelectionText('', '');
            }
            if (this.options.ajax) {
                this.renderAjaxResults();
            } else {
                this.populateResults();
                this.filterResults();
            }
            this.triggerEvent('clear');
            this.triggerEvent('change', null);
        },

        updateElement: function () {
            var self = this;
            for (var i = 0; i < this.element.options.length; i++) {
                var opt = self.element.options[i];
                opt.selected = self.selectedValues.indexOf(opt.value) !== -1;
            }
            this.refreshPlaceholder();
            var evt = new Event('change', { bubbles: true });
            this.element.dispatchEvent(evt);
        },

        /* -------------------- state -------------------- */

        enable: function () {
            this.isDisabled = false;
            this.element.disabled = false;
            this.container.classList.remove('fms-select2-container--disabled');
            this.selection.tabIndex = 0;
            if (this.searchInput) this.searchInput.style.display = '';
        },

        disable: function () {
            this.isDisabled = true;
            this.element.disabled = true;
            this.container.classList.add('fms-select2-container--disabled');
            this.selection.tabIndex = -1;
            if (this.isOpen) this.close();
            if (this.clearButton) this.clearButton.style.display = 'none';
        },

        val: function (value) {
            if (value === undefined) {
                return this.multiple ? this.selectedValues.slice() :
                    (this.selectedValues[0] !== undefined ? this.selectedValues[0] : null);
            }
            if (this.multiple) {
                this.selectedValues = Array.isArray(value) ? value.slice() :
                    (value === null || value === '' ? [] : [value]);
                this.renderMultiSelection();
            } else {
                this.selectedValues = (value === null || value === '') ? [] : [value];
                var option = this.findOption(value);
                this.updateSelectionText(option ? option.text : '', option ? value : '');
            }
            this.updateElement();
            this.populateResults();
            this.filterResults();
            return this;
        },

        destroy: function () {
            this.close();
            this.unbindEvents();
            if (this.container.parentNode) this.container.parentNode.removeChild(this.container);
            if (this.dropdown.parentNode) this.dropdown.parentNode.removeChild(this.dropdown);
            this.element.style.display = '';
            this.element.classList.remove('fms-select2-hidden-accessible');
            this.element._fmsSelect2 = null;
        },

        triggerEvent: function (name, value) {
            var evt = new CustomEvent('fms-select2:' + name, {
                bubbles: true,
                detail: { instance: this, value: value !== undefined ? value : this.val() }
            });
            this.element.dispatchEvent(evt);
        },

        /* -------------------- jquery-ish helpers -------------------- */

        on: function (type, fn) {
            this.element.addEventListener(type, fn);
            return this;
        },

        off: function (type, fn) {
            this.element.removeEventListener(type, fn);
            return this;
        },

        trigger: function (type) {
            this.element.dispatchEvent(new Event(type, { bubbles: true }));
            return this;
        },

        prop: function (name, value) {
            if (value === undefined) return this.element[name];
            if (name === 'disabled') {
                value ? this.disable() : this.enable();
            } else {
                this.element[name] = value;
            }
            return this;
        }
    };

    /* ------------------------------------------------------------------ */
    /*  Static API                                                        */
    /* ------------------------------------------------------------------ */

    FMSSelect2.init = function (selector, options) {
        var elements;
        if (typeof selector === 'string') {
            elements = Array.prototype.slice.call(document.querySelectorAll(selector));
        } else if (selector instanceof Element) {
            elements = [selector];
        } else if (selector && selector.length !== undefined) {
            elements = Array.prototype.slice.call(selector);
        } else {
            elements = [];
        }

        var instances = [];
        elements.forEach(function (el) {
            if (el && el.tagName === 'SELECT' && !el._fmsSelect2) {
                instances.push(new FMSSelect2(el, options));
            } else if (el && el._fmsSelect2) {
                instances.push(el._fmsSelect2);
            }
        });
        return instances;
    };

    FMSSelect2.getInstance = function (el) {
        return el ? el._fmsSelect2 || null : null;
    };

    return FMSSelect2;
}));

/**
 * ---------------------------------------------------------------------
 *  jQuery shim — lets legacy template code keep calling
 *  `$('.x').select2({...})`, `.select2('val', v)`, `.select2('destroy')`
 *  even when jQuery is not loaded, and delegates to it when it is.
 * ---------------------------------------------------------------------
 */
(function (global) {
    'use strict';
    var FMS = global.FMSSelect2;
    if (!FMS) return;

    function toArray(sel) {
        if (typeof sel === 'string') {
            return Array.prototype.slice.call(document.querySelectorAll(sel));
        }
        if (sel instanceof Element) return [sel];
        if (sel && sel.length !== undefined) return Array.prototype.slice.call(sel);
        return [];
    }

    function act(sel, args) {
        var method = null;
        var params = [];
        if (typeof args[0] === 'string') {
            method = args[0];
            params = Array.prototype.slice.call(args, 1);
        }
        var anyInstance = null;

        toArray(sel).forEach(function (el) {
            if (method === 'destroy') {
                if (el._fmsSelect2) el._fmsSelect2.destroy();
                return;
            }
            var inst = el._fmsSelect2;
            if (!inst) {
                if (method === null) inst = new FMS(el, args[0] || {});
                else return;              // method call on non-initialised element
            }
            anyInstance = anyInstance || inst;
            if (inst && method && typeof inst[method] === 'function') {
                inst[method].apply(inst, params);
            }
        });
        return anyInstance;
    }

    function makeWrapper(sel, jq) {
        var wrapper = {
            length: toArray(sel).length,
            _sel: sel,
            select2: function () {
                return act(sel, arguments);
            },
            val: function () {
                var hasArg = arguments.length > 0;
                if (!hasArg) {
                    var first = toArray(sel)[0];
                    if (first && first._fmsSelect2) return first._fmsSelect2.val();
                    return first ? first.value : null;
                }
                toArray(sel).forEach(function (el) {
                    if (el._fmsSelect2) el._fmsSelect2.val(arguments[0]);
                    else el.value = arguments[0];
                });
                return wrapper;
            },
            prop: function (name, value) {
                if (value === undefined) {
                    var first = toArray(sel)[0];
                    return first ? first[name] : undefined;
                }
                toArray(sel).forEach(function (el) {
                    if (el._fmsSelect2 && name === 'disabled') {
                        el._fmsSelect2.prop('disabled', value);
                    } else {
                        el[name] = value;
                    }
                });
                return wrapper;
            },
            on: function (type, fn) {
                toArray(sel).forEach(function (el) { el.addEventListener(type, fn); });
                return wrapper;
            },
            off: function (type, fn) {
                toArray(sel).forEach(function (el) { el.removeEventListener(type, fn); });
                return wrapper;
            },
            trigger: function (type) {
                toArray(sel).forEach(function (el) {
                    el.dispatchEvent(new Event(type, { bubbles: true }));
                });
                return wrapper;
            },
            each: function (fn) {
                toArray(sel).forEach(function (el, i) { fn.call(jq || wrapper, i, el); });
                return wrapper;
            }
        };
        return wrapper;
    }

    // Expose a `$`/`jQuery` that handles select2 + val/prop/on/off/trigger
    // if the template loaded without jQuery. When a real jQuery is present we
    // only add the plugin, so behaviour stays identical.
    var realJQ = global.jQuery || global.$;
    global.HermesSelect2 = { act: act, makeWrapper: makeWrapper, toArray: toArray };

    if (realJQ && realJQ.fn) {
        realJQ.fn.select2 = function () {
            var args = arguments;
            return act(this, args) || this;
        };
    } else {
        var shim = function (sel) {
            if (typeof sel === 'function') { // $(fn)
                if (document.readyState !== 'loading') sel();
                else document.addEventListener('DOMContentLoaded', sel);
                return shim('');
            }
            return makeWrapper(sel, shim);
        };
        shim.fn = shim.prototype = {};
        shim.ready = function (fn) { fn(); };
        global.$ = shim;
        global.jQuery = shim;
    }
}(typeof self !== 'undefined' ? self : this));
