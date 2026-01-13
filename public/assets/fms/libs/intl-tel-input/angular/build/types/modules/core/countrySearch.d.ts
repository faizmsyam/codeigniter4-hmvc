import type { Country } from "../../intl-tel-input/data";
/**
 * Country search: Given raw query, return ordered list of countries by priority buckets.
 * Buckets (in order):
 *  1. exact ISO2 matches
 *  2. name starts with
 *  3. name contains
 *  4. dial code exact match (bare or with plus)
 *  5. dial code contains (with plus form)
 *  6. initials match
 * Each bucket preserves country.priority ordering.
 */
export declare const getMatchedCountries: (countries: Country[], query: string) => Country[];
/**
 * Hidden search (when countrySearch disabled): find first whose name starts with query (case-insensitive).
 */
export declare const findFirstCountryStartingWith: (countries: Country[], query: string) => Country | null;
