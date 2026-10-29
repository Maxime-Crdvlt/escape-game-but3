/* BAIE SERVEURS : la supervision du redémarrage
   Application du bureau. Utilise les outils de app.js (el, svg, action, toast, creerFenetre, S, appliquerEtat...). */

let baieDonnees = null, baieTimer = null, baieAnim = {}, baieDernierJournal = null, baieBanniere = null;
const BAIE_DUREE_BOOT = 2400;

function construireBaie(geo) {
  const corps = el('div', 'baie');
  corps.innerHTML = `<div class="baie-haut"><div><b>Supervision de la baie</b> · salle serveurs A</div><span id="baie-compteur">0/4 serveurs en ligne</span></div>
    <div class="baie-corps"><div class="baie-rack" id="baie-rack"></div>
      <div class="baie-droite"><div class="baie-banniere" id="baie-banniere">En attente de la première commande…</div>
        <pre class="baie-journal" id="baie-journal"></pre>
        <div class="baie-aide"><b>Comment faire ?</b><br>Appuyez sur les <b>boutons physiques</b> de la baie, dans la salle. Un serveur ne démarre qu'<b>après ceux dont il dépend</b> (flèches jaunes de la carte réseau). En cas d'erreur, la séquence repart de zéro et le journal indique ce qui manquait.<div id="baie-deps" style="margin-top:6px"></div></div></div></div>`;
  const f = creerFenetre('baie', 'Baie serveurs - Supervision', geo, corps);
  f.auOuvrir = () => { chargerBaie(); clearInterval(baieTimer); baieTimer = setInterval(() => { if (f.ouverte && !f.reduite) chargerBaie(); else if (!f.ouverte) clearInterval(baieTimer); }, 1200); };
  setInterval(() => { if (f.ouverte && !f.reduite) baieDessiner(); }, 200);
}

async function chargerBaie() {
  let d;
  try { d = await action('baie'); } catch (e) { return; }
  if (d.erreur) { $('baie-banniere').className = 'baie-banniere erreur'; $('baie-banniere').textContent = d.erreur; return; }
  const avant = baieDonnees;
  baieDonnees = d;
  if (avant) for (const s of d.serveurs) { const a = avant.serveurs.find(x => x.id === s.id); if (s.en_ligne && a && !a.en_ligne) baieAnim[s.id] = Date.now(); }
  const dernier = d.journal.length ? d.journal[d.journal.length - 1] : null;
  const cle = dernier ? dernier.ts + dernier.texte : null;
  if (avant && dernier && cle !== baieDernierJournal) {
    if (dernier.type === 'erreur') baieErreur(dernier.texte);
    else if (dernier.texte.startsWith('Les 4')) baieMessage('Réseau rétabli : les 4 serveurs sont en ligne.', 'ok');
    else baieMessage(dernier.texte, 'ok');
  }
  baieDernierJournal = cle;
  baieDessiner();
}

function baieMessage(texte, type) {
  const b = $('baie-banniere'); b.className = 'baie-banniere ' + type; b.textContent = texte;
  clearTimeout(baieBanniere);
}
function baieErreur(texte) {
  baieAnim = {};
  baieMessage('ERREUR DE SÉQUENCE : ' + texte.replace(/^ERREUR : /, ''), 'erreur');
  const r = $('baie-rack'); r.classList.remove('erreur'); void r.offsetWidth; r.classList.add('erreur');
  setTimeout(() => r.classList.remove('erreur'), 1500);
}

function baieDessiner() {
  const d = baieDonnees, rack = $('baie-rack');
  if (!d || !rack) return;
  rack.innerHTML = '';
  let enLigne = 0;
  for (const s of d.serveurs) {
    const debut = baieAnim[s.id], t = debut ? Date.now() - debut : null;
    const boot = s.en_ligne && t !== null && t < BAIE_DUREE_BOOT;
    if (s.en_ligne && !boot) enLigne++;
    const u = el('div', 'baie-u' + (boot ? ' boot' : s.en_ligne ? ' ok' : ''));
    const led = el('div', 'baie-led');
    const nom = el('div', 'baie-nom', s.nom); nom.appendChild(el('small', '', s.ip));
    const etat = el('div', 'baie-etat', boot ? 'DÉMARRAGE… ' + Math.round(t / BAIE_DUREE_BOOT * 100) + ' %' : s.en_ligne ? 'EN LIGNE' : 'HORS SERVICE (corrompu)');
    const prog = el('div', 'baie-prog'); const barre = el('div'); barre.style.width = boot ? Math.round(t / BAIE_DUREE_BOOT * 100) + '%' : '0'; prog.appendChild(barre);
    const centre = el('div'); centre.append(nom, etat, prog);
    const bt = el('div', 'baie-bouton', 'BOUTON'); bt.appendChild(el('b', '', s.bouton));
    u.append(led, centre, bt);
    rack.appendChild(u);
  }
  $('baie-compteur').textContent = enLigne + '/4 serveurs en ligne';
  const j = $('baie-journal');
  const bas = j.scrollTop + j.clientHeight >= j.scrollHeight - 20;
  j.innerHTML = '';
  for (const l of d.journal) {
    const ligne = el('div', l.type, '[' + new Date(l.ts * 1000).toLocaleTimeString('fr-FR') + '] ' + l.texte);
    j.appendChild(ligne);
  }
  if (!d.journal.length) j.textContent = 'Aucun redémarrage pour l\'instant.';
  if (bas) j.scrollTop = j.scrollHeight;
  const dep = $('baie-deps');
  if (dep && !dep.dataset.fait) {
    dep.dataset.fait = '1';
    dep.innerHTML = '<b>Dépendances connues :</b> ' + d.dependances.map(x => x.de + ' → ' + x.vers).join(' ; ');
  }
}
