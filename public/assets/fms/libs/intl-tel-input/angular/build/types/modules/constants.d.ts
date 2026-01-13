import { Iso2 } from "../intl-tel-input/data";
export declare const EVENTS: {
    readonly OPEN_COUNTRY_DROPDOWN: "open:countrydropdown";
    readonly CLOSE_COUNTRY_DROPDOWN: "close:countrydropdown";
    readonly COUNTRY_CHANGE: "countrychange";
    readonly INPUT: "input";
};
export declare const CLASSES: {
    readonly HIDE: "iti__hide";
    readonly V_HIDE: "iti__v-hide";
    readonly ARROW_UP: "iti__arrow--up";
    readonly GLOBE: "iti__globe";
    readonly FLAG: "iti__flag";
    readonly COUNTRY_ITEM: "iti__country";
    readonly HIGHLIGHT: "iti__highlight";
};
export declare const KEYS: {
    readonly ARROW_UP: "ArrowUp";
    readonly ARROW_DOWN: "ArrowDown";
    readonly SPACE: " ";
    readonly ENTER: "Enter";
    readonly ESC: "Escape";
    readonly TAB: "Tab";
};
export declare const INPUT_TYPES: {
    readonly PASTE: "insertFromPaste";
    readonly DELETE_FWD: "deleteContentForward";
};
export declare const REGEX: {
    readonly ALPHA_UNICODE: RegExp;
    readonly NON_PLUS_NUMERIC: RegExp;
    readonly NON_PLUS_NUMERIC_GLOBAL: RegExp;
    readonly HIDDEN_SEARCH_CHAR: RegExp;
};
export declare const TIMINGS: {
    readonly SEARCH_DEBOUNCE_MS: 100;
    readonly HIDDEN_SEARCH_RESET_MS: 1000;
    readonly NEXT_TICK: 0;
};
export declare const SENTINELS: {
    readonly UNKNOWN_NUMBER_TYPE: -99;
    readonly UNKNOWN_VALIDATION_ERROR: -99;
};
export declare const LAYOUT: {
    readonly SANE_SELECTED_WITH_DIAL_WIDTH: 78;
    readonly SANE_SELECTED_NO_DIAL_WIDTH: 42;
    readonly INPUT_PADDING_EXTRA_LEFT: 6;
};
export declare const DIAL: {
    readonly PLUS: "+";
    readonly NANP: "1";
};
export declare const UK: {
    readonly ISO2: Iso2;
    readonly DIAL_CODE: "44";
    readonly MOBILE_PREFIX: "7";
    readonly MOBILE_CORE_LENGTH: 10;
};
export declare const US: {
    ISO2: Iso2;
    DIAL_CODE: string;
};
export declare const PLACEHOLDER_MODES: {
    readonly AGGRESSIVE: "aggressive";
    readonly POLITE: "polite";
    readonly OFF: "off";
};
export declare const INITIAL_COUNTRY: {
    readonly AUTO: "auto";
};
export declare const DATA_KEYS: {
    readonly COUNTRY_CODE: "countryCode";
    readonly DIAL_CODE: "dialCode";
};
export declare const ARIA: {
    readonly EXPANDED: "aria-expanded";
    readonly LABEL: "aria-label";
    readonly SELECTED: "aria-selected";
    readonly ACTIVE_DESCENDANT: "aria-activedescendant";
    readonly HASPOPUP: "aria-haspopup";
    readonly CONTROLS: "aria-controls";
    readonly HIDDEN: "aria-hidden";
    readonly AUTOCOMPLETE: "aria-autocomplete";
    readonly MODAL: "aria-modal";
};
