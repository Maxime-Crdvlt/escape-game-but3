/* app.js - le poste de travail de Kovac : connexion en deux étapes, fenêtres, jauge, chrono, panneau Mission, indices,
   terminal, messagerie, alarme et écrans de fin. Les applications (documents, photos, navigateur, calculatrice,
   baie serveurs, carte réseau, éditeur) sont dans js/apps/, un fichier par application.
   Le serveur (api.php) décide de tout : ce fichier affiche l'état qu'il reçoit et envoie les actions du joueur. */
const $ = id => document.getElementById(id);
const NS_SVG = 'http://www.w3.org/2000/svg';

function el(tag, cls, text) {
  const e = document.createElement(tag);
  if (cls) e.className = cls;
  if (text !== undefined) e.textContent = text;
  return e;
}
function svg(tag, attrs, text) {
  const e = document.createElementNS(NS_SVG, tag);
  for (const k in attrs) e.setAttribute(k, attrs[k]);
  if (text !== undefined) e.textContent = text;
  return e;
}
/* Le stockage du navigateur peut être indisponible (navigation privée) : on ne s'en sert que pour confort. */
const memo = {
  lire(k, def) { try { return JSON.parse(localStorage.getItem(k)) ?? def; } catch (e) { return def; } },
  ecrire(k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) { /* tant pis */ } },
};

async function api(body) {
  const r = await fetch('api.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
  return r.json();
}
const commande = command => api({ command });
const action = (a, extra = {}) => api({ action: a, ...extra });

/* ====================================================================== */
/*  ÉTAT DE LA PARTIE (reçu du serveur) et réactions aux changements       */
/* ====================================================================== */
const S_LOGIN = 1, S_2FA = 2, S_IP = 3, S_PIVOT = 4, S_SERVEURS = 5, S_SUSPECT = 6, S_AUTH = 7, S_ALARME = 8, S_SCRIPT = 9;
const NOMS_SERVEURS = { routeur: 'ROUTEUR-DGSI', bd: 'SRV-BD', archives: 'SRV-ARCHIVES', web: 'SRV-WEB' };
let S = { step: -1, phase: 'attente', leak: 0, restant: null, serveurs: [], nb_mails: 0, nb_docs: 0, logged: false, pass_ok: false };
let restantRef = { valeur: null, t: 0 };
let bureauOuvert = false;

function appliquerEtat(d) {
  if (!d || d.leak === undefined) return;
  const avant = S;
  S = { ...S, ...d };
  const premiere = avant.step === -1;

  if (!premiere && bureauOuvert && avant.step > 0 && d.step === 0 && d.phase === 'attente') { location.reload(); return; }   // nouvelle partie
  if (d.logged && !bureauOuvert) ouvrirBureau(false);
  if (!d.logged && d.pass_ok && !bureauOuvert) afficherEtape2FA();

  majJauge();
  restantRef = { valeur: d.restant, t: Date.now() };
  majChrono();
  $('prompt') && ($('prompt').textContent = d.prompt || 'C:\\Users\\Kovac>');
  majIcones();
  if (typeof majAffichageCoffre === 'function') majAffichageCoffre();
  $('alarme').style.display = d.phase === 'alarme' ? 'block' : 'none';
  if (bureauOuvert) majMission(!premiere && d.step !== avant.step);

  if (bureauOuvert && !premiere) {
    if (d.step > avant.step) etapeFranchie(d.step);
    for (const s of d.serveurs || []) {
      if (!(avant.serveurs || []).includes(s) && d.step <= S_PIVOT && !fenetres.baie.ouverte) toast('Serveur redémarré', NOMS_SERVEURS[s] + ' est de nouveau en ligne (' + d.serveurs.length + '/4).', 'ok');
    }
    if (avant.serveurs && d.serveurs.length < avant.serveurs.length && d.step === S_PIVOT && !fenetres.baie.ouverte) toast('Mauvais ordre !', 'Un serveur a été redémarré trop tôt : la séquence repart de zéro (+3 %).', 'alerte');
  }
  if (d.nb_mails !== avant.nb_mails && bureauOuvert) chargerMails(!premiere && d.nb_mails > avant.nb_mails);
  if (d.nb_docs !== avant.nb_docs && bureauOuvert) chargerDocuments(!premiere && d.nb_docs > avant.nb_docs);
  if (bureauOuvert && (d.phase === 'victoire' || d.phase === 'defaite')) ecranDeFin(d);
  else $('fin').style.display = 'none';
}

function etapeFranchie(step) {
  if (step === S_IP) toast('Carte réseau déverrouillée', 'Le pirate garde un relais dans le réseau. Ouvrez la Carte réseau (cliquez sur une machine), ou tapez scan et netstat.', 'ok');
  else if (step === S_PIVOT) { toast('Relais coupé', 'Les 4 serveurs sont corrompus : l\'application Baie serveurs s\'ouvre, redémarrez-les avec les boutons de la baie.', 'ok'); fenetres.baie.ouvrir(); }
  else if (step === S_SERVEURS) toast('Base de données accessible', 'Tapez bd dans le terminal. Le guide SQL est dans Documents.', 'ok');
  else if (step === S_AUTH) { toast('Superviseur authentifié', 'Le code 4117 est validé. L\'application Coffre d\'urgence s\'ouvre pour déverrouiller la clé USB.', 'ok'); fenetres.coffre.ouvrir(); }
  else if (step === S_ALARME) toast('ALARME GÉNÉRALE', 'La procédure d\'arrêt est corrompue : ouvrez urgence.sh dans l\'éditeur.', 'alerte');
  else if (step === S_SCRIPT) toast('Kill switch armé', 'La procédure est réparée. Appuyez sur le bouton ARRÊT de la baie !', 'ok');
}

function majJauge() {
  const p = S.leak;
  $('leak-fill').style.width = p + '%';
  $('leak-pct').textContent = p + '%';
  $('leak-fill').style.backgroundColor = p >= 100 ? '#ff0000' : p > 60 ? '#ff9900' : '#16c60c';
}

function formatTemps(s) { return String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0'); }

function majChrono() {
  const c = $('chrono');
  if (restantRef.valeur === null || restantRef.valeur === undefined) { c.textContent = '--:--'; c.classList.remove('urgent'); return; }
  const reste = Math.max(0, restantRef.valeur - Math.floor((Date.now() - restantRef.t) / 1000));
  c.textContent = formatTemps(reste);
  c.classList.toggle('urgent', reste <= 300);
}
setInterval(majChrono, 1000);

/* ====================================================================== */
/*  CONNEXION : mot de passe, puis code du téléphone                       */
/* ====================================================================== */
const erreurLogin = msg => { $('login-error').textContent = msg; $('login-error').style.display = 'block'; };

function afficherEtape2FA() {
  $('stage-pass').style.display = 'none';
  $('stage-2fa').style.display = 'flex';
  $('login-error').style.display = 'none';
  $('win-2fa').focus();
}

async function verifierMotDePasse() {
  const pwd = $('win-password').value.trim().replace(/\s+/g, '');
  $('win-password').value = '';
  if (!pwd) return;
  try {
    const d = await commande('login ' + pwd);
    if (d.pass_ok) { appliquerEtat(d); if (!d.logged) afficherEtape2FA(); }
    else erreurLogin('Mot de passe incorrect. Indice : Cadre photo sur le bureau.');
  } catch (e) { erreurLogin('Serveur local injoignable (PHP lancé ?).'); }
}

async function verifierCode() {
  const code = $('win-2fa').value.replace(/\D/g, '');
  $('win-2fa').value = '';
  if (code.length !== 6) { erreurLogin('Le code comporte 6 chiffres.'); return; }
  try {
    const d = await commande('2fa ' + code);
    if (d.logged) {
      appliquerEtat(d);   // ouvre le bureau
      document.documentElement.requestFullscreen?.().catch(() => {});
      fenetres.terminal.ouvrir();
      terminalEcrire(d.message, d.status);
    } else { appliquerEtat(d); erreurLogin('Code incorrect ou expiré : il change toutes les 30 secondes. +3 % de fuite.'); }
  } catch (e) { erreurLogin('Serveur local injoignable (PHP lancé ?).'); }
}
$('login-btn').addEventListener('click', verifierMotDePasse);
$('win-password').addEventListener('keydown', e => { if (e.key === 'Enter') verifierMotDePasse(); });
$('fa-btn').addEventListener('click', verifierCode);
$('win-2fa').addEventListener('keydown', e => { if (e.key === 'Enter') verifierCode(); });

function ouvrirBureau(pleinEcran) {
  if (bureauOuvert) return;
  bureauOuvert = true;
  $('login-screen').style.display = 'none';
  $('desktop').style.display = 'block';
  if (pleinEcran) document.documentElement.requestFullscreen?.().catch(() => {});
  construireBureau();
}

/* ====================================================================== */
/*  NOTIFICATIONS                                                          */
/* ====================================================================== */
/* Une notification reste 18 secondes (60 pour un indice), s'arrête de compter tant qu'on la survole, et se ferme d'un clic. */
function toast(titre, texte, type = '', duree = 18000) {
  const t = el('div', 'toast ' + type);
  t.append(el('b', '', titre), document.createTextNode(texte));
  t.onclick = () => t.remove();
  let minuteur;
  const lancer = () => { minuteur = setTimeout(() => t.remove(), duree); };
  t.onmouseenter = () => clearTimeout(minuteur);
  t.onmouseleave = lancer;
  $('toasts').appendChild(t);
  while ($('toasts').children.length > 5) $('toasts').firstChild.remove();
  lancer();
}

/* ====================================================================== */
/*  GESTIONNAIRE DE FENÊTRES                                               */
/* ====================================================================== */
const fenetres = {};
let zMax = 10;

function creerFenetre(id, titre, { x, y, w, h }, corps) {
  const win = el('div', 'win');
  win.style.cssText = `left:${x}px; top:${y}px; width:${w}px; height:${h}px; display:none`;
  const entete = el('div', 'win-h');
  entete.appendChild(el('span', '', titre));
  const btns = el('div', 'btns');
  const bMin = el('button', '', '─'), bFermer = el('button', 'x', '✕');
  btns.append(bMin, bFermer); entete.appendChild(btns);
  const body = el('div', 'win-b'); body.appendChild(corps);
  win.append(entete, body);
  $('fenetres').appendChild(win);

  const tache = el('button', 'tache', titre);
  tache.style.display = 'none';
  $('taches').appendChild(tache);

  const f = {
    id, win, tache, ouverte: false, reduite: false, auOuvrir: null,
    ouvrir() {
      f.ouverte = true; f.reduite = false;
      win.style.display = 'flex'; tache.style.display = '';
      f.focus();
      f.auOuvrir?.();
    },
    fermer() { f.ouverte = false; win.style.display = 'none'; tache.style.display = 'none'; majTaches(); },
    reduire() { f.reduite = true; win.style.display = 'none'; majTaches(); },
    focus() { win.style.zIndex = ++zMax; f.reduite = false; win.style.display = 'flex'; majTaches(f.id); },
  };
  fenetres[id] = f;

  win.addEventListener('pointerdown', () => f.focus());
  bFermer.onclick = e => { e.stopPropagation(); f.fermer(); };
  bMin.onclick = e => { e.stopPropagation(); f.reduire(); };
  tache.onclick = () => { if (!f.reduite && win.style.zIndex == zMax) f.reduire(); else f.focus(); };

  entete.addEventListener('pointerdown', e => {
    if (e.target.closest('button')) return;
    const r = win.getBoundingClientRect(), dx = e.clientX - r.left, dy = e.clientY - r.top;
    entete.setPointerCapture(e.pointerId);
    const bouge = ev => {
      win.style.left = Math.max(-r.width + 120, Math.min(innerWidth - 120, ev.clientX - dx)) + 'px';
      win.style.top = Math.max(0, Math.min(innerHeight - 90, ev.clientY - dy)) + 'px';
    };
    const fini = () => { entete.removeEventListener('pointermove', bouge); entete.removeEventListener('pointerup', fini); };
    entete.addEventListener('pointermove', bouge); entete.addEventListener('pointerup', fini);
  });
  return f;
}

function majTaches(actif) {
  for (const f of Object.values(fenetres)) f.tache.classList.toggle('actif', f.id === actif && !f.reduite && f.ouverte);
}

/* ====================================================================== */
/*  BUREAU : icônes et fenêtres                                            */
/* ====================================================================== */
const ICONES = [
  { id: 'terminal', emoji: '⌨️', nom: 'TERMINAL L.E.R.N.A', ouvre: () => fenetres.terminal.ouvrir() },
  { id: 'mail', emoji: '✉️', nom: 'Messagerie', badge: true, ouvre: () => fenetres.mail.ouvrir() },
  { id: 'docs', emoji: '📁', nom: 'Documents', ouvre: () => ouvrirDocuments('Documentation') },
  { id: 'reseau', emoji: '🕸️', nom: 'Carte réseau', minStep: S_IP, raison: 'Module verrouillé : isolez d\'abord la menace (commande cibler).', ouvre: () => fenetres.reseau.ouvrir() },
  { id: 'baie', emoji: '🗄️', nom: 'Baie serveurs', minStep: S_PIVOT, raison: 'Supervision indisponible : coupez d\'abord le relais du pirate.', ouvre: () => fenetres.baie.ouvrir() },
  { id: 'coffre', emoji: '🔐', nom: 'Coffre d\'urgence', minStep: S_SUSPECT, raison: 'Coffre verrouillé : identifiez d\'abord l\'employé piégé.', ouvre: () => fenetres.coffre.ouvrir() },
  { id: 'script', emoji: '📝', nom: 'urgence.sh', minStep: S_ALARME, raison: 'Fichier protégé : il faut d\'abord lancer le script d\'urgence.', ouvre: () => fenetres.script.ouvrir() },
  { id: 'photos', emoji: '🖼️', nom: 'Photos', ouvre: () => fenetres.photos.ouvrir() },
  { id: 'web', emoji: '🌐', nom: 'Navigateur', ouvre: () => fenetres.nav.ouvrir() },
  { id: 'corbeille', emoji: '🗑️', nom: 'Corbeille', ouvre: () => ouvrirDocuments('Corbeille') },
  { id: 'pdf', emoji: '📄', nom: 'Rapport_T3.pdf', ouvre: () => ouvrirDocuments('Rapports', 'rapport-t3') },
  { id: 'calc', emoji: '🧮', nom: 'Calculatrice', ouvre: () => fenetres.calc.ouvrir() },
];

function construireBureau() {
  const grille = $('icones');
  for (const i of ICONES) {
    const d = el('div', 'icon'); d.id = 'ico-' + i.id;
    d.append(el('div', 'emoji', i.emoji), document.createTextNode(i.nom));
    if (i.badge) d.appendChild(el('span', 'badge'));
    d.appendChild(el('span', 'cadenas'));
    d.onclick = () => {
      if (i.minStep && S.step < i.minStep) { toast(i.nom, i.raison, 'alerte'); return; }
      i.ouvre();
    };
    grille.appendChild(d);
  }
  const L = innerWidth, H = innerHeight - 44;
  const geo = (fx, fy, fw, fh) => ({ x: Math.round(L * fx), y: Math.round(H * fy), w: Math.round(L * fw), h: Math.round(H * fh) });
  construireTerminal(geo(.13, .05, .55, .72));
  construireMail(geo(.11, .08, .58, .70));
  construireExplorateur(geo(.12, .07, .60, .72));
  construireReseau(geo(.07, .03, .72, .88));
  construireBaie(geo(.10, .05, .66, .78));
  construireCoffre(geo(.16, .05, .58, .84));
  construireEditeur(geo(.14, .04, .62, .88));
  construireGalerie(geo(.15, .08, .56, .72));
  construireNavigateur(geo(.12, .06, .62, .80));
  construireCalculatrice({ x: Math.round(L * .40), y: Math.round(H * .12), w: 290, h: 400 });
  construireMission();
  $('btn-indice').onclick = demanderIndice;
  horloge(); setInterval(horloge, 10000);
  chargerMails(false); chargerDocuments(false);
  majMission(false);
}

function majIcones() {
  for (const i of ICONES) {
    const d = $('ico-' + i.id);
    if (!d) continue;
    const verrou = i.minStep && S.step < i.minStep;
    d.classList.toggle('verrou', !!verrou);
    d.querySelector('.cadenas').textContent = verrou ? '🔒' : '';
  }
  majBadge();
}

function horloge() { $('horloge').textContent = new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }); }

/* ====================================================================== */
/*  MISSION : le panneau qui dit où on en est et où chercher               */
/* ====================================================================== */
function construireMission() {
  const m = el('div'); m.id = 'mission';
  m.innerHTML = '<div class="m-tete" id="m-tete"><span class="m-num" id="m-num"></span><span class="m-titre" id="m-titre"></span><span class="m-plus" id="m-plus">▾</span></div><div class="m-barre"><div id="m-barre"></div></div><div class="m-corps" id="m-corps"></div>';
  $('desktop').appendChild(m);
  $('m-tete').onclick = () => { m.classList.toggle('replie'); $('m-plus').textContent = m.classList.contains('replie') ? '▸' : '▾'; };
}

/** Les boutons de raccourci du panneau : où aller pour cet objectif. */
function liensMission(step) {
  const l = [];
  const doc = (nom, dossier, id) => l.push([nom, () => ouvrirDocuments(dossier, id)]);
  if (step === 1) l.push(['Messagerie', () => fenetres.mail.ouvrir()]);
  if (step === 2) { l.push(['Terminal', () => fenetres.terminal.ouvrir()]); doc('Procédure d\'incident', 'Documentation', 'procedure'); }
  if (step === 3) { l.push(['Carte réseau', () => fenetres.reseau.ouvrir()]); doc('Rapport T3', 'Rapports', 'rapport-t3'); doc('Guide réseau', 'Documentation', 'guide-reseau'); }
  if (step === 4) { l.push(['Baie serveurs', () => fenetres.baie.ouvrir()]); l.push(['Carte réseau', () => fenetres.reseau.ouvrir()]); l.push(['Photos', () => fenetres.photos.ouvrir()]); }
  if (step === 5 || step === 6) { l.push(['Terminal', () => fenetres.terminal.ouvrir()]); doc('Guide SQL', 'Documentation', 'guide-sql'); }
  if (step === 7) l.push(['Terminal', () => fenetres.terminal.ouvrir()]);
  if (step === 8) { l.push(['Éditeur urgence.sh', () => fenetres.script.ouvrir()]); doc('Guide Python', 'Documentation', 'guide-python'); l.push(['Messagerie', () => fenetres.mail.ouvrir()]); }
  return l;
}

let missionStep = null;
function majMission(signaler) {
  const m = S.mission;
  if (!m || !$('mission')) return;
  const nouveau = missionStep !== m.n;
  missionStep = m.n;
  $('m-num').textContent = 'Objectif ' + m.n + '/' + m.total;
  $('m-titre').textContent = m.titre;
  $('m-barre').style.width = Math.round((m.n - 1) / m.total * 100) + '%';
  if (nouveau) {
    const c = $('m-corps'); c.innerHTML = '';
    const p1 = el('p'); p1.appendChild(el('span', 'lab', 'Contexte')); p1.appendChild(el('br')); p1.appendChild(document.createTextNode(m.contexte));
    const p2 = el('p'); p2.appendChild(el('span', 'lab', 'Où chercher')); p2.appendChild(el('br')); p2.appendChild(document.createTextNode(m.piste));
    c.append(p1, p2);
    const liens = el('div', 'm-liens');
    for (const [nom, fn] of liensMission(S.step)) { const b = el('button', '', '→ ' + nom); b.onclick = fn; liens.appendChild(b); }
    c.appendChild(liens);
    if (signaler) {
      $('mission').classList.remove('replie'); $('m-plus').textContent = '▾';
      $('mission').classList.remove('nouveau'); void $('mission').offsetWidth; $('mission').classList.add('nouveau');
    }
  }
}

/* ====================================================================== */
/*  INDICES                                                                */
/* ====================================================================== */
let confirmerIndice = null;
async function demanderIndice() {
  const b = $('btn-indice');
  if (!confirmerIndice) {
    b.textContent = 'Confirmer : +5 % de fuite';
    confirmerIndice = setTimeout(() => { confirmerIndice = null; b.textContent = '💡 Indice'; }, 3500);
    return;
  }
  clearTimeout(confirmerIndice); confirmerIndice = null; b.textContent = '💡 Indice';
  try {
    const d = await action('indice');
    appliquerEtat(d);
    if (d.texte === null) { toast('Plus d\'indice', 'Vous avez tout ce qu\'il faut pour cet objectif.', 'indice'); return; }
    toast('Indice ' + d.niveau + '/' + d.total + '  (+5 % de fuite)', d.texte, 'indice', 60000);
    terminalEcrire('INDICE ' + d.niveau + '/' + d.total + '\n> ' + d.texte, 'sys-msg');
  } catch (e) { toast('Erreur', 'Serveur injoignable.', 'alerte'); }
}

/* ====================================================================== */
/*  TERMINAL                                                               */
/* ====================================================================== */
const COMMANDES = ['aide', 'statut', 'indice', 'show logs', 'cibler ', 'scan', 'netstat', 'bloquer ', 'bd', 'sql ', 'suspect ', 'auth ', 'run urgence.sh', 'ls', 'cat ', 'ping ', 'whoami', 'date', 'clear', 'reset'];
const historique = []; let hIdx = 0;

function terminalEcrire(texte, classe = '') {
  if (!$('history')) return;
  const l = el('div', 'output-line ' + classe, texte);
  $('history').appendChild(l);
  $('terminal-content').scrollTop = $('terminal-content').scrollHeight;
}

function construireTerminal(geo) {
  const corps = el('div'); corps.style.cssText = 'display:flex; flex:1; min-height:0';
  corps.innerHTML = `<div id="terminal-content" style="flex:1" onclick="document.getElementById('cmd-input').focus()">
      <div class="output-line sys-msg">DGSI - Cellule Zéro-Jour</div>
      <div class="output-line sys-msg">Système L.E.R.N.A v2.4 chargé.</div>
      <div class="output-line">Tapez 'aide' pour voir la liste des commandes, 'statut' pour savoir où vous en êtes. La documentation est dans l'application Documents.</div>
      <div id="history"></div>
      <div id="input-line"><span id="prompt">kovac@lerna:~$</span><input type="text" id="cmd-input" autocomplete="off" spellcheck="false"></div>
    </div>`;
  const f = creerFenetre('terminal', 'Invite de commandes - Système L.E.R.N.A', geo, corps);
  f.auOuvrir = () => setTimeout(() => $('cmd-input').focus(), 30);

  $('cmd-input').addEventListener('keydown', async e => {
    const champ = e.target;
    if (e.key === 'ArrowUp') { if (hIdx > 0) champ.value = historique[--hIdx]; e.preventDefault(); return; }
    if (e.key === 'ArrowDown') { hIdx = Math.min(hIdx + 1, historique.length); champ.value = historique[hIdx] || ''; e.preventDefault(); return; }
    if (e.key === 'Tab') {
      e.preventDefault();
      const v = champ.value.toLowerCase();
      const m = COMMANDES.filter(c => c.startsWith(v));
      if (v && m.length === 1) champ.value = m[0];
      else if (v && m.length > 1) terminalEcrire(m.map(c => c.trim()).join('   '), 'sys-msg');
      return;
    }
    if (e.key !== 'Enter') return;
    const c = champ.value.trim();
    champ.value = '';
    if (!c) return;
    historique.push(c); hIdx = historique.length;
    terminalEcrire(($('prompt').textContent || '>') + ' ' + c, 'cmd-echo');
    if (c.toLowerCase() === 'clear') { $('history').innerHTML = ''; return; }
    try {
      const d = await commande(c);
      if (d.message) terminalEcrire(d.message, d.status);
      appliquerEtat(d);
      if (d.reset) setTimeout(() => location.reload(), 800);
      ptSig = '';   // la carte réseau se met à jour au prochain tour
    } catch (err) { terminalEcrire('Erreur de connexion au serveur PHP.', 'error-msg'); }
  });
}

/* ====================================================================== */
/*  MESSAGERIE                                                             */
/* ====================================================================== */
let MAILS = [], mailSel = null, mailsConnus = null;
const mailsLus = () => new Set(memo.lire('lerna_lus', []));

function construireMail(geo) {
  const corps = el('div', 'mail');
  corps.innerHTML = '<div class="mail-liste" id="mail-liste"></div><div class="mail-lecture" id="mail-lecture"><div class="mail-vide">Sélectionnez un message.</div></div>';
  creerFenetre('mail', 'Messagerie - Adrien Kovac', geo, corps).auOuvrir = () => chargerMails(false);
}

async function chargerMails(notifier) {
  let d;
  try { d = await action('mails'); } catch (e) { return; }
  if (!d.mails) return;
  if (mailsConnus !== null && notifier) {
    for (const m of d.mails) if (!mailsConnus.has(m.id)) toast('Nouveau message : ' + m.de, m.sujet, '');
  }
  MAILS = d.mails;
  mailsConnus = new Set(MAILS.map(m => m.id));
  dessinerMails();
  majBadge();
}

function dessinerMails() {
  const liste = $('mail-liste');
  if (!liste) return;
  const lus = mailsLus();
  liste.innerHTML = '';
  for (const m of MAILS) {
    const it = el('div', 'mail-item' + (lus.has(m.id) ? '' : ' nonlu') + (mailSel === m.id ? ' sel' : ''));
    const de = el('div', 'de', m.de); de.appendChild(el('span', '', m.heure));
    it.append(de, el('div', 'sujet', m.sujet));
    it.onclick = () => lireMail(m.id);
    liste.appendChild(it);
  }
}

function lireMail(id) {
  const m = MAILS.find(x => x.id === id);
  if (!m) return;
  mailSel = id;
  const lus = mailsLus(); lus.add(id); memo.ecrire('lerna_lus', [...lus]);
  const z = $('mail-lecture'); z.innerHTML = '';
  z.append(el('h2', '', m.sujet), el('div', 'meta', 'De : ' + m.de + '   ·   ' + m.heure), el('div', 'corps', m.texte));
  if (m.pj) z.appendChild(el('div', 'pj', '📎 ' + m.pj));
  dessinerMails(); majBadge();
}

function majBadge() {
  const b = document.querySelector('#ico-mail .badge');
  if (!b) return;
  const lus = mailsLus();
  const n = MAILS.filter(m => !lus.has(m.id)).length;
  b.textContent = n; b.style.display = n > 0 ? 'block' : 'none';
}

/* ====================================================================== */
/*  ÉCRANS DE FIN                                                          */
/* ====================================================================== */
const COMPETENCES = ['Sécurité des réseaux', 'Bases de données SQL', 'Programmation Python', 'Systèmes embarqués et IoT', 'Protocole MQTT', 'Travail en équipe'];

function ordinal(n) { return n === 1 ? '1er' : n + 'e'; }

function ecranDeFin(d) {
  const f = $('fin');
  const victoire = d.phase === 'victoire';
  f.className = victoire ? '' : 'defaite';
  f.style.display = 'flex';
  const st = d.stats || { temps: 0, indices: 0, erreurs: 0, penalites: 0, jauge: d.leak, grade: '' };
  const cle = JSON.stringify([d.phase, st]);
  if (f.dataset.cle === cle) return;
  f.dataset.cle = cle;
  f.innerHTML = '';
  const boite = el('div', 'boite');
  boite.append(el('h1', '', victoire ? 'L.E.R.N.A NEUTRALISÉ' : 'FUITE TOTALE'));
  boite.append(el('div', 'grade', victoire ? st.grade : 'Les données ont fuité… mais vous avez appris beaucoup !'));
  const stats = el('div', 'stats');
  for (const [valeur, nom] of [[formatTemps(st.temps), 'temps de jeu'], [st.jauge + ' %', 'fuite finale'], [st.indices, 'indices utilisés'], [st.erreurs ?? 0, 'erreurs']]) {
    const c = el('div', 'stat'); c.append(el('b', '', String(valeur)), el('span', '', nom)); stats.appendChild(c);
  }
  boite.appendChild(stats);
  if (victoire && st.rang) {
    const r = el('div', 'rang');
    r.textContent = 'Classement : ' + ordinal(st.rang) + ' meilleur temps sur ' + st.total + ' partie' + (st.total > 1 ? 's' : '') + ' gagnée' + (st.total > 1 ? 's' : '') +
      (st.meilleur_temps ? ' (record : ' + formatTemps(st.meilleur_temps) + ')' : '');
    boite.appendChild(r);
  }
  const comp = el('div', 'comp'); comp.append(document.createTextNode('Compétences du BUT mobilisées : '));
  for (const c of COMPETENCES) comp.appendChild(el('i', '', c));
  boite.append(comp, el('div', 'rejouer', 'Appuyez sur le bouton DÉMARRAGE de la baie pour une nouvelle partie.'));
  f.appendChild(boite);
}

/* ====================================================================== */
/*  SYNCHRO : jauge, chrono, étapes franchies par les boutons physiques    */
/* ====================================================================== */
async function sync() {
  try { appliquerEtat(await action('etat')); } catch (e) { /* on réessaie au prochain tour */ }
}
(async function demarrer() {
  await sync();
  if (S.step < 2) memo.ecrire('lerna_lus', []);   // nouvelle partie : tous les messages redeviennent non lus
  setInterval(sync, 1500);
})();
