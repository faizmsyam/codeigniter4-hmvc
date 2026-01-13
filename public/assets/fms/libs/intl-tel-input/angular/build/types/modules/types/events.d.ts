import { EVENTS } from "../constants";
export type ItiEventMap = {
    [EVENTS.COUNTRY_CHANGE]: Record<string, never>;
    [EVENTS.OPEN_COUNTRY_DROPDOWN]: Record<string, never>;
    [EVENTS.CLOSE_COUNTRY_DROPDOWN]: Record<string, never>;
    [EVENTS.INPUT]: {
        isSetNumber?: boolean;
    };
};
