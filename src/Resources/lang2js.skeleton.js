/*!
 * Lang2js - https://github.com/developersunesis
 * Version - 0.0.1
 * Licensed under the MIT license - https://opensource.org/licenses/MIT
 */

const AVAILABLE_LOCALES = {'$AVAILABLE_LOCALES':''};
try {
    function __(string, locale = undefined) {
        if(locale === undefined) return string

        locale = '$PREFIX'.concat(locale)
        const strings = AVAILABLE_LOCALES[locale]
        return strings === undefined || strings[string] === undefined
            || strings[string] === null ? string : strings[string];
    }
} catch (e) {}