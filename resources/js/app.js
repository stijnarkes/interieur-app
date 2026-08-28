import "./bootstrap";
import { initQuiz } from "./quiz/quiz.js";
import { loadRemoteQuizConfig } from "./quiz/remoteConfig.js";

const root = document.getElementById("quizRoot");
if (root) {
  // initQuiz() start meteen, met de statische ingebouwde inhoud — knoppen moeten direct werken.
  // De admin-aangepaste inhoud van /api/quiz-config wordt op de achtergrond ingeladen en muteert
  // de gedeelde QUESTIONS/PALETTE_OPTIONS/STYLE_PROFILES in place zodra hij binnenkomt (zie
  // remoteConfig.js); een trage of falende fetch mag de quiz dus nooit laten wachten.
  initQuiz(root);
  loadRemoteQuizConfig();
}
