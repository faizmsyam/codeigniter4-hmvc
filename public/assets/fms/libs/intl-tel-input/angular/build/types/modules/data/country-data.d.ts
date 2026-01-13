import { Country, Iso2 } from "../../intl-tel-input/data";
import type { AllOptions } from "../types/public-api";
export interface DialCodeProcessingResult {
    dialCodes: Set<string>;
    dialCodeMaxLen: number;
    dialCodeToIso2Map: Record<string, Iso2[]>;
}
export declare const processAllCountries: (options: AllOptions) => Country[];
export declare const translateCountryNames: (countries: Country[], options: AllOptions) => void;
export declare const processDialCodes: (countries: Country[]) => DialCodeProcessingResult;
export declare const sortCountries: (countries: Country[], options: AllOptions) => void;
export declare const cacheSearchTokens: (countries: Country[]) => void;
