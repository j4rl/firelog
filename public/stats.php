<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_shooter();

$userId = current_user_id();
$page_title = 'Statistik';
$seriesTable = db_table('series');
$sessionsTable = db_table('shooting_sessions');
$weaponsTable = db_table('weapons');

$stmt = $pdo->prepare(
    'SELECT ss.discipline, COUNT(s.id) AS series_count
     FROM ' . $sessionsTable . ' ss
     JOIN ' . $seriesTable . ' s ON s.session_id = ss.id
     WHERE ss.user_id = ? AND ss.discipline <> \'\'
     GROUP BY ss.discipline
     ORDER BY series_count DESC, ss.discipline'
);
$stmt->execute([$userId]);
$disciplineStats = $stmt->fetchAll();
$disciplineValues = array_column($disciplineStats, 'discipline');
$requestedDiscipline = trim((string) ($_GET['discipline'] ?? ''));
$selectedDiscipline = in_array($requestedDiscipline, $disciplineValues, true)
    ? $requestedDiscipline
    : (string) ($disciplineValues[0] ?? '');

$where = ['ss.user_id = ?'];
$summaryParams = [$userId];
if ($selectedDiscipline !== '') {
    $where[] = 'ss.discipline = ?';
    $summaryParams[] = $selectedDiscipline;
}

$stmt = $pdo->prepare(
    'SELECT COUNT(*) AS series_count, COALESCE(SUM(shot_count), 0) AS shot_count, COALESCE(SUM(x_count), 0) AS x_count,
            COALESCE(SUM(miss_count), 0) AS miss_count,
            AVG(total_score) AS avg_score, MAX(total_score) AS best_score
     FROM ' . $seriesTable . ' s
     JOIN ' . $sessionsTable . ' ss ON ss.id = s.session_id
     WHERE ' . implode(' AND ', $where)
);
$stmt->execute($summaryParams);
$summary = $stmt->fetch();

$sessionJoinDiscipline = '';
$weaponParams = [];
if ($selectedDiscipline !== '') {
    $sessionJoinDiscipline = ' AND ss.discipline = ?';
    $weaponParams[] = $selectedDiscipline;
}
$weaponParams[] = $userId;

$stmt = $pdo->prepare(
    'SELECT w.manufacturer, w.model, COUNT(s.id) AS series_count, ROUND(AVG(s.total_score), 1) AS avg_score
     FROM ' . $weaponsTable . ' w
     LEFT JOIN ' . $sessionsTable . ' ss ON ss.weapon_id = w.id AND ss.user_id = w.user_id' . $sessionJoinDiscipline . '
     LEFT JOIN ' . $seriesTable . ' s ON s.session_id = ss.id
     WHERE w.user_id = ?
     GROUP BY w.id, w.manufacturer, w.model
     ORDER BY avg_score DESC, w.manufacturer'
);
$stmt->execute($weaponParams);
$weaponStats = $stmt->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<div class="stack">
    <div class="actions"><a class="button secondary" href="export.php">Exportera CSV</a></div>

    <section class="card">
        <?php if ($disciplineStats): ?>
            <form class="form-grid two" method="get">
                <label>Disciplin
                    <select name="discipline" onchange="this.form.submit()">
                        <?php foreach ($disciplineStats as $discipline): ?>
                            <option value="<?= e($discipline['discipline']) ?>" <?= $selectedDiscipline === (string) $discipline['discipline'] ? 'selected' : '' ?>>
                                <?= e($discipline['discipline']) ?> (<?= (int) $discipline['series_count'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button type="submit">Visa</button>
            </form>
        <?php else: ?>
            <p class="muted">Ingen statistik ännu.</p>
        <?php endif; ?>
    </section>

    <section class="grid three">
        <article class="card metric"><span class="muted">Serier</span><strong><?= (int) $summary['series_count'] ?></strong></article>
        <article class="card metric"><span class="muted">Skott</span><strong><?= (int) $summary['shot_count'] ?></strong></article>
        <article class="card metric"><span class="muted">X</span><strong><?= (int) $summary['x_count'] ?></strong></article>
        <article class="card metric"><span class="muted">Missar</span><strong><?= (int) $summary['miss_count'] ?></strong></article>
        <article class="card metric"><span class="muted">Snitt/serie</span><strong><?= $summary['avg_score'] !== null ? e(number_format((float) $summary['avg_score'], 1, ',', '')) : '-' ?></strong></article>
        <article class="card metric"><span class="muted">Bästa serie</span><strong><?= $summary['best_score'] !== null ? (int) $summary['best_score'] : '-' ?></strong></article>
    </section>

    <section class="card stack">
        <h2>Snitt per vapen</h2>
        <div class="list">
            <?php foreach ($weaponStats as $weapon): ?>
                <div class="list-item">
                    <strong><?= e($weapon['manufacturer']) ?> <?= e($weapon['model']) ?></strong>
                    <span class="meta"><?= (int) $weapon['series_count'] ?> serier · snitt <?= $weapon['avg_score'] !== null ? e($weapon['avg_score']) : '-' ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="grid two">
        <article class="card stack">
            <h2>Utveckling</h2>
            <canvas id="trendChart" aria-label="Utveckling över tid"></canvas>
        </article>
        <article class="card stack">
            <h2>Poängfördelning</h2>
            <canvas id="distributionChart" aria-label="Poängfördelning"></canvas>
        </article>
    </section>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
fetch('api/get_stats.php?discipline=<?= rawurlencode($selectedDiscipline) ?>')
  .then((response) => response.json())
  .then((data) => {
    if (!data.success || !window.Chart) return;

    const charts = [];
    const cssVar = (name) => getComputedStyle(document.body).getPropertyValue(name).trim();
    const colors = () => ({
      text: cssVar('--text') || '#191b22',
      grid: cssVar('--line') || '#d7dde6',
      accent: cssVar('--accent') || '#d99a16',
      accentFill: document.body.dataset.theme === 'dark' ? 'rgba(242,184,75,.18)' : 'rgba(217,154,22,.18)',
      ok: cssVar('--ok') || '#178a55',
    });
    const palette = [
      '#d99a16',
      '#178a55',
      '#2f80ed',
      '#d14f4f',
      '#7b61ff',
      '#00a7a7',
      '#b77835',
      '#8a5a9e',
      '#557a21',
      '#c24b8f',
    ];
    const colorFor = (index, alpha = 1) => {
      const hex = palette[index % palette.length];
      const r = parseInt(hex.slice(1, 3), 16);
      const g = parseInt(hex.slice(3, 5), 16);
      const b = parseInt(hex.slice(5, 7), 16);
      return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    };
    const applyDatasetColors = (chart) => {
      chart.data.datasets.forEach((dataset, index) => {
        if (chart.config.type === 'line') {
          dataset.borderColor = colorFor(index);
          dataset.backgroundColor = colorFor(index, .14);
          dataset.pointBackgroundColor = colorFor(index);
          dataset.pointBorderColor = colorFor(index);
        } else {
          dataset.backgroundColor = colorFor(index, .82);
          dataset.borderColor = colorFor(index);
          dataset.borderWidth = 1;
        }
      });
    };
    const applyChartTheme = () => {
      const theme = colors();
      charts.forEach((chart) => {
        chart.options.plugins.legend.labels.color = theme.text;
        Object.values(chart.options.scales).forEach((scale) => {
          scale.ticks.color = theme.text;
          scale.grid.color = theme.grid;
        });
        applyDatasetColors(chart);
        chart.update();
      });
    };
    const theme = colors();
    const trendDatasets = data.trend.datasets.map((dataset, index) => ({
      ...dataset,
      borderColor: colorFor(index),
      backgroundColor: colorFor(index, .14),
      pointBackgroundColor: colorFor(index),
      pointBorderColor: colorFor(index),
      tension: .25,
      spanGaps: true,
    }));
    const distributionDatasets = data.distribution.datasets.map((dataset, index) => ({
      ...dataset,
      backgroundColor: colorFor(index, .82),
      borderColor: colorFor(index),
      borderWidth: 1,
    }));

    charts.push(new Chart(document.getElementById('trendChart'), {
      type: 'line',
      data: {
        labels: data.trend.labels,
        datasets: trendDatasets
      },
      options: { responsive: true, interaction: { mode: 'index', intersect: false }, plugins: { legend: { labels: { color: theme.text } } }, scales: { x: { ticks: { color: theme.text }, grid: { color: theme.grid } }, y: { ticks: { color: theme.text }, grid: { color: theme.grid } } } }
    }));
    charts.push(new Chart(document.getElementById('distributionChart'), {
      type: 'bar',
      data: {
        labels: data.distribution.labels,
        datasets: distributionDatasets
      },
      options: { responsive: true, plugins: { legend: { labels: { color: theme.text } } }, scales: { x: { ticks: { color: theme.text }, grid: { color: theme.grid } }, y: { ticks: { color: theme.text }, grid: { color: theme.grid } } } }
    }));

    new MutationObserver(applyChartTheme).observe(document.body, {
      attributes: true,
      attributeFilter: ['data-theme'],
    });
  });
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
