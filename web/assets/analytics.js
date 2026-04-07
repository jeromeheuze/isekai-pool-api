(function () {
    var endpoint = 'https://api.isekai-pool.com/a.gif';
    var path = window.location.pathname || '/';
    var host = window.location.hostname || 'unknown';
    var qs = '?p=' + encodeURIComponent(path) + '&h=' + encodeURIComponent(host);
    var img = new Image(1, 1);
    img.referrerPolicy = 'no-referrer';
    img.src = endpoint + qs;
})();
