/* COFFRE ET CLÉ USB D'URGENCE
   Application du bureau. Déverrouille le servomoteur avec le code à 4 chiffres (4117),
   détecte la clé USB physique sur le PC et permet le Drag & Drop du fichier urgence.sh. */

let coffreStatut = 'FERME';
let coffreUsbDetectee = false;
let coffreTimerSync = null;

function construireCoffre(geo) {
  const corps = el('div', 'coffre-app');
  corps.id = 'coffre-corps';
  corps.innerHTML = `
    <div class="coffre-panneau">
      <div class="coffre-header">
        <div class="coffre-badge" id="coffre-badge">🔒 COFFRE PHYSIQUE : VERROUILLÉ</div>
        <p class="coffre-desc">Module de contrôle du coffre-fort physique de la cellule Zéro-Jour.</p>
      </div>

      <!-- ÉTAPE 1 : CODE DE SÉCURITÉ DU SUPERVISEUR -->
      <div id="coffre-etape-code" class="coffre-section">
        <h3>1. Code d'autorisation superviseur</h3>
        <p class="coffre-sous-titre">Saisissez le code à 4 chiffres trouvé dans la base du personnel (habilitations d'urgence) :</p>
        <div class="coffre-code-input-group">
          <input type="text" id="coffre-code-val" maxlength="4" placeholder="••••" autocomplete="off" spellcheck="false" inputmode="numeric">
          <button id="coffre-btn-valider" class="btn-coffre">Déverrouiller le coffre</button>
        </div>
        <div id="coffre-code-msg" class="coffre-msg"></div>
      </div>

      <!-- ÉTAPE 2 : INSERTION DE LA CLÉ USB PHYSIQUE -->
      <div id="coffre-etape-usb" class="coffre-section" style="display:none">
        <div class="coffre-alerte-succes">
          <span class="ico">🔓</span>
          <div>
            <strong>Coffre déverrouillé !</strong>
            <p>Le servomoteur s'est actionné (90°). Le coffre physique est ouvert. Récupérez la clé USB physique à l'intérieur et branchez-la au PC.</p>
          </div>
        </div>

        <h3>2. Connexion du support physique</h3>
        <div id="coffre-usb-status" class="coffre-usb-status en-attente">
          <span class="usb-pulse"></span>
          <span id="coffre-usb-texte">En attente de la clé USB physique sur le PC...</span>
        </div>

        <!-- ZONE DE DRAG & DROP -->
        <div id="coffre-drop-container" style="display:none; margin-top:16px;">
          <h3>3. Injection de la procédure d'urgence</h3>
          <p class="coffre-sous-titre">Ouvrez <strong>Documents</strong>, cliquez sur <strong>Clé USB</strong>, puis glissez-déposez le fichier <strong>urgence.sh</strong> ci-dessous :</p>
          <div id="coffre-drop-zone" class="coffre-drop-zone">
            <div class="drop-ico">📥</div>
            <div class="drop-titre">Glissez-déposez urgence.sh ici</div>
            <div class="drop-sous">ou cliquez pour injecter directement</div>
          </div>
          <div id="coffre-inject-msg" class="coffre-msg" style="margin-top:10px;"></div>
        </div>
      </div>

      <!-- ÉTAPE 3 : PROCÉDURE CHARGÉE -->
      <div id="coffre-etape-fini" class="coffre-section" style="display:none">
        <div class="coffre-alerte-succes">
          <span class="ico">⚡</span>
          <div>
            <strong>Procédure urgence.sh injectée !</strong>
            <p>L'alarme générale a été déclenchée. Ouvrez l'application <strong>urgence.sh</strong> pour réparer le code à trous et couper la fuite.</p>
          </div>
        </div>
        <button id="coffre-btn-editeur" class="btn-coffre" style="margin-top:12px;">Ouvrir l'éditeur urgence.sh</button>
      </div>
    </div>
  `;

  const f = creerFenetre('coffre', 'Coffre physique & Clé USB d\'urgence', geo, corps);
  f.auOuvrir = () => {
    majAffichageCoffre();
    if (!coffreTimerSync) coffreTimerSync = setInterval(verifierUsbEnDirect, 2000);
  };

  // Événements du formulaire de code
  const btnValider = $('coffre-btn-valider');
  const inputCode = $('coffre-code-val');
  if (btnValider && inputCode) {
    btnValider.onclick = soumettreCodeCoffre;
    inputCode.onkeydown = e => { if (e.key === 'Enter') soumettreCodeCoffre(); };
  }

  // Événements Drag & Drop
  const dropZone = $('coffre-drop-zone');
  if (dropZone) {
    dropZone.ondragover = e => {
      e.preventDefault();
      dropZone.classList.add('dragover');
    };
    dropZone.ondragleave = () => {
      dropZone.classList.remove('dragover');
    };
    dropZone.ondrop = e => {
      e.preventDefault();
      dropZone.classList.remove('dragover');
      const fichier = e.dataTransfer.getData('application/lerna-file') || e.dataTransfer.getData('text/plain');
      coffreInjecterFichier(fichier || 'urgence.sh');
    };
    dropZone.onclick = () => coffreInjecterFichier('urgence.sh');
  }

  const btnEditeur = $('coffre-btn-editeur');
  if (btnEditeur) {
    btnEditeur.onclick = () => {
      if (typeof fenetres !== 'undefined' && fenetres.script) fenetres.script.ouvrir();
    };
  }
}

async function soumettreCodeCoffre() {
  const code = ($('coffre-code-val')?.value || '').trim();
  const msg = $('coffre-code-msg');
  if (!code) {
    if (msg) { msg.textContent = 'Veuillez saisir un code à 4 chiffres.'; msg.className = 'coffre-msg ko'; }
    return;
  }
  try {
    const d = await action('coffre_code', { code });
    appliquerEtat(d);
    if (d.ok) {
      if (msg) { msg.textContent = d.message; msg.className = 'coffre-msg ok'; }
      toast('Coffre déverrouillé !', 'Le servomoteur s\'est ouvert. Insérez la clé USB physique.', 'ok');
      majAffichageCoffre();
    } else {
      if (msg) { msg.textContent = d.message; msg.className = 'coffre-msg ko'; }
      toast('Erreur code', d.message, 'alerte');
    }
  } catch (err) {
    if (msg) { msg.textContent = 'Erreur de communication avec le serveur.'; msg.className = 'coffre-msg ko'; }
  }
}

async function verifierUsbEnDirect() {
  if (S.step < S_AUTH && S.coffre !== 'OUVERT') return;
  try {
    const d = await action('detecter_usb');
    if (d.detectee !== coffreUsbDetectee) {
      coffreUsbDetectee = d.detectee;
      if (d.detectee) {
        toast('Clé USB détectée', `Lecteur ${d.lecteur || 'D:'} connecté. Fichier urgence.sh disponible dans Documents.`, 'ok');
        if (typeof chargerDocuments === 'function') chargerDocuments(false);
      }
      majAffichageCoffre();
    }
  } catch (e) {}
}

async function coffreInjecterFichier(nom) {
  const msg = $('coffre-inject-msg');
  try {
    const d = await action('coffre_injecter', { fichier: nom });
    appliquerEtat(d);
    if (d.ok) {
      if (msg) { msg.textContent = d.message; msg.className = 'coffre-msg ok'; }
      toast('Procédure injectée !', 'Alarme générale déclenchée. Ouvrez urgence.sh.', 'alerte');
      majAffichageCoffre();
      if (typeof fenetres !== 'undefined' && fenetres.script) {
        setTimeout(() => fenetres.script.ouvrir(), 600);
      }
    } else {
      if (msg) { msg.textContent = d.message; msg.className = 'coffre-msg ko'; }
    }
  } catch (e) {
    if (msg) { msg.textContent = 'Erreur serveur lors de l\'injection.'; msg.className = 'coffre-msg ko'; }
  }
}

function majAffichageCoffre() {
  const badge = $('coffre-badge');
  const secCode = $('coffre-etape-code');
  const secUsb = $('coffre-etape-usb');
  const secFini = $('coffre-etape-fini');
  const usbStatus = $('coffre-usb-status');
  const usbTexte = $('coffre-usb-texte');
  const dropCont = $('coffre-drop-container');

  const estOuvert = S.coffre === 'OUVERT' || S.step >= S_AUTH;
  const estAlarme = S.step >= S_ALARME;

  if (badge) {
    badge.textContent = estOuvert ? '🔓 COFFRE PHYSIQUE : DÉVERROUILLÉ (90°)' : '🔒 COFFRE PHYSIQUE : VERROUILLÉ';
    badge.className = 'coffre-badge ' + (estOuvert ? 'ouvert' : 'ferme');
  }

  if (estAlarme) {
    if (secCode) secCode.style.display = 'none';
    if (secUsb) secUsb.style.display = 'none';
    if (secFini) secFini.style.display = 'block';
    return;
  }

  if (estOuvert) {
    if (secCode) secCode.style.display = 'none';
    if (secUsb) secUsb.style.display = 'block';
    if (secFini) secFini.style.display = 'none';

    // Statut USB
    const usb = S.usb || {};
    const detectee = usb.detectee || coffreUsbDetectee;
    if (usbStatus && usbTexte) {
      if (detectee) {
        usbStatus.className = 'coffre-usb-status connectee';
        usbTexte.innerHTML = `✅ <strong>Clé USB physique détectée !</strong> (${usb.lecteur || 'D:'})`;
        if (dropCont) dropCont.style.display = 'block';
      } else {
        usbStatus.className = 'coffre-usb-status en-attente';
        usbTexte.textContent = 'En attente de la clé USB physique sur le PC...';
        if (dropCont) dropCont.style.display = 'none';
      }
    }
  } else {
    if (secCode) secCode.style.display = 'block';
    if (secUsb) secUsb.style.display = 'none';
    if (secFini) secFini.style.display = 'none';
  }
}

