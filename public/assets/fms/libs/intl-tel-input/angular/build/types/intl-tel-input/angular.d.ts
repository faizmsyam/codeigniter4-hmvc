import intlTelInput from "../intl-tel-input";
import { Iti } from "../intl-tel-input";
import { OnInit, OnDestroy, ElementRef, EventEmitter, AfterViewInit, OnChanges, SimpleChanges } from "@angular/core";
import { ControlValueAccessor, Validator, AbstractControl, ValidationErrors } from "@angular/forms";
import { SomeOptions } from "../modules/types/public-api";
import * as i0 from "@angular/core";
export { intlTelInput };
export declare const PHONE_ERROR_MESSAGES: string[];
export declare class IntlTelInputComponent implements OnInit, AfterViewInit, OnDestroy, OnChanges, ControlValueAccessor, Validator {
    inputRef: ElementRef<HTMLInputElement>;
    initialValue: string;
    usePreciseValidation: boolean;
    inputProps: object;
    disabled?: boolean;
    initOptions: SomeOptions;
    numberChange: EventEmitter<string>;
    countryChange: EventEmitter<string>;
    validityChange: EventEmitter<boolean>;
    errorCodeChange: EventEmitter<number>;
    blur: EventEmitter<FocusEvent>;
    focus: EventEmitter<FocusEvent>;
    keydown: EventEmitter<KeyboardEvent>;
    keyup: EventEmitter<KeyboardEvent>;
    paste: EventEmitter<ClipboardEvent>;
    click: EventEmitter<MouseEvent>;
    private iti;
    private countryChangeHandler;
    private onChange;
    private onTouched;
    private onValidatorChange;
    ngOnInit(): void;
    ngAfterViewInit(): void;
    ngOnChanges(changes: SimpleChanges): void;
    handleInput(): void;
    handleBlur(event: FocusEvent): void;
    handleFocus(event: FocusEvent): void;
    handleKeyDown(event: KeyboardEvent): void;
    handleKeyUp(event: KeyboardEvent): void;
    handlePaste(event: ClipboardEvent): void;
    handleClick(event: MouseEvent): void;
    /**
     * This method must be called in `ngAfterViewInit` or later lifecycle hooks,
     * not in `ngOnInit` or the `constructor`, as the component needs to be fully initialized.
     */
    getInstance(): Iti | null;
    /**
     * This method must be called in `ngAfterViewInit` or later lifecycle hooks,
     * not in `ngOnInit` or the `constructor`, as the component needs to be fully initialized.
     */
    getInput(): HTMLInputElement | null;
    ngOnDestroy(): void;
    private applyInputProps;
    writeValue(value: string | null): void;
    registerOnChange(fn: any): void;
    registerOnTouched(fn: any): void;
    setDisabledState(isDisabled: boolean): void;
    validate(control: AbstractControl): ValidationErrors | null;
    registerOnValidatorChange(fn: () => void): void;
    static ɵfac: i0.ɵɵFactoryDeclaration<IntlTelInputComponent, never>;
    static ɵcmp: i0.ɵɵComponentDeclaration<IntlTelInputComponent, "intl-tel-input", never, { "initialValue": { "alias": "initialValue"; "required": false; }; "usePreciseValidation": { "alias": "usePreciseValidation"; "required": false; }; "inputProps": { "alias": "inputProps"; "required": false; }; "disabled": { "alias": "disabled"; "required": false; }; "initOptions": { "alias": "initOptions"; "required": false; }; }, { "numberChange": "numberChange"; "countryChange": "countryChange"; "validityChange": "validityChange"; "errorCodeChange": "errorCodeChange"; "blur": "blur"; "focus": "focus"; "keydown": "keydown"; "keyup": "keyup"; "paste": "paste"; "click": "click"; }, never, never, true, never>;
}
