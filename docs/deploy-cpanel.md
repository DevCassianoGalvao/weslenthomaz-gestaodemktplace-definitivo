# Deploy no cPanel — gestorweslen.com.br/paineldemetricas

Guia completo: sincronizar o GitHub com o cPanel via Git Version Control,
instalar dependências, configurar o banco e publicar o sistema na subpasta
`gestorweslen.com.br/paineldemetricas`.

O projeto usa `BASE_PATH` configurável (ver `config/config.php`) justamente
para funcionar corretamente numa subpasta — todos os links internos passam
por `url()`, então não precisa editar nenhum arquivo de código pra isso.

---

## 1. Pré-requisitos no cPanel

Confira antes de começar (em "Selecionar Versão do PHP" / "MultiPHP Manager"):

- **PHP 8.2 ou superior** selecionado pro dominio.
- Extensões ativas: `pdo_mysql`, `mbstring`, `zip`, `gd`, `curl`, `xml` (a maioria já vem ligada por padrão; confira `zip` especialmente — é comum vir desligada e é obrigatória pro PhpSpreadsheet gerar `.xlsx`).
- **Git** disponível no servidor (cPanel → "Git Version Control" precisa disso; se a opção não aparecer no menu, a hospedagem não suporta e você usa o [Plano B](#plano-b-sem-git-version-control) no fim do guia).
- O repositorio ja inclui `vendor/`, entao o deploy nao depende de rodar Composer no servidor.

## 2. Criar o banco de dados MySQL

Se você vai manter o banco já usado pelo sistema antigo, não crie outro banco.
Use o mesmo `DB_NAME`, `DB_USER` e `DB_PASS` no novo `config.local.php`. Isso
mantém clientes, usuários, marketplaces, períodos, lançamentos e histórico.

Crie um banco novo apenas se quiser uma instalação zerada:

1. cPanel → **MySQL® Databases**.
2. Em "Create New Database", crie algo como `cassian1_weslenmkt` (o cPanel prefixa automaticamente com seu usuário).
3. Em "MySQL Users → Add New User", crie um usuário e senha forte (anote os dois).
4. Em "Add User to Database", adicione esse usuário ao banco criado e marque **All Privileges**.
5. Anote os três valores finais: nome do banco, usuário e senha — vai usar no passo 6.

## 3. Conectar o GitHub ao cPanel (Git Version Control)

1. cPanel → **Git™ Version Control** → **Create**.
2. **Clone a Repository** (ligue o toggle "Clone a Repository").
3. **Repository URL**: `https://github.com/DevCassianoGalvao/weslenthomaz-gestaodemktplace-definitivo.git`
   - Se o repositório for **privado**, o cPanel vai pedir autenticação nesse clone. O jeito mais simples: gere um **Personal Access Token** no GitHub (Settings → Developer settings → Personal access tokens → Fine-grained, com permissão de leitura só nesse repo) e use a URL no formato `https://SEU_USUARIO:SEU_TOKEN@github.com/DevCassianoGalvao/weslenthomaz-gestaodemktplace-definitivo.git` no campo acima. Não deixe esse token em nenhum lugar versionado.
4. **Repository Path**: aqui está o pulo do gato. Digite:
   ```
   repositories/paineldemetricas
   ```
   (o cPanel completa automaticamente com o caminho da sua conta, tipo
   `/home/seuusuario/repositories/paineldemetricas`.)
   **Não** aponte direto pra `gestorweslen.com.br/paineldemetricas` — o cPanel Git clona um repositório de trabalho completo (incluindo a pasta `.git`) e você não quer isso exposto na web. Vamos "publicar" (deploy) desse repositório pra dentro da pasta do domínio no passo seguinte.
5. Clique **Create**. O cPanel clona o repo pra essa pasta.

> **Nota sobre o caminho do site**: confirme em cPanel → Domains → coluna "Document Root" qual é a pasta real do domínio `gestorweslen.com.br`. O `.cpanel.yml` deste projeto aponta para `$HOME/gestorweslen.com.br/paineldemetricas/`. Se o cPanel criar o domínio dentro de outra estrutura (ex: `public_html/gestorweslen.com.br/`), ajuste essa linha no `.cpanel.yml` antes de usar o deploy.

### 3.1 Publicar (deploy) do repositório pra dentro do domínio

O cPanel Git Version Control tem um recurso de "Pull or Deploy" que copia os
arquivos do repositório clonado pra um destino final, usando um arquivo
`.cpanel.yml` na raiz do projeto. **Esse arquivo já está no repositório**
(`.cpanel.yml`, na raiz), então você não precisa criar nada — o cPanel lê
ele automaticamente assim que detecta o repositório.

Toda vez que você clicar **"Update from Remote"** (puxa do
GitHub) seguido de **"Deploy HEAD Commit"** na tela do repositório no cPanel,
os arquivos são copiados pra `gestorweslen.com.br/paineldemetricas/` automaticamente.

> **Pegadinha do cPanel**: cada linha da lista `tasks` do `.cpanel.yml` roda
> num shell **separado** — uma variável criada com `export` numa linha some
> antes da próxima linha rodar. Por isso o comando final ficou tudo numa
> linha só (`cp -R * .htaccess "$HOME/.../paineldemetricas/"`), sem
> `export` no meio. Se um dia o deploy não copiar nada silenciosamente,
> desconfie primeiro disso.
>
> Pra ver se o deploy realmente rodou (e o que deu errado, se deu), clique
> em **"History"** na tela do repositório (aparece do lado do "HEAD Commit")
> — mostra o log de cada deploy, incluindo erros do `cp`.

**Resumindo o fluxo de atualização depois que estiver tudo configurado:**
1. Você faz alterações e `git push` normalmente no seu computador.
2. No cPanel → Git Version Control → seu repositório → **"Update from Remote"**.
3. Clique **"Deploy HEAD Commit"**.

Isso não é 100% automático (não sincroniza sozinho a cada push), mas é dois
cliques no painel. Se quiser sincronização automática de verdade a cada push
(via webhook do GitHub chamando a API do cPanel), isso dá pra configurar
depois — é mais complexo e não é necessário pra colocar o sistema no ar hoje.

## 4. Dependencias PHP

O repositorio definitivo ja versiona a pasta `vendor/` para evitar falhas de
Composer em hospedagem compartilhada. Depois do deploy, confirme apenas que
existe:

```txt
gestorweslen.com.br/paineldemetricas/vendor/autoload.php
```

Se algum dia atualizar `composer.json`/`composer.lock`, rode Composer localmente
e suba o `vendor/` atualizado no proximo commit.

## 5. Ativar a extensão zip do PHP

cPanel → **Select PHP Version** (ou "MultiPHP Manager") → **Options/Extensions**
→ marque `zip` → **Save**. Sem isso, a exportação em Excel (`.xlsx`) quebra.

## 6. Configurar o `config/config.local.php`

Esse arquivo **nunca vai pro Git** (fica de fora do repositório de propósito,
por segurança). Crie ele direto no servidor via **File Manager**, dentro de
`gestorweslen.com.br/paineldemetricas/config/config.local.php`:

```php
<?php
return [
    'DB_HOST' => 'localhost',
    'DB_NAME' => 'cassian1_weslenmkt',       // o nome real que você criou no passo 2
    'DB_USER' => 'cassian1_xxxxx',            // o usuário real
    'DB_PASS' => 'sua-senha-aqui',
    'APP_URL' => 'https://gestorweslen.com.br/paineldemetricas',
    'APP_ENV' => 'production',

    // Essencial pro app funcionar na subpasta:
    'BASE_PATH' => '/paineldemetricas',

    // Só usados pelo install.php pra criar o admin inicial — depois pode
    // apagar essas duas linhas (ou deixar, não tem problema, só não são
    // mais lidas depois que o admin já existe no banco).
    'ADMIN_EMAIL' => 'seu-email-real@dominio.com',
    'ADMIN_PASSWORD' => 'uma-senha-forte-temporaria',
];
```

Use como base o arquivo `config/config.local.example.php` que já está no
repositório, só ajustando os valores reais.

## 7. Rodar a instalação do banco (schema + admin inicial)

Se o novo domínio estiver apontando para o banco antigo, este passo não é
obrigatório. Primeiro teste login e dashboard. Rode o instalador só se o banco
estiver vazio ou se você precisar recriar tabelas/catálogo/admin inicial.

Para banco antigo que já estava em uso antes do suporte a múltiplas contas de
marketplace, rode uma vez:

```
https://gestorweslen.com.br/paineldemetricas/migrate-marketplace-accounts.php
```

Depois apague `public/migrate-marketplace-accounts.php` e
`database/migrate_marketplace_accounts.php`.

Para habilitar investimento em Ads e ROAS, depois de publicar esta versão abra
uma vez:

```
https://gestorweslen.com.br/paineldemetricas/migrate-ads-metrics.php
```

A migration apenas adiciona as colunas de Ads com valor inicial `0`; ela não
remove nem altera os lançamentos existentes. Depois da confirmação, apague
`public/migrate-ads-metrics.php` e `database/migrate_ads_metrics.php` pelo
File Manager.

Acesse no navegador:

```
https://gestorweslen.com.br/paineldemetricas/install.php
```

A tela vai confirmar as tabelas criadas, o catálogo de marketplaces semeado
e o usuário admin criado com o e-mail/senha que você colocou no passo 6.

## 8. (Opcional) Gerar dados de demonstração

Pra já ter algo pra mostrar ao cliente, acesse:

```
https://gestorweslen.com.br/paineldemetricas/seed-demo.php
```

Isso cria 5 clientes fictícios (Bella Casa Decor, TechNova Eletrônicos, Verde
Vida Suplementos, Urban Style Moda, PetFeliz Acessórios), cada um com
marketplaces vinculados e 3 meses de lançamentos com valores plausíveis. A
senha de acesso pra todas as contas de demonstração é `Demonstracao@123`
(a tela mostra os e-mails de cada uma). É seguro rodar mais de uma vez —
ele pula qualquer cliente que já exista, nunca duplica.

**Esse é dado de demonstração, não real.** Quando o cliente de verdade for
usar o sistema pra valer, apague essas 5 empresas fictícias (pela tela de
Clientes → não tem botão de apagar cliente ainda no sistema, então por ora
seria via phpMyAdmin — me avise se quiser que eu adicione essa função).

## 9. Apagar os instaladores

**Depois de rodar os passos 7 e 8**, apague estes 4 arquivos (via File
Manager ou Terminal) — eles não devem ficar acessíveis publicamente depois
de usados:

```
gestorweslen.com.br/paineldemetricas/install.php
gestorweslen.com.br/paineldemetricas/database/install.php
gestorweslen.com.br/paineldemetricas/seed-demo.php
gestorweslen.com.br/paineldemetricas/database/seed_demo.php
```

## 10. Testar

Acesse `https://gestorweslen.com.br/paineldemetricas/` e faça login
com o admin criado no passo 6. Confira que CSS, gráficos e navegação
carregam normalmente (se algo vier sem estilo/quebrado, o suspeito nº 1 é o
`BASE_PATH` não estar exatamente `/paineldemetricas`, sem barra no final).

---

## Plano B: sem Git Version Control

Se sua hospedagem não tiver a opção "Git Version Control" no cPanel, o
caminho mais simples é: rodar `git clone` (ou baixar o ZIP do GitHub) na sua
maquina e subir a pasta inteira (exceto `.git/`) via **FTP** pra
`gestorweslen.com.br/paineldemetricas/`. Pra
atualizar depois, repete o processo ou usa um cliente FTP com sincronização
(FileZilla tem isso). Menos elegante que o Git, mas funciona igual.
