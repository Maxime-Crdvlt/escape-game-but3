/* CARTE RÉSEAU : le style d'un simulateur de réseau
   Application du bureau. Utilise les outils de app.js (el, svg, action, toast, creerFenetre, S, appliquerEtat...). */

const PT_W = 800, PT_H = 470;
let ptDonnees = null, ptSig = '', ptSel = null, ptMode = 'realtime', ptTimer = null, ptPaquetTimer = null, ptEvents = [], ptSecondes = 0, ptDialogOnglet = 'config';
const PICTOS_PT = {
  routeur: g => {
    g.appendChild(svg('circle', { r: 25, fill: '#2f6db5', stroke: '#1b4a85', 'stroke-width': 2 }));
    g.appendChild(svg('path', { d: 'M-15,-5 H15 M9,-11 L15,-5 L9,1 M15,7 H-15 M-9,1 L-15,7 L-9,13', stroke: '#fff', 'stroke-width': 3, fill: 'none', 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }));
  },
  switch: g => {
    g.appendChild(svg('rect', { x: -32, y: -17, width: 64, height: 34, rx: 4, fill: '#2f6db5', stroke: '#1b4a85', 'stroke-width': 2 }));
    g.appendChild(svg('path', { d: 'M-22,-5 H-4 M-9,-9 L-4,-5 L-9,-1 M22,-5 H4 M9,-9 L4,-5 L9,-1 M-22,6 H-4 M-9,2 L-4,6 L-9,10 M22,6 H4 M9,2 L4,6 L9,10', stroke: '#fff', 'stroke-width': 2.4, fill: 'none', 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }));
  },
  serveur: g => {
    g.appendChild(svg('rect', { x: -18, y: -28, width: 36, height: 56, rx: 3, fill: '#a3adb9', stroke: '#5c6b7a', 'stroke-width': 2 }));
    for (const y of [-20, -8, 4]) g.appendChild(svg('rect', { x: -13, y, width: 26, height: 7, rx: 1, fill: '#5c6b7a' }));
    g.appendChild(svg('circle', { cx: 10, cy: 20, r: 3, fill: '#3ddc84' }));
  },
  poste: g => {
    g.appendChild(svg('rect', { x: -27, y: -25, width: 54, height: 36, rx: 3, fill: '#37474f', stroke: '#222', 'stroke-width': 1.5 }));
    g.appendChild(svg('rect', { x: -23, y: -21, width: 46, height: 28, fill: '#4aa3e0' }));
    g.appendChild(svg('rect', { x: -8, y: 11, width: 16, height: 6, fill: '#90a4ae' }));
    g.appendChild(svg('rect', { x: -18, y: 17, width: 36, height: 5, rx: 2, fill: '#78909c' }));
  },
  imprimante: g => {
    g.appendChild(svg('rect', { x: -14, y: -22, width: 28, height: 16, fill: '#fff', stroke: '#90a4ae' }));
    g.appendChild(svg('rect', { x: -27, y: -7, width: 54, height: 26, rx: 4, fill: '#cfd8dc', stroke: '#78909c', 'stroke-width': 1.5 }));
    g.appendChild(svg('rect', { x: -18, y: 10, width: 36, height: 8, fill: '#fff', stroke: '#90a4ae' }));
  },
  pirate: g => {
    g.appendChild(svg('path', { d: 'M-34,14 a14,14 0 0 1 4,-27 a20,20 0 0 1 38,-6 a16,16 0 0 1 26,12 a12,12 0 0 1 -4,21 z', fill: '#e3e8ee', stroke: '#8fa3b5', 'stroke-width': 2 }));
    g.appendChild(svg('text', { y: 6, 'text-anchor': 'middle', 'font-size': 22 }, '☠'));
  },
};

function construireReseau(geo) {
  const corps = el('div', 'pt');
  corps.innerHTML = `<div class="pt-menu"><span>File</span><span>Edit</span><span>Options</span><span>View</span><span>Tools</span><span>Extensions</span><span>Window</span><span>Help</span></div>
    <div class="pt-tool"><span class="ti">📄</span><span class="ti">📂</span><span class="ti">💾</span><span class="ti">🖨️</span><span class="sep"></span><span class="ti">🔍</span><span class="ti">✋</span><span class="ti">🗑️</span><span class="ti">✉️</span><span class="sep"></span><span class="pt-titre">Logical · DGSI-Reseau.pkt</span>
      <label><input type="checkbox" id="pt-dep" checked> Afficher les dépendances</label></div>
    <div class="pt-body"><div class="pt-travail" id="pt-travail"><svg id="reseau-svg" viewBox="0 0 ${PT_W} ${PT_H}" preserveAspectRatio="xMidYMid meet"></svg><div class="pt-dialog" id="pt-dialog"></div></div>
      <div class="pt-sim" id="pt-sim"><div class="pt-sim-titre">Simulation Panel · Event List</div><div class="pt-sim-liste"><table><thead><tr><th>Temps</th><th>De</th><th>Vers</th><th>Type</th></tr></thead><tbody id="pt-events"></tbody></table></div></div></div>
    <div class="pt-modes"><button id="pt-realtime" class="on">Realtime</button><button id="pt-simulation">Simulation</button></div>
    <div class="pt-palette"><span class="cat on">📡</span><span class="cat">🔀</span><span class="cat">🖥️</span><span class="cat">☁️</span><span class="cat">🔌</span><span class="sep"></span><small>Cliquez sur un équipement pour l'ouvrir : onglets Config et CLI</small></div>
    <div class="pt-etat"><span id="pt-heure">Time: 00:00:00</span><span>Power Cycle Devices</span><span>Fast Forward Time</span></div>`;
  const f = creerFenetre('reseau', 'Simulateur réseau - DGSI-Reseau.pkt', geo, corps);
  f.auOuvrir = () => { chargerReseau(); clearInterval(ptTimer); ptTimer = setInterval(() => { if (f.ouverte && !f.reduite) chargerReseau(); }, 2000); };
  $('pt-dep').onchange = () => { ptSig = ''; ptDessiner(); };
  $('pt-realtime').onclick = () => ptSimulation(false);
  $('pt-simulation').onclick = () => ptSimulation(true);
  setInterval(() => { if (f.ouverte && ptMode === 'sim') { ptSecondes++; ptHeure(); } }, 1000);
}

function ptHeure() { const s = ptSecondes; $('pt-heure').textContent = 'Time: ' + [Math.floor(s / 3600), Math.floor(s / 60) % 60, s % 60].map(x => String(x).padStart(2, '0')).join(':'); }

async function chargerReseau() {
  let d;
  try { d = await action('reseau'); } catch (e) { return; }
  if (d.erreur) {
    $('reseau-svg').innerHTML = '';
    const t = svg('text', { x: PT_W / 2, y: PT_H / 2, 'text-anchor': 'middle', 'font-size': 18, fill: '#667' }, '🔒 ' + d.erreur);
    $('reseau-svg').appendChild(t);
    return;
  }
  const sig = JSON.stringify(d);
  if (sig === ptSig) return;
  ptSig = sig; ptDonnees = d;
  ptDessiner();
  if ($('pt-dialog').style.display === 'block' && ptSel) ptDialogDessiner();
}

function ptPoint(a, b, t) { return { x: a.x + (b.x - a.x) * t, y: a.y + (b.y - a.y) * t }; }

function ptDessiner() {
  const d = ptDonnees, s = $('reseau-svg');
  if (!d) return;
  const couche = s.querySelector('#pt-paquets');   // les paquets en vol survivent au redessin
  s.innerHTML = '';
  const H = Object.fromEntries(d.hotes.map(h => [h.id, h]));
  const pirateBloque = S.step >= 3;

  for (const l of d.liens) {
    const a = H[l.de], b = H[l.vers], wan = l.vers === 'pirate' || l.de === 'pirate';
    s.appendChild(svg('line', { x1: a.x, y1: a.y, x2: b.x, y2: b.y, stroke: wan ? '#c0392b' : '#222', 'stroke-width': 2, 'stroke-dasharray': wan ? '7 4' : '' }));
    const bas = wan && pirateBloque;
    for (const t of [0.24, 0.76]) { const p = ptPoint(a, b, t); s.appendChild(svg('circle', { cx: p.x, cy: p.y, r: 4, fill: bas ? '#d32f2f' : '#2fbf4f', stroke: '#145a24', 'stroke-width': 1 })); }
  }
  if ($('pt-dep').checked) {
    for (const dep of d.dependances) {
      const a = H[dep.de], b = H[dep.vers];
      const dx = b.x - a.x, dy = b.y - a.y, len = Math.hypot(dx, dy) || 1;
      const c = { x: (a.x + b.x) / 2 - dy / len * 60, y: (a.y + b.y) / 2 + dx / len * 60 };
      const pc = (p, t) => { const u = 1 - t; return { x: u * u * p[0].x + 2 * u * t * p[1].x + t * t * p[2].x, y: u * u * p[0].y + 2 * u * t * p[1].y + t * t * p[2].y }; };
      let t = 1;
      while (t > 0.4) { const q = pc([a, c, b], t); if (Math.abs(q.x - b.x) > 40 || Math.abs(q.y - b.y) > 40) break; t -= 0.01; }
      const trace = []; for (let k = 0; k <= 40; k++) trace.push(pc([a, c, b], t * k / 40));
      s.appendChild(svg('path', { d: 'M' + trace.map(p => p.x.toFixed(1) + ',' + p.y.toFixed(1)).join(' L'), fill: 'none', stroke: '#e0a800', 'stroke-width': 2, 'stroke-dasharray': '6 4' }));
      const bout = trace[40], av = trace[37], ang = Math.atan2(bout.y - av.y, bout.x - av.x);
      const pt = (r, n) => (bout.x - r * Math.cos(ang) + n * Math.sin(ang)).toFixed(1) + ',' + (bout.y - r * Math.sin(ang) - n * Math.cos(ang)).toFixed(1);
      s.appendChild(svg('polygon', { points: `${bout.x.toFixed(1)},${bout.y.toFixed(1)} ${pt(13, 5.5)} ${pt(13, -5.5)}`, fill: '#e0a800' }));
      const m = pc([a, c, b], t / 2);
      s.appendChild(svg('text', { x: m.x, y: m.y - 4, 'text-anchor': 'middle', 'font-size': 10, fill: '#8a6500', 'font-style': 'italic', 'paint-order': 'stroke', stroke: '#fbfcfe', 'stroke-width': 3 }, 'dépend de'));
    }
  }
  for (const h of d.hotes) {
    const g = svg('g', { transform: `translate(${h.x},${h.y})`, style: 'cursor:pointer' });
    if (ptSel === h.id) g.appendChild(svg('rect', { x: -46, y: -40, width: 92, height: 98, rx: 6, fill: 'rgba(80,150,230,.12)', stroke: '#3b82d4', 'stroke-width': 1.5, 'stroke-dasharray': '4 3' }));
    (PICTOS_PT[h.type] || PICTOS_PT.poste)(g);
    g.appendChild(svg('text', { y: 44, 'text-anchor': 'middle', 'font-size': 11.5, 'font-weight': 700, fill: '#111', 'paint-order': 'stroke', stroke: '#fbfcfe', 'stroke-width': 3 }, h.nom));
    g.appendChild(svg('text', { y: 57, 'text-anchor': 'middle', 'font-size': 10, fill: '#444', 'paint-order': 'stroke', stroke: '#fbfcfe', 'stroke-width': 3 }, h.type === 'pirate' ? '' : h.ip));
    if (h.redemarre) { g.appendChild(svg('circle', { cx: 24, cy: -24, r: 10, fill: '#2e7d32', stroke: '#fff', 'stroke-width': 2 })); g.appendChild(svg('text', { x: 24, y: -20, 'text-anchor': 'middle', 'font-size': 12, 'font-weight': 700, fill: '#fff' }, '✓')); }
    else if (h.corrompu) { g.appendChild(svg('circle', { cx: 24, cy: -24, r: 10, fill: '#d32f2f', stroke: '#fff', 'stroke-width': 2 })); g.appendChild(svg('text', { x: 24, y: -20, 'text-anchor': 'middle', 'font-size': 13, 'font-weight': 700, fill: '#fff' }, '!')); }
    g.appendChild(svg('rect', { x: -46, y: -40, width: 92, height: 98, fill: 'transparent' }));
    g.onclick = () => { ptSel = h.id; ptDialogOuvrir(); ptDessiner(); };
    s.appendChild(g);
  }
  s.appendChild(couche || svg('g', { id: 'pt-paquets' }));
}

/* --- La fenêtre d'un équipement : Physical, Config, CLI --- */
function ptDialogOuvrir() { ptDialogOnglet = 'config'; $('pt-dialog').style.display = 'block'; ptDialogDessiner(); }

function ptDialogDessiner() {
  const h = ptDonnees.hotes.find(x => x.id === ptSel), z = $('pt-dialog');
  if (!h) return;
  z.innerHTML = '';
  const tete = el('div', 'tete'); tete.appendChild(el('span', '', h.nom));
  const x = el('button', '', '✕'); x.onclick = () => { z.style.display = 'none'; ptSel = null; ptDessiner(); }; tete.appendChild(x);
  const onglets = el('div', 'onglets');
  for (const [id, nom] of [['phys', 'Physical'], ['config', 'Config'], ['cli', 'CLI']]) {
    const b = el('button', id === ptDialogOnglet ? 'on' : '', nom);
    b.onclick = () => { ptDialogOnglet = id; ptDialogDessiner(); };
    onglets.appendChild(b);
  }
  const corps = el('div', 'corps');
  if (ptDialogOnglet === 'phys') corps.appendChild(el('div', 'pt-phys', 'Vue de face de ' + h.nom + ' : ' + (Object.keys(h.ports).length) + ' service(s) actif(s)'));
  else if (ptDialogOnglet === 'config') ptConfig(corps, h);
  else ptCLI(corps, h);
  z.append(tete, onglets, corps);
}

function ptMac(h) { let n = 0; for (const c of h.ip) n = (n * 31 + c.charCodeAt(0)) >>> 0; const hx = n.toString(16).padStart(8, '0'); return '00E0.' + hx.slice(0, 4).toUpperCase() + '.' + hx.slice(4).toUpperCase(); }

function ptConfig(c, h) {
  c.appendChild(el('b', '', 'GLOBAL / Paramètres'));
  const champ = (n, v) => { const d = el('div', 'champ'); const i = el('input'); i.value = v; i.readOnly = true; d.append(el('span', '', n), i); c.appendChild(d); };
  champ('Nom affiché', h.nom);
  if (h.type === 'pirate') { champ('Adresse IP', h.ip + ' (bloquée par le pare-feu)'); return; }
  c.appendChild(el('div', '', '')); c.appendChild(el('b', '', 'INTERFACE / FastEthernet0'));
  champ('État', 'Up (liaison active)'); champ('Adresse IP', h.ip); champ('Masque', '255.255.255.0'); champ('Passerelle', '192.168.1.1'); champ('Adresse MAC', ptMac(h));
  c.appendChild(el('div', '')); c.appendChild(el('b', '', 'SERVICES / Ports en écoute'));
  const t = el('table'); const th = el('tr'); for (const x of ['Port', 'Service', 'État']) th.appendChild(el('th', '', x)); t.appendChild(th);
  const ports = Object.entries(h.ports);
  if (!ports.length) { const r = el('tr'); const td = el('td', '', 'Aucun port ouvert'); td.colSpan = 3; r.appendChild(td); t.appendChild(r); }
  for (const [p, n] of ports) { const r = el('tr'); r.append(el('td', '', p), el('td', '', n), el('td', '', 'Listening')); t.appendChild(r); }
  c.appendChild(t);
  c.appendChild(el('div', 'petit', ''));
  const note = el('div'); note.style.cssText = 'margin-top:8px;color:#557;font-size:11px'; note.textContent = 'Comparez avec l\'inventaire du rapport d\'audit (Documents > Rapports). Pour agir : onglet CLI.';
  c.appendChild(note);
}

const cliHistoriques = {};
function ptCLI(c, h) {
  const cli = el('div', 'pt-cli');
  const h0 = cliHistoriques[h.id] || (cliHistoriques[h.id] = [h.nom + ' - console. Tapez help pour la liste des commandes.']);
  const sortie = el('div');
  for (const l of h0) sortie.appendChild(el('div', '', l));
  const invite = el('div', 'invite'); invite.append(el('span', '', h.nom + '> '));
  const inp = el('input'); inp.spellcheck = false; inp.autocomplete = 'off'; invite.appendChild(inp);
  cli.append(sortie, invite); c.appendChild(cli);
  inp.addEventListener('keydown', async e => {
    if (e.key !== 'Enter') return;
    const ligne = inp.value.trim(); inp.value = '';
    if (!ligne) return;
    h0.push(h.nom + '> ' + ligne);
    if (ligne.toLowerCase() === 'clear') { h0.length = 0; ptDialogDessiner(); return; }
    try {
      const d = await action('cli', { hote: h.id, ligne });
      if (d.sortie) h0.push(d.sortie);
      appliquerEtat(d);
    } catch (err) { h0.push('Erreur de connexion.'); }
    ptSig = ''; chargerReseau();
    ptDialogDessiner();
  });
  setTimeout(() => { inp.focus(); cli.scrollTop = cli.scrollHeight; }, 20);
  cli.onclick = () => inp.focus();
}

/* --- Mode simulation : on voit passer les paquets --- */
function ptSimulation(actif) {
  ptMode = actif ? 'sim' : 'realtime';
  $('pt-simulation').classList.toggle('on', actif); $('pt-realtime').classList.toggle('on', !actif);
  $('pt-sim').style.display = actif ? 'flex' : 'none';
  clearInterval(ptPaquetTimer);
  if (actif) { ptPaquetTimer = setInterval(ptPaquet, 1300); ptPaquet(); }
  else { const c = $('reseau-svg').querySelector('#pt-paquets'); if (c) c.innerHTML = ''; }
}

function ptChemin(de, vers) {
  const adj = {};
  for (const l of ptDonnees.liens) { (adj[l.de] = adj[l.de] || []).push(l.vers); (adj[l.vers] = adj[l.vers] || []).push(l.de); }
  const prec = { [de]: null }, file = [de];
  while (file.length) { const x = file.shift(); if (x === vers) break; for (const y of adj[x] || []) if (!(y in prec)) { prec[y] = x; file.push(y); } }
  if (!(vers in prec)) return [de, vers];
  const chemin = []; for (let x = vers; x !== null; x = prec[x]) chemin.unshift(x);
  return chemin;
}

function ptPaquet() {
  if (!ptDonnees || !ptDonnees.flux.length || !$('reseau-svg')) return;
  const f = ptDonnees.flux[Math.floor(Math.random() * ptDonnees.flux.length)];
  const H = Object.fromEntries(ptDonnees.hotes.map(h => [h.id, h]));
  const chemin = ptChemin(f.de, f.vers).map(id => H[id]);
  const suspect = f.vers === 'pirate';
  const couche = $('reseau-svg').querySelector('#pt-paquets'); if (!couche) return;
  const g = svg('g', { class: 'paquet' });
  g.appendChild(svg('rect', { x: -9, y: -6, width: 18, height: 12, rx: 1.5, fill: suspect ? '#d32f2f' : '#2b8be0', stroke: '#fff', 'stroke-width': 1.5 }));
  g.appendChild(svg('path', { d: 'M-9,-6 L0,1 L9,-6', fill: 'none', stroke: '#fff', 'stroke-width': 1.2 }));
  const anim = svg('animateMotion', { path: 'M' + chemin.map(p => p.x + ',' + p.y).join(' L'), dur: (0.9 * (chemin.length - 1) + 0.6) + 's', fill: 'freeze' });
  g.appendChild(anim); couche.appendChild(g);
  setTimeout(() => g.remove(), (0.9 * (chemin.length - 1) + 0.9) * 1000);
  ptEvents.unshift({ t: ptSecondes, de: H[f.de].nom, vers: H[f.vers].nom, type: 'TCP :' + f.port + (suspect ? ' ⚠' : ''), suspect });
  ptEvents = ptEvents.slice(0, 14);
  const tb = $('pt-events'); tb.innerHTML = '';
  for (const e of ptEvents) {
    const r = el('tr', e.suspect ? 'suspect' : ''); r.append(el('td', '', '0.' + String(e.t).padStart(3, '0')), el('td', '', e.de), el('td', '', e.vers), el('td', '', e.type)); tb.appendChild(r);
  }
}
