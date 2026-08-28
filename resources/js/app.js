import "./bootstrap";
import { initQuiz } from "./quiz/quiz.js";
import { loadRemoteQuizConfig } from "./quiz/remoteConfig.js";

const root = document.getElementById("quizRoot");
if (root) {
  loadRemoteQuizConfig().finally(() => initQuiz(root));
}
