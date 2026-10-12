// Closing Report: "Ask the bot for new data". Sends the request, waits for
// the bot (Orbita) to finish and reloads the report with the fresh numbers.
(function () {
  'use strict';

  var button = document.querySelector('[data-bot-refresh]');
  if (!button) return;
  var statusEl = document.querySelector('[data-bot-status]');
  var endpoint = 'api/closing-report-refresh.php';
  var POLL_MS = 3000;
  var TIMEOUT_MS = 3 * 60 * 1000;

  function setStatus(text, tone) {
    statusEl.textContent = text;
    statusEl.className = 'bot-status' + (tone ? ' bot-status--' + tone : '');
    statusEl.hidden = !text;
  }

  function setBusy(busy) {
    button.disabled = busy;
    button.toggleAttribute('data-loading', busy);
  }

  function poll(startedAt, sawRunning) {
    if (Date.now() - startedAt > TIMEOUT_MS) {
      setBusy(false);
      setStatus('The bot has not finished yet. The report will show the new data once it does.', 'warn');
      return;
    }
    fetch(endpoint, { credentials: 'same-origin', cache: 'no-store' })
      .then(function (r) { return r.json(); })
      .then(function (state) {
        if (!state.ok) throw new Error(state.error);
        if (state.run) {
          setStatus('The bot is fetching the calls…', 'busy');
          return setTimeout(function () { poll(startedAt, true); }, POLL_MS);
        }
        if (state.last_error) {
          setBusy(false);
          setStatus('The bot reported an error: ' + state.last_error, 'error');
          return;
        }
        // run is back to false: the bot finished (or picked it up very fast)
        if (sawRunning || Date.now() - startedAt > POLL_MS) {
          setStatus('Done. Updating the report…', 'ok');
          return location.reload();
        }
        setTimeout(function () { poll(startedAt, sawRunning); }, POLL_MS);
      })
      .catch(function (err) {
        setBusy(false);
        setStatus(err.message || 'Could not check the bot status.', 'error');
      });
  }

  button.addEventListener('click', function () {
    setBusy(true);
    setStatus('Sending the request to the bot…', 'busy');
    fetch(endpoint, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': button.getAttribute('data-csrf') },
      body: JSON.stringify({ from: button.getAttribute('data-from'), to: button.getAttribute('data-to') })
    })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (!res.ok) throw new Error(res.error);
        setStatus('Request sent. Waiting for the bot…', 'busy');
        poll(Date.now(), false);
      })
      .catch(function (err) {
        setBusy(false);
        setStatus(err.message || 'The request could not be sent.', 'error');
      });
  });
})();
