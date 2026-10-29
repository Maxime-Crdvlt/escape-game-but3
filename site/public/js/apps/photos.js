/* PHOTOS : les images sont des scènes dessinées en SVG
   Application du bureau. Utilise les outils de app.js (el, svg, action, toast, creerFenetre, S, appliquerEtat...). */

const POLICE_MARQUEUR = "font-family=\"'Segoe Print','Comic Sans MS',cursive\"";

const SCENES = {
  tableau: () => `<svg viewBox="0 0 400 260" xmlns="http://www.w3.org/2000/svg">
    <rect width="400" height="260" fill="#cfd8dc"/><rect x="18" y="16" width="364" height="206" rx="4" fill="#fafafa" stroke="#90a4ae" stroke-width="9"/>
    <text x="36" y="50" font-size="21" ${POLICE_MARQUEUR} font-weight="bold" fill="#c62828">INCIDENT LERNA</text>
    <line x1="36" y1="57" x2="230" y2="57" stroke="#c62828" stroke-width="2"/>
    <g font-size="13.5" ${POLICE_MARQUEUR} fill="#1a237e">
      <text x="36" y="86">1  isoler l'IP du pirate</text><text x="36" y="110">2  trouver le relais : les PORTS !</text>
      <text x="36" y="134">3  redémarrer : dépendances d'abord</text><text x="36" y="158">4  base du personnel : SQL</text>
      <text x="36" y="182">5  superviseur → run urgence.sh</text><text x="36" y="206" fill="#b71c1c">6  réparer le script, puis ARRÊT</text></g>
    <rect x="140" y="226" width="120" height="9" rx="3" fill="#78909c"/><rect x="170" y="222" width="22" height="5" fill="#c62828"/><rect x="200" y="222" width="22" height="5" fill="#1a237e"/></svg>`,

  baie: () => {
    const unites = [['SRV-WEB', 1], ['SRV-ARCHIVES', 2], ['SRV-BD', 3], ['ROUTEUR-DGSI', 4]];
    let s = '<svg viewBox="0 0 400 260" xmlns="http://www.w3.org/2000/svg"><rect width="400" height="260" fill="#1b2128"/><rect x="24" y="10" width="352" height="240" rx="6" fill="#2c3641" stroke="#55626f" stroke-width="3"/>';
    unites.forEach(([nom, n], i) => {
      const y = 24 + i * 44;
      s += `<rect x="38" y="${y}" width="324" height="36" rx="3" fill="#38444f" stroke="#5d6b7a"/><circle cx="54" cy="${y + 18}" r="5" fill="#2fd36b"/>
        <text x="72" y="${y + 23}" font-size="14" font-family="Consolas,monospace" font-weight="bold" fill="#e8eef5">${nom}</text>
        <circle cx="334" cy="${y + 18}" r="13" fill="#111" stroke="#9fb4cb" stroke-width="2"/><text x="334" y="${y + 24}" font-size="17" font-family="Segoe UI,sans-serif" font-weight="bold" text-anchor="middle" fill="#fff">${n}</text>
        <text x="296" y="${y + 22}" font-size="9" text-anchor="end" fill="#8fa3b8" font-family="Segoe UI,sans-serif">BOUTON</text>`;
    });
    s += `<circle cx="120" cy="222" r="14" fill="#b71c1c" stroke="#fff" stroke-width="2"/><text x="120" y="226" font-size="8" text-anchor="middle" fill="#fff" font-weight="bold">ARRÊT</text>
      <circle cx="170" cy="222" r="12" fill="#2e7d32" stroke="#fff" stroke-width="2"/><text x="170" y="225" font-size="6.5" text-anchor="middle" fill="#fff" font-weight="bold">DÉMARR.</text>
      <text x="320" y="226" font-size="10" text-anchor="end" fill="#8fa3b8" font-family="Segoe UI,sans-serif">Baie A · salle serveurs</text></svg>`;
    return s;
  },

  postit: () => `<svg viewBox="0 0 400 260" xmlns="http://www.w3.org/2000/svg">
    <rect width="400" height="260" fill="#8d6e63"/><rect x="0" y="0" width="400" height="150" fill="#263238"/><rect x="20" y="14" width="360" height="124" rx="4" fill="#0b1117" stroke="#455a64" stroke-width="4"/>
    <text x="40" y="60" font-size="12" fill="#3ddc84" font-family="Consolas,monospace">&gt; sql SELECT ...</text><text x="40" y="82" font-size="12" fill="#3ddc84" font-family="Consolas,monospace">_</text>
    <g transform="rotate(-4 250 130)"><rect x="190" y="70" width="170" height="150" fill="#ffe66d" stroke="#d9b800"/><rect x="190" y="70" width="170" height="14" fill="#f5d33c"/>
    <g font-size="12" ${POLICE_MARQUEUR} fill="#33290a"><text x="200" y="106">Superviseur d'urgence ?</text><text x="200" y="128">= le droit</text><text x="200" y="148" font-weight="bold">URGENCE_PHYSIQUE</text>
    <text x="200" y="172">ET le bon service :</text><text x="200" y="192" font-weight="bold">ZERO-JOUR</text><text x="200" y="210">(pas la Direction !)</text></g></g></svg>`,

  gateau: () => `<svg viewBox="0 0 400 260" xmlns="http://www.w3.org/2000/svg">
    <rect width="400" height="260" fill="#fce4ec"/><ellipse cx="200" cy="205" rx="150" ry="22" fill="#f8bbd0"/>
    <rect x="95" y="120" width="210" height="78" rx="8" fill="#8d5a3b"/><rect x="95" y="106" width="210" height="34" rx="14" fill="#fff3e0"/>
    <g fill="#ffb300"><rect x="140" y="72" width="8" height="36"/><rect x="196" y="66" width="8" height="42"/><rect x="252" y="72" width="8" height="36"/></g>
    <g fill="#ff7043"><ellipse cx="144" cy="64" rx="6" ry="9"/><ellipse cx="200" cy="58" rx="6" ry="9"/><ellipse cx="256" cy="64" rx="6" ry="9"/></g>
    <text x="200" y="168" font-size="22" text-anchor="middle" fill="#fff8e1" ${POLICE_MARQUEUR} font-weight="bold">07/12</text>
    <text x="200" y="40" font-size="20" text-anchor="middle" fill="#ad1457" ${POLICE_MARQUEUR}>Joyeux anniversaire Adrien !</text>
    <text x="200" y="246" font-size="11" text-anchor="middle" fill="#880e4f" font-family="Segoe UI,sans-serif">cafétéria · 15 h</text></svg>`,

  vacances: () => `<svg viewBox="0 0 400 260" xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="ciel" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#4fc3f7"/><stop offset="1" stop-color="#e1f5fe"/></linearGradient></defs>
    <rect width="400" height="260" fill="url(#ciel)"/><circle cx="320" cy="60" r="28" fill="#ffd54f"/><rect y="150" width="400" height="110" fill="#0288d1"/><rect y="150" width="400" height="6" fill="#81d4fa"/>
    <path d="M60 200 h110 l-16 24 h-78 z" fill="#8d6e63"/><rect x="113" y="96" width="4" height="104" fill="#5d4037"/><path d="M117 100 v84 h-54 z" fill="#fff"/><path d="M121 120 v64 h40 z" fill="#ef5350"/>
    <path d="M0 236 q50 -20 100 0 t100 0 t100 0 t100 0 v24 h-400 z" fill="#01579b" opacity=".6"/></svg>`,
};

function construireGalerie(geo) {
  const corps = el('div', 'photos');
  corps.innerHTML = '<div class="photos-grille" id="photos-grille"></div><div class="photos-vue" id="photos-vue"></div>';
  creerFenetre('photos', 'Photos', geo, corps).auOuvrir = () => { chargerDocuments(false); photoRetour(); };
}

function dessinerGalerie() {
  const g = $('photos-grille');
  if (!g) return;
  g.innerHTML = '';
  for (const p of PHOTOS) {
    const c = el('div', 'photo');
    c.innerHTML = (SCENES[p.id] ? SCENES[p.id]() : '');
    const leg = el('div', 'leg', p.titre); leg.appendChild(el('span', '', p.date));
    c.appendChild(leg);
    c.onclick = () => photoOuvrir(p);
    g.appendChild(c);
  }
}

function photoOuvrir(p) {
  const v = $('photos-vue');
  v.innerHTML = '';
  const b = el('button', '', '← Retour'); b.onclick = photoRetour;
  const w = el('div'); w.style.cssText = 'width:100%; display:flex; justify-content:center'; w.innerHTML = SCENES[p.id]();
  const leg = el('div', 'leg', p.titre); leg.appendChild(el('div', '', p.date));
  v.append(b, w, leg);
  v.style.display = 'flex'; $('photos-grille').style.display = 'none';
}
function photoRetour() { if ($('photos-vue')) { $('photos-vue').style.display = 'none'; $('photos-grille').style.display = 'grid'; } }
