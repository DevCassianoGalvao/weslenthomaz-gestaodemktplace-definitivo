function formatBRL(value) {
    return Number(value).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

function chartGridColor() {
    return 'rgba(255, 255, 255, 0.06)';
}

function hexToHsl(hex) {
    hex = hex.replace('#', '');
    if (hex.length === 3) {
        hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
    }
    var r = parseInt(hex.substr(0, 2), 16) / 255;
    var g = parseInt(hex.substr(2, 2), 16) / 255;
    var b = parseInt(hex.substr(4, 2), 16) / 255;
    var max = Math.max(r, g, b), min = Math.min(r, g, b);
    var h, s, l = (max + min) / 2;

    if (max === min) {
        h = s = 0;
    } else {
        var d = max - min;
        s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
        switch (max) {
            case r: h = (g - b) / d + (g < b ? 6 : 0); break;
            case g: h = (b - r) / d + 2; break;
            default: h = (r - g) / d + 4;
        }
        h /= 6;
    }
    return [h * 360, s * 100, l * 100];
}

function hslToHex(h, s, l) {
    h /= 360; s /= 100; l /= 100;
    var r, g, b;

    if (s === 0) {
        r = g = b = l;
    } else {
        var hue2rgb = function (p, q, t) {
            if (t < 0) t += 1;
            if (t > 1) t -= 1;
            if (t < 1 / 6) return p + (q - p) * 6 * t;
            if (t < 1 / 2) return q;
            if (t < 2 / 3) return p + (q - p) * (2 / 3 - t) * 6;
            return p;
        };
        var q = l < 0.5 ? l * (1 + s) : l + s - l * s;
        var p = 2 * l - q;
        r = hue2rgb(p, q, h + 1 / 3);
        g = hue2rgb(p, q, h);
        b = hue2rgb(p, q, h - 1 / 3);
    }

    var toHex = function (x) {
        var v = Math.round(x * 255).toString(16);
        return v.length === 1 ? '0' + v : v;
    };
    return '#' + toHex(r) + toHex(g) + toHex(b);
}

/**
 * Normaliza uma cor de marca (marketplace/cliente) pra um tom de saturação e
 * luminosidade consistentes — evita que cores de marca muito vivas/neon
 * (ex: amarelo puro) briguem entre si e com o tema dark navy/blue quando
 * usadas lado a lado num gráfico (donut, barras). Mantém o matiz original,
 * então a cor continua reconhecível, só entra "no tom" do resto do produto.
 */
function harmonizeChartColor(hex, index) {
    if (!hex || hex[0] !== '#') {
        return '#d6b25e';
    }
    var hsl = hexToHsl(hex);
    var s = Math.min(Math.max(hsl[1], 55), 68);
    var l = Math.min(Math.max(hsl[2], 50), 60);
    return hslToHex(hsl[0], s, l);
}

function renderDashboardCharts(evolution, distribution, comparativo, accentColor) {
    if (!accentColor) {
        var computed = getComputedStyle(document.documentElement).getPropertyValue('--accent-primary');
        accentColor = computed ? computed.trim() : '#d6b25e';
    }

    if (evolution.categories.length && document.getElementById('chart-evolution')) {
        new ApexCharts(document.querySelector('#chart-evolution'), {
            chart: { type: 'area', height: 320, parentHeightOffset: 0, redrawOnParentResize: true, toolbar: { show: false }, fontFamily: 'Manrope, sans-serif' },
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
            chart: { type: 'donut', height: 320, parentHeightOffset: 0, redrawOnParentResize: true, fontFamily: 'Manrope, sans-serif' },
            series: distribution.values,
            labels: distribution.labels,
            colors: distribution.colors.map(harmonizeChartColor),
            legend: { position: 'bottom', horizontalAlign: 'center', fontSize: '12px', offsetY: 2, labels: { colors: '#f6f3eb' }, markers: { width: 8, height: 8, radius: 2 } },
            stroke: { colors: ['#0d0d0d'], width: 2 },
            dataLabels: {
                enabled: false,
                style: { fontSize: '12px', fontWeight: 600 },
                dropShadow: { enabled: true, top: 1, left: 1, blur: 2, color: '#000', opacity: 0.6 },
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '68%',
                        labels: {
                            show: true,
                            name: { show: false },
                            value: {
                                show: false,
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
            chart: { type: 'bar', height: 320, parentHeightOffset: 0, redrawOnParentResize: true, toolbar: { show: false }, fontFamily: 'Manrope, sans-serif' },
            series: comparativo.series,
            xaxis: {
                categories: comparativo.categories,
                axisBorder: { show: false },
                axisTicks: { show: false },
                labels: { style: { colors: '#8b93a7' } },
            },
            grid: { borderColor: chartGridColor(), strokeDashArray: 3 },
            colors: comparativo.colors.map(harmonizeChartColor),
            dataLabels: { enabled: false },
            plotOptions: { bar: { columnWidth: '52%', borderRadius: 3 } },
            legend: { position: 'bottom', horizontalAlign: 'center', fontSize: '12px', labels: { colors: '#f6f3eb' }, markers: { width: 8, height: 8, radius: 2 } },
            yaxis: { labels: { style: { colors: '#8b93a7' }, formatter: function (v) { return formatBRL(v); } } },
            tooltip: { theme: 'dark', fillSeriesColor: false, y: { formatter: function (v) { return formatBRL(v); } } },
        }).render();
    }
}
