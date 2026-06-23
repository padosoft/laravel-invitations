# Task: crea da zero un sito di documentazione con docmd (deploy farò io su Cloudflare Pages)

> Saved verbatim from Lorenzo (2026-06-23). This is the canonical playbook for building the
> `padosoft/laravel-invitations` docmd doc-site under `docs-site/`. The `docmd-docs` skill and the
> `rule-docmd-docs-sync` rule already exist in many sister packages (e.g. `laravel-ai-guardrails`,
> `laravel-pii-redactor`, `laravel-rebel-core`) — copy them in, don't reinvent.

Filled-in values for THIS package:
- `<PACKAGE_NAME>` = `padosoft/laravel-invitations`
- `<PROJECT_TITLE>` = `Laravel Invitations`
- `<ONE_LINE_DESC>` = Enterprise invite-by-code, referral, rewards, waitlist & anti-abuse suite for Laravel — multi-tenant, concurrency-safe, idempotent redemption, GDPR-ready, tri-surface (PHP + HTTP API + MCP).
- `<REPO_URL>` = `https://github.com/padosoft/laravel-invitations`
- `<SITE_URL>` = mirror the sister-package CF convention (confirm with Lorenzo) — e.g. `https://laravel-invitations.padosoft.com` / `*.pages.dev`
- `<BRAND_HEX>` = the Padosoft DS cyan/teal (align with the admin DS; `#0d9488`-class)
- `<BANNER_URL>` = `https://raw.githubusercontent.com/padosoft/laravel-invitations/main/resources/banner.png`
- `<DOCS_DIR>` = `docs-site`
- `<CF_PROJECT>` = `laravel-invitations`

---

Sei un agente che deve creare la documentazione pubblica di questo progetto usando
**docmd** (https://docs.docmd.io), un generatore di siti statici open-source basato
su Markdown. Segui questo playbook alla lettera. Verifica SEMPRE con un build reale,
non dare nulla per scontato.

## 0. Valori da riempire (sostituiscili ovunque compaiono)

- `<PACKAGE_NAME>`        es. `padosoft/NOME_PACKAGE`
- `<PROJECT_TITLE>`       es. `NOME_PACKAGE`
- `<ONE_LINE_DESC>`       (descrizione predila del repo github)
- `<REPO_URL>`            es. `https://github.com/NOME_PACKAGE`
- `<SITE_URL>`            dominio finale, es. `https://doc.NOME_PACKAGE.padosoft.com`
- `<BRAND_HEX>`           colore primario, es. `#0d9488`
- `<BANNER_URL>`          URL immagine banner del README, se esiste (altrimenti ometti)
- `<DOCS_DIR>`            cartella del sito doc nel repo, es. `docs-site`
- `<CF_PROJECT>`          nome progetto Cloudflare Pages, es. `NOME_PACKAGE`

## 1. Struttura da creare

```
<DOCS_DIR>/
  docmd.config.json          # config: metadata, url, navigation, theme, plugins
  package.json               # scripts + deps
  .node-version              # "20"  (CF Pages auto-pinning)
  .gitignore                 # ignora _site/, node_modules/, cache search
  .docmd-search/config.json  # modello embedding pinnato (COMMITTATO)
  assets/
    favicon.svg
    custom.css               # brand override
  scripts/
    check-no-raw-html.mjs    # guard CI
  docs/                       # TUTTE le pagine .md (le route ricalcano l'albero)
    index.md                  # homepage = route "/" (OBBLIGATORIA)
    ...
  _site/                      # output build (git-ignored)
```

Regola route: `docs/guides/foo.md` → `/guides/foo`. `docs/index.md` → `/`.

## 2. Install & versioni

```bash
cd <DOCS_DIR>
npm install -D @docmd/core           # VERIFICA la versione reale: `npm view @docmd/core version` (NON inventarla)
npm install docmd-search             # ricerca semantica
npm install -D @huggingface/transformers onnxruntime-node   # embedding a build-time (ONNX)
```

`package.json`:

```json
{
  "name": "<PROJECT_TITLE>-docs",
  "private": true,
  "type": "module",
  "scripts": {
    "dev": "docmd dev",
    "build": "docmd build",
    "check": "node scripts/check-no-raw-html.mjs"
  }
}
```

`.node-version`: contiene solo `20`.

## 3. docmd.config.json (completo, tutti i plugin attivi)

```json
{
  "title": "<PROJECT_TITLE>",
  "description": "<ONE_LINE_DESC>",
  "url": "<SITE_URL>",
  "src": "docs",
  "out": "_site",
  "base": "/",
  "favicon": "assets/favicon.svg",
  "theme": { "name": "default", "defaultMode": "dark", "customCss": ["assets/custom.css"] },
  "autoTitleFromH1": true,
  "copyCode": true,
  "pageNavigation": true,
  "minify": true,
  "markdown": { "breaks": true },
  "layout": {
    "spa": true,
    "header": { "enabled": true },
    "sidebar": { "collapsible": true, "defaultCollapsed": false },
    "optionsMenu": { "position": "header", "components": { "search": true, "themeSwitch": true } }
  },
  "footer": {
    "style": "minimal",
    "branding": true,
    "content": "© <AUTHOR_NAME> — [<ORG>](<ORG_URL>) · [GitHub](<REPO_URL>) · <LICENSE>",
    "columns": [
      { "title": "Project", "links": [ { "title": "GitHub", "path": "<REPO_URL>", "external": true } ] }
    ]
  },
  "navigation": [
    { "title": "Get Started", "icon": "rocket", "children": [
      { "title": "Introduction", "path": "/" },
      { "title": "Quickstart", "path": "/quickstart" }
    ]},
    { "title": "Links", "children": [
      { "title": "GitHub", "path": "<REPO_URL>", "external": true }
    ]}
  ],
  "plugins": {
    "search": { "enabled": true, "semantic": true, "placeholder": "Search the docs…" },
    "git": { "repo": "<REPO_URL>", "branch": "main", "editLink": true, "lastUpdated": true, "commitHistory": true, "dateFormat": "relative" },
    "seo": { "defaultDescription": "<ONE_LINE_DESC>", "aiBots": true, "openGraph": {}, "twitter": { "cardType": "summary_large_image" } },
    "sitemap": { "defaultChangefreq": "weekly", "defaultPriority": 0.8 },
    "mermaid": {},
    "math": {},
    "llms": { "fullContext": true },
    "analytics": { "enabled": false }
  }
}
```

Note plugin:
- **search** → vedi §4 (semantica).
- **git** → richiede `repo` e, in CI, checkout con storia completa (`fetch-depth: 0`).
- **sitemap/seo/llms** → richiedono il campo root `url`.
- **mermaid** → stessa fence ` ```mermaid `, nessun setup.
- **math** → KaTeX: inline `$…$`, blocco `$$…$$`.
- **llms** → genera `llms.txt` + `llms-full.txt`.

`navigation[]` è l'UNICA fonte della sidebar (non si auto-genera): ogni pagina nuova
va aggiunta qui o non compare. Le icone sono nomi **Lucide** in kebab-case (https://lucide.dev).

## 4. Ricerca semantica (docmd-search) — CRITICO per la CI

`plugins.search.semantic: true` usa `docmd-search`: gli embedding sono calcolati a
**build-time** con ONNX Runtime; il browser riceve solo vettori quantizzati Int8 e fa
keyword-match + cosine — 100% client-side, nessun server, nessun modello nel browser.

**Problema:** al primo build parte un *wizard interattivo* di scelta modello che si
**blocca in CI**. Evitalo committando il modello in `<DOCS_DIR>/.docmd-search/config.json`:

```json
{ "model": "Xenova/all-MiniLM-L6-v2", "chunkSize": 512, "chunkOverlap": 64, "incremental": true, "topK": 10 }
```

Modelli: `Xenova/all-MiniLM-L6-v2` (EN, ~23MB, consigliato per doc inglesi) oppure
multilingue `Xenova/multilingual-e5-small`. `.gitignore` deve **tenere** questo
`config.json` ma ignorare il resto:

```
node_modules/
_site/
.docmd-search/*
!.docmd-search/config.json
```

Test funzionale (senza browser): la stessa logica gira da CLI —
`(sleep 34; echo "una query parafrasata") | node node_modules/docmd-search/dist/bin/docmd-search.js docs`
deve restituire risultati pertinenti anche con parole diverse da quelle nei doc.

## 5. Banner in homepage (se `<BANNER_URL>` esiste)

In cima a `docs/index.md`, subito sotto il frontmatter:

```md
![<PROJECT_TITLE> banner](<BANNER_URL>)
```

## 6. Sintassi contenuti (container docmd — NIENTE MDX/JSX)

Markdown puro + container `:::`. NON usare mai tag tipo `<Card>`/`<Note>` (il guard li rifiuta).

| Serve | Sintassi |
|---|---|
| Callout | `::: callout info` … `:::` (tipi: `info`, `tip`, `warning`, `danger`, `success`) |
| Tabs | `::: tabs` poi blocchi `== tab "Label"`, chiudi `:::` |
| Steps | `::: steps` poi lista numerata `1. **Titolo**` con corpo indentato **3 spazi**, chiudi `:::` |
| Collapsible | `::: collapsible "Titolo"` … `:::` (prefisso `open` per aprirlo di default). NON esiste accordion esclusivo: usa collapsible indipendenti. |
| Cards | `::: grids` › `::: grid` › `::: card "Titolo" icon:lucide-name` › corpo › link `[Apri →](/path)` › `:::` (card) › `:::` (grid) › `:::` (grids) |
| Diagrammi | fence ` ```mermaid ` (flowchart, sequenceDiagram, ecc.) |
| Formule | KaTeX `$…$` / `$$…$$` |

## 7. Standard di struttura della documentazione

Organizza la doc in gruppi (sidebar): **Get Started, Guides, Concetti & Teoria,
Architettura, Best Practices, Operations, Reference**.

Ogni pagina "profonda" segue questo template accademico/senior:

1. **Motivazione** — quale problema risolve, perché esiste.
2. **Teoria** — definizioni formali, formule in KaTeX dove pertinente (es. metriche,
   complessità, probabilità). Tono accademico ma leggibile.
3. **Design + diagramma** — un diagramma **Mermaid** (`flowchart`/`sequenceDiagram`)
   per architettura, pipeline o flusso dati.
4. **Modello dati / contratto** — schema di input/output, tabelle, esempi.
5. **ADR (Architecture Decision Record)** — decisioni chiave in stile
   *Problema → Decisione → Conseguenze/Trade-off*, usando `::: collapsible` per voce.
6. **Esempio worked-through** — un caso concreto end-to-end con codice.
7. **Gotcha / limiti** — insidie reali, in `::: callout warning`.

Crea pagine dedicate per: **Architettura overview**, **Pipeline/Workflow** (con
diagramma della pipeline), **Decisioni architetturali (ADR)**, **Reference** (CLI/API),
oltre a Quickstart e guide pratiche. I diagrammi Mermaid sono testo puro → leggibili
anche dagli agenti AI e indicizzati dalla ricerca.

## 8. Brand (assets/custom.css)

```css
:root { --docmd-color-primary: <BRAND_HEX>; --color-primary: <BRAND_HEX>; --link-color: <BRAND_HEX>; }
:root[data-theme="dark"], .dark { --docmd-color-primary: <BRAND_HEX>; }
a { color: var(--link-color, <BRAND_HEX>); }
```

## 9. Guard CI (scripts/check-no-raw-html.mjs)

Fallisce il build se in `docs/**.md` sopravvive un tag componente JSX (utile se
converti da Mintlify/MDX, o per evitare HTML grezzo per errore):

```js
import { readdirSync, statSync, readFileSync } from 'node:fs';
import { join } from 'node:path';
const DOCS = join(process.cwd(), 'docs');
const TAG = /<\/?[A-Z][A-Za-z0-9]*(\s|>|\/)/g;
const bad = [];
(function walk(d){ for (const n of readdirSync(d)) { const p = join(d, n);
  if (statSync(p).isDirectory()) { walk(p); continue; }
  if (!n.endsWith('.md')) continue;
  readFileSync(p,'utf8').split('\n').forEach((l,i)=>{ const m=l.match(TAG); if(m) bad.push(`${p}:${i+1} ${m.join(' ')}`); });
}})(DOCS);
if (bad.length) { console.error('Raw component tags found:\n'+bad.join('\n')); process.exit(1); }
console.log('OK: no raw component tags.');
```

## 10. Build & verifica

```bash
cd <DOCS_DIR>
npm install
npm run check       # guard
npm run build       # genera _site/
```

Verifica obbligatoria sull'output:
- `_site/index.html` esiste (altrimenti "Root index.html not found" → manca `docs/index.md`).
- 0 occorrenze di `:::` come **testo visibile** (solo dentro `class="docmd-container …"`).
- KaTeX renderizzato nelle pagine con formule; 0 falsi positivi nelle pagine con `$variabili` in code-block.
- `_site/.docmd-search/manifest.json` + batch presenti; `llms.txt`, `sitemap.xml` generati.

## 11. Deploy: Cloudflare Pages (Opzione A — primaria, niente API key)

Usa la **Git integration** di Cloudflare Pages: CF builda ad ogni push su `main`.
Build config nel dashboard Pages:

| Campo | Valore |
|---|---|
| Production branch | `main` |
| Root directory | `<DOCS_DIR>` |
| Build command | `npm run build` |
| Build output directory | `_site` |
| Node | auto via `.node-version` (20) |

**Confermato funzionante:** il build della search semantica (`onnxruntime-node` +
`@huggingface/transformers`) gira sull'immagine Linux di CF. Assicurati che il
`package-lock.json` sia **lockfileVersion 3** (cross-platform): contiene già i binari
Linux (`@img/sharp-linux-x64`, onnxruntime per `linux`), quindi `npm ci` su CF risolve
i nativi giusti anche se il lock è stato generato su un altro OS. Collega
`<SITE_URL>` in Custom domains.

**Opzione B (fallback, NON necessaria):** workflow GitHub Actions che builda e fa
`wrangler pages deploy _site --project-name=<CF_PROJECT>` (secret
`CLOUDFLARE_API_TOKEN` + `CLOUDFLARE_ACCOUNT_ID`). Tienilo `workflow_dispatch`-only
(non auto-run) per non consumare minuti; usalo solo se il build di CF rompe su ONNX.

## 12. Gotcha (imparati sul campo — verifica con build reale)

1. **Pagina root obbligatoria** → serve `docs/index.md` (route `/`).
2. **`::: button` NON è un blocco appaiato**: il `:::` di chiusura esce come testo.
   Dentro le card usa un link markdown `[Apri →](/path)`.
3. **Steps**: de-indenta il corpo a colonna 0, poi re-indenta a **3 spazi** così
   code-fence e callout annidati restano dentro l'item della lista.
4. **Math**: KaTeX processa `$…$` solo fuori dai code-block; controlla l'HTML per
   `class="katex"` spurie nelle pagine piene di `$variabili`.
5. **Lockfile cross-platform**: usa npm v3 lockfile; non committare un lock che
   risolve solo gli optional-deps del tuo OS.

## 13. DELIVERABLE AGGIUNTIVI — crea skill + rule + Aggiorna il README

### A) Skill `docmd-docs` → `.claude/skills/docmd-docs/SKILL.md`
(Copia dalla sister package — già esistente in laravel-ai-guardrails / laravel-pii-redactor /
laravel-rebel-core — e adatta i valori. NON reinventare.)

### B) Rule rigida di auto-sync → `.claude/rules/rule-docmd-docs-sync.md`
(Idem: copia `rule-docmd-docs-sync.md` da una sister e adatta.)

### C) Inserimento link della doc in README del package
Aggiungi al README del package abbastanza in alto (dopo banner, badge, titolo, toc, why)
il link alla documentazione ufficiale `<SITE_URL>`.

## 14. Criteri di accettazione (checklist finale)

- [ ] `npm run build` verde, `_site/index.html` presente, 31+ pagine (o quante ne servono).
- [ ] Tutti i plugin attivi: search semantica, git, seo, sitemap, mermaid, math, llms.
- [ ] Indice semantico generato + test query parafrasata pertinente.
- [ ] 0 `:::` come testo visibile; KaTeX ok; mermaid renderizzati.
- [ ] Banner in homepage (se presente), footer con credito autore, brand color applicato.
- [ ] CF Pages Git integration configurata (root/build/output/Node) → deploy live su `<SITE_URL>`.
- [ ] Skill `docmd-docs` e rule rigida di auto-sync create e coerenti.
