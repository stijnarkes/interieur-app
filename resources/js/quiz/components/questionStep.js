import { createOptionCard } from "./optionCard.js";

/**
 * Vertelt bij elke vraag consequent hoeveel opties gekozen mogen worden — voorheen ontbrak dit
 * bij vragen met precies 1 keuze, en stond het bij meerkeuzevragen als kaal maximum ("Kies
 * maximaal 2."). Vriendelijkere, consistente formulering voor de twee gebruikte waarden; een
 * eventueel groter, zelden gebruikt maximum (admin staat tot 10 toe) valt terug op een generieke
 * zin i.p.v. te crashen of stil te blijven.
 */
function selectionHintFor(maxSelections) {
  if (maxSelections <= 1) return "Kies je favoriet.";
  if (maxSelections === 2) return "Kies één of twee favorieten.";

  return `Kies tot ${maxSelections} favorieten.`;
}

/** Rendert één vraag met haar foto-opties (aantal is vrij, admin-bepaald). `onSelect` wordt aangeroepen met de (de)geselecteerde option-id. */
function renderQuestionStep(container, question, selectedOptionIds, onSelect) {
  container.innerHTML = "";

  const heading = document.createElement("h2");
  heading.className = "quiz-question-title";
  heading.textContent = question.title;
  container.appendChild(heading);

  const maxSelections = question.maxSelections ?? 1;
  const hint = document.createElement("p");
  hint.className = "quiz-question-hint";
  hint.textContent = selectionHintFor(maxSelections);
  container.appendChild(hint);

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
