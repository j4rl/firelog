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
    'SELECT COUNT(*) AS series_count, COALESCE(SUM(shot_count), 0) AS shot_count, COALESCE(SUM(x_count), 0) AS x_count,
            AVG(total_score) AS avg_score, MAX(total_score) AS best_score
     FROM ' . $seriesTable . ' s
     JOIN ' . $sessionsTable . ' ss ON ss.id = s.session_id
     WHERE ss.user_id = ?'
);
$stmt->execute([$userId]);
$summary = $stmt->fetch();

$stmt = $pdo->prepare(
    'SELECT w.manufacturer, w.model, COUNT(s.id) AS series_count, ROUND(AVG(s.total_score), 1) AS avg_score
     FROM ' . $weaponsTable . ' w
     LEFT JOIN ' . $sessionsTable . ' ss ON ss.weapon_id = w.id AND ss.user_id = w.user_id
     LEFT JOIN ' . $seriesTable . ' s ON s.session_id = ss.id
     WHERE w.user_id = ?
     GROUP BY w.id, w.manufacturer, w.model
     ORDER BY avg_score DESC, w.manufacturer'
);
$stmt->execute([$userId]);
$weaponStats = $stmt->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<div class="stack">
    <div class="actions"><a class="button secondary" href="export.php">Exportera CSV</a></div>

    <section class="grid three">
        <article class="card metric"><span class="muted">Serier</span><strong><?= (int) $summary['series_count'] ?></strong></article>
        <article class="card metric"><span class="muted">Skott</span><strong><?= (int) $summary['shot_count'] ?></strong></article>
        <article class="card metric"><span class="muted">X</span><strong><?= (int) $summary['x_count'] ?></strong></article>
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
fetch('api/get_stats.php')
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
    const applyChartTheme = () => {
      const theme = colors();
      charts.forEach((chart) => {
        chart.options.plugins.legend.labels.color = theme.text;
        Object.values(chart.options.scales).forEach((scale) => {
          scale.ticks.color = theme.text;
          scale.grid.color = theme.grid;
        });
        if (chart.config.type === 'line') {
          chart.data.datasets[0].borderColor = theme.accent;
          chart.data.datasets[0].backgroundColor = theme.accentFill;
        } else {
          chart.data.datasets[0].backgroundColor = theme.ok;
        }
        chart.update();
      });
    };
    const theme = colors();

    charts.push(new Chart(document.getElementById('trendChart'), {
      type: 'line',
      data: {
        labels: data.trend.map((item) => item.label),
        datasets: [{ label: 'Snittpoäng', data: data.trend.map((item) => item.avg_score), borderColor: theme.accent, backgroundColor: theme.accentFill, tension: .25 }]
      },
      options: { responsive: true, plugins: { legend: { labels: { color: theme.text } } }, scales: { x: { ticks: { color: theme.text }, grid: { color: theme.grid } }, y: { ticks: { color: theme.text }, grid: { color: theme.grid } } } }
    }));
    charts.push(new Chart(document.getElementById('distributionChart'), {
      type: 'bar',
      data: {
        labels: Object.keys(data.distribution),
        datasets: [{ label: 'Antal', data: Object.values(data.distribution), backgroundColor: theme.ok }]
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
