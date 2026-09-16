// Bewaart uitsluitend het toegangstoken van dít apparaat voor een gegeven uitnodigingscode — nooit
// server-side/sessie-gebaseerd, exact hetzelfde patroon als resources/js/quiz/state.js gebruikt
// voor de quizvoortgang. Dit is hoe "hervatten op hetzelfde apparaat" werkt (zie het
// implementatieplan, sectie "Tokens en toegang"): vóór het claimen checkt de uitnodigingspagina
// eerst of hier al een bewaard toegangstoken voor déze code staat.
const PREFIX = "interieur_partner_v1_";

function keyFor(inviteToken) {
  return `${PREFIX}${inviteToken}`;
}

function savePartnerAccess(inviteToken, { accessToken, role }) {
  try {
    window.localStorage.setItem(keyFor(inviteToken), JSON.stringify({ accessToken, role }));
  } catch {
    // localStorage niet beschikbaar (bv. privémodus) — claimen blijft werken, alleen zonder
    // herstel bij een latere terugkeer op ditzelfde apparaat.
  }
}

function loadPartnerAccess(inviteToken) {
  try {
    const raw = window.localStorage.getItem(keyFor(inviteToken));
    if (!raw) return null;
    const parsed = JSON.parse(raw);
    return parsed && typeof parsed.accessToken === "string" ? parsed : null;
  } catch {
    return null;
  }
}

export { savePartnerAccess, loadPartnerAccess };
