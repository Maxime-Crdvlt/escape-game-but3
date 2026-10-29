/* NAVIGATEUR : l'intranet de la DGSI
   Application du bureau. Utilise les outils de app.js (el, svg, action, toast, creerFenetre, S, appliquerEtat...). */

let navUrl = 'intranet.dgsi.local', navHistorique = [];
const ANNUAIRE = [
  ['A. KOVAC', 'ZERO-JOUR', 'Superviseur de la cellule', '4117'], ['K. DIALLO', 'ZERO-JOUR', 'Analyste', '8891'], ['M. LEFEBVRE', 'IT_SUPPORT', 'Support systèmes', '2199'],
  ['R. GOMEZ', 'SECURITE_PHY', 'Sécurité physique', '6710'], ['E. BLANC', 'AUDIT_INTERNE', 'Auditeur', '9954'], ['L. MARTIN', 'ARCHIVES', 'Archiviste', '5002'],
  ['C. MOREAU', 'DIRECTION', 'Directrice', '3588'], ['J. DUPONT', 'RESSOURCES_HUM', 'Gestionnaire RH', '1042'],
];

function construireNavigateur(geo) {
  const corps = el('div', 'nav');
  corps.innerHTML = '<div class="nav-barre"><button id="nav-ret" title="Retour">←</button><button id="nav-acc" title="Accueil">⌂</button><input id="nav-url" spellcheck="false" autocomplete="off"></div><div class="nav-page" id="nav-page"></div>';
  creerFenetre('nav', 'Navigateur DGSI', geo, corps).auOuvrir = () => { chargerDocuments(false); navAller(navUrl, false); };
  $('nav-ret').onclick = () => { if (navHistorique.length) navAller(navHistorique.pop(), false); };
  $('nav-acc').onclick = () => navAller('intranet.dgsi.local');
  $('nav-url').addEventListener('keydown', e => { if (e.key === 'Enter') navAller(e.target.value.trim()); });
}

function navAller(url, empiler = true) {
  url = url.replace(/^https?:\/\//, '').replace(/\/$/, '') || 'intranet.dgsi.local';
  if (empiler && url !== navUrl) navHistorique.push(navUrl);
  navUrl = url;
  $('nav-url').value = url;
  navMaj();
}

function navMaj() {
  const page = $('nav-page');
  if (!page) return;
  page.innerHTML = '';
  const hote = 'intranet.dgsi.local';
  const entete = (titre, sous) => { const e = el('div', 'nav-entete'); e.append(el('h1', '', titre), el('div', '', sous || 'Intranet de la DGSI · accès interne uniquement')); page.appendChild(e); const c = el('div', 'nav-contenu'); page.appendChild(c); return c; };
  const lien = (texte, cible) => { const a = el('a', '', texte); a.onclick = () => navAller(cible); return a; };

  if (!navUrl.startsWith(hote)) {
    const c = entete('Accès bloqué', 'Proxy DGSI');
    c.append(el('p', '', 'Le site « ' + navUrl + ' » n\'est pas accessible depuis ce poste : seul l\'intranet est autorisé.'), lien('Retour à l\'intranet', hote));
    return;
  }
  const chemin = navUrl.slice(hote.length) || '/';
  if (chemin === '/') {
    const c = entete('Intranet DGSI', 'Bienvenue, Adrien Kovac');
    c.append(el('h2', '', 'Accès rapides'));
    const ul = el('ul');
    for (const [t, u] of [['Wiki : procédures et guides', hote + '/wiki'], ['Ticket de l\'incident en cours', hote + '/tickets'], ['Annuaire du personnel', hote + '/annuaire']]) { const li = el('li'); li.appendChild(lien(t, u)); ul.appendChild(li); }
    c.appendChild(ul);
    c.append(el('h2', '', 'Actualités'));
    for (const [t, x] of [['Rappel sécurité', 'Tout port ouvert qui ne figure pas dans l\'inventaire d\'audit doit être fermé immédiatement (règle n°4).'],
      ['Cafétéria', 'Jeudi, c\'est le jour du burger.'], ['Formation', 'Les modules RGPD sont à terminer avant la fin du mois.']]) {
      const k = el('div', 'carte'); k.append(el('b', '', t), el('div', '', x)); c.appendChild(k);
    }
  } else if (chemin === '/wiki' || chemin.startsWith('/wiki/')) {
    const id = chemin.slice(6);
    const doc = DOCS.find(d => d.id === id);
    if (doc) {
      const c = entete(doc.titre, 'Wiki DGSI · ' + doc.dossier);
      c.append(lien('← Tous les articles', hote + '/wiki'), el('pre', '', doc.texte));
    } else {
      const c = entete('Wiki DGSI', 'Procédures et guides');
      const ul = el('ul');
      for (const d of DOCS.filter(x => x.dossier !== 'Corbeille')) { const li = el('li'); li.appendChild(lien(d.titre, hote + '/wiki/' + d.id)); ul.appendChild(li); }
      c.appendChild(ul);
    }
  } else if (chemin === '/tickets') {
    const c = entete('INC-2026-0412', 'Fuite de données L.E.R.N.A. · priorité critique');
    c.append(el('p', '', 'Statut : en cours. Ticket ouvert par la cellule Zéro-Jour.'), el('h2', '', 'Avancement de la résolution'));
    const noms = ['Accéder au poste de Kovac', 'Authentification à deux facteurs', 'Repérer et bloquer l\'IP du pirate', 'Couper le relais dans le réseau', 'Redémarrer les serveurs (baie)',
      'Identifier l\'employé piégé', 'Identifier le superviseur d\'urgence', 'Lancer le script d\'urgence', 'Réparer la procédure d\'arrêt', 'Couper l\'alarme (kill switch)'];
    const t = el('table');
    noms.forEach((n, i) => {
      const r = el('tr'); const etat = i < S.step ? ['✔ fait', 'fait'] : i === S.step ? ['▶ en cours', 'encours'] : ['… à venir', 'attente'];
      r.append(el('td', '', String(i + 1)), el('td', '', i <= S.step ? n : '???'), el('td', etat[1], etat[0])); t.appendChild(r);
    });
    c.appendChild(t);
  } else if (chemin === '/annuaire') {
    const c = entete('Annuaire du personnel', 'DGSI');
    const t = el('table'); const h = el('tr'); for (const x of ['Nom', 'Service', 'Fonction', 'Matricule']) h.appendChild(el('th', '', x)); t.appendChild(h);
    for (const l of ANNUAIRE) { const r = el('tr'); for (const x of l) r.appendChild(el('td', '', x)); t.appendChild(r); }
    c.appendChild(t);
  } else {
    const c = entete('Page introuvable', '404');
    c.append(el('p', '', 'Cette page n\'existe pas.'), lien('Retour à l\'accueil', hote));
  }
}
