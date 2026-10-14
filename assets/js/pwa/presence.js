/**
 * "Online now": while the app is on screen the phone joins a Supabase
 * Realtime Presence channel (see includes/presence.php). A single WebSocket,
 * no polling: the connection itself is the signal. It closes when the app
 * goes to the background or the page is left.
 *
 * Talks the Realtime (Phoenix) protocol directly so no SDK is needed.
 * The channel only carries an opaque per-user token and the device type.
 *
 * Other scripts can listen for the live list:
 *   window.addEventListener('presence:change', e => e.detail.online) // Set of tokens
 */
(function () {
  'use strict';

  var el = document.getElementById('presenceConfig');
  if (!el || !('WebSocket' in window)) return;
  var cfg;
  try { cfg = JSON.parse(el.textContent); } catch (err) { return; }

  var TOPIC = 'realtime:' + cfg.channel;
  var HEARTBEAT_MS = 25000;
  var device = /iPhone|iPad|iPod/i.test(navigator.userAgent) ? 'iPhone'
    : /Android/i.test(navigator.userAgent) ? 'Android'
    : /Macintosh/i.test(navigator.userAgent) ? 'Mac'
    : /Windows/i.test(navigator.userAgent) ? 'Windows' : 'Other';

  var socket = null;
  var heartbeat = null;
  var retry = null;
  var retryDelay = 2000;
  var ref = 0;
  var joinRef = null;
  var state = {}; // token -> { phx_ref: true } for each open connection (device/tab)

  function send(msg) {
    if (socket && socket.readyState === WebSocket.OPEN) socket.send(JSON.stringify(msg));
  }

  function publish() {
    var online = new Set(Object.keys(state).filter(function (k) { return Object.keys(state[k]).length > 0; }));
    window.dispatchEvent(new CustomEvent('presence:change', { detail: { online: online } }));
  }

  // Tracks every connection by its phx_ref, so a repeated join or leave
  // never leaves someone marked online by mistake.
  function apply(entries, add) {
    Object.keys(entries || {}).forEach(function (key) {
      var refs = state[key] || (state[key] = {});
      ((entries[key] && entries[key].metas) || []).forEach(function (meta) {
        if (add) refs[meta.phx_ref] = true; else delete refs[meta.phx_ref];
      });
    });
  }

  function connect() {
    if (socket || document.visibilityState !== 'visible' || !navigator.onLine) return;
    clearTimeout(retry);

    var url = cfg.url.replace(/^http/, 'ws') + '/realtime/v1/websocket?apikey='
      + encodeURIComponent(cfg.key) + '&vsn=1.0.0';
    socket = new WebSocket(url);

    socket.onopen = function () {
      retryDelay = 2000;
      joinRef = String(++ref);
      send({
        topic: TOPIC, event: 'phx_join', ref: joinRef, join_ref: joinRef,
        payload: {
          config: {
            broadcast: { ack: false, self: false },
            presence: { key: cfg.token },
            postgres_changes: [],
            private: false
          },
          access_token: cfg.key
        }
      });
      heartbeat = setInterval(function () {
        send({ topic: 'phoenix', event: 'heartbeat', payload: {}, ref: String(++ref) });
      }, HEARTBEAT_MS);
    };

    socket.onmessage = function (e) {
      var msg;
      try { msg = JSON.parse(e.data); } catch (err) { return; }
      if (msg.topic !== TOPIC) return;

      if (msg.event === 'phx_reply' && msg.ref === joinRef) {
        if (msg.payload && msg.payload.status === 'ok') {
          send({
            topic: TOPIC, event: 'presence', ref: String(++ref), join_ref: joinRef,
            payload: { type: 'presence', event: 'track', payload: { d: device } }
          });
        }
      } else if (msg.event === 'presence_state') {
        state = {};
        apply(msg.payload, true);
        publish();
      } else if (msg.event === 'presence_diff') {
        apply(msg.payload && msg.payload.joins, true);
        apply(msg.payload && msg.payload.leaves, false);
        publish();
      }
    };

    socket.onclose = function () {
      clearInterval(heartbeat);
      socket = null;
      // Reconnect only while the app is on screen, waiting longer each time.
      if (document.visibilityState === 'visible') {
        retry = setTimeout(connect, retryDelay);
        retryDelay = Math.min(retryDelay * 2, 60000);
      }
    };
  }

  function disconnect() {
    clearTimeout(retry);
    if (!socket) return;
    send({ topic: TOPIC, event: 'phx_leave', payload: {}, ref: String(++ref), join_ref: joinRef });
    socket.onclose = null;
    socket.close();
    socket = null;
    clearInterval(heartbeat);
  }

  document.addEventListener('visibilitychange', function () {
    if (document.visibilityState === 'visible') connect(); else disconnect();
  });
  window.addEventListener('pagehide', disconnect);
  window.addEventListener('online', connect);
  window.addEventListener('offline', disconnect);

  connect();
})();
