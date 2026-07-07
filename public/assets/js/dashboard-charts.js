function formatBRL(value) {
    return Number(value).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

function renderDashboardCharts(evolution, distribution, comparativo) {
    if (evolution.categories.length && document.getElementById('chart-evolution')) {
        new ApexCharts(document.querySelector('#chart-evolution'), {
            chart: { type: 'area', height: 280, toolbar: { show: false } },
            series: [{ name: 'Faturamento', data: evolution.values }],
            xaxis: { categories: evolution.categories },
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 2 },
            colors: ['#3b82f6'],
            yaxis: { labels: { formatter: function (v) { return formatBRL(v); } } },
            tooltip: { y: { formatter: function (v) { return formatBRL(v); } } },
        }).render();
    }

    if (distribution.labels.length && document.getElementById('chart-distribution')) {
        new ApexCharts(document.querySelector('#chart-distribution'), {
            chart: { type: 'donut', height: 280 },
            series: distribution.values,
            labels: distribution.labels,
            colors: distribution.colors,
            legend: { position: 'bottom' },
            tooltip: { y: { formatter: function (v) { return formatBRL(v); } } },
        }).render();
    }

    if (comparativo.series.length && document.getElementById('chart-comparativo')) {
        new ApexCharts(document.querySelector('#chart-comparativo'), {
            chart: { type: 'bar', height: 300, toolbar: { show: false } },
            series: comparativo.series,
            xaxis: { categories: comparativo.categories },
            colors: comparativo.colors,
            dataLabels: { enabled: false },
            plotOptions: { bar: { columnWidth: '60%' } },
            yaxis: { labels: { formatter: function (v) { return formatBRL(v); } } },
            tooltip: { y: { formatter: function (v) { return formatBRL(v); } } },
        }).render();
    }
}
