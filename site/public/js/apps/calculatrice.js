/* CALCULATRICE
   Application du bureau. Utilise les outils de app.js (el, svg, action, toast, creerFenetre, S, appliquerEtat...). */

let calc = { affiche: '0', acc: null, op: null, neuf: true, histo: '' };

function construireCalculatrice(geo) {
  const corps = el('div', 'calc');
  corps.innerHTML = '<div class="calc-ecran"><small id="calc-histo"></small><span id="calc-val">0</span></div><div class="calc-touches" id="calc-touches"></div>';
  creerFenetre('calc', 'Calculatrice', geo, corps);
  const touches = [['C', 'eff'], ['⌫', ''], ['%', 'op'], ['÷', 'op'], ['7'], ['8'], ['9'], ['×', 'op'], ['4'], ['5'], ['6'], ['−', 'op'], ['1'], ['2'], ['3'], ['+', 'op'], ['±'], ['0'], ['.'], ['=', 'egal']];
  for (const [t, cls] of touches) {
    const b = el('button', cls || '', t);
    b.onclick = () => calcTouche(t);
    $('calc-touches').appendChild(b);
  }
}

function calcAppliquer(a, op, b) {
  switch (op) { case '+': return a + b; case '−': return a - b; case '×': return a * b; case '÷': return b === 0 ? NaN : a / b; }
  return b;
}
function calcFormat(n) { return Number.isFinite(n) ? String(Math.round(n * 1e10) / 1e10) : 'Erreur'; }

function calcTouche(t) {
  const c = calc;
  if (/^\d$/.test(t)) { c.affiche = c.neuf || c.affiche === '0' ? t : c.affiche + t; c.neuf = false; }
  else if (t === '.') { if (c.neuf) { c.affiche = '0.'; c.neuf = false; } else if (!c.affiche.includes('.')) c.affiche += '.'; }
  else if (t === 'C') calc = { affiche: '0', acc: null, op: null, neuf: true, histo: '' };
  else if (t === '⌫') { if (!c.neuf) c.affiche = c.affiche.length > 1 ? c.affiche.slice(0, -1) : '0'; }
  else if (t === '±') { c.affiche = c.affiche.startsWith('-') ? c.affiche.slice(1) : (c.affiche === '0' ? '0' : '-' + c.affiche); }
  else if (t === '%') { c.affiche = calcFormat(parseFloat(c.affiche) / 100); c.neuf = true; }
  else if (t === '=') {
    if (c.op !== null) { const r = calcAppliquer(c.acc, c.op, parseFloat(c.affiche)); c.histo = c.histo + ' ' + c.affiche + ' ='; c.affiche = calcFormat(r); c.acc = null; c.op = null; c.neuf = true; }
  } else {
    const v = parseFloat(c.affiche);
    if (c.op !== null && !c.neuf) { const r = calcAppliquer(c.acc, c.op, v); c.acc = r; c.affiche = calcFormat(r); } else c.acc = v;
    c.op = t; c.neuf = true; c.histo = calcFormat(c.acc) + ' ' + t;
  }
  $('calc-val').textContent = calc.affiche; $('calc-histo').textContent = calc.histo;
}
