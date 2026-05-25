(function () {
    'use strict';

    var unsupportedPath = '/__not-implemented';
    var messagePrefix = 'この機能は現在BeMartでは未対応です。';

    function closestUnsupportedTarget(target) {
        if (!target || typeof target.closest !== 'function') {
            return null;
        }

        return target.closest('a[href], button[data-bemart-unsupported-route], input[data-bemart-unsupported-route]');
    }

    function unsupportedRouteFromUrl(value) {
        if (!value) {
            return null;
        }

        try {
            var url = new URL(value, window.location.href);
            if (url.pathname !== unsupportedPath) {
                return null;
            }

            return url.searchParams.get('route') || 'unknown';
        } catch (e) {
            return value.indexOf(unsupportedPath) === 0 ? 'unknown' : null;
        }
    }

    function unsupportedRouteFromElement(el) {
        if (!el) {
            return null;
        }

        if (el.dataset && el.dataset.bemartUnsupportedRoute) {
            return el.dataset.bemartUnsupportedRoute;
        }

        if (el.tagName === 'A') {
            return unsupportedRouteFromUrl(el.getAttribute('href'));
        }

        return null;
    }

    function alertUnsupported(route, label) {
        var lines = [messagePrefix];
        if (label) {
            lines.push(label);
        }
        if (route) {
            lines.push('EC-CUBE route: ' + route);
        }

        window.alert(lines.join('\n'));
    }

    document.addEventListener('click', function (event) {
        var el = closestUnsupportedTarget(event.target);
        var route = unsupportedRouteFromElement(el);
        if (!route) {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();
        alertUnsupported(route, el.dataset ? el.dataset.bemartUnsupportedLabel : '');
    }, true);

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form || form.tagName !== 'FORM') {
            return;
        }

        var route = form.dataset && form.dataset.bemartUnsupportedRoute
            ? form.dataset.bemartUnsupportedRoute
            : unsupportedRouteFromUrl(form.getAttribute('action'));
        if (!route) {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();
        alertUnsupported(route, form.dataset ? form.dataset.bemartUnsupportedLabel : '');
    }, true);
}());
