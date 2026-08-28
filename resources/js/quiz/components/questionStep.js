import { createOptionCard } from "./optionCard.js";

/** Rendert één vraag met 6 foto-opties. `onSelect` wordt aangeroepen met de gekozen option-id. */
function renderQuestionStep(container, question, selectedOptionId, onSelect) {
  container.innerHTML = "";

  const heading = document.createElement("h2");
  heading.className = "quiz-question-title";
  heading.textContent = question.title;
  container.appendChild(heading);

  const grid = document.createElement("div");
  grid.className = "option-grid";

  question.options.forEach((option) => {
    const card = createOptionCard(option, {
      questionTitle: question.title,
      selected: option.id === selectedOptionId,
      onSelect,
    });
    grid.appendChild(card);
  });

  container.appendChild(grid);
}

export { renderQuestionStep };
