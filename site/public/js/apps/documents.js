/* DOCUMENTS ET CORBEILLE : l'explorateur de fichiers
   Application du bureau. Utilise les outils de app.js (el, svg, action, toast, creerFenetre, S, appliquerEtat...). */

let DOCS = [], PHOTOS = [], docsConnus = null;
let docDossier = 'Documentation', docSel = null;
const DOSSIERS = [['Documentation', '📚'], ['Rapports', '📊'], ['Mes documents', '📁'], ['Corbeille', '🗑️']];
const CHEMINS = { 'Documentation': 'Documents\\Documentation', 'Rapports': 'Documents\\Rapports', 'Mes documents': 'Documents\\Perso', 'Corbeille': 'Corbeille' };

async function chargerDocuments(notifier) {
  let d;
  try { d = await action('documents'); } catch (e) { return; }
  if (!d.documents) return;
  const ids = new Set([...d.documents.map(x => 'd:' + x.id), ...d.photos.map(x => 'p:' + x.id)]);
  if (docsConnus !== null && notifier === true) {
    const nd = d.documents.filter(x => !docsConnus.has('d:' + x.id) && x.dossier !== 'Corbeille');
    if (nd.length) toast('Nouveau document', nd.map(x => x.titre).join(', ') + '\n(application Documents)', 'ok');
    const np = d.photos.filter(x => !docsConnus.has('p:' + x.id));
    if (np.length) toast('Nouvelle photo', np.map(x => x.titre).join(', ') + '\n(application Photos)', 'ok');
  }
  DOCS = d.documents; PHOTOS = d.photos; docsConnus = ids;
  dessinerExplorateur(); dessinerGalerie();
  if (typeof navMaj === 'function') navMaj();
}

function iconeFichier(titre) {
  if (/\.exe$/i.test(titre)) return '⚠️';
  if (/\.pdf/i.test(titre)) return '📕';
  if (/\.csv$/i.test(titre)) return '📊';
  return '📄';
}

function construireExplorateur(geo) {
  const corps = el('div', 'expl');
  corps.innerHTML = '<div class="expl-barre" id="expl-chemin"></div><div class="expl-main"><div class="expl-arbre" id="expl-arbre"></div><div class="expl-liste" id="expl-liste"></div><div class="expl-vue" id="expl-vue"><div class="expl-vide">Sélectionnez un fichier.</div></div></div>';
  creerFenetre('docs', 'Explorateur de fichiers', geo, corps).auOuvrir = () => chargerDocuments(false);
}

function ouvrirDocuments(dossier, docId) {
  if (dossier) docDossier = dossier;
  fenetres.docs.ouvrir();
  dessinerExplorateur();
  if (docId) lireDoc(docId);
}

function dessinerExplorateur() {
  if (!$('expl-arbre')) return;
  $('expl-chemin').textContent = 'C:\\Utilisateurs\\Kovac\\' + (CHEMINS[docDossier] || docDossier);
  const arbre = $('expl-arbre'); arbre.innerHTML = '';
  for (const [nom, ico] of DOSSIERS) {
    const n = DOCS.filter(d => d.dossier === nom).length;
    const li = el('div', 'expl-dossier' + (nom === docDossier ? ' sel' : ''));
    li.append(el('div', '', ico + ' ' + nom), el('span', 'n', String(n)));
    li.onclick = () => { docDossier = nom; docSel = null; dessinerExplorateur(); $('expl-vue').innerHTML = '<div class="expl-vide">Sélectionnez un fichier.</div>'; };
    arbre.appendChild(li);
  }
  const liste = $('expl-liste'); liste.innerHTML = '';
  const fichiers = DOCS.filter(d => d.dossier === docDossier);
  if (!fichiers.length) liste.appendChild(el('div', 'expl-vide', 'Dossier vide.'));
  for (const f of fichiers) {
    const it = el('div', 'expl-fichier' + (docSel === f.id ? ' sel' : ''));
    it.append(el('span', 'ic', iconeFichier(f.titre)), el('span', '', f.titre));
    it.onclick = () => lireDoc(f.id);
    liste.appendChild(it);
  }
}

function lireDoc(id) {
  const f = DOCS.find(d => d.id === id);
  if (!f) return;
  docSel = id; docDossier = f.dossier;
  dessinerExplorateur();
  const v = $('expl-vue'); v.innerHTML = '';
  v.appendChild(el('h2', '', f.titre));
  v.appendChild(el('div', 'meta', f.dossier + ' · ' + (/\.pdf/i.test(f.titre) ? 'Document PDF' : /\.exe$/i.test(f.titre) ? 'Fichier mis en quarantaine' : 'Fichier texte')));
  const pre = el('pre', '', f.texte);
  if (/\.pdf/i.test(f.titre)) { const p = el('div', 'papier'); p.appendChild(pre); v.appendChild(p); } else v.appendChild(pre);
  if (f.dossier === 'Corbeille') {
    const b = el('button', '', '↩ Restaurer');
    b.onclick = () => toast('Corbeille', 'Restauration impossible : droits insuffisants.', 'alerte');
    v.appendChild(b);
  }
}
