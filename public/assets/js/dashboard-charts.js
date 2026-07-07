function formatBRL(value) {
    return Number(value).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

function chartGridColor() {
    return 'rgba(255, 255, 255, 0.06)';
}

function renderDashboardCharts(evolution, distribution, comparativo, accentColor) {
    if (!accentColor) {
        var computed = getComputedStyle(document.documentElement).getPropertyValue('--accent-primary');
        accentColor = computed ? computed.trim() : '#4f7fff';
    }

    if (evolution.categories.length && document.getElementById('chart-evolution')) {
        new ApexCharts(document.querySelector('#chart-evolution'), {
            chart: { type: 'area', height: 280, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
            series: [{ name: 'Faturamento', data: evolution.values }],
            xaxis: {
                categories: evolution.categories,
                axisBorder: { show: false },
                axisTicks: { show: false },
                labels: { style: { colors: '#8b93a7' } },
            },
            grid: { borderColor: chartGridColor(), strokeDashArray: 3 },
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 2.5 },
            colors: [accentColor],
            fill: {
                type: 'gradient',
                gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0, stops: [0, 90, 100] },
            },
            yaxis: { labels: { style: { colors: '#8b93a7' }, formatter: function (v) { return formatBRL(v); } } },
            tooltip: { theme: 'dark', fillSeriesColor: false, y: { formatter: function (v) { return formatBRL(v); } } },
        }).render();
    }

    if (distribution.labels.length && document.getElementById('chart-distribution')) {
        var distributionTotal = distribution.values.reduce(function (a, b) { return a + b; }, 0);

        new ApexCharts(document.querySelector('#chart-distribution'), {
            chart: { type: 'donut', height: 280, fontFamily: 'Inter, sans-serif' },
            series: distribution.values,
            labels: distribution.labels,
            colors: distribution.colors,
            legend: { position: 'bottom', labels: { colors: '#e5e7eb' }, markers: { radius: 4 } },
            stroke: { colors: ['#10141f'], width: 2 },
            dataLabels: {
                style: { fontSize: '13px', fontWeight: 600 },
                dropShadow: { enabled: true, top: 1, left: 1, blur: 2, color: '#000', opacity: 0.6 },
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '72%',
                        labels: {
                            show: true,
                            name: { color: '#8b93a7', fontSize: '0.8rem', offsetY: 4 },
                            value: {
                                color: '#e5e7eb',
                                fontSize: '1.3rem',
                                fontWeight: 700,
                                offsetY: -4,
                                formatter: function (v) { return formatBRL(v); },
                            },
                            total: {
                                show: true,
                                label: 'Total',
                                color: '#8b93a7',
                                fontSize: '0.8rem',
                                formatter: function () { return formatBRL(distributionTotal); },
                            },
                        },
                    },
                },
            },
            tooltip: { theme: 'dark', fillSeriesColor: false, y: { formatter: function (v) { return formatBRL(v); } } },
        }).render();
    }

    if (comparativo.series.length && document.getElementById('chart-comparativo')) {
        new ApexCharts(document.querySelector('#chart-comparativo'), {
            chart: { type: 'bar', height: 300, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
            series: comparativo.series,
            xaxis: {
                categories: comparativo.categories,
                axisBorder: { show: false },
                axisTicks: { show: false },
                labels: { style: { colors: '#8b93a7' } },
            },
            grid: { borderColor: chartGridColor(), strokeDashArray: 3 },
            colors: comparativo.colors,
            dataLabels: { enabled: false },
            plotOptions: { bar: { columnWidth: '60%', borderRadius: 4 } },
            legend: { labels: { colors: '#e5e7eb' } },
            yaxis: { labels: { style: { colors: '#8b93a7' }, formatter: function (v) { return formatBRL(v); } } },
            tooltip: { theme: 'dark', fillSeriesColor: false, y: { formatter: function (v) { return formatBRL(v); } } },
        }).render();
    }
}
