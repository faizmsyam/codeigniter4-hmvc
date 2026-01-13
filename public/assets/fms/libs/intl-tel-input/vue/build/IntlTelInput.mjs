import { mergeModels as W, useModel as Q, ref as O, onMounted as J, watch as Z, onUnmounted as tt, withDirectives as et, createElementBlock as it, openBlock as st, mergeProps as nt, vModelText as ot } from "vue";
const rt = [
  [
    "af",
    // Afghanistan
    "93",
    0,
    null,
    "0"
  ],
  [
    "ax",
    // Åland Islands
    "358",
    1,
    ["18", "4"],
    // (4 is a mobile range shared with FI)
    "0"
  ],
  [
    "al",
    // Albania
    "355",
    0,
    null,
    "0"
  ],
  [
    "dz",
    // Algeria
    "213",
    0,
    null,
    "0"
  ],
  [
    "as",
    // American Samoa
    "1",
    5,
    ["684"],
    "1"
  ],
  [
    "ad",
    // Andorra
    "376"
  ],
  [
    "ao",
    // Angola
    "244"
  ],
  [
    "ai",
    // Anguilla
    "1",
    6,
    ["264"],
    "1"
  ],
  [
    "ag",
    // Antigua and Barbuda
    "1",
    7,
    ["268"],
    "1"
  ],
  [
    "ar",
    // Argentina
    "54",
    0,
    null,
    "0"
  ],
  [
    "am",
    // Armenia
    "374",
    0,
    null,
    "0"
  ],
  [
    "aw",
    // Aruba
    "297"
  ],
  [
    "ac",
    // Ascension Island
    "247"
  ],
  [
    "au",
    // Australia
    "61",
    0,
    ["4"],
    // (mobile range shared with CX and CC)
    "0"
  ],
  [
    "at",
    // Austria
    "43",
    0,
    null,
    "0"
  ],
  [
    "az",
    // Azerbaijan
    "994",
    0,
    null,
    "0"
  ],
  [
    "bs",
    // Bahamas
    "1",
    8,
    ["242"],
    "1"
  ],
  [
    "bh",
    // Bahrain
    "973"
  ],
  [
    "bd",
    // Bangladesh
    "880",
    0,
    null,
    "0"
  ],
  [
    "bb",
    // Barbados
    "1",
    9,
    ["246"],
    "1"
  ],
  [
    "by",
    // Belarus
    "375",
    0,
    null,
    "8"
  ],
  [
    "be",
    // Belgium
    "32",
    0,
    null,
    "0"
  ],
  [
    "bz",
    // Belize
    "501"
  ],
  [
    "bj",
    // Benin
    "229"
  ],
  [
    "bm",
    // Bermuda
    "1",
    10,
    ["441"],
    "1"
  ],
  [
    "bt",
    // Bhutan
    "975"
  ],
  [
    "bo",
    // Bolivia
    "591",
    0,
    null,
    "0"
  ],
  [
    "ba",
    // Bosnia and Herzegovina
    "387",
    0,
    null,
    "0"
  ],
  [
    "bw",
    // Botswana
    "267"
  ],
  [
    "br",
    // Brazil
    "55",
    0,
    null,
    "0"
  ],
  [
    "io",
    // British Indian Ocean Territory
    "246"
  ],
  [
    "vg",
    // British Virgin Islands
    "1",
    11,
    ["284"],
    "1"
  ],
  [
    "bn",
    // Brunei
    "673"
  ],
  [
    "bg",
    // Bulgaria
    "359",
    0,
    null,
    "0"
  ],
  [
    "bf",
    // Burkina Faso
    "226"
  ],
  [
    "bi",
    // Burundi
    "257"
  ],
  [
    "kh",
    // Cambodia
    "855",
    0,
    null,
    "0"
  ],
  [
    "cm",
    // Cameroon
    "237"
  ],
  [
    "ca",
    // Canada
    "1",
    1,
    ["204", "226", "236", "249", "250", "257", "263", "289", "306", "343", "354", "365", "367", "368", "382", "403", "416", "418", "428", "431", "437", "438", "450", "468", "474", "506", "514", "519", "548", "579", "581", "584", "587", "604", "613", "639", "647", "672", "683", "705", "709", "742", "753", "778", "780", "782", "807", "819", "825", "867", "873", "879", "902", "905", "942"],
    "1"
  ],
  [
    "cv",
    // Cape Verde
    "238"
  ],
  [
    "bq",
    // Caribbean Netherlands
    "599",
    1,
    ["3", "4", "7"]
  ],
  [
    "ky",
    // Cayman Islands
    "1",
    12,
    ["345"],
    "1"
  ],
  [
    "cf",
    // Central African Republic
    "236"
  ],
  [
    "td",
    // Chad
    "235"
  ],
  [
    "cl",
    // Chile
    "56"
  ],
  [
    "cn",
    // China
    "86",
    0,
    null,
    "0"
  ],
  [
    "cx",
    // Christmas Island
    "61",
    2,
    ["4", "89164"],
    // (4 is a mobile range shared with AU and CC)
    "0"
  ],
  [
    "cc",
    // Cocos (Keeling) Islands
    "61",
    1,
    ["4", "89162"],
    // (4 is a mobile range shared with AU and CX)
    "0"
  ],
  [
    "co",
    // Colombia
    "57",
    0,
    null,
    "0"
  ],
  [
    "km",
    // Comoros
    "269"
  ],
  [
    "cg",
    // Congo (Brazzaville)
    "242"
  ],
  [
    "cd",
    // Congo (Kinshasa)
    "243",
    0,
    null,
    "0"
  ],
  [
    "ck",
    // Cook Islands
    "682"
  ],
  [
    "cr",
    // Costa Rica
    "506"
  ],
  [
    "ci",
    // Côte d'Ivoire
    "225"
  ],
  [
    "hr",
    // Croatia
    "385",
    0,
    null,
    "0"
  ],
  [
    "cu",
    // Cuba
    "53",
    0,
    null,
    "0"
  ],
  [
    "cw",
    // Curaçao
    "599",
    0
  ],
  [
    "cy",
    // Cyprus
    "357"
  ],
  [
    "cz",
    // Czech Republic
    "420"
  ],
  [
    "dk",
    // Denmark
    "45"
  ],
  [
    "dj",
    // Djibouti
    "253"
  ],
  [
    "dm",
    // Dominica
    "1",
    13,
    ["767"],
    "1"
  ],
  [
    "do",
    // Dominican Republic
    "1",
    2,
    ["809", "829", "849"],
    "1"
  ],
  [
    "ec",
    // Ecuador
    "593",
    0,
    null,
    "0"
  ],
  [
    "eg",
    // Egypt
    "20",
    0,
    null,
    "0"
  ],
  [
    "sv",
    // El Salvador
    "503"
  ],
  [
    "gq",
    // Equatorial Guinea
    "240"
  ],
  [
    "er",
    // Eritrea
    "291",
    0,
    null,
    "0"
  ],
  [
    "ee",
    // Estonia
    "372"
  ],
  [
    "sz",
    // Eswatini
    "268"
  ],
  [
    "et",
    // Ethiopia
    "251",
    0,
    null,
    "0"
  ],
  [
    "fk",
    // Falkland Islands (Malvinas)
    "500"
  ],
  [
    "fo",
    // Faroe Islands
    "298"
  ],
  [
    "fj",
    // Fiji
    "679"
  ],
  [
    "fi",
    // Finland
    "358",
    0,
    ["4"],
    // (mobile range shared with AX)
    "0"
  ],
  [
    "fr",
    // France
    "33",
    0,
    null,
    "0"
  ],
  [
    "gf",
    // French Guiana
    "594",
    0,
    null,
    "0"
  ],
  [
    "pf",
    // French Polynesia
    "689"
  ],
  [
    "ga",
    // Gabon
    "241"
  ],
  [
    "gm",
    // Gambia
    "220"
  ],
  [
    "ge",
    // Georgia
    "995",
    0,
    null,
    "0"
  ],
  [
    "de",
    // Germany
    "49",
    0,
    null,
    "0"
  ],
  [
    "gh",
    // Ghana
    "233",
    0,
    null,
    "0"
  ],
  [
    "gi",
    // Gibraltar
    "350"
  ],
  [
    "gr",
    // Greece
    "30"
  ],
  [
    "gl",
    // Greenland
    "299"
  ],
  [
    "gd",
    // Grenada
    "1",
    14,
    ["473"],
    "1"
  ],
  [
    "gp",
    // Guadeloupe
    "590",
    0,
    null,
    "0"
  ],
  [
    "gu",
    // Guam
    "1",
    15,
    ["671"],
    "1"
  ],
  [
    "gt",
    // Guatemala
    "502"
  ],
  [
    "gg",
    // Guernsey
    "44",
    1,
    ["1481", "7781", "7839", "7911"],
    "0"
  ],
  [
    "gn",
    // Guinea
    "224"
  ],
  [
    "gw",
    // Guinea-Bissau
    "245"
  ],
  [
    "gy",
    // Guyana
    "592"
  ],
  [
    "ht",
    // Haiti
    "509"
  ],
  [
    "hn",
    // Honduras
    "504"
  ],
  [
    "hk",
    // Hong Kong SAR China
    "852"
  ],
  [
    "hu",
    // Hungary
    "36",
    0,
    null,
    "06"
  ],
  [
    "is",
    // Iceland
    "354"
  ],
  [
    "in",
    // India
    "91",
    0,
    null,
    "0"
  ],
  [
    "id",
    // Indonesia
    "62",
    0,
    null,
    "0"
  ],
  [
    "ir",
    // Iran
    "98",
    0,
    null,
    "0"
  ],
  [
    "iq",
    // Iraq
    "964",
    0,
    null,
    "0"
  ],
  [
    "ie",
    // Ireland
    "353",
    0,
    null,
    "0"
  ],
  [
    "im",
    // Isle of Man
    "44",
    2,
    ["1624", "74576", "7524", "7624", "7924"],
    "0"
  ],
  [
    "il",
    // Israel
    "972",
    0,
    null,
    "0"
  ],
  [
    "it",
    // Italy
    "39",
    0,
    ["3"]
    // (mobile range shared with VA)
  ],
  [
    "jm",
    // Jamaica
    "1",
    4,
    ["658", "876"],
    "1"
  ],
  [
    "jp",
    // Japan
    "81",
    0,
    null,
    "0"
  ],
  [
    "je",
    // Jersey
    "44",
    3,
    ["1534", "7509", "7700", "7797", "7829", "7937"],
    "0"
  ],
  [
    "jo",
    // Jordan
    "962",
    0,
    null,
    "0"
  ],
  [
    "kz",
    // Kazakhstan
    "7",
    1,
    ["33", "7"],
    // (33 is shared with RU)
    "8"
  ],
  [
    "ke",
    // Kenya
    "254",
    0,
    null,
    "0"
  ],
  [
    "ki",
    // Kiribati
    "686",
    0,
    null,
    "0"
  ],
  [
    "xk",
    // Kosovo
    "383",
    0,
    null,
    "0"
  ],
  [
    "kw",
    // Kuwait
    "965"
  ],
  [
    "kg",
    // Kyrgyzstan
    "996",
    0,
    null,
    "0"
  ],
  [
    "la",
    // Laos
    "856",
    0,
    null,
    "0"
  ],
  [
    "lv",
    // Latvia
    "371"
  ],
  [
    "lb",
    // Lebanon
    "961",
    0,
    null,
    "0"
  ],
  [
    "ls",
    // Lesotho
    "266"
  ],
  [
    "lr",
    // Liberia
    "231",
    0,
    null,
    "0"
  ],
  [
    "ly",
    // Libya
    "218",
    0,
    null,
    "0"
  ],
  [
    "li",
    // Liechtenstein
    "423",
    0,
    null,
    "0"
  ],
  [
    "lt",
    // Lithuania
    "370",
    0,
    null,
    "0"
  ],
  [
    "lu",
    // Luxembourg
    "352"
  ],
  [
    "mo",
    // Macao SAR China
    "853"
  ],
  [
    "mg",
    // Madagascar
    "261",
    0,
    null,
    "0"
  ],
  [
    "mw",
    // Malawi
    "265",
    0,
    null,
    "0"
  ],
  [
    "my",
    // Malaysia
    "60",
    0,
    null,
    "0"
  ],
  [
    "mv",
    // Maldives
    "960"
  ],
  [
    "ml",
    // Mali
    "223"
  ],
  [
    "mt",
    // Malta
    "356"
  ],
  [
    "mh",
    // Marshall Islands
    "692",
    0,
    null,
    "1"
  ],
  [
    "mq",
    // Martinique
    "596",
    0,
    null,
    "0"
  ],
  [
    "mr",
    // Mauritania
    "222"
  ],
  [
    "mu",
    // Mauritius
    "230"
  ],
  [
    "yt",
    // Mayotte
    "262",
    1,
    ["269", "639"],
    "0"
  ],
  [
    "mx",
    // Mexico
    "52"
  ],
  [
    "fm",
    // Micronesia
    "691"
  ],
  [
    "md",
    // Moldova
    "373",
    0,
    null,
    "0"
  ],
  [
    "mc",
    // Monaco
    "377",
    0,
    null,
    "0"
  ],
  [
    "mn",
    // Mongolia
    "976",
    0,
    null,
    "0"
  ],
  [
    "me",
    // Montenegro
    "382",
    0,
    null,
    "0"
  ],
  [
    "ms",
    // Montserrat
    "1",
    16,
    ["664"],
    "1"
  ],
  [
    "ma",
    // Morocco
    "212",
    0,
    ["6", "7"],
    // (mobile ranges shared with EH)
    "0"
  ],
  [
    "mz",
    // Mozambique
    "258"
  ],
  [
    "mm",
    // Myanmar (Burma)
    "95",
    0,
    null,
    "0"
  ],
  [
    "na",
    // Namibia
    "264",
    0,
    null,
    "0"
  ],
  [
    "nr",
    // Nauru
    "674"
  ],
  [
    "np",
    // Nepal
    "977",
    0,
    null,
    "0"
  ],
  [
    "nl",
    // Netherlands
    "31",
    0,
    null,
    "0"
  ],
  [
    "nc",
    // New Caledonia
    "687"
  ],
  [
    "nz",
    // New Zealand
    "64",
    0,
    null,
    "0"
  ],
  [
    "ni",
    // Nicaragua
    "505"
  ],
  [
    "ne",
    // Niger
    "227"
  ],
  [
    "ng",
    // Nigeria
    "234",
    0,
    null,
    "0"
  ],
  [
    "nu",
    // Niue
    "683"
  ],
  [
    "nf",
    // Norfolk Island
    "672"
  ],
  [
    "kp",
    // North Korea
    "850",
    0,
    null,
    "0"
  ],
  [
    "mk",
    // North Macedonia
    "389",
    0,
    null,
    "0"
  ],
  [
    "mp",
    // Northern Mariana Islands
    "1",
    17,
    ["670"],
    "1"
  ],
  [
    "no",
    // Norway
    "47",
    0,
    ["4", "9"]
    // (mobile ranges shared with SJ)
  ],
  [
    "om",
    // Oman
    "968"
  ],
  [
    "pk",
    // Pakistan
    "92",
    0,
    null,
    "0"
  ],
  [
    "pw",
    // Palau
    "680"
  ],
  [
    "ps",
    // Palestinian Territories
    "970",
    0,
    null,
    "0"
  ],
  [
    "pa",
    // Panama
    "507"
  ],
  [
    "pg",
    // Papua New Guinea
    "675"
  ],
  [
    "py",
    // Paraguay
    "595",
    0,
    null,
    "0"
  ],
  [
    "pe",
    // Peru
    "51",
    0,
    null,
    "0"
  ],
  [
    "ph",
    // Philippines
    "63",
    0,
    null,
    "0"
  ],
  [
    "pl",
    // Poland
    "48"
  ],
  [
    "pt",
    // Portugal
    "351"
  ],
  [
    "pr",
    // Puerto Rico
    "1",
    3,
    ["787", "939"],
    "1"
  ],
  [
    "qa",
    // Qatar
    "974"
  ],
  [
    "re",
    // Réunion
    "262",
    0,
    null,
    "0"
  ],
  [
    "ro",
    // Romania
    "40",
    0,
    null,
    "0"
  ],
  [
    "ru",
    // Russia
    "7",
    0,
    ["33"],
    // (shared with KZ)
    "8"
  ],
  [
    "rw",
    // Rwanda
    "250",
    0,
    null,
    "0"
  ],
  [
    "ws",
    // Samoa
    "685"
  ],
  [
    "sm",
    // San Marino
    "378"
  ],
  [
    "st",
    // São Tomé & Príncipe
    "239"
  ],
  [
    "sa",
    // Saudi Arabia
    "966",
    0,
    null,
    "0"
  ],
  [
    "sn",
    // Senegal
    "221"
  ],
  [
    "rs",
    // Serbia
    "381",
    0,
    null,
    "0"
  ],
  [
    "sc",
    // Seychelles
    "248"
  ],
  [
    "sl",
    // Sierra Leone
    "232",
    0,
    null,
    "0"
  ],
  [
    "sg",
    // Singapore
    "65"
  ],
  [
    "sx",
    // Sint Maarten
    "1",
    21,
    ["721"],
    "1"
  ],
  [
    "sk",
    // Slovakia
    "421",
    0,
    null,
    "0"
  ],
  [
    "si",
    // Slovenia
    "386",
    0,
    null,
    "0"
  ],
  [
    "sb",
    // Solomon Islands
    "677"
  ],
  [
    "so",
    // Somalia
    "252",
    0,
    null,
    "0"
  ],
  [
    "za",
    // South Africa
    "27",
    0,
    null,
    "0"
  ],
  [
    "kr",
    // South Korea
    "82",
    0,
    null,
    "0"
  ],
  [
    "ss",
    // South Sudan
    "211",
    0,
    null,
    "0"
  ],
  [
    "es",
    // Spain
    "34"
  ],
  [
    "lk",
    // Sri Lanka
    "94",
    0,
    null,
    "0"
  ],
  [
    "bl",
    // St. Barthélemy
    "590",
    1,
    null,
    "0"
  ],
  [
    "sh",
    // St. Helena
    "290"
  ],
  [
    "kn",
    // St. Kitts & Nevis
    "1",
    18,
    ["869"],
    "1"
  ],
  [
    "lc",
    // St. Lucia
    "1",
    19,
    ["758"],
    "1"
  ],
  [
    "mf",
    // St. Martin
    "590",
    2,
    null,
    "0"
  ],
  [
    "pm",
    // St. Pierre & Miquelon
    "508",
    0,
    null,
    "0"
  ],
  [
    "vc",
    // St. Vincent & Grenadines
    "1",
    20,
    ["784"],
    "1"
  ],
  [
    "sd",
    // Sudan
    "249",
    0,
    null,
    "0"
  ],
  [
    "sr",
    // Suriname
    "597"
  ],
  [
    "sj",
    // Svalbard & Jan Mayen
    "47",
    1,
    ["4", "79", "9"]
    // (4 and 9 are mobile ranges shared with NO)
  ],
  [
    "se",
    // Sweden
    "46",
    0,
    null,
    "0"
  ],
  [
    "ch",
    // Switzerland
    "41",
    0,
    null,
    "0"
  ],
  [
    "sy",
    // Syria
    "963",
    0,
    null,
    "0"
  ],
  [
    "tw",
    // Taiwan
    "886",
    0,
    null,
    "0"
  ],
  [
    "tj",
    // Tajikistan
    "992"
  ],
  [
    "tz",
    // Tanzania
    "255",
    0,
    null,
    "0"
  ],
  [
    "th",
    // Thailand
    "66",
    0,
    null,
    "0"
  ],
  [
    "tl",
    // Timor-Leste
    "670"
  ],
  [
    "tg",
    // Togo
    "228"
  ],
  [
    "tk",
    // Tokelau
    "690"
  ],
  [
    "to",
    // Tonga
    "676"
  ],
  [
    "tt",
    // Trinidad & Tobago
    "1",
    22,
    ["868"],
    "1"
  ],
  [
    "tn",
    // Tunisia
    "216"
  ],
  [
    "tr",
    // Turkey
    "90",
    0,
    null,
    "0"
  ],
  [
    "tm",
    // Turkmenistan
    "993",
    0,
    null,
    "8"
  ],
  [
    "tc",
    // Turks & Caicos Islands
    "1",
    23,
    ["649"],
    "1"
  ],
  [
    "tv",
    // Tuvalu
    "688"
  ],
  [
    "vi",
    // U.S. Virgin Islands
    "1",
    24,
    ["340"],
    "1"
  ],
  [
    "ug",
    // Uganda
    "256",
    0,
    null,
    "0"
  ],
  [
    "ua",
    // Ukraine
    "380",
    0,
    null,
    "0"
  ],
  [
    "ae",
    // United Arab Emirates
    "971",
    0,
    null,
    "0"
  ],
  [
    "gb",
    // United Kingdom
    "44",
    0,
    null,
    "0"
  ],
  [
    "us",
    // United States
    "1",
    0,
    null,
    "1"
  ],
  [
    "uy",
    // Uruguay
    "598",
    0,
    null,
    "0"
  ],
  [
    "uz",
    // Uzbekistan
    "998"
  ],
  [
    "vu",
    // Vanuatu
    "678"
  ],
  [
    "va",
    // Vatican City
    "39",
    1,
    ["06698", "3"]
    // (3 is a mobile range shared with IT)
  ],
  [
    "ve",
    // Venezuela
    "58",
    0,
    null,
    "0"
  ],
  [
    "vn",
    // Vietnam
    "84",
    0,
    null,
    "0"
  ],
  [
    "wf",
    // Wallis & Futuna
    "681"
  ],
  [
    "eh",
    // Western Sahara
    "212",
    1,
    ["5288", "5289", "6", "7"],
    // (6 and 7 are mobile ranges shared with MA)
    "0"
  ],
  [
    "ye",
    // Yemen
    "967",
    0,
    null,
    "0"
  ],
  [
    "zm",
    // Zambia
    "260",
    0,
    null,
    "0"
  ],
  [
    "zw",
    // Zimbabwe
    "263",
    0,
    null,
    "0"
  ]
], A = [];
for (const l of rt)
  A.push({
    name: "",
    // populated in the plugin
    iso2: l[0],
    dialCode: l[1],
    priority: l[2] || 0,
    areaCodes: l[3] || null,
    nodeById: {},
    // populated by the plugin
    nationalPrefix: l[4] || null,
    normalisedName: "",
    // populated in the plugin
    initials: "",
    // populated in the plugin
    dialCodePlus: ""
    // populated in the plugin
  });
const lt = {
  ad: "Andorra",
  ae: "United Arab Emirates",
  af: "Afghanistan",
  ag: "Antigua & Barbuda",
  ai: "Anguilla",
  al: "Albania",
  am: "Armenia",
  ao: "Angola",
  ar: "Argentina",
  as: "American Samoa",
  at: "Austria",
  au: "Australia",
  aw: "Aruba",
  ax: "Åland Islands",
  az: "Azerbaijan",
  ba: "Bosnia & Herzegovina",
  bb: "Barbados",
  bd: "Bangladesh",
  be: "Belgium",
  bf: "Burkina Faso",
  bg: "Bulgaria",
  bh: "Bahrain",
  bi: "Burundi",
  bj: "Benin",
  bl: "St. Barthélemy",
  bm: "Bermuda",
  bn: "Brunei",
  bo: "Bolivia",
  bq: "Caribbean Netherlands",
  br: "Brazil",
  bs: "Bahamas",
  bt: "Bhutan",
  bw: "Botswana",
  by: "Belarus",
  bz: "Belize",
  ca: "Canada",
  cc: "Cocos (Keeling) Islands",
  cd: "Congo - Kinshasa",
  cf: "Central African Republic",
  cg: "Congo - Brazzaville",
  ch: "Switzerland",
  ci: "Côte d’Ivoire",
  ck: "Cook Islands",
  cl: "Chile",
  cm: "Cameroon",
  cn: "China",
  co: "Colombia",
  cr: "Costa Rica",
  cu: "Cuba",
  cv: "Cape Verde",
  cw: "Curaçao",
  cx: "Christmas Island",
  cy: "Cyprus",
  cz: "Czechia",
  de: "Germany",
  dj: "Djibouti",
  dk: "Denmark",
  dm: "Dominica",
  do: "Dominican Republic",
  dz: "Algeria",
  ec: "Ecuador",
  ee: "Estonia",
  eg: "Egypt",
  eh: "Western Sahara",
  er: "Eritrea",
  es: "Spain",
  et: "Ethiopia",
  fi: "Finland",
  fj: "Fiji",
  fk: "Falkland Islands",
  fm: "Micronesia",
  fo: "Faroe Islands",
  fr: "France",
  ga: "Gabon",
  gb: "United Kingdom",
  gd: "Grenada",
  ge: "Georgia",
  gf: "French Guiana",
  gg: "Guernsey",
  gh: "Ghana",
  gi: "Gibraltar",
  gl: "Greenland",
  gm: "Gambia",
  gn: "Guinea",
  gp: "Guadeloupe",
  gq: "Equatorial Guinea",
  gr: "Greece",
  gt: "Guatemala",
  gu: "Guam",
  gw: "Guinea-Bissau",
  gy: "Guyana",
  hk: "Hong Kong SAR China",
  hn: "Honduras",
  hr: "Croatia",
  ht: "Haiti",
  hu: "Hungary",
  id: "Indonesia",
  ie: "Ireland",
  il: "Israel",
  im: "Isle of Man",
  in: "India",
  io: "British Indian Ocean Territory",
  iq: "Iraq",
  ir: "Iran",
  is: "Iceland",
  it: "Italy",
  je: "Jersey",
  jm: "Jamaica",
  jo: "Jordan",
  jp: "Japan",
  ke: "Kenya",
  kg: "Kyrgyzstan",
  kh: "Cambodia",
  ki: "Kiribati",
  km: "Comoros",
  kn: "St. Kitts & Nevis",
  kp: "North Korea",
  kr: "South Korea",
  kw: "Kuwait",
  ky: "Cayman Islands",
  kz: "Kazakhstan",
  la: "Laos",
  lb: "Lebanon",
  lc: "St. Lucia",
  li: "Liechtenstein",
  lk: "Sri Lanka",
  lr: "Liberia",
  ls: "Lesotho",
  lt: "Lithuania",
  lu: "Luxembourg",
  lv: "Latvia",
  ly: "Libya",
  ma: "Morocco",
  mc: "Monaco",
  md: "Moldova",
  me: "Montenegro",
  mf: "St. Martin",
  mg: "Madagascar",
  mh: "Marshall Islands",
  mk: "North Macedonia",
  ml: "Mali",
  mm: "Myanmar (Burma)",
  mn: "Mongolia",
  mo: "Macao SAR China",
  mp: "Northern Mariana Islands",
  mq: "Martinique",
  mr: "Mauritania",
  ms: "Montserrat",
  mt: "Malta",
  mu: "Mauritius",
  mv: "Maldives",
  mw: "Malawi",
  mx: "Mexico",
  my: "Malaysia",
  mz: "Mozambique",
  na: "Namibia",
  nc: "New Caledonia",
  ne: "Niger",
  nf: "Norfolk Island",
  ng: "Nigeria",
  ni: "Nicaragua",
  nl: "Netherlands",
  no: "Norway",
  np: "Nepal",
  nr: "Nauru",
  nu: "Niue",
  nz: "New Zealand",
  om: "Oman",
  pa: "Panama",
  pe: "Peru",
  pf: "French Polynesia",
  pg: "Papua New Guinea",
  ph: "Philippines",
  pk: "Pakistan",
  pl: "Poland",
  pm: "St. Pierre & Miquelon",
  pr: "Puerto Rico",
  ps: "Palestinian Territories",
  pt: "Portugal",
  pw: "Palau",
  py: "Paraguay",
  qa: "Qatar",
  re: "Réunion",
  ro: "Romania",
  rs: "Serbia",
  ru: "Russia",
  rw: "Rwanda",
  sa: "Saudi Arabia",
  sb: "Solomon Islands",
  sc: "Seychelles",
  sd: "Sudan",
  se: "Sweden",
  sg: "Singapore",
  sh: "St. Helena",
  si: "Slovenia",
  sj: "Svalbard & Jan Mayen",
  sk: "Slovakia",
  sl: "Sierra Leone",
  sm: "San Marino",
  sn: "Senegal",
  so: "Somalia",
  sr: "Suriname",
  ss: "South Sudan",
  st: "São Tomé & Príncipe",
  sv: "El Salvador",
  sx: "Sint Maarten",
  sy: "Syria",
  sz: "Eswatini",
  tc: "Turks & Caicos Islands",
  td: "Chad",
  tg: "Togo",
  th: "Thailand",
  tj: "Tajikistan",
  tk: "Tokelau",
  tl: "Timor-Leste",
  tm: "Turkmenistan",
  tn: "Tunisia",
  to: "Tonga",
  tr: "Turkey",
  tt: "Trinidad & Tobago",
  tv: "Tuvalu",
  tw: "Taiwan",
  tz: "Tanzania",
  ua: "Ukraine",
  ug: "Uganda",
  us: "United States",
  uy: "Uruguay",
  uz: "Uzbekistan",
  va: "Vatican City",
  vc: "St. Vincent & Grenadines",
  ve: "Venezuela",
  vg: "British Virgin Islands",
  vi: "U.S. Virgin Islands",
  vn: "Vietnam",
  vu: "Vanuatu",
  wf: "Wallis & Futuna",
  ws: "Samoa",
  ye: "Yemen",
  yt: "Mayotte",
  za: "South Africa",
  zm: "Zambia",
  zw: "Zimbabwe"
}, at = {
  selectedCountryAriaLabel: "Change country, selected ${countryName} (${dialCode})",
  noCountrySelected: "Select country",
  countryListAriaLabel: "List of countries",
  searchPlaceholder: "Search",
  clearSearchAriaLabel: "Clear search",
  zeroSearchResults: "No results found",
  oneSearchResult: "1 result found",
  multipleSearchResults: "${count} results found",
  // additional countries (not supported by country-list library)
  ac: "Ascension Island",
  xk: "Kosovo"
}, j = { ...lt, ...at }, T = {
  OPEN_COUNTRY_DROPDOWN: "open:countrydropdown",
  CLOSE_COUNTRY_DROPDOWN: "close:countrydropdown",
  COUNTRY_CHANGE: "countrychange",
  INPUT: "input"
  // used for synthetic input trigger
}, h = {
  HIDE: "iti__hide",
  V_HIDE: "iti__v-hide",
  ARROW_UP: "iti__arrow--up",
  GLOBE: "iti__globe",
  FLAG: "iti__flag",
  COUNTRY_ITEM: "iti__country",
  HIGHLIGHT: "iti__highlight"
}, _ = {
  ARROW_UP: "ArrowUp",
  ARROW_DOWN: "ArrowDown",
  SPACE: " ",
  ENTER: "Enter",
  ESC: "Escape",
  TAB: "Tab"
}, F = {
  PASTE: "insertFromPaste",
  DELETE_FWD: "deleteContentForward"
}, E = {
  ALPHA_UNICODE: new RegExp("\\p{L}", "u"),
  // any kind of letter from any language
  NON_PLUS_NUMERIC: /[^+0-9]/,
  // chars that are NOT + or digit
  NON_PLUS_NUMERIC_GLOBAL: /[^+0-9]/g,
  // chars that are NOT + or digit (global)
  HIDDEN_SEARCH_CHAR: /^[a-zA-ZÀ-ÿа-яА-Я ]$/
  // single acceptable hidden-search char
}, ut = {
  HIDDEN_SEARCH_RESET_MS: 1e3
}, $ = {
  UNKNOWN_NUMBER_TYPE: -99,
  UNKNOWN_VALIDATION_ERROR: -99
}, R = {
  SANE_SELECTED_WITH_DIAL_WIDTH: 78,
  // px width fallback when separateDialCode enabled
  SANE_SELECTED_NO_DIAL_WIDTH: 42,
  // px width fallback when no separate dial code
  INPUT_PADDING_EXTRA_LEFT: 6
  // px gap between selected country container and input text
}, K = {
  NANP: "1"
  // North American Numbering Plan
}, k = {
  DIAL_CODE: "44",
  // +44 United Kingdom
  MOBILE_PREFIX: "7",
  // UK mobile numbers start with 7 after national trunk (0) or core section
  MOBILE_CORE_LENGTH: 10
  // core number length (excluding dial code / national prefix) for mobiles
}, dt = {
  ISO2: "us"
}, H = {
  AGGRESSIVE: "aggressive",
  POLITE: "polite"
}, M = {
  AUTO: "auto"
}, G = {
  COUNTRY_CODE: "countryCode",
  DIAL_CODE: "dialCode"
}, m = {
  EXPANDED: "aria-expanded",
  LABEL: "aria-label",
  SELECTED: "aria-selected",
  ACTIVE_DESCENDANT: "aria-activedescendant",
  HASPOPUP: "aria-haspopup",
  CONTROLS: "aria-controls",
  HIDDEN: "aria-hidden",
  AUTOCOMPLETE: "aria-autocomplete",
  MODAL: "aria-modal"
}, x = (l) => typeof window < "u" && typeof window.matchMedia == "function" && window.matchMedia(l).matches, ct = () => {
  if (typeof navigator < "u" && typeof window < "u") {
    const l = /Android.+Mobile|webOS|iPhone|iPod|BlackBerry|IEMobile|Opera Mini/i.test(
      navigator.userAgent
    ), t = x("(max-width: 500px)"), e = x("(max-height: 600px)"), i = x("(pointer: coarse)");
    return l || t || i && e;
  }
  return !1;
}, Y = {
  // Allow alphanumeric "phonewords" (e.g. +1 800 FLOWERS) as valid numbers
  allowPhonewords: !1,
  //* Whether or not to allow the dropdown.
  allowDropdown: !0,
  //* Add a placeholder in the input with an example number for the selected country.
  autoPlaceholder: H.POLITE,
  //* Modify the parentClass.
  containerClass: "",
  //* The order of the countries in the dropdown. Defaults to alphabetical.
  countryOrder: null,
  //* Add a country search input at the top of the dropdown.
  countrySearch: !0,
  //* Modify the auto placeholder.
  customPlaceholder: null,
  //* Append menu to specified element.
  dropdownContainer: null,
  //* Don't display these countries.
  excludeCountries: [],
  //* Fix the dropdown width to the input width (rather than being as wide as the longest country name).
  fixDropdownWidth: !0,
  //* Format the number as the user types
  formatAsYouType: !0,
  //* Format the input value during initialisation and on setNumber.
  formatOnDisplay: !0,
  //* geoIp lookup function.
  geoIpLookup: null,
  //* Inject a hidden input with the name returned from this function, and on submit, populate it with the result of getNumber.
  hiddenInput: null,
  //* Internationalise the plugin text e.g. search input placeholder, country names.
  i18n: {},
  //* Initial country.
  initialCountry: "",
  //* A function to load the utils script.
  loadUtils: null,
  //* National vs international formatting for numbers e.g. placeholders and displaying existing numbers.
  nationalMode: !0,
  //* Display only these countries.
  onlyCountries: [],
  //* Number type to use for placeholders.
  placeholderNumberType: "MOBILE",
  //* Show flags - for both the selected country, and in the country dropdown
  showFlags: !0,
  //* Display the international dial code next to the selected flag.
  separateDialCode: !1,
  //* Only allow certain chars e.g. a plus followed by numeric digits, and cap at max valid length.
  strictMode: !1,
  //* Use full screen popup instead of dropdown for country list.
  useFullscreenPopup: ct(),
  //* The number type to enforce during validation.
  validationNumberTypes: ["MOBILE"]
}, ht = (l, t) => {
  l.useFullscreenPopup && (l.fixDropdownWidth = !1), l.onlyCountries.length === 1 && (l.initialCountry = l.onlyCountries[0]), l.separateDialCode && (l.nationalMode = !1), l.allowDropdown && !l.showFlags && !l.separateDialCode && (l.nationalMode = !1), l.useFullscreenPopup && !l.dropdownContainer && (l.dropdownContainer = document.body), l.i18n = { ...t, ...l.i18n };
}, v = (l) => l.replace(/\D/g, ""), q = (l = "") => l.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase(), pt = (l, t) => {
  const e = q(t), i = [], s = [], o = [], n = [], r = [], a = [];
  for (const c of l)
    c.iso2 === e ? i.push(c) : c.normalisedName.startsWith(e) ? s.push(c) : c.normalisedName.includes(e) ? o.push(c) : e === c.dialCode || e === c.dialCodePlus ? n.push(c) : c.dialCodePlus.includes(e) ? r.push(c) : c.initials.includes(e) && a.push(c);
  const d = (c, p) => c.priority - p.priority;
  return [
    ...i.sort(d),
    ...s.sort(d),
    ...o.sort(d),
    ...n.sort(d),
    ...r.sort(d),
    ...a.sort(d)
  ];
}, Ct = (l, t) => {
  const e = t.toLowerCase();
  for (const i of l)
    if (i.name.toLowerCase().startsWith(e))
      return i;
  return null;
}, U = (l) => Object.keys(l).filter((t) => !!l[t]).join(" "), f = (l, t, e) => {
  const i = document.createElement(l);
  return t && Object.entries(t).forEach(
    ([s, o]) => i.setAttribute(s, o)
  ), e && e.appendChild(i), i;
}, mt = () => `
  <svg class="iti__search-icon-svg" width="14" height="14" viewBox="0 0 24 24" focusable="false" ${m.HIDDEN}="true">
    <circle cx="11" cy="11" r="7" />
    <line x1="21" y1="21" x2="16.65" y2="16.65" />
  </svg>`, ft = (l) => {
  const t = `iti-${l}-clear-mask`;
  return `
    <svg class="iti__search-clear-svg" width="12" height="12" viewBox="0 0 16 16" ${m.HIDDEN}="true" focusable="false">
      <mask id="${t}" maskUnits="userSpaceOnUse">
        <rect width="16" height="16" fill="white" />
        <path d="M5.2 5.2 L10.8 10.8 M10.8 5.2 L5.2 10.8" stroke="black" stroke-linecap="round" class="iti__search-clear-x" />
      </mask>
      <circle cx="8" cy="8" r="8" class="iti__search-clear-bg" mask="url(#${t})" />
    </svg>`;
};
class yt {
  constructor(t, e, i) {
    this.highlightedItem = null, this.selectedItem = null, t.dataset.intlTelInputId = i.toString(), this.telInput = t, this.options = e, this.id = i, this.hadInitialPlaceholder = !!t.getAttribute("placeholder"), this.isRTL = !!this.telInput.closest("[dir=rtl]"), this.options.separateDialCode && (this.originalPaddingLeft = this.telInput.style.paddingLeft);
  }
  //* Generate all of the markup for the plugin: the selected country overlay, and the dropdown.
  generateMarkup(t) {
    this.countries = t, this._prepareTelInput();
    const e = this._createWrapperAndInsert();
    this._maybeBuildCountryContainer(e), e.appendChild(this.telInput), this._maybeUpdateInputPaddingAndReveal(), this._maybeBuildHiddenInputs(e);
  }
  _prepareTelInput() {
    var t;
    this.telInput.classList.add("iti__tel-input"), !this.telInput.hasAttribute("autocomplete") && !((t = this.telInput.form) != null && t.hasAttribute("autocomplete")) && this.telInput.setAttribute("autocomplete", "off");
  }
  _createWrapperAndInsert() {
    const { allowDropdown: t, showFlags: e, containerClass: i, useFullscreenPopup: s } = this.options, o = U({
      iti: !0,
      "iti--allow-dropdown": t,
      "iti--show-flags": e,
      "iti--inline-dropdown": !s,
      [i]: !!i
    }), n = f("div", { class: o });
    return this.isRTL && n.setAttribute("dir", "ltr"), this.telInput.before(n), n;
  }
  _maybeBuildCountryContainer(t) {
    const { allowDropdown: e, separateDialCode: i, showFlags: s } = this.options;
    if (e || s || i) {
      this.countryContainer = f(
        "div",
        // visibly hidden until we measure it's width to set the input padding correctly
        { class: `iti__country-container ${h.V_HIDE}` },
        t
      ), e ? (this.selectedCountry = f(
        "button",
        {
          type: "button",
          class: "iti__selected-country",
          [m.EXPANDED]: "false",
          [m.LABEL]: this.options.i18n.noCountrySelected,
          [m.HASPOPUP]: "dialog",
          [m.CONTROLS]: `iti-${this.id}__dropdown-content`
        },
        this.countryContainer
      ), this.telInput.disabled && this.selectedCountry.setAttribute("disabled", "true")) : this.selectedCountry = f(
        "div",
        { class: "iti__selected-country" },
        this.countryContainer
      );
      const o = f(
        "div",
        { class: "iti__selected-country-primary" },
        this.selectedCountry
      );
      this.selectedCountryInner = f(
        "div",
        { class: h.FLAG },
        o
      ), e && (this.dropdownArrow = f(
        "div",
        { class: "iti__arrow", [m.HIDDEN]: "true" },
        o
      )), i && (this.selectedDialCode = f(
        "div",
        { class: "iti__selected-dial-code" },
        this.selectedCountry
      )), e && this._buildDropdownContent();
    }
  }
  _buildDropdownContent() {
    const {
      fixDropdownWidth: t,
      useFullscreenPopup: e,
      countrySearch: i,
      i18n: s,
      dropdownContainer: o,
      containerClass: n
    } = this.options, r = t ? "" : "iti--flexible-dropdown-width";
    if (this.dropdownContent = f("div", {
      id: `iti-${this.id}__dropdown-content`,
      class: `iti__dropdown-content ${h.HIDE} ${r}`,
      role: "dialog",
      [m.MODAL]: "true"
    }), this.isRTL && this.dropdownContent.setAttribute("dir", "rtl"), i && this._buildSearchUI(), this.countryList = f(
      "ul",
      {
        class: "iti__country-list",
        id: `iti-${this.id}__country-listbox`,
        role: "listbox",
        [m.LABEL]: s.countryListAriaLabel
      },
      this.dropdownContent
    ), this._appendListItems(), i && this.updateSearchResultsA11yText(), o) {
      const a = U({
        iti: !0,
        "iti--container": !0,
        "iti--fullscreen-popup": e,
        "iti--inline-dropdown": !e,
        [n]: !!n
      });
      this.dropdown = f("div", { class: a }), this.dropdown.appendChild(this.dropdownContent);
    } else
      this.countryContainer.appendChild(this.dropdownContent);
  }
  _buildSearchUI() {
    const { i18n: t } = this.options, e = f(
      "div",
      { class: "iti__search-input-wrapper" },
      this.dropdownContent
    );
    this.searchIcon = f(
      "span",
      {
        class: "iti__search-icon",
        [m.HIDDEN]: "true"
      },
      e
    ), this.searchIcon.innerHTML = mt(), this.searchInput = f(
      "input",
      {
        id: `iti-${this.id}__search-input`,
        // Chrome says inputs need either a name or an id
        type: "search",
        class: "iti__search-input",
        placeholder: t.searchPlaceholder,
        // role=combobox + aria-autocomplete=list + aria-activedescendant allows maintaining focus on the search input while allowing users to navigate search results with up/down keyboard keys
        role: "combobox",
        [m.EXPANDED]: "true",
        [m.LABEL]: t.searchPlaceholder,
        [m.CONTROLS]: `iti-${this.id}__country-listbox`,
        [m.AUTOCOMPLETE]: "list",
        autocomplete: "off"
      },
      e
    ), this.searchClearButton = f(
      "button",
      {
        type: "button",
        class: `iti__search-clear ${h.HIDE}`,
        [m.LABEL]: t.clearSearchAriaLabel,
        tabindex: "-1"
      },
      e
    ), this.searchClearButton.innerHTML = ft(this.id), this.searchResultsA11yText = f(
      "span",
      { class: "iti__a11y-text" },
      this.dropdownContent
    ), this.searchNoResults = f(
      "div",
      {
        class: `iti__no-results ${h.HIDE}`,
        [m.HIDDEN]: "true"
        // all a11y messaging happens in this.searchResultsA11yText
      },
      this.dropdownContent
    ), this.searchNoResults.textContent = t.zeroSearchResults;
  }
  _maybeUpdateInputPaddingAndReveal() {
    this.countryContainer && (this.updateInputPadding(), this.countryContainer.classList.remove(h.V_HIDE));
  }
  _maybeBuildHiddenInputs(t) {
    var i, s;
    const { hiddenInput: e } = this.options;
    if (e) {
      const o = this.telInput.getAttribute("name") || "", n = e(o);
      if (n.phone) {
        const r = (i = this.telInput.form) == null ? void 0 : i.querySelector(
          `input[name="${n.phone}"]`
        );
        r ? this.hiddenInput = r : (this.hiddenInput = f("input", {
          type: "hidden",
          name: n.phone
        }), t.appendChild(this.hiddenInput));
      }
      if (n.country) {
        const r = (s = this.telInput.form) == null ? void 0 : s.querySelector(
          `input[name="${n.country}"]`
        );
        r ? this.hiddenInputCountry = r : (this.hiddenInputCountry = f("input", {
          type: "hidden",
          name: n.country
        }), t.appendChild(this.hiddenInputCountry));
      }
    }
  }
  //* For each country: add a country list item <li> to the countryList <ul> container.
  _appendListItems() {
    const t = document.createDocumentFragment();
    for (let e = 0; e < this.countries.length; e++) {
      const i = this.countries[e], s = U({
        [h.COUNTRY_ITEM]: !0
      }), o = f("li", {
        id: `iti-${this.id}__item-${i.iso2}`,
        class: s,
        tabindex: "-1",
        role: "option",
        [m.SELECTED]: "false"
      });
      o.dataset.dialCode = i.dialCode, o.dataset.countryCode = i.iso2, i.nodeById[this.id] = o, this.options.showFlags && f("div", { class: `${h.FLAG} iti__${i.iso2}` }, o);
      const n = f("span", { class: "iti__country-name" }, o);
      n.textContent = i.name;
      const r = f("span", { class: "iti__dial-code" }, o);
      this.isRTL && r.setAttribute("dir", "ltr"), r.textContent = `+${i.dialCode}`, t.appendChild(o);
    }
    this.countryList.appendChild(t);
  }
  //* Update the input padding to make space for the selected country/dial code.
  updateInputPadding() {
    if (this.selectedCountry) {
      const t = this.options.separateDialCode ? R.SANE_SELECTED_WITH_DIAL_WIDTH : R.SANE_SELECTED_NO_DIAL_WIDTH, i = (this.selectedCountry.offsetWidth || this._getHiddenSelectedCountryWidth() || t) + R.INPUT_PADDING_EXTRA_LEFT;
      this.telInput.style.paddingLeft = `${i}px`;
    }
  }
  //* When input is in a hidden container during init, we cannot calculate the selected country width.
  //* Fix: clone the markup, make it invisible, add it to the end of the DOM, and then measure it's width.
  //* To get the right styling to apply, all we need is a shallow clone of the container,
  //* and then to inject a deep clone of the selectedCountry element.
  _getHiddenSelectedCountryWidth() {
    if (this.telInput.parentNode) {
      let t;
      try {
        t = window.top.document.body;
      } catch {
        t = document.body;
      }
      const e = this.telInput.parentNode.cloneNode(
        !1
      );
      e.style.visibility = "hidden", t.appendChild(e);
      const i = this.countryContainer.cloneNode();
      e.appendChild(i);
      const s = this.selectedCountry.cloneNode(
        !0
      );
      i.appendChild(s);
      const o = s.offsetWidth;
      return t.removeChild(e), o;
    }
    return 0;
  }
  //* Update search results text (for a11y).
  updateSearchResultsA11yText() {
    const { i18n: t } = this.options, e = this.countryList.childElementCount;
    let i;
    e === 0 ? i = t.zeroSearchResults : t.searchResultsText ? i = t.searchResultsText(e) : e === 1 ? i = t.oneSearchResult : i = t.multipleSearchResults.replace(
      "${count}",
      e.toString()
    ), this.searchResultsA11yText.textContent = i;
  }
  //* Check if an element is visible within it's container, else scroll until it is.
  scrollTo(t) {
    const e = this.countryList, i = document.documentElement.scrollTop, s = e.offsetHeight, o = e.getBoundingClientRect().top + i, n = o + s, r = t.offsetHeight, a = t.getBoundingClientRect().top + i, d = a + r, c = a - o + e.scrollTop;
    if (a < o)
      e.scrollTop = c;
    else if (d > n) {
      const p = s - r;
      e.scrollTop = c - p;
    }
  }
  //* Remove highlighting from the previous list item and highlight the new one.
  highlightListItem(t, e) {
    const i = this.highlightedItem;
    if (i && i.classList.remove(h.HIGHLIGHT), this.highlightedItem = t, this.highlightedItem && (this.highlightedItem.classList.add(h.HIGHLIGHT), this.options.countrySearch)) {
      const s = this.highlightedItem.getAttribute("id") || "";
      this.searchInput.setAttribute(m.ACTIVE_DESCENDANT, s);
    }
    e && this.highlightedItem.focus();
  }
  updateSelectedItem(t) {
    if (this.selectedItem && this.selectedItem.dataset.countryCode !== t && (this.selectedItem.setAttribute(m.SELECTED, "false"), this.selectedItem = null), t && !this.selectedItem) {
      const e = this.countryList.querySelector(
        `[data-country-code="${t}"]`
      );
      e && (e.setAttribute(m.SELECTED, "true"), this.selectedItem = e);
    }
  }
  //* Country search: Filter the country list to the given array of countries.
  filterCountries(t) {
    this.countryList.innerHTML = "";
    let e = !0;
    for (const i of t) {
      const s = i.nodeById[this.id];
      s && (this.countryList.appendChild(s), e && (this.highlightListItem(s, !1), e = !1));
    }
    e ? (this.highlightListItem(null, !1), this.searchNoResults && this.searchNoResults.classList.remove(h.HIDE)) : this.searchNoResults && this.searchNoResults.classList.add(h.HIDE), this.countryList.scrollTop = 0, this.updateSearchResultsA11yText();
  }
  destroy() {
    this.telInput.iti = void 0, delete this.telInput.dataset.intlTelInputId, this.options.separateDialCode && (this.telInput.style.paddingLeft = this.originalPaddingLeft);
    const t = this.telInput.parentNode;
    t.before(this.telInput), t.remove(), this.telInput = null, this.countryContainer = null, this.selectedCountry = null, this.selectedCountryInner = null, this.selectedDialCode = null, this.dropdownArrow = null, this.dropdownContent = null, this.searchInput = null, this.searchIcon = null, this.searchClearButton = null, this.searchNoResults = null, this.searchResultsA11yText = null, this.countryList = null, this.dropdown = null, this.hiddenInput = null, this.hiddenInputCountry = null, this.highlightedItem = null, this.selectedItem = null;
    for (const e of this.countries)
      delete e.nodeById[this.id];
    this.countries = null;
  }
}
const gt = (l) => {
  const { onlyCountries: t, excludeCountries: e } = l;
  if (t.length) {
    const i = t.map(
      (s) => s.toLowerCase()
    );
    return A.filter(
      (s) => i.includes(s.iso2)
    );
  } else if (e.length) {
    const i = e.map(
      (s) => s.toLowerCase()
    );
    return A.filter(
      (s) => !i.includes(s.iso2)
    );
  }
  return A;
}, _t = (l, t) => {
  for (const e of l) {
    const i = e.iso2.toLowerCase();
    t.i18n[i] && (e.name = t.i18n[i]);
  }
}, bt = (l) => {
  const t = /* @__PURE__ */ new Set();
  let e = 0;
  const i = {}, s = (n, r) => {
    if (!n || !r)
      return;
    r.length > e && (e = r.length), i.hasOwnProperty(r) || (i[r] = []);
    const a = i[r];
    a.includes(n) || a.push(n);
  }, o = [...l].sort((n, r) => n.priority - r.priority);
  for (const n of o) {
    t.has(n.dialCode) || t.add(n.dialCode);
    for (let r = 1; r < n.dialCode.length; r++) {
      const a = n.dialCode.substring(0, r);
      s(n.iso2, a);
    }
    if (s(n.iso2, n.dialCode), n.areaCodes) {
      const r = i[n.dialCode][0];
      for (const a of n.areaCodes) {
        for (let d = 1; d < a.length; d++) {
          const c = a.substring(0, d), p = n.dialCode + c;
          s(r, p), s(n.iso2, p);
        }
        s(n.iso2, n.dialCode + a);
      }
    }
  }
  return { dialCodes: t, dialCodeMaxLen: e, dialCodeToIso2Map: i };
}, It = (l, t) => {
  t.countryOrder && (t.countryOrder = t.countryOrder.map(
    (e) => e.toLowerCase()
  )), l.sort((e, i) => {
    const { countryOrder: s } = t;
    if (s) {
      const o = s.indexOf(e.iso2), n = s.indexOf(i.iso2), r = o > -1, a = n > -1;
      if (r || a)
        return r && a ? o - n : r ? -1 : 1;
    }
    return e.name.localeCompare(i.name);
  });
}, Nt = (l) => {
  for (const t of l)
    t.normalisedName = q(t.name), t.initials = t.normalisedName.split(/[^a-z]/).map((e) => e[0]).join(""), t.dialCodePlus = `+${t.dialCode}`;
}, wt = (l, t, e, i) => {
  let s = l;
  if (e && t) {
    t = `+${i.dialCode}`;
    const o = s[t.length] === " " || s[t.length] === "-" ? t.length + 1 : t.length;
    s = s.substring(o);
  }
  return s;
}, Dt = (l, t, e, i, s) => {
  const o = e ? e.formatNumberAsYouType(l, i.iso2) : l, { dialCode: n } = i;
  return s && t.charAt(0) !== "+" && o.includes(`+${n}`) ? (o.split(`+${n}`)[1] || "").trim() : o;
}, Lt = (l, t, e, i) => {
  if (e === 0 && !i)
    return 0;
  let s = 0;
  for (let o = 0; o < t.length; o++) {
    if (/[+0-9]/.test(t[o]) && s++, s === l && !i)
      return o + 1;
    if (i && s === l + 1)
      return o;
  }
  return t.length;
}, Et = [
  "800",
  "822",
  "833",
  "844",
  "855",
  "866",
  "877",
  "880",
  "881",
  "882",
  "883",
  "884",
  "885",
  "886",
  "887",
  "888",
  "889"
], z = (l) => {
  const t = v(l);
  if (t.startsWith(K.NANP) && t.length >= 4) {
    const e = t.substring(1, 4);
    return Et.includes(e);
  }
  return !1;
};
for (const l of A)
  l.name = j[l.iso2];
let At = 0;
const St = new Set(A.map((l) => l.iso2)), B = (l) => St.has(l);
class V {
  constructor(t, e = {}) {
    this.id = At++, this.options = { ...Y, ...e }, ht(this.options, j), this.ui = new yt(t, this.options, this.id), this.isAndroid = V._getIsAndroid(), this.promise = this._createInitPromises(), this.countries = gt(this.options);
    const { dialCodes: i, dialCodeMaxLen: s, dialCodeToIso2Map: o } = bt(
      this.countries
    );
    this.dialCodes = i, this.dialCodeMaxLen = s, this.dialCodeToIso2Map = o, this.countryByIso2 = new Map(this.countries.map((n) => [n.iso2, n])), this._init();
  }
  static _getIsAndroid() {
    return typeof navigator < "u" ? /Android/i.test(navigator.userAgent) : !1;
  }
  _updateNumeralSet(t) {
    /[\u0660-\u0669]/.test(t) ? this.userNumeralSet = "arabic-indic" : /[\u06F0-\u06F9]/.test(t) ? this.userNumeralSet = "persian" : this.userNumeralSet = "ascii";
  }
  _mapAsciiToUserNumerals(t) {
    if (this.userNumeralSet || this._updateNumeralSet(this.ui.telInput.value), this.userNumeralSet === "ascii")
      return t;
    const e = this.userNumeralSet === "arabic-indic" ? 1632 : 1776;
    return t.replace(/[0-9]/g, (i) => String.fromCharCode(e + Number(i)));
  }
  // Normalize Eastern Arabic (U+0660-0669) and Persian/Extended Arabic-Indic (U+06F0-06F9) numerals to ASCII 0-9
  _normaliseNumerals(t) {
    if (!t)
      return "";
    if (this._updateNumeralSet(t), this.userNumeralSet === "ascii")
      return t;
    const e = this.userNumeralSet === "arabic-indic" ? 1632 : 1776, i = this.userNumeralSet === "arabic-indic" ? /[\u0660-\u0669]/g : /[\u06F0-\u06F9]/g;
    return t.replace(i, (s) => String.fromCharCode(48 + (s.charCodeAt(0) - e)));
  }
  _getTelInputValue() {
    const t = this.ui.telInput.value.trim();
    return this._normaliseNumerals(t);
  }
  _setTelInputValue(t) {
    this.ui.telInput.value = this._mapAsciiToUserNumerals(t);
  }
  _createInitPromises() {
    const t = new Promise((i, s) => {
      this.resolveAutoCountryPromise = i, this.rejectAutoCountryPromise = s;
    }), e = new Promise((i, s) => {
      this.resolveUtilsScriptPromise = i, this.rejectUtilsScriptPromise = s;
    });
    return Promise.all([t, e]);
  }
  //* Can't be private as it's called from intlTelInput convenience wrapper.
  _init() {
    this.selectedCountryData = {}, this.abortController = new AbortController(), this._processCountryData(), this.ui.generateMarkup(this.countries), this._setInitialState(), this._initListeners(), this._initRequests();
  }
  //********************
  //*  PRIVATE METHODS
  //********************
  //* Prepare all of the country data, including onlyCountries, excludeCountries, countryOrder options.
  _processCountryData() {
    _t(this.countries, this.options), It(this.countries, this.options), Nt(this.countries);
  }
  //* Set the initial state of the input value and the selected country by:
  //* 1. Extracting a dial code from the given number
  //* 2. Using explicit initialCountry
  _setInitialState(t = !1) {
    const e = this.ui.telInput.getAttribute("value"), i = this._normaliseNumerals(e), s = this._getTelInputValue(), n = i && i.startsWith("+") && (!s || !s.startsWith("+")) ? i : s, r = this._getDialCode(n), a = z(n), { initialCountry: d, geoIpLookup: c } = this.options, p = d === M.AUTO && c;
    if (r && !a)
      this._updateCountryFromNumber(n);
    else if (!p || t) {
      const b = d ? d.toLowerCase() : "";
      B(b) ? this._setCountry(b) : r && a ? this._setCountry(dt.ISO2) : this._setCountry("");
    }
    n && this._updateValFromNumber(n);
  }
  //* Initialise the main event listeners: input keyup, and click selected country.
  _initListeners() {
    this._initTelInputListeners(), this.options.allowDropdown && this._initDropdownListeners(), (this.ui.hiddenInput || this.ui.hiddenInputCountry) && this.ui.telInput.form && this._initHiddenInputListener();
  }
  //* Update hidden input on form submit.
  _initHiddenInputListener() {
    var e;
    const t = () => {
      this.ui.hiddenInput && (this.ui.hiddenInput.value = this.getNumber()), this.ui.hiddenInputCountry && (this.ui.hiddenInputCountry.value = this.selectedCountryData.iso2 || "");
    };
    (e = this.ui.telInput.form) == null || e.addEventListener("submit", t, {
      signal: this.abortController.signal
    });
  }
  //* initialise the dropdown listeners.
  _initDropdownListeners() {
    const t = this.abortController.signal, e = (n) => {
      this.ui.dropdownContent.classList.contains(h.HIDE) ? this.ui.telInput.focus() : n.preventDefault();
    }, i = this.ui.telInput.closest("label");
    i && i.addEventListener("click", e, { signal: t });
    const s = () => {
      this.ui.dropdownContent.classList.contains(
        h.HIDE
      ) && !this.ui.telInput.disabled && !this.ui.telInput.readOnly && this._openDropdown();
    };
    this.ui.selectedCountry.addEventListener(
      "click",
      s,
      {
        signal: t
      }
    );
    const o = (n) => {
      this.ui.dropdownContent.classList.contains(
        h.HIDE
      ) && [_.ARROW_UP, _.ARROW_DOWN, _.SPACE, _.ENTER].includes(n.key) && (n.preventDefault(), n.stopPropagation(), this._openDropdown()), n.key === _.TAB && this._closeDropdown();
    };
    this.ui.countryContainer.addEventListener(
      "keydown",
      o,
      { signal: t }
    );
  }
  //* Init many requests: utils script / geo ip lookup.
  _initRequests() {
    const { loadUtils: t, initialCountry: e, geoIpLookup: i } = this.options;
    if (t && !u.utils) {
      const o = () => {
        var n;
        (n = u.attachUtils(t)) == null || n.catch(() => {
        });
      };
      if (u.documentReady())
        o();
      else {
        const n = () => {
          o();
        };
        window.addEventListener("load", n, {
          signal: this.abortController.signal
        });
      }
    } else
      this.resolveUtilsScriptPromise();
    e === M.AUTO && i && !this.selectedCountryData.iso2 ? this._loadAutoCountry() : this.resolveAutoCountryPromise();
  }
  //* Perform the geo ip lookup.
  _loadAutoCountry() {
    u.autoCountry ? this.handleAutoCountry() : u.startedLoadingAutoCountry || (u.startedLoadingAutoCountry = !0, typeof this.options.geoIpLookup == "function" && this.options.geoIpLookup(
      (t = "") => {
        const e = t.toLowerCase();
        B(e) ? (u.autoCountry = e, setTimeout(() => S("handleAutoCountry"))) : (this._setInitialState(!0), S("rejectAutoCountryPromise"));
      },
      () => {
        this._setInitialState(!0), S("rejectAutoCountryPromise");
      }
    ));
  }
  _openDropdownWithPlus() {
    this._openDropdown(), this.ui.searchInput.value = "+", this._filterCountriesByQuery("");
  }
  //* Initialize the tel input listeners.
  _initTelInputListeners() {
    this._bindInputListener(), this._maybeBindKeydownListener(), this._maybeBindPasteListener();
  }
  _bindInputListener() {
    const {
      strictMode: t,
      formatAsYouType: e,
      separateDialCode: i,
      allowDropdown: s,
      countrySearch: o
    } = this.options;
    let n = !1;
    E.ALPHA_UNICODE.test(this._getTelInputValue()) && (n = !0);
    const r = (a) => {
      const d = this._getTelInputValue();
      if (this.isAndroid && (a == null ? void 0 : a.data) === "+" && i && s && o) {
        const y = this.ui.telInput.selectionStart || 0, I = d.substring(0, y - 1), N = d.substring(y);
        this._setTelInputValue(I + N), this._openDropdownWithPlus();
        return;
      }
      this._updateCountryFromNumber(d) && this._triggerCountryChange();
      const c = (a == null ? void 0 : a.data) && E.NON_PLUS_NUMERIC.test(a.data), p = (a == null ? void 0 : a.inputType) === F.PASTE && d;
      c || p && !t ? n = !0 : E.NON_PLUS_NUMERIC.test(d) || (n = !1);
      const b = (a == null ? void 0 : a.detail) && a.detail.isSetNumber, C = this.userNumeralSet === "ascii";
      if (e && !n && !b && C) {
        const y = this.ui.telInput.selectionStart || 0, N = d.substring(
          0,
          y
        ).replace(
          E.NON_PLUS_NUMERIC_GLOBAL,
          ""
        ).length, D = (a == null ? void 0 : a.inputType) === F.DELETE_FWD, g = this._getFullNumber(), L = Dt(
          g,
          d,
          u.utils,
          this.selectedCountryData,
          this.options.separateDialCode
        ), w = Lt(
          N,
          L,
          y,
          D
        );
        this._setTelInputValue(L), this.ui.telInput.setSelectionRange(w, w);
      }
    };
    this.ui.telInput.addEventListener(
      "input",
      r,
      {
        signal: this.abortController.signal
      }
    );
  }
  _maybeBindKeydownListener() {
    const { strictMode: t, separateDialCode: e, allowDropdown: i, countrySearch: s } = this.options;
    if (t || e) {
      const o = (n) => {
        if (n.key && n.key.length === 1 && !n.altKey && !n.ctrlKey && !n.metaKey) {
          if (e && i && s && n.key === "+") {
            n.preventDefault(), this._openDropdownWithPlus();
            return;
          }
          if (t) {
            const r = this._getTelInputValue(), d = !r.startsWith("+") && this.ui.telInput.selectionStart === 0 && n.key === "+", c = this._normaliseNumerals(n.key), p = /^[0-9]$/.test(c), b = e ? p : d || p, C = this.ui.telInput, y = C.selectionStart, I = C.selectionEnd, N = r.slice(0, y), D = r.slice(I), g = N + n.key + D, L = this._getFullNumber(g), w = u.utils.getCoreNumber(
              L,
              this.selectedCountryData.iso2
            ), P = this.maxCoreNumberLength && w.length > this.maxCoreNumberLength, X = this._getNewCountryFromNumber(L) !== null;
            (!b || P && !X && !d) && n.preventDefault();
          }
        }
      };
      this.ui.telInput.addEventListener("keydown", o, {
        signal: this.abortController.signal
      });
    }
  }
  _maybeBindPasteListener() {
    if (this.options.strictMode) {
      const t = (e) => {
        e.preventDefault();
        const i = this.ui.telInput, s = i.selectionStart, o = i.selectionEnd, n = this._getTelInputValue(), r = n.slice(0, s), a = n.slice(o), d = this.selectedCountryData.iso2, c = e.clipboardData.getData("text"), p = this._normaliseNumerals(c), b = s === 0 && o > 0, C = !n.startsWith("+") || b, y = p.replace(E.NON_PLUS_NUMERIC_GLOBAL, ""), I = y.startsWith("+"), N = y.replace(/\+/g, ""), D = I && C ? `+${N}` : N;
        let g = r + D + a;
        if (g.length > 5) {
          let w = u.utils.getCoreNumber(g, d);
          for (; w.length === 0 && g.length > 0; )
            g = g.slice(0, -1), w = u.utils.getCoreNumber(g, d);
          if (!w)
            return;
          if (this.maxCoreNumberLength && w.length > this.maxCoreNumberLength)
            if (i.selectionEnd === n.length) {
              const P = w.length - this.maxCoreNumberLength;
              g = g.slice(0, g.length - P);
            } else
              return;
        }
        this._setTelInputValue(g);
        const L = s + D.length;
        i.setSelectionRange(L, L), i.dispatchEvent(new InputEvent("input", { bubbles: !0 }));
      };
      this.ui.telInput.addEventListener("paste", t, {
        signal: this.abortController.signal
      });
    }
  }
  //* Adhere to the input's maxlength attr.
  _cap(t) {
    const e = Number(this.ui.telInput.getAttribute("maxlength"));
    return e && t.length > e ? t.substring(0, e) : t;
  }
  //* Trigger a custom event on the input (typed via ItiEventMap).
  _trigger(t, e = {}) {
    const i = new CustomEvent(t, {
      bubbles: !0,
      cancelable: !0,
      detail: e
    });
    this.ui.telInput.dispatchEvent(i);
  }
  //* Open the dropdown.
  _openDropdown() {
    const { fixDropdownWidth: t, countrySearch: e } = this.options;
    if (this.dropdownAbortController = new AbortController(), t && (this.ui.dropdownContent.style.width = `${this.ui.telInput.offsetWidth}px`), this.ui.dropdownContent.classList.remove(h.HIDE), this.ui.selectedCountry.setAttribute(m.EXPANDED, "true"), this._setDropdownPosition(), e) {
      const i = this.ui.countryList.firstElementChild;
      i && (this.ui.highlightListItem(i, !1), this.ui.countryList.scrollTop = 0), this.ui.searchInput.focus();
    }
    this._bindDropdownListeners(), this.ui.dropdownArrow.classList.add(h.ARROW_UP), this._trigger(T.OPEN_COUNTRY_DROPDOWN);
  }
  //* Set the dropdown position
  _setDropdownPosition() {
    if (this.options.dropdownContainer && this.options.dropdownContainer.appendChild(this.ui.dropdown), !this.options.useFullscreenPopup) {
      const t = this.ui.telInput.getBoundingClientRect(), e = this.ui.telInput.offsetHeight;
      if (this.options.dropdownContainer) {
        this.ui.dropdown.style.top = `${t.top + e}px`, this.ui.dropdown.style.left = `${t.left}px`;
        const i = () => this._closeDropdown();
        window.addEventListener("scroll", i, {
          signal: this.dropdownAbortController.signal
        });
      }
    }
  }
  //* We only bind dropdown listeners when the dropdown is open.
  _bindDropdownListeners() {
    const t = this.dropdownAbortController.signal;
    this._bindDropdownMouseoverListener(t), this._bindDropdownCountryClickListener(t), this._bindDropdownClickOffListener(t), this._bindDropdownKeydownListener(t), this.options.countrySearch && this._bindDropdownSearchListeners(t);
  }
  //* When mouse over a list item, just highlight that one
  //* we add the class "highlight", so if they hit "enter" we know which one to select.
  _bindDropdownMouseoverListener(t) {
    const e = (i) => {
      var o;
      const s = (o = i.target) == null ? void 0 : o.closest(
        `.${h.COUNTRY_ITEM}`
      );
      s && this.ui.highlightListItem(s, !1);
    };
    this.ui.countryList.addEventListener(
      "mouseover",
      e,
      {
        signal: t
      }
    );
  }
  //* Listen for country selection.
  _bindDropdownCountryClickListener(t) {
    const e = (i) => {
      var o;
      const s = (o = i.target) == null ? void 0 : o.closest(
        `.${h.COUNTRY_ITEM}`
      );
      s && this._selectListItem(s);
    };
    this.ui.countryList.addEventListener("click", e, {
      signal: t
    });
  }
  //* Click off to close (except when this initial opening click is bubbling up).
  //* We cannot just stopPropagation as it may be needed to close another instance.
  _bindDropdownClickOffListener(t) {
    const e = (i) => {
      !!i.target.closest(
        `#iti-${this.id}__dropdown-content`
      ) || this._closeDropdown();
    };
    setTimeout(() => {
      document.documentElement.addEventListener(
        "click",
        e,
        { signal: t }
      );
    }, 0);
  }
  //* Listen for up/down scrolling, enter to select, or escape to close.
  //* Use keydown as keypress doesn't fire for non-char keys and we want to catch if they
  //* just hit down and hold it to scroll down (no keyup event).
  //* Listen on the document because that's where key events are triggered if no input has focus.
  _bindDropdownKeydownListener(t) {
    let e = "", i = null;
    const s = (o) => {
      [
        _.ARROW_UP,
        _.ARROW_DOWN,
        _.ENTER,
        _.ESC
      ].includes(o.key) && (o.preventDefault(), o.stopPropagation(), o.key === _.ARROW_UP || o.key === _.ARROW_DOWN ? this._handleUpDownKey(o.key) : o.key === _.ENTER ? this._handleEnterKey() : o.key === _.ESC && (this._closeDropdown(), this.ui.selectedCountry.focus())), !this.options.countrySearch && E.HIDDEN_SEARCH_CHAR.test(o.key) && (o.stopPropagation(), i && clearTimeout(i), e += o.key.toLowerCase(), this._searchForCountry(e), i = setTimeout(() => {
        e = "";
      }, ut.HIDDEN_SEARCH_RESET_MS));
    };
    document.addEventListener("keydown", s, { signal: t });
  }
  //* Search input listeners when countrySearch enabled.
  _bindDropdownSearchListeners(t) {
    const e = () => {
      const n = this.ui.searchInput.value.trim();
      this._filterCountriesByQuery(n), this.ui.searchInput.value ? this.ui.searchClearButton.classList.remove(h.HIDE) : this.ui.searchClearButton.classList.add(h.HIDE);
    };
    let i = null;
    const s = () => {
      i && clearTimeout(i), i = setTimeout(() => {
        e(), i = null;
      }, 100);
    };
    this.ui.searchInput.addEventListener("input", s, {
      signal: t
    });
    const o = () => {
      this.ui.searchInput.value = "", this.ui.searchInput.focus(), e();
    };
    this.ui.searchClearButton.addEventListener("click", o, {
      signal: t
    });
  }
  //* Hidden search (countrySearch disabled): Find the first list item whose name starts with the query string.
  _searchForCountry(t) {
    const e = Ct(this.countries, t);
    if (e) {
      const i = e.nodeById[this.id];
      this.ui.highlightListItem(i, !1), this.ui.scrollTo(i);
    }
  }
  //* Country search: Filter the countries according to the search query.
  _filterCountriesByQuery(t) {
    let e;
    t === "" ? e = this.countries : e = pt(this.countries, t), this.ui.filterCountries(e);
  }
  //* Highlight the next/prev item in the list (and ensure it is visible).
  _handleUpDownKey(t) {
    var i, s;
    let e = t === _.ARROW_UP ? (i = this.ui.highlightedItem) == null ? void 0 : i.previousElementSibling : (s = this.ui.highlightedItem) == null ? void 0 : s.nextElementSibling;
    !e && this.ui.countryList.childElementCount > 1 && (e = t === _.ARROW_UP ? this.ui.countryList.lastElementChild : this.ui.countryList.firstElementChild), e && (this.ui.scrollTo(e), this.ui.highlightListItem(e, !1));
  }
  //* Select the currently highlighted item.
  _handleEnterKey() {
    this.ui.highlightedItem && this._selectListItem(this.ui.highlightedItem);
  }
  //* Update the input's value to the given val (format first if possible)
  //* NOTE: this is called from _setInitialState, handleUtils and setNumber.
  _updateValFromNumber(t) {
    let e = t;
    if (this.options.formatOnDisplay && u.utils && this.selectedCountryData) {
      const i = this.options.nationalMode || !e.startsWith("+") && !this.options.separateDialCode, { NATIONAL: s, INTERNATIONAL: o } = u.utils.numberFormat, n = i ? s : o;
      e = u.utils.formatNumber(
        e,
        this.selectedCountryData.iso2,
        n
      );
    }
    e = this._beforeSetNumber(e), this._setTelInputValue(e);
  }
  //* Check if need to select a new country based on the given number
  //* Note: called from _setInitialState, keyup handler, setNumber.
  _updateCountryFromNumber(t) {
    const e = this._getNewCountryFromNumber(t);
    return e !== null ? this._setCountry(e) : !1;
  }
  // if there is a selected country, and the number doesn't start with a dial code, then add it
  _ensureHasDialCode(t) {
    const { dialCode: e, nationalPrefix: i } = this.selectedCountryData;
    if (t.startsWith("+") || !e)
      return t;
    const n = i && t.startsWith(i) && !this.options.separateDialCode ? t.substring(1) : t;
    return `+${e}${n}`;
  }
  // Get the country ISO2 code from the given number
  // BUT ONLY IF ITS CHANGED FROM THE CURRENTLY SELECTED COUNTRY
  // NOTE: consider refactoring this to be more clear
  _getNewCountryFromNumber(t) {
    const e = t.indexOf("+");
    let i = e ? t.substring(e) : t;
    const s = this.selectedCountryData.iso2, o = this.selectedCountryData.dialCode;
    i = this._ensureHasDialCode(i);
    const n = this._getDialCode(i, !0), r = v(i);
    if (n) {
      const a = v(n), d = this.dialCodeToIso2Map[a];
      if (d.length === 1)
        return d[0] === s ? null : d[0];
      if (!s && this.defaultCountry && d.includes(this.defaultCountry))
        return this.defaultCountry;
      if (o === K.NANP && z(r))
        return null;
      const { areaCodes: p, priority: b } = this.selectedCountryData;
      if (p) {
        const D = p.map(
          (g) => `${o}${g}`
        );
        for (const g of D)
          if (r.startsWith(g))
            return null;
      }
      const y = p && !(b === 0) && r.length > a.length, I = s && d.includes(s) && !y, N = s === d[0];
      if (!I && !N)
        return d[0];
    } else if (i.startsWith("+") && r.length) {
      const a = this.selectedCountryData.dialCode || "";
      return a && a.startsWith(r) ? null : "";
    } else if ((!i || i === "+") && !s)
      return this.defaultCountry;
    return null;
  }
  //* Update the selected country, dial code (if separateDialCode), placeholder, title, and selected list item.
  //* Note: called from _setInitialState, _updateCountryFromNumber, _selectListItem, setCountry.
  _setCountry(t) {
    const { separateDialCode: e, showFlags: i, i18n: s, allowDropdown: o } = this.options, n = this.selectedCountryData.iso2 || "";
    if (o && this.ui.updateSelectedItem(t), this.selectedCountryData = t ? this.countryByIso2.get(t) : {}, this.selectedCountryData.iso2 && (this.defaultCountry = this.selectedCountryData.iso2), this.ui.selectedCountry) {
      const r = t && i ? `${h.FLAG} iti__${t}` : `${h.FLAG} ${h.GLOBE}`;
      let a, d;
      if (t) {
        const { name: c, dialCode: p } = this.selectedCountryData;
        d = c, a = s.selectedCountryAriaLabel.replace("${countryName}", c).replace("${dialCode}", `+${p}`);
      } else
        d = s.noCountrySelected, a = s.noCountrySelected;
      this.ui.selectedCountryInner.className = r, this.ui.selectedCountry.setAttribute("title", d), this.ui.selectedCountry.setAttribute(m.LABEL, a);
    }
    if (e) {
      const r = this.selectedCountryData.dialCode ? `+${this.selectedCountryData.dialCode}` : "";
      this.ui.selectedDialCode.textContent = r, this.ui.updateInputPadding();
    }
    return this._updatePlaceholder(), this._updateMaxLength(), n !== t;
  }
  //* Update the maximum valid number length for the currently selected country.
  _updateMaxLength() {
    const { strictMode: t, placeholderNumberType: e, validationNumberTypes: i } = this.options, { iso2: s } = this.selectedCountryData;
    if (t && u.utils)
      if (s) {
        const o = u.utils.numberType[e];
        let n = u.utils.getExampleNumber(
          s,
          !1,
          o,
          !0
        ), r = n;
        for (; u.utils.isPossibleNumber(
          n,
          s,
          i
        ); )
          r = n, n += "0";
        const a = u.utils.getCoreNumber(r, s);
        this.maxCoreNumberLength = a.length, s === "by" && (this.maxCoreNumberLength = a.length + 1);
      } else
        this.maxCoreNumberLength = null;
  }
  //* Update the input placeholder to an example number from the currently selected country.
  _updatePlaceholder() {
    const {
      autoPlaceholder: t,
      placeholderNumberType: e,
      nationalMode: i,
      customPlaceholder: s
    } = this.options, o = t === H.AGGRESSIVE || !this.ui.hadInitialPlaceholder && t === H.POLITE;
    if (u.utils && o) {
      const n = u.utils.numberType[e];
      let r = this.selectedCountryData.iso2 ? u.utils.getExampleNumber(
        this.selectedCountryData.iso2,
        i,
        n
      ) : "";
      r = this._beforeSetNumber(r), typeof s == "function" && (r = s(r, this.selectedCountryData)), this.ui.telInput.setAttribute("placeholder", r);
    }
  }
  //* Called when the user selects a list item from the dropdown.
  _selectListItem(t) {
    const e = t.dataset[G.COUNTRY_CODE], i = this._setCountry(e);
    this._closeDropdown();
    const s = t.dataset[G.DIAL_CODE];
    if (this._updateDialCode(s), this.options.formatOnDisplay) {
      const o = this._getTelInputValue();
      this._updateValFromNumber(o);
    }
    this.ui.telInput.focus(), i && this._triggerCountryChange();
  }
  //* Close the dropdown and unbind any listeners.
  _closeDropdown() {
    this.ui.dropdownContent.classList.contains(h.HIDE) || (this.ui.dropdownContent.classList.add(h.HIDE), this.ui.selectedCountry.setAttribute(m.EXPANDED, "false"), this.options.countrySearch && (this.ui.searchInput.removeAttribute(m.ACTIVE_DESCENDANT), this.ui.highlightedItem && (this.ui.highlightedItem.classList.remove(h.HIGHLIGHT), this.ui.highlightedItem = null)), this.ui.dropdownArrow.classList.remove(h.ARROW_UP), this.dropdownAbortController.abort(), this.dropdownAbortController = null, this.options.dropdownContainer && this.ui.dropdown.remove(), this._trigger(T.CLOSE_COUNTRY_DROPDOWN));
  }
  //* Replace any existing dial code with the new one
  //* Note: called from _selectListItem and setCountry
  _updateDialCode(t) {
    const e = this._getTelInputValue(), i = `+${t}`;
    let s;
    if (e.startsWith("+")) {
      const o = this._getDialCode(e);
      o ? s = e.replace(o, i) : s = i, this._setTelInputValue(s);
    }
  }
  //* Try and extract a valid international dial code from a full telephone number.
  //* Note: returns the raw string inc plus character and any whitespace/dots etc.
  _getDialCode(t, e) {
    let i = "";
    if (t.startsWith("+")) {
      let s = "", o = !1;
      for (let n = 0; n < t.length; n++) {
        const r = t.charAt(n);
        if (/[0-9]/.test(r)) {
          if (s += r, !!!this.dialCodeToIso2Map[s])
            break;
          if (this.dialCodes.has(s)) {
            if (i = t.substring(0, n + 1), o = !0, !e)
              break;
          } else e && o && (i = t.substring(0, n + 1));
          if (s.length === this.dialCodeMaxLen)
            break;
        }
      }
    }
    return i;
  }
  //* Get the input val, adding the dial code if separateDialCode is enabled.
  _getFullNumber(t) {
    const e = t ? this._normaliseNumerals(t) : this._getTelInputValue(), { dialCode: i } = this.selectedCountryData;
    let s;
    const o = v(e);
    return this.options.separateDialCode && !e.startsWith("+") && i && o ? s = `+${i}` : s = "", s + e;
  }
  //* Remove the dial code if separateDialCode is enabled also cap the length if the input has a maxlength attribute
  _beforeSetNumber(t) {
    const e = this._getDialCode(t), i = wt(
      t,
      e,
      this.options.separateDialCode,
      this.selectedCountryData
    );
    return this._cap(i);
  }
  //* Trigger the 'countrychange' event.
  _triggerCountryChange() {
    this._trigger(T.COUNTRY_CHANGE);
  }
  //**************************
  //*  SECRET PUBLIC METHODS
  //**************************
  //* This is called when the geoip call returns.
  handleAutoCountry() {
    this.options.initialCountry === M.AUTO && u.autoCountry && (this.defaultCountry = u.autoCountry, this.selectedCountryData.iso2 || this.ui.selectedCountryInner.classList.contains(h.GLOBE) || this.setCountry(this.defaultCountry), this.resolveAutoCountryPromise());
  }
  //* This is called when the utils request completes.
  handleUtils() {
    if (u.utils) {
      const t = this._getTelInputValue();
      t && this._updateValFromNumber(t), this.selectedCountryData.iso2 && (this._updatePlaceholder(), this._updateMaxLength());
    }
    this.resolveUtilsScriptPromise();
  }
  //********************
  //*  PUBLIC METHODS
  //********************
  //* Remove plugin.
  destroy() {
    this.ui.telInput && (this.options.allowDropdown && this._closeDropdown(), this.abortController.abort(), this.abortController = null, this.ui.destroy(), u.instances instanceof Map ? u.instances.delete(this.id) : delete u.instances[this.id]);
  }
  //* Get the extension from the current number.
  getExtension() {
    return u.utils ? u.utils.getExtension(
      this._getFullNumber(),
      this.selectedCountryData.iso2
    ) : "";
  }
  //* Format the number to the given format.
  getNumber(t) {
    if (u.utils) {
      const { iso2: e } = this.selectedCountryData, i = this._getFullNumber(), s = u.utils.formatNumber(
        i,
        e,
        t
      );
      return this._mapAsciiToUserNumerals(s);
    }
    return "";
  }
  //* Get the type of the entered number e.g. landline/mobile.
  getNumberType() {
    return u.utils ? u.utils.getNumberType(
      this._getFullNumber(),
      this.selectedCountryData.iso2
    ) : $.UNKNOWN_NUMBER_TYPE;
  }
  //* Get the country data for the currently selected country.
  getSelectedCountryData() {
    return this.selectedCountryData;
  }
  //* Get the validation error.
  getValidationError() {
    if (u.utils) {
      const { iso2: t } = this.selectedCountryData;
      return u.utils.getValidationError(this._getFullNumber(), t);
    }
    return $.UNKNOWN_VALIDATION_ERROR;
  }
  //* Validate the input val using number length only
  isValidNumber() {
    const { dialCode: t, iso2: e } = this.selectedCountryData;
    if (t === k.DIAL_CODE && u.utils) {
      const i = this._getFullNumber(), s = u.utils.getCoreNumber(i, e);
      if (s[0] === k.MOBILE_PREFIX && s.length !== k.MOBILE_CORE_LENGTH)
        return !1;
    }
    return this._validateNumber(!1);
  }
  //* Validate the input val with precise validation
  isValidNumberPrecise() {
    return this._validateNumber(!0);
  }
  _utilsIsPossibleNumber(t) {
    return u.utils ? u.utils.isPossibleNumber(
      t,
      this.selectedCountryData.iso2,
      this.options.validationNumberTypes
    ) : null;
  }
  //* Shared internal validation logic to handle alpha character extension rules.
  _validateNumber(t) {
    if (!u.utils)
      return null;
    if (!this.selectedCountryData.iso2)
      return !1;
    const e = (n) => t ? this._utilsIsValidNumber(n) : this._utilsIsPossibleNumber(n), i = this._getFullNumber(), s = i.search(E.ALPHA_UNICODE);
    if (s > -1 && !this.options.allowPhonewords) {
      const n = i.substring(0, s), r = e(n), a = e(i);
      return r && a;
    }
    return e(i);
  }
  _utilsIsValidNumber(t) {
    return u.utils ? u.utils.isValidNumber(
      t,
      this.selectedCountryData.iso2,
      this.options.validationNumberTypes
    ) : null;
  }
  //* Update the selected country, and update the input val accordingly.
  setCountry(t) {
    const e = t == null ? void 0 : t.toLowerCase();
    if (!B(e))
      throw new Error(`Invalid country code: '${e}'`);
    const i = this.selectedCountryData.iso2;
    if (t && e !== i || !t && i) {
      if (this._setCountry(e), this._updateDialCode(this.selectedCountryData.dialCode), this.options.formatOnDisplay) {
        const o = this._getTelInputValue();
        this._updateValFromNumber(o);
      }
      this._triggerCountryChange();
    }
  }
  //* Set the input value and update the country.
  setNumber(t) {
    const e = this._normaliseNumerals(t), i = this._updateCountryFromNumber(e);
    this._updateValFromNumber(e), i && this._triggerCountryChange(), this._trigger(T.INPUT, { isSetNumber: !0 });
  }
  //* Set the placeholder number typ
  setPlaceholderNumberType(t) {
    this.options.placeholderNumberType = t, this._updatePlaceholder();
  }
  setDisabled(t) {
    this.ui.telInput.disabled = t, t ? this.ui.selectedCountry.setAttribute("disabled", "true") : this.ui.selectedCountry.removeAttribute("disabled");
  }
}
const Tt = (l) => {
  if (!u.utils && !u.startedLoadingUtilsScript) {
    let t;
    if (typeof l == "function")
      try {
        t = Promise.resolve(l());
      } catch (e) {
        return Promise.reject(e);
      }
    else
      return Promise.reject(
        new TypeError(
          `The argument passed to attachUtils must be a function that returns a promise for the utilities module, not ${typeof l}`
        )
      );
    return u.startedLoadingUtilsScript = !0, t.then((e) => {
      const i = e == null ? void 0 : e.default;
      if (!i || typeof i != "object")
        throw new TypeError(
          "The loader function passed to attachUtils did not resolve to a module object with utils as its default export."
        );
      return u.utils = i, S("handleUtils"), !0;
    }).catch((e) => {
      throw S("rejectUtilsScriptPromise", e), e;
    });
  }
  return null;
}, S = (l, ...t) => {
  Object.values(u.instances).forEach((e) => {
    const i = e[l];
    typeof i == "function" && i.apply(e, t);
  });
}, u = Object.assign(
  (l, t) => {
    const e = new V(l, t);
    return u.instances[e.id] = e, l.iti = e, e;
  },
  {
    defaults: Y,
    //* Using a static var like this allows us to mock it in the tests.
    documentReady: () => document.readyState === "complete",
    //* Get the country data object.
    getCountryData: () => A,
    //* A getter for the plugin instance.
    getInstance: (l) => {
      const t = l.dataset.intlTelInputId;
      return t ? u.instances[t] : null;
    },
    //* A map from instance ID to instance object.
    instances: {},
    attachUtils: Tt,
    startedLoadingUtilsScript: !1,
    startedLoadingAutoCountry: !1,
    version: "25.14.1"
  }
), Ot = {
  __name: "IntlTelInput",
  props: /* @__PURE__ */ W({
    disabled: {
      type: Boolean,
      default: !1
    },
    inputProps: {
      type: Object,
      default: () => ({})
    },
    options: {
      type: Object,
      default: () => ({})
    },
    value: {
      type: String,
      default: ""
    }
  }, {
    modelValue: {
      type: String,
      default: ""
    },
    modelModifiers: {}
  }),
  emits: /* @__PURE__ */ W([
    "changeNumber",
    "changeCountry",
    "changeValidity",
    "changeErrorCode"
  ], ["update:modelValue"]),
  setup(l, { expose: t, emit: e }) {
    const i = Q(l, "modelValue"), s = l, o = e, n = O(), r = O(), a = O(!1), d = () => r.value ? s.options.strictMode ? r.value.isValidNumberPrecise() : r.value.isValidNumber() : null, c = () => {
      let C = d();
      a.value !== C && (a.value = C, o("changeValidity", !!C), o(
        "changeErrorCode",
        C ? null : r.value.getValidationError()
      ));
    }, p = () => {
      var C;
      o("changeNumber", ((C = r.value) == null ? void 0 : C.getNumber()) ?? ""), c();
    }, b = () => {
      var C;
      o("changeCountry", ((C = r.value) == null ? void 0 : C.getSelectedCountryData().iso2) ?? ""), p(), c();
    };
    return J(() => {
      n.value && (r.value = u(n.value, s.options), s.value && r.value.setNumber(s.value), s.disabled && r.value.setDisabled(s.disabled), a.value = d());
    }), Z(
      () => s.disabled,
      (C) => {
        var y;
        return (y = r.value) == null ? void 0 : y.setDisabled(C);
      }
    ), tt(() => {
      var C;
      return (C = r.value) == null ? void 0 : C.destroy();
    }), t({ instance: r, input: n }), (C, y) => et((st(), it("input", nt({
      ref_key: "input",
      ref: n,
      "onUpdate:modelValue": y[0] || (y[0] = (I) => i.value = I),
      type: "tel",
      onCountrychange: b,
      onInput: p
    }, l.inputProps), null, 16)), [
      [
        ot,
        i.value,
        void 0,
        { lazy: !0 }
      ]
    ]);
  }
};
export {
  Ot as default
};
