/* ÉDITEUR DE CODE À TROUS (urgence.sh)
   Application du bureau. Utilise les outils de app.js (el, svg, action, toast, creerFenetre, S, appliquerEtat...). */

let edChamps = [], edDernier = null, edFini = false;

function construireEditeur(geo) {
  const corps = el('div', 'editeur');
  corps.innerHTML = `<div class="editeur-code" id="ed-code"></div>
    <div class="editeur-bas"><div class="banque" id="ed-banque"></div>
      <div class="editeur-actions"><button class="test" id="ed-test">▶ Tester la procédure</button><span id="ed-msg"></span></div></div>`;
  const f = creerFenetre('script', 'urgence.sh - Éditeur de code', geo, corps);
  f.auOuvrir = chargerEditeur;
  $('ed-test').onclick = testerScript;
}

async function chargerEditeur() {
  if (edChamps.length) return;   // déjà construit : on garde ce que le joueur a tapé
  let d;
  try { d = await action('script'); } catch (e) { return; }
  const code = $('ed-code');
  if (d.erreur) { code.innerHTML = ''; code.appendChild(el('div', 'verrouille', d.erreur)); return; }
  code.innerHTML = ''; $('ed-banque').innerHTML = '';
  d.lignes.forEach((ligne, i) => {
    const l = el('div', 'ligne'); l.appendChild(el('span', 'n', String(i + 1)));
    ligne.split(/\{(\d+)\}/).forEach((morceau, k) => {
      if (k % 2 === 1) {
        const inp = el('input', 'trou'); inp.type = 'text'; inp.spellcheck = false; inp.autocomplete = 'off';
        inp.size = 6; inp.dataset.n = morceau; inp.placeholder = '(' + morceau + ')'; inp.onfocus = () => { edDernier = inp; };
        inp.onkeydown = e => { if (e.key === 'Enter') testerScript(); };
        edChamps[Number(morceau) - 1] = inp; l.appendChild(inp);
      } else {
        const c = morceau.indexOf('#');
        if (c < 0) l.appendChild(document.createTextNode(morceau));
        else { l.appendChild(document.createTextNode(morceau.slice(0, c))); l.appendChild(el('span', 'com', morceau.slice(c))); }
      }
    });
    code.appendChild(l);
  });
  for (const mot of d.banque) {
    const b = el('button', '', mot);
    b.onclick = () => {
      const cible = (edDernier && !edFini) ? edDernier : edChamps.find(c => c && !c.value);
      if (cible) { cible.value = mot; cible.classList.remove('ok', 'ko'); cible.focus(); }
    };
    $('ed-banque').appendChild(b);
  }
  if (d.reussi) editeurReussi();
}

function editeurReussi() {
  edFini = true;
  edChamps.forEach(c => { c.classList.add('ok'); c.readOnly = true; });
  $('ed-test').disabled = true;
  $('ed-msg').style.color = '#a6e3a1';
  $('ed-msg').textContent = 'Procédure réparée. Le kill switch est armé : appuyez sur le bouton ARRÊT de la baie !';
}

async function testerScript() {
  if (edFini) return;
  $('ed-test').disabled = true;
  try {
    const d = await action('script_verifier', { reponses: edChamps.map(c => c.value) });
    if (d.erreur) { $('ed-msg').textContent = d.erreur; return; }
    appliquerEtat(d);
    let justes = 0;
    edChamps.forEach((c, i) => { const ok = d.trous[i + 1]; c.classList.toggle('ok', ok); c.classList.toggle('ko', !ok); if (ok) justes++; });
    if (d.ok) editeurReussi();
    else {
      $('ed-msg').style.color = '#f38ba8';
      $('ed-msg').textContent = justes + ' trou' + (justes > 1 ? 's' : '') + ' juste' + (justes > 1 ? 's' : '') + ' sur ' + edChamps.length + '. +3 % de fuite. Les trous rouges sont à revoir.';
    }
  } catch (e) { $('ed-msg').textContent = 'Serveur injoignable.'; }
  if (!edFini) $('ed-test').disabled = false;
}
