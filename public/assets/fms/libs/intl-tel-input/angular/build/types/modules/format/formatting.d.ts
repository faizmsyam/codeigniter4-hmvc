import { SelectedCountryData } from "../types/public-api";
export declare const beforeSetNumber: (fullNumber: string, dialCode: string, separateDialCode: boolean, selectedCountryData: SelectedCountryData) => string;
export declare const formatNumberAsYouType: (fullNumber: string, telInputValue: string, utils: any, selectedCountryData: SelectedCountryData, separateDialCode: boolean) => string;
