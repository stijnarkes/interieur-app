import { createOptionCard } from "./optionCard.js";

/** Rendert één vraag met haar foto-opties (aantal is vrij, admin-bepaald). `onSelect` wordt aangeroepen met de (de)geselecteerde option-id. */
function renderQuestionStep(container, question, selectedOptionIds, onSelect) {
  container.innerHTML = "";

  const heading = document.createElement("h2");
  heading.className = "quiz-question-title";
  heading.textContent = question.title;
  container.appendChild(heading);

  const maxSelections = question.maxSelections ?? 1;
  if (maxSelections > 1) {
    const hint = document.createElement("p");
    hint.className = "quiz-question-hint";
    hint.textContent = `Kies maximaal ${maxSelections}.`;
    container.appendChild(hint);
  }

  const grid = document.createElement("div");
  grid.className = "option-grid";

  question.options.forEach((option) => {
    const selected = selectedOptionIds.includes(option.id);
    const card = createOptionCard(option, {
      questionTitle: question.title,
      selected,
      disabled: !selected && maxSelections > 1 && selectedOptionIds.length >= maxSelections,
      imageDisplayMode: question.imageDisplayMode,
      onSelect,
    });
    grid.appendChild(card);
  });

  container.appendChild(grid);
}

export { renderQuestionStep };
