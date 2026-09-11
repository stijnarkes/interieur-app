import "./bootstrap";
import { initQuiz } from "./quiz/quiz.js";
import { loadRemoteQuizConfig } from "./quiz/remoteConfig.js";

const root = document.getElementById("quizRoot");
if (root) {
  // initQuiz() bouwt de pagina meteen op met de statische ingebouwde inhoud, zodat er niets
  // "flitst" zodra de live inhoud straks binnenkomt. De startknop blijft echter uit tot die live
  // inhoud (of het definitieve falen ervan) binnen is — zonder die wachtstap zou een bezoeker die
  // direct op "Start" klikt de verouderde meegebundelde vragenlijst (data.js) kunnen krijgen in
  // plaats van wat een admin er intussen van gemaakt heeft (zie remoteConfig.js).
  const startBtn = root.querySelector("#startQuizBtn");
  const startBtnDefaultLabel = startBtn.textContent;
  startBtn.disabled = true;
  startBtn.textContent = "Bezig met laden...";

  initQuiz(root);

  loadRemoteQuizConfig().finally(() => {
    startBtn.disabled = false;
    startBtn.textContent = startBtnDefaultLabel;
  });
}
