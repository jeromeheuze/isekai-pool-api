/**
 * isekai-pool.com — shared Laravel API helpers (browser only).
 * @see _docs/CURSOR.md — never fetch raw node IPs from the frontend.
 */
(function (global) {
    'use strict';

    var API_BASE = 'https://api.isekai-pool.com/api/v1';

    /** rpcPort + minerAlgo: miner documentation only — never used in fetch(). Use YOUR_RPC_HOST in commands. */
    var COINS = {
        YTN: { slug: 'yenten', name: 'Yenten', symbol: 'YTN', algo: 'YespowerR16', rpcPort: 9982, minerAlgo: 'yespowerr16', page: '/ytn.html' },
        KOTO: { slug: 'koto', name: 'Koto', symbol: 'KOTO', algo: 'Yescrypt', rpcPort: 8432, minerAlgo: 'yescrypt', page: '/koto.html' },
        TDC: { slug: 'tidecoin', name: 'Tidecoin', symbol: 'TDC', algo: 'YespowerTIDE', rpcPort: 9368, minerAlgo: 'yespowertide', page: '/tdc.html' },
    };

    function getCoinStatus(slug) {
        return fetch(API_BASE + '/' + encodeURIComponent(slug) + '/status')
            .then(function (res) {
                return res.json().then(function (data) {
                    return { ok: res.ok, status: res.status, data: data };
                });
            })
            .catch(function () {
                return { ok: false, status: 0, data: null };
            });
    }

    function getHealth() {
        return fetch(API_BASE + '/health')
            .then(function (res) {
                return res.json().then(function (data) {
                    return { ok: res.ok, data: data };
                });
            })
            .catch(function () {
                return { ok: false, data: null };
            });
    }

    global.IsekaiAPI = {
        API_BASE: API_BASE,
        COINS: COINS,
        getCoinStatus: getCoinStatus,
        getHealth: getHealth,
    };
})(typeof window !== 'undefined' ? window : this);
