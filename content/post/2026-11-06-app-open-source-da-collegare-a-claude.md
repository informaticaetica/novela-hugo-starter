---
title: "Claude e open source: tutte le app che puoi collegare (guida 2026)"
date: 2026-11-06
hero: /images/hero-2.jpg
excerpt: Da Obsidian a Home Assistant, da Blender a n8n: la mappa completa delle app open source che si collegano a Claude tramite MCP, e dei client liberi che usano Claude come motore.
timeToRead: 10
authors:
  - Redazione AI24

---

Claude non è più soltanto una finestra di chat. Grazie al **Model Context Protocol (MCP)**, lo standard aperto lanciato da Anthropic a fine 2024 e oggi gestito in modo neutrale sotto l'ombrello della Linux Foundation, l'assistente può leggere le tue note, accendere le luci di casa, modellare in 3D, interrogare un database o pubblicare un articolo sul tuo blog.

La parte più interessante? Moltissime di queste integrazioni sono **open source**: puoi installarle, ispezionarne il codice e farle girare sui tuoi server. In questa guida abbiamo raccolto le app libere più utili da collegare a Claude, divise per categoria, più una seconda lista: i **client open source** che usano Claude come "cervello".

## Come si collega un'app a Claude: tre strade

Prima della lista, conviene capire i tre modi in cui un'app può parlare con Claude.

1. **Connettore personalizzato remoto (claude.ai, app desktop e mobile).** Se l'app espone un server MCP raggiungibile via HTTPS, lo aggiungi da *Impostazioni → Connettori → Aggiungi connettore personalizzato*, incollando l'URL. Funziona su tutti i piani, compreso quello gratuito (che però è limitato a un solo connettore personalizzato). Attenzione: la connessione parte dall'infrastruttura cloud di Anthropic, quindi un server in rete locale deve essere esposto in modo sicuro (reverse proxy, tunnel, ecc.).
2. **Server MCP locale (Claude Desktop e Claude Code).** Il server gira sul tuo computer e viene avviato da Claude stesso, configurandolo nel file `claude_desktop_config.json` o con il comando `claude mcp add`. È la strada più semplice per le app desktop come Obsidian, Blender o Ghidra.
3. **API di Anthropic dentro un'app open source.** Il percorso inverso: è l'app libera (una chat self-hosted, un editor, un agente di coding) a chiamare Claude tramite la tua chiave API.

> Regola pratica: le app sul tuo PC passano dal server MCP locale; i servizi self-hosted raggiungibili da internet si collegano come connettore remoto.

## Parte 1 — App open source da collegare a Claude via MCP

### Note, conoscenza e ricerca

**Obsidian** — Il server [mcp-obsidian](https://github.com/MarkusPfundstein/mcp-obsidian) (Python, licenza MIT) dialoga con il vault tramite il plugin comunitario *Local REST API*. Claude può cercare tra le note, leggerle, aggiungere contenuti e riassumere intere cartelle. Esistono numerose alternative della community: prima di sceglierne una, controlla che il repository sia ancora attivo.

**Joplin** — Il pacchetto [second-brain-joplin](https://pypi.org/project/second-brain-joplin/) sfrutta l'API locale di Joplin Desktop (quella del Web Clipper) per permettere a Claude di interrogare il tuo archivio di note in tempo reale.

**Zotero** — [zotero-mcp](https://github.com/54yyyu/zotero-mcp) (MIT) è tra i server più apprezzati da studenti e ricercatori: ricerca nella libreria, metadati, testo completo dei PDF, estrazione delle annotazioni e perfino ricerca semantica sull'intera bibliografia. Richiede Zotero 7 o successivo.

### Cloud personale e documenti

**Nextcloud** — Ci sono più server della community, per esempio [nc-mcp-server](https://pypi.org/project/nc-mcp-server/), che espone file, calendario, contatti e altro. Un dettaglio che apprezziamo: i permessi sono **in sola lettura per impostazione predefinita**, con scrittura e cancellazione da abilitare esplicitamente.

**Paperless-ngx** — Esistono server MCP non ufficiali che permettono a Claude di cercare e consultare i documenti archiviati nel tuo gestionale documentale self-hosted: comodissimo per ritrovare una bolletta o un contratto descrivendolo a parole.

### Casa intelligente

**Home Assistant** — Dalla versione 2025.2 Home Assistant include un'**integrazione MCP Server ufficiale**: Claude può leggere lo stato dei sensori e comandare i dispositivi esposti all'assistente. Per controlli più avanzati la community ha sviluppato [ha-mcp](https://github.com/homeassistant-ai/ha-mcp), installabile anche come add-on. Per usarlo dal Claude "cloud" serve un URL HTTPS pubblico (Nabu Casa, Cloudflare Tunnel o un reverse proxy).

### Automazione e workflow

**n8n** — Il nodo **MCP Server Trigger** trasforma qualsiasi workflow in uno strumento richiamabile da Claude, tramite un URL dedicato (trasporti SSE e Streamable HTTP). Esiste anche il nodo inverso, *MCP Client Tool*, per far usare server MCP agli agenti costruiti in n8n. Nota: n8n ha una licenza *fair-code*, non strettamente open source.

**Activepieces** — Alternativa con licenza MIT e completamente self-hostabile: può esporre i propri "pezzi" e flussi come un unico server MCP da collegare a Claude.

### Siti web e CMS

**WordPress** — L'[MCP Adapter](https://github.com/WordPress/mcp-adapter) è il progetto **ufficiale** nato dall'iniziativa *AI Building Blocks for WordPress* e richiede WordPress 6.9 o successivo. Permette a Claude di creare bozze, gestire pagine, categorie e media. Ideale per redazioni che vogliono un assistente editoriale collegato direttamente al CMS.

### Sviluppo, dati e infrastruttura

- **Git** — Uno dei server di riferimento mantenuti dal progetto MCP: cronologia, diff, commit e branch di un repository locale.
- **Gitea / Forgejo** — Server della community per gestire repository, issue e pull request sulla tua forgia self-hosted.
- **Playwright MCP** — Il server open source di Microsoft che dà a Claude un browser vero: navigare, compilare form, fare screenshot e test end-to-end. Si avvia con `npx @playwright/mcp@latest`.
- **PostgreSQL** — Progetti come *Postgres MCP Pro* offrono query sicure, piani di esecuzione, suggerimenti sugli indici e controlli di salute del database.
- **Grafana** — [mcp-grafana](https://github.com/grafana/mcp-grafana) è il server ufficiale scritto in Go: dashboard, Prometheus, Loki, regole di alerting e incidenti consultabili a parole.

### Creatività, ingegneria e sicurezza

- **Blender** — [BlenderMCP](https://github.com/ahujasid/blender-mcp) collega Claude a Blender (3.0+): creazione di scene, modellazione e materiali guidati da prompt. È stato uno dei progetti MCP più virali in assoluto.
- **KiCad** — Diversi server (per esempio *kicad-mcp*) permettono di analizzare progetti di circuiti stampati ed eseguire i controlli DRC tramite la CLI di KiCad.
- **Ghidra** — [GhidraMCP](https://github.com/LaurieWired/GhidraMCP) porta Claude dentro lo strumento di reverse engineering della NSA: decompilazione, rinomina automatica di funzioni e variabili, elenco di import ed export.

### I server di riferimento del progetto MCP

Il repository ufficiale [modelcontextprotocol/servers](https://github.com/modelcontextprotocol/servers) mantiene alcuni server "base" che vale la pena conoscere: **Filesystem** (accesso controllato a cartelle locali), **Fetch** (lettura di pagine web), **Memory** (memoria persistente a grafo), **Sequential Thinking**, **Time**, **Git** ed **Everything** (un server di prova con tutte le funzioni del protocollo).

## Parte 2 — App open source che usano Claude come motore

Qui la logica si ribalta: è l'app libera a "chiamare" Claude tramite API, spesso aggiungendo a sua volta il supporto ai server MCP.

### Interfacce di chat self-hosted

- **LibreChat** — Tra le prime piattaforme a supportare MCP in modo completo. Supporta l'endpoint Anthropic e mostra i server MCP configurati direttamente nella chat. Perfetta per aziende e scuole che vogliono un "ChatGPT interno" con Claude dietro le quinte.
- **Open WebUI** — Dalla versione 0.6.31 supporta **MCP in modo nativo** (solo trasporto Streamable HTTP; per server stdio o SSE si usa il proxy *mcpo*). Si collega ai modelli Claude tramite API.

### Agenti per programmatori

- **Cline** (Apache 2.0) — Agente per VS Code e altri editor, ora anche da riga di comando; supporta Claude e i server MCP.
- **Continue** (Apache 2.0) — Assistente per VS Code e JetBrains, con regole e controlli applicabili anche in CI.
- **Aider** — Il veterano del "pair programming" da terminale, costruito attorno a Git; Claude è tra i modelli consigliati.
- **Goose** — Agente open source creato da Block: gira in locale ed è estendibile tramite MCP, utile anche oltre il codice (browser, database, automazioni).
- **OpenCode** (MIT) — Agente da terminale molto popolare, compatibile con decine di provider tra cui Anthropic.
- **Zed** — Editor open source ad alte prestazioni con assistente AI integrato, compatibile con i modelli Claude e con i server MCP.

## Tabella riassuntiva

| App | Categoria | Come si collega | Stato |
|---|---|---|---|
| Obsidian | Note | MCP locale (Local REST API) | Community |
| Joplin | Note | MCP locale | Community |
| Zotero | Ricerca | MCP locale | Community |
| Nextcloud | Cloud | MCP locale o remoto | Community |
| Paperless-ngx | Documenti | MCP | Community |
| Home Assistant | Domotica | MCP remoto | **Ufficiale** + community |
| n8n | Automazione | MCP Server Trigger | **Ufficiale** |
| Activepieces | Automazione | MCP | **Ufficiale** |
| WordPress | CMS | MCP Adapter | **Ufficiale** |
| Grafana | Monitoraggio | mcp-grafana | **Ufficiale** |
| Playwright | Browser | MCP locale | **Ufficiale** (Microsoft) |
| Blender | 3D | MCP locale | Community |
| KiCad | Elettronica | MCP locale | Community |
| Ghidra | Sicurezza | MCP locale | Community |
| LibreChat | Chat self-hosted | API Anthropic + MCP | Open source |
| Open WebUI | Chat self-hosted | API Anthropic + MCP | Open source |
| Cline, Continue, Aider, Goose, OpenCode, Zed | Coding | API Anthropic (+ MCP) | Open source |

## Sicurezza: cinque regole prima di collegare qualsiasi cosa

Open source non significa automaticamente sicuro. Le analisi indipendenti pubblicate nel 2026 hanno trovato vulnerabilità in una quota consistente dei server MCP in circolazione, e perfino alcuni server di riferimento hanno ricevuto CVE. Qualche precauzione:

1. **Preferisci i server ufficiali** del progetto (Home Assistant, WordPress, Grafana, n8n) o quelli più mantenuti.
2. **Fissa una versione** invece di usare sempre `@latest`, e aggiorna consapevolmente.
3. **Parti dalla sola lettura** e concedi i permessi di scrittura solo quando servono.
4. **Usa token dedicati** con privilegi minimi, mai la password dell'amministratore.
5. **Non esporre servizi interni** su internet senza autenticazione: per i connettori remoti usa OAuth, un tunnel sicuro e, se serve, una lista di IP consentiti.

## Dove trovarne altri

L'ecosistema cresce ogni settimana. I punti di partenza migliori sono il **registro ufficiale MCP**, la directory dei connettori di Claude e la lista comunitaria [awesome-mcp-servers](https://github.com/punkpeye/awesome-mcp-servers), che raccoglie migliaia di progetti. Prima di installare, guarda sempre data dell'ultimo commit, numero di contributori e issue aperte.

## Conclusione

Collegare Claude alle app open source che già usi è il modo più concreto per trasformarlo da chatbot a collaboratore: le tue note su Obsidian, la bibliografia su Zotero, la casa su Home Assistant, il blog su WordPress. E se vuoi tenere tutto sotto il tuo controllo, client come LibreChat e Open WebUI ti permettono di usare Claude dentro un'interfaccia libera e self-hosted.

Hai un'integrazione open source che non abbiamo citato? Scrivici: aggiorneremo questa guida.
