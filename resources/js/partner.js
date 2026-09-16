import "./bootstrap";
import { initInvitePage } from "./partner/invitePage.js";
import { initResultPage } from "./partner/resultPage.js";

// Losse, kleine Vite-entry voor de twee publieke partnerpagina's (zie routes/web.php) — draait
// nooit tegelijk met #quizRoot op de hoofdpagina (die laadt app.js, niet dit bestand), zie
// resources/views/layouts/app.blade.php's $viteEntries-wissel.
const inviteRoot = document.getElementById("partnerInviteRoot");
if (inviteRoot) {
  initInvitePage(inviteRoot);
}

const resultRoot = document.getElementById("partnerResultRoot");
if (resultRoot) {
  initResultPage(resultRoot);
}
