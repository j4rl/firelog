(function () {
  const shoot = document.querySelector('[data-shoot]');
  const themeMeta = document.querySelector('meta[name="theme-color"]');

  function cssVar(name) {
    return getComputedStyle(document.body).getPropertyValue(name).trim();
  }

  function updateThemeColor() {
    if (themeMeta) {
      themeMeta.setAttribute('content', cssVar('--theme-color') || '#151515');
    }
  }

  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register('service-worker.js').catch(() => {});
    });
  }

  updateThemeColor();

  if (themeMeta) {
    new MutationObserver(updateThemeColor).observe(document.body, {
      attributes: true,
      attributeFilter: ['data-theme'],
    });
  }

  if (!shoot) {
    return;
  }

  const sessionId = Number(shoot.dataset.sessionId);
  const shotsEl = document.querySelector('[data-current-shots]');
  const totalEl = document.querySelector('[data-total]');
  const xEl = document.querySelector('[data-x-count]');
  const missEl = document.querySelector('[data-miss-count]');
  const numberEl = document.querySelector('[data-series-number]');
  const messageEl = document.querySelector('[data-shoot-message]');
  const saveButton = document.querySelector('[data-save-series]');
  const undoButton = document.querySelector('[data-undo-shot]');
  let currentShots = [];

  function score() {
    return currentShots.reduce((sum, shot) => {
      if (shot === 'X') return sum + 10;
      if (shot === '-') return sum;
      return sum + Number(shot);
    }, 0);
  }

  function xCount() {
    return currentShots.filter((shot) => shot === 'X').length;
  }

  function missCount() {
    return currentShots.filter((shot) => shot === '-' || shot === '0').length;
  }

  function render() {
    shotsEl.innerHTML = '';
    currentShots.forEach((shot) => {
      const pill = document.createElement('span');
      pill.className = 'shot-pill';
      pill.textContent = shot;
      shotsEl.appendChild(pill);
    });
    if (currentShots.length === 0) {
      const empty = document.createElement('span');
      empty.className = 'muted';
      empty.textContent = 'Inga skott ännu';
      shotsEl.appendChild(empty);
    }
    totalEl.textContent = String(score());
    xEl.textContent = String(xCount());
    missEl.textContent = String(missCount());
  }

  function setMessage(text, type) {
    messageEl.textContent = text;
    messageEl.className = type ? `message ${type}` : 'message';
    messageEl.hidden = !text;
  }

  async function saveSeries() {
    if (currentShots.length === 0) {
      setMessage('Lägg in minst ett skott innan du sparar.', 'error');
      return;
    }

    saveButton.disabled = true;
    setMessage('Sparar serie...', '');

    try {
      const response = await fetch('api/save_series.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ session_id: sessionId, shots: currentShots }),
      });
      const data = await response.json();

      if (!response.ok || !data.success) {
        throw new Error(data.message || 'Kunde inte spara serien.');
      }

      currentShots = [];
      numberEl.textContent = String(Number(data.series_number) + 1);
      render();
      const medals = [data.medal, data.session_medal]
        .filter((medal) => medal && medal.label)
        .map((medal) => medal.label);
      const medalText = medals.length > 0 ? ` ${medals.join('. ')}.` : '';
      setMessage(`Serie ${data.series_number} sparad: ${data.total_score} poäng, ${data.x_count} X, ${data.miss_count} missar.${medalText}`, 'ok');
    } catch (error) {
      setMessage(error.message, 'error');
    } finally {
      saveButton.disabled = false;
    }
  }

  shoot.addEventListener('click', (event) => {
    const button = event.target.closest('[data-shot]');
    if (!button) {
      return;
    }
    currentShots.push(button.dataset.shot);
    setMessage('', '');
    render();
  });

  undoButton.addEventListener('click', () => {
    currentShots.pop();
    render();
  });

  saveButton.addEventListener('click', saveSeries);

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Backspace') {
      event.preventDefault();
      currentShots.pop();
      render();
    }
    if (event.key === 'Enter') {
      event.preventDefault();
      saveSeries();
    }
  });

  render();
})();
