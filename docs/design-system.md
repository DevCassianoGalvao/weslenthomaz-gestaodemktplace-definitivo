# Design System — Gestão de Marketplaces

Direção visual: **dark navy/black + blue**. Este guia documenta os tokens e
componentes reutilizáveis para que novas telas sigam o mesmo padrão.

Arquivos-fonte:
- `/public/assets/css/design-tokens.css` — variáveis (cores, tipografia, raio, sombra, transições).
- `/public/assets/css/app.css` — componentes que consomem os tokens.
- `/public/assets/js/animations.js` — animações GSAP (auto-inicializa fade de página + indicador da sidebar).
- `/public/assets/js/dashboard-charts.js` — tema dark padrão para gráficos ApexCharts.
- `/app/Core/Icon.php` — set único de ícones SVG inline (`Icon::svg('nome')`).

## Cores

| Token | Valor | Uso |
|---|---|---|
| `--bg-base` | `#05070d` | fundo geral da aplicação (`body`) |
| `--bg-surface` | `#10141f` | cards, sidebar, tabelas |
| `--bg-surface-hover` | `#161b2b` | hover de linhas/itens de nav |
| `--bg-inset` | `#0a0d15` | inputs, chips |
| `--border-subtle` | `rgba(255,255,255,.06)` | borda padrão de qualquer superfície |
| `--border-strong` | `rgba(255,255,255,.12)` | borda em hover/foco |
| `--accent-primary` | `#4f7fff` (default) | **ver regra de contexto abaixo** |
| `--accent-gradient` | `linear-gradient(135deg, accent-primary, #6d5bff)` | botões primários, marca da sidebar |
| `--success` / `--danger` / `--warning` | `#10b981` / `#ef4444` / `#f59e0b` | variação positiva/negativa, badges |
| `--text-primary` / `--text-muted` / `--text-faint` | `#e5e7eb` / `#8b93a7` / `#5b6478` | hierarquia de texto |

### Regra de contexto do `--accent-primary`

- **Painel interno (admin/operador)**: sempre o azul fixo (`--accent-primary-default`). Nunca sobrescrever.
- **Dashboard do cliente final** (`dashboard/client.php`, nos dois modos — visão própria e drill-down do admin): sobrescrito com o `brand_color` do cliente via `app/Views/partials/brand-style.php`, que injeta `<style>:root{--color-primary:...}</style>` **apenas quando o valor é um hex válido** (`#RRGGBB`). O resto da paleta (fundo, superfícies, textos) permanece igual — só o acento muda.
- Ao adicionar uma tela nova: se ela pertence ao contexto do cliente (dashboard, exportações, futuras telas de "portal do cliente"), inclua `brand-style.php` no `<head>`. Se pertence ao painel interno, não inclua.

## Tipografia

Fonte: **Inter** (Google Fonts, carregada em `design-tokens.css`), pesos 400/500/600/700.
`body` usa `font-variant-numeric: tabular-nums` — números não "dançam" durante animações de contagem.

- Título de página (`h1`): 1.5rem / 700.
- KPI (`.kpi-value`): 1.75rem / 700, `letter-spacing: -0.02em`.
- Labels/uppercase (`.kpi-label`, `th`): ~0.75rem, `text-transform: uppercase`, `letter-spacing: 0.02–0.03em`.

## Componentes

### Cards
`.card` (tela avulsa/login), `.form-card`, `.kpi-card`, `.chart-card` — todos:
raio `16px` (`--radius-card`), borda `--border-subtle`, fundo `--bg-surface`.
`.kpi-card` e `.chart-card` têm elevação no hover (`translateY(-3px)` + `--shadow-card-hover`,
transição ~200ms). Não adicionar hover a `.form-card` (não é um elemento clicável).

### Botões
- `.btn` / `.btn-link` — ação primária, fundo `--accent-gradient`, texto branco.
- `.btn-secondary` — ação secundária, transparente com borda sutil.
- Nunca usar cor de botão hardcoded; para ações destrutivas usar `style="background:var(--danger)"` sobre `.btn`.

### Badges (status)
Sempre **ponto colorido + texto**, nunca só texto ou preenchimento sólido:
```html
<span class="badge badge-active"><span class="badge-dot"></span>active</span>
```
Modificadores disponíveis: `badge-active`/`badge-paused`/`badge-archived` (status de cliente/marketplace),
`badge-create`/`badge-update`/`badge-delete` (ação do histórico). Para um novo tipo de badge,
adicione `.badge-<nome> .badge-dot { background: var(--token-semântico); }` — não invente cor nova.

### Inputs
Todo `input[type=text|email|password|date|month|number|url]` e `select` já herdam estilo global
(fundo `--bg-inset`, borda sutil, foco com anel azul). Não estilizar inline — se precisar de um
tamanho específico, use só `style="width:...px"`.

### Tabelas
`<table>` já vem com radius, borda e hover de linha. Use `<span class="badge-dot">` dentro de células
de status. Em telas ≤800px, tabelas viram scroll horizontal automaticamente (ver responsivo).

### Sidebar (layout padrão de toda tela autenticada)
```html
<div class="app-shell">
    <?php $active = 'clients'; require __DIR__ . '/../partials/sidebar.php'; ?>
    <main class="app-main">
        <div class="content"> ... </div>
    </main>
</div>
```
`$active` deve ser um de: `clients`, `marketplaces`, `dashboard`, `history` (ou omitido para o cliente final,
que só vê o item "Dashboard"). O indicador do item ativo (barra + fundo tintado) desliza suavemente
entre navegações reais via `initSidebarIndicator()` (sessionStorage lembra a posição anterior).

### Ícones
Um único set inline SVG em `App\Core\Icon`. Para adicionar um ícone novo, inclua o path no array
`ICONS` da classe — não introduza uma segunda biblioteca/fonte de ícones.
```php
<?= \App\Core\Icon::svg('users', 20) ?>
```

## Gráficos (ApexCharts)

Tema dark padrão em `dashboard-charts.js`: grid discreto (`rgba(255,255,255,.06)`), eixos em
`--text-muted`, tooltip `theme: 'dark'`. Gráfico de área/linha usa preenchimento em gradiente
(opacidade 40%→0). Ao criar um gráfico novo fora desse arquivo, replique esses parâmetros
(`fontFamily: 'Inter, sans-serif'`, `grid.borderColor`, `tooltip.theme: 'dark'`) para manter consistência.

## Animações (GSAP)

Tudo em `/public/assets/js/animations.js`, com guarda `typeof gsap === 'undefined'` (degrada
graciosamente sem JS/CDN indisponível):

| Função | Quando usar |
|---|---|
| `initPageFade()` | automático em toda página (fade-in do `.content` ao carregar) |
| `initSidebarIndicator()` | automático em toda página (desliza o indicador ativo) |
| `animateDashboardEntrance()` | chamar após montar os gráficos de um dashboard (stagger dos KPI cards + fade dos chart-cards via ScrollTrigger + count-up) |
| `animateCountUps()` | roda dentro de `animateDashboardEntrance()`; anima qualquer elemento com `data-countup="123.45" data-format="currency|percent|number"` |
| `animateSuccessBanner(seletor, ms)` | banner de confirmação (ex: lançamento salvo) — entra, some sozinho após `ms` |
| `animateCardEntrance(seletor)` | entrada simples de um card avulso (ex: tela de login, cliente criado) |
| `fadeNavigate(url)` | usar no `onchange` de um seletor que navega para outra URL (ex: trocar de cliente) — dá fade-out antes de navegar |
| `fadeSubmit(form)` | igual, mas para `<select onchange>` que dá `submit()` num formulário de filtro |

Duração padrão: 150–220ms para transições de UI (hover, foco), 400–600ms para entradas de conteúdo,
`power2.out` ou `back.out` como easing. Nada acima de ~1s exceto o count-up.

## Responsivo

- `≤900px`: `.kpi-grid` vira 2 colunas, `.chart-grid` e `.form-grid` viram 1 coluna.
- `≤800px`: sidebar vira barra horizontal no topo (rolável), tabelas ganham scroll horizontal.
- `≤520px`: `.kpi-grid` vira 1 coluna, `.filter-bar` empilha verticalmente.

## Ao criar uma tela nova

1. `<head>`: `<?php require __DIR__ . '/../partials/head-assets.php'; ?>` (tokens + app.css + GSAP + animations.js).
   Se a tela pertence ao contexto do cliente final, inclua também `partials/brand-style.php` logo depois.
2. `<body>`: envolva o conteúdo em `.app-shell` + `partials/sidebar.php` + `<main class="app-main"><div class="content">`.
3. Reaproveite as classes existentes (`.card`, `.kpi-grid`, `.badge`, `.btn*`, `.form-grid` etc.) —
   não crie CSS ad-hoc para algo que já tem componente equivalente aqui.
4. Se a tela tiver números que atualizam via filtro (KPIs, totais), adicione `data-countup` e chame
   `animateDashboardEntrance()` ou `animateCountUps()` depois de montar o DOM.
