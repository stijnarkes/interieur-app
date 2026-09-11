import "./bootstrap";
import { initQuiz } from "./quiz/quiz.js";
import { loadRemoteQuizConfig } from "./quiz/remoteConfig.js";

const root = document.getElementById("quizRoot");
if (root) {
  // De vragen/opties zijn volledig admin-beheerd (zie data.js) — er is geen zinvolle statische
  // inhoud om de quiz vast mee te starten, dus initQuiz() draait pas zodra /api/quiz-config
  // daadwerkelijk is opgehaald. Lukt dat niet, dan wordt de startknop hergebruikt als
  // "Opnieuw proberen" i.p.v. de bezoeker een gok-vragenlijst voor te schotelen.
  const startBtn = root.querySelector("#startQuizBtn");
  const loadError = root.querySelector("#quizLoadError");
  const startBtnDefaultLabel = startBtn.textContent;

  async function boot() {
    startBtn.onclick = null;
    startBtn.disabled = true;
    startBtn.textContent = "Bezig met laden...";
    loadError.hidden = true;

    const loaded = await loadRemoteQuizConfig();

    if (!loaded) {
      startBtn.disabled = false;
      startBtn.textContent = "Opnieuw proberen";
      startBtn.onclick = boot;
      loadError.hidden = false;
      return;
    }

    startBtn.disabled = false;
    startBtn.textContent = startBtnDefaultLabel;
    initQuiz(root);
  }

  boot();
}
